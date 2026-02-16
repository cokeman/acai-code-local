<?
require_once "init.php";

$request = json_decode(file_get_contents("php://input"),true); 

$method = isset($request['method'])?$request['method']:$_SERVER['REQUEST_METHOD']?:'';
$method = strtoupper($method);
if (!in_array($method, ['GET', 'POST', 'PATCH', 'DELETE'])) { API::error(new Error("La acción solicitada no existe.")); }


switch ($method) {
    case "POST":
        try {
            $data = [];
            if (!@$_GET["tableName"]) API::error(new Error("Se debe enviar la tabla y/o el identificador"));
            if (!@$request) API::error(new Error("Se debe enviar datos"));
            if (@$_GET["id"]){
                $data = CocoDB::updateRecords($_GET["tableName"],$request,"num=".intval($_GET["id"]));    
            }else{
                $data = CocoDB::insertRecords($_GET["tableName"],$request);    
            }
        }
        catch(ApiError $e) {
            API::error($e);
        }    
        
        API::success($data);
    case "DELETE":
        $data = [];
        if (!@$_GET["tableName"] || !$_GET["id"]) API::error(new Error("Se debe enviar la tabla y el identificador"));
        try{
            $data = CmsCRUD::removeRecord(intval($_GET["id"]),$_GET["tableName"]);
        }catch(Exception $e){
            API::error($e);
        }
        
        API::success($data);
        break;
    default:
        if (!@$request) $request = [];
        if (@$_GET["tableName"]) {
            $request[$_GET["tableName"]] = [];
            if (@$_GET["id"]) {
                $request[$_GET["tableName"]]["where"] = [["field" => "num","operator" => "=","value" => intval(@$_GET["id"])]];
                $request[$_GET["tableName"]]["limit"] = 1;
            }
        }
        
        try {

            $data = CmsCRUD::listRecordsBulk(@$request);
        }
        catch(ApiError $e) {
            API::error($e);
        }    
        
        API::success($data);
    break;
}