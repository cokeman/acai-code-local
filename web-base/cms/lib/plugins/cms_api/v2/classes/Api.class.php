<?
class Api {
    private static $renewToken = null;
    public static $user;
    public static $request;
    public static $responseData;
    
    function __construct() {

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
    
    static function log($log = null,$code = 200){
        mysql_query("INSERT INTO logs SET 
            createdDate='".date("Y-m-d H:i:s")."', 
            user=".API::$user['num'].",
            website='1',
            method='".mysql_real_escape_string($_SERVER["REQUEST_METHOD"])."',
            endpoint='".mysql_real_escape_string($_SERVER["REQUEST_URI"])."',
            code='".mysql_real_escape_string($code)."',
            request='".mysql_real_escape_string(json_encode($_REQUEST))."',
            response='".mysql_real_escape_string($log)."'");
    }

    static function success($data) {
        $response = [
            'success' => true,
            'data' => $data,
        ];    
        if (self::$renewToken) {
            $response['renewToken'] = self::$renewToken;
        }
        
        self::log(json_encode($response));
        echo json_encode($response);
        die();
    }
    
    static function setToken($token) {
        if (isset($token['renewToken'])) {
            self::$renewToken = $token['renewToken'];
        }
    }
    
}