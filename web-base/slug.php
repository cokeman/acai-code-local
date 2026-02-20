<?
$sep = explode("?enlace=", @$_SERVER["REQUEST_URI"]);
if (@$sep[1]){
    header("HTTP/1.1 301 Moved Permanently"); 
    header("Location: http://".$_SERVER["HTTP_HOST"].$sep[0].""); 
}
require_once "lib/variables.php";

$sufijo = ".html";
if (@$_REQUEST["tipo"]=="barra") $sufijo="";

// SISTEMA DE CACHE DE SLUGS PARA EVITAR CONSULTAS A LA BASE DE DATOS EN CADA PETICIÓN, SE ALMACENAN EN REDIS SI ESTÁ DISPONIBLE O EN MEMORIA SI NO

function slugCacheRedisKey($cacheKey){
    return "SLUG_CACHE_".$_SERVER["HTTP_HOST"]."_".$cacheKey;
}
function slugRedisAvailable(){
    static $available = null;
    if (!is_null($available)) return $available;
    if (!class_exists("CocoDB")) return false;
    try{
        CocoDB::initCache();
        if (!@CocoDB::$redis) {
            $available = false;
            return $available;
        }
        if (!@CocoDB::$redis->isConnected()) {
            $available = false;
            return $available;
        }
        $available = true;
        return $available;
    }catch(Exception $e){
        $available = false;
        return $available;
    }
}
function slugCacheRead($cacheKey){
    global $slugRedisAvailable;
    if (!$slugRedisAvailable) return null;
    $redisKey = slugCacheRedisKey($cacheKey);
    $rawRedis = CocoDB::cacheGet($redisKey);
    if (!$rawRedis) return null;
    $dataRedis = @json_decode($rawRedis,true);
    return is_array($dataRedis) ? $dataRedis : null;
}
function slugCacheWrite($cacheKey,$data,$ttl = 0){
    global $slugRedisAvailable;
    if (!$slugRedisAvailable) return;
    $redisKey = slugCacheRedisKey($cacheKey);
    $expire = intval($ttl) > 0 ? intval($ttl) : null;
    CocoDB::cacheSet($redisKey,json_encode($data),$expire);
}
function slugCacheDelete($cacheKey){
    global $slugRedisAvailable;
    if (!$slugRedisAvailable) return;
    $redisKey = slugCacheRedisKey($cacheKey);
    CocoDB::cacheSet($redisKey,"",1);
}
function slugRouteCacheKey($enlace){
    return "slug-route-".md5($_SERVER["HTTP_HOST"]."|".$enlace);
}
function slugAliasCacheKey($aliasPath){
    return "slug-alias-".md5($_SERVER["HTTP_HOST"]."|".$aliasPath);
}
function slugTranslationCacheKey($enlaceAux,$sufijo){
    return "slug-translation-".md5($_SERVER["HTTP_HOST"]."|".$enlaceAux."|".$sufijo);
}
function slugGetCachedRoute($enlace){
    $data = slugCacheRead(slugRouteCacheKey($enlace));
    if (!is_array($data)) return null;
    if (!@$data["expiresAt"] || intval($data["expiresAt"]) < time()) {
        slugCacheDelete(slugRouteCacheKey($enlace));
        return null;
    }
    return $data;
}
function slugSetCachedRoute($enlace,$data,$ttl = 120){
    $ttl = max(5,intval($ttl));
    $data["expiresAt"] = time() + $ttl;
    slugCacheWrite(slugRouteCacheKey($enlace),$data,$ttl);
}
function slugGetCachedAlias($aliasPath){
    $data = slugCacheRead(slugAliasCacheKey($aliasPath));
    if (!is_array($data)) return null;
    if (!@$data["expiresAt"] || intval($data["expiresAt"]) < time()) {
        slugCacheDelete(slugAliasCacheKey($aliasPath));
        return null;
    }
    return $data;
}
function slugSetCachedAlias($aliasPath,$data,$ttl = 120){
    $ttl = max(5,intval($ttl));
    $data["expiresAt"] = time() + $ttl;
    slugCacheWrite(slugAliasCacheKey($aliasPath),$data,$ttl);
}
function slugGetCachedTranslation($enlaceAux,$sufijo){
    $data = slugCacheRead(slugTranslationCacheKey($enlaceAux,$sufijo));
    if (!is_array($data)) return null;
    if (!@$data["expiresAt"] || intval($data["expiresAt"]) < time()) {
        slugCacheDelete(slugTranslationCacheKey($enlaceAux,$sufijo));
        return null;
    }
    return $data;
}
function slugSetCachedTranslation($enlaceAux,$sufijo,$data,$ttl = 120){
    $ttl = max(5,intval($ttl));
    $data["expiresAt"] = time() + $ttl;
    slugCacheWrite(slugTranslationCacheKey($enlaceAux,$sufijo),$data,$ttl);
}
function slugGetTablesWithEnlace($database){
    $cacheKey = "slug-tables-enlace-".md5($_SERVER["HTTP_HOST"]."|".$database);
    $cacheData = slugCacheRead($cacheKey);
    if (is_array($cacheData) && intval(@$cacheData["expiresAt"]) >= time() && is_array(@$cacheData["data"])) {
        return $cacheData["data"];
    }

    $sql = "
        SELECT DISTINCT TABLE_NAME,COLUMN_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE COLUMN_NAME IN ('enlace')
            AND TABLE_SCHEMA='".mysql_real_escape_string($database)."'
        ";
    $result = mysql_query($sql) or die(mysql_error());
    $tables = array();
    while($row = mysql_fetch_assoc($result)){
        $tables[] = $row;
    }

    slugCacheWrite($cacheKey,array(
        "expiresAt" => time() + 600,
        "data" => $tables
    ),600);

    return $tables;
}

