<?
// TEST 4
define('COCO_ENDPOINT',"http://ws.cocosolution.com/api/");
define('SCHEMAS_PATH',dirname(__FILE__)."/../../data/schema");

require_once dirname(__FILE__)."/../classes/CocoWS.php";

$post = json_decode(file_get_contents('php://input'), true);
if ($post && !empty($post)) {
    $_REQUEST = array_merge($_REQUEST, $post);
}
require_once __DIR__."/../plugins/index.php";

CocoWS::setEndpoint(COCO_ENDPOINT);

$dummy = @$_REQUEST ?: [];
$pluginsInserted = addPlugins('handler_ws', $dummy);

if (isset($_REQUEST["action_ws"])){
    header("Content-type:application/json");
    switch($_REQUEST["action_ws"]){
        case "updateAllSchemas" : _updateAllSchemas(); break;
        case "updateSchema" : _updateSchema(@$_REQUEST["tableName"]); break;
        case "getAllSchemas" : _getAllSchemas(); break;
        case "validateCredentials" : _validateCredentials(); break;
        case "getInstalledPlugins" : _getInstalledPlugins(); break;
        case "updateAllPlugins" : _updateAllPlugins(); break;
        case "updatePlugin" : _updatePLugin(@$_REQUEST["plugin"]); break;
        case "saveFile": _saveFile(); break;
        case "coreUpdate": _coreUpdate(realpath($GLOBALS["APP"]["pluginsdir"]."/../../lib")); break;
        case "pluginsUpdate": _coreUpdate(realpath($GLOBALS["APP"]["pluginsdir"]),true); break;
        case "removeFile": _removeFile(); break;
        case "clearCache": _clearCache(); break;
        case "renameFile": _renameFile();
        default : die(json_encode(["result" => 0,"error" => "Método no permitido"]));
    }
}

