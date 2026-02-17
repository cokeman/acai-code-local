<?
session_start();
$domains_allowed = ['https://cms.cocosolution.com','https://acai.cms.cocosolution.com','https://cms.acaisuite.com','https://indev.plandeweb.com'];
$tokens_allowed = ['0d775395420d7f6a3f231a86a00e998c'];

require_once __DIR__."/../../../init.php";
require_once __DIR__."/../../../database_functions.php";
require_once __DIR__."/../../../admin_functions.php";

if (!@$_SESSION["CURRENT_USER"]){
    $current_user = getCurrentUserAndLogin();
    $GLOBALS["CURRENT_USER"] = $current_user;
}else{
    $GLOBALS["CURRENT_USER"] = $_SESSION["CURRENT_USER"];
}

connectToMySQL();

function handleError($code = 400, $userMessage = 'Error.', $adminMessage = 'Error.', $response) {
    http_response_code($code);
    $response['error']['userMessage'] = $userMessage;
    $response['error']['adminMessage'] = $adminMessage;
    die(json_encode($response));
}

// Allow from any origin
$allow_all = false;
if (isset($_SERVER['HTTP_ORIGIN'])) {
    // should do a check here to match $_SERVER['HTTP_ORIGIN'] to a
    if (in_array($_SERVER['HTTP_ORIGIN'], $domains_allowed) || $allow_all) {
        // whitelist of safe domains
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    } else {
        handleError(400, 'No permitido.', 'No está autorizado.'.$_SERVER['HTTP_ORIGIN'], $response);
        die();
    }
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS");
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    http_response_code(200);
    die();
}

$response = [
    'data' => ''
];

$request = json_decode(file_get_contents("php://input"), true)?:[];
$request = array_merge($request, $_REQUEST);

if(!isset($request['token']) || !in_array($request['token'], $tokens_allowed)) {
    handleError(403, 'Acceso no autorizado.', 'Token inválido o no presente.', $response);
}

if (isset($request) && count($request)) file_put_contents(__DIR__."/../../../../postqueries.log", "[".$GLOBALS["CURRENT_USER"]["domain"]["domain"]."] - [".getmypid()."] - [BUILDER]".json_encode(array_keys($request))."\n\n",FILE_APPEND | LOCK_EX);

// Extructura de API:
// GET /api/tableName
// GET /api/tableName/num
// POST /api/tableName
// PATCH /api/tableName/num
// DELETE /api/tableName/num
require_once __DIR__ . '/../../../viewer_functions.php';

//require_once __DIR__ . '/../../../sesion.php';
//require_once __DIR__ . '/../../../funciones.php';

if(isset($request['tableName'])){
    $tableName = $request['tableName'];
}

if(isset($request['num']))
    $num = $request['num'];

if (!isset($tableName) || $tableName === '') { handleError(404, 'La tabla solicitada no existe.', 'No existe la tabla: ' . $tableName, $response); }

