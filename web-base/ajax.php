<?
require_once("sesion.php");
require_once("funciones.php");


if (@$_GET["getFeaturedCategory"]) {
	header("Content-Type: application/json");
	$res = array("error" => null);
	$categoria = dame_registros("categorias_productos", "num='".mysql_real_escape_string($_GET["getFeaturedCategory"])."'");
	$categoria = @$categoria[0];
	if (!@$categoria) {
		$res["error"] = "No se ha encontrado la categoría solicitada";
		die(json_encode($res));
	}

	// Buscamos categorías hijas de la categoría que estamos buscando
	$categoriasPosibles = array();
	dame_num_categorias_subcategorias($categoria, $categoriasPosibles);
	if (!@$categoriasPosibles) {
		$res["error"] = "No se ha encontrado la categoría solicitada o ninguna descendiente";
		die(json_encode($res));
	}
	$productos = dame_registros("productos", "categoria IN(".join(",", $categoriasPosibles).")", "visitas DESC", 4);

	if (!@$productos) {
		$res["error"] = "No hay productos de esta categoría";
		die(json_encode($res));
	}

	$subcategorias = dame_registros("categorias_productos", "parentNum=".$categoria["num"], "name ASC", 5);

	$res["html"] = '<button class="close"><i class="fa fa-times"></i></button>';
	$res["html"] .= '<div class="container">';
	$res["html"] .= '<a href="'.t($categoria, "enlace").'"><h3>'.t($categoria, "name").'&nbsp;<i class="fa fa-angle-right"></i></h3></a>';
	$res["html"] .= '<p class="sub">'.acorta_texto(t($categoria, "descripcion"), 20).'</p>';
	$res["html"] .= '<div class="wrapper-flex no-flex-xs">';
	if (@$subcategorias) {
		$res["html"] .= '<div class="col-sm-4">
			<h4>'.t_var("Subcategorías").'</h4>
			<div class="separa-20"></div>
			<ul class="list-group subcategorias">
				'.join("", array_map(function($a) {
					return '<li class="list-group-item"><a href="'.t($a, "enlace").'">'.t($a, "name").'</a></li>';
				}, $subcategorias)).'
			</ul>
		</div>';
	}

	$producto = array_shift($productos);
	$res["html"] .= '<div class="col-sm-4">
	<div class="product-wrapper principal">
		<a href="'.t($producto, "enlace").'">
			<div class="img">
				<img src="'.parsea_imagen(@$producto["foto"][0]["urlPath"]).'" alt="'.t($producto, "name").'">
			</div>
		</a>
		<a href="'.t($producto, "enlace").'"><h4 class="titular">'.t($producto, "name").'</h4></a>
	</div>
</div>';
	if (@$productos) {
		$res["html"] .= '<div class="col-sm-4 product-list">';
		foreach ($productos as $i => $producto):
		$res["html"] .= '
	<div class="product-wrapper">
		<a href="'.t($producto, "enlace").'">
			<div class="img">
				<img src="'.parsea_imagen(@$producto["foto"][0]["urlPath"]).'" alt="'.t($producto, "name").'">
			</div>
		</a>
		<a href="'.t($producto, "enlace").'"><h4>'.t($producto, "name").'</h4></a>
	</div>';
		endforeach;
		$res["html"] .= '</div>';
	}
	$res["html"] .= '</div></div>';
	die(json_encode($res));
}

?>