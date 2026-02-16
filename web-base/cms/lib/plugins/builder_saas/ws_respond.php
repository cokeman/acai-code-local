<?
if (!defined("APP_ROOT_PATH")) {
    define("APP_ROOT_PATH", realpath(__DIR__ . "/../../../../") . "/");
}
if (isset($_REQUEST["action_ws"])){ 
    header("Content-type:application/json");  

    global $wsTimeStamp;   

    switch($_REQUEST["action_ws"]){
        case "getModuleSchemas": die(json_encode(["result" => 1,"modules" => _getModules()])); break;
        case "getFullModule": die(json_encode(["result" => 1,"data" => getZipModule()])); break;
        case "getLayoutData": die(json_encode(["result" => 1,"data" => _getLayoutData()])); break;
        case "getHooksData": die(json_encode(["result" => 1,"data" => getHooksData()])); break;
        case "getTableData": die(json_encode(["result" => 1,"data" => _getTableData()])); break;
        case "getGitLog" : die(json_encode(["result" => 1,"data" => getGitLog()])); break;
        case "recoverGit" : die(json_encode(["result" => 1,"data" => recoverGit()])); break;
        case "saveLibrarie": _saveLibrarie(); break;
        case "compileTailwind": compileTailwind(); break;
        case "saveLexicalData": saveLexicalData(); break;
        case "set_builder_data": set_builder_data(); break;
        case "saveLayoutData": _saveLayoutData(); break;
        case "saveHooksData": saveHooksData(); break;
        case "saveModule": _saveModule(); break;
        case "saveFileBuilder": _saveFileBuilder(); break;
        case "removeFileBuilder": _removeFileBuilder(); break;
        case "deleteModule": _deleteModule(); break;
        case "checkCodeSyntax": checkCodeSyntax(); break;
        case "getAllLinks": getAllLinks(); break;
        case "moduleExists": moduleExists(); break;
        case "getLastUpdate": die(json_encode(getLastUpdate())); break;
        case "eraseData": eraseData(); break;
        case "checkModuleInWeb": checkModuleInWeb(); break;
        case "getFTPFiles": getFTPFiles(); break;
        case "generateMinCss" : require_once "builder_functions.php"; die(generateMinCssV2()); break;
        case "generateMinJs" : require_once "builder_functions.php"; die(generateMinJsV2()); break;
        case "checkModuleCode" : die(checkModuleCode()); break;
        case "cleanUploads": cleanUploads(); break;
        case "generateModuleFromString": _generateModuleFromString(); break;
    }

    require_once __DIR__."/mcp_respond.php";

    
}


function _generateModuleFromString(){
    // ocultar warnings php 
    error_reporting(E_ERROR | E_PARSE);

    $data = file_get_contents('php://input');
    header("Content-type:application/json");
    if (!@$data) die(json_encode(["error" => "Missing Data"]));
    $data = json_decode($data,true);
    require_once __DIR__."/funciones.php";
    //$respond = generateModuleFromString(@$data);
    $respond = ["result" => 1,"success" => true,"data" => "Función no implementada","dataReceived" => $data];
    die(json_encode($respond));
}

function cleanUploads(){
      require_once __DIR__."/../../classes/CocoDB.php";

      $fileData = json_decode(file_get_contents('php://input'), true);
      CocoWS::setToken(@$fileData["token"]);
      if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
          die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
      }

      $uploadsPath = realpath(__DIR__.'/../../../uploads');
      if (!$uploadsPath) {
          die(json_encode(['error' => ['message' => 'La ruta de uploads no existe', 'code' => 403]]));
      }

      // 1) Eliminar todos los archivos de uploads/webp
      $webpPath = $uploadsPath."/webp";
      $webpDeleted = [];
      if (is_dir($webpPath)) {
          foreach (scandir($webpPath) as $file) {
              if ($file === "." || $file === "..") continue;
              $full = $webpPath."/".$file;
              if (is_file($full) && @unlink($full)) {
                  $webpDeleted[] = $file;
              }
          }
      }
      // 1) Eliminar todos los archivos de uploads/webp
      $bigPath = $uploadsPath."/big";
      $bigDeleted = [];
      if (is_dir($bigPath)) {
          foreach (scandir($bigPath) as $file) {
              if ($file === "." || $file === "..") continue;
              $full = $bigPath."/".$file;
              if (is_file($full) && @unlink($full)) {
                  $bigDeleted[] = $file;
              }
          }
      }

      // 2) Listar archivos directos en uploads (no recursivo, excluyendo la carpeta webp)
      $uploadsFiles = [];
      foreach (scandir($uploadsPath) as $file) {
          if ($file === "." || $file === ".." || $file === "webp") continue;
          $full = $uploadsPath."/".$file;
          if (is_file($full)) {
              $uploadsFiles[] = $file;
          }
      }

      // Consultar referencias en BD
      $dbRecords = @mysql_query_fetch_all_assoc("SELECT filePath FROM cms_uploads");
      $dbPaths = [];
      if ($dbRecords) {
          foreach ($dbRecords as $row) {
              if (isset($row["filePath"])) {
                  $dbPaths[] = trim($row["filePath"]);
              }
          }
      }

       // Archivos sin referencia: moverlos a uploads/unusedFiles
      $unusedFiles = [];
      $unusedDir = $uploadsPath."/unusedFiles";
      if (!is_dir($unusedDir)) {
          @mkdir($unusedDir, 0777, true);
      }

      foreach ($uploadsFiles as $file) {
          $referenced = false;
          foreach ($dbPaths as $dbPath) {
              $dbPathClean = rtrim($dbPath);
              if (
                  $dbPathClean === $file ||
                  $dbPathClean === "uploads/".$file ||
                  $dbPathClean === "/uploads/".$file ||
                  substr($dbPathClean, -strlen($file)) === $file
              ) {
                  $referenced = true;
                  break;
              }
          }
          if (!$referenced) {
              $src = $uploadsPath."/".$file;
              $dst = $unusedDir."/".$file;
              if (@rename($src, $dst)) {
                  $unusedFiles[] = $file;
              }
          }
      }

      die(json_encode([
          "result" => 1,
          "webpDeleted" => $webpDeleted,
          "movedToUnused" => $unusedFiles
      ]));


      die(json_encode([
          "result" => 1,
          "unusedFiles" => $unusedFiles,
          "usedFiles" => $usedFiles,
      ]));
  }

function checkModuleCode(){
    $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];
    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    
    require_once __DIR__."/../../../../sesion.php";
    require_once __DIR__."/../../../../funciones.php";
    require_once __DIR__."/replace_code.php";
    require_once __DIR__."/builder_functions.php";
    if (file_exists(__DIR__."/../cms_api/v3/CmsApi.class.php")) require_once __DIR__."/../cms_api/v3/CmsApi.class.php";
    
    $path = realpath(__DIR__.'/'.$config['modulePath']);
    if (!@$path){
        die(json_encode(['error' => ['message' => 'La ruta de modulos no existe', 'code' => 403]]));
    }
    $modulePath = $path."/".$fileData['moduleName'];

    $result = BuilderModule($fileData['moduleName'],$fileData['vars']);

    return json_encode(["success" => true,"html" => $result]);
}

function getFTPFiles(){
    if (!@file_exists(__DIR__.'/../../../../template/estandar/chat_history/')) {
        mkdir(__DIR__.'/../../../../template/estandar/chat_history', 0777, true);
    }

    $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];
    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }

    $path = @realpath(__DIR__."/../../../../".(@$fileData["path"] ?: "./"));
    if (!$path) die(json_encode(["error" => 1,"message" => "La ruta no existe"]));

    if (is_dir($path)){
        $files = scandir($path);
        $fileAux = [];
        foreach($files as $file){
            $fileAux[] = ["filename" => $file,"isDir" => is_dir($path."/".$file),"extension" => $extension = strtolower(pathinfo($path."/".$file, PATHINFO_EXTENSION))]; 
        }
        die(json_encode($fileAux)); 
    }else{
        $info = pathinfo($path);
        $info["content"] = file_get_contents($path);
        $info["filename"] = $info["basename"];
        die(json_encode($info));
        //die(json_encode(["filename" => $file,"isDir" => is_dir($path.$file),"extension" => $extension = strtolower(pathinfo($path.$file, PATHINFO_EXTENSION)),"content" => $content]))
    }
}