try {
    
    $method = isset($request['method'])?$request['method']:$_SERVER['REQUEST_METHOD']?:'';
    $method = strtoupper($method);
    if (!in_array($method, ['GET', 'POST', 'PATCH', 'DELETE'])) { handleError(400, 'La acción solicitada no existe.', 'No existe el método.', $response); }

    // $tableName
    $where = isset($request['where'])?$request['where']:[];
    $options = isset($request['options'])?$request['options']:[];
    $order = isset($request['order'])?$request['order']:'';
    $offset = isset($request['offset'])?$request['offset']:0;
    $limit = isset($request['limit'])?$request['limit']:50;

    $records = isset($request['records'])?$request['records']:[];

    if(isset($num) && !isset($where['num'])) { $where['num'] = $num; }
    $functions = [];

    switch ($method) {
        case 'GET':
            
            if (isset($request["isQuery"])){
                $response["data"] = mysql_query_fetch_all_assoc($request["query"]);
                die(json_encode($response));
            }

            
            // $response['data']['records'] = CocoDB::get($tableName, $where, $order, $limit);
            if(isset($num)) $where = 'num='.intval($num);
            if (!@$_REQUEST["ignoreSchema"] && FALSE){
                
                //$response['data'] = array_slice(mysql_query_fetch_all_assoc("SELECT * FROM $tableName WHERE , $where, $order), $offset, $limit);
            }else{
                
                $whereAux = @$where ? " WHERE ".$where : ""; 
                $orderAux = @$order ? " ORDER BY ".$order : ""; 
                $limitAux = @$limit ? " LIMIT ".$limit : ""; 
                
                
//                $response["data"] = CocoDB::get($tableName,$where,$order,$limit,["uploads" => false]);
                $response['data'] = mysql_query_fetch_all_assoc("SELECT * FROM ".$TABLE_PREFIX.$tableName.$whereAux.$orderAux.$limitAux);
                
//                $fields_to_select = "num, recordNum, fieldName, CONCAT('https://".$CURRENT_USER['domain']['domain']."', urlPath) as urlPath, info1, info2, info3, info4, info5";
//                
//                if (@$where && strpos($where,"num in(") !== false) $whereUploads = " AND recordN".substr($where,1); else $whereUploads = "";                
//                
//                $uploads = mysql_query_fetch_all_assoc("SELECT $fields_to_select FROM ".$TABLE_PREFIX."uploads WHERE tableName='".$tableName."' ".$whereUploads." ORDER BY `order` ASC");
//                $uploadsResult = [];
//                foreach($uploads as $upload){
//                    if (!isset($uploadsResult[$upload["recordNum"]])) $uploadsResult[$upload["recordNum"]] = [];
//                    if (!isset($uploadsResult[$upload["recordNum"]][$upload["fieldName"]])) $uploadsResult[$upload["recordNum"]][$upload["fieldName"]] = [];
//                    $uploadsResult[$upload["recordNum"]][$upload["fieldName"]][] = $upload;
//                }
//                
//                if ($uploadsResult){
//                    foreach($response["data"] as $contData => $data){
//                        if (isset($uploadsResult[$data["num"]])) $response["data"][$contData] = array_merge($response["data"][$contData],$uploadsResult[$data["num"]]);
//                    }
//                }
                
                $response["data"] = array_map(function($record) use($request){ 
                    $record["tableName"] = $request["tableName"]; 
                    if (@$request["returnData"]){
                        $record["returnData"] = $request["returnData"];
                    }
                    return $record;
                },$response["data"]);
            }
            break;
        case 'POST':
            $response['data'] = CocoDB::insertRecords($tableName, $records, $functions, $options);
            
            break;
        case 'PATCH':
            if (@$tableName == "bulkUpdate"){
                $response['data'] = [];
                
                // REGISTRO DE ACTIVIDAD
                if ((@$CURRENT_USER["domain"]["plugins"]["registro_actividad"][0]["campo"] == "enabled") && (@$CURRENT_USER["domain"]["plugins"]["registro_actividad"][0]["valor"] == "1")){
                    $menu = "bulkUpdate";
                    $response["groupId"] = "bld_" . substr(uniqid(),0,10);
                    $recordIds = array_filter(array_map(function($rec){ return @explode("__",$rec)[1]; },array_keys(array_map(function($record){ return $record["records"];},$records))));
                    if ($recordIds) {
                        $prevRecords = CocoDB::get("builder_custom","num in (".join(",",$recordIds).")","num desc",null,["ignoreSchema" => true]);
                        $prevRecords = array_map(function($rec){ return array_filter($rec); }, $prevRecords);

                        $newRecords = array_filter(array_values(array_map(function($rec){ return @$rec["where"] ? array_merge($rec["where"], $rec["records"]) : null; },$records)));

                        if ($prevRecords && $newRecords){
                            
                            require_once __DIR__."./../../registro_actividad/registro_act.php";
                            
                            foreach($newRecords as $newRecord){
                                $prevRecord = null;
                                foreach($prevRecords as $prev){
                                    if ($prev["num"] == $newRecord["num"]) $prevRecord = $prev;
                                }
                                
                                if (!$prevRecord) continue;
                                generateNotificationPlugin_registro_actividad(@$CURRENT_USER["num"],"builder_custom",intval(@$newRecord["num"]),"UPDATE",array(
                                    "newRecord" => $newRecord,
                                    "prevRecord" => $prevRecord,
                                    "extraRecords" => getExtraRecords("builder_custom",intval(@$newRecord["num"]),$prevRecord)
                                ),$response["groupId"]);
                            }
                            
                            
                        }
                    }
                }
                // HASTA AQUI
                    
                

                foreach($records as $record){
                    $response['data'][] = CocoDB::updateRecords($record["tableName"], $record["records"], $record["where"], [], $options);
                }

            }else{
                $response['data'] = CocoDB::updateRecords($tableName, $records, $where, $functions, $options);
            }
                        
            break;
        case 'DELETE':
            $response['data'] = CocoDB::deleteRecords($tableName, $where, $options);
            break;
    }
    if($response['data'] !== 0 && $response['data'] == $limit) {
        $response['nextPage'] = 'https://' . $_SERVER['HTTP_HOST'] . preg_replace('/&?offset=[0-9]+/', '', $_SERVER['REQUEST_URI']);
        if(strpos($response['nextPage'], '?') !== false)
            $response['nextPage'] .= '&';
        else
            $response['nextPage'] .= '?';
        $response['nextPage'] .= 'offset=' . ($offset + $limit);
    }
    if($offset - $limit >= 0) {
        $response['prevPage'] = 'https://' . $_SERVER['HTTP_HOST'] . preg_replace('/&?offset=[0-9]+/', '', $_SERVER['REQUEST_URI']);
        if(strpos($response['prevPage'], '?') !== false)
            $response['prevPage'] .= '&';
        else
            $response['prevPage'] .= '?';
        $response['prevPage'] .= 'offset=' . ($offset - $limit);
    }
} catch (Exception $e) {
    handleError(400, 'Lo sentimos, ha habido un problema con su petición, inténtelo de nuevo o contacto con un administrador.', 'Hubo una excepción:' . $e->getMessage(), $response);
}

if (@$response["data"] && @$request["tableName"] == "uploads" && @$method != "GET"){
    $auxResponse = API::sendToWeb([
        'action_ws' => 'clearCache',
        'token'=>getPrefixedCookie("COCOOKIE"),
        'tokenHash' => Uploads::generate_token()
    ], 'POST');
}

die(json_encode($response));