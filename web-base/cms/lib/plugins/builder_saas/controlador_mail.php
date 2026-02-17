<?
require_once (BASE_PATH."sesion.php");
require_once BASE_PATH."funciones.php";
require_once __DIR__."/builder_functions.php"; 

if (@$configuracionRecord["titulo_de_pagina"]!="") $configuracionRecord["titulo_de_pagina"] = t($configuracionRecord,"titulo_de_pagina");
if (@$configuracionRecord["metatag_descripcion"]!="") $configuracionRecord["metatag_descripcion"] = t($configuracionRecord,"metatag_descripcion");
if (@$configuracionRecord["metatag_palabras"]!="") $configuracionRecord["metatag_palabras"] = t($configuracionRecord,"metatag_palabras");

$allowedTranslateFields = array_flip(array_map(function($field) {
	return $field['fieldName'];
}, mysql_query_fetch_all_assoc("SELECT DISTINCT fieldName FROM {$TABLE_PREFIX}traducciones")));

$apartado = dame_registros(str_replace($TABLE_PREFIX,"",$tabla), "num=".intval(@$_REQUEST["num"]), "num DESC", 1);
$apartado = @$apartado[0]; // get first record
$configuracionRecord["titulo_de_pagina"] = t($apartado,"name")." - ".t($configuracionRecord,"titulo_de_pagina");
if (@$apartado["title"]) $configuracionRecord["titulo_de_pagina"] = t($apartado,"title")." - ".t($configuracionRecord,"titulo_de_pagina");

if (@$apartado["titulo_de_pagina"]!="") $configuracionRecord["titulo_de_pagina"] = t($apartado,"titulo_de_pagina");
if (@$apartado["metatag_descripcion"]!="") $configuracionRecord["metatag_descripcion"] = t($apartado,"metatag_descripcion");
if (@$apartado["metatag_palabras"]!="") $configuracionRecord["metatag_palabras"] = t($apartado,"metatag_palabras");

if (!@$apartado["builder"] || !isJson($apartado["builder"])) {
	header("HTTP/1.0 404 Not Found");
	include(BASE_PATH."header.php");
	echo tpl("apartados",array("apartado" => array("name" => "Error","enlace" => ""), "nombre" => PAGINA_NO_ENCONTRADA,"contenido_texto" => PAGINA_NO_ENCONTRADA_TEXTO));
	include BASE_PATH."footer.php";
	die();
}
if (@$_REQUEST["onlyModule"]){
	header("X-Robots-Tag: noindex, nofollow", true);
}

require_once __DIR__."/replace_code.php";
if (@$_REQUEST["onlyModule"]) {
	?><style>body{margin:0px !important;padding:0px !important;}body *{display:none !important; } main,.builderModule,.builderModule *{ display:block !important; }</style><?
}

