<?
if (isset($_REQUEST["openAIModuleFill"])){
    header("Content-type:application/json"); 
    $fileData = json_decode(file_get_contents('php://input'), true);
    $result = API::request("gpt",["action" => "textCompletion","prompt" => @$fileData["prompt"],"tokenValue" => @$fileData["tokenValue"],"model" => @$fileData["model"]],"POST");
    die(json_encode($result));
}
if (isset($_REQUEST["payModule"])){ 
    header("Content-type:application/json"); 
    $module = $_REQUEST["payModule"];
    if (!@$module) die(json_encode(["error" => "Modulo no definido"]));
    ModulesAPI::clearCacheModules();
    $result = API::request(@$CURRENT_USER["isSuperAdmin"] ? "pay_test" : "pay",["action" => "PayModule","module" => $_REQUEST["payModule"],"backTo" => "https://".$_SERVER["HTTP_HOST"]."/admin.php?menu=module_marketplace"],"POST");
    die(json_encode($result));
}

if (isset($_REQUEST["getWebsiteLinks"])){
    header("Content-type:application/json");
    $enlace = $_REQUEST["getWebsiteLinks"];
    
    if (@$enlace && strpos($enlace,",") > -1){
        $sep = explode(",",$enlace);
        $data = [["tableName" => $sep[0],"num" => $sep[1],"enlace" => "Mi elección"]];
        $data[0]["enlace"] = @mysql_query_fetch_all_assoc("SELECT num,enlace FROM ".$TABLE_PREFIX.$sep[0]." WHERE num = '".$sep[1]."' LIMIT 1")[0]["enlace"];
        
        die(json_encode($data));
    }
    
    $sql = "
    SELECT DISTINCT TABLE_NAME,COLUMN_NAME 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE COLUMN_NAME IN ('enlace')
        AND TABLE_SCHEMA='".$SETTINGS["mysql"]["database"]."'
    ";
    $result = mysql_query($sql) or die(mysql_error());
    $data = [];
    $count = 0;
    while($record = mysql_fetch_assoc($result)){
        $encontrados = mysql_query_fetch_all_assoc("SELECT num,enlace FROM ".$record["TABLE_NAME"]." WHERE (".$record["COLUMN_NAME"]." LIKE '%".$enlace."%' or concat('".str_replace($TABLE_PREFIX,"",$record["TABLE_NAME"]).",',num) = '".$enlace."') ORDER BY ".$record["COLUMN_NAME"]." ASC LIMIT 5");
        if (@$encontrados){
            foreach($encontrados as $encontrado){
                $data[] = ["tableName" => str_replace($TABLE_PREFIX,"",$record["TABLE_NAME"]),"num" => $encontrado["num"],"enlace" => $encontrado["enlace"]];
                $count+=1;
                if ($count>5) break 2;
            }
        }
    }
    die(json_encode($data));
}

if (isset($_REQUEST["getWebsiteLinksv2"])){
    header("Content-type:application/json");
    $enlace = $_REQUEST["getWebsiteLinksv2"];
    
    if (@$enlace && strpos($enlace,",") > -1){
        $sep = explode(",",$enlace);
        $data = [["tableName" => $sep[0],"num" => $sep[1],"enlace" => "Mi elección"]];
        $data[0]["enlace"] = @mysql_query_fetch_all_assoc("SELECT num,enlace FROM ".$TABLE_PREFIX.$sep[0]." WHERE num = '".$sep[1]."' LIMIT 1")[0]["enlace"];
        
        die(json_encode($data));
    }
    
    $sql = "
    SELECT DISTINCT TABLE_NAME,COLUMN_NAME 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE COLUMN_NAME IN ('enlace')
        AND TABLE_SCHEMA='".$SETTINGS["mysql"]["database"]."'
    ";
    $result = mysql_query($sql) or die(mysql_error());
    $data = [];
    $sql_search_everywhere = '';

    $order_limit = ' ORDER BY enlace ASC LIMIT 5';
    while($record = mysql_fetch_assoc($result)){
        if($sql_search_everywhere != '') $sql_search_everywhere .= ' UNION ';
        $sql_search_everywhere .= "(SELECT '" . str_replace($TABLE_PREFIX,"",$record["TABLE_NAME"]) . "' as tableName, num, enlace FROM ".$record["TABLE_NAME"]." WHERE (".$record["COLUMN_NAME"]." != '' AND (".$record["COLUMN_NAME"]." LIKE '%".$enlace."%' OR concat('".str_replace($TABLE_PREFIX,"",$record["TABLE_NAME"]).",',num) = '".$enlace."'))" . $order_limit.")";
    }
    $sql_search_everywhere .= $order_limit;
    $data = mysql_query_fetch_all_assoc($sql_search_everywhere);
    //$data = ['sql' => $sql_search_everywhere];
    die(json_encode($data));
}