// HASTA AQUI

$padre = 0;
$padreContenidos = false;
$controlador = "";
$tabla = null;
$registro = null;

$useInformationSchemaCache = defined("USE_INFORMATION_SCHEMA_CACHE") && USE_INFORMATION_SCHEMA_CACHE && class_exists("CocoDB");
$slugRedisAvailable = $useInformationSchemaCache ? slugRedisAvailable() : false;
$slugCacheEnabled = $useInformationSchemaCache && $slugRedisAvailable;

/********/
// GESTION DEL ALIAS
/********/
$aliasPath = "/".@$_REQUEST["enlace"].$sufijo;
$aliasData = $slugCacheEnabled ? slugGetCachedAlias($aliasPath) : null;
if (!is_array($aliasData)){
    $aliasPathEscaped = mysql_real_escape_string($aliasPath);
    $existe_alias = mysql_fetch_assoc(mysql_query("SELECT url_destino FROM cms_alias_urls WHERE url_alias = '".$aliasPathEscaped."' LIMIT 1"));
    if (@$existe_alias && isset($existe_alias["url_destino"])) {
        $aliasData = array(
            "found" => true,
            "url_destino" => $existe_alias["url_destino"]
        );
        if ($slugCacheEnabled) slugSetCachedAlias($aliasPath,$aliasData,300);
    }else{
        $aliasData = array("found" => false);
        if ($slugCacheEnabled) slugSetCachedAlias($aliasPath,$aliasData,60);
    }
}

if (@$aliasData["found"] && @$_REQUEST["enlace"]) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    header("HTTP/1.1 301 Moved Permanently");
    if (strpos(@$aliasData["url_destino"],"http") > -1){
        header("Location: ".$aliasData["url_destino"]."");
    }else{
        header("Location: ".$protocol.$_SERVER["HTTP_HOST"].$aliasData["url_destino"]."");
    }
    die();
}

$_REQUEST["enlace"] = str_replace("amp/", "", @$_REQUEST["enlace"]);
$enlace_aux = @$_REQUEST["enlace"];
if (@$_REQUEST["idioma"]) {
	$enlace_aux = $_REQUEST["idioma"]."/".$enlace_aux;
	$enlace_aux = str_replace($_REQUEST["idioma"]."/".$_REQUEST["idioma"]."/", $_REQUEST["idioma"]."/", $enlace_aux);
}