$jsonConfig = json_decode($apartado["builder"],true);
$cacheQuery = [];
$result = "";
$head = '<mj-style inline="inline">.text-transparent{color:transparent} .text-black{color:rgba(0,0,0,1)} .text-white{color:rgba(255,255,255,1)} .text-gray-50{color:rgba(249,250,251,1)} .text-gray-100{color:rgba(243,244,246,1)} .text-gray-200{color:rgba(229,231,235,1)} .text-gray-300{color:rgba(209,213,219,1)} .text-gray-400{color:rgba(156,163,175,1)} .text-gray-500{color:rgba(107,114,128,1)} .text-gray-600{color:rgba(75,85,99,1)} .text-gray-700{color:rgba(55,65,81,1)} .text-gray-800{color:rgba(31,41,55,1)} .text-gray-900{color:rgba(17,24,39,1)} .text-red-50{color:rgba(254,242,242,1)} .text-red-100{color:rgba(254,226,226,1)} .text-red-200{color:rgba(254,202,202,1)} .text-red-300{color:rgba(252,165,165,1)} .text-red-400{color:rgba(248,113,113,1)} .text-red-500{color:rgba(239,68,68,1)} .text-red-600{color:rgba(220,38,38,1)} .text-red-700{color:rgba(185,28,28,1)} .text-red-800{color:rgba(153,27,27,1)} .text-red-900{color:rgba(127,29,29,1)} .text-yellow-50{color:rgba(255,251,235,1)} .text-yellow-100{color:rgba(254,243,199,1)} .text-yellow-200{color:rgba(253,230,138,1)} .text-yellow-300{color:rgba(252,211,77,1)} .text-yellow-400{color:rgba(251,191,36,1)} .text-yellow-500{color:rgba(245,158,11,1)} .text-yellow-600{color:rgba(217,119,6,1)} .text-yellow-700{color:rgba(180,83,9,1)} .text-yellow-800{color:rgba(146,64,14,1)} .text-yellow-900{color:rgba(120,53,15,1)} .text-green-50{color:rgba(236,253,245,1)} .text-green-100{color:rgba(209,250,229,1)} .text-green-200{color:rgba(167,243,208,1)} .text-green-300{color:rgba(110,231,183,1)} .text-green-400{color:rgba(52,211,153,1)} .text-green-500{color:rgba(16,185,129,1)} .text-green-600{color:rgba(5,150,105,1)} .text-green-700{color:rgba(4,120,87,1)} .text-green-800{color:rgba(6,95,70,1)} .text-green-900{color:rgba(6,78,59,1)} .text-blue-50{color:rgba(239,246,255,1)} .text-blue-100{color:rgba(219,234,254,1)} .text-blue-200{color:rgba(191,219,254,1)} .text-blue-300{color:rgba(147,197,253,1)} .text-blue-400{color:rgba(96,165,250,1)} .text-blue-500{color:rgba(59,130,246,1)} .text-blue-600{color:rgba(37,99,235,1)} .text-blue-700{color:rgba(29,78,216,1)} .text-blue-800{color:rgba(30,64,175,1)} .text-blue-900{color:rgba(30,58,138,1)} .text-indigo-50{color:rgba(238,242,255,1)} .text-indigo-100{color:rgba(224,231,255,1)} .text-indigo-200{color:rgba(199,210,254,1)} .text-indigo-300{color:rgba(165,180,252,1)} .text-indigo-400{color:rgba(129,140,248,1)} .text-indigo-500{color:rgba(99,102,241,1)} .text-indigo-600{color:rgba(79,70,229,1)} .text-indigo-700{color:rgba(67,56,202,1)} .text-indigo-800{color:rgba(55,48,163,1)} .text-indigo-900{color:rgba(49,46,129,1)} .text-purple-50{color:rgba(245,243,255,1)} .text-purple-100{color:rgba(237,233,254,1)} .text-purple-200{color:rgba(221,214,254,1)} .text-purple-300{color:rgba(196,181,253,1)} .text-purple-400{color:rgba(167,139,250,1)} .text-purple-500{color:rgba(139,92,246,1)} .text-purple-600{color:rgba(124,58,237,1)} .text-purple-700{color:rgba(109,40,217,1)} .text-purple-800{color:rgba(91,33,182,1)} .text-purple-900{color:rgba(76,29,149,1)} .text-pink-50{color:rgba(253,242,248,1)} .text-pink-100{color:rgba(252,231,243,1)} .text-pink-200{color:rgba(251,207,232,1)} .text-pink-300{color:rgba(249,168,212,1)} .text-pink-400{color:rgba(244,114,182,1)} .text-pink-500{color:rgba(236,72,153,1)} .text-pink-600{color:rgba(219,39,119,1)} .text-pink-700{color:rgba(190,24,93,1)} .text-pink-800{color:rgba(157,23,77,1)} .text-pink-900{color:rgba(131,24,67,1)} .bg-transparent{background-color:transparent} .bg-current{background-color:currentColor} .bg-black{background-color:rgba(0,0,0,1)} .bg-white{background-color:rgba(255,255,255,1)} .bg-gray-50{background-color:rgba(249,250,251,1)} .bg-gray-100{background-color:rgba(243,244,246,1)} .bg-gray-200{background-color:rgba(229,231,235,1)} .bg-gray-300{background-color:rgba(209,213,219,1)} .bg-gray-400{background-color:rgba(156,163,175,1)} .bg-gray-500{background-color:rgba(107,114,128,1)} .bg-gray-600{background-color:rgba(75,85,99,1)} .bg-gray-700{background-color:rgba(55,65,81,1)} .bg-gray-800{background-color:rgba(31,41,55,1)} .bg-gray-900{background-color:rgba(17,24,39,1)} .bg-red-50{background-color:rgba(254,242,242,1)} .bg-red-100{background-color:rgba(254,226,226,1)} .bg-red-200{background-color:rgba(254,202,202,1)} .bg-red-300{background-color:rgba(252,165,165,1)} .bg-red-400{background-color:rgba(248,113,113,1)} .bg-red-500{background-color:rgba(239,68,68,1)} .bg-red-600{background-color:rgba(220,38,38,1)} .bg-red-700{background-color:rgba(185,28,28,1)} .bg-red-800{background-color:rgba(153,27,27,1)} .bg-red-900{background-color:rgba(127,29,29,1)} .bg-yellow-50{background-color:rgba(255,251,235,1)} .bg-yellow-100{background-color:rgba(254,243,199,1)} .bg-yellow-200{background-color:rgba(253,230,138,1)} .bg-yellow-300{background-color:rgba(252,211,77,1)} .bg-yellow-400{background-color:rgba(251,191,36,1)} .bg-yellow-500{background-color:rgba(245,158,11,1)} .bg-yellow-600{background-color:rgba(217,119,6,1)} .bg-yellow-700{background-color:rgba(180,83,9,1)} .bg-yellow-800{background-color:rgba(146,64,14,1)} .bg-yellow-900{background-color:rgba(120,53,15,1)} .bg-green-50{background-color:rgba(236,253,245,1)} .bg-green-100{background-color:rgba(209,250,229,1)} .bg-green-200{background-color:rgba(167,243,208,1)} .bg-green-300{background-color:rgba(110,231,183,1)} .bg-green-400{background-color:rgba(52,211,153,1)} .bg-green-500{background-color:rgba(16,185,129,1)} .bg-green-600{background-color:rgba(5,150,105,1)} .bg-green-700{background-color:rgba(4,120,87,1)} .bg-green-800{background-color:rgba(6,95,70,1)} .bg-green-900{background-color:rgba(6,78,59,1)} .bg-blue-50{background-color:rgba(239,246,255,1)} .bg-blue-100{background-color:rgba(219,234,254,1)} .bg-blue-200{background-color:rgba(191,219,254,1)} .bg-blue-300{background-color:rgba(147,197,253,1)} .bg-blue-400{background-color:rgba(96,165,250,1)} .bg-blue-500{background-color:rgba(59,130,246,1)} .bg-blue-600{background-color:rgba(37,99,235,1)} .bg-blue-700{background-color:rgba(29,78,216,1)} .bg-blue-800{background-color:rgba(30,64,175,1)} .bg-blue-900{background-color:rgba(30,58,138,1)} .bg-indigo-50{background-color:rgba(238,242,255,1)} .bg-indigo-100{background-color:rgba(224,231,255,1)} .bg-indigo-200{background-color:rgba(199,210,254,1)} .bg-indigo-300{background-color:rgba(165,180,252,1)} .bg-indigo-400{background-color:rgba(129,140,248,1)} .bg-indigo-500{background-color:rgba(99,102,241,1)} .bg-indigo-600{background-color:rgba(79,70,229,1)} .bg-indigo-700{background-color:rgba(67,56,202,1)} .bg-indigo-800{background-color:rgba(55,48,163,1)} .bg-indigo-900{background-color:rgba(49,46,129,1)} .bg-purple-50{background-color:rgba(245,243,255,1)} .bg-purple-100{background-color:rgba(237,233,254,1)} .bg-purple-200{background-color:rgba(221,214,254,1)} .bg-purple-300{background-color:rgba(196,181,253,1)} .bg-purple-400{background-color:rgba(167,139,250,1)} .bg-purple-500{background-color:rgba(139,92,246,1)} .bg-purple-600{background-color:rgba(124,58,237,1)} .bg-purple-700{background-color:rgba(109,40,217,1)} .bg-purple-800{background-color:rgba(91,33,182,1)} .bg-purple-900{background-color:rgba(76,29,149,1)} .bg-pink-50{background-color:rgba(253,242,248,1)} .bg-pink-100{background-color:rgba(252,231,243,1)} .bg-pink-200{background-color:rgba(251,207,232,1)} .bg-pink-300{background-color:rgba(249,168,212,1)} .bg-pink-400{background-color:rgba(244,114,182,1)} .bg-pink-500{background-color:rgba(236,72,153,1)} .bg-pink-600{background-color:rgba(219,39,119,1)} .bg-pink-700{background-color:rgba(190,24,93,1)} .bg-pink-800{background-color:rgba(157,23,77,1)} .bg-pink-900{background-color:rgba(131,24,67,1)}</mj-style>';
if (@$_REQUEST["builderPreview"]) $head.='<mj-style inline="inline">.moduleWrapperBuilder{ pointer-events:auto; transition:all .3s ease-in-out; } .moduleWrapperBuilder *{pointer-events: none;} </mj-style>';