if (@$_REQUEST["saveTableData"]){
    $fileData = json_decode(file_get_contents('php://input'), true);
    
    $result = saveTableData(@$fileData);
    die(json_encode($result));
}
if (isset($_REQUEST["getTableData"])){
    header("Content-type:application/json");
    $result = getTableData();
    die(json_encode($result));
}

if (@$_REQUEST["saveLibrarie"]){
    $fileData = json_decode(file_get_contents('php://input'), true);
    $result = saveLibrarie(null,@$fileData);
    die(json_encode($result));
}

if (@$_REQUEST["saveLayoutData"]){
    $fileData = json_decode(file_get_contents('php://input'), true);
    if (!@$fileData["layout"]) die(json_encode(["error" => true,"Hace falta actualizar"]));
    $result = saveLayoutData(null,@$fileData["layout"],@$fileData["headerParsed"],@$fileData["footerParsed"]);
    die(json_encode($result));
}
if (isset($_REQUEST["getLayoutData"])){
    header("Content-type:application/json");
    $result = getLayoutData();
    die(json_encode($result));
}

if (isset($_REQUEST["getRequiredModules"])){
    header("Content-type:application/json");
    $requestModules = array_filter(explode(",",@$_REQUEST["getRequiredModules"]));
    if (!@$requestModules) die(json_encode([]));
    $modules = getModules(null,$requestModules);
    
    $localModules = (@$configPlugin["acceso_a_plugins_especiales"]) ? getLocalModules(null,$configPlugin["acceso_a_plugins_especiales"],$requestModules) : [];
    $localModules2 = intval(@$configPlugin["acceso_a_plugins_generales"]) ? getLocalModules(null,null,$requestModules) : [];
    $localModules = array_merge($localModules,$localModules2);

    if (!@$modules) $modules = [];
    if (!@$localModules) $localModules = [];
    die(json_encode(["web" => $modules,"local" => $localModules]));
    return;
}

if (isset($_REQUEST["getPricingModules"])){
    header("Content-type:application/json");
    $pricingModules = ModulesAPI::getInstance()->getAllModules() ?: [];
    die(json_encode($pricingModules));
    return;
}

