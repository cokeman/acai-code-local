<?

if (isset($_REQUEST["action_ws"])){
    header("Content-type:application/json");
    switch($_REQUEST["action_ws"]){
        case "getModuleSchemas": die(json_encode(["result" => 1,"modules" => getModules()])); break;
        case "saveModule": saveModule(); break;
        case "moduleExists": moduleExists(); break;
    }
}

function moduleExists(){
    $fileData = json_decode(file_get_contents('php://input'), true);
    $fileName = basename($fileData['fileName']);
    if (empty($fileName)) {
        die(json_encode(['error' => ['message' => 'Nombre no válido', 'code' => 403]]));
    }
    $ruta = realpath(__DIR__."/../../../../template/estandar/modulos/");
    if (!@$ruta){
        die(json_encode(['error' => ['message' => 'La ruta de modulos no existe', 'code' => 403]]));
    }
    $existe = file_exists($ruta."/".$fileName."/");
    
    if (@$existe) 
        die(json_encode(['success' => true,'yaExiste' => true]));
    else
        die(json_encode(['success' => true,'yaExiste' => false]));
}

function saveModule(){
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
    
    $ruta = realpath(__DIR__."/../../../../template/estandar/modulos/");
    if (!@$ruta){
        die(json_encode(['error' => ['message' => 'La ruta de modulos no existe', 'code' => 403]]));
    }
    
    $existe = file_exists($ruta."/".$fileName."/");
    
    if (@$existe) die(json_encode(['success' => true,'yaExiste' => true]));
    

    if (isset($fileData['zip'])) {
        
        $zipname = tempnam(sys_get_temp_dir(), 'zip').'.zip';
        file_put_contents($zipname, $data);
        
        $zip = new ZipArchive;
        
        if ($zip->open($zipname) === true) {
            
            $zip->extractTo($ruta."/".$fileName."/");
            
            $zip->close();
            unlink($zipname);
        }
        else {
            $res["title"] = "Error";
            $res["error"] = ["message" => "Ha ocurrido un error descomprimiendo el modulo. Por favor, contacta con los administradores para solventarlo.", "code" => 500];
            die(json_encode($res));
        }
        die(json_encode(['success' => true]));
    }
    
}

function getModules($path="template/estandar/modulos"){
    $result = array("result" => 0);
    $ds = DIRECTORY_SEPARATOR;
    $pathAux = $path;
    $path = realpath(dirname(__FILE__)."{$ds}..{$ds}..{$ds}..{$ds}..{$ds}".$path.$ds);
    $arrayModules = [];
    
    $modules = scandir($path);
    if (@$modules){
        
        $result["result"] = 1;
        $result["data"] = array();
        foreach($modules as $module):
            if ($module!="." && $module!=".."){
                
                if (file_exists($path.$ds.str_replace(".tpl","",$module)."/builder.json")){
                    $array = array(
                        "id" => $module,
                        "path" => protocol()."://".$_SERVER["HTTP_HOST"].$ds.$pathAux.$ds.str_replace(".tpl","",$module)
                    );
                    
                    $schema = file_get_contents(dirname(__FILE__)."/../../../../template/estandar/modulos/".str_replace(".tpl","",$module)."/builder.json");
                    
                    $schema = json_decode(preg_replace('/\s*(?!<\")\/\*[^\*]+\*\/(?!\")\s*/', '', $schema),true);
                    
                    if (@$schema){
                        foreach($schema as $key => $value):
                            $array[$key] = $value;    
                        endforeach;
                    }
                    
                    $arrayModules[$module] = $array;
                }
                
            }
        endforeach;
        return $arrayModules;
    }
    return [];
    
}
?>