function compileTailwind($path){
    unlink($path."/../../tailwind.config.js");
    unlink($path."/css/tailwindcss-custom.css");

    file_put_contents($path."/../../tailwind.config.js", 'module.exports = {content: ["./template/estandar/modulos/**/*.{php,vue,tpl,js}"],theme: {extend: {},},plugins: []}');

    if (defined("TAILWIND_PATH")){
        $result = @shell_exec('cd ~/httpdocs; '.TAILWIND_PATH.' -m -o ./template/estandar/css/tailwindcss-custom.css 2>&1;');
    }else{
        $result = @shell_exec('cd ~/httpdocs; tailwindcss -m -o ./template/estandar/css/tailwindcss-custom.css 2>&1;');    
    }
    return ["result" => 1,"success" => true,"data" => strpos($result,"Done") !== false ? file_get_contents($path."/css/tailwindcss-custom.css") : null];
}

function getZipModule(){
    global $SETTINGS;
    $TABLE_PREFIX = $SETTINGS["mysql"]["tablePrefix"];
    
    require_once __DIR__."/../../classes/CocoDB.php"; 
    
    $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];
    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }

    if (!isset($fileData['fileName'])) {
        die(json_encode(['error' => ['message' => 'Datos no enviados', 'code' => 403]]));
    }
    
    $fileName = basename($fileData['fileName']);
    if (empty($fileName)) {
        die(json_encode(['error' => ['message' => 'Nombre no válido', 'code' => 403]]));
    }
    
    $ruta = realpath(__DIR__.'/'.$config['modulePath']);
    if (!@$ruta){
        die(json_encode(['error' => ['message' => 'La ruta de modulos no existe', 'code' => 403]]));
    }
    
    $existe = file_exists($ruta."/".$fileName."/");
    
    if (!@$existe) die(json_encode(['success' => true,'noExiste' => true]));
    
    $rootPath = realpath($ruta."/".$fileName."/");

    $zipname = tempnam(sys_get_temp_dir(), 'zip').'.zip';

    $zip = new ZipArchive();
    $zip->open($zipname, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file)
    {
        if (!$file->isDir())
        {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootPath) + 1);

            $zip->addFile($filePath, $relativePath);
        }
    }

    $zip->close();

    return base64_encode(file_get_contents($zipname));

}
function checkModuleInWeb(){
    global $SETTINGS,$CURRENT_USER;
    $TABLE_PREFIX = $SETTINGS["mysql"]["tablePrefix"];
    
    require_once __DIR__."/../../classes/CocoDB.php"; 
    
    $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];
    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }

    try{
        $sql = "SELECT table_name FROM information_schema.columns WHERE column_name = 'builder'";
        $tables = mysql_query_fetch_all_assoc($sql); 
        $result = [];
        if (@$tables){
            $tablasWeb = [];
            
            foreach($tables as $table){
                $result = mysql_query_fetch_all_assoc("SELECT num,enlace FROM ".$table["table_name"]." WHERE builder LIKE '%".$fileData["module"]."%'");
                if ($result) {
                    $tablasWeb[$table["table_name"]] = array_map(function($rec){ return "https://".$_SERVER["HTTP_HOST"].$rec["enlace"];},$result);
                }
            }

            $tablasWebString = "<p>Este módulo se encuentra en las siguientes tablas:</p><ul class='w-full mt-4'>
                    <li class='flex w-full justify-between border border-gray-600 bg-gray-800'>
                        <span class='w-64 flex-shrink-0'>TABLA</span>
                        <span class='w-full'>ENLACES</span>
                    </li>";
            if ($tablasWeb){
                foreach($tablasWeb as $tabla => $nums){
                    $enlaces = "<ul class='text-left'>";
                    foreach($nums as $num){
                        $enlaces.="<li><a href='".$num."' target='_blank' class='leading-snug underline truncate'>".$num."</a></li>";
                    }
                    $enlaces.="</ul>";
                    $tablasWebString.="
                    <li class='flex w-full justify-between border border-gray-700'>
                        <span class='w-64 flex-shrink-0'>".$tabla."</span>
                        <span class='w-full pr-4'>".$enlaces."</span>
                    </li>";
                }
            }
            $tablasWebString.= "</ul>";

            if ($tablasWeb) {
                $message = $tablasWebString;
            }else{
                $message = "No encuentro el módulo en ninguna sección";
            }
        }

    }catch(Exception $e){
        $errors[] = $e->getMessage();
    }
    die(json_encode(["result" => 1,"success" => true,"message" => @$message])); 
}
function eraseData(){
    global $SETTINGS;
    $TABLE_PREFIX = $SETTINGS["mysql"]["tablePrefix"];
    
    require_once __DIR__."/../../classes/CocoDB.php"; 
    
    
    $unlinks = [];
    $sqls = [];
    $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];
    
    $fileData = json_decode(file_get_contents('php://input'), true);
        
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }
    
    $data = @$fileData["data"];
    if (!@$data) die(json_encode(['error' => ['message' => 'No data', 'code' => 403]]));
    
    if (!file_exists(__DIR__."/layout.json")) die(json_encode(['error' => ['message' => 'No layout data', 'code' => 403]]));
    
    $layout = file_get_contents(__DIR__."/layout.json");
    $layout = json_decode($layout,true);
    $prevLayout = $layout;
    
    if (@$layout["hooks"] && @$data["hooks"]) {
        $layout["hooks"] = array_filter($layout["hooks"],function($rec) use($data){ return !in_array($rec["endPoint"],$data["hooks"]); });
    }
    
    if (@$data["assets"]) {
        foreach($layout["librariesJSONt"] as $asset){
            if (in_array($asset["url"],$data["assets"]) && @file_exists(__DIR__."/../../../../".$asset["url"])) $unlinks[] = realpath(__DIR__."/../../../../".$asset["url"]);
        }
        foreach($layout["librariesJSONb"] as $asset){
            if (in_array($asset["url"],$data["assets"]) && @file_exists(__DIR__."/../../../../".$asset["url"])) $unlinks[] = realpath(__DIR__."/../../../../".$asset["url"]);
        }
        foreach($layout["librariesJSONAMP"] as $asset){
            if (in_array($asset["url"],$data["assets"]) && @file_exists(__DIR__."/../../../../".$asset["url"])) $unlinks[] = realpath(__DIR__."/../../../../".$asset["url"]);
        }
        
        if (@$layout["librariesJSONAMP"]) $layout["librariesJSONAMP"] = array_values(array_filter($layout["librariesJSONAMP"],function($rec) use($data){ return !in_array($rec["url"],$data["assets"]); }));
        if (@$layout["librariesJSONt"]) $layout["librariesJSONt"] = array_values(array_filter($layout["librariesJSONt"],function($rec) use($data){ return !in_array($rec["url"],$data["assets"]); }));
        if (@$layout["librariesJSONb"]) $layout["librariesJSONb"] = array_values(array_filter($layout["librariesJSONb"],function($rec) use($data){ return !in_array($rec["url"],$data["assets"]); }));
        
        $unlinks = array_unique($unlinks);
    }
    
    if (@$data["modules"]){
        
        $nums = @$data["sections"] ?: [0];
        
        foreach(scandir(__DIR__."/../../../../template/estandar/modulos/") as $module){
            if (in_array($module,$data["modules"])) {
                $existeEnApartado = mysql_query_fetch_all_assoc("SELECT * FROM ".$TABLE_PREFIX."apartados WHERE num NOT IN(".join(",",$nums).") and builder like '%".$module."%'");
                if (!@$existeEnApartado) $unlinks[] = realpath(__DIR__."/../../../../template/estandar/modulos/".$module);
            }
        } 
    }
    
    if (@$data["sections"]){
        $nums = @$data["sections"] ?: [0];
        $sqls[] = "DELETE FROM ".$TABLE_PREFIX."apartados WHERE num in(".join(",",$nums).")";
    }
    
    if (in_array("upload",@$data["otros"])){
        $sqls[] = "TRUNCATE TABLE ".$TABLE_PREFIX."uploads";
        if (file_exists(realpath(__DIR__."/../../../uploads/webp"))) $unlinks[] = realpath(__DIR__."/../../../uploads/webp");
        foreach(scandir(__DIR__."/../../../uploads/") as $file){
            if (!is_dir($file) && $file !== "." && $file !== ".."){
                $unlinks[] = realpath(__DIR__."/../../../uploads/".$file);
            }
        }
    }
    
    $errors = [];
    
    if ($layout !== $prevLayout){ 
        try{ 
            file_put_contents(__DIR__."/layout.json",json_encode($layout));
        }catch(Exception $e){
            $errors[] = $e->getMessage();
        }
    }
    if (@$sqls){ 
        foreach($sqls as $sql){ 
            try{ 
                mysql_query($sql); 
            }catch(Exception $e){
                $errors[] = $e->getMessage();
            }
            
        } 
    }
    if (@$unlinks){ 
        foreach($unlinks as $unlink){ 
            try{ 
                _deleteAll($unlink);
            }catch(Exception $e){
                $errors[] = $e->getMessage();
            }
        } 
    }
    
    die(json_encode(["result" => 1,"errors" => $errors,"layout" => $layout])); 
}

