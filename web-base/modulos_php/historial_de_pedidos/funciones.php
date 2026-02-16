<?
require_once str_replace("/modulos_php/historial_de_pedidos","",dirname(__FILE__))."/sesion.php";
require_once str_replace("/modulos_php/historial_de_pedidos","",dirname(__FILE__))."/funciones.php";

function muestra_historial($usuario){
	$categorias = dame_registros_con_id("categorias_productos","","siblingOrder ASC");
	$pedidos = dame_registros("posibles_pedidos", "usuario=".$usuario["num"]." AND (estado_pedido = 1 OR estado_pedido = 2)");
	if (@$pedidos) {
		$estado = array(t_var("Esperando pago"),t_var("Pago recibido"),t_var("Enviado"));
	?>
	<ul class="lista-pedidos">
		<? foreach ($pedidos as $pedido):?>
		<li>
			<header class="header">
				<?=strtr(t_var("Cabecera de pedido"), array(
					"{FECHA}" => date("d/m/Y", strtotime($pedido["createdDate"])),
					"{REFERENCIA}" => @$pedido["title"]
				));?>
			</header>
			<? $lineas = dame_registros("lineas_de_compra", "pedido=".$pedido["num"]);?>
			<? foreach ($lineas as $linea):
				$producto = dame_registros("productos", "num=".$linea["producto"]); $producto = @$producto[0];
				if (!@$producto) continue;
				$variacion = mysql_fetch_assoc(mysql_query("SELECT * FROM aux_variaciones_productos WHERE num=".intval(@$linea["variacion"])));
				$img = @$variacion["foto"] ? $variacion["foto"] : @$producto["foto"][0]["urlPath"];
				$referencia = @$variacion["referencia"] ? $variacion["referencia"] : @$producto["title"];
			?>

				<div class="pedido">
					<div class="left">
						<div class="img">
							<img src="<?=parsea_imagen($img);?>" alt="<?=t($producto, "name");?>">
						</div>
						<div class="separa-10"></div>
						<a href="<?=t($producto, "enlace");?>" target="_blank" class="btn btn-success btn-block"><?=t_var("Ver producto");?></a>
					</div>
					<div>
						<h4><?=t($producto, "name");?> ( <?=intval($linea["cantidad"]);?> )</h4>
						<div class="separa-10"></div>
						<p><strong><?=t_var("Estado");?>: <?=$estado[intval($pedido["estado_pedido"])%3];?></strong></p>
						<? if ($pedido["estado_pedido"] == 2) include dirname(__FILE__)."/../modulo_valoraciones.php";?>
					</div>
				</div>
			<? endforeach;?>
		</li>
		<? endforeach;?>
	</ul>
	<? } else {?>
		<p class="text-center"><?=t_var("No has realizado ningún pedido");?></p>
	<?}
}
?>
