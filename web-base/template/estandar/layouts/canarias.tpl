<div class="header-fix"></div>
<section id="banner-canarias">
	<ul class="slider">
		<? foreach (@$portada["banner"] as $cont => $banner):?>
		<li>
			<div class="imagen" style="background-image: url('<?=parsea_imagen($banner["urlPath"]);?>');"></div>
			<div class="content">
				<div>
					<? if (@$cont) {?>
					<h1 class="wow fadeInLeft" data-wow-delay="0.5s"><?=t($banner, "info1");?></h1>
					<?}
					else {?>
					<h2 class="wow fadeInLeft" data-wow-delay="0.5s"><?=t($banner, "info1");?></h2>
					<?}?>
					<? if (@$banner["info2"]) {?>
					<div class="separador wow fadeIn" data-wow-delay="0.5s"></div>
					<h4 class="wow fadeInLeft" data-wow-delay="0.5s"><?=t($banner, "info2");?></h4>
					<?}?>
				</div>
			</div>
			<div class="overlay"></div>
		</li>
		<? endforeach;?>
	</ul>
</section>
<section>
	<div class="separa-40"></div>
	<div class="separa-40"></div>
	<div class="container">
		<h2 class="titular"><?=t($portada, "titulo_seccion_1");?></h2>
		<div class="separa-20"></div>

		<?=str_replace("<p", "<p class='text-lg'", t($portada, "texto_seccion_1"));?>
		<div class="separa-40"></div>
		<ul class="bloques-canarias">
			<? foreach ($portada["bloques_seccion_1"] as $bloque):?>
			<li class="col-sm-4">
				<div>
					<img src="<?=parsea_imagen($bloque["urlPath"]);?>" alt="<?=t($bloque, "info1");?>">
					<h3><?=t($bloque, "info1");?></h3>
					<p><?=t($bloque, "info2");?></p>
				</div>
			</li>
			<? endforeach;?>
		</ul>
	</div>
	<div class="separa-40"></div>
</section>

<section class="gray">
	<div class="separa-40"></div>
	<div class="separa-40"></div>
	<div class="container">
		<h2 class="titular"><?=t($portada, "titulo_seccion_2");?></h2>
		<div class="separa-20"></div>
		<?=str_replace("<p", "<p class='text-lg'", t($portada, "texto_seccion_2"));?>
		<? $puntos = json_decode(@$portada["puntos_seccion_2"], true);?>
		<? if (@$puntos) {?>
		<div class="separa-40"></div>
		<div class="col-sm-8 col-sm-offset-2">
			<ul class="checked">
				<? foreach ($puntos as $punto):?>
				<li><?=$punto["texto"];?></li>
				<? endforeach;?>
			</ul>
		</div>
		<?}?>
	</div>
	<div class="separa-40"></div>
	<div class="separa-40"></div>
</section>

<section>
	<div class="container">
		<div class="row flex no-flex-xs">
			<div class="col-sm-6 image-wrapper">
				<div class="imagen" style="background-image: url('<?=parsea_imagen(@$portada["imagen_principal_seccion_3"][0]["urlPath"]);?>');"></div>
			</div>
			<div class="col-sm-6">
				<div class="separa-40"></div>
				<div class="separa-40"></div>
				<h2 class="titular left"><?=t($portada, "titulo_seccion_3");?></h2>
				<div class="separa-20"></div>
				<?=t($portada, "texto_seccion_3");?>
				<div class="separa-40"></div>
				<div class="separa-40"></div>
			</div>
		</div>
	</div>
	<? if (@$portada["galeria_seccion_3"]) {?>
	<?=modulo("galeria_interno", array("galeria" => $portada["galeria_seccion_3"], "sin_titulo" => true));?>
	<?}?>
</section>

<section>
	<div class="separa-40"></div>
	<div class="separa-40"></div>
	<div class="container">
		<div class="col-sm-8 col-sm-offset-2">
			<h2 class="titular"><?=t($portada, "titulo_contacto");?></h2>
			<p><?=t($portada, "texto_contacto");?></p>
			<?= t(array("contenido" => "{FORMULARIO_SOLICITAR}"), "contenido", array("clases" => "btn btn-lg btn-outline btn-primary", "widget" => true, "tipo" => "inline")) ?>
		</div>
	</div>
	<div class="separa-40"></div>
	<div class="separa-40"></div>
</section>