function set_builder_data(){
    require_once __DIR__."/../../classes/CocoDB.php"; 
    
    $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];
    
    $fileData = json_decode(file_get_contents('php://input'), true);
    
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenUpload"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }
    if (!isset($fileData['tableName'])) {
        die(json_encode(['error' => ['message' => 'No existe la tabla de destino', 'code' => 403]]));
    }
    if (!isset($fileData['recordNum'])) {
        die(json_encode(['error' => ['message' => 'No existe el registro de destino', 'code' => 403]]));
    }
    if (!isset($fileData['varName'])) {
        die(json_encode(['error' => ['message' => 'No existe el varName de destino', 'code' => 403]]));
    }
    if (!isset($fileData['sectionId'])) {
        die(json_encode(['error' => ['message' => 'No existe el sectionId de destino', 'code' => 403]]));
    }
    if (!isset($fileData['moduleName'])) {
        die(json_encode(['error' => ['message' => 'No existe el moduleName de destino', 'code' => 403]]));
    }
    if (!isset($fileData['content'])) {
        die(json_encode(['error' => ['message' => 'No existe el content de destino', 'code' => 403]]));
    }
    
    $apartado = CocoDB::get($fileData["tableName"],"num=".$fileData["recordNum"],"num desc",1);
    if (!@$apartado[0]["builder"]) die(json_encode(['error' => ['message' => 'Este registro no tiene modulos', 'code' => 403]]));
    
    $builderJSON = json_decode($apartado[0]["builder"],true);
    
    $moduleJSON = null;
    foreach($builderJSON as $buildJSON){
        if ($buildJSON["modulo"] == $fileData["moduleName"]) $moduleJSON = $buildJSON;
    }
    if (!@$moduleJSON) die(json_encode(['error' => ['message' => 'No se encuentra el módulo', 'code' => 403,"builder" => $builderJSON]]));
    
    $moduleJSON = $moduleJSON;
    if (!@$moduleJSON["config-vars"]) die(json_encode(['error' => ['message' => 'No se encuentra el config-vars', 'code' => 403]]));
    
    $varJSON = @$moduleJSON["config-vars"][$fileData["varName"]];
    if (!$varJSON)die(json_encode(['error' => ['message' => 'No se encuentra la variable', 'code' => 403]]));
    
    $recordNum = @$varJSON["recordNum"];
    if (!$recordNum)die(json_encode(['error' => ['message' => 'No se encuentra el recordNum en la variable', 'code' => 403]]));
    
    $tableName = @$varJSON["tableName"];
    if (!$recordNum)die(json_encode(['error' => ['message' => 'No se encuentra el tableName en la variable', 'code' => 403]]));
    
    $moduleConfig = @file_get_contents(__DIR__."/../../../../template/estandar/modulos/".$fileData["moduleName"]."/builder.json");
    if (!$moduleConfig)die(json_encode(['error' => ['message' => 'No se encuentra la conf del modulo', 'code' => 403]]));
    $moduleConfig = json_decode($moduleConfig,true);
    if (!@$moduleConfig["vars"][$fileData["varName"]]) die(json_encode(['error' => ['message' => 'No se encuentra la variable en la config del modulo', 'code' => 403]]));
    $varConfig = $moduleConfig["vars"][$fileData["varName"]];
    if (!@$varConfig["relations"][$tableName]) die(json_encode(['error' => ['message' => 'No se encuentra la tabla en la config del modulo', 'code' => 403]]));
    $campoFinal = $varConfig["relations"][$tableName];
    
    $dataRegistro = [$campoFinal => $fileData["content"]];
    
    $registro = CocoDB::updateRecords($tableName,$dataRegistro,"num=".$recordNum);
    
    if (intval(@$registro)){
        die(json_encode(['success' => ['message' => 'Guardado', 'code' => 403]]));
    }else{
        die(json_encode(['error' => ['message' => 'Error en el guardado', 'code' => 403]]));
    }
    
}
function saveLexicalData(){
    $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];
    
    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }
    if (!isset($fileData['parserType'])) {
        die(json_encode(['error' => ['message' => 'Debes seleccionar el tipo de analizador', 'code' => 403]]));
    }
    if (!isset($fileData['endPointFolder']) || !isset($fileData['content'] )) {
        die(json_encode(['error' => ['message' => 'Datos no enviados', 'code' => 403]]));
    }
    
    $path = realpath(__DIR__.'/'.$config['modulePath']);
    if (!@$path){
        die(json_encode(['error' => ['message' => 'La ruta de modulos no existe', 'code' => 403]]));
    }
    
    $modulePath = $path."/".$fileData['endPointFolder'];

    if (@$fileData["rawDataSended"]){
        $isUTF8 = mb_check_encoding($fileData['content'], "UTF-8");
        $data = $isUTF8 ? $fileData['content'] : utf8_encode($fileData['content']);
    }else{
        // Versiones anteriores en las que se enviaba en b64
        $isUTF8 = mb_check_encoding(base64_decode($fileData['content']), "UTF-8");
        $data = $isUTF8 ? base64_decode($fileData['content']) : utf8_encode(base64_decode($fileData['content']));
    }
    
    $lexicalType = $fileData['parserType'];
    
    switch($lexicalType){
        case "2":
            require_once "builder_functions.php";
        break;
    }
    $prefix = @$fileData["type"] ?: "index";
    
    $files = [
        $prefix."-twig.tpl" => $lexicalType == "2" ? compileTWIG($data,$modulePath) : ""
    ];
    
    foreach($files as $fileName => $content){
        if ($content !== "") {
            file_put_contents($modulePath."/".$fileName, $content);
        }else{
            file_put_contents($modulePath."/".$fileName, "");
        }
    } 
    
    if (@$fileData["aditionalFiles"]){
        foreach($fileData["aditionalFiles"] as $aditionalFile){
            file_put_contents($modulePath."/".$aditionalFile["fileName"], @$fileData["rawDataSended"] ? $aditionalFile["content"] : base64_decode($aditionalFile["content"]));
        }
    }
    
    //saveAMPPages();
    
    // COMMIT DE LOS CAMBIOS
    // $ultimoCommit = sendCommit($path);
    // 
    die(json_encode(['success' => true,'modulePath' => $modulePath, "lexicalType" => @$lexicalType]));
}
function saveAMPPages(){
    $path = realpath(__DIR__."/../../../../template/estandar/modulos");
    $result = array_filter(scandir($path."/"),function($rec){ return $rec!="." && $rec!=".."; });
    $files = [];
    foreach($result as $folder){
        $file = $path."/".$folder."/amp.tpl";
        if (file_exists($file) && filesize($file) && !in_array($folder,$files)) $files[] = $folder;
    }
    if (@$files) {
        $ampModulesJSON = __DIR__."/ampModules.json";
        if ((file_exists($ampModulesJSON) && is_writable($ampModulesJSON)) || (!file_exists($ampModulesJSON))){
            file_put_contents(__DIR__."/ampModules.json",json_encode($files));
        }else{
            die(json_encode(['error' => ['message' => "No es posible generar el archivo de AMP", 'code' => 403]]));  
        }
    }else if (file_exists(__DIR__."/ampModules.json")) {
        unlink(__DIR__."/ampModules.json");
    }
}

