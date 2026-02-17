<?
//************************************************
// CONFIGURACION
//************************************************

$configPlugin = [];
if (@$CURRENT_USER["domain"]["plugins"]["builder_saas"]){
    foreach($CURRENT_USER["domain"]["plugins"]["builder_saas"] as $cont => $arrayValue){
        $configPlugin[$arrayValue["campo"]] = $arrayValue["valor"];
    }
}
if (file_exists(__DIR__."/schema.ini.php")){
    
    $schemaPlugin = loadINI(__DIR__."/schema.ini.php");
    foreach($schemaPlugin["config"] as $key => $value){
        if (!isset($configPlugin[$key])) $configPlugin[$key] = $value;
    }

}
$CURRENT_USER_filtrado = [
    "num"           => $CURRENT_USER["num"],
    "isAdmin"       => $CURRENT_USER["isAdmin"],
    "isSuperAdmin"  => $CURRENT_USER["isSuperAdmin"],
    "licencia"      => $CURRENT_USER["licencia"],
    "domain"        => [
        "num"           =>  $CURRENT_USER["domain"]["num"],
        "domain"        =>  $CURRENT_USER["domain"]["domain"]
    ],
];

define("CDN_MODULES_WEBSITE",384);
define("CDN_MODULES_WEBSITE_DOMAIN","cdn-modules.plandeweb.com");

//************************************************
// FUNCIONES
//************************************************

// Custom Colors
function getExtraDataFromModule($module = null,$num = null,$section_id = null,$keyPl = 'custom-colors'){
    global $TABLE_PREFIX;
    if (!$module || !$num || !$section_id) return [];

    $section_id = str_replace("/","",@$section_id); // Por alguna razón la sección id esta llegando con / pero no debe llevarla por lo que lo forzamos
    
    require_once __DIR__."/../../classes/CocoDB.php";
    

    $colors = @CocoDB::get("aux_plg_config","plugin = '".md5($keyPl."|".strtolower($module)."|".$num."|".$section_id)."'","num desc",1,["ignoreSchema" => true,"prefix" => ""])[0];
    
    return $colors ? json_decode($colors["config"],true) : [];
}
function setExtraDataFromModule($module = null,$num = null,$section_id = null,$value = [],$keyPl = 'custom-colors'){
    global $TABLE_PREFIX;
    if (!$module || !$num || !$section_id) return [];
    
    $section_id = str_replace("/","",@$section_id); // Por alguna razón la sección id esta llegando con / pero no debe llevarla por lo que lo forzamos

    $colors = [];
    foreach($value as $key => $val){
        if ($val) $colors[$key] = $val;
    }
    require_once __DIR__."/../../classes/CocoDB.php";
    $result = CocoDB::deleteRecords("aux_plg_config","plugin='".md5($keyPl."|".strtolower($module)."|".$num."|".$section_id)."'",["ignoreSchema" => true,"prefix" => ""]);
    
    $colors = CocoDB::insertRecords("aux_plg_config",[
        "createdDate" => date("Y-m-d H:i:s"),
        "updatedDate" => date("Y-m-d H:i:s"),
        "plugin" => md5($keyPl."|".strtolower($module)."|".$num."|".$section_id),
        "config" => json_encode($colors)
    ],null,["ignoreSchema" => true,"prefix" => ""]);

    CocoDB::updateRecords("apartados",["updatedDate" => date("Y-m-d H:i:s",time())],"num=".$num);

    return ["success" => true,"value" => $value,"localKey" => $keyPl."|".strtolower($module)."|".$num."|".$section_id,"hasKey" => md5($keyPl."|".strtolower($module)."|".$num."|".$section_id)];
}
// FIN Custom Colors

function erasePageCache($num = null,$section = null){
    global $TABLE_PREFIX;
    if (!$num || !$section) return mysql_query("DELETE FROM ".$TABLE_PREFIX."builder_custom WHERE title1 LIKE 'vars-%'");    
    return mysql_query("DELETE FROM ".$TABLE_PREFIX."builder_custom WHERE title1 LIKE 'vars-".$section."%'");
}

function getProyectLinks(){
    global $SETTINGS,$TABLE_PREFIX;
    
    $tablasLinks = mysql_query_fetch_all_assoc("SELECT DISTINCT TABLE_NAME,COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE COLUMN_NAME IN ('enlace') AND TABLE_SCHEMA='".$SETTINGS["mysql"]["database"]."'");
    $tablasLinks = array_map(function($rec){ return $rec["TABLE_NAME"]; },$tablasLinks);

    $resultLinks = [];
    
    $tablasLinks = [$TABLE_PREFIX."apartados",$TABLE_PREFIX."otros_contenidos"];
    
    foreach($tablasLinks as $tablaLink){
        if (strpos($tablaLink,$TABLE_PREFIX)!==false){
            $resultLinks = array_merge($resultLinks,array_map(function($rec) use ($tablaLink,$TABLE_PREFIX){ return ["value" => str_replace($TABLE_PREFIX,"",$tablaLink).",".$rec["num"],"label" => $rec["enlace"]];},mysql_query_fetch_all_assoc("SELECT num,enlace FROM ".$tablaLink)));
        }
    }
    
    $resultLinks = array_filter($resultLinks,function($rec){ return $rec; });
    $resultLinks = _ordenarArray($resultLinks,"label");
    //sort($resultLinks);    
    return $resultLinks;
}

