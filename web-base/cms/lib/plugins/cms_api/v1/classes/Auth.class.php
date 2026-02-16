<?php
require_once realpath(__DIR__."/../lib/php-jwt/vendor/autoload.php");

use Firebase\JWT\JWT;

class Auth {
    public $privateKey = 'wscO4QaF'; // Key de codificación
    public $expire = 60; // 3600 Expire time in seconds
    public $renewTime = 60; // 1200 Renew time before expired in seconds
    public $now;
    /**
     * @param privateKey: Private Key
     */
    public function __construct($privateKey = null) {
        if ($privateKey) $this->privateKey = $privateKey;
        $this->now = time();
    }

    static function AuthenticateUserLocal($loginAccess){
        global $TABLE_PREFIX;
        $user = $loginAccess[0];
        $pass = $loginAccess[1];

        $userBD = mysql_query_fetch_all_assoc("SELECT *,(neverExpires = '0' AND expiresDate < NOW()) as isExpired FROM ".$TABLE_PREFIX."accounts WHERE `username`='".$user."' and `password`= '".md5($pass)."' LIMIT 1");
        $userBD = @$userBD[0];
        return $userBD;
    }

    static function AuthenticateUser($loginAccess){
        global $TABLE_PREFIX;
        $user = $loginAccess[0];
        $pass = $loginAccess[1];
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://ws.cocosolution.com/api/auth/",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => array(
                "Accept: */*",
                "Accept-Encoding: gzip, deflate",
                "Authorization: SimpleAuth ".base64_encode($user.":".$pass),
                "Cache-Control: no-cache",
                "Connection: keep-alive",
                "Content-length: 0",
                "Content-type: application/json",
                "Referer: ".$_SERVER["HTTP_HOST"]
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);

        if ($err) {
            API::error(new Exception("Login failed"));
        } else if (@json_decode($response,true)["error"]){
            API::error(new Exception("Login failed"));
        }else{
            $schema = loadINI(__DIR__."/../../custom-schema.ini.php");
            if (!@$schema["enabled"]) API::error(new Exception("Login failed"));
            
            try{
                $resultData = json_decode($response,true);
                self::validateUserHash($resultData["data"]["userNum"]);
                return ["num" => $resultData["data"]["userNum"]];
            }catch(Exception $e){
                API::error(new ApiError('Login Failed '.$e->getMessage(),403));
            }
        }
    }

    static function validateUserHash($num){
        $schema = loadINI(__DIR__."/../../custom-schema.ini.php");
        if (!@$schema["enabled"]) API::error(new Exception("Login failed"));
        if (@$schema["config"]["hash"] != sha1($num)) API::error(new Exception("Login failed"));
    }

    static function updateTokenUserData(){
        $api = new API();
        $auth = new self();
        $token = array(
            'iat' => $auth->now, // Tiempo que inició el token
            'exp' => $auth->now+($auth->expire), // Tiempo que expirará el token 
            'data' => API::$user
        );
        $jwt = JWT::encode($token, $auth->privateKey);

        API::setToken(["renewToken" => $jwt]);
    }
    static function authorizeApi($auth = false) {
        
        $api = new API();
        $auth = new self();
        $requestToken = self::getBearerToken();
        $loginAccess = self::getLoginAccess();
        
        $tokenData = [];


        if (!$requestToken && !$loginAccess) API::error(new ApiError('No token was sent',403));

        if ($requestToken) {

            try{
                $data = (object)JWT::decode($requestToken, $auth->privateKey, array('HS256'));
                $data->data = (array) $data->data;
                
                API::$user = json_decode(json_encode($data->data), true);
                
                self::validateUserHash(API::$user["num"]);
                
                $difference = $data->exp-$auth->now;
                if ($difference<0){
                    API::error(new ApiError('Token expired',403));
                }else if($difference >= 0 && $difference < $auth->renewTime){
                    $token = array(
                        'iat' => $auth->now, // Tiempo que inició el token
                        'exp' => $auth->now+($auth->expire), // Tiempo que expirará el token 
                        'data' => $data->data
                    );
                    $jwt = JWT::encode($token, $auth->privateKey);
                    // AQUI FALTA CONVALIDAR LOS DATOS DEL USUARIO DEL TOKEN CON LA BBDD PARA 
                    // ECHARLO FUERA SI LOS ACCESOS O ALGUN OTRO DATO HA CAMBIADO
                    $tokenData = ["renewToken" => $jwt,"user" => $data->data,"difference" => ($data->exp-$auth->now)];
                    if ($auth) $tokenData["token"] = $jwt;
                    API::addResponseData($tokenData);
                    
                    return $tokenData;
                }else{
                    $tokenData = ["user" => $data->data,"difference" => ($data->exp-$auth->now)];
                    if ($auth) $tokenData["token"] = $requestToken;
                    API::addResponseData($tokenData);
                    return $tokenData;
                }


            }catch (Exception $e) {
                API::error(new ApiError('Token expired',403));
            }

        }else if ($loginAccess){

            try{
                $authenticated = self::AuthenticateUser($loginAccess);

                if ($authenticated){

                    $token = array(
                        'iat' => $auth->now, // Tiempo que inició el token
                        'exp' => $auth->now+($auth->expire), // Tiempo que expirará el token 
                        'data' => $authenticated
                    );

                    $jwt = JWT::encode($token, $auth->privateKey);    

                    $tokenData = ["token" => $jwt,"expire" => $auth->now+($auth->expire),"user" => ["num" => $authenticated["num"]],"login" => true];

                    API::$user = $authenticated;

                    return $tokenData;
                }else{
                    API::error(new ApiError('Login Failed',403));
                }
            }catch (Exception $e) {
                API::error(new ApiError('Login Failed',403));
            }
        }else{
            API::error(new ApiError('Login Failed',403));
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