function getLastUpdate(){
    global $wsTimeStamp;
    
    // Obtiene la fecha de actualización del último archivo modificado en el plugin builder_saas

    $localTimeZone = date_default_timezone_get();

    $time = time();
    $lastUpdates = 0;
    $result = array_filter(scandir(__DIR__),function($rec){ return $rec!="." && $rec!=".." && $rec!="layout.json" && strpos($rec,"."); });
    $lastFile = '';
    foreach($result as $file){
        $time = filemtime(__DIR__."/".$file);
        if ($time>$lastUpdates) {
            $lastUpdates = $time;
            $lastFile = $file;
        }
    }
        
    $lastUpdates = adjustServerTimeStamp($lastUpdates);

    $diff = adjustServerTimeStamp($lastUpdates,true);

    // Anael: Esto es un apaño por la diferencia de hora entre servidores
    if(strpos($_SERVER['HTTP_HOST'], 'acai.cms.cocosolution.com') !== false) $lastUpdates -= 10; 

    return ['success' => true, 'lastUpdate' => $lastUpdates,'formated' => date("Y-m-d H:i:s",$lastUpdates),"Diff" => $diff,"wsTimeStamp" => $wsTimeStamp["now"],"linux" => shell_exec('date +"%Y-%m-%d %H:%M:%S"')];

}

function getLastUpdateFolder($folder,$lastUpdate = 0){
    $files = scandir($folder);
    
    foreach($files as $file){
        if ($file == "." || $file == "..") continue;
        $timeAux = intval(@filemtime($folder."/".$file));
        if ($timeAux > $lastUpdate) $lastUpdate = $timeAux;
    }

    return $lastUpdate;
}

function adjustServerTimeStamp($timestamp = 0,$getDiff = false){
    global $wsTimeStamp;

    $ctx = stream_context_create(['http'=> ['timeout' => 3]]);

    $wsTimeStamp = @$wsTimeStamp ?: json_decode(file_get_contents("https://ws.cocosolution.com/api/time", false, $ctx),true);

    $time = strtotime(@shell_exec('date +"%Y-%m-%d %H:%M:%S"'));

    $diff = intval($time) - strtotime(@$wsTimeStamp["shell"]);

    if ($getDiff) return $diff;
    // Esta solución la aportó Anael después de 2 horas intentando dar con una solución elegante.
    if (floatval(PHP_VERSION) > 8){   
    $timestamp = $timestamp + $diff;   
    }    
    return $timestamp;
}



function getAllLinks(){
    global $SETTINGS;
    $sql = "
    SELECT DISTINCT TABLE_NAME,COLUMN_NAME 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE COLUMN_NAME IN ('enlace')
        AND TABLE_SCHEMA='".$SETTINGS["mysql"]["database"]."'
    ";
    $resultEnlaces = [];
    $result = mysql_query_fetch_all_assoc($sql) or die(mysql_error());
    foreach($result as $res){
        $enlaces = mysql_query_fetch_all_assoc("SELECT num,'".$res["TABLE_NAME"]."' as tableName, enlace FROM ".$res["TABLE_NAME"]." WHERE enlace != ''");
        if (!@$enlaces) $enlaces = [];
        foreach($enlaces as $enlace){
            $resultEnlaces[] = $enlace;
        }
    }
    die(json_encode(['success' => true, 'data' => $resultEnlaces]));
}

function recoverGit(){
    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }
    $id = @$fileData["id"];
    if (empty($id)){
        die(json_encode(['error' => ['message' => 'Id no encontrado', 'code' => 403]]));
    }
    if (@$fileData["path"] == "/modulos/layout/"){
        $paths = [];
        list($paths[],$urlPath) = getPaths("/modulos/custom-header/",@$fileName);
        list($paths[],$urlPath) = getPaths("/modulos/custom-footer/",@$fileName);
        $paths[] = __DIR__."/layout.json";
        $path = join(" ",$paths);
    }else if (@$fileData["path"]){
        list($path,$urlPath) = getPaths(@$fileData["path"],@$fileName);    
        
    }else{
        $path = "";
        $urlPath = "";
    }
    
    if (empty($path) || $path == "") {
        //die(json_encode(['error' => ['message' => 'Path no encontrado', 'code' => 403]]));
        $command = 'cd ~/httpdocs; git checkout '.$id;
        $log = @shell_exec($command);
    } else{
        $command = 'cd ~/httpdocs; git checkout '.$id.' -- '.$path;
        $log = @shell_exec($command);
    }
    
    die(json_encode(['success' => true, 'executeCommand' => $command, 'filePath' => $path, 'urlPath' => $urlPath,'log' => $log]));
}
function getGitLog(){
    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    } 
    if (@$fileData["path"] == "/modulos/layout/"){
        $paths = [];
        list($paths[],$urlPath) = getPaths("/modulos/custom-header/",@$fileName);
        list($paths[],$urlPath) = getPaths("/modulos/custom-footer/",@$fileName);
        $paths[] = __DIR__."/layout.json";
        $path = join(" ",$paths);
    }else{
        list($path,$urlPath) = getPaths(@$fileData["path"],@$fileName);    
    }
    
    if (empty($path)) {
        die(json_encode(['error' => ['message' => 'Path no encontrado', 'code' => 403]]));
    }
    if (@$fileData["full"]){
        $logData = @shell_exec('cd ~/httpdocs; git show '.$fileData["full"].' -- '.$path);
        die(json_encode(['success' => true, 'filePath' => $path, 'urlPath' => $urlPath,'logData' => $logData]));
    }
    $log = @shell_exec('cd ~/httpdocs; git log --pretty=format:"%ci|%H" -- '.$path);
    $logArrayResult = [];
    $user = @str_replace("\n","",@shell_exec('echo "$USER"'));
    if (!empty($log)){
        $logArray = explode("\n",$log);    
        if (!empty($logArray)){
            foreach($logArray as $logLine){
                $result = array_filter(explode("|",$logLine));
                $result[0] = date("Y-m-d H:i:s",strtotime($result[0]));
                $logArrayResult[] = $result;
            }
        }
    }else{
        
        if (!empty($user)){
            $shell = @shell_exec('
                cd ~/httpdocs; 
                git init;
                wget -O ~/httpdocs/.gitignore https://cms.cocosolution.com/gitignorebase.txt;
                cd ~/httpdocs; 
                git add . ;
                cd ~/httpdocs; 
            ');
            $logArrayResult = @shell_exec('cd ~/httpdocs; git -c user.name="php" commit -m "Comienzo" 2>&1;');
            $logArrayResult.= $user;
            
//                            echo "#!/bin/sh\n# post-checkout hook:\n# chmod directories and executable files 0777,\n# chmod other files 0666. Exclude .git.\nfind . \( -name .git -type d -prune \) -o -exec chmod a+rwX \"{}\" \+\nfind . \( -name .git -type d -prune \) -o -exec chown '.$user.' -R \"{}\" \+" > ~/.git/hooks/post-checkout;

        }
    }
    
    die(json_encode(['success' => true, 'filePath' => $path, 'urlPath' => $urlPath,'log' => $logArrayResult,'user' => $user]));
}
function sendCommit($path){
    if (!$path) return null;   
    $ultimoCommit = @shell_exec("cd ~/httpdocs; git log -1 --format=%ci");
    if (@$ultimoCommit) {
        $shell_output = array();
        $status = NULL;
        //if (strtotime($ultimoCommit)<(time()-(5*60))){
        
            // git log -- $path
            // git log --pretty=format:"%ci|%H" -- $path
            // esto me devuelve los commit de una carpeta
        
            // git checkout 05ae1654bb59a115d79870044501f0ff9e6e054e -- $path
        
            $ultimoCommit = @shell_exec('cd ~/httpdocs; git add .; git -c user.name="php" commit -m "'.$path.' - '.date("Y-m-d H:i:s").'" 2>&1;');
        //} 
        // ESTO FUNCIONA SUPER BIEN PERO TENEMOS QUE PARAMETRIZARLO PARA QUE SE PUEDA CREAR EL CSS DESDE ACAI A PETICION Y NO EN CADA GUARDADO
        $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];
        
        /*
        DESACTIVADO PARA DEJAR PASO AL generateMinCssV2
        if (@$config["minimalCSSTail"]) {
            generateMinTailwind();          
        }else if(file_exists(__DIR__."/../../../../template/estandar/css/cocotail.min.css")){
            unlink(__DIR__."/../../../../template/estandar/css/cocotail.min.css"); 
        }*/
    }
    return $ultimoCommit;
}
function generateMinTailwind(){
    // DESACTIVADO PARA DEJAR PASO AL generateMinCssV2
    return;

    if (!file_exists(__DIR__."/../../../../template/estandar/tailwind.config.js")) {
        file_put_contents(__DIR__."/../../../../template/estandar/tailwind.config.js", 'module.exports = { defaultExtractor: (content) => content.match(/[^<>"\'`\s]*[^<>"\'`\s:]/g) || []};');
    }
    try{
        $logArrayResult = @shell_exec('cd ~/httpdocs/template/estandar; purgecss -c tailwind.config.js -css css/tailwind.min.css --content *.tpl js/*.js modulos/**/*.js modulos/**/*.tpl modulos/**/*.vue ../../../../cocotail-classes.tpl --output css/cocotail.min.css');
    }catch(Exception $e){}
}