CustomCode::$webp = false;
CustomCode::$iniConfig["config"]["webp"] = 0;

foreach($jsonConfig as $section):
	
	if (@$_REQUEST["onlyModule"] && $_REQUEST["onlyModule"]!=$section["modulo"]) continue;
	$data = [];
	$configModulo = modulo_builder_config($section["modulo"]);
	if (!@$configModulo) continue;
	if (@$section["oculto"]) continue;
	$variables = [];
	if (@$section["referenciada"]){
		$apartadoReferencia = @dame_registros("apartados","num=".intval(@$section["referenciada"]),"num desc",1)[0];
		if (@$apartadoReferencia){
			$jsonConfigAux = json_decode($apartadoReferencia["builder"],true);
			foreach($jsonConfigAux as $contAux => $sectionAux){
				if ($sectionAux["modulo"] == $section["modulo"]) $section["config-vars"] = $sectionAux["config-vars"];
			}
		}
	}

	dame_variables_config($configModulo,$section["config-vars"],$variables,$section);
	
	
	if (@$_REQUEST["onlyModule"]){
		//echo "<div class='builderModule'>".modulo($section["modulo"],$variables)."</div>";
		echo "<div class='builderModule'>".BuilderModule($section["modulo"],$variables)."</div>";
	}else{
//		$variables["apartadoWrapper"] = $apartado;
//		$variables["thisrecord"] = $apartado;
//		$variables["configuracion"] = @$configuracionRecord;
//		$variables["request"] = @$_REQUEST;
//		$variables["section_id"] = @$section["section_id"];
//		$variables["thismodule"] = @$section;
//				
//		$moduloHTML = modulo($section["modulo"],$variables);
		$moduloHTML = BuilderModule($section["modulo"],$variables);
		
		preg_match('/<mj-head>(.*)<\/mj-head>/s', $moduloHTML, $m);
		
		if (@$_REQUEST["builderPreview"]) $result.='<mj-hero css-class="moduleWrapperBuilder" padding="0px">';
		
		if (@$m[1]){
			$head = str_replace($m[1],"",$head);
			$head.=$m[1];
			$moduloHTML = preg_replace_callback('/<mj-head>(.*)<\/mj-head>(.*)/s', function($m){ return @$m[2]; }, $moduloHTML);
			//$moduloHTML = str_replace("<mj-head>".$m[1]."</mh-head>","",$moduloHTML);
			$result.=$moduloHTML;
		}else{
			$result.=$moduloHTML;
		}
		if (@$_REQUEST["builderPreview"]) $result.='</mj-hero>';
		
	}
	//echo "<pre>";print_r($jsonConfig);echo "</pre>"; // comentario
	//echo "<pre>";print_r($configModulo);echo "</pre>"; // comentario
	//echo "<pre>";print_r($variables);echo "</pre>"; // comentario
