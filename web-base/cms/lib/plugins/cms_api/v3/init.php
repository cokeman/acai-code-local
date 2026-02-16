<?
//require_once 'allow-cors.php';
require_once __DIR__."/../../../viewer_functions.php";
if(file_exists(realpath(__DIR__."/../../../../../lib/variables.php"))) {
    require_once __DIR__."/../../../../../lib/variables.php";
}
require_once __DIR__."/CmsApi.class.php";
API::$die = true;
ini_set("display_errors",1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST,GET,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache'); 

$tokenData = Auth::authorizeApi(isset($_REQUEST["auth"]) ? true : false);

if (!$tokenData || isset($tokenData['error'])) {
    API::error(new ApiError('Invalid token', 403));
}

API::setToken($tokenData);

if (isset($_REQUEST["auth"])) API::success($tokenData);
API::$request = $_REQUEST;