function _removeFileBuilder($coreUpdate = false) {
    
    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }

    if (!isset($fileData['path'])) {
        die(json_encode(['error' => ['message' => 'Datos no enviados', 'code' => 403]]));
    }
    
    
    $fileName = basename($fileData['path']);
    if (empty($fileName)) {
        die(json_encode(['error' => ['message' => 'Nombre no válido', 'code' => 403]]));
    }
        
    if (@$fileData["path"]){
        $path = realpath(__DIR__.'/../../../../template/estandar/');
        if (!file_exists($path)) die(json_encode(['error' => ['message' => 'La ruta de destino del template no existe', 'code' => 403, 'fileName' => $fileName, 'extension' => $extension, 'mime' => $mime_type]]));
        $path = $path.=str_replace("..","",$fileData["path"]);
        if (!file_exists($path)){
            die(json_encode(['error' => ['message' => 'La ruta de destino no existe', 'code' => 403, 'fileName' => $fileName, 'extension' => @$extension, 'mime' => @$mime_type]]));
        }
    }
    
    unlink($path);
    
    die(json_encode(['success' => true, 'filePath' => $path]));
}
function checkCodeSyntax(){
    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }
    $path = @$fileData['path'];
    if (empty($path)) {
        die(json_encode(['error' => ['message' => 'Path no válido', 'code' => 403]]));
    }
    
    $realPath = realpath(__DIR__.'/../../../../template/estandar/modulos/'.$path."/index.tpl");
    if (empty($realPath)) {
        die(json_encode(['error' => ['message' => 'Path no válido', 'code' => 403]]));
    }
    
    $phpSyntax = "";
    
    try{
        $postdata = http_build_query(
            array(
                'code' => file_get_contents($realPath)
            )
        );

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postdata
            )
        );

        $context  = stream_context_create($opts);

        $phpSyntaxCad = file_get_contents("https://phpcodechecker.com/api/", false, $context);
        $jsonSyntax = json_decode($phpSyntaxCad,true);
        $phpSyntax = @$jsonSyntax["syntax"]["message"] ? @$jsonSyntax["syntax"]["message"]." <--- ".@$jsonSyntax["syntax"]["code"]." ---> " : "";
    }catch(Exception $e){
        $phpSyntax = $e->getMessage();
    }
    
    die(json_encode(['success' => true, 'filePath' => $path,'PHPSyntax' => $phpSyntax]));
}
function _saveFileBuilder($coreUpdate = false,$replaceData = [],$die = true) {
    
    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }
    if (@$replaceData){
        foreach($replaceData as $key => $value){
            $fileData[$key] = $value;
        }
    }
    if (!isset($fileData['fileName']) || !isset($fileData['content'])) {
        die(json_encode(['error' => ['message' => 'Datos no enviados', 'code' => 403]]));
    }
    
    //$data = base64_decode($fileData['content']);

    if (@$fileData["rawDataSended"]){
        $isUTF8 = mb_check_encoding($fileData['content'], "UTF-8");
        $data = $isUTF8 ? $fileData['content'] : utf8_encode($fileData['content']);
    }else{
        // Versiones anteriores en las que se enviaba en b64
        // Se ha quitado el check_encoding porque rompía las imágenes
        $isUTF8 = true; //mb_check_encoding(base64_decode($fileData['content']), "UTF-8");
        $data = $isUTF8 ? base64_decode($fileData['content']) : utf8_encode(base64_decode($fileData['content']));
        
    }
    
