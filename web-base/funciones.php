<?
require_once CMS_VIEWER_LIB;

// VARIABLES RELACIONADAS CON LA EXTRACCION DE CSS
$contadorImagenes = 0;
$modulosCargados = array();
$recursosCSS = array();
// HASTA AUQI

define('USAR_WEBP', false);
if (!file_exists(__DIR__.'/'.CMS_FOLDER.'/uploads/webp/')) {
	mkdir(__DIR__.'/'.CMS_FOLDER.'/uploads/webp/');
}
global $configuracionTienda;
$configuracionRecord = dame_registros("configuracion","","",1);$configuracionRecord=@$configuracionRecord[0];
$configuracionTienda = dame_registros("configuracion_tienda","","",1); $configuracionTienda = @$configuracionTienda[0];
define("HAY_TIENDA", @$configuracionTienda["tienda_activa"]);

if (@$configuracionRecord["pagina_publicada"] && !@$_SESSION["pruebas"]) error_reporting(0);
// COMPROBAMOS SI HAY CUSTOM HEADER Y FOOTER Y ACTIVAMOS LA VARIABLE
// ARCHIVOS AFECTADOS : header.php - footer.php - funciones.php - htaccess
$layoutFile = __DIR__."/".CMS_FOLDER."/lib/plugins/builder_saas/layout.json";
if (file_exists($layoutFile)){
	$layoutJson = json_decode(file_get_contents($layoutFile),true);
	if (@$layoutJson["active"]){
		global $customCode;
		$customCode = true;
	}
}

/******** NUEVAS FUNCIONES ********/

function dame_alternates($link) {
	
	global $SETTINGS, $TABLE_PREFIX,$tabla, $num;
	$enlaces = array();
	
	$idiomasPermitidos = array_filter(array_map(function($idioma) {
		return str_replace("/", "", $idioma["valor"]);
	}, dame_idiomas()));
	
	if (!@$tabla) return [];
	$encontrado = mysql_fetch_assoc(mysql_query("SELECT * FROM ".$tabla." WHERE num=".$num." limit 1"));
	if (@$encontrado) $encontrado["tableName"] = str_replace($TABLE_PREFIX,"",$tabla);
	if (!@$encontrado["num"]) return $enlaces; 
	$enlaces[] = array(
		"prefix" => "es",
		"fieldValue" => base64_encode($encontrado["enlace"])
	);	
	
	$sql = mysql_query("SELECT * FROM ".$TABLE_PREFIX."traducciones WHERE fieldName='enlace' AND prefix IN('".join("','", $idiomasPermitidos)."') AND tableName='".$encontrado["tableName"]."' AND recordNum=".$encontrado["num"]);
	while ($row = mysql_fetch_assoc($sql)) {
		$enlaces[] = $row;
	}
	return $enlaces;
}

/******** FIN NUEVAS FUNCIONES ********/

function hasRecaptcha() {
	global $configuracionRecord;
	return @$configuracionRecord["site_key_recaptcha"] && @$configuracionRecord["secret_key_recaptcha"];
}

