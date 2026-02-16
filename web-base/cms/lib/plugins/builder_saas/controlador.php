<?

if (!defined("BASE_PATH")) define("BASE_PATH","");

if (@$tabla==$TABLE_PREFIX."mail_marketing"){
	require_once BASE_PATH."controlador_mail.php"; 
	die();
}

if (@$_REQUEST["moduloBuilder"]){ 
	header("X-Robots-Tag: noindex, nofollow", true);
	$_REQUEST["enlace"] = "/";
	
	require_once __DIR__."/../../viewer_functions.php";
	
	$otrosModulos = '';
	$modulo = @$_REQUEST["moduloBuilder"];
	if (@$modulo) $moduloInfo = json_decode(@file_get_contents(__DIR__."/../../../../template/estandar/modulos/".$_REQUEST["moduloBuilder"]."/builder.json") ?: '[]',true);
	//if (@$modulo && !@$moduloInfo) die("Error 3");
	$menu = @$_REQUEST["menu"] ? mysql_real_escape_string(@$_REQUEST["menu"]) : "apartados";
	$num = @$_REQUEST["num"] ? " and num = ".intval(@$_REQUEST["num"]) : "";
	
	$apartado = CocoDB::get($menu,'builder LIKE \'%"'.$modulo.'"%\''.$num,"num ASC",1); $apartado=@$apartado[0];
	if (!@$apartado){
		$apartado = CocoDB::get($menu,'builder LIKE \'%"'.$modulo.'"%\'',"num ASC",1); $apartado=@$apartado[0];
	}
	// EN CASO DE QUE NO LO ENCUENTRE EN APARTADOS LO VOY A BUSCAR EN OTRO SITIO
	if (!@$apartado && $menu == "apartados"){
		require_once __DIR__."/builder_functions.php";
		$apartado = __get_apartado_from_all_tablenames($modulo,$num);	
	}
	
	$tabla = $menu;
	$key = "builder_saas";
	$var = ["num" => $num,"tabla" => $tabla];
	
	require_once __DIR__."/slug.php";

	if (!defined("BUILDER_JSON_RESPONSE") || BUILDER_JSON_RESPONSE === false) {
		// Cambio realizado por matito, si falla es culpa de tana el marica
		// $result = renderiza_modulo($modulo,$moduloInfo,$apartado,@$_REQUEST["index"]);
		$result = renderiza_modulo($modulo,$moduloInfo,$apartado,@$_REQUEST["index"],[],["allow_links" => @$_REQUEST["allow_links"] ? true : false, "disallow_scripts" => @$_REQUEST["disallow_scripts"] ? true : false]);
		echo $result;
	}else if (defined("BUILDER_JSON_RESPONSE") && defined("BUILDER_VUE_MAIN")){
		$data = [
			"modulo" => BUILDER_VUE_MAIN,
			"moduloInfo" => json_decode(@file_get_contents(__DIR__."/../../../../template/estandar/modulos/".BUILDER_VUE_MAIN."/builder.json") ?: '[]',true),
		];
		
		$result = renderiza_modulo($data["modulo"],$data["moduloInfo"],[ "builder" => json_encode([
		  [
			"modulo" => BUILDER_VUE_MAIN,
			"section_id" => time(),
			"oculto" => false,
			"config-vars" => []
		  ]
		])],null,["enlace" => $apartado["enlace"]]);
		
		echo $result;
	}else{
		echo "Error cargando el módulo";
	}
	die();
}

require_once (BASE_PATH."sesion.php");
require_once BASE_PATH."funciones.php";
require_once __DIR__."/builder_functions.php";

if (@$configuracionRecord["titulo_de_pagina"]!="") $configuracionRecord["titulo_de_pagina"] = t($configuracionRecord,"titulo_de_pagina");
if (@$configuracionRecord["metatag_descripcion"]!="") $configuracionRecord["metatag_descripcion"] = t($configuracionRecord,"metatag_descripcion");
if (@$configuracionRecord["metatag_palabras"]!="") $configuracionRecord["metatag_palabras"] = t($configuracionRecord,"metatag_palabras");

$menu = str_replace($TABLE_PREFIX,"",@$tabla);
if (!@$menu) $menu = "apartados";

$allowedTranslateFields = array_flip(array_map(function($field) {
	return $field['fieldName'];
}, mysql_query_fetch_all_assoc("SELECT DISTINCT fieldName FROM {$TABLE_PREFIX}traducciones")));

//$apartado = dame_registros($menu, "num=".intval(@$_REQUEST["num"]), "num DESC", 1);
$apartado = CocoDB::get($menu, "num=".intval(@$_REQUEST["num"]), "num DESC", 1,["relationsDepth" => 1]);
$apartado = @$apartado[0]; // get first record

global $apartado_override;
if(@$apartado_override) {
	$apartado = $apartado_override;
}

if (@$apartado["name"]) $configuracionRecord["titulo_de_pagina"] = t($apartado,"name")." - ".t($configuracionRecord,"titulo_de_pagina");
if (@$apartado["title"]) $configuracionRecord["titulo_de_pagina"] = t($apartado,"title")." - ".t($configuracionRecord,"titulo_de_pagina");

if (@$apartado["titulo_de_pagina"]!="") $configuracionRecord["titulo_de_pagina"] = t($apartado,"titulo_de_pagina");
if (@$apartado["metatag_descripcion"]!="") $configuracionRecord["metatag_descripcion"] = t($apartado,"metatag_descripcion");
if (@$apartado["metatag_palabras"]!="") $configuracionRecord["metatag_palabras"] = t($apartado,"metatag_palabras");