//    if (!$data) {
//        die(json_encode(['error' => ['message' => 'Datos no enviados', 'code' => 403]]));
//    }
    
    $fileName = basename($fileData['fileName']);
    if (empty($fileName)) {
        die(json_encode(['error' => ['message' => 'Nombre no válido', 'code' => 403]]));
    }

    if (isset($fileData['zip'])) {
        
        $zipname = tempnam(sys_get_temp_dir(), 'zip').'.zip';
        file_put_contents($zipname, $data);
        
        $zip = new ZipArchive;
        
        if ($zip->open($zipname) === true) {
            
            if (!$coreUpdate) 
                $zip->extractTo($GLOBALS["APP"]["pluginsdir"]."/".$fileName."/");
            else
                $zip->extractTo(realpath($GLOBALS["APP"]["pluginsdir"]."/../")."/");
            
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
        'application/octet-stream' => ['vue','css','js','html','tpl','php','png','jpg','gif','svg','md'],
        'audio/x-aac' => 'aac',
        'application/vnd.amazon.ebook' => 'azw',
        'audio/x-aiff' => 'aiff',
        'image/bmp' => 'bmp',
        'text/plain' => ['vue','css','js','html','tpl','php','md'],
        'text/csv' => 'csv',
        'text/x-php' => ['php','tpl'],
        'text/markdown' => 'md',
        'application/epub+zip' => 'epub',
        'image/gif' => 'gif',
        'image/x-icon' => 'ico',
        'image/vnd.microsoft.icon' => 'ico',
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png','ico'],
        'image/svg+xml' => 'svg',
        'image/svg' => 'svg',        
        'application/x-empty' => ['css','js','html','tpl','php'],
        'image/tiff' => 'tiff',
        'image/webp' => 'webp',
        'text/html' => ['html','tpl','php'],
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
    
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $isBinary = false;
    if ($extension == "js" || $extension == "css" || $extension == "tpl" || $extension == "vue" || $extension == "json" || $extension == "md" || ($extension == "php" && $fileName == "hook.php") ) {
        $mime_type = "text/plain";
    }else{
        $isBinary = true;
        if (!isset($allowed[$mime_type])) {
            die(json_encode(['error' => ['message' => 'Tipo de archivo no válido', 'code' => 403, 'fileName' => $fileName, 'extensions' => $extension, 'mime_type' => $mime_type]]));
        }        
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
    }
    
    if ((@$mime_type == "text/html" || @$mime_type == "text/plain") && @$fileData["path"]) $data = preg_replace_callback('|(\|\*)([^*]*)(\*\|)|',function($con){ return base64_decode(substr($con[0],2,strlen($con[0])-4)); },@$data);
    
    list($path,$urlPath) = getPaths(@$fileData["path"],@$fileName,@$extension,@$mime_type,@$fileData["rootFolder"] ? true : false);
    
    file_put_contents($path."/".$fileName, mb_check_encoding($data,"UTF-8") || $isBinary ? $data : utf8_encode($data));
    
    // COMMIT DE LOS CAMBIOS
    $ultimoCommit = sendCommit($path);
    
    if (@$die) {
        die(json_encode(['success' => true, 'filePath' => $path."/".$fileName, 'urlPath' => $urlPath."/".$fileName,'fechaCommit' => $ultimoCommit]));
    }else{
        return json_encode(['success' => true, 'filePath' => $path."/".$fileName, 'urlPath' => $urlPath."/".$fileName,'fechaCommit' => $ultimoCommit]);
    }
}
function getPaths($filePath,$fileName = null,$extension = null,$mime_type = null,$rootFolder = false){

    if (@$filePath){
        $path = $rootFolder ? realpath(__DIR__.'/../../../../') : realpath(__DIR__.'/../../../../template/estandar/');

        if (!file_exists($path)) die(json_encode(['error' => ['message' => 'La ruta de destino del template no existe', 'code' => 403, 'fileName' => $fileName, 'extension' => $extension, 'mime' => $mime_type]]));
        $path = $path.=str_replace("..","",$filePath);
        if (!file_exists($path)){
            mkdir($path);
        }
        if (!file_exists($path)) die(json_encode(['error' => ['message' => 'La ruta de destino no existe', 'code' => 403, 'fileName' => $fileName, 'extension' => $extension, 'mime' => $mime_type]]));
        $urlPath = str_replace(realpath(__DIR__.'/../../..'),"",realpath($path));
    }else{
        $path = realpath(__DIR__.'/../../../uploads/');
        if (!file_exists($path)) die(json_encode(['error' => ['message' => 'La ruta de destino no existe', 'code' => 403, 'fileName' => $fileName, 'extension' => $extension, 'mime' => $mime_type]]));
        $urlPath = str_replace(realpath(__DIR__.'/../../../..'),"",realpath($path));
    }
    return [$path,$urlPath];
}

// HOOKS_FILES_2 -> Nuevas funciones para la gestión de hooks desde archivos externos, de esta forma se pueden cargar desde acai code sin necesidad de añadirlos al layout.json
function getHooksData(){
    require_once __DIR__."/builder_functions.php";
    $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];

    if (!@$_REQUEST["remoteHooksToken"]){
        $fileData = json_decode(file_get_contents('php://input'), true);
        CocoWS::setToken(@$fileData["token"]);
        if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
            die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
        }
    } else if ($_REQUEST["remoteHooksToken"] !== "6342e78dfae29c3bc0f8e9c1f82676f1"){
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }
    
    if (!file_exists(__DIR__."/layout.json")) return ["header" => "","footer" => "","libraries" => "","style" => "","mantenimiento" => ""];
    $layout = file_get_contents(__DIR__."/layout.json");
    $resultHooks = [];
    try{
        $layout = json_decode($layout,true);
        
        // HOOKS_FILES -> Si existen archivos en la carpeta hooks, añádelos al layout para que se puedan cargar desde acai code
        $filesHooks = glob(__DIR__."/../../../../hooks/*.php");
        if ($filesHooks){
            if (!@$layout["hooks"]) $layout["hooks"] = [];
            addFilesHooksToLayout($layout["hooks"]); 
        }

        $resultHooks = @$layout["hooks"] ?: [];

        return $resultHooks;
    }catch(Exception $e){
        die(json_encode(['error' => ['message' => 'El formato del layout no es compatible', 'code' => 403]]));
    }

}