function loadMyDataConfig(){
    global $TABLE_PREFIX,$menu;
    
    $data = @$_REQUEST["num"] ? mysql_query_fetch_all_assoc("SELECT builder FROM ".$TABLE_PREFIX.$menu." WHERE num=".intval(@$_REQUEST["num"]))[0]["builder"] : '[]';

    if ($data){
        $dataArray = json_decode($data,true);
        foreach($dataArray as $cont => $dat){
            if (!@$dat["modulo"]) continue;
            $moduloId = $dat["modulo"];
            $existen = mysql_query_fetch_all_assoc("SELECT * FROM ".$TABLE_PREFIX.$menu." where num!=".intval(@$_REQUEST["num"])." and builder!='' AND builder like '%".$moduloId."%'");

            if ($existen){
                $dataArray[$cont]["referencias"] = [];
                $campoName = dameCampoTitle();
                foreach($existen as $apart){
                    $jsonConfigAux = json_decode($apart["builder"],true);
                    $contadorEncontrados = 0;
                    foreach($jsonConfigAux as $contAux => $sectionAux){
                        if (!@$sectionAux["modulo"]) continue;
                        if ($sectionAux["modulo"] == $moduloId){
                            $contadorEncontrados+=1;
                            $dataArray[$cont]["referencias"][] = [
                                "menu" => $menu,
                                "num" => $menu."|".$apart["num"]."|".$contadorEncontrados,
                                "index" => $contadorEncontrados,
                                "disabled" => @$sectionAux["referenciada"] ? true : false,
                                "title" => "ESTA SECCIÓN : ".(@$apart[$campoName] ?: @$apart["title"] ?: $apart["name"])." (".$contadorEncontrados.")"
                            ];
                            // break;
                        }
                    }

                }
            }

            // ESTO SE PUEDE OPTIMIZAR HACIENDO UNA RECURSIVA QUE RECORRA TODAS LAS TABLAS
            if ($menu !== "apartados"){
                $existen = mysql_query_fetch_all_assoc("SELECT * FROM ".$TABLE_PREFIX."apartados where builder!='' AND builder like '%".$moduloId."%'");

                if ($existen){
                    $dataArray[$cont]["referencias"] = @$dataArray[$cont]["referencias"] ?: [];
                    $campoName = dameCampoTitle();
                    
                    foreach($existen as $apart){
                        $jsonConfigAux = json_decode($apart["builder"],true);
                        $contadorEncontrados = 0;
                        foreach($jsonConfigAux as $contAux => $sectionAux){
                            if ($sectionAux["modulo"] == $moduloId && !@$sectionAux["referenciada"]){
                                $contadorEncontrados+=1;
                                $dataArray[$cont]["referencias"][] = [
                                    "menu" => "apartados",
                                    "num" => "apartados|".$apart["num"]."|".$contadorEncontrados,
                                    "index" => $contadorEncontrados,
                                    "title" => "APARTADOS : ".(@$apart[$campoName] ?: @$apart["title"] ?: $apart["name"])." (".$contadorEncontrados.")"
                                ];
                                // break;
                            }
                        }

                    }
                }
            }
            if (!@$dataArray[$cont]["section_id"]) $dataArray[$cont]["section_id"] = substr(md5($dat["modulo"].json_encode($dataArray[$cont])),0,5);
        }
        $data = json_encode($dataArray);
    }


    if (!$data) $data = '[]';
    
    return $data;
}
function saveLibrarie($path = null,$data){
    if (!$path) $path = "template/estandar/modulos";
    global $TABLE_PREFIX;

    $result = array("result" => 0);
    $ds = DIRECTORY_SEPARATOR;
    $pathAux = $path;
    $path = dirname(__FILE__)."{$ds}..{$ds}..{$ds}..{$ds}..{$ds}".$path.$ds;
    $arrayModules = [];
    $send = [
        'token'=>getPrefixedCookie("COCOOKIE"),
        'tokenHash' => Uploads::generate_token(),
        'data'      => $data,
        'action_ws' => 'saveLibrarie'
    ];
    $result = API::sendToWeb($send,"POST");

    return @$result;
}
function saveTableData($data){
    global $schema,$menu;
    $result = [];
    $schemaNew = $schema;
    if (@$data["html"] || @$data["style"] || @$data["javascript"] || @$data["hook"]){
        if (@$schemaNew["controller"] && strpos($schemaNew["controller"],"builder_saas") === false){
            $schemaNew["controller_alt"] = $schemaNew["controller"];
            $schemaNew["controller"] = "cms/lib/plugins/builder_saas/controlador_tabla.php";
        }
    }else if (@$schemaNew["controller_alt"]){
        $schemaNew["controller"] = $schemaNew["controller_alt"];
    }else if (@$schemaNew["controller"]){
        $schemaNew["controller"] = "";
    }
    if ($schemaNew != $schema){
        saveSchema($menu, $schemaNew);
        createMissingSchemaTablesAndFields();
        $schema = $schemaNew;
        $result["newController"] = $schemaNew["controller"];
    }
    $result["requestData"] = $data;
    
    $data["id"] = "custom-".$menu;
    generateModuleFromString($data,false);
    return $result;
}
function saveLayoutData($path = null,$data,$headerParsedCode = '',$footerParsedCode = ''){
    global $CURRENT_USER;

    $ruta_plantilla = "template/estandar";
    if (!$path) $path = $ruta_plantilla."/modulos";
    global $TABLE_PREFIX;
    
    $result = array("result" => 0);
    $ds = DIRECTORY_SEPARATOR;
    $pathAux = $path;
    $path = dirname(__FILE__)."{$ds}..{$ds}..{$ds}..{$ds}..{$ds}".$path.$ds;
    $arrayModules = [];
    foreach(scandir(__DIR__."/layout_templates/") as $file){
        if ($file=="." || $file=="..") continue;
        if (isset($data[pathinfo($file)["filename"]])) {
            if (@$data["real_".pathinfo($file)["filename"]]){
                $data[pathinfo($file)["filename"]."_php"] = str_replace("{CODE}",'<?php echo builderModule("custom-'.pathinfo($file)["filename"].'-twig"); ?>',file_get_contents(__DIR__."/layout_templates/".$file));   
            }else{
                $data[pathinfo($file)["filename"]."_php"] = str_replace("{CODE}",'<?php echo builderModule("custom-'.pathinfo($file)["filename"].'-twig"); ?>',file_get_contents(__DIR__."/layout_templates/".$file));    
            }
            
            $data[pathinfo($file)["filename"]."_php"] = str_replace("{CMSAPI_JS}",file_get_contents(__DIR__."/js/cmsApiInit.js"),$data[pathinfo($file)["filename"]."_php"]);
            
            if (@$data["librariesJSONt"]){
                $resultLibrary = "";
                foreach($data["librariesJSONt"] as $library){
                    $library["url"] = str_replace("/".$ruta_plantilla,"",$library["url"]);
                    $async = strpos(@$library["attr"],"async") !== false ? "true" : "false";
                    if (strpos(strtolower($library["url"]),".css")!==false) {
                        $resultLibrary.="\t<? Resource::link('".$library["url"]."', ".$async."); ?>\n";
                    }else if (strpos(strtolower($library["url"]),"/css")!==false) {
                        $resultLibrary.="\t<? Resource::link('".$library["url"]."', ".$async."); ?>\n";
                    }else{
                        $resultLibrary.="<? if (!@\$_REQUEST['viewAMP']) { \$strJavascript = '<script src=\''.h('".$library["url"]."').'\' ".@$library["attr"]."></script>';  if (!defined('USE_MIN_JS_TAILWIND') || (defined('USE_MIN_JS_TAILWIND') && !USE_MIN_JS_TAILWIND)) {    if (defined('USE_MIN_TAILWIND') && USE_MIN_TAILWIND) { if (!strpos('".$library["url"]."','tailwind')) { echo \$strJavascript; } }else{ echo \$strJavascript; } } } ?>"; 
                    }
                }
                $data[pathinfo($file)["filename"]."_php"] = str_replace("{TOP_LIBRARIES}",$resultLibrary,$data[pathinfo($file)["filename"]."_php"]);
            }else{
                $data[pathinfo($file)["filename"]."_php"] = str_replace("{TOP_LIBRARIES}","",$data[pathinfo($file)["filename"]."_php"]);
                
            }
            if (@$data["librariesJSONAMP"]){
                $resultLibrary = "";
                foreach($data["librariesJSONAMP"] as $library){
                    $library["url"] = str_replace("/".$ruta_plantilla,"",$library["url"]);
                    $libraryParsed = substr($library["url"],0,4) == "http" ? "'".$library["url"]."'" : "'https://'.\$_SERVER['HTTP_HOST'].'/template/estandar".$library["url"]."'";
                    if (strpos(strtolower($library["url"]),"fonts.")!==false) {
                        $resultLibrary.="\t <? Resource::link('".$library["url"]."', true); ?>\n";
                    }else if (strpos(strtolower($library["url"]),".css")!==false) {
                        $resultLibrary.="\t <style amp-custom><? echo minify_css(file_get_contents(".$libraryParsed.")); ?></style>\n";
                    }else if (strpos(strtolower($library["url"]),"/css")!==false) {
                        $resultLibrary.="\t <style amp-custom><? echo minify_css(file_get_contents(".$libraryParsed.")); ?></style>\n";
                    }else{
                        $resultLibrary.='<? if (@$_REQUEST["viewAMP"]) { ?> <script src="<?=h("'.$library["url"].'");?>" '.@$library['attr'].'></script> <? } ?>';
                    }
                }
                $data[pathinfo($file)["filename"]."_php"] = str_replace("{AMP_LIBRARIES}",$resultLibrary,$data[pathinfo($file)["filename"]."_php"]);
            }else{
                $data[pathinfo($file)["filename"]."_php"] = str_replace("{AMP_LIBRARIES}","",$data[pathinfo($file)["filename"]."_php"]);
                
            }
            if (@$data["librariesJSONb"]){
                $resultLibrary = "";
                foreach($data["librariesJSONb"] as $library){
                    $library["url"] = str_replace("/".$ruta_plantilla,"",$library["url"]);
                    $async = strpos(@$library["attr"],"async") !== false ? "true" : "false";
                    if (strpos(strtolower($library["url"]),".css")!==false) {
                        $resultLibrary.="\t<? Resource::link('".$library["url"]."', ".$async."); ?>\n";
                    }else if (strpos(strtolower($library["url"]),"/css")!==false) {
                        $resultLibrary.="\t<? Resource::link('".$library["url"]."', ".$async."); ?>\n";
                    }else{
                        $resultLibrary.="<? if (!@\$_REQUEST['viewAMP']) { \$strJavascript = '<script src=\''.h('".$library["url"]."').'\' ".@$library["attr"]."></script>';  if (!defined('USE_MIN_JS_TAILWIND') || (defined('USE_MIN_JS_TAILWIND') && !USE_MIN_JS_TAILWIND)) {    if (defined('USE_MIN_TAILWIND') && USE_MIN_TAILWIND) { if (!strpos('".$library["url"]."','tailwind')) { echo \$strJavascript; } }else{ echo \$strJavascript; } } } ?>"; 
                    }
                    
                }
                $data[pathinfo($file)["filename"]."_php"] = str_replace("{BOTTOM_LIBRARIES}",$resultLibrary,$data[pathinfo($file)["filename"]."_php"]);
                
            }else{
                $data[pathinfo($file)["filename"]."_php"] = str_replace("{BOTTOM_LIBRARIES}","",$data[pathinfo($file)["filename"]."_php"]);
                
            }
            $data[pathinfo($file)["filename"]."_php"] = str_replace("{STYLE}","\t<link rel='stylesheet' href='/custom-builder-style.css?timestamp=<?=filemtime(__DIR__.\"/../../../../cms/lib/plugins/builder_saas/layout.json\");?>'>\n",$data[pathinfo($file)["filename"]."_php"]);
            $data[pathinfo($file)["filename"]."_php"] = str_replace("{JAVASCRIPT}","\t<script src='/custom-builder-javascript.js?timestamp=<?=filemtime(__DIR__.\"/../../../../cms/lib/plugins/builder_saas/layout.json\");?>'></script>\n",$data[pathinfo($file)["filename"]."_php"]);
            
        }
    }
    
    $send = [
        'token' => getPrefixedCookie("COCOOKIE"),
        'tokenHash' => Uploads::generate_token(),
        'data'      => $data,
        'action_ws' => 'saveLayoutData'
    ];
    
        
    foreach(["header","footer"] as $file){
        $menu =["content" => $data[$file]];

        $dataNewModule = [
            "description"  => $file . ' en TWIG Auto',
            "editMode"  => false,
            "html"  => $menu["content"],
            "htmlParsed"  => $file == "header" ? $headerParsedCode : $footerParsedCode,
            "id"  => 'custom-' . $file . '-twig',
            "image"  => 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAoHBwkHBgoJCAkLCwoMDxkQDw4ODx4WFxIZJCAmJSMgIyIoLTkwKCo2KyIjMkQyNjs9QEBAJjBGS0U+Sjk/QD3/2wBDAQsLCw8NDx0QEB09KSMpPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT3/wgARCAK2A3QDAREAAhEBAxEB/8QAFwABAQEBAAAAAAAAAAAAAAAAAAECB//EABYBAQEBAAAAAAAAAAAAAAAAAAABA//aAAwDAQACEAMQAAAA53pAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAIQhQCFKUAAAAAAGSFAIaKAAAAAAACEIUAApQAAADIANAAAAAAAAAAAAAAAAAAAAAwUAAAAhsAAAAAyAAAAaAAAAAAAMgAAAAhooAABkAFKAAAAAAAAAAAAAAAAAAAQgAAAAABSgAAAwUAAAA0AAAAAAQgAAAAAANAAEIAClAAAAAAAAAAAAAAAAAABCAAAAAAAFKAADIAAAABoAAAAAAyAAAAAAAUoAIQAA0AAAAAAAAAAAAAAAAAADBQAAAAAACGwACEAAAAIaKAAAAADBQAAAAAAAaABCAAGgAAAAAAAAAAAAAAAAADIAABCgAAAApQAYKAAAUoAAAAAAMgAAEKCmSgAAGgCEAANAAAAAAAAAAAAAAAAAAGQAADQABkAAA0ADIAAKUAAAAAAAGCgAA0AAZAABDYBCAAGgAAAAAAAAAAAAAAAAAQgAANAAAGCgAGgCEAABoAAAAyQGigAyAADQAABkAAGgCEAANAAAAAAAAAAAAAAAAAAhAAClAAAMgAA0AZAABoAAAAyAAUoIQAApQAACEAAKUEIAAaAAAAAAAAAAAAAAAAABkAAGgAAAQgABSghAAQ2AAAAYKAClBkAAFKAAAQgABSghAADQAAAAAAAAAAAAAAAAAIQAA0AAACEAAKUGQAAaAAAAMgAA0DIAANAAAAGQAClBCAAGgAAAAAAAAAAAAAAAAAQgABSgAAAyAAUoIQAA0AAACEAABoEIAAUoAAAMgAFKCEABSgAAAAAAAAAAAAAAAAAGQACGwAACEAAKUAyAADQAABkAAFKAZAAIbAAAIQAApQQgABoAAAAAAAAAAAAAAAAAAwUAAFKAAQgABSgGQAADQABkAAA0ADIAAIaKAAQgABSghAADQAAAAAAAAAAAAAAAAABkAAAEKUoIQAApQCEAAABSEKAAAaABCAAAEKDQBkAApQQgABoAAAAAAAAAAAAAAAAAAGQAAAADQMgAFKADBQAAAAAACGwAAZAAAAANAyAAUoIQAA0AAAAAAAAAAAAAAAAAAAYKAAAAaBkAApQADBQAAAAAAUoAABgoAAABSmQAClBCAAGgAAAAAAAAAAAAAAAAAAAYKAAADRCAAFKAADBQAAAAAUoAAABkAAAA0DIABSghAADQAAAAAAAAAAAAAAAAAAABkhQAAaBgoBDYAAAMgAAAAGgAAAAAQyUAAhsGQADQAMgAGgAAAAAAAAAAAAAAAAAAAACEICmgAZIU0AAAAAZABCgA0AAAAAACEICmgAZIUpQAQyUGgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAADIBDYAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABCAGgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAZBoAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAhDQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABT/8QAIhAAAQMEAwEBAQEAAAAAAAAAAQIRIAAQMDFAQVASYLAh/9oACAEBAAE/AP7NTjjPTjjuM70/mvX1IFqCsztTyB4L19SBoGhhJuC1DyXbGC1A4347tT4hQU8zqKfIJzA8NOUnMDI6inxyeAC4mcYyE8AROoo79cUInGB4R1FHfjHAE01fOBOQJr5GdWAJr4psCYK1FHfmAPQiQ0hAzA44D0IkYlaijvxTIcEyTie4VA/4DIZhdWoo78UyTgMhcyGExTcyTgMk3VqKO/FVuQwGSdXMRnTcyGsB1JF1aijvxTIYDIXVxE7uZDAZC6tRHimScBknVzIYDJNzJOAyTdWop8U5jJOrmSS2AyTwTqSdG6tRR35goKkZJgZAyJmIGQoKkdSTo3Ooo78ZWH6NBVzqSYGYLUFU9PgTE4XoKwp0bq1FHfopsZJ4wkcQwJ0bmKO/HOMUdSTEjgJDcITTdWoo79IUdSTIh8wGJmwjUxdWoo78r5mMADYGbEA9ANk+cRiLmKO/M+aY3CXoXamawD0A2JqamMADTWCqBzfNNcJoBoM1moBolNwKAb8SqAL/AKs3A/WG4/WkWH65qH9Fv//EABQRAQAAAAAAAAAAAAAAAAAAAND/2gAIAQIBAT8AFuf/xAAUEQEAAAAAAAAAAAAAAAAAAADQ/9oACAEDAQE/ABbn/9k=',
            "javascript"  => '',
            "label"  => $file . ' TWIG Auto',
            "notParseComponents"  => 2,
            "onlyAdminModule"  => true,
            "requiredPlugins" => "",
            "MJMLModule"  => false,
            "style"  => '',
            "tailWind"  => true,
            "vars"  => null,
            "lastUpdate"  => $data["lastUpdate"]
        ];
        
        $send[$file."ModuleCustom"] = $dataNewModule;

    }

    $result = API::sendToWeb($send,"POST");
    
    //foreach(["header","footer"] as $file){
        //generateModuleFromString($send[$file."ModuleCustom"]);
    //}
    
    return @$result;
}
function getLayoutData($path = null){
    if (!$path) $path = "template/estandar/modulos";
    global $TABLE_PREFIX;
    
    $result = array("result" => 0);
    $ds = DIRECTORY_SEPARATOR;
    $pathAux = $path;
    $path = dirname(__FILE__)."{$ds}..{$ds}..{$ds}..{$ds}..{$ds}".$path.$ds;
    $arrayModules = [];
    $send = [
        'token'=>getPrefixedCookie("COCOOKIE"),
        'tokenHash' => Uploads::generate_token(),
        'action_ws' => 'getLayoutData'
    ];
    $result = API::sendToWeb($send,"POST");
    
    return @$result;
}
function getTableData($path = null){
    global $menu;
    if (!$path) $path = "template/estandar/modulos";
    global $TABLE_PREFIX;
    
    $result = array("result" => 0);
    $ds = DIRECTORY_SEPARATOR;
    $pathAux = $path;
    $path = dirname(__FILE__)."{$ds}..{$ds}..{$ds}..{$ds}..{$ds}".$path.$ds;
    $arrayModules = [];
    $send = [
        'token'=>getPrefixedCookie("COCOOKIE"),
        'tokenHash' => Uploads::generate_token(),
        'menu'      => $menu,
        'action_ws' => 'getTableData'
    ];
    $result = API::sendToWeb($send,"POST");
    
    return @$result;
}

