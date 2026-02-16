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

// CARGA DE MODULOS POR AJAX
if (@$_REQUEST["modulo"]&&$_REQUEST["clave"]=="wscO4QaF"){
	if (!@$_REQUEST["datos"]) $datos=array(); else $datos=json_decode(base64_decode(@$_REQUEST["datos"]),true);
	die(modulo(@$_REQUEST["modulo"],$datos));
}

// ENVIO DE CORREOS POR AJAX
if (@$_REQUEST["enviar_correo"]&&$_REQUEST["clave"]=="wscO4QaF"){
	if (!@$_REQUEST["datos"]) $datos=array(); else $datos=json_decode(base64_decode(@$_REQUEST["datos"]),true);
	if ($datos["destinatarios"]){
		foreach($datos["destinatarios"] as $destinatario):
		//echo "Enviamos el correo a ".$destinatario." con el formulario de ".$datos["asunto"]."<br>".$datos["identificador"];
		enviarcorreo($destinatario,$datos["asunto"],base64_decode($datos["contenido"]));
		endforeach;
		die("<div class='alert alert-success'>".CORREO_ENVIADO."</div>");
	}else{
		die("<div class='alert alert-danger'>Error 4065456. Contacte con el administrador</div>");
	}
}

/* BOTONES DE COMPRA */
function boton_compra($p,$d=array()){
    global $configuracionRecord,$TABLE_PREFIX;
	extract(array("options" => $d));
	ob_start();
	require("./modulos_php/botones_compra/".$p.'/index.php');
	return ob_get_clean();
}

function array_merge_recursive_distinct ( array &$array1, array &$array2 )
{
  $merged = $array1;

  foreach ( $array2 as $key => &$value )
  {
    if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) )
    {
      $merged [$key] = array_merge_recursive_distinct ( $merged [$key], $value );
    }
    else
    {
      $merged [$key] = $value;
    }
  }

  return $merged;
}
/* FIN BOTONES DE COMPRA */

/******** NUEVAS FUNCIONES ********/

function dame_productos_relacionados($producto) {
	if (!@$producto["tags_bd"]) {
		$productos = dame_registros("productos", "num != ".$producto["num"], "visitas DESC", 4);
	}
	else {
		$sql = "";
		foreach ($producto["tags_bd"] as $cont => $tag):
		if (@$cont) $sql .= " OR ";
		$sql .= "tags LIKE '\t".$tag."\t'";
		endforeach;
		$productos = dame_registros("productos", "num != ".$producto["num"]." AND ".$sql, "visitas DESC", 4);
	}
	return $productos;
}

function dame_valoraciones_producto($producto) {
	return dame_registros("valoraciones_productos", "producto=".$producto["num"], "createdDate DESC");
}

function dame_media_valoracion_producto($producto) {
	$media = mysql_fetch_assoc(mysql_query("SELECT AVG(valoracion) AS media FROM cms_valoraciones_productos WHERE producto=".$producto["num"]));
	return parsea_decimales(@$media["media"]);
}

function dame_valoracion_producto($producto) {
	return mysql_fetch_assoc(mysql_query("SELECT * FROM cms_valoraciones_productos WHERE id_usuario='".$_COOKIE["userId"]."' AND producto=".$producto["num"]));
}

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

function dame_num_categorias_subcategorias($principal, &$lista) {
	$principal["subcategorias"] = dame_registros("categorias_productos", "parentNum=".$principal["num"]);
	array_push($lista, $principal["num"]);
	if (@$principal["subcategorias"]) {
		foreach ($principal["subcategorias"] as $i => $sub):
		$principal["subcategorias"][$i] = dame_num_categorias_subcategorias($sub, $lista);
		endforeach;
	}
	return $principal;
}

function dame_categorias_subcategorias($principal) {
	$principal["subcategorias"] = dame_registros("categorias_productos", "parentNum=".$principal["num"]);
	if (@$principal["subcategorias"]) {
		foreach ($principal["subcategorias"] as $i => $sub):
		$principal["subcategorias"][$i] = dame_categorias_subcategorias($sub);
		endforeach;
	}
	return $principal;
}

/**
 * Comprueba si el enlace del registro pasado por parámetro es al que estamos accediendo
 * o es un padre del que estamos accediendo.
 * @param $record: Registro con el enlace
 * @param $html: HTML a devolver en caso de match
 */
function comprueba_si_activo($record, $html = " class='active'") {
	if ($_SERVER["REQUEST_URI"] === RUTA_RAIZ."/" && @$record["controlador"] === "index.php") return $html;
	if (@t($record,"enlace") && strpos($_SERVER["REQUEST_URI"], t($record, "enlace")) !== false && $record["enlace"] != "/") {
		return $html;
	}
	return "";
}