function dame_registros($tabla,$where="",$order="",$limit=1000,$depth=0){
	global $TABLE_PREFIX;
	list($configuracionRecords, $configuracionMetaData,$schema) = getRecords(array(
		'tableName'   =>  $tabla,
		'where'       =>  $where,
		'allowSearch' =>  0,
		'orderBy'     =>  $order,
		'limit'       =>  $limit
	));
	
	/*
    EXPERIMENTAL - EN DESARROLLO
    */
	foreach ($configuracionRecords as $index => $record) {
		if (@$schema["menuType"] == "category"){
			$configuracionRecords[$index]["childs"] = mysql_num_rows(mysql_query("SELECT * FROM ".$TABLE_PREFIX.$tabla." WHERE parentNum=".$record["num"]));
		}
		foreach ($record as $key => $value) {
			if (!isset($schema[$key]["type"]) || !is_array($schema[$key])) continue;
			switch (@$schema[$key]["type"]) {
				case "list":
					if (@$schema[$key]["optionsType"] == "table") {
						$nums = array_filter(explode("\t", $value ?? ''));
						
						if (@$nums && !$depth) {
							$newSchema = loadSchema($schema[$key]["optionsTablename"]);
							
							if (@$newSchema["dragSortOrder"]) $order = "dragSortOrder DESC";
							if (@$newSchema["siblingOrder"]) $order = "siblingOrder ASC";
							if (!@$order) $order = "num DESC";
							
							$newRecord = dame_registros($schema[$key]["optionsTablename"], $schema[$key]["optionsValueField"]." IN (".join(",", $nums).")", $order,1000,1);
							
							$configuracionRecords[$index][$key."_bd"] = $newRecord;
						}else {
							$configuracionRecords[$index][$key."_bd"] = array();
						}
					}else{
						$configuracionRecords[$index][$key."_bd"] = array();
					}
					break;
				case "multitext":
					$result = (@$value) ? json_decode(t($record, $key), true) : array();
					$configuracionRecords[$index][$key."_bd"] = $result;
					break;
				case "textfield":
					if (@$schema[$key]["tipoTags"]) {
						$tags = array_filter(explode(",", $value));
						$configuracionRecords[$index][$key."_bd"] = $tags;
					}
					break;
				default:
					break;
			}
		}
		$configuracionRecords[$index]["breadcrumbField"] = @$schema["breadcrumbField"];
		// Si es parentNum ponemos valores por defecto
		if (@$configuracionRecords[$index]["breadcrumbField"] == "parentNum") {
			$configuracionRecords[$index]["optionsTablename"] = $configuracionRecords[$index]["tableName"];
			$configuracionRecords[$index]["optionsValueField"] = "num";
		} else if (@$configuracionRecords[$index]["breadcrumbField"]) {
			// Si no es parentNum, ponemos los que dicte el schema
			$configuracionRecords[$index]["optionsTablename"] = @$schema[$schema["breadcrumbField"]]["optionsTablename"];
			$configuracionRecords[$index]["optionsValueField"] =  @$schema[$schema["breadcrumbField"]]["optionsValueField"];
		}

		// Para el campo principal (para la generación de enlaces y el breadcrumb)
		if (@$record["name"]) {
			$configuracionRecords[$index]["mainFieldBreadcrumb"] = t($record, "name");
		}
		else if (@$record["title"]) {
			$configuracionRecords[$index]["mainFieldBreadcrumb"] = t($record, "title");
		}
		else if (@$record["titulo"]) {
			$configuracionRecords[$index]["mainFieldBreadcrumb"] = t($record, "titulo");
		}
		else if (@$record["nombre"]) {
			$configuracionRecords[$index]["mainFieldBreadcrumb"] = t($record, "nombre");
		}
		else {
			foreach ($schema as $key => $value):
				if (!is_array($value)) continue;
			if (@$value["type"] == "textfield" && $key != "enlace") {
				$configuracionRecords[$index]["mainFieldBreadcrumb"] = t($record, $key);
				break;
			}
			endforeach;
		}
	}
	
	
	if ($configuracionRecords) {
		
		return $configuracionRecords; 
	}else {
		return array();
	}
}

function dame_idiomas(){
	$array_ini = parse_ini_file($_SERVER["DOCUMENT_ROOT"]."/".CMS_FOLDER."/data/settings.dat.php", true);
	$resultado = array();
	if (@$array_ini["idiomas"]){

		foreach ($array_ini["idiomas"] as $idioma => $valor):
		if ($valor!=""){
			if ($valor=="www") $valor=""; else $valor="/".$valor;
			if ($idioma=="espanol") $idioma="Español";
			if ($idioma=="ingles") $idioma ="Inglés";
			if ($idioma=="aleman") $idioma="Alemán";
			if ($idioma=="frances") $idioma ="Francés";
			if ($idioma=="portugues") $idioma ="Portugués";
			if ($idioma=="catalan") $idioma ="Catalán";
			if ($idioma=="italiano") $idioma ="Italiano";
			if ($idioma=="koreano") $idioma ="Koreano";
			if ($idioma=="chino") $idioma ="Chino";
			if ($idioma=="noruego") $idioma ="Noruego";
			if ($idioma=="ruso") $idioma ="Ruso";
			if ($idioma=="nigeriano") $idioma ="Nigeriano";

			array_push($resultado,array("idioma" => $idioma,"valor" => $valor));
		}
		endforeach;
	}
	return $resultado;
}

