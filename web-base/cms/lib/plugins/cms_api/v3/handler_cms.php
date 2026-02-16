<?

require_once "init.php";

$request = json_decode(file_get_contents("php://input"),true); 

$method = isset($request['method']) ? $request['method'] : ($_SERVER['REQUEST_METHOD'] ? :'');
$method = strtoupper($method);
if (!in_array($method, ['GET', 'POST', 'PATCH', 'DELETE'])) { API::error(new Error("La acción solicitada no existe.")); }

if (in_array($method,["PATCH","POST","DELETE"]) && file_exists(__DIR__."/../../../../../logs")){
	$log = file_put_contents(__DIR__."/../../../../../logs/api_access_".date("Y-m-d").".log", "[".$_SERVER["REMOTE_ADDR"]."]\t[".date("Y-m-d H:i:s")."]\t".$method."\t".@$_REQUEST["endpoint"]."\t".json_encode(["request" => $request])."\n\n",FILE_APPEND | LOCK_EX);
}

CmsApi::generateBaseConfig();
$result = CmsApi::request($_REQUEST["endpoint"],$request,$method);
API::success($result);