function muestra_breadcrumb($record = array(), $previousLinks = array(),$class = "bg-gray-200 p-3 rounded font-sans w-full breadcrumb-v2") {
	global $TABLE_PREFIX;
	$enlaces = array();
	if (@$record) {
		array_unshift($enlaces, $record);
	}
	$cont = 0;
	$breadcrumbRecord = $record;
	
	while (true && $cont++ <= 30) { // Contador de seguridad para evitar el bucle infinito (que en teoría nunca pasará, jaja)
		// Comprobamos si la tabla ha cambiado para no volver a cargar el schema
		if (@$breadcrumbRecord["tableName"] != @$tabla) {
			$tabla = $breadcrumbRecord["tableName"];
			$breadcrumbField = @$breadcrumbRecord["breadcrumbField"];
		}
		
		if (!@$breadcrumbField || !@$breadcrumbRecord["optionsTablename"]) break;
		$breadcrumbRecord = dame_registros($breadcrumbRecord["optionsTablename"], $breadcrumbRecord["optionsValueField"]."=".mysql_real_escape_string($breadcrumbRecord[$breadcrumbField]));
		$breadcrumbRecord = @$breadcrumbRecord[0];

		if (!@$breadcrumbRecord) break;
		array_unshift($enlaces, $breadcrumbRecord);
	}

	if ($cont == 30) return; // Por si las moscas
	$i = 1;
	echo '<nav aria-label="Breadcrumb" class="'.$class.'">';
	echo '<ol itemscope itemtype="https://schema.org/BreadcrumbList" class="list-reset flex text-gray-700">';
	echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" class="text-black-500 font-bold"><a itemprop="item" href="'.RUTA_RAIZ.'/"><span itemprop="name">'.t_var("Inicio").'</span></a><meta itemprop="position" content="'.$i.'" /></li><li><span class="mx-2 text-gray-400">/</span></li>';
	$i++;
	if (@$previousLinks) {
		foreach ($previousLinks as $link):
		echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"><a itemprop="item" href="'.t($link, "enlace").'"><span itemprop="name">'.acorta_texto($link["mainFieldBreadcrumb"], 4).'</span></a><meta itemprop="position" content="'.$i.'" /></li><li><span class="mx-2 text-gray-400">/</span></li>';
		$i++;
		endforeach;
	}
	foreach ($enlaces as $cont => $enlace):
	if ($cont == count($enlaces)-1) {
		echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"><a itemprop="item" href="'.t($enlace, "enlace").'" aria-current="page"><span itemprop="name">'.acorta_texto(@$enlace["mainFieldBreadcrumb"], 4).'</span></a><meta itemprop="position" content="'.$i.'" /></li><li><span class="mx-2 text-gray-400">/</span></li>';
	}
	else {
		echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"><a itemprop="item" href="'.t($enlace, "enlace").'"><span itemprop="name">'.acorta_texto($enlace["mainFieldBreadcrumb"], 4).'</span></a><meta itemprop="position" content="'.$i.'" /></li><li><span class="mx-2 text-gray-400">/</span></li>';
	}
	$i++;
	endforeach;
	echo '</ol>';
	echo '</nav>';
}

function dame_precio_producto($producto) {
	$oferta = dame_oferta_producto($producto);
	if (@$oferta) {
		return parsea_decimales($producto["precio"])*(1-parsea_decimales($oferta["descuento"])/100);
	}
	return parsea_decimales($producto["precio"]);
}

function dame_precio_por_cantidades($producto, $cantidad) {
	if (!@$producto["precio_por_cantidades_bd"]) {
		return parsea_decimales($producto["precio"]);
	}

	// Ordenamos los precios por cantidades DESC
	usort($producto["precio_por_cantidades_bd"], function($a, $b) {
		return parsea_decimales($b["cantidad"]) - parsea_decimales($a["cantidad"]);
	});

	$restantes = intval($cantidad);
	$indice = count($producto["precio_por_cantidades_bd"])-1;
	$precio = 0;
	$i = 0;

	while ($restantes > 0 && $i <= 1000 && $indice >= 0) {
		$row = $producto["precio_por_cantidades_bd"][$indice];
		$row["cantidad"] = intval($row["cantidad"]);
		$row["precio"] = parsea_decimales($row["precio"]);
		if ($row["cantidad"] > $restantes) {
			$indice--;
			$i++;
			continue;
		}
		$restantes -= $row["cantidad"];
		$precio += $row["precio"];
		$i++;
	}
	if ($restantes === intval($cantidad)) {
		return parsea_decimales($producto["precio"]);
	}


	while ($restantes > 0) {
			$precio += parsea_decimales($producto["precio"]);
			$restantes--;
	}
	return $precio/intval($cantidad);
}

