<?
if (class_exists("CocoDB") && property_exists("CocoDB","trackData") && isset($_REQUEST["acaiTrackData"])){
    header("Content-Type:application/json");

    CocoWS::setToken(@$_REQUEST["token"]);
    if (!CocoWS::validateUploadToken(@$_REQUEST["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }

    $result = CocoDB::getTrackData();

    $result[0]["BBDD_Data"] = array_map(function($rec){ unset($rec["records"]); return $rec;},CocoDB::$debugData);

    die(json_encode($result,JSON_PRETTY_PRINT));
}
// Para dominios con carpetas
if (defined("ROOT_FOLDER") && ROOT_FOLDER){
    $var = str_replace('href="/','href="'.ROOT_FOLDER.'/',$var);
    $var = str_replace('src="/','src="'.ROOT_FOLDER.'/',$var);
    $var = str_replace("href='/","href='".ROOT_FOLDER."/",$var);
    $var = str_replace("src='/","src='".ROOT_FOLDER."/",$var);
}