if (!@$apartado["builder"] || !isJson($apartado["builder"])) {
	header("HTTP/1.0 404 Not Found");
	include(BASE_PATH."header.php");
	echo tpl("apartados",array("apartado" => array("name" => "Error","enlace" => ""), "nombre" => t_var('PAGINA_NO_ENCONTRADA_TEXTO'),"contenido_texto" => t_var('PAGINA_NO_ENCONTRADA_TEXTO')));
	include BASE_PATH."footer.php";
	die();
}

if (!defined("BUILDER_JSON_RESPONSE") || BUILDER_JSON_RESPONSE === false) {
	include(BASE_PATH."header.php");
}

$jsonConfig = json_decode($apartado["builder"],true);
$cacheQuery = [];
$result = "";
$returnJSON = $jsonConfig;

foreach($jsonConfig as $cont => $section):
	if (@$_REQUEST["onlyModule"] && $_REQUEST["onlyModule"]!=$section["modulo"]) continue;
	$data = [];
	$configModulo = modulo_builder_config($section["modulo"]);
	if (!@$configModulo) continue;
	if (@$section["oculto"]) continue;
	$variables = [];
	if (@$section["referenciada"]){

		if (strpos($section["referenciada"],"|") !== false){
			$auxReferenciada = $section["referenciada"];
			$menu = explode("|",$auxReferenciada)[0];
			$section["referenciada"] = explode("|",$auxReferenciada)[1];
			$section["referenciada_indice"] = @explode("|",$auxReferenciada)[2] ?: "1";
		}else{
			$section["referenciada_indice"] = "1";
		}

		$apartadoReferencia = @CmsApi::get($menu,"num=".intval(@$section["referenciada"]),"num desc",1,["relationsDepth" => 0])[0];
		
		
		if (@$apartadoReferencia["builder"]){
			$jsonConfigAux = json_decode($apartadoReferencia["builder"],true);
			$contReferenciada = 0;
			foreach($jsonConfigAux as $contAux => $sectionAux){
				if ($sectionAux["modulo"] == $section["modulo"]) {
					$contReferenciada += 1;	
					if ($contReferenciada == $section["referenciada_indice"]) $section["config-vars"] = $sectionAux["config-vars"];
				}
			}
		}
	}
	if (@$section["config-vars"]){
		$variables = [];
		try{
			dame_variables_config($configModulo,$section["config-vars"],$variables,$section);
		}catch(Error $e){
			echo "Error procesando el módulo ".$section["modulo"].": ".$e->getMessage();
		}
		
	}
	if (!areFilledVariables($variables) && @$configModulo["staticVars"]){
		$variables = $configModulo["staticVars"];
	} 

	$pluginsNeeded = checkModulePluginsNeeded($configModulo);
	$pluginsNeeded = @$pluginsNeeded["result"] ? $pluginsNeeded["data"] : false;

	if (!defined("BUILDER_JSON_RESPONSE") || BUILDER_JSON_RESPONSE === false){
		if (@$_REQUEST["builderPreview"]) echo "<section class='moduleWrapperBuilder'>";

		if (@$pluginsNeeded){
			echo "<div class='bg-red-200 rounded p-4 text-center w-full'>Los siguientes plugins necesitan ser instalados para mostrar el módulo : <b>".join(", ",$pluginsNeeded)."</b><br><button onclick='installPluginsAcai(\"".$_SERVER["HTTP_HOST"]."\",null,\"".join(",",$pluginsNeeded)."\")' class='bg-red-600 text-white rounded px-4 py-2 hover:bg-red-700 inline-block mt-4'>Instalar plugins</button></div>
				<script src='https://cms.cocosolution.com/lib/plugins/builder_saas/js/plugins.js?timestamp=".time()."'></script>
			";
		}else{
			echo BuilderModule($section["modulo"],$variables);	
		}
		
		if (@$_REQUEST["builderPreview"]) echo "</section>";
	}else{
		if (@$pluginsNeeded){
			$returnJSON[$cont]["error"] = "Los siguientes plugins necesitan ser instalados para mostrar el módulo : ".join(", ",$pluginsNeeded);
		}else{
			$returnJSON[$cont]["variables"] = $variables;
			$returnJSON[$cont]["moduloInfo"] = $configModulo;
			$returnJSON[$cont]["metadata"] = ["tableName" => $menu,"num" => intval(@$num),"index" => $cont + 1,"parentNum" => intval(@$apartado["parentNum"]),"enlace" => @$apartado["enlace"],"idioma" => @$_REQUEST["idioma"]];
		}
	}
	//echo "<pre>";print_r($jsonConfig);echo "</pre>"; // comentario
	//echo "<pre>";print_r($configModulo);echo "</pre>"; // comentario
	//echo "<pre>";print_r($variables);echo "</pre>"; // comentario
endforeach;

if (!isset($_REQUEST["generateMinCss"]) && @$apartado["enlace"]) {
	generateMinCSS(t($apartado,"enlace"));
}

if (@$_REQUEST["builderPreview"]){
	?>
<style> .moduleWrapperBuilder{ pointer-events:auto; transition:all .3s ease-in-out; } .moduleWrapperBuilder *{pointer-events: none;}</style>
	<script src="https://cms.cocosolution.com/lib/plugins/builder_saas/js/builderPreview.js?timestamp=<?=time();?>"></script>
	<?
}


if (!defined("BUILDER_JSON_RESPONSE") || BUILDER_JSON_RESPONSE === false){
	echo $result;
	include(BASE_PATH."footer.php");
}else{
	header("Content-Type: application/json");
	echo json_encode($returnJSON);
}

