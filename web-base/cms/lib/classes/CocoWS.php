<?php
class CocoWS {
    private static $_instances = array();
    static $endpoint = "";
    static $token = "";
    static $cacheSchemaTables = [];    
    static $cacheSchemas = [];

    public static function getInstance() {
        $class = get_called_class();
        if (!isset(self::$_instances[$class])) {
            self::$_instances[$class] = new $class();
        }
        return self::$_instances[$class];
    }

    private function __construct() {}
        
    static function request($api_method, $data = null, $method = 'GET') {
        $url = rtrim(self::$endpoint, "/")."/".trim($api_method, "/")."/";
        $header = array();
        $header[] = 'Content-type: application/json';        
        $header[] = 'Authorization: Bearer '.self::$token;
        
        $result = self::curl($url,$header,$method,$data);
        
        return $result;
    }  

    static function validateUploadToken($token) {
        $response = self::request('upload_token', ['action' => 'validate', 'token' => $token], 'POST');
        return isset($response['success']) && $response['success'];
    }
    
    /**
     * Actualiza la configuracion de la pagina
     * @destacar
     * @category WS
     * @param settings: Actualiza los settings
     * @return boolean que indica el exito 
     */
    static function updateSettings($settings) {
        return true;
    }
    
    /**
     * Actualiza un Schema
     * @destacar
     * @category WS
     * @param tableName: Identificador del schema
     * @param path: ruta del archivo
     * @return boolean que indica el exito 
     */
    static function insertOrUpdateSchema($tableName,$path,$schemaNew = null) {
        if (!$schemaNew) { $schemaNew =  CocoWS::loadSchema($tableName);}
        saveINI($path."/".$tableName.".ini.php",$schemaNew);
        return true;
    }
    
    /**
     * Inserta un modulo dado en la carpeta de modulos
     * @destacar
     * @category WS
     * @param id: Identificador del módulo
     * @param zipFile: archivo Zip
     * @return boolean que indica el exito 
     */
    static function insertOrUpdateModule($id, $zipFile) {
        return true;
    }

    /**
     * Inserta un plugin dado en la carpeta de plugins
     * @destacar
     * @category WS
     * @param id: Identificador del plugin
     * @param zipFile: archivo Zip
     * @return boolean que indica el exito 
     */
    static function insertOrUpdatePlugin($id, $zipFile) {
        return true;
    }
    
    /**
     * Ejecuta un curl contra el servidor
     * @destacar
     * @category WS
     * @param url: url del servidor
     * @param header: cabeceras
     * @param method: metodo utilizado
     * @param data: datos que se transfieren en la consulta
     * @return boolean que indica el exito 
     */
    static function curl($url,$header,$method = "GET",$data = null){
        $ch = curl_init();
        
        
        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
            case 'PUT':
            case "DELETE":
                if ($data) {
                    if (is_array($data)) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    }
                    else {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                    }
                }
                break;
            default:
                $url .= "?".http_build_query($data);
                break;
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_REFERER, $_SERVER["HTTP_HOST"]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);
        //curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        
        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        
        if (!$response) {
            rewind($verbose);
            $verboseLog = stream_get_contents($verbose);
            $error = "Verbose information:\n<pre>".htmlspecialchars($verboseLog)."</pre>\n";
            $error.= sprintf("cUrl error (#%d): %s<br>\n", curl_errno($ch),
                htmlspecialchars(curl_error($ch)));

            curl_close($ch);
            return self::parseError(["error" => $error,"code" => 418]);
        }
        $json = json_decode($response,true);
        if (json_last_error() !== JSON_ERROR_NONE){
            curl_close($ch);
            return self::parseError(["error" => "Data response error, not a object","message" => $response]);
        }
        if (isset($json["error"])){
            curl_close($ch);
            return self::parseError($json);
        }
        curl_close($ch);
        
        return $json;
        
    }
    
    /**
     * Parsea el error de respuesta
     * @destacar
     * @category WS
     * @param data: Datos del error
     * @return devuelve los datos
     */
    public static function parseError($data){
        switch (@$data["code"]){
            case 403:
                break;
            case 600:
                break;
            default:
                throw new Exception(json_encode($data));
        }
        return $data;
    }
    
    /**
     * Hacer Login en el servidor
     * @destacar
     * @category WS
     * @param user: usuario
     * @param pass: password
     * @param domain: dominio
     * @param token: token
     * @param method: método utilizado
     * @return devuelve el token y el usuario
     */
    static function login($user=null,$pass=null,$domain=null,$token=null,$method = "POST"){
        
        $userPass = $user && $pass ? base64_encode($user.':'.$pass.':'.$domain) : $token;
        $authorization = $token ? "Bearer" : "Login";
        $header = array();
        $header[] = 'Content-length: 0';
        $header[] = 'Content-type: application/json';
        $header[] = 'Authorization: '.$authorization.' '.$userPass;
        $url = rtrim(self::$endpoint,"/")."/auth/";
        
        $result = self::curl($url,$header,$method);
        if (@$result["error"]) return self::parseError($result);
        
        return [$result["data"]["token"],$result["data"]["user"]];
    }
    
    static function setEndpoint($endpoint) {
        self::$endpoint = $endpoint;
    }
    
    static function setToken($token) {
        self::$token = $token;
    }
    
    static function getToken() {
        return self::$token;
    }
    
    static function getSchemaTables($dir = '',$type = '') {
        
        $respuesta = self::request("schemas",["action" => "getSchemaTables","dir" => $dir,"type" => $type]);
        
        if (isset($respuesta["error"])){
            return self::parseError($respuesta);
        }else{
            self::$cacheSchemaTables = $respuesta["data"];
            return $respuesta["data"];
        }
    }
    
    static function loadSchema($tableName, $schemaDir = '') {
        if (isset(self::$cacheSchemas[$tableName])) return self::$cacheSchemas[$tableName];

        if (!$tableName) { die(__FUNCTION__ . ": no tableName specified!"); }

        $tableNameWithoutPrefix = getTableNameWithoutPrefix($tableName);

        $respuesta = self::request("schemas",["id" => $tableNameWithoutPrefix,"dir" => $schemaDir]);
        
        if (isset($respuesta["error"])){
            return self::parseError($respuesta);
        }else{
            self::$cacheSchemas[$tableName] = $respuesta["data"];
            return $respuesta["data"];
        }

    }
    
    static function getAuthorizationHeader(){
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        }
        else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }
    
    static function getBearerToken() {
        $headers = self::getAuthorizationHeader();
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    static function getLoginAccess() {
        $headers = self::getAuthorizationHeader();
        if (!empty($headers)) {
            if (preg_match('/Login\s(\S+)/', $headers, $matches)) {
                return explode(":",base64_decode($matches[1]));
            }
        }
        return null;
    }

}