function _getLayoutData(){
    require_once __DIR__."/builder_functions.php";
    $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];
    
    if (!file_exists(__DIR__."/layout.json")) return ["header" => "","footer" => "","libraries" => "","style" => "","mantenimiento" => ""];
    $layout = file_get_contents(__DIR__."/layout.json");
        
    try{
        $layout = json_decode($layout,true);
        $layout["lastUpdate"] = adjustServerTimeStamp(filemtime(__DIR__."/layout.json"));

        $rutaImages = __DIR__."/../../../../template/estandar/images/";
        if (@$_REQUEST["getImageFolder"] && file_exists($rutaImages)){
            $result = array_filter(scandir($rutaImages),function($rec){ return $rec!="." && $rec!=".." && strpos($rec,"."); });
            $layout["imageFolder"] = $result;
        }

        // HOOKS_FILES -> Si existen archivos en la carpeta hooks, añádelos al layout para que se puedan cargar desde acai code
        //unset($layout["hooks"]);
        $filesHooks = glob(__DIR__."/../../../../hooks/*.php");
        if ($filesHooks){
            if (!@$layout["hooks"]) $layout["hooks"] = [];
            addFilesHooksToLayout($layout["hooks"]); 
        }
        
        return $layout;
    }catch(Exception $e){
        die(json_encode(['error' => ['message' => 'El formato del layout no es compatible', 'code' => 403]]));
    }
    
}
function _getTableData(){
    $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];
    
    $result = array("result" => 0);
    $fileData = json_decode(file_get_contents('php://input'), true);
    if (!$fileData["menu"]) die(json_encode(['error' => ['message' => 'No se ha suministrado el menu', 'code' => 403]]));
    
    $ds = DIRECTORY_SEPARATOR;
    $path = realpath(__DIR__.'/'.$config['modulePath']);
    $fileData["menu"] = "custom-".$fileData["menu"];
    $arrayModules = [];
    $modules = scandir($path);
    $schema = [];
    
    if (@$modules){
        
        $result["result"] = 1;
        $result["data"] = array();
        foreach($modules as $module):
            
            if ($module!=$fileData["menu"]) continue;

            $moduleTablePath = $path."/".str_replace(".tpl","",$module);

            if ($module==$fileData["menu"]){

                $schema["lastUpdate"] = adjustServerTimeStamp(getLastUpdateFolder($moduleTablePath,0));
                
                $htmlData = @file_get_contents($moduleTablePath."/index-base.tpl");
                if (@$htmlData) $schema["htmlData"] = $htmlData;
                $ampHtmlData = @file_get_contents($moduleTablePath."/amp-base.tpl");
                if (@$ampHtmlData) $schema["ampHtmlData"] = $ampHtmlData;
                $styleData = @file_get_contents($moduleTablePath."/style.css");
                if (@$styleData) $schema["styleData"] = $styleData;
                $javascriptData = @file_get_contents($moduleTablePath."/script.js");
                if (@$javascriptData) $schema["javascriptData"] = $javascriptData;
                return $schema;
            }
        endforeach;
        try{
            if (!file_exists($path."/".$fileData["menu"])) mkdir($path."/".$fileData["menu"]);
            if (!file_exists($path."/".$fileData["menu"]."/index-base.tpl")) file_put_contents($path."/".$fileData["menu"]."/index-base.tpl",'');
            if (!file_exists($path."/".$fileData["menu"]."/index.tpl")) file_put_contents($path."/".$fileData["menu"]."/index.tpl",'');
            if (!file_exists($path."/".$fileData["menu"]."/amp.tpl")) file_put_contents($path."/".$fileData["menu"]."/amp.tpl",'');
            if (!file_exists($path."/".$fileData["menu"]."/amp-base.tpl")) file_put_contents($path."/".$fileData["menu"]."/amp-base.tpl",'');
            if (!file_exists($path."/".$fileData["menu"]."/style.css")) file_put_contents($path."/".$fileData["menu"]."/style.css",'');
            if (!file_exists($path."/".$fileData["menu"]."/script.js")) file_put_contents($path."/".$fileData["menu"]."/script.js",'');
            return ["htmlData" => '','ampHtmlData' => '',"styleData" => '',"javascriptData" => ''];
        }catch(Exception $e){
            die(json_encode(['error' => ['message' => 'Error al crear el módulo', 'code' => 405]]));
        }
    }
    die(json_encode(['error' => ['message' => 'No se ha suministrado el menu', 'code' => 403]]));
    
}
function _saveLibrarie(){
    
    $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];
    
    $fileData = json_decode(file_get_contents('php://input'), true);
    $permitidos = ["css","js"];
    if (!@$fileData["data"]["filename"]) die(json_encode(['error' => ['message' => "No se ha enviado el nombre de archivo",'code' => 403]]));
    if (!@$fileData["data"]["content"]) die(json_encode(['error' => ['message' => "No se ha enviado contenido del archivo",'code' => 403]]));
    if (!@$fileData["data"]["type"] || !in_array(strtolower(@$fileData["data"]["type"]),$permitidos)) die(json_encode(['error' => ['message' => "Type no permitido",'code' => 403]]));
    
    $ruta = null;
    $type = "css";
    
    switch(strtolower($fileData["data"]["type"])){
        case "css":$ruta = realpath(__DIR__.'/'.$config['modulePath']."/../css/");$type="css";break;
        case "js":$ruta = realpath(__DIR__.'/'.$config['modulePath']."/../js/");$type="js";break;
    }
    if (!@$ruta){
        die(json_encode(['error' => ['message' => 'La ruta de modulos no existe', 'code' => 403]]));
    }
    try{
        $data = mb_check_encoding(@$fileData["data"]["content"],"UTF-8") ? @$fileData["data"]["content"] : utf8_encode(@$fileData["data"]["content"]);
        file_put_contents($ruta."/".pathinfo($fileData["data"]["filename"])["filename"].".".$type,$data);
        return die(json_encode(['success' => true,'data' => protocol()."://".$_SERVER["HTTP_HOST"]."/template/estandar/".pathinfo($fileData["data"]["filename"])["filename"].".".$type]));
    }catch(Exception $e){
        die(json_encode(['error' => ['message' => 'No se puede guardar el layout', 'code' => 403]]));
    }
}

function createHooksFiles($hooksArray, $deletePreviousFiles = false){
    
    $pathHooks = APP_ROOT_PATH."/hooks";
    if (!file_exists($pathHooks)) mkdir($pathHooks);
    
    if ($deletePreviousFiles) {
        $files = glob($pathHooks."/*.php");
        foreach($files as $file){
            unlink($file);
        }
    }
    
    foreach($hooksArray as $hook){
        $hookName = array_values(array_filter(explode(DIRECTORY_SEPARATOR, $hook["endPoint"])));
        $hookName = join(".",$hookName);
        $hookFilePath = $pathHooks.DIRECTORY_SEPARATOR.$hookName.".php";
        file_put_contents($hookFilePath,base64_decode($hook["code"]));
    }

}

function saveHooksData(){
    require_once "builder_functions.php";
    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }
    try{
        
        createHooksFiles(@$fileData["data"]["hooks"] ? $fileData["data"]["hooks"] : [],true);
        $layout = file_get_contents(__DIR__."/layout.json");
        $layout = json_decode($layout,true);
        $layout["hooks"] = @$fileData["data"]["hooks"] ? array_map(function($hook){ $hook["code"] = "code_hidden_for_security"; return $hook; },$fileData["data"]["hooks"]) : [];
        file_put_contents(__DIR__."/layout.json",json_encode($layout,JSON_PRETTY_PRINT));
        return die(json_encode(['success' => true,'hooks' => $fileData["data"]["hooks"]]));
    }catch(Exception $e){
        die(json_encode(['error' => ['message' => 'No se pueden guardar los hooks', 'code' => 403]]));
    }
}

function _saveLayoutData(){
    require_once "builder_functions.php";
    
    $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];
    $modulosACrear = ["header","footer"];
    
    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }
    
    $ruta = realpath(__DIR__.'/'.$config['modulePath']);
    if (!@$ruta){
        die(json_encode(['error' => ['message' => 'La ruta de modulos no existe', 'code' => 403]]));
    }
    
    try{
        //createHooksFiles(@$fileData["data"]["hooks"] ? $fileData["data"]["hooks"] : []);

        // HOOKS_FILES -> Los hooks se gestionan de forma independiente por saveHooksData,
        // preservamos los hooks actuales del layout.json para evitar sobreescrituras
        $existingLayout = json_decode(file_get_contents(__DIR__."/layout.json"), true);
        $fileData["data"]["hooks"] = @$existingLayout["hooks"] ?: [];

        file_put_contents(__DIR__."/layout.json",json_encode($fileData["data"],JSON_PRETTY_PRINT));
        
        foreach($fileData["data"] as $key => $value){
            if (!in_array($key,$modulosACrear)) continue;
            if (!file_exists($ruta."/custom-".$key."/")) mkdir($ruta."/custom-".$key."/");
            
            if (!file_exists($ruta."/custom-".$key."-twig/")) mkdir($ruta."/custom-".$key."-twig/");

            if (@$fileData["data"][$key."_php"]){
                file_put_contents($ruta."/custom-".$key."/index-base.tpl",$fileData["data"][$key]);        
                file_put_contents($ruta."/custom-".$key."/index.tpl",$fileData["data"][$key."_php"]);
            }else{
                file_put_contents($ruta."/custom-".$key."/index.tpl",$fileData["data"][$key]);
            }

            // VERSION TWIG
            if (@$fileData[$key."ModuleCustom"]){
                file_put_contents($ruta."/custom-".$key."-twig/index-base.tpl",$fileData["data"][$key]);
                file_put_contents($ruta."/custom-".$key."-twig/index.tpl",@$fileData[$key."ModuleCustom"]["htmlParsed"]);
                
                $resultTWIG = compileTWIG($fileData[$key."ModuleCustom"]["htmlParsed"],$ruta."/custom-".$key."-twig/");
                file_put_contents($ruta."/custom-".$key."-twig/index-twig.tpl",$resultTWIG);
            }

            
        }
        if (@$fileData["data"]["mantenimiento"]){
            file_put_contents($ruta."/../mantenimiento.tpl",$fileData["data"]["mantenimiento"]);
        }
        sendCommit($ruta."/custom-header/ ".$ruta."/custom-footer/");
        return die(json_encode(['success' => true]));
    }catch(Exception $e){
        die(json_encode(['error' => ['message' => 'No se puede guardar el layout', 'code' => 403]]));
    }
    
}
function saveLayoutData_ANTIGUO(){
    
    $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];
    
    $fileData = json_decode(file_get_contents('php://input'), true);
    
    try{
        file_put_contents(__DIR__."/layout.json",json_encode($fileData["data"]));
        return die(json_encode(['success' => true]));
    }catch(Exception $e){
        die(json_encode(['error' => ['message' => 'No se puede guardar el layout', 'code' => 403]]));
    }
}
function moduleExists(){
    $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];
    $fileData = json_decode(file_get_contents('php://input'), true);
    $fileName = basename($fileData['fileName']);
    if (empty($fileName)) {
        die(json_encode(['error' => ['message' => 'Nombre no válido', 'code' => 403]]));
    }
    $ruta = realpath(__DIR__.'/'.$config['modulePath']);
    if (!@$ruta){
        die(json_encode(['error' => ['message' => 'La ruta de modulos no existe', 'code' => 403]]));
    }
    $existe = file_exists($ruta."/".$fileName."/");
    
    if (@$existe) 
        die(json_encode(['success' => true,'yaExiste' => true]));
    else
        die(json_encode(['success' => true,'yaExiste' => false]));
}

