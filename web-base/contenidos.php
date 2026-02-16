<?
require_once ("sesion.php");
require_once "funciones.php";
$apartado = dame_registros("apartados", "num=".intval($familia));
$apartado=@$apartado[0];

$contenidosRecords = dame_registros("apartados", "parentNum=".intval($familia));

if (isset($num)) $cadena="num=".$num; else $cadena="parentNum=".$familia;
$contenidoRecord = dame_registros("apartados", $cadena);
$contenidoRecord = @$contenidoRecord[0];

if (@$_REQUEST["num"]){
	$configuracionRecord["titulo_de_pagina"] = t($contenidoRecord,"name")." - ".$configuracionRecord["titulo_de_pagina"];
	
	if (@$contenidoRecord["titulo_de_pagina"]!="") $configuracionRecord["titulo_de_pagina"] = t($contenidoRecord,"titulo_de_pagina");
	if (@$contenidoRecord["metatag_descripcion"]!="") $configuracionRecord["metatag_descripcion"] = t($contenidoRecord,"metatag_descripcion");
	if (@$contenidoRecord["metatag_palabras"]!="") $configuracionRecord["metatag_palabras"] = t($contenidoRecord,"metatag_palabras");
}else{
	$configuracionRecord["titulo_de_pagina"] = t($apartado,"name")." - ".$configuracionRecord["titulo_de_pagina"];

	if (@$apartado["titulo_de_pagina"]!="") $configuracionRecord["titulo_de_pagina"] = t($apartado,"titulo_de_pagina");
	if (@$apartado["metatag_descripcion"]!="") $configuracionRecord["metatag_descripcion"] = t($apartado,"metatag_descripcion");
	if (@$apartado["metatag_palabras"]!="") $configuracionRecord["metatag_palabras"] = t($apartado,"metatag_palabras");
}

$layout = "contenidos";

include("header.php");

if (!@$apartado) {echo tpl("apartados",array("apartado" => array("name" => "Error","enlace" => ""), "nombre" => PAGINA_NO_ENCONTRADA,"contenido" => PAGINA_NO_ENCONTRADA_TEXTO));die();}
if (!@$contenidoRecord) {echo tpl("apartados",array("apartado" => array("name" => "Error","enlace" => ""), "nombre" => PAGINA_NO_ENCONTRADA,"contenido" => PAGINA_NO_ENCONTRADA_TEXTO));die();}

echo tpl($layout,array(
						"apartado"				=>	$apartado,
						"apartado_nombre"		=>	t($apartado,"name"),
						"sub_contenidos"		=>	$contenidosRecords,
						"contenido"				=>	$contenidoRecord,
						"contenido_nombre"		=>	t($contenidoRecord,"name"),
						"contenido_contenido"	=>	t($contenidoRecord,"content"),
					)
);
?>
<?
function enlace_contenido($record){
	return ROOT."/contenidos/".$_GET["familia"]."/".$_GET["familia_name"]."/".$record["num"]."/".parsea_enlace(t($record,"name")).".html";
}
function titulo_subcontenido($sub){
	return $sub[PREFIJO."name"];
}
?>
<?include("footer.php");?>						
<script type="text/javascript">$(".apartados li[ident='<?=$familia;?>']").addClass("current");</script>
