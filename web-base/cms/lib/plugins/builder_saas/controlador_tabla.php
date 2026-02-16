<?

if (!defined("BASE_PATH")) define("BASE_PATH","");

if (@$_REQUEST["moduloBuilder"]){
	if (!file_exists(__DIR__."/layout.json")) die("Error");
	require_once __DIR__."/../../viewer_functions.php";
	require_once __DIR__."/../../../../sesion.php";
	require_once __DIR__."/../../../../funciones.php";
	require_once __DIR__."/replace_code.php";
	require_once __DIR__."/builder_functions.php";
	if (file_exists(__DIR__."/../cms_api/v3/CmsApi.class.php")) require_once __DIR__."/../cms_api/v3/CmsApi.class.php";
	$tabla = @$_REQUEST["moduloBuilder"];
	$apartado = CocoDB::get(str_replace($TABLE_PREFIX,"",$tabla), "", "num DESC",1);
	$apartado = @$apartado[0]; // get first record
	
	if (isset($_REQUEST["viewAMP"])){
		$libraries = "";
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
		
		echo '
		<!DOCTYPE>
		<html amp>

		<head>
			'.$libraries.'
		</head>

		<body>	
		';
	}else{
		echo '
		<html>

		<head>
			<link rel="stylesheet" href="/custom-builder-style.css?t=1582749792">
			<link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
			<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tiny-slider/2.9.2/tiny-slider.css">
			<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
			<link href="/template/estandar/modulos/custom-'.$tabla.'/style.css" rel="stylesheet">
			<script src="https://cdnjs.cloudflare.com/ajax/libs/tiny-slider/2.9.2/min/tiny-slider.js"></script>
			<style>html,body{height:auto}</style>
		</head>

		<body>	
		';
	}
	
	echo BuilderModule("custom-".str_replace($TABLE_PREFIX,"",$tabla));
	echo '
	</body>
	</html>
	';
	die();
}

require_once (BASE_PATH."sesion.php");
require_once BASE_PATH."funciones.php";
require_once __DIR__."/builder_functions.php";

if (@$configuracionRecord["titulo_de_pagina"]!="") $configuracionRecord["titulo_de_pagina"] = t($configuracionRecord,"titulo_de_pagina");
if (@$configuracionRecord["metatag_descripcion"]!="") $configuracionRecord["metatag_descripcion"] = t($configuracionRecord,"metatag_descripcion");
if (@$configuracionRecord["metatag_palabras"]!="") $configuracionRecord["metatag_palabras"] = t($configuracionRecord,"metatag_palabras");

$allowedTranslateFields = array_flip(array_map(function($field) {
	return $field['fieldName'];
}, mysql_query_fetch_all_assoc("SELECT DISTINCT fieldName FROM {$TABLE_PREFIX}traducciones")));


$apartado = CocoDB::get(str_replace($TABLE_PREFIX,"",$tabla), "num=".intval(@$_REQUEST["num"]), "num DESC");
$apartado = @$apartado[0]; // get first record
if (@$apartado["name"]) $configuracionRecord["titulo_de_pagina"] = t($apartado,"name")." - ".t($configuracionRecord,"titulo_de_pagina");
if (@$apartado["title"]) $configuracionRecord["titulo_de_pagina"] = t($apartado,"title")." - ".t($configuracionRecord,"titulo_de_pagina");

if (@$apartado["titulo_de_pagina"]!="") $configuracionRecord["titulo_de_pagina"] = t($apartado,"titulo_de_pagina");
if (@$apartado["metatag_descripcion"]!="") $configuracionRecord["metatag_descripcion"] = t($apartado,"metatag_descripcion");
if (@$apartado["metatag_palabras"]!="") $configuracionRecord["metatag_palabras"] = t($apartado,"metatag_palabras");

if (!@$apartado) {
	header("HTTP/1.0 404 Not Found");
	include(BASE_PATH."header.php");
	echo tpl("apartados",array("apartado" => array("name" => "Error","enlace" => ""), "nombre" => PAGINA_NO_ENCONTRADA,"contenido_texto" => PAGINA_NO_ENCONTRADA_TEXTO));
	include BASE_PATH."footer.php";
	die();
}

include(BASE_PATH."header.php");
echo BuilderModule("custom-".str_replace($TABLE_PREFIX,"",$tabla));
/*echo modulo("custom-".str_replace($TABLE_PREFIX,"",$tabla),[
	"apartadoWrapper" => $apartado,
	"thisrecord" => $apartado,
	"configuracion" => @$configuracionRecord,
	"request" => @$_REQUEST
]);*/

if (!isset($_REQUEST["generateMinCss"]) && @$apartado["enlace"]) {
	generateMinCSS(t($apartado,"enlace"));
}

include(BASE_PATH."footer.php");
