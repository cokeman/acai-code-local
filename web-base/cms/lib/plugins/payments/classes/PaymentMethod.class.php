<?php
require_once __DIR__."/PaymentHelper.class.php";

abstract class PaymentMethod {
    private static $css = [];
    private static $js = [];
    protected static $config = null;
    
    // Define si estamos en modo test o no
    protected static $isTest = false;
    // ID del tipo de pago
    private $payment_id = null;
    // Record ID para pagos aplazados
    public $payment_record_id = null;
    // Tipo de pago
    private $payment_name = null;
    // URL Test
    public $test_url = null;
    // Array con las credenciales test
    public $test_credentials = null;
    // URL Producción
    public $prod_url = null;
    // Array con las credenciales producción
    public $prod_credentials = null;
    // Cantidad a pagar
    private $quantity = 0;
    // Clase paypal que realiza el envío de formulario
    public $helper = null;
    // Referencia del pedido
    protected $reference = null;
    // Descripción del pedido
    protected $description = '';

    public $url_ok = null;
    public $url_ko = null;

    /**
     * Añade un archivo CSS a la lista para su importación
     * 
     * @param string path
     * @return void
     */
    static function add_css($path) {
        $filename = basename($path);
        self::$css[$filename] = $path;
    }

    /**
     * Devuelve la lista de links a importar
     * 
     * @return array
     */
    static function get_links() {
        $links = '';
        foreach (self::$css as $css) {
            $links .= '<link href="'.h($css).'" rel="stylesheet"';
        }
        return $links;
    }

    /**
     * Añade un archivo JS a la lista para su importación
     * 
     * @param string path
     * @return void
     */
    static function add_js($path) {
        $filename = basename($path);
        self::$js[$filename] = $path;
    }

    /**
     * Devuelve la lista de links a importar
     * 
     * @return array
     */
    static function get_scripts() {
        $links = '';
        foreach (self::$css as $css) {
            $links .= '<link href="'.h($css).'" rel="stylesheet"';
        }
        return $links;
    }

    /**
     * Define si los pagos están en modo test o producción
     * 
     * @param bool test
     * @return void
     */
    static function set_test($test = true) {
        self::$isTest = $test ? true : false;
    }

    // Dani (2025-02-06): Añadida función.
    public function isTest() {
        return self::$isTest;
    }