function dame_oferta_producto($producto, $variacion = null) {
	if (@$variacion) {
		$currentOffer = $currentOffer = dame_registros("ofertas", "producto=".intval(@$producto["num"])." AND variacion=".intval(@$variacion["num"])." AND desde <= '".date("Y-m-d H:i:s")."' AND hasta >= '".date("Y-m-d H:i:s")."'", "descuento DESC", 1);
		$currentOffer = @$currentOffer[0];
	}
	if (!@$currentOffer) {
		$currentOffer = dame_registros("ofertas", "producto=".intval(@$producto["num"])." AND (variacion=0 OR variacion IS NULL)  AND desde <= '".date("Y-m-d H:i:s")."' AND hasta >= '".date("Y-m-d H:i:s")."'", "descuento DESC", 1);
		$currentOffer = @$currentOffer[0];
	}
	return $currentOffer;
}

function parsea_precio($precio, $variacion = null) {
	if (@$variacion["precio"]) {
		return number_format(parsea_decimales($variacion["precio"]));
	}
	return number_format($precio, 2)." €";
}

function parsea_decimales($precio) {
	return floatval(str_replace(",", ".", $precio));
}

/******** FIN NUEVAS FUNCIONES ********/

function hasRecaptcha() {
	global $configuracionRecord;
	return @$configuracionRecord["site_key_recaptcha"] && @$configuracionRecord["secret_key_recaptcha"];
}

function dame_breadcrumb_categorias($categoria_num){
	global $categorias_id,$configuracionRecord;
	$lineage = array_filter(explode(":",$categorias_id[intval($categoria_num)]["lineage"]));
	foreach($lineage as $cont => $linea):
	$record = $categorias_id[intval($linea)];
	echo '<li class="breadcrumb-item"><a href="'.RUTA_RAIZ.'/'.t($configuracionRecord,"categorias").'/'.parsea_enlace(t($record,'name')).'/'.$record['num'].'.html">'.t($record,"name").'</a></li>';
	endforeach;
}

function dame_icono_adjuntos($archivo) {
	$extension = preg_replace("/(.*)(\.)([\w+])/", "$3", $archivo);
	$res = array("icono" => "save", "background" => "#555");
	switch ($extension) {
		case "mp4":
		case "avi":
		case "mov":
			$res["icono"] = "file-video-o";
			$res["background"] = "#F95759";
			break;
		case "mp3":
		case "ogg":
		case "aiff":
			$res["icono"] = "music";
			$res["background"] = "#98BF32";
			break;
		case "pdf":
			$res["icono"] = "file-pdf-o";
			$res["background"] = "#236421";
			break;
		case "doc":
		case "docx":
		case "odt":
		case "rtf":
		case "txt":
			$res["icono"] = "file-text";
			$res["background"] = "#2E5996";
			break;
		case "xls":
		case "xlsx":
			$res["icono"] = "file-excel-o";
			$res["background"] = "#0E713D";
			break;
		case "ppt":
		case "pptx":
			$res["icono"] = "file-powerpoint-o";
			$res["background"] = "#CE482F";
			break;
		case "jpg":
		case "jpeg":
		case "png":
		case "gif":
		case "bmp":
			$res["icono"] = "file-image-o";
			$res["background"] = "#599F7C";
			break;
	}
	return $res;
}

function acorta_texto($texto, $numeroDePalabras) {
	// Primero contamos el numero de palabras que hay
	$arrayPalabrasTexto = explode(" ", $texto);
	$numeroPalabrasEnTexto = count($arrayPalabrasTexto);
	// Si el numero de palabras es menor que las palabras del texto, las recortamos y devolvemos
	if ($numeroDePalabras < $numeroPalabrasEnTexto) {
		$arrayPalabrasTextoFinal = array();
		for ($i = 0; $i < $numeroDePalabras; $i++) {
			array_push($arrayPalabrasTextoFinal, $arrayPalabrasTexto[$i]);
		}
		return join(" ", $arrayPalabrasTextoFinal)."...";
	}else{
		// Si no, devolvemos el mismo texto
		return $texto;
	}
}

function elimina_registros($table, $where, $options = []) {
	return CocoDB::deleteRecords($table, $where, $options);
}

function actualiza_registros($table, $records, $where, $functions = [], $options = []) {
	return CocoDB::updateRecords($table, $records, $where, $functions, $options);
}

