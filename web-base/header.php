<?
require_once ("sesion.php");
if (isset($_GET["pruebas"])) $_SESSION["pruebas"]=true;

require_once "funciones.php";
if (!isset($current)) $current=0;

if (HAY_TIENDA) {
	$categorias = dame_registros("categorias_productos", "parentNum=0", "siblingOrder ASC");
}

$idiomas = dame_idiomas();

$idiomaSeleccionado = "Español";

foreach ($idiomas as $idioma):

	if ($idioma["valor"]=="/".@$_REQUEST["idioma"]) $idiomaSeleccionado=$idioma["idioma"];
endforeach;

$apartadoCesta = dame_registros("apartados", "controlador='cesta.php'");
$apartadoCesta = @$apartadoCesta[0];

$apartadoFavoritos = dame_registros("apartados", "controlador='favoritos.php'");
$apartadoFavoritos = @$apartadoFavoritos[0];

$apartadoLogin = dame_registros("apartados", "controlador='login.php'");
$apartadoLogin = @$apartadoLogin[0];

$apartadoRegistro = dame_registros("apartados", "controlador='registro.php'");
$apartadoRegistro = @$apartadoRegistro[0];

$alternate = dame_alternates($_SERVER["REQUEST_URI"]);

$config_header 	= 	array(
		                'titulo_de_pagina'	=>	$configuracionRecord["titulo_de_pagina"],
		                'categorias'		=>	@$categorias,
						'pagina_publicada'	=>	$configuracionRecord["pagina_publicada"],
		                'current'			=>	$current,
		                'portada'			=>	@$portada,
		                'index'				=>	@$index,
		                'configuracionRecord' => $configuracionRecord,
		                'configuracionTienda' => $configuracionTienda,
		                'meta_descripcion'	=>  $configuracionRecord["metatag_descripcion"],
		                'meta_palabras'		=>  @$configuracionRecord["metatag_palabras"],
		                'otros_metas'		=>	@$otros_metas,
		                'color_base'		=>	@$color_base,
						'categorias'		=>	@$categorias,
		                'idiomas'			=>	@$idiomas,
		                'idiomaSeleccionado'=>  @$idiomaSeleccionado,
						'apartadoCesta'		=>	@$apartadoCesta,
						'apartadoFavoritos'	=>	@$apartadoFavoritos,
						'apartadoLogin'		=>	@$apartadoLogin,
						'apartadoRegistro'		=>	@$apartadoRegistro,
						'sitebuilder'		=>	@$apartado["plantilla_sitebuilder"],
						'alternate'			=>	@$alternate
					);

if ($configuracionRecord["pagina_publicada"]){
	
	if (@$customCode) echo modulo('custom-header',$config_header); else echo tpl('cabecera',$config_header);	
}else{
	if (!isset($_SESSION["pruebas"])){
		die(tpl('mantenimiento',$config_header));
		
	}else{
		if (@$customCode) echo modulo('custom-header',$config_header); else echo tpl('cabecera',$config_header);	
		if (isset($_SESSION["pruebas"])){
			if ($configuracionRecord["pagina_publicada"]==0){
			?>
			<div style="position:absolute;top:0px;width:150px;background-color:red;color:#fff;font-size:12px;padding:0 10px;z-index:99999999;transform:translateZ(1000px);">SITIO EN PRUEBAS</div>
			<?
			}
		}
	}
	
}
?>