function _clearCache(){
    CocoWS::setToken(@$_REQUEST["token"]);
    if (!CocoWS::validateUploadToken(@$_REQUEST["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }
    _clearCacheFiles();
    header('Content-Type: application/json');
    $res = array("success" => true);
    die(json_encode($res));
}

function _clearCacheFiles(){
    // Remove uploads Caché
    if (file_exists(__DIR__.'/../../../cache')) rrmdir(__DIR__.'/../../../cache/');
    mkdir(__DIR__.'/../../../cache');
}

function rrmdir($src) {
    $dir = opendir($src);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            $full = $src . '/' . $file;
            if ( is_dir($full) ) {
                rrmdir($full);
            }
            else {
                unlink($full);
            }
        }
    }
    closedir($dir);
    rmdir($src);
}

function _coreUpdate($path,$plugins = false){
    CocoWS::setToken(@$_REQUEST["token"]);
    if (!CocoWS::validateUploadToken(@$_REQUEST["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }

    if (@$_REQUEST["multisite"]) $path = realpath($GLOBALS["APP"]["pluginsdir"]."/../../../");

    switch(@$_REQUEST["action"]){
        case "getFile":
            _saveFile(true);
            break;
        case "diff":
            header('Content-Type: application/json');

            $res = array("file" => @$_REQUEST["file"],"hash" => @$_REQUEST["hash"]);
            if (md5_file($path.@$_REQUEST["file"])!=@$_REQUEST["hash"]) die(json_encode(["error" => "Los hashes no coinciden"]));
            $res["fileContent"] = base64_encode(file_get_contents($path.@$_REQUEST["file"]));
            if (!@$_REQUEST["file"]||!@$_REQUEST["hash"]) die(json_encode(["error" => "Faltan datos"]));
            die(json_encode($res));
            break;
        default:
            //if (@$_REQUEST["token"]!=md5(md5_file($path."/lib/admin_functions.php").md5_file($path."/lib/user_functions.php"))) die("Error");
            $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
            $files = array();
            if (@$_REQUEST["multisite"]) $files[] = array("fileName" => "/cms/lib/plugins/builder_saas/layout.json","hash" => md5_file(__DIR__."/../plugins/builder_saas/layout.json"));
            foreach ($rii as $file) {
                if ($file->isDir()){ continue;}
                if (!strpos($file->getPathname(),"/.")
                    && !strpos($file->getPathname(),"cms/uploads/")
                    && !strpos($file->getPathname(),"cache/")
                    && !strpos($file->getPathname(),"template/estandar/images")
                    && !strpos($file->getPathname(),"/minified/")
                    && !strpos($file->getPathname(),"cms/data")){
                        if (!$plugins){
                            if (strpos($file->getPathname(),"cms/lib/plugins/index.php")){
                                $files[] = array("fileName" => str_replace($path,"",$file->getPathname()),"hash" => md5_file($file->getPathname()));
                            }else if (!strpos($file->getPathname(),"cms/lib/plugins/")){
                                $files[] = array("fileName" => str_replace($path,"",$file->getPathname()),"hash" => md5_file($file->getPathname()));
                            }
                        }else{
                            $files[] = array("fileName" => str_replace($path,"",$file->getPathname()),"hash" => md5_file($file->getPathname()));
                        }

                }
            }
            header('Content-Type: application/json');
            $res = array("coreHashes" => $files);
            die(json_encode($res));
    }

}
function _getInstalledPlugins(){
    die(json_encode(["result" => 1,"data" => getSchemaPlugins()]));
}
function _updatePlugin($name){
    die(json_encode(["result" => 0,"error" => "sin terminar"]));
}
function _updateAllPlugins(){
    die(json_encode(["result" => 0,"error" => "sin terminar"]));
}

function _updateSchema($tableName){
    CocoWS::setToken(@$_REQUEST["token"]);
    if (!CocoWS::validateUploadToken(@$_REQUEST["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }
    $fileData = json_decode(file_get_contents('php://input'), true);
    if (@$fileData["schemaNew"] && @$fileData["tableName"]){
        CocoWS::insertOrUpdateSchema(@$fileData["tableName"],SCHEMAS_PATH,$fileData["schemaNew"]);
    }else{
        CocoWS::insertOrUpdateSchema(@$_REQUEST["tableName"],SCHEMAS_PATH);
    }
    
    die(json_encode(["result" => 3]));
}
function _updateAllSchemas(){
    CocoWS::setToken(@$_REQUEST["token"]);
    if (!CocoWS::validateUploadToken(@$_REQUEST["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }
    $allWSSchemas = CocoWS::getSchemaTables('','acai');
    $data = array_map(function($rec){ return $rec["tableName"]; },$allWSSchemas);

    $localSchemas = array_values(array_filter(array_map(function($rec){ if ($rec!==".." && $rec !== ".") return str_replace(".ini.php","",$rec); },scandir(SCHEMAS_PATH))));

    $diff = array_diff($localSchemas,$data);

    if (!@$data) die(json_encode(["result" => 0,"error" => "Error, schemas no encontrados"]));
    foreach(scandir(SCHEMAS_PATH) as $file){
        if ($file==".." || $file==".") continue;
        if (in_array(str_replace(".ini.php","",$file), $diff)) unlink(SCHEMAS_PATH."/".$file);
    }

    foreach($data as $schema){
        CocoWS::insertOrUpdateSchema($schema,SCHEMAS_PATH,@array_values(array_filter($allWSSchemas,function($rec) use ($schema) { return @$rec["tableName"] == $schema; }))[0]);
    }
    die(json_encode(["result" => 1]));
}

function _saveFile($coreUpdate = false) {

    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }

    if (!isset($fileData['fileName']) || !isset($fileData['content'])) {
        die(json_encode(['error' => ['message' => 'Datos no enviados', 'code' => 403]]));
    }

    $data = base64_decode($fileData['content']);
    if (!$data) {
        die(json_encode(['error' => ['message' => 'Datos no enviados', 'code' => 403]]));
    }

    $fileName = basename($fileData['fileName']);
    if (empty($fileName)) {
        die(json_encode(['error' => ['message' => 'Nombre no válido', 'code' => 403]]));
    }

    if (isset($fileData['zip'])) {

        $zipname = tempnam(sys_get_temp_dir(), 'zip').'.zip';
        file_put_contents($zipname, $data);

        $zip = new ZipArchive;

        if ($zip->open($zipname) === true) {

            if (!$coreUpdate){
                $zip->extractTo($GLOBALS["APP"]["pluginsdir"]."/".$fileName."/");
            }else if (@$_REQUEST["multisite"]){
                $zip->extractTo(realpath($GLOBALS["APP"]["pluginsdir"]."/../../../")."/");
            }else{
                $zip->extractTo(realpath($GLOBALS["APP"]["pluginsdir"]."/../")."/");
            }

            $zip->close();
            unlink($zipname);
        }
        else {
            $res["title"] = "Error";
            $res["error"] = ["message" => "Ha ocurrido un error descomprimiendo el plugin. Por favor, contacta con los administradores para solventarlo.", "code" => 500];
            die(json_encode($res));
        }
        die(json_encode(['success' => true]));
    }

    $allowed = [
        'application/pdf' => 'pdf',
        'application/zip' => 'zip',
        'audio/x-aac' => 'aac',
        'application/vnd.amazon.ebook' => 'azw',
        'audio/x-aiff' => 'aiff',
        'audio/mp3' => 'mp3',
        'audio/mpeg' => 'mp3',
        'image/bmp' => 'bmp',
        'text/css' => 'css',
        'text/csv' => 'csv',
        'text/plain' => 'csv',
        'text/calendar' => 'ics',
        'application/epub+zip' => 'epub',
        'image/gif' => 'gif',
        'image/x-icon' => 'ico',
        'image/vnd.microsoft.icon' => 'ico',
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => 'png',
        'image/svg+xml' => 'svg',
        'image/svg' => 'svg',
        'image/tiff' => 'tiff',
        'image/webp' => 'webp',
        'video/x-m4v' => 'm4v',
        'video/x-ms-wmv' => 'wmv',
        'video/mpeg' => 'mpeg',
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/ogg' => 'ogg',
        'application/vnd.oasis.opendocument.text' => 'odt',
        'application/vnd.oasis.opendocument.graphics' => 'odg',
        'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
        'application/vnd.oasis.opendocument.presentation' => 'odp',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/msword' => 'doc',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx'
    ];

    $f = finfo_open();
    $mime_type = finfo_buffer($f, $data, FILEINFO_MIME_TYPE);
    finfo_close($f);

    if (!isset($allowed[$mime_type])) {
        die(json_encode(['error' => ['message' => 'Tipo de archivo no válido', 'code' => 403]]));
    }

    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    if (is_array($allowed[$mime_type])) {
        if (!in_array($extension, $allowed[$mime_type])) {
            die(json_encode(['error' => ['message' => 'Nombre de archivo no válido', 'code' => 403, 'fileName' => $fileName, 'extension' => $extension, 'mime' => $mime_type]]));
        }
    }
    else {
        if ($extension !== $allowed[$mime_type]) {
            die(json_encode(['error' => ['message' => 'Nombre de archivo no válido', 'code' => 403, 'fileName' => $fileName, 'extension' => $extension, 'mime' => $mime_type]]));
        }
    }

    if (@$fileData["path"]){
        $path = realpath(__DIR__.'/../../../template/estandar/'.str_replace("..","",$fileData["path"]));
        if (!file_exists($path)) die(json_encode(['error' => ['message' => 'La ruta de destino no existe', 'code' => 403, 'fileName' => $fileName, 'extension' => $extension, 'mime' => $mime_type]]));
        $urlPath = str_replace(realpath(__DIR__.'/../../..'),"",realpath($path));
    }else{
        $path = realpath(__DIR__.'/../../uploads/');
        if (!file_exists($path)) die(json_encode(['error' => ['message' => 'La ruta de destino no existe', 'code' => 403, 'fileName' => $fileName, 'extension' => $extension, 'mime' => $mime_type]]));
        $urlPath = str_replace(realpath(__DIR__.'/../../..'),"",realpath($path));
        _clearCacheFiles();
    }

    file_put_contents($path."/".$fileName, $data);

    die(json_encode(['success' => true, 'filePath' => $path."/".$fileName, 'urlPath' => $urlPath."/".$fileName]));
}

function _removeFile() {

    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }

    if (!isset($fileData['file'])) {
        die(json_encode(['error' => ['message' => 'Datos no enviados', 'code' => 403]]));
    }

    $filePath = $fileData['file'];
    if (isset($fileData['plugin'])) {
        $filePath = $GLOBALS['APP']['pluginsdir'].'/'.$filePath;
    }

    $deleted = _deleteAll($filePath);

    die(json_encode(['success' => $deleted ? true : false]));
}

function _deleteAll($str) {
    if (!file_exists($str)) return false;
    if (is_file($str)) {
        return unlink($str);
    }
    elseif (is_dir($str)) {
        //Get a list of the files in this directory.
        $scan = glob(rtrim($str,'/').'/*');
        //Loop through the list of files.
        foreach($scan as $index=>$path) {
            //Call our recursive function.
            _deleteAll($path);
        }
        //Remove the directory itself.
        return @rmdir($str);
    }
}

function _validateCredentials() {

    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }

    $result = ["success" => true];

    die(json_encode($result));
}
function _getAllSchemas(){

    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }

    $result = [];
    foreach(scandir(SCHEMAS_PATH) as $file){
        $schemaName = str_replace(".ini.php","",$file);
        if (strpos($file,".ini.php")) $result[$schemaName] = loadINI(SCHEMAS_PATH."/".$file);
    }
    die(json_encode($result));

}

function _renameFile() {
    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }

    if (!isset($fileData['prevFile']) || !isset($fileData['newFile'])) {
        die(json_encode(['error' => ['message' => 'Datos no enviados', 'code' => 403]]));
    }

    $path = realpath(__DIR__.'/../../uploads/').'/';
    $prevFile = $path.basename($fileData['prevFile']);
    $newFile = $path.basename($fileData['newFile']);
    if (file_exists($prevFile)) {
        rename($prevFile, $newFile);
    }

    die(json_encode(['success' => file_exists($newFile)]));
}