    /**
     * Devuelve la configuración del plugin
     *
     * @return array
     */
    static function get_config() {
        if (self::$config) return self::$config;

        // Dani (2024-10-04): En clementime, la configuración de los métodos de
        // pagos están en la tabla del cliente.
        if (
            isset($_SERVER['SERVER_NAME'])
            && $_SERVER['SERVER_NAME'] === 'clementime.cocosolution.com'
            && isset($_GET['client'])
        ) {
            $num = mysql_real_escape_string($_GET['client']);
            $cliente = mysql_fetch_assoc(mysql_query("
                SELECT *
                FROM `cms_clientes`
                WHERE `num` = '{$num}'
            "));

            self::$config = [
                'test' => strpos($cliente['entorno_tpv'] ?? '', 'sis-t') !== false,
                'test_stripe_pk' => $cliente['clave_publica_stripe'],
                'test_stripe_sk' => $cliente['clave_privada_stripe'],
                'test_webhook_sk' => '',
                'stripe_pk' => $cliente['clave_publica_stripe'],
                'stripe_sk' => $cliente['clave_privada_stripe'],
                'webhook_sk' => '',
                'test_paypal_account' => $cliente['cuenta_paypal'],
                'paypal_account' => $cliente['cuenta_paypal'],
                'test_tpv_merchant' => $cliente['merchant_code'],
                'test_tpv_currency' => $cliente['merchant_currency'],
                'test_tpv_terminal' => $cliente['terminal'],
                'test_tpv_key' => $cliente['clave_tpv'],
                'tpv_merchant' => $cliente['merchant_code'],
                'tpv_currency' => $cliente['merchant_currency'],
                'tpv_terminal' => $cliente['terminal'],
                'tpv_key' => $cliente['clave_tpv'],
                'aplazame_public_key' => '',
                'aplazame_private_key' => '',
                'clave_debug' => 'Coco$olut10n',
            ];

            return self::$config;
        }

        self::$config = loadINI(__DIR__."/../custom-schema.ini.php")['config'];    

        $result = mysql_query("SHOW TABLES LIKE 'aux_plg_config'");
        if($result->num_rows == 1) {
            
            $result = mysql_query_fetch_all_assoc("select * from aux_plg_config where plugin='payments'");
            if (@$result){
                foreach($result as $record){
                    if (@$record["config"]){
                        foreach(json_decode($record["config"],true) as $confBD){
                            if (@$confBD["padre"] == "config") {
                                self::$config[$confBD["campo"]] = $confBD["valor"];
                            }
                        }
                    }
                }
            }
        }
        
        if (self::$config['test']) {
            self::set_test(true);
        } 
        
        return self::$config;
    }

    /**
     * Parsea un número quitándole las comas
     *
     * @param string $price
     * @return float
     */
    static function parse_number($price) {
        return floatval(str_replace(',', '.', $price));
    }

    /**
     * Devuelve las credenciales oportunas según estemos en modo test o en producción
     *
     * @return array
     */
    function get_credentials() {
        return self::$isTest ? $this->test_credentials : $this->prod_credentials;
    }

    /**
     * Devuelve el ID de pago
     *
     * @return int
     */
    function get_payment_id() {
        return $this->payment_id;
    }

    /**
     * Devuelve la descripción del producto
     * 
     * @return string
     */
    function get_product_description() {
        return $this->description;
    }

    /**
     * Define la descripción del producto
     * 
     * @param string $description
     * @return void
     */
    function set_product_description($description) {
        $this->description = $description;
    }

    /**
     * Devuelve la URL de test o producción
     *
     * @return string
     */
    protected function get_url() {
        return self::$isTest ? $this->test_url : $this->prod_url;
    }

    /**
     * Inicializa el método de pago para ser usado
     *
     * @param int $payment_id
     * @param string $payment_name
     * @param string $test_url
     * @param array $test_credentials
     * @param string $prod_url
     * @param array $prod_credentials
     * @return void
     */
    protected function init($payment_id, $payment_name, $test_url, $test_credentials, $prod_url, $prod_credentials) {
        $this->payment_id = $payment_id;
        $this->payment_name = $payment_name;
        $this->test_url = $test_url;
        $this->test_credentials = $test_credentials;
        $this->prod_url = $prod_url;
        $this->prod_credentials = $prod_credentials;
        $this->reference = time();
        $this->helper = new PaymentHelper;
    }

    /**
     * Devuelve True si el tipo de pago puede ser utilizado (tiene todos los datos rellenos)
     * 
     * @return void
     */
    abstract function can_be_used();

    // Dani (2025-02-06): Añadida función.
    public function setQuantity($quantity) {
        $this->quantity = self::parse($quantity);
    }

    public function setReference($reference) {
        $this->reference = $reference;
    }


    /**
     * Realiza el pago de una cuantía X.
     * El segundo parámetro es un array con las claves `records`: lista de registros, `ipn`: Nombre de la clase IPNAction, `callback`, `urlok`
     * 
     * @param float $quantity
     * @param array $insertData
     * @return void
     */
    function pay($quantity, $insertData = null, $payment = null, $curl = false) {
        $this->quantity = self::parse($quantity);
        if ($this->quantity < 0) {
            // Anael me dijo que puedo quitar el igual en esta condicion el 11 de julio de 2025
            throw new Exception('Cantidad no válida');
        }
        if ($insertData && !is_array($insertData)) {
            throw new Exception('Datos no válidos. Deben ser un array');
        }
        
        if(isset($insertData['urlok'])) $this->url_ok = $insertData['urlok'];
        if(isset($insertData['urlko'])) $this->url_ko = $insertData['urlko'];
        if(isset($insertData['reference'])) $this->reference = $insertData['reference'];
        if(isset($insertData['paymentId'])){
            $this->payment_record_id = $insertData['paymentId'];
            return $this->payment_record_id;
        }else{
            $this->insert(@$insertData['records'], @$insertData['email'], @$insertData['ipn'], @$insertData['callback']);    
            return mysql_insert_id();
        }
        
    }

    /**
     * Crea una suscripción de cuantía X.
     * El segundo parámetro es un array con las claves `records`: lista de registros, `ipn`: Nombre de la clase IPNAction, `callback`, `urlok`
     * 
     * @param float $quantity
     * @param array $insertData
     * @return void
     */
    function subscribe($quantity, $insertData = null) {
        $this->quantity = self::parse($quantity);
        if ($this->quantity <= 0) {
            throw new Exception('Cantidad no válida');
        }
        if ($insertData && !is_array($insertData)) {
            throw new Exception('Datos no válidos. Deben ser un array');
        }
        
        if(isset($insertData['urlok'])) $this->url_ok = $insertData['urlok'];
        if(isset($insertData['urlko'])) $this->url_ko = $insertData['urlko'];

        $this->insert(@$insertData['records'], @$insertData['email'], @$insertData['ipn'], @$insertData['callback'], 1);
    }

    /**
     * Inserta en la tabla de pagos del plugin.
     * @param array $records: Lista de registros
     * @param string $email: Objeto CocoEmail
     * @param string $ipn: Nombre de la clase IPNAction
     * @param callback $callback
     * @param int $tipo: 0 si es pago y 1 suscripción
     */
    // Dani (2025-02-06): Modificada la función de privada a pública.
    public function insert($records = null, $email = null, $ipn = null, $callback = null, $tipo = 0) {
        self::_install();
        $tipo = $tipo === 0 ? 0 : 1;
        # Inserción en tablas de funcionamiento interno
        $d = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'];
        if (is_array($records)) {
            $recordsJson = json_encode($records,JSON_UNESCAPED_UNICODE);
        }
        else if ($records) {
            $recordsJson = json_encode([$records],JSON_UNESCAPED_UNICODE);
        }
        else {
            $recordsJson = json_encode([],JSON_UNESCAPED_UNICODE);
        }
        $recordsJson = mysql_real_escape_string($recordsJson);
        $ipn = mysql_real_escape_string($ipn) ?: 'NULL';
        $request = mysql_real_escape_string(json_encode($_REQUEST ?: [],JSON_UNESCAPED_UNICODE));
        // Dani (2024-10-04): Comentada línea porque no permitía introducir emojis.
        // mysql_set_charset('utf8');
        switch($this->payment_id){
            case 4: $status = "Financiacion Solicitada";break;
            default: $status = "Esperando";
        }
        mysql_query("INSERT INTO `aux_plg_payments` SET num=NULL, createdDate='$d', ip='$ip', tipo=$tipo, price=".$this->quantity.", records='$recordsJson', status='".$status."', ipn_action='$ipn', `error`='', `request`='$request', method='".$this->payment_id."'") or die(mysql_error());
        
        $this->payment_record_id = mysql_insert_id();
        
        # Callback tras inserción
        if ($callback && is_callable($callback)) {
            call_user_func_array($callback, array(&$records, $_REQUEST, $this->payment_record_id));
            if (isset($records["referencia"])) $this->reference = $records["referencia"];
        }
    }

    /**
     * Parsea un precio de string a float
     *
     * @param string $price
     * @return float
     */
    private static function parse($price) {
        return floatval(str_replace(",", ".", $price));
    }

    /**
     * Devuelve la URL del ipn asociada a dicho registro
     *
     * @param int $num
     * @return string
     */
    // Dani (2024-10-04): Añadido parámetro $extraParams para obtener más
    // información en la petición que hace el banco a nuestro ipn.php
    function get_ipn_url($num, $extraParams = []) {
        $params = [
            "ipn" => sha1($num),
            "idioma" => @$_REQUEST["idioma"],
        ];
        $params = array_merge($params, $extraParams);
        $params = http_build_query($params);
        return protocol()."://".$_SERVER["HTTP_HOST"]."/cms/lib/plugins/payments/ipn.php?".$params;
    }

    /**
     * Devuelve la URL de cancelación asociada a dicho registro
     *
     * @param int $num
     * @return string
     */
    function get_cancel_url($num) {
        $params = ['cancel' => sha1($num)];
        if ($this->url_ko) $params['url'] = base64_encode($this->url_ko);
        $params["idioma"] = @$_REQUEST["idioma"];
        $params = http_build_query($params);
        return protocol()."://".$_SERVER["HTTP_HOST"]."/cms/lib/plugins/payments/cancel.php?".$params;
    }

    /**
     * Devuelve la URL de pago realizado
     *
     * @param int $num
     * @return string
     */
    function get_success_url() {
        $params = [];
        if ($this->url_ok) $params['url'] = base64_encode($this->url_ok);
        $params["idioma"] = @$_REQUEST["idioma"];
        $params = http_build_query($params);
        return protocol()."://".$_SERVER["HTTP_HOST"]."/cms/lib/plugins/payments/ok.php?".$params;
    }

    private static function _install() {
        mysql_query("CREATE TABLE IF NOT EXISTS `aux_plg_payments` (
            `num` INT NOT NULL AUTO_INCREMENT,
            `createdDate` DATETIME NOT NULL,
            `ip` VARCHAR(255) NOT NULL,
            `records` MEDIUMTEXT,
            `request` MEDIUMTEXT,
            `price` FLOAT NOT NULL,
            `status` ENUM('Esperando', 'Cancelado', 'Pagado', 'Error','Financiacion Solicitada') NOT NULL,
            `tipo` TINYINT DEFAULT 0,
            `error` MEDIUMTEXT,
            `ipn_action` VARCHAR(255),
            `method` INT NOT NULL,
            `card_id` VARCHAR(255),
            `card_caduc` VARCHAR(255),
            `ipn_response` MEDIUMTEXT,
            PRIMARY KEY (`num`),
            INDEX (`tipo`),
            INDEX (`method`),
            INDEX (`status`)
        )") or die(mysql_error());
    }
}