function getModules($path=null,$ids = [],$menuAlt = null, $domain = null){
    global $CURRENT_USER;

    if (!$path) $path = "template/estandar/modulos";
    // CARGAMOS LOS MODULOS DE LA WEB DEL CLIENTE
    
    global $TABLE_PREFIX,$menu,$schema;
    if (!$menuAlt) $menuAlt = $menu;
    
    $result = array("result" => 0);
    $ds = DIRECTORY_SEPARATOR;
    $pathAux = $path;
    $path = dirname(__FILE__)."{$ds}..{$ds}..{$ds}..{$ds}..{$ds}".$path.$ds;
    $arrayModules = [];
    $send = [
        'token'=>getPrefixedCookie("COCOOKIE"),
        'tokenHash' => Uploads::generate_token(),
        'action_ws' => 'getModuleSchemas'
    ];
    if (@$ids) $send["ids"] = $ids;
    if (@$_REQUEST["full"]) $send["full"] = 1;

    if (!$domain){
        $result = API::sendToWeb($send,"POST");
    }else{
        $data = [];
        $header = array();
        $header[] = 'Content-length: '.strlen(json_encode($data));
        try{
            $postdata = http_build_query($send);

            $opts = array('http' =>
                array(
                    'method'  => 'POST',
                    'header'  => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => $postdata
                )
            );

            $context  = stream_context_create($opts);

            if ($domain != CDN_MODULES_WEBSITE){
                $domain = array_filter($CURRENT_USER["domains"],function($rec) use($domain) { return $rec["num"] == $domain; });
                if (@$domain) $domain = array_values($domain)[0];
            }else{
                $domain = ["id" => CDN_MODULES_WEBSITE,"domain" => CDN_MODULES_WEBSITE_DOMAIN];
            }
            
            $result = json_decode(@file_get_contents("https://".$domain["domain"]."/cms/lib/viewer_functions.php?action_ws=getModuleSchemas",false,$context),true);
            
        }catch(Exception $e){
            $result = ["result" => 0];
        }
        
    }
    
    
    $localModules = getLocalModules();
    
    if (@$result["modules"]){
        foreach($result["modules"] as $key => $module){
            $arrayModules[$key] = $module;
        }
    }
    
    // COMPROBAMOS SI EXISTE EN ALGUN OTRO LADO PARA LAS REFERENCIAS
    if ($arrayModules){
        
        foreach($arrayModules as $cont => $module){
            $moduloId = $module["id"];
            $contEncontrada = 0;
            $existen = mysql_query_fetch_all_assoc("SELECT * FROM ".$TABLE_PREFIX.$menuAlt." where num!=".intval(@$_REQUEST["num"])." and builder!='' AND builder like '%".$moduloId."%'");
            if ($existen){
                $campoName = dameCampoTitle();
                
                $arrayModules[$cont]["referencias"] = [];
                foreach($existen as $apart){
                    $jsonConfigAux = json_decode($apart["builder"],true);
                    foreach($jsonConfigAux as $contAux => $sectionAux){
                        if (!@$sectionAux["modulo"]) continue;
                        if ($sectionAux["modulo"] == $moduloId && !@$sectionAux["referenciada"]){
                            $contEncontrada+=1;
                            $arrayModules[$cont]["referencias"][] = [
                                "num" => $apart["num"],
                                "menu" => $menuAlt,
                                "index" => $contEncontrada,
                                "title" => $apart[$campoName]." (".$contEncontrada.")"
                            ];
                            // break;
                        }
                    }

                }
            }
        }
    }
    
    return @$arrayModules ?: [];
}
function dameCampoTitle(){
    global $schema;
    $campoName = "name";
    if (!isset($schema[$campoName])){
        foreach($schema as $key => $value){
            if (!is_array($value)) continue;
            if ($value["type"]!="textfield") continue;
            if ($value["type"]=="textfield" && $key != "enlace"){
                $campoName = $key;
                break;
            }
        }
    }
    return $campoName;
}
function parseaConfigPlugins($plugins){
    $result = [];
    if (!$plugins) return $result;
    foreach($plugins as $key => $plugin){
        if (!is_array($plugin)) continue;
        $result[$key] = [];
        foreach($plugin as $plug){
            $result[$key][$plug["campo"]] = $plug["valor"];
        }
    }
    return $result;
}
function getLocalModules($path=null,$especiales = null,$ids = []){
    if (!$path) $path = "modulos";
    if (!$especiales) $especiales = "";
    
    $result = array("result" => 0);
    $ds = DIRECTORY_SEPARATOR;
    $pathAux = $path;
    $path = dirname(__FILE__)."{$ds}".$path.$ds;
    $pathAux2 = str_replace(realpath(__DIR__.$ds."..".$ds."..".$ds."..".$ds),"",$path);
    
    $arrayModules = [];
    $modules = scandir($path);
    
    if (@$modules){
        $result["result"] = 1;
        $result["data"] = array();
        foreach($modules as $module):
            if ($ids && !in_array(str_replace(".tpl","",$module),$ids)) continue;
            if ($module!="." && $module!=".."){
                if (file_exists($path.str_replace(".tpl","",$module)."/builder.json")){
                    $array = array(
                        "id" => $module,
                        "path" => protocol()."://".$_SERVER["HTTP_HOST"].$ds.$pathAux2.$ds.str_replace(".tpl","",$module)
                    );
                    
                    $schema = file_get_contents(dirname(__FILE__)."/modulos/".str_replace(".tpl","",$module)."/builder.json");
                    $schema = json_decode(preg_replace('/\s*(?!<\")\/\*[^\*]+\*\/(?!\")\s*/', '', $schema),true);
                    
                    if (@$schema["editable"]){
                        $htmlData = @file_get_contents($path."/".str_replace(".tpl","",$module)."/index-base.tpl");
                        if (@$htmlData) $schema["htmlData"] = $htmlData;
                        $styleData = @file_get_contents($path."/".str_replace(".tpl","",$module)."/style.css");
                        if (@$styleData) $schema["styleData"] = $styleData;
                        $javascriptData = @file_get_contents($path."/".str_replace(".tpl","",$module)."/script.js");
                        if (@$javascriptData) $schema["javascriptData"] = $javascriptData;
                        $hookData = @file_get_contents($path."/".str_replace(".tpl","",$module)."/hook.php");
                        if (@$hookData) $schema["hookData"] = $hookData;
                    }
                    
                    if (@$schema){
                        foreach($schema as $key => $value):
                            $array[$key] = $value;    
                        endforeach;
                    }
                    if (!isset($array["special"])) $array["special"] = 0;
                    
                    if (@$array["special"] && !in_array($module,array_filter(explode(",",$especiales)))) continue;
                    $array["general"] = 1;
                    $arrayModules[$module] = $array;
                }
                
            }
        endforeach;
        return $arrayModules;
    }
    return [];
    
}

