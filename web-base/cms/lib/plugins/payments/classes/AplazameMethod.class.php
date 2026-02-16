<?php
/*
    Pasos para integrar:
    
    1. Integrar la carga del javascript principal con la key
        <script c-if="tienda.pago_por_financiacion" src="https://cdn.aplazame.com/aplazame.js" data-aplazame="{{tienda.aplazame_public_key}}" data-sandbox="{{tienda.aplazame_modo ? 'true' : 'false'}}"></script>
    2. Integrar el simulador para que el usuario sepa las cuotas o el propio widget
        https://aplazame.com/docs/api/
    3. Llama a $tpv = new AplazameMethod(); y $tpv->pay(); para generar el id de payment y haz la llamada a aplazame.checkout(data) desde javascript;
    4. Apunta el confirm a la url https://tudominio.com/cms/lib/plugins/payments/ipn.php?ipn={{sha1(id de payment)}}
        
*/

// require_once __DIR__."/vendor/apiRedsys.php";
// require_once __DIR__."/PaymentMethod.class.php";

require_once __DIR__ . "/vendor/vendor-aplazame/autoload.php";
require_once __DIR__ . "/PaymentMethod.class.php";

use Aplazame\Api\Client as AplazameClient;

class AplazameMethod extends PaymentMethod {
    
    function __construct() {
        self::get_config();
        $this->init(41, 'Aplazame', 'https://api.aplazame.com', [
            'public_key' => self::$config['aplazame_public_key'],
            'private_key' => self::$config['aplazame_private_key']
        ], 'https://api.aplazame.com', [
            'public_key' => self::$config['aplazame_public_key'],
            'private_key' => self::$config['aplazame_private_key']
        ]);
    }

    function can_be_used() {
        $cred = $this->get_credentials();
        return true;
    }

    function pay($quantity, $insertData = null, $payment  = null, $curl = false) {
        parent::pay($quantity, $insertData, $payment);
        
        $privateKey = self::$config['aplazame_private_key'];
        $environment = self::$isTest ? AplazameClient::ENVIRONMENT_SANDBOX : AplazameClient::ENVIRONMENT_PRODUCTION;
        $apiBaseUri = 'https://api.aplazame.com';

        $usuario_email = @CocoDB::get('usuarios', ['num' => $insertData["records"]["user"]], '', 1, ['ignoreSchema' => true])[0]['correo'];

        $payload = (object) [
            "merchant" => [
                "notification_url" => $this->get_ipn_url($this->payment_record_id),
                "success_url" => $this->get_success_url(),
                "pending_url" => $this->get_success_url(),
                "error_url" => $this->get_cancel_url($this->payment_record_id)
            ],
            "order" => [
                "id" => (string) $this->payment_record_id,
                "articles" => array_map(function($rec) {
                    $producto_enlace = @CocoDB::get('productos', ['num' => $rec["num"]], '', 1, ['ignoreSchema' => true])[0]['enlace'];
                    $foto = (strpos($rec['photo'], 'https://') === 0) ? $rec['photo'] : 'https://' . $_SERVER['HTPT_HOST'] . $rec["photo"];
                    return [
                        "id" => $rec["num"],
                        "name" => $rec["referencia"],
                        "quantity" => $rec["quantity"],
                        "price" => ((float) floor(self::parse_number($rec["price"]) * 100)),
                        "url" => 'https://' . $_SERVER['HTPT_HOST'] . $producto_enlace,
                        "image_url" => $foto
                    ];
                }, $insertData["records"]["productos"]),
                "currency" => "EUR",
                "total_amount" => ((float) floor(self::parse_number($quantity) * 100)),
                "tax_rate" => 0
            ],
            "customer" => [
                "email" => $usuario_email
            ],
            "shipping" => [
                "first_name" => @$insertData["records"]["direccion_envio"]["nombre"] ?: @$insertData["records"]["direccion_facturacion"]["nombre"],
                "last_name" => @$insertData["records"]["direccion_envio"]["apellidos"] ?: @$insertData["records"]["direccion_facturacion"]["apellidos"],
                "street" => @$insertData["records"]["direccion_envio"]["direccion"] ?: @$insertData["records"]["direccion_facturacion"]["direccion"],
                "city" => trim(@explode(":",@$insertData["records"]["direccion_envio"]["poblacion_bd"][0] ? $insertData["records"]["direccion_envio"]["poblacion_bd"][0]["breadcrumb"] : @$insertData["records"]["direccion_facturacion"]["poblacion_bd"][0]["breadcrumb"])[1]),
                "state" => trim(@explode(":",@$insertData["records"]["direccion_envio"]["poblacion_bd"][0] ? $insertData["records"]["direccion_envio"]["poblacion_bd"][0]["breadcrumb"] : @$insertData["records"]["direccion_facturacion"]["poblacion_bd"][0]["breadcrumb"])[0]),
                "country" => "ES",
                "postcode" => @$insertData["records"]["direccion_envio"]["codigo_postal"] ?: @$insertData["records"]["direccion_facturacion"]["codigo_postal"],
                "price" => 0,
                "name" => "Empresa habitual y/o recogida en tienda"
            ]
        ];

        $aplazameApiClient = new Aplazame\Api\Client($apiBaseUri, $environment, $privateKey);

        try{
            $order_created = $aplazameApiClient->request('POST', '/checkout', $payload, 4)["id"];
        } catch(Exception $e){
            die(json_encode(["payload" => $payload,"error" => $e->getMessage()]));
        }

        return ["success" => true,"paymentId" => $order_created,"hashPaymendId" => sha1($this->payment_record_id)];
    }

    function autorizar_pedido($order_id){
        self::get_config();
        
        $is_production = self::$isTest ? false : true;
        $private_key = self::$config['aplazame_private_key'];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.aplazame.com/orders/".$order_id."/authorize");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        $pro = (@$is_production) ? ".v1+json" : ".sandbox.v1+json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/vnd.aplazame'.$pro,
            'Authorization: Bearer '.$private_key,
            'Host: api.aplazame.com'
        ));
        $data = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result = array(
            'config' => curl_getinfo($ch),
            'status' => $status,
            'data' => json_decode($data),
            'succeeded' => ( $status >= 200 && $status < 400 )
        );

        curl_close($ch);

        return $result;

    }
    
}