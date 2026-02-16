<?
$index=true;
include("header.php");

$apartado = dame_registros("apartados", "enlace LIKE '%index%'", "siblingOrder DESC", 1);
$apartado = @$apartado[0];

$configuracionRecord["titulo_de_pagina"] = t($apartado,"name")." - ".$configuracionRecord["titulo_de_pagina"];

if (@$apartado["titulo_de_pagina"]!="") $configuracionRecord["titulo_de_pagina"] = t($apartado,"titulo_de_pagina");
if (@$apartado["metatag_descripcion"]!="") $configuracionRecord["metatag_descripcion"] = t($apartado,"metatag_descripcion");
if (@$apartado["metatag_palabras"]!="") $configuracionRecord["metatag_palabras"] = t($apartado,"metatag_palabras");

$portada = dame_registros("portada", "", "num DESC", 1);
$portada = @$portada[0];

$config_portada 	= 	array(
		                'portada'			=>	$portada,
						'apartado'			=>	@$apartado
					);

echo tpl("portada", $config_portada);
?>

<? include("footer.php");?>