function getFreeNextFieldName($prefix = "title",$busyFields = []){
    $total = 10;
    for ($i=1;$i<=$total;$i++){
        if (!in_array($prefix.$i,$busyFields)) {
            return $prefix.$i;
        }
    }
}

function generateModuleFromString($data = [],$config = true,$fromWebsite = false){
    global $CURRENT_USER;
    
    if (!$data) return ["error" => "Missing Data"];
    $modulePath = __DIR__."/modulos/cache";
    
    if (@$data["htmlParsed"]) {
        $htmlParsed = preg_replace_callback('|(\|\*)([^*]*)(\*\|)|',function($con){ return base64_decode(substr($con[0],2,strlen($con[0])-4)); },$data["htmlParsed"]);
        $data["htmlParsed"] = $htmlParsed;
    }
    
    $result = [
        "data" => [
            "id" => $data["id"],
            "html" => $data["html"],
            "htmlParsed" => @$data["htmlParsed"] ?: $data["html"],
            "style" => @$data["style"] ?: "",
            "javascript" => @$data["javascript"] ?: "",
            "hook" => @$data["hook"] ?: ""
        ]
    ];
    if ($config){
        $result["data"]["config"] = [
            "label" => @$data["label"] ?: "Sin título",
            "description" => @$data["description"] ?: "Sin descripcion",
            "notParseComponents" => @$data["notParseComponents"] ? @$data["notParseComponents"] : "0",
            "onlyAdminModule" => @$data["onlyAdminModule"] ? true : false,
            "requiredPlugins" => @$data["requiredPlugins"] ?: "",
            "MJMLModule" => @$data["MJMLModule"] ? true : false,
            "wrapper" => "index.tpl",
            "thumbnail" => "thumbnail.jpg",
            "tables" => ["builder_custom"],
            "vars" => [
                "nombreModulo" => [
                    "type" => "textfield",
                    "label" => "Nombre del módulo",
                    "relations" => [
                        "builder_custom" => "title1"
                    ]
                ]
            ]
        ];
        if (@$data["staticVars"]){
            $result["data"]["config"]["staticVars"] = $data["staticVars"];
        }
    }
    
    if ($config && @$data["vars"]){
        $resultVars = [];
        $types = [];
        $typesCustomField = ["link" => "title","headfield" => "title", "textfield" => "title","textbox" => "text","wysiwyg" => "text","upload" => "image","list" => "list"];
        
        // RECOPILO TODOS LOS CAMPOS PREASIGNADOS SEGUN TIPO
        $busyFields = ["title" => [],"text" => [],"image" => [],"list" => []];
        $usedFields = $busyFields;
        foreach($data["vars"] as $var){
            if (isset($var["prevFieldName"]) && !is_array($var["prevFieldName"])){
                $busyFields[substr($var["prevFieldName"],0,-1)][] = $var["prevFieldName"];
            }
        }
        // PRIMERO PARSEAMOS LOS CAMPOS DE PRIMER NIVEL
        foreach($data["vars"] as $var){
            if (isset($var["vars"])) continue;
            if (!isset($var["type"])) continue;
            if (!isset($var["label"])) continue;
            if (!isset($var["field"])) continue;
            if (!isset($typesCustomField[$var["type"]])) continue;
            
            if (!isset($types[$typesCustomField[$var["type"]]])){ $types[$typesCustomField[$var["type"]]] = 1; }else{ $types[$typesCustomField[$var["type"]]]+=1; }
            
            //$customField = $typesCustomField[$var["type"]].$types[$typesCustomField[$var["type"]]];
            if (@$var["prevFieldName"] && !in_array($var["prevFieldName"],$usedFields[$typesCustomField[$var["type"]]])){
                $customField = $var["prevFieldName"];
            }else{
                $customField = getFreeNextFieldName($typesCustomField[$var["type"]],$busyFields[$typesCustomField[$var["type"]]]);    
            }
            
            $busyFields[$typesCustomField[$var["type"]]][] = $customField;
            $usedFields[$typesCustomField[$var["type"]]][] = $customField;
            
            $resultVars[$var["field"]] = [
                "type" => $var["type"],
                "label" => $var["label"],
                "relations" => [
                    "builder_custom" => $customField
                ]
            ];
            
            switch($var["type"]){
                case "upload":
                    $resultVars[$var["field"]]["infoLabels"] = @$var["infos"] ?: [];
                    break;
                case "list":
                    if (@$var["options"] && !$fromWebsite){
                        $resultVars[$var["field"]]["options"] = parseListOptionsField($var["options"]);
                        if (@$var['multi']) $resultVars[$var["field"]]["multi"] = true;
                    }else if(@$var["options"] && $fromWebsite){
                        $resultVars[$var["field"]]["options"] = $var["options"];
                        if (@$var['multi']) $resultVars[$var["field"]]["multi"] = true;
                    }
                    break;
            }
        }
        // AHORA PARSEAMOS LOS MULTIS
        // ESTO CREO QUE SE PUEDE OPTIMIZAR PORQUE ESTA DUPLICADO A LO CHANO
        foreach($data["vars"] as $varParent){
            if (!isset($varParent["vars"])) continue;
            $resultVars[$varParent["field"]] = [
                "type" => $varParent["type"],
                "label" => $varParent["label"],
                "tables" => ["builder_custom"],
                "vars" => []
            ];
            
            // RECOPILO TODOS LOS CAMPOS PREASIGNADOS SEGUN TIPO
            $busyFields = ["title" => [],"text" => [],"image" => [],"list" => []];
            $usedFields = $busyFields; 
            
            foreach($varParent["vars"] as $var){
                if (isset($var["prevFieldName"])){
                    $busyFields[substr($var["prevFieldName"],0,-1)][] = $var["prevFieldName"];
                }
            }
            
            foreach($varParent["vars"] as $var){
                if (!isset($var["type"])) continue;
                if (!isset($var["label"])) continue;
                if (!isset($var["field"])) continue;
                if (!isset($typesCustomField[$var["type"]])) continue;

                if (!isset($types[$varParent["field"]][$typesCustomField[$var["type"]]])){ $types[$varParent["field"]][$typesCustomField[$var["type"]]] = 1; }else{ $types[$varParent["field"]][$typesCustomField[$var["type"]]]+=1; }

                //$customField = $typesCustomField[$var["type"]].$types[$varParent["field"]][$typesCustomField[$var["type"]]];
                
                if (@$var["prevFieldName"] && !in_array($var["prevFieldName"],$usedFields[$typesCustomField[$var["type"]]])){
                    $customField = $var["prevFieldName"];
                }else{
                    $customField = getFreeNextFieldName($typesCustomField[$var["type"]],$busyFields[$typesCustomField[$var["type"]]]);    
                }
                
                $busyFields[$typesCustomField[$var["type"]]][] = $customField;
                $usedFields[$typesCustomField[$var["type"]]][] = $customField;

                $resultVars[$varParent["field"]]["vars"][$var["field"]] = [
                    "type" => $var["type"],
                    "label" => $var["label"],
                    "relations" => [
                        "builder_custom" => $customField
                    ]
                ];
                switch($var["type"]){
                    case "upload":
                        $resultVars[$varParent["field"]]["vars"][$var["field"]]["infoLabels"] = @$var["infos"] ?: [];
                        break;
                    case "list":
                        if (@$var["options"]){
                            $resultVars[$varParent["field"]]["vars"][$var["field"]]["options"] = parseListOptionsField($var["options"]);
                            if (@$var['multi']) $resultVars[$varParent["field"]]["vars"][$var["field"]]["multi"] = true;
                        }
                        break;
                }
            }
        }
        if ($resultVars) $result["data"]["config"]["vars"] = $resultVars;
        
    }
    
    
    // SI EL MODULO ES DE TAILWIND LO PONEMOS EDITABLE
    if (@$data["tailWind"]) $result["data"]["config"]["editable"] = true;
    if ($config){
        if (!$result["data"]["id"]) return array_merge(["error" => "No existen id"],$result);
        if (!$result["data"]["html"] && !$result["data"]["style"]) return array_merge(["error" => "No existen id"],$result);
    }
    $html = $result["data"]["html"];
    $htmlParsed = $result["data"]["htmlParsed"];
    $style = $result["data"]["style"];
    $javascript = $result["data"]["javascript"];
    $hook = $result["data"]["hook"];
    
    if ($config && ($result["data"]["id"] != "custom-header-twig" && $result["data"]["id"] != "custom-footer-twig") ){
        $moduloExiste = in_array($result["data"]["id"],scandir($modulePath)) ? true : false;
        if ($moduloExiste && !@$data["editMode"]) $result["data"]["id"] = preg_replace('/([^A-Za-z0-9]*)/', '', $CURRENT_USER["domain"]["domain"])."-".$result["data"]["id"];
        $moduloExiste = in_array($result["data"]["id"],scandir($modulePath)) ? true : false;
        if ($moduloExiste && !@$data["editMode"]) $result["data"]["id"] = $result["data"]["id"]."-".time();
    }
    
    $newModulePath = $modulePath."/".$result["data"]["id"];
    
    if (@$html && @$data["image"]){
        list($type, $dataImage) = explode(';', $data["image"]);
        list(, $dataImage)      = explode(',', $dataImage);
        $dataImage = base64_decode($dataImage);
    }
    if (!@$data["editMode"] && ($result["data"]["id"] != "custom-header-twig" && $result["data"]["id"] != "custom-footer-twig")){
        if (!mkdir($newModulePath)) return array_merge(["error" => "No es posible crear la carpeta del modulo"],$result);    
    }else{
        if (!file_exists($newModulePath) && !mkdir($newModulePath)) return array_merge(["error" => "No es posible crear la carpeta del modulo"],$result);
    }
    
    
    
    if (@$html || !$config) {
        $htmlTwig = "";
        if (@$data["notParseComponents"] == "2"){
            require_once __DIR__ . "/builder_functions.php";
            $htmlTwig = compileTWIG($htmlParsed,$newModulePath);
        }
        file_put_contents($newModulePath."/index-twig.tpl",mb_check_encoding($htmlTwig,"UTF-8") ? $htmlTwig : utf8_encode($htmlTwig));
        file_put_contents($newModulePath."/index.tpl",mb_check_encoding($htmlParsed,"UTF-8") ? $htmlParsed : utf8_encode($htmlParsed));
        file_put_contents($newModulePath."/index-base.tpl",mb_check_encoding($html,"UTF-8") ? $html : utf8_encode($html));
        if ($config){
            if (@$data["editMode"]) file_put_contents($newModulePath."/builder.json",json_encode($result["data"]["config"]));
            file_put_contents($newModulePath.'/thumbnail.jpg', @$dataImage ?: file_get_contents("https://cms.cocosolution.com/img/module_base.jpg"));
            file_put_contents($newModulePath.'/screenshot.jpg', @$dataImage ?: file_get_contents("https://cms.cocosolution.com/img/module_base.jpg"));

        }
        
    }
    if (isset($style)) file_put_contents($newModulePath."/style.css",mb_check_encoding($style,"UTF-8") ? $style : utf8_encode($style));  
    if (isset($javascript)) file_put_contents($newModulePath."/script.js",mb_check_encoding($javascript,"UTF-8") ? $javascript : utf8_encode($javascript));  
    if (isset($hook) && $hook != '') file_put_contents($newModulePath."/hook.php",mb_check_encoding($hook,"UTF-8") ? $hook : utf8_encode($hook));  
    
    // ENVIAMOS EL MODULO
    
    $result["response"] = saveModule($result["data"]["id"],true,"/cache");
    
    // BORRADO
    foreach(scandir($newModulePath) as $file){
        if ($file!=".." && $file!="."){
            unlink($newModulePath."/".$file);
        }
    }
    try{
        rmdir($newModulePath);
    }catch(Exception $e){}
    
    return $result;
}

