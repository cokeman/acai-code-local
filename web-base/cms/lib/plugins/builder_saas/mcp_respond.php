<?
switch($_REQUEST["action_ws"]){
    case "cmsApi": die(json_encode(MCP_executeCmsApiAction())); break;
    case "setStaticVars": die(json_encode(MCP_setStaticVars())); break;
    case "addModuleToRecord": die(json_encode(MCP_addModuleToRecord())); break;
    case "getHistoryData": die(json_encode(MCP_getHistoryData())); break;
    case "executeGitCommand" : die(json_encode(["result" => 1,"data" => executeGitCommand()])); break;
}

function executeGitCommand(){
    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }
    if (!@$fileData["command"] || substr(@$fileData["command"],0,3) != "git"){
        die(json_encode(['error' => ['message' => 'Comando no válido. Debes usar un comando git', 'code' => 403]]));
    }

    $command = @$fileData["command"];
    if (empty($command)){
        die(json_encode(['error' => ['message' => 'Command no encontrado', 'code' => 403]]));
    }

    // Seguridad: solo permitir comandos git legítimos
    $command = str_replace(["\n", "\r"], "", $command);

    // Bloquear metacaracteres de shell que permiten encadenar comandos
    $forbidden = [';', '&&', '||', '|', '`', '$(', '>', '<', '{', '}'];
    foreach ($forbidden as $char) {
        if (strpos($command, $char) !== false) {
            die(json_encode(['error' => ['message' => 'Comando bloqueado: caracteres no permitidos', 'code' => 403]]));
        }
    }

    // Whitelist de subcomandos git permitidos
    $allowedSubcommands = [
        'status', 'log', 'diff', 'add', 'commit', 'push', 'pull',
        'checkout', 'branch', 'merge', 'fetch', 'reset', 'stash',
        'show', 'tag', 'rebase', 'cherry-pick', 'rev-parse', 'remote'
    ];
    // Extraer el subcomando (segunda palabra después de "git")
    $parts = preg_split('/\s+/', trim($command));
    if ($parts[0] !== 'git' || count($parts) < 2 || !in_array($parts[1], $allowedSubcommands)) {
        die(json_encode(['error' => ['message' => 'Subcomando git no permitido', 'code' => 403]]));
    }

    $log = @shell_exec('cd ~/httpdocs; '.$command.' 2>&1');
    
    die(json_encode(['success' => true, 'executeCommand' => $command,'log' => $log]));
}

function MCP_getHistoryData(){
    if (!@file_exists(__DIR__.'/../../../../template/estandar/chat_history/')) {
        mkdir(__DIR__.'/../../../../template/estandar/chat_history', 0777, true);
    }

    $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];
    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }

    $path = @realpath(__DIR__."/../../../../template/estandar/chat_history/");
    if (!$path) die(json_encode(["error" => 1,"message" => "La ruta no existe"]));
    $data = [];
    if (is_dir($path)){
        $files = scandir($path);
        $fileAux = [];
        foreach($files as $cont => $file){
            if ($cont > 12) continue;
            if ($file == "." || $file == "..") continue;
            $fileAux[] = ["filename" => $file,"data" => json_decode(file_get_contents($path."/".$file), true) ]; 
        }
        die(json_encode($fileAux)); 
    }
}
function MCP_addModuleToRecord(){
    $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];
    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }
    
    try {
        if (empty($fileData["moduleId"]) || empty($fileData["recordNum"]) || empty($fileData["tableName"])) {
            return ['error' => ['message' => 'Faltan los parámetros moduleId o recordNum o tableName', 'code' => 400]];
        }
        
        $moduleId = $fileData["moduleId"];
        $recordNum = $fileData["recordNum"];
        $tableName = $fileData["tableName"];

        if (empty($fileData["modulePosition"])) {
            $modulePosition = 0;
        }else{
            $modulePosition = intval($fileData["modulePosition"]);
        }
        
        $recordDB = @CocoDB::get($tableName, "num=".$recordNum)[0];
        if (!$recordDB) {
            return ['error' => ['message' => 'No se encontró el registro especificado', 'code' => 404]];
        }
        if (!@$recordDB["builder"]) {
            return ['error' => ['message' => 'El registro no tiene un campo builder válido', 'code' => 400]];
        }
        try {
            $builderData = json_decode($recordDB["builder"], true);
        } catch (Exception $e) {
            return ['error' => ['message' => 'Error al decodificar el campo builder del registro', 'code' => 400]];
        }

        // $builderData debe ser un array, hay que colocar el módulo en la posición indicada
        if (!is_array($builderData)) {
            return ['error' => ['message' => 'El campo builder del registro no es un array válido', 'code' => 400]];
        }
        
        $moduleData = [
            "modulo" => $moduleId,
            "section_id" => uniqid(),
            "oculto" => false,
            "config-vars" => []
        ];
        array_splice($builderData, $modulePosition, 0, [ $moduleData ]);

        // Guardar el nuevo builderData en el registro
        $updateCount = CocoDB::updateRecords($tableName, [ "builder" => json_encode($builderData, JSON_UNESCAPED_UNICODE) ], "num=".$recordNum);
        
        $result = ["success" => true, "data" => $updateCount];

    } catch (Exception $e) {
        return ['error' => ['message' => $e->getMessage(), 'code' => 403]];
    }
    return $result;
}