function inserta_registros($table, $records, $functions = [], $options = []) {
	return CocoDB::insertRecords($table, $records, $functions, $options);
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


function enviarcorreo($destinatario="publicidad@d2consulting.es",$asunto="Error al enviar correo",$contenido="",$respuesta=""){
	global $configuracionRecord;

	$correoadmin=$configuracionRecord["correo_admin"];
	$headers = "From: ".$correoadmin." \r\nContent-type: text/html; charset=iso-8859-1\r\n";

	$mensaje = "
	    <html>
	    <head>
	    	<title>".$asunto."</title>
	    	<style>
	    		body{font-family:Arial;color:#777;background-color:#fafafa}
	    		#contenido{max-width:640px;margin:0 auto;border:solid 1px #ddd;padding:20px;background-color:#fff}
	    		h3{font-weight:normal;color:#111;}
	    		table td{border:solid 1px #ddd;padding:5px;width:100%;margin:0px;}
				.table thead tr{background-color: #470A05;}
				.table thead tr th{color: #FED07C; font-size: 13px; border-bottom: none;}
				.table tbody tr:nth-child(even){background-color: rgba(253, 207, 124, 0.1);}
				.producto-cesta p{margin: 0;}
				.producto-cesta .plus i, .producto-cesta .minus{display: none;}
				.table th, .table td{width: 25%;}
				.table{width: 100%; max-width: 100%; margin-bottom: 1rem;}
	    	</style>
	    </head>
	    <body>
	    	<div id='contenido'>
				<center><img src='http://".$_SERVER["HTTP_HOST"]."/template/estandar/images/logo.png' style='max-width:200px;'></center>
		    	<br>
				".$contenido."
		    </div>
	    </body>
	    </html>
    ";

	$sfrom=$configuracionRecord["correo_admin"];
	$sdestinatario=$destinatario;
	$ssubject=$asunto;
	$shtml=$mensaje;
	$sheader = "From: ".$sfrom."\r\n";
	$sheader .= "MIME-Version: 1.0\r\n";
	$sheader .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
	$header = "";
	$header .= "Reply-To: ".$configuracionRecord["tienda_nombre_empresa"]." <".$configuracionRecord["correo_admin"].">\r\n";
	$header .= "Return-Path: ".$configuracionRecord["tienda_nombre_empresa"]." <".$configuracionRecord["correo_admin"].">\r\n";
	$header .= "From: ".$configuracionRecord["tienda_nombre_empresa"]." <".$configuracionRecord["correo_admin"].">\r\n";
	$header .= "Organization: ".$configuracionRecord["tienda_nombre_empresa"]."\r\n";
	$header .= "Content-Type: text/html; charset=UTF-8\r\n";

	if (mail($sdestinatario,$ssubject,$shtml,$header)){
		if ($respuesta!="") echo "<div class='box success'>".$respuesta."</div>";
	}else{
		echo '<div class="notice error"><i class="icon-remove-sign icon-large"></i>Fallo al enviar el mensaje <a href="#close" class="icon-remove"></a></div>';
	}
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

function hayhijoscategoriamenu($record,$layout=0,$where="AND visible_en_el_menu=1"){

	if ($record["layout"]==$layout){
		list($apartadosRecords, $apartadosMetaData) = getRecords(array(
			'tableName'   => 'apartados',
			'where'		=> 'parentNum='.$record["num"].' '.$where,
			'allowSearch' => 0,
		));
		return $apartadosRecords;
	}else{
		return false;
	}
}

function dame_mes($fecha){
	if (!@$_REQUEST["idioma"]) {
		$meses = array("","Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
		return $meses[date("n",strtotime($fecha))];
	}else{
		return date("F",strtotime($fecha));
	}
}

function parsea_fecha($fecha,$hora=false){
	global $subdominio;
	if (!@$_REQUEST["idioma"]) {
		$meses = array("","Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
		$resultado = date("d",strtotime($fecha))." de ".$meses[date("n",strtotime($fecha))]." de ".date("Y",strtotime($fecha));
		if ($hora) $resultado.=" a las ".date("H:i",strtotime($fecha));
	}else{
		$meses = array("","January","February","March","April","May","June","July","August","September","October","November","December");
		$sufijoDiaEnIngles = "";
		if (date("d" ,strtotime($fecha)) == 1 || date("d" ,strtotime($fecha)) == 21 || date("d" ,strtotime($fecha)) == 31) {
			$sufijoDiaEnIngles = "st";
		}else if(date("d" ,strtotime($fecha)) == 2 || date("d" ,strtotime($fecha)) == 22) {
			$sufijoDiaEnIngles = "nd";
		}else if(date("d" ,strtotime($fecha)) == 3 || date("d" ,strtotime($fecha)) == 23) {
			$sufijoDiaEnIngles = "rd";
		}else{
			$sufijoDiaEnIngles = "th";
		}
		$resultado = date("d",strtotime($fecha)).$sufijoDiaEnIngles." ".$meses[date("n",strtotime($fecha))]." ".date("Y",strtotime($fecha));
		if ($hora) $resultado.="at ".date("H:i",strtotime($fecha));
	}


	return $resultado;
}

function eliminar_espacios_innecesarios($string) {
	return preg_replace("/([ ]+)/", " ", trim($string));
}

// Parsea enlace definitivo
function parsea_enlace($txt) {
	$transliterationTable = array('á' => 'a', 'Á' => 'A', 'à' => 'a', 'À' => 'A', 'ă' => 'a', 'Ă' => 'A', 'â' => 'a', 'Â' => 'A', 'å' => 'a', 'Å' => 'A', 'ã' => 'a', 'Ã' => 'A', 'ą' => 'a', 'Ą' => 'A', 'ā' => 'a', 'Ā' => 'A', 'ä' => 'a', 'Ä' => 'A', 'æ' => 'ae', 'Æ' => 'AE', 'ḃ' => 'b', 'Ḃ' => 'B', 'ć' => 'c', 'Ć' => 'C', 'ĉ' => 'c', 'Ĉ' => 'C', 'č' => 'c', 'Č' => 'C', 'ċ' => 'c', 'Ċ' => 'C', 'ç' => 'c', 'Ç' => 'C', 'ď' => 'd', 'Ď' => 'D', 'ḋ' => 'd', 'Ḋ' => 'D', 'đ' => 'd', 'Đ' => 'D', 'ð' => 'dh', 'Ð' => 'Dh', 'é' => 'e', 'É' => 'E', 'è' => 'e', 'È' => 'E', 'ĕ' => 'e', 'Ĕ' => 'E', 'ê' => 'e', 'Ê' => 'E', 'ě' => 'e', 'Ě' => 'E', 'ë' => 'e', 'Ë' => 'E', 'ė' => 'e', 'Ė' => 'E', 'ę' => 'e', 'Ę' => 'E', 'ē' => 'e', 'Ē' => 'E', 'ḟ' => 'f', 'Ḟ' => 'F', 'ƒ' => 'f', 'Ƒ' => 'F', 'ğ' => 'g', 'Ğ' => 'G', 'ĝ' => 'g', 'Ĝ' => 'G', 'ġ' => 'g', 'Ġ' => 'G', 'ģ' => 'g', 'Ģ' => 'G', 'ĥ' => 'h', 'Ĥ' => 'H', 'ħ' => 'h', 'Ħ' => 'H', 'í' => 'i', 'Í' => 'I', 'ì' => 'i', 'Ì' => 'I', 'î' => 'i', 'Î' => 'I', 'ï' => 'i', 'Ï' => 'I', 'ĩ' => 'i', 'Ĩ' => 'I', 'į' => 'i', 'Į' => 'I', 'ī' => 'i', 'Ī' => 'I', 'ĵ' => 'j', 'Ĵ' => 'J', 'ķ' => 'k', 'Ķ' => 'K', 'ĺ' => 'l', 'Ĺ' => 'L', 'ľ' => 'l', 'Ľ' => 'L', 'ļ' => 'l', 'Ļ' => 'L', 'ł' => 'l', 'Ł' => 'L', 'ṁ' => 'm', 'Ṁ' => 'M', 'ń' => 'n', 'Ń' => 'N', 'ň' => 'n', 'Ň' => 'N', 'ñ' => 'n', 'Ñ' => 'N', 'ņ' => 'n', 'Ņ' => 'N', 'ó' => 'o', 'Ó' => 'O', 'ò' => 'o', 'Ò' => 'O', 'ô' => 'o', 'Ô' => 'O', 'ő' => 'o', 'Ő' => 'O', 'õ' => 'o', 'Õ' => 'O', 'ø' => 'o', 'Ø' => 'O', 'ō' => 'o', 'Ō' => 'O', 'ơ' => 'o', 'Ơ' => 'O', 'ö' => 'o', 'Ö' => 'O', 'ṗ' => 'p', 'Ṗ' => 'P', 'ŕ' => 'r', 'Ŕ' => 'R', 'ř' => 'r', 'Ř' => 'R', 'ŗ' => 'r', 'Ŗ' => 'R', 'ś' => 's', 'Ś' => 'S', 'ŝ' => 's', 'Ŝ' => 'S', 'š' => 's', 'Š' => 'S', 'ṡ' => 's', 'Ṡ' => 'S', 'ş' => 's', 'Ş' => 'S', 'ș' => 's', 'Ș' => 'S', 'ß' => 'SS', 'ť' => 't', 'Ť' => 'T', 'ṫ' => 't', 'Ṫ' => 'T', 'ţ' => 't', 'Ţ' => 'T', 'ț' => 't', 'Ț' => 'T', 'ŧ' => 't', 'Ŧ' => 'T', 'ú' => 'u', 'Ú' => 'U', 'ù' => 'u', 'Ù' => 'U', 'ŭ' => 'u', 'Ŭ' => 'U', 'û' => 'u', 'Û' => 'U', 'ů' => 'u', 'Ů' => 'U', 'ű' => 'u', 'Ű' => 'U', 'ũ' => 'u', 'Ũ' => 'U', 'ų' => 'u', 'Ų' => 'U', 'ū' => 'u', 'Ū' => 'U', 'ư' => 'u', 'Ư' => 'U', 'ü' => 'u', 'Ü' => 'U', 'ẃ' => 'w', 'Ẃ' => 'W', 'ẁ' => 'w', 'Ẁ' => 'W', 'ŵ' => 'w', 'Ŵ' => 'W', 'ẅ' => 'w', 'Ẅ' => 'W', 'ý' => 'y', 'Ý' => 'Y', 'ỳ' => 'y', 'Ỳ' => 'Y', 'ŷ' => 'y', 'Ŷ' => 'Y', 'ÿ' => 'y', 'Ÿ' => 'Y', 'ź' => 'z', 'Ź' => 'Z', 'ž' => 'z', 'Ž' => 'Z', 'ż' => 'z', 'Ż' => 'Z', 'þ' => 'th', 'Þ' => 'Th', 'µ' => 'u', 'а' => 'a', 'А' => 'a', 'б' => 'b', 'Б' => 'b', 'в' => 'v', 'В' => 'v', 'г' => 'g', 'Г' => 'g', 'д' => 'd', 'Д' => 'd', 'е' => 'e', 'Е' => 'E', 'ё' => 'e', 'Ё' => 'E', 'ж' => 'zh', 'Ж' => 'zh', 'з' => 'z', 'З' => 'z', 'и' => 'i', 'И' => 'i', 'й' => 'j', 'Й' => 'j', 'к' => 'k', 'К' => 'k', 'л' => 'l', 'Л' => 'l', 'м' => 'm', 'М' => 'm', 'н' => 'n', 'Н' => 'n', 'о' => 'o', 'О' => 'o', 'п' => 'p', 'П' => 'p', 'р' => 'r', 'Р' => 'r', 'с' => 's', 'С' => 's', 'т' => 't', 'Т' => 't', 'у' => 'u', 'У' => 'u', 'ф' => 'f', 'Ф' => 'f', 'х' => 'h', 'Х' => 'h', 'ц' => 'c', 'Ц' => 'c', 'ч' => 'ch', 'Ч' => 'ch', 'ш' => 'sh', 'Ш' => 'sh', 'щ' => 'sch', 'Щ' => 'sch', 'ъ' => '', 'Ъ' => '', 'ы' => 'y', 'Ы' => 'y', 'ь' => '', 'Ь' => '', 'э' => 'e', 'Э' => 'e', 'ю' => 'ju', 'Ю' => 'ju', 'я' => 'ja', 'Я' => 'ja', "!" => "", "|" => "", "'" => "", "\"" => "", "'" => "", "@" => "", "·" => "", "#" => "", "$" => "", "¢" => "", "%" => "", "∞" => "", "¬" => "", "/" => "", "÷" => "", "(" => "", "“" => "", ")" => "", "”" => "", "≠" => "", "?" => "", "'" => "", "¡" => "", "¿" => "", "‚" => "", "´" => "", "^" => "", "`" => "", "[" => "", "*" => "", "+" => "", "]" => "", "¨" => "", "´" => "", "{" => "", "}" => "", "," => "", ";" => "", "„" => "", "." => "", ":" => "", "…" => "", "<" => "", ">" => "", "≤" => "", "≥" => "", "»" => "", "«" => "", "œ" => "", "æ" => "", "®" => "", "†" => "", "¥" => "", "π" => "", "∫" => "", "" => "", "™" => "", "¶" => "", "§" => "", "~" => "", "Ω" => "", "∑" => "", "©" => "", "√" => "", "µ" => "", "=" => "", "&" => "", " " => "-", "–" => "-", "_" => "-", " " => "-", '€' => 'e', 'º' => '', 'ª' => '', '&' => 'y', '\'' => '');
	$enlace = strtolower(str_replace(array_keys($transliterationTable), array_values($transliterationTable), $txt));
	$enlace = preg_replace("/([\-]+)/", "-", $enlace);
	return urlencode($enlace);
}


function dame_paginacion($productosMetaData){
	if ($productosMetaData['totalPages']>1){
		/*if (@$_REQUEST["page"]){
			$productosMetaData["nextPageLink"] = str_replace("/pagina-".$_REQUEST["page"].".html","",$_SERVER["REQUEST_URI"])."/pagina-".$productosMetaData["nextPage"].".html";
			$productosMetaData["prevPageLink"] = str_replace("/pagina-".$_REQUEST["page"].".html","",$_SERVER["REQUEST_URI"])."/pagina-".$productosMetaData["prevPage"].".html";
		}else{
			$productosMetaData["nextPageLink"] = str_replace(".html","",$_SERVER["REQUEST_URI"])."/pagina-".$productosMetaData["nextPage"].".html";
			$productosMetaData["prevPageLink"] = str_replace(".html","",$_SERVER["REQUEST_URI"])."/pagina-".$productosMetaData["prevPage"].".html";
		}*/
		$sep_url = (strpos(@$_SERVER["REQUEST_URI"],"?page")) ? explode("?page",$_SERVER["REQUEST_URI"]) : explode("&page",$_SERVER["REQUEST_URI"]);
		$url_base = @$sep_url[0];
		$productosMetaData["nextPageLink"] = $url_base."?page=".$productosMetaData["nextPage"];
		$productosMetaData["prevPageLink"] = $url_base."?page=".$productosMetaData["prevPage"];;
?>
<div class="clearfix"></div>
<div class="post_pager">
	<div class="btn-group" role="group">
		<?php if ($productosMetaData['prevPage']): ?>
		<a class="btn btn-default" href="<?php echo $productosMetaData['prevPageLink'] ?>"><?=ANTERIOR;?></a>
		<?php else: ?>
		<a href="#"  class="btn btn-default"><?=ANTERIOR;?></a>
		<?php endif ?>

		<button type="button" class="btn btn-primary"><?=PAGINA;?> <?php echo $productosMetaData['page'] ?> <?=DE;?> <?php echo $productosMetaData['totalPages'] ?></button>

		<?php if ($productosMetaData['nextPage']): ?>
		<a href="<?php echo $productosMetaData['nextPageLink'] ?>"  class="btn btn-default"><?=SIGUIENTE;?></a>
		<?php else: ?>
		<a href="#"  class="btn btn-default"><?=SIGUIENTE;?></a>
		<?php endif ?>

	</div>
</div>
<div class="clearfix"></div>
<?
	}
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


function muestracategorias($padre=0,$subnivel=false,$actual=0){

	list($apartadosRecords, $apartadosMetaData) = getRecords(array(
		'tableName'   => 'categorias_productos',
		'where'		=> 'parentNum='.$padre,
		'allowSearch' => 0,
	));
	$cont2=0;

	foreach ($apartadosRecords as $cont => $record):
	$hijos = dame_registros("categorias_productos","parentNum=".$record["num"],"siblingOrder ASC");
	$icono = '';
	//if ($record["depth"]>0) $icono = '<i class="fa fa-angle-right"></i>&nbsp;&nbsp;';

	if (@$_REQUEST["categoria"]==$record["num"]) $clase="active"; else $clase="";

	if (!$hijos){
		echo '<li >';

		if (!$record["enlace"]){
			echo '<a href="'.ROOT.'/productos/'.$record["num"].'/'.parsea_enlace($record["name"]).'.html" class="'.$clase.'">'.$icono.''.$record[PREFIJO."name"].'</a>';
		}else{
			echo '<a href="'.$record["enlace"].'" class="'.$clase.'">'.$icono.''.$record[PREFIJO."name"].'</a>';
		}
		echo '</li>';
	}else{
		if ($record["parentNum"]==0) $clasedrop = (@$_REQUEST["categoria"]==$record["num"]) ? "active dropdown keep-open" : "dropdown keep-open"; else $clasedrop= (@$_REQUEST["categoria"]==$record["num"]) ? "active dropdown-submenu" : "dropdown-submenu";
		if ($record["parentNum"]==0) $multi = "multi-level"; else $multi="";

		echo '<li>';
		echo '<a href="#" class="'.$clase.'">'.$icono.''.$record[PREFIJO."name"].'</a>';
		echo '<ul class="lista-collapse">';
		muestracategorias($record["num"],true,$actual);
		echo '</ul>
				<div class="clearfix"></div>
				';
		echo '</li>';

	}

	endforeach;
	return $apartadosRecords;
}

function muestraservicios($apartado,$subnivel=false,$actual=0){
	global $configuracionRecord;

	if (!isset($GLOBALS["primer_nivel"])||$apartado["depth"]<=@$GLOBALS["primer_nivel"]["depth"]) $GLOBALS["primer_nivel"] = $apartado;

	list($apartadosRecords, $apartadosMetaData) = getRecords(array(
		'tableName'   => 'apartados',
		'where'		=> 'parentNum='.$apartado["num"].' AND visible_en_el_menu=1',
		'allowSearch' => 0,
	));
	$cont2=0;

	foreach ($apartadosRecords as $cont => $record):

	$icono = '';

	if (@$actual==$record["num"]) $clase="active"; else $clase="";

	echo '<li data-depth="'.$record["depth"].'" class="'.$clase.'">';

	if (!$record["enlace"]){
		if (hayhijoscategoriamenu($record,1)){
			echo '<a href="'.RUTA_RAIZ.'/'.t($configuracionRecord,"contenidos").'/'.parsea_enlace(t($record,"name")).'/'.$record["num"].'.html" class="">'.$icono.''.t($record,"name").'</a>';
		}else{
			if (!$subnivel){
				echo '<a href="'.RUTA_RAIZ.'/'.t($configuracionRecord,"contenidos").'/'.parsea_enlace(t($apartado,"name")).'/'.parsea_enlace(t($record,"name")).'/'.$apartado["num"].'/'.$record["num"].'.html" class="">'.$icono.''.t($record,"name").'</a>';
			}else{
				echo '<a href="'.RUTA_RAIZ.'/'.t($configuracionRecord,"contenidos").'/'.parsea_enlace(t($GLOBALS["primer_nivel"],"name")).'/'.parsea_enlace(t($record,"name")).'/'.$GLOBALS["primer_nivel"]["num"].'/'.$record["num"].'.html" class="">'.$icono.''.t($record,"name").'</a>';

			}

		}
	}else{
		echo '<a href="'.t($record,"enlace").'" class="">'.$icono.''.t($record,"name").'</a>';
	}
	echo '</li>';


	endforeach;
	return $apartadosRecords;
}

function muestramenu($parentNum=0,$where = "AND visible_en_el_menu=1"){
	global $configuracionRecord, $configuracionTienda, $categorias_cursos_id;
	list($apartadosRecords, $apartadosMetaData) = getRecords(array(
		'tableName'   => 'apartados',
		'where'		=> 'parentNum='.$parentNum.' '.$where,
		'allowSearch' => 0,
	));
	$cont2=0;

	foreach ($apartadosRecords as $cont => $record):

	$icono = '';

	if (!hayhijoscategoriamenu($record,0,$where)){
		echo '<li class="list-inline-item'.comprueba_si_activo($record, " active").'">';
		$hijo = hayhijoscategoriamenu($record,1,$where);
		$hijo=@$hijo[0];
		if (@$hijo){
			if (@$hijo["enlace"]){
				$enla = t($hijo,"enlace");
				if ($enla==$hijo["enlace"]) $enla = RUTA_RAIZ.$enla;
				echo '<a href="'.$enla.'" class="">'.$icono.''.t($record,"name").'</a>';
			}else{
				echo '<a href="'.RUTA_RAIZ.'/'.t($configuracionRecord,"contenidos").'/'.parsea_enlace(t($record,"name")).'/'.parsea_enlace(t($hijo,"name")).'/'.$record["num"].'/'.$hijo["num"].'.html" class="">'.$icono.''.t($record,"name").'</a>';
			}
		}else{
			if ($record["enlace"]){
				$enla = t($record,"enlace");
				if ($enla==$record["enlace"]) $enla = RUTA_RAIZ.$enla;
				echo '<a href="'.$enla.'" class="">'.$icono.''.t($record,"name").'</a>';
			}else{
				echo '<a href="'.RUTA_RAIZ.'/'.t($configuracionRecord,"apartados")."/".parsea_enlace(t($record,"name")).'/'.$record["num"].'.html" class="">'.$icono.''.t($record,"name").'</a>';
			}
		}

		echo '</li>';
	}else{
		if ($record["parentNum"]==0) $clasedrop = "dropdown"; else $clasedrop="dropdown-submenu";
		echo '<li class="'.$clasedrop.comprueba_si_activo(" active").'">';
		echo '<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="true">'.$icono.''.t($record,"name").'</a>';
		echo '<ul class="dropdown-menu multi-level" role="menu" aria-labelledby="dropdownMenu">';
		muestramenu($record["num"],$where);
		echo '</ul>';
		echo '</li>';

	}
	endforeach;
	
	if (HAY_TIENDA) {
		foreach ($configuracionTienda["categorias_nav_bd"] as $categoria):
		echo '<li class="visible-xs'.comprueba_si_activo($categoria, " active").'">';
		echo '<a href="'.t($categoria, "enlace").'" data-category="'.$categoria["num"].'">';
		if (@$categoria["icono"]) {
			echo '<img src="'.parsea_imagen($categoria["icono"][0]["urlPath"]).'" alt="'.addslashes(t($categoria, "name")).'">';
		}
		echo t($categoria, "name");
		echo '</a></li>';
		endforeach;
	}
	
	return $apartadosRecords;
}
?>