function parseListOptionsField ($options) {
    
    $result = [];
    foreach ($options as $table => $value) {
        if (!isset($value['options']) && !isset($value['tableName']) && !isset($value['query'])) {
            continue;
        }
        $result[$table] = [];
        
        if (isset($value['options'])) {
            $splitted = array_filter(array_map('trim', explode(",", $value["options"])));
            $options = [];
            foreach ($splitted as $split) {
                $splittedOption = explode("|", $split);
                if (count($splittedOption) == 1) array_unshift($splittedOption, $splittedOption[0]);
                $options[$splittedOption[0]] = $splittedOption[1];
            }
            $result[$table] = $options;
        }else{
            $result[$table] = $value;
        }
    }
    
    return $result;
}

function saveModule($folder,$replace = false,$suffix = ""){
    if (!$replace){
        $send = [
            'fileName' => $folder,
            'action_ws' => 'moduleExists'
        ];
        $response = API::sendToWeb($send, 'POST');
        if (@$response["yaExiste"]) return true;
    }
    
    $pluginDir = realpath(__DIR__.DIRECTORY_SEPARATOR."modulos".$suffix.DIRECTORY_SEPARATOR.$folder);
    if (!$pluginDir) return;
    
    $zip = compressPlugin($pluginDir);
    if (!$zip) {
        die(json_encode(['error' => 'Error al comprimir plugin']));
    }
    $send = [
        'content' => base64_encode(file_get_contents($zip)),
        'token'=>getPrefixedCookie("COCOOKIE"),
        'tokenHash' => Uploads::generate_token(),
        'fileName' => $folder,
        'replace' => $replace,
        'zip' => true,
        'action_ws' => 'saveModule'
    ];
    
    $response = API::sendToWeb($send, 'POST');
    unlink($zip);
    return $response;
}