function ordenarArray ($toOrderArray, $field, $inverse = false) {
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

function dame_registros_con_id($tabla,$where="",$order="",$limite=10000){
	list($anunciosRecords, $anunciosMetaData) = getRecords(array(
		'tableName'   => $tabla,
		'where'       => $where,
		'orderBy'   => $order,
		'limit'   => $limite,
		'allowSearch' => 0
	));

	if ($anunciosRecords) {
		$resultado = array();
		foreach ($anunciosRecords as $record):
		$resultado[$record["num"]] = $record;
		endforeach;
		return $resultado;
	}else{
		return 0;
	}
}

function tpl($p,$d=array()){
	global $configuracionRecord,$recursosCSS,$SETTINGS;

	extract($d);
	ob_start();
	$doc = new domdocument();

	require("./".PLANTILLA."/".$p.'.tpl');

	$resultado = ob_get_clean();

	return minify_html($resultado);

	
}

function modulo($p,$d=array()){
	return Module::load($p, $d);
}

function modulo_php($p,$d=array()){
	extract($d);
	ob_start();
	require("./modulos_php/".$p.'.php');
	return ob_get_clean();
}

// Parsea enlace definitivo
function parsea_enlace($txt) {
	$transliterationTable = array('á' => 'a', 'Á' => 'A', 'à' => 'a', 'À' => 'A', 'ă' => 'a', 'Ă' => 'A', 'â' => 'a', 'Â' => 'A', 'å' => 'a', 'Å' => 'A', 'ã' => 'a', 'Ã' => 'A', 'ą' => 'a', 'Ą' => 'A', 'ā' => 'a', 'Ā' => 'A', 'ä' => 'a', 'Ä' => 'A', 'æ' => 'ae', 'Æ' => 'AE', 'ḃ' => 'b', 'Ḃ' => 'B', 'ć' => 'c', 'Ć' => 'C', 'ĉ' => 'c', 'Ĉ' => 'C', 'č' => 'c', 'Č' => 'C', 'ċ' => 'c', 'Ċ' => 'C', 'ç' => 'c', 'Ç' => 'C', 'ď' => 'd', 'Ď' => 'D', 'ḋ' => 'd', 'Ḋ' => 'D', 'đ' => 'd', 'Đ' => 'D', 'ð' => 'dh', 'Ð' => 'Dh', 'é' => 'e', 'É' => 'E', 'è' => 'e', 'È' => 'E', 'ĕ' => 'e', 'Ĕ' => 'E', 'ê' => 'e', 'Ê' => 'E', 'ě' => 'e', 'Ě' => 'E', 'ë' => 'e', 'Ë' => 'E', 'ė' => 'e', 'Ė' => 'E', 'ę' => 'e', 'Ę' => 'E', 'ē' => 'e', 'Ē' => 'E', 'ḟ' => 'f', 'Ḟ' => 'F', 'ƒ' => 'f', 'Ƒ' => 'F', 'ğ' => 'g', 'Ğ' => 'G', 'ĝ' => 'g', 'Ĝ' => 'G', 'ġ' => 'g', 'Ġ' => 'G', 'ģ' => 'g', 'Ģ' => 'G', 'ĥ' => 'h', 'Ĥ' => 'H', 'ħ' => 'h', 'Ħ' => 'H', 'í' => 'i', 'Í' => 'I', 'ì' => 'i', 'Ì' => 'I', 'î' => 'i', 'Î' => 'I', 'ï' => 'i', 'Ï' => 'I', 'ĩ' => 'i', 'Ĩ' => 'I', 'į' => 'i', 'Į' => 'I', 'ī' => 'i', 'Ī' => 'I', 'ĵ' => 'j', 'Ĵ' => 'J', 'ķ' => 'k', 'Ķ' => 'K', 'ĺ' => 'l', 'Ĺ' => 'L', 'ľ' => 'l', 'Ľ' => 'L', 'ļ' => 'l', 'Ļ' => 'L', 'ł' => 'l', 'Ł' => 'L', 'ṁ' => 'm', 'Ṁ' => 'M', 'ń' => 'n', 'Ń' => 'N', 'ň' => 'n', 'Ň' => 'N', 'ñ' => 'n', 'Ñ' => 'N', 'ņ' => 'n', 'Ņ' => 'N', 'ó' => 'o', 'Ó' => 'O', 'ò' => 'o', 'Ò' => 'O', 'ô' => 'o', 'Ô' => 'O', 'ő' => 'o', 'Ő' => 'O', 'õ' => 'o', 'Õ' => 'O', 'ø' => 'o', 'Ø' => 'O', 'ō' => 'o', 'Ō' => 'O', 'ơ' => 'o', 'Ơ' => 'O', 'ö' => 'o', 'Ö' => 'O', 'ṗ' => 'p', 'Ṗ' => 'P', 'ŕ' => 'r', 'Ŕ' => 'R', 'ř' => 'r', 'Ř' => 'R', 'ŗ' => 'r', 'Ŗ' => 'R', 'ś' => 's', 'Ś' => 'S', 'ŝ' => 's', 'Ŝ' => 'S', 'š' => 's', 'Š' => 'S', 'ṡ' => 's', 'Ṡ' => 'S', 'ş' => 's', 'Ş' => 'S', 'ș' => 's', 'Ș' => 'S', 'ß' => 'SS', 'ť' => 't', 'Ť' => 'T', 'ṫ' => 't', 'Ṫ' => 'T', 'ţ' => 't', 'Ţ' => 'T', 'ț' => 't', 'Ț' => 'T', 'ŧ' => 't', 'Ŧ' => 'T', 'ú' => 'u', 'Ú' => 'U', 'ù' => 'u', 'Ù' => 'U', 'ŭ' => 'u', 'Ŭ' => 'U', 'û' => 'u', 'Û' => 'U', 'ů' => 'u', 'Ů' => 'U', 'ű' => 'u', 'Ű' => 'U', 'ũ' => 'u', 'Ũ' => 'U', 'ų' => 'u', 'Ų' => 'U', 'ū' => 'u', 'Ū' => 'U', 'ư' => 'u', 'Ư' => 'U', 'ü' => 'u', 'Ü' => 'U', 'ẃ' => 'w', 'Ẃ' => 'W', 'ẁ' => 'w', 'Ẁ' => 'W', 'ŵ' => 'w', 'Ŵ' => 'W', 'ẅ' => 'w', 'Ẅ' => 'W', 'ý' => 'y', 'Ý' => 'Y', 'ỳ' => 'y', 'Ỳ' => 'Y', 'ŷ' => 'y', 'Ŷ' => 'Y', 'ÿ' => 'y', 'Ÿ' => 'Y', 'ź' => 'z', 'Ź' => 'Z', 'ž' => 'z', 'Ž' => 'Z', 'ż' => 'z', 'Ż' => 'Z', 'þ' => 'th', 'Þ' => 'Th', 'µ' => 'u', 'а' => 'a', 'А' => 'a', 'б' => 'b', 'Б' => 'b', 'в' => 'v', 'В' => 'v', 'г' => 'g', 'Г' => 'g', 'д' => 'd', 'Д' => 'd', 'е' => 'e', 'Е' => 'E', 'ё' => 'e', 'Ё' => 'E', 'ж' => 'zh', 'Ж' => 'zh', 'з' => 'z', 'З' => 'z', 'и' => 'i', 'И' => 'i', 'й' => 'j', 'Й' => 'j', 'к' => 'k', 'К' => 'k', 'л' => 'l', 'Л' => 'l', 'м' => 'm', 'М' => 'm', 'н' => 'n', 'Н' => 'n', 'о' => 'o', 'О' => 'o', 'п' => 'p', 'П' => 'p', 'р' => 'r', 'Р' => 'r', 'с' => 's', 'С' => 's', 'т' => 't', 'Т' => 't', 'у' => 'u', 'У' => 'u', 'ф' => 'f', 'Ф' => 'f', 'х' => 'h', 'Х' => 'h', 'ц' => 'c', 'Ц' => 'c', 'ч' => 'ch', 'Ч' => 'ch', 'ш' => 'sh', 'Ш' => 'sh', 'щ' => 'sch', 'Щ' => 'sch', 'ъ' => '', 'Ъ' => '', 'ы' => 'y', 'Ы' => 'y', 'ь' => '', 'Ь' => '', 'э' => 'e', 'Э' => 'e', 'ю' => 'ju', 'Ю' => 'ju', 'я' => 'ja', 'Я' => 'ja', "!" => "", "|" => "", "'" => "", "\"" => "", "'" => "", "@" => "", "·" => "", "#" => "", "$" => "", "¢" => "", "%" => "", "∞" => "", "¬" => "", "/" => "", "÷" => "", "(" => "", "“" => "", ")" => "", "”" => "", "≠" => "", "?" => "", "'" => "", "¡" => "", "¿" => "", "‚" => "", "´" => "", "^" => "", "`" => "", "[" => "", "*" => "", "+" => "", "]" => "", "¨" => "", "´" => "", "{" => "", "}" => "", "," => "", ";" => "", "„" => "", "." => "", ":" => "", "…" => "", "<" => "", ">" => "", "≤" => "", "≥" => "", "»" => "", "«" => "", "œ" => "", "æ" => "", "®" => "", "†" => "", "¥" => "", "π" => "", "∫" => "", "" => "", "™" => "", "¶" => "", "§" => "", "~" => "", "Ω" => "", "∑" => "", "©" => "", "√" => "", "µ" => "", "=" => "", "&" => "", " " => "-", "–" => "-", "_" => "-", " " => "-", '€' => 'e', 'º' => '', 'ª' => '', '&' => 'y', '\'' => '');
	$enlace = strtolower(str_replace(array_keys($transliterationTable), array_values($transliterationTable), $txt));
	$enlace = preg_replace("/([\-]+)/", "-", $enlace);
	return urlencode($enlace);
}

function webp($img, $size = null) {
	if (strpos(@$_SERVER['HTTP_USER_AGENT'], 'Chrome') === false && strpos(@$_SERVER['HTTP_USER_AGENT'], 'CriOS') === false) {
		return $img;
	}

	require_once dirname(__FILE__)."/lib/SimpleImage/SimpleImage.php";
	// Conseguimos el nombre del archivo y le quitamos la extensión
	$filename = pathinfo($img);
	$extension = $filename["extension"];
	$filename = $filename["filename"];

	$newPath = "/".CMS_FOLDER."/uploads/webp/".$filename.".webp";
	// Si no existe la versión webp la creamos
	if (!file_exists(dirname(__FILE__).$newPath)) {
		$image = new SimpleImage();
		try {
			if (@$size) {
				$image
					->fromFile(dirname(__FILE__).$newPath)
					->bestFit($size, 100000)
					->toFile(dirname(__FILE__).$newPath);
			}else{
				$image
					->fromFile(dirname(__FILE__).$img)
					->toFile(dirname(__FILE__).$newPath);
			}
			$dummy = ['filePath' => __DIR__.$newPath];
			addPlugins("upload_file", $dummy);
		}catch(Exception $err) {
			return $img;
		}
	}
	return $newPath;
}

function parsea_imagen($imagen,$url_completa=false){
	if (USAR_WEBP) $imagen = webp($imagen);
	if ($url_completa)
		return protocol()."://".$_SERVER["HTTP_HOST"].str_replace("plupload/multiupload/../../","",$imagen);
	else
		return str_replace("plupload/multiupload/../../","",$imagen);
}
?>
