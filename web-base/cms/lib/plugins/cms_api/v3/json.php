<?
require_once __DIR__."/../../../viewer_functions.php";
require_once "CmsApi.class.php";
ApiJsonBuilder::generateBaseConfig();
API::setLanguage();
API::generateJsonConfig([__DIR__."/schemaBase.json",__DIR__."/schemaCustom.json"]);

switch(@$_REQUEST["type"]){
    case "custom":
        die(json_encode(API::$jsonConfig,JSON_PRETTY_PRINT));
        break;
    default:
        die(json_encode(API::$jsonConfig,JSON_PRETTY_PRINT));
}