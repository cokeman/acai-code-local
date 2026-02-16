<?
require_once ("sesion.php");
require_once "funciones.php";

$apartado = dame_registros("apartados", "num=".intval(@$_REQUEST["num"]), "num DESC", 1);
$apartado = @$apartado[0]; // get first record
$configuracionRecord["titulo_de_pagina"] = t($apartado,"name")." - ".$configuracionRecord["titulo_de_pagina"];

if (@$apartado["titulo_de_pagina"]!="") $configuracionRecord["titulo_de_pagina"] = t($apartado,"titulo_de_pagina");
if (@$apartado["metatag_descripcion"]!="") $configuracionRecord["metatag_descripcion"] = t($apartado,"metatag_descripcion");
if (@$apartado["metatag_palabras"]!="") $configuracionRecord["metatag_palabras"] = t($apartado,"metatag_palabras");

if (!@$apartado) {
	header("HTTP/1.0 404 Not Found");
	include("header.php");
	$apartado = [
		"name" => '<div class="text-center"><span class="text-6xl font-bold text-gray-600">404</span></div>',
		"content" => '<div class="text-center text-lg">'.t_var("La Página solicitada no existe. Disculpe las molestias")."<br><br><a href='/' class='p-4 border rounded-lg hover:bg-gray-300'>".t_var("Ir a inicio")."</a></div><style>.breadcrumb-v2{display:none;}</style>"
	];
	echo tpl("apartados",array("apartado" => $apartado));
	include "footer.php";
	die();
}
include("header.php");

$portada = dame_registros("portada","","",1);$portada = @$portada[0];

$config_apartados 	= 	array(
						'portada'			=>	@$portada,
						'apartado'			=>	$apartado
					);
echo tpl('apartados',$config_apartados);

include("footer.php");
?>
