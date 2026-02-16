<?
class Api {
    private static $renewToken = null;
    public static $user;
    public static $request;
    public static $responseData;
    public static $jsonConfig;
    public static $warnings = [];
    public static $allowedTranslateFields = null;
    public static $lang = "";
    public static $die = false;
    
    function __construct() {

    }
    static function setLanguage(){

        $idioma_seleccionado = @substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);

        if(@$idioma_seleccionado && !isset($_REQUEST['idioma'])) {
            $_REQUEST['idioma'] = $idioma_seleccionado;
        }

        $idioma_seleccionado_forzado = @substr($_SERVER['HTTP_X_ACAI_ACCEPT_LANGUAGE'], 0, 2);

        if(@$idioma_seleccionado_forzado) {
            $_REQUEST['idioma'] = $idioma_seleccionado_forzado;
        }

        if(@$_REQUEST['idioma'] === 'www') $_REQUEST['idioma'] = '';

        self::$lang = @$_REQUEST['idioma'];
        return $idioma_seleccionado;
    }
    
    static function t_recursivo($record, $idx=null) {
        global $TABLE_PREFIX;
        if(is_null(self::$allowedTranslateFields)) {
            self::$allowedTranslateFields = array_flip(array_map(function($field) {
                return $field['fieldName'];
            }, mysql_query_fetch_all_assoc("SELECT DISTINCT fieldName FROM {$TABLE_PREFIX}traducciones")));
        }
        $it = $idx ? $record[$idx] : $record;
        if (is_array($it)) {
            foreach ($it as $key => $value) {
                if (is_array($it[$key])) {
                    $it[$key] = self::t_recursivo($it[$key], null);
                } else {
                    if (isset(self::$allowedTranslateFields[$key])) {
                        $it[$key] = t($it, $key);
                    }
                }
            }
            if ($idx) {
                $record[$idx] = $it;
            } else {
                $record = $it;
            }
        } else {
            if ($idx && isset(self::$allowedTranslateFields[$idx])) {
                $record[$idx] = t($record, $idx);
            }
        }
        return $record;
    }
    
    static function generateJsonConfig($files = []){
        $jsons = [];
        foreach($files as $file){
            if (file_exists($file)){
                $jsons[] = json_decode(file_get_contents($file),true);
            }
        }
        self::$jsonConfig = self::array_merge_deep_array($jsons);
        return self::$jsonConfig;
        
    }
    private static function array_merge_deep() {
        $args = func_get_args();
        return self::array_merge_deep_array($args);
    }

    private static function array_merge_deep_array($arrays) {
        $result = array();
        foreach ($arrays as $array) {
            if (!is_array($array)) continue;
            foreach ($array as $key => $value) {
                if (is_integer($key)) {
                    $result[] = $value;
                }
                elseif (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
                    $result[$key] = self::array_merge_deep_array(array(
                        $result[$key],
                        $value,
                    ));
                }
                else {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }
    
    static function activity($prevRecord = null,$tableName = '',$newRecord = null,$method = "UPDATE"){
        if (!$tableName) self::error(new ApiError('No tableName specified'));
        
        // INICIO REGISTRO DE ACTIVIDAD
        global $menu;
        $menu = $tableName;
        if (!function_exists("generateNotificationPlugin_registro_actividad") && file_exists(__DIR__."/../../cms/lib/plugins/registro_actividad/registro_act.php")){
            require_once __DIR__."/../../cms/lib/plugins/registro_actividad/registro_act.php";
        }
        if (file_exists(__DIR__."/../../cms/lib/plugins/registro_actividad/registro_act.php")){
            $arrayData = [];
            $recordNum = 0;
            if ($prevRecord){
                $prevRecordResult = @mysql_fetch_assoc(mysql_query("SELECT * FROM ".$TABLE_PREFIX.$tableName." WHERE num=".intval(@$prevRecord)." LIMIT 1"));
                
                if (!@$prevRecordResult) self::error(new ApiError('No record found!'));
                
                $arrayData["prevRecord"] = $prevRecordResult;
                $recordNum = intval($prevRecord);
            }
            if ($newRecord){
                $arrayData["newRecord"] = $newRecord;
            }
            
            generateNotificationPlugin_registro_actividad(self::$user["num"],$tableName,$recordNum,$method,$arrayData);
        }
        // FIN REGISTRO DE ACTIVIDAD                        
    }

    static function error($error) {
        
        self::log($error->getMessage(), $error->getCode());
        $result = [
            'error' => $error->getMessage(),
            'code' => $error->getCode()
        ];
        // if (self::$user["isSuperAdmin"]) {
            // $result["request"] = self::$request;
            // $result["trace"] = $error->getTraceAsString();
        // }
        if (!self::$die) return $result;
        echo json_encode($result);
        die();
    }
    
    static function addResponseData($data = null){
        if (!@$data) return false;
        foreach($data as $key => $value){
            API::$responseData[$key] = $value;
        }
        return true;
    }    
    static function addWarning($string){
        self::$warnings[] = $string;
    }
    static function log($log = null,$code = 200){
        return false;
//        mysql_query("INSERT INTO logs SET 
//            createdDate='".date("Y-m-d H:i:s")."', 
//            user=".API::$user['num'].",
//            website='1',
//            method='".mysql_real_escape_string($_SERVER["REQUEST_METHOD"])."',
//            endpoint='".mysql_real_escape_string($_SERVER["REQUEST_URI"])."',
//            code='".mysql_real_escape_string($code)."',
//            request='".mysql_real_escape_string(json_encode($_REQUEST))."',
//            response='".mysql_real_escape_string($log)."'");
    }

    static function success($data) {
        $response = [
            'success' => true,
        ];
        if (!empty(self::$warnings)){
            $response["warnings"] = self::$warnings;
        }
        if (isset($data[0]) && isset($data[0]["totalRecords"])){
            $response["meta"] = $data[0];
            $response["data"] = $data[1];
        }else{
            $response["data"] = $data;
        }
        
        if (self::$renewToken) {
            $response['renewToken'] = self::$renewToken;
        }
        
        self::log(json_encode($response));
        
        if (!self::$die) return $response;
        
        echo json_encode($response);
        die();
    }
    
    static function setToken($token) {
        if (isset($token['renewToken'])) {
            self::$renewToken = $token['renewToken'];
        }
    }
    
}