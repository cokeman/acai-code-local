<?
// 2026-01-28 14-26-00 
// Comentario para forzar que la detección de modificaciones actualice bien cuando se edita algo que no se copia 
if (!file_exists(__DIR__ . '/../twig/vendor/autoload.php')){
    die("Es requerido instalar el plugin Twig");
}else{
    require_once __DIR__ . '/../twig/vendor/autoload.php';
}
$globalBuilderFilters = getBuilderFilters();
$tokenEditorValidated = false;
$iniConfig = null;
$globalRemoteCacheData = [];

if (class_exists("CocoDB") && property_exists("CocoDB","trackData") && isset($_REQUEST["acaiTrackData"])) CocoDB::$storeDebugData = true;

// AJUSTES DE ARCHIVOS FUERA DEL BUILDER NECESARIOS QUE ESTEN ACTUALIZADOS
if (file_exists(__DIR__."/../../../../lib/Module.php")){
    if (filemtime(__DIR__."/../../../../lib/Module.php")<1582840163){
        file_put_contents(__DIR__."/../../../../lib/Module.php",str_replace("private ","public ",file_get_contents(__DIR__."/../../../../lib/Module.php")));
    }
    if (filemtime(__DIR__."/../../../../lib/Module.php")<1658909291){
        file_put_contents(__DIR__."/../../../../lib/Module.php",str_replace("links = '';","links=''; if (defined('USE_MIN_TAILWIND') && USE_MIN_TAILWIND) return '';",file_get_contents(__DIR__."/../../../../lib/Module.php")));
    }
    if (filemtime(__DIR__."/../../../../lib/Module.php")<1659082434){
        file_put_contents(__DIR__."/../../../../lib/Module.php",str_replace("scripts = '';","scripts=''; if (defined('USE_MIN_JS_TAILWIND') && USE_MIN_JS_TAILWIND) return '';",file_get_contents(__DIR__."/../../../../lib/Module.php")));
    }
}

/* EXPERIMENTAL MINIMAL CSS */

$bbddConfig = mysql_query_fetch_all_assoc("SELECT * FROM aux_plg_config where plugin = 'builder_saas'");
$bbddConfig = json_decode($bbddConfig[0]["config"],true);
$bbddConfig = intval(@array_values(array_filter($bbddConfig,function($rec){ return $rec["campo"] == "minimalCSSTail"; }))[0]["valor"]);
if ($bbddConfig && !defined("USE_MIN_TAILWIND")){
    define("USE_MIN_TAILWIND",$bbddConfig);  
    // define("USE_MIN_JS_TAILWIND",true);     -> Este es el de javascript que por ahora se activará a mano desde variables.php
}
    

/* Necesario añadir esto en Resource.class.php

        $existMinimalCSSFile = array_filter(Module::$css,function($rec){ return strpos($rec,"cache") !== false; });
        if ($existMinimalCSSFile) $existMinimalCSSFile = array_values($existMinimalCSSFile);
        if (strpos($path,"cache") !== false && $existMinimalCSSFile) return;
        if (strpos($path,"tailwind") !== false && $existMinimalCSSFile) $path = $existMinimalCSSFile[0];
        if (strpos($path,"tailwind") == false && !$existMinimalCSSFile && @$_REQUEST["generateMinCss"]) return;
*/

function generateMinCSS($link){

    // YA NO LO USAMOS;
    return;

    /*global $tabla,$num,$iniConfig;
    if (!isset($iniConfig)) $iniConfig = file_exists(__DIR__."/custom-schema.ini.php") ? loadINI(__DIR__."/custom-schema.ini.php") : [];
    if (intval(@$iniConfig["config"]["minimalCSSTail"]) !== 1 || @$_REQUEST["moduloBuilder"]) return;

    if (!@$tabla || !@$num) return;
    $hash = substr(md5(strtotime(getLastCommit()).$tabla.$num),0,8);
    $cachePath = realpath(__DIR__."/../../../../cache");

    if (!file_exists(__DIR__."/layout.json")) return;
    if (!file_exists($cachePath)) mkdir($cachePath);
    if (!file_exists($cachePath."/".$hash)) mkdir($cachePath."/".$hash);
    if (file_exists($cachePath."/".$hash."/style.css") || file_exists($cachePath."/".$hash."/style.min.css")) {
        if (!file_exists($cachePath."/".$hash."/style.min.css") && file_exists($cachePath."/".$hash."/style.css") && filesize($cachePath."/".$hash."/style.css") > 0){
            $minifier = new MatthiasMullie\Minify\CSS($cachePath."/".$hash."/style.css");
            $minifier->minify($cachePath."/".$hash."/style.min.css");
            unlink($cachePath."/".$hash."/style.css");

        }
        ModuleBuilder::loadFiles($hash, [],'cache');
        return;
    }

    $resultMinCSS = @shell_exec('uncss -t 3000  "https://'.$_SERVER["HTTP_HOST"].$link.'?pruebas=1&generateMinCss=1" > ~/httpdocs/cache/'.$hash.'/style.css 2>/dev/null &');*/

}

function generateMinJsV2(){
    $cocotailFilename = "cocotail.min.js";
    require_once __DIR__."/../../../../lib/minifier.php";

    $path = realpath(__DIR__."/../../../../template/estandar");


    if (file_exists($path."/js/".$cocotailFilename) && !isset($_REQUEST["indev"])){
        $fileTimestamp = intval(filemtime($path."/js/cocotail.min.js"));
        $lastCommitTimestamp = intval(shell_exec("cd ~/httpdocs; git log -1 --format=%at"));

        if ($fileTimestamp > $lastCommitTimestamp ){
            $resource = h("/js/cocotail.min.js");
            return file_get_contents($path."/js/minified/cocotail.min.js");
        }
    }


    if (!file_exists(__DIR__."/layout.json")) die(json_encode(['error' => ['message' => 'No layout data', 'code' => 403]]));

    $modulosScripts = ["if (typeof(MODULES_LOADED) == 'undefined'){ console.log('ERROR : No se han encontrado módulos cargados en esta página en Js por lo que los scripts de estos no se cargarán'); var MODULES_LOADED = []; }"];
    foreach(scandir($path."/modulos/") as $modulo){

        if ($modulo == "." || $modulo == ".." || !file_exists($path."/modulos/".$modulo."/script.js")) continue;

        $content = file_get_contents($path."/modulos/".$modulo."/script.js");

        if (file_exists($path."/modulos/".$modulo."/script.js") && strpos($content,"File not found") !== false) {
            unlink($path."/modulos/".$modulo."/script.js");
            continue;
        }
        if (file_exists($path."/modulos/".$modulo."/script.js") && strpos($content,"502 Bad Gateway") !== false) {
            unlink($path."/modulos/".$modulo."/script.js");
            continue;
        }
        if (file_exists($path."/modulos/".$modulo."/script.js") && strpos($content,"PUT JAVASCRIPT MODULE HERE") !== false) { 
            continue;
        }
        
        if (!$content) continue;
        $modulosScripts[] = "if (MODULES_LOADED && MODULES_LOADED.indexOf('".$modulo."') > -1){ console.log(`Ejecutando Script Modulo ".$modulo."`); ".$content."};";
    }


    $layout = json_decode(file_get_contents(__DIR__."/layout.json"),true);
    $merged = array_map(function($rec){ return $rec["url"]; },array_merge($layout["librariesJSONt"],$layout["librariesJSONb"]));

    $mergeJs = array_values(array_filter($merged,function($rec){ return strpos($rec,"http") === false && strpos($rec,".js"); }));
    $mergeJs = array_map(function($r) { return str_replace("/template/estandar","",$r);},$mergeJs);
    $mergeJs = array_map(function($r) { return substr($r,0,1) == "/" ? substr($r,1) : $r; },$mergeJs);
    
    if ($mergeJs){
        $mergeJsContent = [];
        foreach($mergeJs as $jsFile){
            $content = file_get_contents($path."/".$jsFile);
            if (!$content) continue;
            $mergeJsContent[] = "\n\n/* *[".$jsFile."]* */\n\n".file_get_contents($path."/".$jsFile).";";
        }
    }

    $modulosScripts[] = $layout["javascript"];
    
    $modulosScripts = array_merge($mergeJsContent,$modulosScripts);

    $result = join("\n\n",$modulosScripts);

    
    file_put_contents($path."/js/".$cocotailFilename,$result);

    $resource = h("/js/cocotail.min.js");

     file_put_contents($path."/js/minified/".$cocotailFilename,$result);

    return ' /* Generated now '.date("d/m/Y H:i:s",filemtime($path."/js/".$cocotailFilename)).' */ '.file_get_contents($path."/js/minified/cocotail.min.js");
    
}

