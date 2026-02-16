<?
$sep = explode("?enlace=", @$_SERVER["REQUEST_URI"]);
if (@$sep[1]){
    header("HTTP/1.1 301 Moved Permanently"); 
    header("Location: http://".$_SERVER["HTTP_HOST"].$sep[0].""); 
}
require_once "lib/variables.php";

$sufijo = ".html";
if (@$_REQUEST["tipo"]=="barra") $sufijo="";

/********/
// GESTION DEL ALIAS 
/********/
$existe_alias = mysql_fetch_assoc(mysql_query("SELECT url_destino FROM cms_alias_urls WHERE url_alias LIKE '/".@$_REQUEST["enlace"].$sufijo."' LIMIT 1"));
if (@$existe_alias && @$_REQUEST["enlace"]) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    header("HTTP/1.1 301 Moved Permanently");
    if (strpos($existe_alias["url_destino"],"http") > -1){
        header("Location: ".$existe_alias["url_destino"]."");     
    }else{
        header("Location: ".$protocol.$_SERVER["HTTP_HOST"].$existe_alias["url_destino"].""); 
    }
    die();
}

$_REQUEST["enlace"] = str_replace("amp/", "", $_REQUEST["enlace"]);
$enlace_aux = $_REQUEST["enlace"];
if (@$_REQUEST["idioma"]) {
	$enlace_aux = $_REQUEST["idioma"]."/".$enlace_aux;
	$enlace_aux = str_replace($_REQUEST["idioma"]."/".$_REQUEST["idioma"]."/", $_REQUEST["idioma"]."/", $enlace_aux);
}

$result = mysql_query("SELECT prefix,tableName,recordNum,fieldName FROM cms_traducciones WHERE 
    (fieldValue LIKE '".@$enlace_aux.$sufijo."' 
    or fieldValue LIKE '".base64_encode(@$enlace_aux.$sufijo)."'  
    or fieldValue LIKE '".base64_encode("/".@$enlace_aux.$sufijo)."')
    AND fieldName = 'enlace'    
    AND tableName != 'builder_custom'
    LIMIT 1");
    
if ($result){
    
    $record = mysql_fetch_assoc($result);
    if ($record) $result2 = mysql_query("SELECT ".$record["fieldName"]." FROM cms_".$record["tableName"]." WHERE num=".$record["recordNum"]." LIMIT 1");
    
    if (isset($result2)){
        $record2 = mysql_fetch_assoc($result2);
        $_REQUEST["enlace"] = (@$sufijo=="/") ? substr($record2[$record["fieldName"]],1,strlen($record2[$record["fieldName"]])-2) : substr(str_replace($sufijo,"",$record2[$record["fieldName"]]),1,10000);

        $_REQUEST["idioma"] = $record["prefix"];
        
    }else{
        $_REQUEST["idioma"]="";
    }
}else{
    $_REQUEST["idioma"]="";
}
$enlace = "/".@$_REQUEST["enlace"].$sufijo;

$sql = "
    SELECT DISTINCT TABLE_NAME,COLUMN_NAME 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE COLUMN_NAME IN ('enlace')
        AND TABLE_SCHEMA='".$SETTINGS["mysql"]["database"]."'
    ";
$result = mysql_query($sql) or die(mysql_error());
$resultados = array();
$padre = 0;
$padreContenidos = false;
$controlador = "";
while($record = mysql_fetch_assoc($result)){
    
    $encontrado = mysql_fetch_assoc(mysql_query("SELECT * FROM ".$record["TABLE_NAME"]." WHERE ".$record["COLUMN_NAME"]."='".$enlace."' LIMIT 1"));

    if (@$encontrado){
            
        $tabla = $record["TABLE_NAME"];
        
        $registro = $encontrado["num"];
        $padre = intval(@$encontrado["parentNum"]);   
        if (isset($encontrado["layout"])&&$padre) $padreContenidos = mysql_fetch_assoc(mysql_query("select * from ".$record["TABLE_NAME"]." where num=".$padre." AND layout=1 LIMIT 1")) ? true : false;
        
        $controlador = @$encontrado["controlador"];  
        if ($record["COLUMN_NAME"]!="enlace") {
            $controlador="reserva.php";
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


