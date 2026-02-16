
<section id="contenido_principal">
	<div class="container mx-auto">
		<div class="h-4"></div>
		<? muestra_breadcrumb($apartado);?>
		<div class="h-4"></div>
		<h1 class="titular"><?=@$apartado["titulo_alternativo"] ? t($apartado, "titulo_alternativo") : t($apartado, "name");?></h1>
		<div class="h-2"></div>
		<div class="text-gray-600 text-xl">
			<?=t($apartado,"content");?>
		</div>
	</div>
	<div class="h-4"></div>

	<? if (@$apartado["galeria_de_fotos"]) {?>
	<div class="container">
		<?=modulo("galeria_interno", array("galeria" => $apartado["galeria_de_fotos"]));?>
	</div>
	<div class="h-4"></div>
	<?}?>

	<? if (@$apartado["archivos_adjuntos"]) {?>
	<div class="container">
		<?=modulo("adjuntos_interno", array("adjuntos" => $apartado["archivos_adjuntos"]));?>
		<div class="h-4"></div>
	</div>
	<?}?>
	<div class="h-12"></div>
</section> 