function deleteModule($folder){
    global $SETTINGS;
    $result = mysql_query_fetch_all_assoc("SELECT DISTINCT TABLE_NAME,COLUMN_NAME 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE COLUMN_NAME IN ('builder')
        AND TABLE_SCHEMA='".$SETTINGS["mysql"]["database"]."'");
    $puedo = true;
    $message = "";
    if ($result){
        foreach($result as $res){
            $existe = mysql_query_fetch_all_assoc("SELECT * FROM ".$res["TABLE_NAME"]." WHERE builder like '%\"modulo\":\"".$folder."\"%'");
            if ($existe){
                $puedo = false;
                continue;
            }
        }
    }
    if (!$puedo) die(json_encode(["error" => 1,"message" => "El módulo no se puede eliminar"]));
    $send = [
        'token'=>getPrefixedCookie("COCOOKIE"),
        'tokenHash' => Uploads::generate_token(),
        'fileName' => $folder,
        'action_ws' => 'deleteModule'
    ];
    
    $response = API::sendToWeb($send, 'POST');
    
    return $response;
}

function compressPlugin($pluginDir) {
    $realpath = realpath($pluginDir);
    if (!$realpath) {
        return false;
    }

    $zipname = tempnam(sys_get_temp_dir(), 'zip').'.zip';
    // Initialize archive object
    $zip = new ZipArchive();
    $zip->open($zipname, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    // Create recursive directory iterator
    /** @var SplFileInfo[] $files */
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($realpath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    $folder = basename($realpath);


    foreach ($files as $name => $file)
    {
        // Skip directories (they would be added automatically)
        if (!$file->isDir())
        {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($realpath) + 1);

            // Add current file to archive
            $zip->addFile($filePath, $relativePath);
        }
    }

    // Zip archive will be created only after closing object
    $zip->close();
    return $zipname;
}

function checkAndRepairSchemaData(){
    global $schema,$menu,$TABLE_PREFIX;
    
    $schemaNew = $schema;
    if (!@$schema["builder"]) $schemaNew = createSchemaField("builder","textbox",["label" => "Builder Schema","adminOnly" => false],$schemaNew);
    if (!@$schema["controlador"]) $schemaNew = createSchemaField("controlador","textfield",["label" => "Controlador","adminOnly" => false],$schemaNew);
    if (!@$schema["precontrolador"]) $schemaNew = createSchemaField("precontrolador","textfield",["label" => "Controlador auxiliar ( para reestablecer )","adminOnly" => false],$schemaNew);
    if (!@$schema["autosaved"]) $schemaNew = createSchemaField("autosaved","checkbox",["order" => -1,"label" => "Auto Save"],$schemaNew);
    if (!@$schema["admin_only_section"]) $schemaNew = createSchemaField("admin_only_section","checkbox",["order" => -2,"label" => "Admin Only","adminOnly" => true],$schemaNew);
    
    $campos = ["builder","controlador","precontrolador","autosaved","admin_only_section"];
    
    if (@$schema["builder"]["adminOnly"] == true) $schemaNew["builder"]["adminOnly"] = false;
    if (@$schema["controlador"]["adminOnly"] == true) $schemaNew["controlador"]["adminOnly"] = false;
    if (@$schema["precontrolador"]["adminOnly"] == true) $schemaNew["precontrolador"]["adminOnly"] = false;
    
    $result = mysql_query_fetch_all_assoc("DESCRIBE `".$TABLE_PREFIX.$menu."`");
    $resultFields = array_map(function($record){ return $record["Field"]; },$result);
    
    foreach($campos as $campo){
        if (@$campo){
            if (!in_array($campo,$resultFields)) {
                $schema = null;
                break;
            }
        }
    }
    
    
    if ($schemaNew!=$schema){
        saveSchema($menu, $schemaNew);
        createMissingSchemaTablesAndFields();
    }
    try{
        
        $resultExists = loadSchema("builder_custom");        
        if (!@mysql_query_fetch_all_assoc("SELECT builder FROM ".$TABLE_PREFIX.$menu)){
            createMissingSchemaTablesAndFields();    
        }
    }catch(Exception $e){
        $newSchema = json_decode(file_get_contents(__DIR__."/builder_custom.json"),true);
        saveSchema("builder_custom", $newSchema);
        createMissingSchemaTablesAndFields();
    }
        
}

function createSchemaField($name,$type,$data = [],$schema){
    switch($type){
        case "textfield":
            $schema[$name] = [
                "order" => 150,
                "label" => "Clase color",
                "type" => "textfield",
                "defaultValue" => "",
                "description" => "",
                "fieldWidth" => "",
                "tipoTags" => 0,
                "isPasswordField" => 0,
                "isRequired" => 0,
                "isUnique" => 0,
                "minLength" => "",
                "maxLength" => "",
                "charsetRule" => "",
                "charset" => "",
                "tipoIcono" => 0,
                "tipoAtributo" => 0
            ];
            break;
        case "textbox":
            $schema[$name] = [
                "order" => 150,
                "label" => "Builder",
                "type" => "textbox",
                "defaultContent" => "",
                "description" => "",
                "isRequired" => 0,
                "isUnique" => 0,
                "minLength" => "",
                "maxLength" => "",
                "fieldHeight" => 500,
                "autoFormat" => 0
            ];
            break;
        case "checkbox":
            $schema[$name] = [
                "order" => 150,
                "label" => "Checkbox",
                "type" => "checkbox",
                "checkedByDefault" => 0,
                "description" => "",
                "checkedValue" => 1,
                "uncheckedValue" => 0,
            ];    
            break;
        default:
            
    }
    if (@$data){
        foreach($data as $key => $value){
            $schema[$name][$key] = $value;
        } 
    }
    return $schema;
}

function _ordenarArray($toOrderArray, $field, $inverse = false) {
	$position = array();
	$newRow = array();
	foreach ($toOrderArray as $key => $row) {
		$position[$key]  = $row[$field];
		$newRow[$key] = $row;
	}
	if ($inverse) {
		arsort($position);
	}
	else {
		asort($position);
	}
	$returnArray = array();
	foreach ($position as $key => $pos) {
		$returnArray[] = $newRow[$key];
	}
	return $returnArray;
}