$translationData = $slugCacheEnabled ? slugGetCachedTranslation($enlace_aux,$sufijo) : null;
if (is_array($translationData) && isset($translationData["found"])) {
    if (@$translationData["found"]){
        $_REQUEST["enlace"] = @$translationData["enlace"];
        $_REQUEST["idioma"] = @$translationData["idioma"];
    }else{
        $_REQUEST["idioma"]="";
    }
}else{
    $fieldValue = @$enlace_aux.$sufijo;
    $fieldValueB64 = base64_encode($fieldValue);
    $fieldValueB64Slash = base64_encode("/".$fieldValue);
    $result = mysql_query("SELECT prefix,tableName,recordNum,fieldName FROM cms_traducciones
        WHERE fieldValue IN ('".mysql_real_escape_string($fieldValue)."','".mysql_real_escape_string($fieldValueB64)."','".mysql_real_escape_string($fieldValueB64Slash)."')
        AND fieldName = 'enlace'
        AND tableName != 'builder_custom'
        LIMIT 1");

    $foundTranslation = false;
    if ($result){
        $record = mysql_fetch_assoc($result);
        if ($record){
            $tableName = "cms_".preg_replace("/[^a-zA-Z0-9_]/","",@$record["tableName"]);
            $fieldName = preg_replace("/[^a-zA-Z0-9_]/","",@$record["fieldName"]);
            $recordNum = intval(@$record["recordNum"]);
            if ($tableName && $fieldName && $recordNum > 0){
                $result2 = mysql_query("SELECT ".$fieldName." FROM ".$tableName." WHERE num=".$recordNum." LIMIT 1");
                if ($result2){
                    $record2 = mysql_fetch_assoc($result2);
                    if (is_array($record2) && isset($record2[$fieldName])) {
                        $_REQUEST["enlace"] = (@$sufijo=="/") ? substr($record2[$fieldName],1,strlen($record2[$fieldName])-2) : substr(str_replace($sufijo,"",$record2[$fieldName]),1,10000);
                        $_REQUEST["idioma"] = @$record["prefix"];
                        $foundTranslation = true;
                    }
                }
            }
        }
    }

    if ($foundTranslation){
        if ($slugCacheEnabled){
            slugSetCachedTranslation($enlace_aux,$sufijo,array(
                "found" => true,
                "enlace" => @$_REQUEST["enlace"],
                "idioma" => @$_REQUEST["idioma"]
            ),300);
        }
    }else{
        $_REQUEST["idioma"]="";
        if ($slugCacheEnabled){
            slugSetCachedTranslation($enlace_aux,$sufijo,array(
                "found" => false
            ),60);
        }
    }
}

$enlace = "/".@$_REQUEST["enlace"].$sufijo;
$enlaceEscaped = mysql_real_escape_string($enlace);
$routeFromCache = $slugCacheEnabled ? slugGetCachedRoute($enlace) : null;
$routeResolved = false;

if (is_array($routeFromCache) && isset($routeFromCache["found"])) {
    if (@$routeFromCache["found"]) {
        $tablaCache = @$routeFromCache["tabla"];
        $registroCache = intval(@$routeFromCache["registro"]);
        if ($tablaCache && $registroCache) {
            $encontrado = mysql_fetch_assoc(mysql_query("SELECT * FROM ".$tablaCache." WHERE num=".$registroCache." AND enlace='".$enlaceEscaped."' LIMIT 1"));
            if (@$encontrado){
                $tabla = $tablaCache;
                $registro = intval($encontrado["num"]);
                $padre = intval(@$encontrado["parentNum"]);
                if (isset($encontrado["layout"])&&$padre) $padreContenidos = mysql_fetch_assoc(mysql_query("select * from ".$tabla." where num=".$padre." AND layout=1 LIMIT 1")) ? true : false;
                $controlador = @$encontrado["controlador"];
                $routeResolved = true;
            }else{
                slugCacheDelete(slugRouteCacheKey($enlace));
            }
        }
    }else{
        $routeResolved = true;
    }
}

if (!$routeResolved){
    if ($slugCacheEnabled){
        $resultados = slugGetTablesWithEnlace($SETTINGS["mysql"]["database"]);
    }else{
        $sql = "
            SELECT DISTINCT TABLE_NAME,COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE COLUMN_NAME IN ('enlace')
                AND TABLE_SCHEMA='".mysql_real_escape_string($SETTINGS["mysql"]["database"])."'
            ";
        $result = mysql_query($sql) or die(mysql_error());
        $resultados = array();
        while($row = mysql_fetch_assoc($result)){
            $resultados[] = $row;
        }
    }
    foreach($resultados as $record){
        $tableName = isset($record["TABLE_NAME"]) ? $record["TABLE_NAME"] : @$record["table_name"];
        $columnName = isset($record["COLUMN_NAME"]) ? $record["COLUMN_NAME"] : @$record["column_name"];
        if (!$tableName || !$columnName) continue;

        $encontrado = mysql_fetch_assoc(mysql_query("SELECT * FROM ".$tableName." WHERE ".$columnName."='".$enlaceEscaped."' LIMIT 1"));

        if (@$encontrado){
            $tabla = $tableName;
            $registro = $encontrado["num"];
            $padre = intval(@$encontrado["parentNum"]);
            if (isset($encontrado["layout"])&&$padre) $padreContenidos = mysql_fetch_assoc(mysql_query("select * from ".$tableName." where num=".$padre." AND layout=1 LIMIT 1")) ? true : false;

            $controlador = @$encontrado["controlador"];
            if ($columnName!="enlace") {
                $controlador="reserva.php";
            }
        }
    }

    if ($slugCacheEnabled){
        if (@$tabla && @$registro){
            slugSetCachedRoute($enlace,array(
                "found" => true,
                "tabla" => $tabla,
                "registro" => intval($registro)
            ),300);
        }else{
            slugSetCachedRoute($enlace,array(
                "found" => false
            ),60);
        }
    }
}

/*var_dump(CocoDB::$force_redis_html);
var_dump($_SERVER["REQUEST_URI"]);*/

if (class_exists("CocoDB") && isset(CocoDB::$force_redis_html) && CocoDB::$force_redis_html){
    $hash = CocoDB::cacheGenerateHash("HTML_".md5(json_encode($_REQUEST)));
    $data2 = CocoDB::cacheGet($hash);

    if (@$data2) {        
        echo CocoDB::replaceHooksToken($data2);
        die();
    }
}

$resultInclude = null;
ob_start();

if (!@$tabla){
    $num=0;
    addPlugins("slug", $pluginData);
    include("apartados.php");
}else{
    $schema = loadSchema($tabla);
    
    if (@$schema["controller"]!=""&&@$schema["controller"]!="apartados.php"){
        if (@$registro)  {
            $num=$registro;
            if (@$num && !@$_REQUEST["num"]) $_REQUEST["num"] = $num;
            $pluginData = array("num" => $registro, "tabla" => $tabla, "schema" => $schema);
            addPlugins("slug", $pluginData);
        }
        if (@$controlador){
            include(dirname(__FILE__)."/".$controlador);
        }else{
            include(dirname(__FILE__)."/".$schema["controller"]);
        }
    }else{        
        $num=$registro;
        if (@$num && !@$_REQUEST["num"]) $_REQUEST["num"] = $num;
        $pluginData = array("num" => $registro, "tabla" => $tabla, "schema" => $schema);
        addPlugins("slug", $pluginData);
        
        if (!@$padre){
            if (@$controlador){
                $num=(@$_REQUEST["num"]) ? $_REQUEST["num"] : @$registro;
                if (@$num && !@$_REQUEST["num"]) $_REQUEST["num"] = $num;
                include(dirname(__FILE__)."/".$controlador);
            }else{
                include(dirname(__FILE__)."/apartados.php");
            }
        }else{
            $familia = $padre;
            if (@$controlador){
                $num=(@$_REQUEST["num"]) ? $_REQUEST["num"] : @$registro;
                if (@$num && !@$_REQUEST["num"]) $_REQUEST["num"] = $num;
                include($controlador);
            }else{
                if ($padreContenidos){
                    include(dirname(__FILE__)."/contenidos.php");
                }else{
                    include(dirname(__FILE__)."/apartados.php");
                }
            }
        }
    }
}
$resultInclude = ob_get_clean();

addPlugins("pre_render",$resultInclude);

if (class_exists("CocoDB") && isset(CocoDB::$force_redis_html) && CocoDB::$force_redis_html && @$hash){
    CocoDB::cacheSet(@$hash,$resultInclude);
}
echo $resultInclude;

?>