endforeach;

$tempFile = sys_get_temp_dir()."/".$_SERVER["HTTP_HOST"]."-".$tabla."-".$apartado["num"].@$_REQUEST["idioma"].".html";

if (file_exists($tempFile) && filemtime($tempFile)>strtotime($apartado["updatedDate"]) && !@$_REQUEST["dev"] && !@$_REQUEST["builderPreview"] && !strpos( file_get_contents($tempFile),"pointer-events")){
	echo file_get_contents($tempFile);
}else{
	$resultMJML = MJMLParser::parse(str_replace("/cms/uploads","https://".$_SERVER["HTTP_HOST"]."/cms/uploads",$result));
	if ($resultMJML["success"]){
		echo $resultMJML["success"];
		file_put_contents($tempFile,$resultMJML["success"]);
	}else{
		echo $resultMJML["error"];
	}
	if (@$_REQUEST["dev"]){
		echo "<pre style='background-color:#ddd;padding:10px;border:solid 1px #999; border-radius:10px; margin:10px;'>";
		print_r(str_replace("<","&lt;",$result));
		echo "</pre>";
		echo "<pre style='background-color:#ddd;padding:10px;border:solid 1px #999; border-radius:10px; margin:10px;'>";
		print_r(str_replace("<","&lt;",$resultMJML));
		echo "</pre>";
	}
}

if (@$_REQUEST["onlyModule"]) {
	?><style>body{margin:0px !important;padding:0px !important;}body *{display:none !important; } main,.builderModule,.builderModule *{ display:block !important; }</style><?
}
if (@$_REQUEST["builderPreview"]){
	?>
	<script src="https://cms.cocosolution.com/lib/plugins/builder_saas/js/builderPreview.js?timestamp=<?=time();?>"></script>
	<?
}

class MJMLParser{
	static function parse($code = ""){
		global $head;
		$head2 = "";
		
		if ($head!="") $head2 = "\n<mj-head>".$head."</mj-head>\n";
		$codeObj = ["mjml" => "<mjml>".$head2."<mj-body>".$code."</mj-body></mjml>"];
		// Generated by curl-to-PHP: http://incarnate.github.io/curl-to-php/
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, 'https://api.mjml.io/v1/render');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($codeObj));
		curl_setopt($ch, CURLOPT_USERPWD, '5184600b-968c-4428-be08-93792b2ffaaf' . ':' . 'd5676f61-4f23-44b7-9559-410f889af7f1');

		$headers = array();
		$headers[] = 'Content-Type: application/x-www-form-urlencoded';
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			return ["error" => curl_error($ch)];
		}
		curl_close($ch);
		
		return ["success" => json_decode($result,true)["html"]];
	} 
}