if (isset($_REQUEST["getWebModules"])){
    $modules = getModules(null,[],null,@$_REQUEST["domain"] ?: null);
    header("Content-type:application/json");
    if (!@$modules) $modules = [];
    $modules = array_map(function($rec){ $rec["label"] = @explode("/",$rec["label"])[1] ? "____".$rec["label"] : $rec["label"]; return $rec; },$modules);
    $modules = _ordenarArray($modules,"label");
    $modules = array_map(function($rec){ $rec["label"] = @explode("____",$rec["label"])[1] ? substr($rec["label"],4) : $rec["label"]; return $rec; },$modules);
    
    $pricingModules = @$_REQUEST["domain"] == CDN_MODULES_WEBSITE ? ModulesAPI::getInstance()->getPricingModules() : [];
    //$pricingModules = ModulesAPI::getInstance()->getPricingModules() ?: [];
    
    
    $modulesWithKey = [];
    foreach($modules as $module){
        if (@$module["onlyAdminModule"] && !@$CURRENT_USER["isAdmin"]) continue;
        $pricingModuleData = @array_values(array_filter($pricingModules,function($rec) use($module) { return $rec["id_module"] == $module["id"]; }))[0];
        if (@$pricingModuleData){
            $module["price"] = $pricingModuleData["price"];
            $module["remote"] = $pricingModuleData["remote"];
        }
        
        $modulesWithKey[$module["id"]] = $module;
    }
    $modules = $modulesWithKey;
    
    //die(var_dump($modules));
    die(json_encode($modules));
    return;
}
if (isset($_REQUEST["getLocalModules"])){
    $localModules = (@$configPlugin["acceso_a_plugins_especiales"]) ? getLocalModules("modulos",$configPlugin["acceso_a_plugins_especiales"]) : [];
    $localModules2 = intval(@$configPlugin["acceso_a_plugins_generales"]) ? getLocalModules() : [];
    $localModules = array_merge($localModules,$localModules2);

    header("Content-type:application/json");
    if (!@$localModules) $localModules = [];
    die(json_encode($localModules));
    return;
}
if (isset($_REQUEST["getAllModules"])){
    $modules = getModules();
    $localModules = (@$configPlugin["acceso_a_plugins_especiales"]) ? getLocalModules("modulos",$configPlugin["acceso_a_plugins_especiales"]) : [];
    $localModules2 = intval(@$configPlugin["acceso_a_plugins_generales"]) ? getLocalModules() : [];
    $localModules = array_merge($localModules,$localModules2);

    header("Content-type:application/json");
    if (!@$localModules) $localModules = [];
    if (!@$modules) $modules = [];
    die(json_encode(["web" => $modules,"local" => $localModules]));
    return;
}

if (@$_REQUEST["standardEdit"]) {
    return;
}

if (@$_REQUEST["getEdit"]) {
    showInterface('../plugins/builder_saas/edit.php');
    die();
    return;
}
if (@$_REQUEST["getCSS"]){
    if (!@$CURRENT_USER) die("Error");
    $file = "../template/estandar/modulos/".mysql_real_escape_string(@$_REQUEST["getCSS"])."/style.css";
    if (!file_exists($file)){
        $result = file_put_contents($file,"/* PUT YOUR CSS HERE */");
    }
    $result = @file_get_contents($file);
    die($result);
}
if (@$_REQUEST["setCSS"] && @$_REQUEST["data"]){
    if (!@$CURRENT_USER) die("Error");
    $file = "../template/estandar/modulos/".mysql_real_escape_string(@$_REQUEST["setCSS"])."/style.css";
    $result = file_put_contents($file,base64_decode(@$_REQUEST["data"]));
    die("ok");
}