function generateMinCssV2(){

    /* GET params:
    ?indev=1 -> Force Generate
    ?remoteDomain -> Add remote files ( needed to be updated ) -- AUN NO REALIZADO
    */

    $cocotailFilename = "cocotail.min.css";
    require_once __DIR__."/../../../../lib/minifier.php";

    $path = realpath(__DIR__."/../../../../template/estandar");


    if (file_exists($path."/css/".$cocotailFilename) && !isset($_REQUEST["indev"])){
        $fileTimestamp = intval(filemtime($path."/css/cocotail.min.css"));
        $lastCommitTimestamp = intval(shell_exec("cd ~/httpdocs; git log -1 --format=%at"));

        if ($fileTimestamp > $lastCommitTimestamp ){
            $resource = h("/css/cocotail.min.css");
            return file_get_contents($path."/css/minified/cocotail.min.css");
        }
    }
    

    if (!file_exists(__DIR__."/layout.json")) die(json_encode(['error' => ['message' => 'No layout data', 'code' => 403]]));

    foreach(scandir($path."/modulos/") as $modulo){
        if (file_exists($path."/modulos/".$modulo."/style.css") && strpos(file_get_contents($path."/modulos/".$modulo."/style.css"),"File not found") !== false) {
            unlink($path."/modulos/".$modulo."/style.css");
        }
        if (file_exists($path."/modulos/".$modulo."/style.css") && strpos(file_get_contents($path."/modulos/".$modulo."/style.css"),"502 Bad Gateway") !== false) {
            unlink($path."/modulos/".$modulo."/style.css");
        }
    }


    $layout = json_decode(file_get_contents(__DIR__."/layout.json"),true);
    $merged = array_map(function($rec){ return $rec["url"]; },array_merge($layout["librariesJSONt"],$layout["librariesJSONb"]));

    $hasTailwind3 = @array_values(array_filter($merged,function($rec){ 
        return strpos($rec,"tailwind") && strpos($rec,".js"); 
    })) ? true : false;

    $mergeJs = array_values(array_filter($merged,function($rec){ return strpos($rec,"http") === false && strpos($rec,".js") && strpos($rec,"tailwind") === false; }));
    $mergeJs = array_map(function($r) { return str_replace("/template/estandar","",$r);},$mergeJs);
    $mergeJs = array_map(function($r) { return substr($r,0,1) == "/" ? substr($r,1) : $r; },$mergeJs);
    $mergeJs = array_merge($mergeJs,['modulos/**/*.tpl','modulos/**/*.js','modulos/**/*.vue','modulos/**/*.php']);

    $merged = array_values(array_filter($merged,function($rec){ return strpos($rec,"http") === false && strpos($rec,".css") && strpos($rec,"tailwind") === false; }));

    $merged = array_merge($merged,['css/tailwind.min.css','css/tailwindcss-custom.css']); 
    
    $merged = array_merge($merged,['modulos/**/style.css',["raw" => minify_css($layout["style"]) ]]);

    if ($hasTailwind3){
        compileTailwind($path);
    }
    
    $merged = array_map(function($rec){ 
        if (is_array($rec)) return $rec;
        $rec = str_replace("/template/estandar","",$rec);
        return substr($rec,0,1) == "/" ? substr($rec,1) : $rec; 
    },$merged);

    unlink($path."/tailwind.config.js");

    $configSTR = "
module.exports = { 
    defaultExtractor: (content) => content.match(/[\w-/:\#.\[\]\%]+(?<!:)/g) || [],
    css: ".json_encode($merged).", 
    content: ".json_encode($mergeJs).",
    skippedContentGlobs: ['modulos/**/minified/*']
};
    ";
    file_put_contents($path."/tailwind.config.js",$configSTR); 

    $logArrayResult = @shell_exec('pwd;');// cd ~/httpdocs/template/estandar; purgecss -c tailwind.config.js 2>&1;');
    $prefixChangeDir = substr($logArrayResult,strlen($logArrayResult)-9,8) == 'httpdocs' ? 'cd template/estandar;' : 'cd ~/httpdocs/template/estandar;';
    $logArrayResult = @shell_exec($prefixChangeDir . ' purgecss -c tailwind.config.js 2>&1;');
    
    
    $array = @json_decode($logArrayResult,true);
    if ($array){
        $array = array_values(array_filter($array,function($rec){ return @$rec["css"]; }));
        $array = array_unique(array_map(function($rec){ return $rec["css"]; },$array));

        $result = join("\n\n",$array);
        
        file_put_contents($path."/css/".$cocotailFilename,$result);

        $resource = h("/css/cocotail.min.css");
        return ' /* Generated now '.date("d/m/Y H:i:s",filemtime($path."/css/".$cocotailFilename)).' */ '.file_get_contents($path."/css/minified/cocotail.min.css");        
        
    }else{

        return ' /* Can`t generate cocotail with purgecss */ \n /*'.$logArrayResult.' */';

    }

}

/* EXPERIMENTAL MINIMAL CSS lala */

function getLastCommit(){
    $ultimoCommit = @shell_exec("cd ~/httpdocs; git log -1 --format=%cd");
    if (@$ultimoCommit){
        $fecha = date("Y-m-d H:i:s",strtotime($ultimoCommit));
        return $fecha;
    }
    return false;
}
function getBuilderFilters(){
    return [
        new \Twig\TwigFilter('addPlugins','addPlugins'),
        new \Twig\TwigFilter('hook','hook'),
        new \Twig\TwigFilter('get','CocoDB::get'),
        new \Twig\TwigFilter('h','h'),
        new \Twig\TwigFilter('isHTML',function($string){
            return $string != strip_tags($string);
        }),
        new \Twig\TwigFilter('resource','Resource::link'),
        new \Twig\TwigFilter('force_to_int','intval'),
        new \Twig\TwigFilter('force_to_float','floatval'),
        new \Twig\TwigFilter('unique','array_unique'),
        new \Twig\TwigFilter('imagec','CustomCode::imagec_inv'),
        new \Twig\TwigFilter('module','BuilderModule'),
        new \Twig\TwigFilter('modulo_builder','modulo_builder_config'),
        new \Twig\TwigFilter('dame_variables','dame_variables_config'),
        new \Twig\TwigFilter('loadSchema','loadSchema'),
        new \Twig\TwigFilter('base64_decode','base64_decode'),
        new \Twig\TwigFilter('count','count'),
        new \Twig\TwigFilter('mysql_escape','mysql_real_escape_string'),
        new \Twig\TwigFilter('json_decode', 'json_decode'),
        new \Twig\TwigFilter('base64_encode','base64_encode'),
        new \Twig\TwigFilter('cocoForm','CocoParser::cocoForm'),
        new \Twig\TwigFilter('translate','t_var'),
        new \Twig\TwigFilter('translateDB','t'),
        new \Twig\TwigFilter('htmlentities', 'htmlentities'),
        //new \Twig\TwigFilter('nl2br','nl2br'),
        new \Twig\TwigFilter('muestra_breadcrumb','muestra_breadcrumb'),
        new \Twig\TwigFilter('queryDB',function($string){
            global $TABLE_PREFIX;
            return @mysql_query_fetch_all_assoc(str_replace("{TABLE_PREFIX}",$TABLE_PREFIX,$string));
        })
    ];
}

function getBuilderFunctions(){
    return [
        new \Twig\TwigFunction('menu', 'CustomCode::Menu'),
        new \Twig\TwigFunction('get_session', function() { return $_SESSION; }),
        new \Twig\TwigFunction('get_request', function() { return $_REQUEST; }),
        new \Twig\TwigFunction('hasRecaptcha', 'hasRecaptcha'),
        new \Twig\TwigFunction('cocoForm', 'CocoParser::cocoForm'),
        new \Twig\TwigFunction('minimalCSS',function(){
            return generateMinCssV2();
        })
    ];
}

function addFilesHooksToLayout(&$layoutHooks = []){
    $filesHooks = glob(__DIR__."/../../../../hooks/*.php");
    foreach($filesHooks as $fileHook){
        
        $id = str_replace(".php","",basename($fileHook));
        $endpoint = "/".str_replace(".","/",$id)."/";
        
        if (array_filter($layoutHooks,function($rec) use ($endpoint){ return $rec["endPoint"] == $endpoint; })){
            // si ya existe sólo actualizamos el código por si se ha modificado el hook
            $layoutHooks = array_map(function($rec) use ($endpoint,$fileHook){
                if ($rec["endPoint"] == $endpoint){
                    $rec["code"] = "|*".base64_encode(file_get_contents($fileHook))."*|";
                }                return $rec;
            },$layoutHooks);
            continue;
        }

        $layoutHooks[] = [
            "endPoint" => $endpoint,
            "id" => "layout:hooks/".str_replace(".","",$id),
            "entryParams" => [],
            "middleWare" => [],
            "code" => "|*".base64_encode(file_get_contents($fileHook))."*|",
            "visible" => true,
            "zoom" => false,
            "saved" => true,
            
        ];
    }
}

function addModulesHooksToLayout($id = null,&$layoutHooks = [],$dataVars = []){

    $functionVars = @$dataVars ? array_keys($dataVars) : [];
    $functionValues = @$dataVars ? array_values($dataVars) : [];
    $entryParams = [];
    if ($functionVars){
        foreach($functionVars as $cont => $functionVar){
            $entryParams[] = [
                "variable" => $functionVar,
                "value" => $functionValues[$cont],
                "valueType" => "String"
            ];
        }
    }

    if ($id && file_exists(__DIR__."/../../../../template/estandar/modulos/".$id."/hook.php")){

        
        $layoutHooks[] = [
            "endPoint" => "/hooks/".$id."/",
            "entryParams" => $entryParams,
            "middleWare" => [],
            "code" => "|*".base64_encode(file_get_contents(__DIR__."/../../../../template/estandar/modulos/".$id."/hook.php"))."*|",
            "visible" => true,
            "zoom" => false,
            "saved" => true,
            "id" => "layout:hooks/".$id
        ];
        return;
    }
    
    $modules = scandir(__DIR__."/../../../../template/estandar/modulos");
    
    foreach($modules as $module){
        if ($id && $module !== $id) continue;
        if ($module == "." || $module == "..") continue;
        if (!file_exists(__DIR__."/../../../../template/estandar/modulos/".$module."/hook.php")) continue;
        $layoutHooks[] = [
            "endPoint" => "/hooks/".$module."/",
            "entryParams" => $entryParams,
            "middleWare" => [],
            "code" => "|*".base64_encode(file_get_contents(__DIR__."/../../../../template/estandar/modulos/".$module."/hook.php"))."*|",
            "visible" => true,
            "zoom" => false,
            "saved" => true,
            "id" => "layout:hooks/".$module
        ];
    }
    return $layoutHooks;
    
}

function sendRemoteBuilder($host = null,$action_ws = "getModuleSchemas&full=1&twig=1",$module = null){
    if (!$host) return "[]";

    $postdata = $module ? http_build_query(['ids' => [$module]]) : [];

    $opts = ['http' =>
        [
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $postdata
        ],
        "ssl"=>[
            "verify_peer"=>false,
            "verify_peer_name"=>false,
        ]
    ];

    $context  = stream_context_create($opts);

    $data = "";

    $data = @file_get_contents("https://".$host."/cms/lib/viewer_functions.php?action_ws=".$action_ws, false, $context);

    if ($data && @$action_ws == "getLayoutData") {
        $data2 = @file_get_contents("https://".$host."/cms/lib/viewer_functions.php?action_ws=getHooksData&remoteHooksToken=6342e78dfae29c3bc0f8e9c1f82676f1", false, $context);
        $data2 = @json_decode($data2,true);
        if (@$data2["data"]){
            $hooksData = $data2["data"];
            $data = json_decode($data,true);
            $data["data"]["hooks"] = $hooksData;
            $data = json_encode($data);
        }
    }

    global $contador_de_llamadas_a_sendRemoteBuilder_por_seguridad;
    $contador_de_llamadas_a_sendRemoteBuilder_por_seguridad += 1;
    if($contador_de_llamadas_a_sendRemoteBuilder_por_seguridad > 100) {
        die('Muchas llamadas');
    }

    if (!$data) {
        CocoEmail::send_email("soporte@cocosolution.com", "Error al conectar a remota", "Se ha intentado conectar desde la web {$_SERVER['REMOTE_HOST']} al módulo ({$module}) en {$host} y el resultado ha sido nulo");
        header("Location:/errorcode/204/");
    }

    return $data;
}

function hook($enlace = null,...$dataVars){
    global $globalRemoteCacheData;

    $enlace = "/hooks/".rtrim(str_replace("/hooks/","",$enlace),"/")."/";

    if (class_exists("CocoDB") && property_exists("CocoDB","trackData") && isset($_REQUEST["acaiTrackData"])) CocoDB::setTrackData(false,"HOOK",$enlace,$dataVars);

    try{
        $remoteHost = null;
        if(isset($dataVars) && isset($dataVars['remote'])) $remoteHost = $dataVars['remote'];
        if(isset($dataVars) && isset($dataVars[0]) && isset($dataVars[0]['remote'])) $remoteHost = $dataVars[0]['remote'];
        
        if ($remoteHost && $remoteHost != $_SERVER["HTTP_HOST"]) {
            // OBGLIGATORIO EL USO DE REDIS
            $hash = CocoDB::cacheGenerateHash("REMOTE_LAYOUT");

            if (!class_exists("CocoDB") ) return ["error" => "Falta librería CocoDB"];
            try{
                CocoDB::initCache();
                if (!CocoDB::$redis) throw new Exception("Error Processing Request", 1);
            }catch(Exception $e){
                return ["error" => "El servidor debe disponer del sistema de caché redis para usar módulos remotos"];
            }

            // MODULOS REMOTOS
            if (isset($globalRemoteCacheData[$hash])){
                $result = $globalRemoteCacheData[$hash];
            }else{
                if (defined("REMOTE_CACHE") && REMOTE_CACHE == true) $result = CocoDB::cacheGet($hash);

                if (!@$result){
                    $result = sendRemoteBuilder($remoteHost,"getLayoutData");
                    $globalRemoteCacheData[$hash] = $result;
                    if (defined("REMOTE_CACHE") && REMOTE_CACHE == true) CocoDB::cacheSet($hash,$result,(defined("REMOTE_CACHE_TIME") ? REMOTE_CACHE_TIME : 60));
                }
            }

            $layout = json_decode($result,true);
            if (!@$layout["data"]) {
                $layout = [];
            }else{
                $layout = $layout["data"];
            }
        }else{
            if (class_exists("CocoDB") && isset(CocoDB::$force_redis) && CocoDB::$force_redis){
                $hash = CocoDB::cacheGenerateHash("LAYOUT");
                $data = CocoDB::cacheGet($hash);
                if (@$data) $layout  = json_decode($data,true);
            }
            if (!@$layout) {
                $layout = json_decode(file_get_contents(__DIR__."/layout.json"),true);

                if (class_exists("CocoDB") && isset(CocoDB::$force_redis) && CocoDB::$force_redis){
                    CocoDB::cacheSet($hash,json_encode($layout));
                }
            }
        }

        $hookNameSep = array_values(array_filter(explode("/",$enlace)));
        $hookName = $hookNameSep[count($hookNameSep)-1];
        addModulesHooksToLayout($hookName,$layout["hooks"],$dataVars);
        
        // HOOKS_FILES
        if (!@$layout["hooks"]) $layout["hooks"] = [];
        addFilesHooksToLayout($layout["hooks"]); // Añadimos también los hooks de archivos para que se ejecuten aunque no se hayan añadido al layout ( por ejemplo en el caso de módulos nuevos añadidos sin editar el layout )

        if (@$layout["hooks"]){

            foreach($layout["hooks"] as $hook){
                if ($enlace === $hook["endPoint"]){
                    if (is_array(@$dataVars[0])) {
                        $dataVars = $dataVars[0];
                    }else{
                        $newDataVars = [];
                        foreach($hook["entryParams"] as $cont => $entryParam){
                            if (isset($dataVars[$cont])){
                                $hook["entryParams"][$cont]["value"] = $dataVars[$cont];
                            }
                            $newDataVars[$hook["entryParams"][$cont]["variable"]] = @$hook["entryParams"][$cont]["value"];

                        }
                        if (!empty($newDataVars)) $dataVars = $newDataVars;
                    }

                    $php = str_replace("|*","",$hook["code"]);
                    $php = str_replace("*|","",$php);
                    $php = base64_decode($php);
                    // Dani 29/10/2024: uft8_encode deprecated.
                    $php = mb_check_encoding($php,"UTF-8") ? $php : mb_convert_encoding($php, 'UTF-8', 'ISO-8859-1');
                    $php = str_replace("<?php","",$php);
                    $php = str_replace("<?","",$php);
                    $php = str_replace("?>","",$php);
                    $php = str_replace("shell_exec(","echo(",$php);
                    $php = str_replace("curl_exec(","curl_eccec(",$php);
                    $php = str_replace("exec(","echo(",$php);
                    $php = str_replace("curl_eccec(","curl_exec(",$php);
                    $php = str_replace("unlink(","echo(",$php);


                    if (class_exists("CmsApi")) CmsApi::setBacktracePoint($hook["endPoint"]);

                    extract($dataVars);
                    return eval($php);
                }
            }

        }
    }catch(Exception $e){
        if (ini_get('display_errors') === '1') throw $e;
        if ($e->getCode() == 403 || $e->getCode() == 400) return(json_encode(["error" => "Error en la ejecución del hook"]));
    }
}
function wrapperCommentModule($moduleName, $html,$variables,$hash = null){
    global $tokenEditorValidated;



    $resultHTML = "";

    if (@$_SESSION["tokenEditor"]){
        CocoWS::setToken(@$_SESSION["tokenEditor"]);
        if (!$tokenEditorValidated){
            $resultToken = CocoWS::validateUploadToken(@$_SESSION["uploadToken"]);
        }else{
            $resultToken = true;
        }
        if (!$resultToken) {
            $resultHTML = "\n<!-- start_mod : ".$moduleName." --> \n".$html."\n<!-- end_mod -->";
        }else{
            $tokenEditorValidated = true;
            $resultHTML = "\n<!-- start_mod : ".$moduleName."|".$variables["section_id"]."|".$variables["thisrecord"]["tableName"]."|".$variables["thisrecord"]["num"]." --> \n".$html."\n<!-- end_mod -->";
        }
    }else{
        $resultHTML = "\n<!-- start_mod : ".$moduleName." --> \n".$html."\n<!-- end_mod -->";
    }

    if (class_exists("CocoDB") && isset(CocoDB::$force_redis_module) && CocoDB::$force_redis_module && $hash){
        CocoDB::cacheSet($hash,$resultHTML);
    }
    return $html ? "<!--margin_sup-->".$resultHTML."<!--margin_inf-->" : $resultHTML;
}

function BuilderModule($module,$data = []){
    global $apartado,$configuracionRecord,$section,$tokenEditorValidated,$globalRemoteCacheData;

    if (@$_REQUEST["appversion"] && ($module == "custom-header-twig" || $module == "custom-footer-twig")) return "<!-- hidden header and footer module in app mode -->";
    if (class_exists("CocoDB") && property_exists("CocoDB","trackData") && isset($_REQUEST["acaiTrackData"])) CocoDB::setTrackData(false,"MODULE",$module,$data);

    $variables = [];
    $variables["apartadoWrapper"] = @$apartado;
    $variables["thisrecord"] = @$apartado;
    $variables["configuracion"] = @$configuracionRecord;
    $variables["request"] = @$_REQUEST;
    $variables["session"] = @$_SESSION;
    $variables["server"] = @$_SERVER;
    $variables["section_id"] = @$section["section_id"];
    $variables["thismodule"] = @$section;

    foreach($data as $key => $value){
        $variables[$key] = $value;
    }
    $modulePath = realpath(__DIR__ . "/../../../../template/estandar/modulos/".$module);

    if (isset($data["remote"]) ){

        // OBGLIGATORIO EL USO DE REDIS
        $hash = CocoDB::cacheGenerateHash("REMOTE_".md5($module.json_encode($data).json_encode($_REQUEST)));

        if (!class_exists("CocoDB") ) return "<div class='bg-red-300 text-red-600 text-xs text-center p-4 rounded'>Falta librería CocoDB</div>";
        try{
            CocoDB::initCache();
            if (!CocoDB::$redis) throw new Exception("Error Processing Request", 1);
        }catch(Exception $e){
            return "<div class='bg-red-300 text-red-600 text-xs text-center p-4 rounded'>El servidor debe disponer del sistema de caché redis para usar módulos remotos</div>";
        }

        // MODULOS REMOTOS


        if (isset($globalRemoteCacheData[$hash])){
            $result = $globalRemoteCacheData[$hash];
        }else{
            if (defined("REMOTE_CACHE") && REMOTE_CACHE == true) $result = CocoDB::cacheGet($hash);
//            $result = CocoDB::cacheGet($hash);
            if (!@$result){
                $result = sendRemoteBuilder($data["remote"],"getModuleSchemas&full=1&twig=1",$module);
                $globalRemoteCacheData[$hash] = $result;
                if (defined("REMOTE_CACHE") && REMOTE_CACHE == true) CocoDB::cacheSet($hash,$result,(defined("REMOTE_CACHE_TIME") ? REMOTE_CACHE_TIME : 60));
//                CocoDB::cacheSet($hash,$result,(defined("REMOTE_CACHE_TIME") ? REMOTE_CACHE_TIME : 60));
            }
        }



        if (@$result){
            $result = json_decode($result,true);
            if (!@$result["modules"][$module]["htmlDataTWIG"]) {
                var_dump($result);
                return "No se encuentra el módulo solicitado";
            }
            if (@$result["modules"][$module]["styleData"]) ModuleBuilder::addRemote("https://".$data["remote"].(@$result["modules"][$module]["styleFilename"] ?: "/template/estandar/modulos/".$module."/style.css"));
            if (@$result["modules"][$module]["javascriptData"]) ModuleBuilder::addRemote("https://".$data["remote"].(@$result["modules"][$module]["javascriptFilename"] ?: "/template/estandar/modulos/".$module."/script.js"));
            if (@$result["modules"][$module]["hookData"]) ModuleBuilder::addRemote("https://".$data["remote"].(@$result["modules"][$module]["hookFilename"] ?: "/template/estandar/modulos/".$module."/hook.php"));


            // Anael: Mejora para poder hacer un modulo remoto con el mismo nombre
            $php_to_compile = str_replace("<?php","",str_replace("?>","",$result["modules"][$module]["htmlDataTWIG"]));
            preg_match('/class\s+(.*)\s+extends/',$php_to_compile,$coincidencias,PREG_OFFSET_CAPTURE);
            if(@$coincidencias[1][0]) {
                $php_to_compile = str_replace($coincidencias[1][0], $coincidencias[1][0] . '_remote', $php_to_compile);
            }
            // Anael: Hasta aquí.

            ob_start();
            eval($php_to_compile);
            // Anael: comentado para la conversión de arriba.
            /*
            eval(str_replace("<?php","",str_replace("?\>","",$result["modules"][$module]["htmlDataTWIG"])));
            */
            $acaiResultData->doDisplay($variables);
            $resultado = ob_get_clean();
            if (@$variables["customColors"]){
                $resultado = str_replace(array_keys($variables["customColors"]), array_values($variables["customColors"]), $resultado);
            }
            // FALTAN LOS JS Y LOS CSS
            return wrapperCommentModule($module,minify_html($resultado),$variables,@$hash);
        }
        return "";

    }

    if (class_exists("CocoDB") && isset(CocoDB::$force_redis_module) && CocoDB::$force_redis_module){
        $hash = CocoDB::cacheGenerateHash("MODULE_".md5($module.json_encode($data).json_encode($_REQUEST)));
        $data2 = CocoDB::cacheGet($hash);

        if (@$data2) {
            if (class_exists("Module") && class_exists('ModuleBuilder')) ModuleBuilder::loadFiles($module, $variables);
            return $data2;
        }
    }

    $base = (@$_REQUEST["viewAMP"]) ? "amp" : "index";

    $variables["customColors"] = @CocoDB::get("aux_plg_config","plugin = '".md5("custom-colors|".strtolower($module)."|".$apartado["num"]."|".$section["section_id"])."'","num desc",1,["ignoreSchema" => true,"prefix" => ""])[0];
    $variables["customColors"] = @$variables["customColors"] ? json_decode($variables["customColors"]["config"],true) : [];

    $variables["customMargins"] = @CocoDB::get("aux_plg_config","plugin = '".md5("custom-margins|".strtolower($module)."|".$apartado["num"]."|".$section["section_id"])."'","num desc",1,["ignoreSchema" => true,"prefix" => ""])[0];
    $variables["customMargins"] = @$variables["customMargins"] ? json_decode($variables["customMargins"]["config"],true) : [];


    if ( file_exists($modulePath.'/'.$base.'-twig.tpl') && filesize($modulePath.'/'.$base.'-twig.tpl') !== 0 ){
        if (class_exists("Module") && class_exists('ModuleBuilder')) ModuleBuilder::loadFiles($module, $variables);
        ob_start();
        require($modulePath.'/'.$base.'-twig.tpl');
        $acaiResultData->doDisplay($variables);
        $resultado = ob_get_clean();
        if (@$variables["customColors"]){
            $resultado = str_replace(array_keys($variables["customColors"]), array_values($variables["customColors"]), $resultado);
        }

        $resultado = wrapperCommentModule($module,minify_html($resultado),$variables,@$hash);

        if (@$variables["customMargins"]){
            if (@$variables["customMargins"]["sup"]) $resultado = str_replace("<!--margin_sup-->", "<div class=\"".join(" ",array_values($variables["customMargins"]["sup"]))."\"></div>", $resultado);
            if (@$variables["customMargins"]["inf"]) $resultado = str_replace("<!--margin_inf-->", "<div class=\"".join(" ",array_values($variables["customMargins"]["inf"]))."\"></div>", $resultado);
        }
        $resultado = str_replace("<!--margin_sup-->","",$resultado);
        $resultado = str_replace("<!--margin_inf-->","",$resultado);

        return $resultado;


    }else{
        if (@$_REQUEST["viewAMP"]){
            /*if (!file_exists($modulePath.'/'.$base.'.tpl')) die("");
            if (class_exists("Module") && class_exists('ModuleBuilder')) ModuleBuilder::loadFiles($module, $variables);
            ob_start();
            extract($variables);

            require($modulePath.'/'.$base.'.tpl');
            $resultado = ob_get_clean();
            if (@$variables["customColors"]){
                $resultado = str_replace(array_keys($variables["customColors"]), array_values($variables["customColors"]), $resultado);
            }
            $resultado = wrapperCommentModule($module,minify_html($resultado),$variables,@$hash);

            if (@$variables["customMargins"]){
                if (@$variables["customMargins"]["sup"]) $resultado = str_replace("<!--margin_sup-->", "<div class=\"".join(" ",array_values($variables["customMargins"]["sup"]))."\"></div>", $resultado);
                if (@$variables["customMargins"]["inf"]) $resultado = str_replace("<!--margin_inf-->", "<div class=\"".join(" ",array_values($variables["customMargins"]["inf"]))."\"></div>", $resultado);
            }
            $resultado = str_replace("<!--margin_sup-->","",$resultado);
            $resultado = str_replace("<!--margin_inf-->","",$resultado);

            return $resultado;*/
        }else{
            $resultado = Modulo($module,$variables);
            if (@$variables["customColors"]){
                $resultado = str_replace(array_keys($variables["customColors"]), array_values($variables["customColors"]), $resultado);
            }

            $resultado = wrapperCommentModule($module,$resultado,$variables,@$hash);

            if (@$variables["customMargins"]){
                if (@$variables["customMargins"]["sup"]) $resultado = str_replace("<!--margin_sup-->", "<div class=\"".join(" ",array_values($variables["customMargins"]["sup"]))."\"></div>", $resultado);
                if (@$variables["customMargins"]["inf"]) $resultado = str_replace("<!--margin_inf-->", "<div class=\"".join(" ",array_values($variables["customMargins"]["inf"]))."\"></div>", $resultado);
            }
            $resultado = str_replace("<!--margin_sup-->","",$resultado);
            $resultado = str_replace("<!--margin_inf-->","",$resultado);

            return $resultado;
        }

    }
}
function compileTWIG($htmlParsed,$newModulePath){
    require_once __DIR__ . "/../../classes/CocoDB.php";
    $globalBuilderFilters = getBuilderFilters();
    $globalBuilderFunctions = getBuilderFunctions();

    try{

        // Dani 29/10/2024: uft8_encode deprecated.
        file_put_contents($newModulePath."/index.twig",mb_check_encoding($htmlParsed,"UTF-8") ? $htmlParsed : mb_convert_encoding($htmlParsed, 'UTF-8', 'ISO-8859-1'));

        $loader = new \Twig\Loader\FilesystemLoader($newModulePath);
        $twig = new \Twig\Environment($loader, ['debug' => true]);
        $twig->addExtension(new \Twig\Extension\DebugExtension());

        require_once __DIR__."/builder_functions.php";

        if (isset($globalBuilderFilters)){
            foreach($globalBuilderFilters as $filter){
                $twig->addFilter($filter);
            }
        }
        if (isset($globalBuilderFunctions)){
            foreach($globalBuilderFunctions as $filter){
                $twig->addFunction($filter);
            }
        }

        $stream = $twig->tokenize(new \Twig\Source($htmlParsed,'index.twig',$newModulePath));
        $compiler = new \Twig\Compiler($twig);
        $twig->setCompiler($compiler);
        $nodes = $twig->parse($stream);
        $compiler->compile($nodes);
        preg_match('/class\s+(.*)\s+extends/',$compiler->getSource(),$coincidencias,PREG_OFFSET_CAPTURE);
        if (isset($coincidencias[1])){
            $compiler->raw("}\n");
            $compiler->raw("\$loader = new \Twig\Loader\FilesystemLoader(__DIR__);\n");
            $compiler->raw("\$twig = new \Twig\Environment(\$loader, ['debug' => true]);\n");
            $compiler->raw("\$twig->addExtension(new \Twig\Extension\DebugExtension());\n");
            $compiler->raw("global \$globalBuilderFilters; \n if (!@\$globalBuilderFilters) \$globalBuilderFilters = getBuilderFilters(); if (isset(\$globalBuilderFilters)) { foreach(\$globalBuilderFilters as \$filter): \$twig->addFilter(\$filter); endforeach; }\n");
            $compiler->raw("global \$globalBuilderFunctions; \n if (!@\$globalBuilderFunctions) \$globalBuilderFunctions = getBuilderFunctions(); if (isset(\$globalBuilderFunctions)) { foreach(\$globalBuilderFunctions as \$filter): \$twig->addFunction(\$filter); endforeach; }\n");

            $compiler->raw("\$acaiResultData = new ".$coincidencias[1][0]."(\$twig);\n");
        }

        $php = str_replace("protected function doDisplay","public function doDisplay",$compiler->getSource());
        $php = str_replace("class __TwigTemplate","if (!class_exists('".$coincidencias[1][0]."')){\nclass __TwigTemplate",$php);
        unlink($newModulePath."/index.twig");

        return $php;
    }catch(Exception $e){
        die(json_encode(["error" => "Error al parsear el documento","message" => $e->getmessage()]));
    }
}
if (!class_exists("Module") && file_exists(__DIR__ . '/../../../../lib/Module.php')){
    require_once __DIR__ . '/../../../../lib/Module.php';
}
if (class_exists("Module")){

    class ModuleBuilder extends Module
    {
        public static function loadFiles($folder,$params,$auxPath = null){
            if (@$_REQUEST["viewAMP"]) return;
            $folderAbs = $auxPath ? './'.$auxPath.'/'.$folder : Module::$path.$folder;

            if (!isset(Module::$loaded[$folder])) {
                $files = @scandir($folderAbs);
                if (!$files) return;
                // Los dos primeros elementos son ../ y ./
                array_splice($files, 0, 2);

                foreach ($files as $file):
                    $ext = pathinfo($folderAbs.'/'.$file, PATHINFO_EXTENSION);
                    switch ($ext) {
                        case 'css':
                            $md5File = md5_file($folderAbs.'/'.$file);

                            if (!in_array($md5File,Module::$cssHash)) {
                                Module::$css[] = $auxPath ? 'https://'.$_SERVER["HTTP_HOST"].'/'.$auxPath.'/'.$folder.'/'.$file : '/modulos/'.$folder.'/'.$file;
                                Module::$cssHash[] = $md5File;
                            }
                            break;
                        case 'js':
                            $md5File = md5_file($folderAbs.'/'.$file);
                            if (!in_array($md5File,Module::$jsHash)) {
                                Module::$js[] = $auxPath ? 'https://'.$_SERVER["HTTP_HOST"].'/'.$auxPath.'/'.$folder.'/'.$file : '/modulos/'.$folder.'/'.$file;
                                Module::$jsHash[] = $md5File;
                            }
                            break;
                        default:
                            break;
                    }
                endforeach;
                Module::$loaded[$folder] = true;
            }
        }
        public static function addRemote($url){
            if (@$_REQUEST["viewAMP"]) return;
            $ext = pathinfo($url, PATHINFO_EXTENSION);
            $md5File = md5($url);
            switch ($ext) {
                case 'css':
                    if (!in_array($md5File,Module::$cssHash)) {
                        Module::$css[] = $url;
                        Module::$cssHash[] = $md5File;
                    }
                    break;
                case 'js':
                    if (!in_array($md5File,Module::$jsHash)) {
                        Module::$js[] = $url;
                        Module::$jsHash[] = $md5File;
                    }
                    break;
                default:
                    break;
            }
        }
    }
}

function extraeDatosModuloPorId($tableName,$id = null,$index = null,$limit = 1){
    $apartados = @CmsApi::get($tableName,"builder like '%".$id."%'",null,$limit);
    if (!@$apartados) return ["error" => "No encuentro el apartado"];


    $result = [];

    foreach($apartados as $apartado){
        $modulos = json_decode($apartado["builder"],true);
        $modulos = array_values(array_filter($modulos,function($rec) use($id){ return strpos($rec["modulo"],$id) !== false; }));

        if ($index){
            $modulos = [@$modulos[$index] ?: ["error" => "No encuentro el index"]];
        }

        foreach($modulos as $cont => $modulo){
            $variables = [];
            $configModulo = modulo_builder_config($modulo["modulo"]);
            dame_variables_config($configModulo,$modulo["config-vars"],$variables,$modulo);
            $result[] = [
                "config" => $configModulo,
                "variables" => $variables,
                "thisrecord" => $apartado
            ];
        }
    }
    return $result;
}

function filtraModulo($jsonConfig,$modulo,$index){
    global $otrosModulos;
    $jsonConfig2 = [];
    if (!@$jsonConfig) return [];

    if ($index > -1 && @$jsonConfig[$index] && $jsonConfig[$index]["modulo"] == $modulo) return [$jsonConfig[$index]];

    foreach($jsonConfig as $key => $value){
        ModuleBuilder::loadFiles($value["modulo"], []);
        if ($value["modulo"] == $modulo) {
            $jsonConfig2[] = $value;
            break;
        }
    }

    if (!$jsonConfig2) die("Error");
    return $jsonConfig2;
}

function checkModulePluginsNeeded($configModule = null){

    $result = ["result" => false];
    if (@$configModule["requiredPlugins"]){
        $sepPlugins  = array_filter(explode(",",$configModule["requiredPlugins"]));
        foreach($sepPlugins as $plugin){
            if (!file_exists(__DIR__."/../".$plugin)){
                $result["result"] = true;
                $result["data"][] = $plugin;
            }    
        }
        
    }
    return $result;

}

function areFilledVariables($variables){
    $areFilled = false;
    foreach($variables as $var => $value){
        if (is_array($value)){
            $areFilled = areFilledVariables($value);
            if ($areFilled) return true;
        }else{
            if ($value !== null && $value !== "" && !is_array($value)){ 
                $areFilled = true;
                return true;
            }
        }
    }
    if (!$areFilled) return false;
    return true;
}

function dame_variables_config($configModulo,$configVars,&$variables = [],$section = null){
    global $cacheQuery,$TABLE_PREFIX,$apartado;

    foreach($configModulo["vars"] as $var => $content){
        if (isset($content["vars"])) {
            $variables[$var] = [];
            if (isset($configVars[$var]) && is_array($configVars[$var])) {
                dame_variables_config($content,$configVars[$var],$variables[$var]);
            }else if (isset($configVars[$var]) && is_string($configVars[$var])){
                $apartado["auto"] = $configVars[$var];
                $variables[$var] = dame_variables_automaticas($configModulo["vars"][$var],$configVars[$var],$configVars);
            }
        }else{
            if (isset($configVars[0][$var])){
                foreach($configVars as $cont => $configVar){
                    dame_variables_config($configModulo,$configVar,$variables[$cont]);
                }
            }else if (isset($configVars[$var]["tableName"])){
                $configuracion = @$configVars[$var];
                if (@$content["controller"]){
                    $variables[$var] = modulo_builder_controller($section["modulo"],$content["controller"],array("var" => $configuracion,"field" => $content["relations"]["builder_custom"]));
                    continue;
                }
                if (is_array($content["relations"][$configuracion["tableName"]])){
                    die("Error: Las relaciones no pueden ser arrays en este contexto. Modulo: ".json_encode($configModulo["label"]).". El siguiente valor debería ser string : ".json_encode($content["relations"][$configuracion["tableName"]]));
                }
                if (strstr((string)$content["relations"][$configuracion["tableName"]],'}}')){
                    // QUERY
                    $query = str_replace("{{","",str_replace("}}","",$content["relations"][$configuracion["tableName"]]));
                    $query = str_replace("{TABLE_PREFIX}",$TABLE_PREFIX,$query);

                    preg_match("/\{(.*)\}/",$query,$matches);
                    $subResult = "";
                    if (@$matches){
                        $result = @getQuery($configuracion["tableName"],$configuracion["recordNum"])[strtolower($matches[1])];
                        $query = str_replace($matches[0],$result,$query);
                        if (@$result){
                            $subResult = mysql_query_fetch_all_assoc($query." LIMIT 1")[0];
                            $subResult = @array_values($subResult)[0];
                        }
                    }
                    $variables[$var] = $subResult;

                }else if (strstr((string)$content["relations"][$configuracion["tableName"]],'}')){
                    // SET VALUE
                    $miValue = str_replace("{","",str_replace("}","",$content["relations"][$configuracion["tableName"]]));
                    $variables[$var] = @$miValue ? t_var($miValue) : "";
                }else{
                    // SET BY BBDD FIELDNAME
                    if (@$configuracion["recordNum"]){
                        $variables[$var] = @getQuery($configuracion["tableName"],$configuracion["recordNum"])[$content["relations"][$configuracion["tableName"]]];

                        // TRAUDCCION
                        if (!is_array($variables[$var])){
                            $recordAux = ["num" => $configuracion["recordNum"],"tableName" => $configuracion["tableName"]];

                            if($configuracion['tableName'] === 'builder_custom') {
                                $recordAux[$var] = $variables[$var];
                                $variables[$var] = t($recordAux,$var);
                            } else {
                                $recordAux[$content["relations"][$configuracion["tableName"]]] = $variables[$var];
                                $variables[$var] = t($recordAux,$content["relations"][$configuracion["tableName"]]);
                            }

                        }else if (isset($variables[$var]["info1"])){
                            $variables[$var]["info1"] = t($variables[$var],"info1");
                            $variables[$var]["info2"] = t($variables[$var],"info2");
                            $variables[$var]["info3"] = t($variables[$var],"info3");
                            $variables[$var]["info4"] = t($variables[$var],"info4");
                            $variables[$var]["info5"] = t($variables[$var],"info5");
                        } else {

                            $variables = array_map(function($r) {
                                return t_recursivo($r, null);
                            }, $variables);

                            // echo "<pre style='display: none;'>"; var_dump(json_encode([$variables[$var],$configuracion["tableName"],[
                            //  'configModulo' => $configModulo,
                            //  'configVars' => $configVars,
                            //  'variables' => $variables,
                            //  'section' => $section
                            // ]])); echo "</pre>";
                        }

                    }else if (@$configuracion["value"]){
                        $variables[$var] = @$configuracion["value"];
                    }
                    // PARSEO DE CAMPOS
                    switch($content["type"]){
                        case "link":
                            $variables[$var] = parseLink($variables[$var], $var, $configVars[$var]);
                            break;
                        case "upload":
                            if (@$content["infoLabels"]){
                                foreach($content["infoLabels"] as $cont => $infoLabel){
                                    if (strpos("enlace",strtolower($infoLabel)) !== false){
                                        $infoNum = $cont+1;
                                        foreach($variables[$var] as $cont2 => $image){
                                            $variables[$var][$cont2]["info".$infoNum] = parseLink("2|".$variables[$var][$cont2]["info".$infoNum]);
                                        }
                                    }
                                }

                            }
                            break;
                    }
                }
            }
        }
    }
    return $variables;
}

function parseLink($string, $label = null, $configuracion = []){
    if (!@$string) return '';
    $sep = array_filter(explode("|",$string));
    if (@$sep[1]){
        if ($sep[0]=="2" || $sep[0]=="3"){
            $sep2 = array_filter(explode(",",$sep[1]));
            if (@$sep2[1]){
                $link = @getQuery($sep2[0],$sep2[1])["enlace"];

                $recordAux = ["num" => $sep2[1],"tableName" => $sep2[0],"enlace" => $link];

                $link = @$_REQUEST["idioma"] ? t($recordAux,"enlace") : $link;

                return $link;
            }else{
                return @$sep2[0];
            }
        }else{
            return $sep[1];
        }
    }else if (@$sep[0]){
        $recordAux = ["num" => @$configuracion["recordNum"], "tableName" => @$configuracion["tableName"], $label => $sep[0]];
        return @$_REQUEST["idioma"] ? t($recordAux, @$label) : $sep[0];
    }
    return $string;
}
function dame_variables_automaticas($configModuloVar,$configVarsVar,$configVars){
    global $TABLE_PREFIX;
    if (@$configModuloVar[0]) $configModuloVar = $configModuloVar[0];
    if (!$configModuloVar["auto"][$configVarsVar]) die("<pre>ERROR en Selección automática</pre>");
    if (!is_array($configModuloVar["auto"][$configVarsVar]["query"])) die("<pre>ERROR la búsqueda automatica no es un objeto</pre>");

    $query = $configModuloVar["auto"][$configVarsVar]["query"];
    $result = @dame_registros($query["tableName"],@$query["where"] ?: "",@$query["order"] ?: "",@$query["limit"] ?: @$configModuloVar["maxLimit"] ?: "");

    $result = array_map(function($r) {
        return t_recursivo($r, null);
    }, $result);

    $query["BD_relations"] = [];
    $query["relationsStrings"] = [];
    $query["relationsQuery"] = [];

    foreach($configModuloVar["vars"] as $key => $value){
        if (strpos($value["relations"][$query["tableName"]],"{")===false){
            $query["BD_relations"][$key] = $value["relations"][$query["tableName"]];
        }else{
            if (strpos($value["relations"][$query["tableName"]],"{{")===false){
                $miValue = str_replace("{","",str_replace("}","",$value["relations"][$query["tableName"]]));
                $query["relationsStrings"][$key] = @$miValue ? t_var($miValue) : "";
            }else{
                // QUERY
                $queryAux = str_replace("{{","",str_replace("}}","",$value["relations"][$query["tableName"]]));
                $queryAux = str_replace("{TABLE_PREFIX}",$TABLE_PREFIX,$queryAux);

                preg_match("/\{(.*)\}/",$queryAux,$matches);

                if (@$matches){
                    $query["relationsQuery"][$key] = ["query" => $queryAux,"field" => $matches[1]];
                }
            }
        }
    }


    $result2 = $result;
    foreach($result as $cont => $record):
        foreach($query["BD_relations"] as $resultKey => $resultValue){
            $result2[$cont][$resultKey] = t_recursivo($result[$cont][$resultValue]);
        }
        foreach($query["relationsStrings"] as $resultKey => $resultValue){
            $result2[$cont][$resultKey] = t_recursivo($resultValue);
        }
        foreach($query["relationsQuery"] as $resultKey => $resultValue){

            $queryAux2 = str_replace("{".$resultValue["field"]."}",$result[$cont][strtolower($resultValue["field"])],$resultValue["query"]);
            $subResult = @mysql_query_fetch_all_assoc($queryAux2." LIMIT 1")[0];
            $subResult = @array_values($subResult)[0];
            $result2[$cont][$resultKey] = $subResult;
        }
    endforeach;

    return $result2;
}
function getQuery($tableName,$recordNum){
    global $cacheQuery;

    $hashQuery = md5($tableName.$recordNum);
    if (!isset($cacheQuery[$hashQuery])){
        $cacheQuery[$hashQuery] = @dame_registros($tableName,"num=".intval($recordNum),"num desc",1)[0];
    }
    return $cacheQuery[$hashQuery];
}

function modulo_builder_config($p,$ruta = "."){
    ob_start();
    require(BASE_PATH.$ruta.RUTA_PLANTILLA."/modulos/".$p.'/builder.json');
    return json_decode(ob_get_clean(),true);
}
function modulo_builder_controller($p,$c,$d=array()){
    extract($d);
    ob_start();
    require(".".RUTA_PLANTILLA."/modulos/".$p.'/'.$c);
    $resultado = ob_get_clean();
    return $resultado;
}

function isJson($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

function t_recursivo($record, $idx=null) {
    global $allowedTranslateFields;

    $it = $idx ? $record[$idx] : $record;
    if (is_array($it)) {
        foreach ($it as $key => $value) {
            if (is_array($it[$key])) {

                $it[$key] = t_recursivo($it[$key], null);

            }
            else {
                if (isset($allowedTranslateFields[$key])) {
                    $it[$key] = t($it, $key);
                }
            }
        }
        if ($idx) {
            $record[$idx] = $it;
        }
        else {
            $record = $it;
        }
    }
    else {
        if ($idx && isset($allowedTranslateFields[$idx])) {
            $record[$idx] = t($record, $idx);
        }
    }

    return $record;
}

function renderiza_modulo($modulo,$moduloInfo = null,$apartado = null,$index = -1,$extraData = [],$options = []){
    global $menu,$section;
    if (!@$menu) $menu = "apartados";

    require_once __DIR__."/../../../../sesion.php";
    require_once __DIR__."/../../../../funciones.php";
    require_once __DIR__."/replace_code.php";
    require_once __DIR__."/builder_functions.php";
    if (file_exists(__DIR__."/../cms_api/v3/CmsApi.class.php")) require_once __DIR__."/../cms_api/v3/CmsApi.class.php";

    $resultado = "";

    $jsonConfig = json_decode($apartado["builder"],true);
    $cacheQuery = [];
    $result = "";

    $jsonConfig = filtraModulo($jsonConfig,$modulo,$index);
    if (isset($_REQUEST["viewAMP"])){
        /*$libraries = "";
        $layout = json_decode(file_get_contents(__DIR__."/layout.json"),true);
        if (@$layout["librariesJSONAMP"]){
            $libraries = '<script async="" src="https://cdn.ampproject.org/v0.js"></script>';
            foreach($layout["librariesJSONAMP"] as $lib){
                if (substr(@$lib["url"],0,1) == "/") $lib["url"] = "https://".$_SERVER["HTTP_HOST"]."/template/estandar".$lib["url"];
                if (strpos(@$lib["url"],".js") !== false) {
                    $libraries.="<script src='".@$lib["url"]."' ".@$lib["attr"]."></script>";
                }else{
                    $libraries.="<link rel='stylesheet' href='".@$lib["url"]."'>";
                }
            }
        }

        $resultado.='
        <!DOCTYPE>
        <html amp>

        <head>
            '.$libraries.'
        </head>

        <body>
        ';*/
    }else{

        $libraries = [];
        $layout = json_decode(file_get_contents(__DIR__."/layout.json"),true);
        $librariesKeys = ["librariesJSONt","librariesJSONb"];
        $ruta_plantilla = "template/estandar";
        foreach($librariesKeys as $keyLib){
            if (@$layout[$keyLib]){
                ob_start();
                $resultLibrary = "";
                foreach($layout[$keyLib] as $library){
                    $library["url"] = str_replace("/".$ruta_plantilla,"",$library["url"]);
                    $async = @$library["attr"] ? strpos(@$library["attr"],"async") !== false ? true : false : false;
                    if (strpos(strtolower($library["url"]),".css")!==false) {
                        Resource::link($library["url"], $async);
                    }else if (strpos(strtolower($library["url"]),"/css")!==false) {
                        Resource::link($library["url"], $async);
                    }else if (!@$_REQUEST['viewAMP']){
                        echo "\t<script src='".h($library["url"])."' ".@$library['attr']."></script>\n";
                    }
                }
                $resultLibrary = ob_get_clean();
                $libraries[$keyLib] = str_replace("preload","stylesheet",$resultLibrary);

            }
        }

        if(!@$options["disallow_scripts"]) {
            $resultado.='
            <html>

            <head>
                '.@$libraries[$librariesKeys[0]].'
                <script>
                    var hooksToken = "'.sha1(session_id().$_SERVER["HTTP_HOST"]).'";
                </script>
                <script src="https://cms.cocosolution.com/lib/plugins/builder_saas/js/cmsApiInit.js"></script>
                <style>html,body{height:auto} body{opacity:0;transition:all .3s ease-in-out;}</style>
            </head>

            <body class="w-full h-full flex items-center flex-col">
                <section class="w-full my-auto">
            ';
        }
    }
    if (!@$jsonConfig){
        echo '
        <div class="w-full h-full flex items-center justify-center"><p class="max-w-xl mx-auto p-10 bg-white rounded-lg shadow-xl text-gray-600">Este módulo no dispone de previsualización porque no se encuentra insertado directamente en la web pero es llamado desde otros módulos o secciones generales. <br><br>Igualmente puedes ver el código en la pestaña "Código" para visualizar cómo está desarrollado.</p></div>
        ';
    }
    foreach($jsonConfig as $section):
        $data = [];
        $configModulo = modulo_builder_config($section["modulo"],__DIR__."/../../../../");
        if (!@$configModulo) continue;
        if (@$section["oculto"]) continue;
        $variables = [];
        if (@$section["referenciada"]){
            if (strpos($section["referenciada"],"|") !== false){
                $menu = explode("|",$section["referenciada"])[0];
                $section["referenciada"] = explode("|",$section["referenciada"])[1];
            }
            $apartadoReferencia = @dame_registros($menu,"num=".intval(@$section["referenciada"]),"num desc",1)[0];
            if (@$apartadoReferencia["builder"]){
                $jsonConfigAux = json_decode($apartadoReferencia["builder"],true);
                foreach($jsonConfigAux as $contAux => $sectionAux){
                    if ($sectionAux["modulo"] == $section["modulo"]) $section["config-vars"] = $sectionAux["config-vars"];
                }
            }
        }

        dame_variables_config($configModulo,$section["config-vars"],$variables,$section);

        if ($extraData){
            foreach($extraData as $keyExtra => $valueExtra){
                if (!isset($variables[$keyExtra])) $variables[$keyExtra] = $valueExtra;
            }
        }

        if (filemtime(__DIR__."/../../../../lib/Module.php")<1582840163){
            file_put_contents(__DIR__."/../../../../lib/Module.php",str_replace("private ","public ",file_get_contents(__DIR__."/../../../../lib/Module.php")));
        }
        if (filemtime(__DIR__."/../../../../lib/Module.php")<1658870903){
            file_put_contents(__DIR__."/../../../../lib/Module.php",str_replace("links = ''; ","links = ''; if (defined('USE_MIN_TAILWIND') && USE_MIN_TAILWIND) return '';",file_get_contents(__DIR__."/../../../../lib/Module.php")));
        }

//      if (@$section["section_id"] ){
//          $colorsKey = md5('custom-colors|'.$section["modulo"]."|".intval(@$_REQUEST["num"])."|".@$section["section_id"]."/");
//          $colorsRecord = @mysql_query_fetch_all_assoc("select plugin,config FROM aux_plg_config where plugin = '".$colorsKey."' order by num desc limit 1")[0];
//
//          if (@$colorsRecord) $variables["customColors"] = json_decode($colorsRecord["config"],true);
//      }

        Module::$path = __DIR__."/../../../../template/estandar/modulos/";
        $resulta = BuilderModule($section["modulo"],$variables);

        //$pattern = "/data-src=\"/i";
        //$replacement = 'src="';
        //$resulta = preg_replace($pattern, $replacement, $resulta);

        //Cambio realizado por matito, si falla es culpa de tana
        // $pattern = "/href=\"(.*?)\"/i";
        // $replacement = 'href="javascript:void(0);"';
        // $resultado.=preg_replace($pattern, $replacement, $resulta);
        if(!@$options["allow_links"]){
            $pattern = "/href=\"(.*?)\"/i";
            $replacement = 'href="javascript:void(0);"';
            $resultado.=preg_replace($pattern, $replacement, $resulta);
        } else {
            $resultado.=$resulta;
        }
        //Fin cambio

    endforeach;
    $resultado.=$result;


    if(!@$options["disallow_scripts"]) {
        $resultado.= '
            '.@$libraries[$librariesKeys[1]].'
            <!-- LINKS -->'.Module::links().'<!-- LINKS -->
            <!-- SCRIPTS -->'.Module::scripts().'<!-- SCRIPTS -->
            <link rel="stylesheet" href="/custom-builder-style.css?timestamp='.filemtime(__DIR__."/layout.json").'">
            <script src="/custom-builder-javascript.js?timestamp='.filemtime(__DIR__."/layout.json").'"></script>
            </section>
            <style>body{opacity:1;}</style>
            <script></script>
        </body>

        </html>
        ';
    }



    return $resultado;
}

function __get_apartado_from_all_tablenames($modulo,$num = '',$menu = null){
    global $SETTINGS,$TABLE_PREFIX;

    $sql = "
        SELECT DISTINCT TABLE_NAME,COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE COLUMN_NAME IN ('builder')
            AND TABLE_SCHEMA='".$SETTINGS["mysql"]["database"]."'
        ";

    $result = mysql_query($sql) or die(mysql_error());

    while($record = mysql_fetch_assoc($result)){
        $menu = str_replace($TABLE_PREFIX,"",$record["TABLE_NAME"]);
        if (!$menu) continue;

        $apartado = CocoDB::get($menu,'builder LIKE \'%"'.$modulo.'"%\''.$num,"num ASC",1); $apartado=@$apartado[0];
        if (!@$apartado){
            $apartado = CocoDB::get($menu,'builder LIKE \'%"'.$modulo.'"%\'',"num ASC",1); $apartado=@$apartado[0];
        }
        if (@$apartado) return $apartado;
    }
}

if (class_exists("CocoDB") && property_exists("CocoDB","trackData") && isset($_REQUEST["acaiTrackData"])) CocoDB::setTrackData(true);