function MCP_setStaticVars(){
    $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];
    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }

    try {
        if (empty($fileData["moduleId"]) || empty($fileData["staticVars"])) {
            return ['error' => ['message' => 'Faltan los parámetros moduleId o staticVars', 'code' => 400]];
        }
        $moduleId = $fileData["moduleId"];
        $staticVars = $fileData["staticVars"];
        $path = realpath(__DIR__.'/'.$config['modulePath']);

        $schema = file_get_contents($path."/".$moduleId."/builder.json");
        $data = json_decode($schema, true);
        if (!$data) {
            throw new Exception("Error al leer el esquema del módulo.");
        }
        $data["staticVars"] = $staticVars;
        file_put_contents($path."/".$moduleId."/builder.json", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $result = ["success" => true,"staticVars" => $staticVars,"schema" => $schema];
    } catch (Exception $e) {
        return ['error' => ['message' => $e->getMessage(), 'code' => 403]];
    }
    return $result;
}

function MCP_executeCmsApiAction(){
    $config = loadINI(__DIR__.'/custom-schema.ini.php')['config'];
    $fileData = json_decode(file_get_contents('php://input'), true);
    CocoWS::setToken(@$fileData["token"]);
    if (!CocoWS::validateUploadToken(@$fileData["tokenHash"])) {
        die(json_encode(['error' => ['message' => 'Token no válido', 'code' => 403]]));
    }

    $result = null;
    switch($_REQUEST["subaction"]){
        case "insert":
            try {
                if (empty($fileData["tableName"]) || empty($fileData["records"])) {
                    return ['error' => ['message' => 'Faltan los parámetros tableName o records', 'code' => 400]];
                }
                $tableName = $fileData["tableName"];
                $records = $fileData["records"];
                $functions = !empty($fileData["functions"]) ? $fileData["functions"] : [];
                $options = !empty($fileData["options"]) ? $fileData["options"] : [];
                $insertedIds = CocoDB::insertRecords($tableName, $records, $functions, $options);
                $result = ["success" => true, "data" => $insertedIds];
            } catch (Exception $e) {
                return ['error' => ['message' => $e->getMessage(), 'code' => 403]];
            }
            break;
        case "update":
            try {
                if (empty($fileData["tableName"]) || empty($fileData["records"]) || !isset($fileData["where"])) {
                    return ['error' => ['message' => 'Faltan los parámetros tableName, records o where', 'code' => 400]];
                }
                $tableName = $fileData["tableName"];
                $records = $fileData["records"];
                $where = $fileData["where"];
                $functions = !empty($fileData["functions"]) ? $fileData["functions"] : [];
                $options = !empty($fileData["options"]) ? $fileData["options"] : [];
                $updatedCount = CocoDB::updateRecords($tableName, $records, $where, $functions, $options);
                $result = ["success" => true, "data" => $updatedCount];
            } catch (Exception $e) {
                return ['error' => ['message' => $e->getMessage(), 'code' => 403]];
            }
            break;
        case "delete":
            try {
                if (empty($fileData["tableName"]) || !isset($fileData["where"])) {
                    return ['error' => ['message' => 'Faltan los parámetros tableName o where', 'code' => 400]];
                }
                $tableName = $fileData["tableName"];
                $where = $fileData["where"];
                $options = !empty($fileData["options"]) ? $fileData["options"] : [];
                $deletedCount = CocoDB::deleteRecords($tableName, $where, $options);
                $result = ["success" => true, "data" => $deletedCount];
            } catch (Exception $e) {
                return ['error' => ['message' => $e->getMessage(), 'code' => 403]];
            }
            break;
        case "get":
            try {
                if (empty($fileData["tableName"])) {
                    return ['error' => ['message' => 'Falta el parámetro tableName', 'code' => 400]];
                }
                $tableName = $fileData["tableName"];
                $where = !empty($fileData["where"]) ? $fileData["where"] : "";
                $order = !empty($fileData["order"]) ? $fileData["order"] : "";
                $limit = !empty($fileData["limit"]) ? $fileData["limit"] : "";
                $options = !empty($fileData["options"]) ? $fileData["options"] : [];
                $resultDB = CocoDB::get($tableName, $where, $order, $limit,$options);
                $result = ["success" => true, "data" => $resultDB];
            } catch (Exception $e) {
                return ['error' => ['message' => $e->getMessage(), 'code' => 403]];
            }
            
            break;
        default:

    }
    return $result;
}
?>