if (@$_REQUEST["saveModule"]){
    saveModule(@$_REQUEST["saveModule"]);
    die("ok");
}
if (@$_REQUEST["deleteModule"]){
    deleteModule(@$_REQUEST["deleteModule"]);
    die(@$_REQUEST["json"] ? json_encode(["success" => true]) : "ok");
}
if (@$_REQUEST["generateModuleFromString"]){
    $data = file_get_contents('php://input');
    header("Content-type:application/json");
    if (!@$data) die(json_encode(["error" => "Missing Data"]));
    $data = json_decode($data,true);
    
    $respond = generateModuleFromString(@$data);
    
    die(json_encode($respond));
}
if (@$_REQUEST["generateModuleFromWebsite"]){

    $data = file_get_contents('php://input');
    header("Content-type:application/json");
    if (!@$data) die(json_encode(["error" => "Missing Data"]));
    $data = json_decode($data,true);
    $domain = $_REQUEST["generateModuleFromWebsite"];

    if (@$data["requiredPlugins"]){
        $sep = array_filter(explode(",",$data["requiredPlugins"]));
        if (@$sep){
            foreach($sep as $se){
                PluginsAPI::syncPlugin(@$se);
            }
        }
    }
    
    /*$send = [
        'token'=>getPrefixedCookie("COCOOKIE"),
        'tokenHash' => Uploads::generate_token(),
        'fileName'      => $data["id"],
        'action_ws' => 'getFullModule'
    ];
    $result = API::sendToWeb($send,"POST",false,CDN_MODULES_WEBSITE);*/

    // LO ENVIAMOS ASI PORQUE SI EL ARCHIVO DE RESPUESTA ES MUY GRANDE EL CURL DA ERROR DE JSON_DECODE
    
    if ($domain != CDN_MODULES_WEBSITE_DOMAIN){
        $domain = array_filter($CURRENT_USER["domains"],function($rec) use($domain) { return $rec["domain"] == $domain; });
        if (@$domain) $domain = array_values($domain)[0];
    }else{
        $domain = ["num" => CDN_MODULES_WEBSITE,"domain" => CDN_MODULES_WEBSITE_DOMAIN];
    }

    $send = [
        'token'=>getPrefixedCookie("COCOOKIE"),
        'tokenHash' => Uploads::generate_token(),
        'fileName'      => $data["id"],
        'action_ws' => 'getFullModule'
    ];
    $header = array();
    $header[] = 'Content-length: '.strlen(json_encode($send));
    try{
        $postdata = json_encode($send);

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-Type: application/json',
                'content' => $postdata
            )
        );

        $context  = stream_context_create($opts);

        $result = json_decode(@file_get_contents("https://".$domain["domain"]."/cms/lib/viewer_functions.php?action_ws=getFullModule",false,$context),true);
        
    }catch(Exception $e){
        $result = ["result" => 0];
    }

    if (!@$result["result"] || !@$result["data"]) die(json_encode(["error" => "No puedo obtener el módulo","result" => $result,"domain" => $domain]));

    

    $send = [
        'token'         => getPrefixedCookie("COCOOKIE"),
        'tokenHash'     => Uploads::generate_token(),
        'fileName'      => $data["id"],
        'content'       => $result["data"],
        'replace'       => true,
        'zip'           => true,
        'action_ws'     => 'saveModule'
    ];
    $result = API::sendToWeb($send,"POST");

    die(json_encode(["success" => true]));
    // ----------
    /* METODO ANTIGUO... LO DEJAMOS POR SI ACASO 
    
    $_REQUEST["full"] = 1;
    $modules = getModules(null,[$data["id"]],null,$domain["num"]);
    if (!@$modules[$data["id"]]) die(json_encode(["error" => 1,"message" => "No encuentro la web con el id ".$data["id"],"modules" => $modules,"domain" => $domain]));
    if (!@$modules[$data["id"]]["htmlData"]) die(json_encode(["error" => 1]));
    
    $data["image"] = 'data:image/jpg;base64,'.base64_encode(file_get_contents($modules[$data["id"]]["path"]."/".$modules[$data["id"]]["thumbnail"]));
    
    $data["html"] = @$modules[$data["id"]]["htmlData"];
    $data["htmlParsed"] = @$modules[$data["id"]]["htmlDataParsed"];
    $data["MJMLModule"] = @$modules[$data["id"]]["MJMLModule"];
    $data["editMode"] = @$modules[$data["id"]]["editable"];
    $data["notParseComponents"] = @$modules[$data["id"]]["notParseComponents"];
    $data["onlyAdminModule"] = @$modules[$data["id"]]["onlyAdminModule"];
    $data["style"] = @$modules[$data["id"]]["styleData"];
    $data["javascript"] = @$modules[$data["id"]]["javascriptData"];
    $data["image"] = 'data:image/jpg;base64,'.base64_encode(file_get_contents($modules[$data["id"]]["path"]."/".$modules[$data["id"]]["thumbnail"]));
    //$data["vars"] = $modules[$data["id"]]["vars"];
    
    foreach ($data["vars"] as $key => $value){
        $data["vars"][$key]["field"] = $key;    
        if (@$data["vars"][$key]["type"] == "multi"){
            foreach($data["vars"][$key]["vars"] as $k => $v){
                $data["vars"][$key]["vars"][$k]["field"] = $k;
            }
        }
    }
    
    $respond = generateModuleFromString($data,true,true);
    
    die(json_encode($respond)); */
}