function _deleteModule(){
    $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];
    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }

    if (!isset($fileData['fileName'])) {
        die(json_encode(['error' => ['message' => 'Datos no enviados', 'code' => 403]]));
    }
    
    $fileName = basename($fileData['fileName']);
    if (empty($fileName)) {
        die(json_encode(['error' => ['message' => 'Nombre no válido', 'code' => 403]]));
    }
    
    $ruta = realpath(__DIR__.'/'.$config['modulePath']);
    if (!@$ruta){
        die(json_encode(['error' => ['message' => 'La ruta de modulos no existe', 'code' => 403]]));
    }
    
    $existe = file_exists($ruta."/".$fileName."/");
    
    if (!@$existe) die(json_encode(['success' => true,'noExiste' => true]));
    
    if (!is_dir($ruta."/".$fileName."/")) die(json_encode(['error' => ['message' => 'No es una carpeta', 'code' => 403]]));
    
    try{
        $files = scandir($ruta."/".$fileName."/");
        foreach($files as $file){
            if ($file!="." && $file!=".."){
                if (file_exists($ruta."/".$fileName."/".$file) && !unlink($ruta."/".$fileName."/".$file)) die(json_encode(['error' => ['message' => 'El modulo no se puede eliminar', 'code' => 403]]));
            }
        }
        rmdir($ruta."/".$fileName."/");
    }catch(Exception $e){
        die(json_encode(['error' => ['message' => 'El modulo no se puede eliminar', 'code' => 403]]));
    }
    die(json_encode(['success' => true]));
}

function _saveModule(){
    $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];
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
    
    $ruta = realpath(__DIR__.'/'.$config['modulePath']);
    if (!@$ruta){
        die(json_encode(['error' => ['message' => 'La ruta de modulos no existe', 'code' => 403]]));
    }
    
    $existe = file_exists($ruta."/".$fileName."/");
    
    if (@$existe && !@$fileData["replace"]) die(json_encode(['success' => true,'yaExiste' => true]));
    

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
        $ultimoCommit = sendCommit($ruta);
        
        die(json_encode(['success' => true,"git" => $ultimoCommit]));
    }
    
}

function _getModules($path=null) {
    $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];
    if (!defined("RUTA_PLANTILLA")) define("RUTA_PLANTILLA","/template/estandar");
    $result = array("result" => 0);
    $fileData = json_decode(file_get_contents('php://input'), true);
    if (@$_REQUEST) $fileData=array_merge(@$fileData ?: [],$_REQUEST);
    $ds = DIRECTORY_SEPARATOR;
    $path = realpath(__DIR__.'/'.$config['modulePath']);
    $arrayModules = [];
    $modules = scandir($path);
    if (@$modules){
        
        $result["result"] = 1;
        $result["data"] = array();
        foreach($modules as $module):
            if (@$fileData["ids"] && !in_array(str_replace(".tpl","",$module),$fileData["ids"])) continue;
            if ($module!="." && $module!=".."){
                
                if (file_exists($path.$ds.str_replace(".tpl","",$module)."/builder.json")){
                    $array = array(
                        "id" => $module,
                        "path" => protocol()."://".$_SERVER["HTTP_HOST"].$ds.$config['modulePathAux'].$ds.str_replace(".tpl","",$module)
                    );
                    
                    $schema = file_get_contents($path."/".str_replace(".tpl","",$module)."/builder.json");
                    
                    $schema = json_decode(preg_replace('/\s*(?!<\")\/\*[^\*]+\*\/(?!\")\s*/', '', $schema),true);

                    $builderVue = file_exists($path.$ds.str_replace(".tpl","",$module)."/builder.vue") ? "https://".$_SERVER["HTTP_HOST"].substr($config["modulePath"],strpos($config["modulePath"],"/template/estandar/modulos")).$module."/builder.vue" : null;
                    if (@$builderVue) $schema["builderVue"] = $builderVue;

                    // SET LASTUPDATE
                    $schema["lastUpdate"] = getLastUpdateFolder($path."/".str_replace(".tpl","",$module),0);                    

                    if (@$schema["editable"] && @$fileData["full"]){

                        $htmlData = @file_get_contents($path."/".str_replace(".tpl","",$module)."/index-base.tpl");
                        if (@$htmlData) $schema["htmlData"] = $htmlData;
                        /*$htmlDataParsed = @file_get_contents($path."/".str_replace(".tpl","",$module)."/index.tpl");
                        if (@$htmlDataParsed) $schema["htmlDataParsed"] = $htmlDataParsed;*/
                        
                        if (@$fileData["twig"]){
                            $htmlDataTWIG = @file_get_contents($path."/".str_replace(".tpl","",$module)."/index-twig.tpl");
                            if (@$htmlDataTWIG) $schema["htmlDataTWIG"] = $htmlDataTWIG;
                        }

                        $ampHtmlData = @file_get_contents($path."/".str_replace(".tpl","",$module)."/amp-base.tpl");
                        if (@$ampHtmlData) $schema["ampHtmlData"] = $ampHtmlData;
                        $styleData = @file_get_contents($path."/".str_replace(".tpl","",$module)."/style.css");
                        if (@$styleData) $schema["styleData"] = $styleData;
                        if (@$styleData) $schema["styleFilename"] = h("/modulos/".str_replace(".tpl","",$module)."/style.css");
                        
                        $javascriptData = @file_get_contents($path."/".str_replace(".tpl","",$module)."/script.js");
                        if (@$javascriptData) $schema["javascriptData"] = $javascriptData;
                        if (@$javascriptData) $schema["javascriptFilename"] = h("/modulos/".str_replace(".tpl","",$module)."/script.js");

                        $hookData = @file_get_contents($path."/".str_replace(".tpl","",$module)."/hook.php");
                        if (@$hookData) $schema["hookData"] = $hookData;
                        if (@$hookData) $schema["hookFilename"] = "/modulos/".str_replace(".tpl","",$module)."/hook.php";
                        
                        // SET THUMBNAIL
                        if (@$fileData["withImage"]){
                            $imageData = @file_get_contents($path."/".str_replace(".tpl","",$module)."/thumbnail.jpg");
                            if (@$imageData) $schema["imageData"] = base64_encode($imageData);   
                        }

                        // SET ASSETS
                        $assetsPath = $path."/".str_replace(".tpl","",$module)."/assets/";
                        if (file_exists($assetsPath)){
                            $files = scandir($assetsPath);
                            $resultAssets = [];
                            foreach($files as $file){
                                if ($file == "." || $file == "..") continue;
                                $resultAssets[] = $file;

                                // REASIGNO LAST UPDATE POR AQUI TAMBIEN
                                $timeAux = intval(@filemtime($assetsPath.$file));
                                if ($timeAux > $schema["lastUpdate"]) $schema["lastUpdate"] = $timeAux;
                            }
                            $schema["assets"] = $resultAssets;
                        }

                    }
                    
                    if (@$schema){
                        
                        $schema["lastUpdate"] = adjustServerTimeStamp(intval(@$schema["lastUpdate"]));

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
