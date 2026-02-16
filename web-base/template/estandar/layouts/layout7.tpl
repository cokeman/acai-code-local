<div id="banner" class="wrapper-flex2 wrapper-left2">
	<ul class="slideshow2">
		<? foreach($portada["banner"] as $banner):?>
		<li class="wrapper-flex wrapper-left">
			<div class="imagen" style="background-image: url('<?=parsea_imagen($banner["urlPath"]) ?>');"></div>
			<? if (@$banner["info1"] || @$banner["info2"]) {?>
			<div>
				<div class="col-md-6 col-sm-8">
					<h4 class="wow fadeInDown"><?=t($banner,"info1"); ?></h4>
					<h1 class="wow fadeInUp"><?=t($banner,"info2"); ?></h1>
					<div class="horizontal big wow fadeInRight" data-wow-delay="1s"></div>
				</div>
				<div class="clearfix"></div>
			</div>
			<?}?>
		</li>
		<? endforeach;?>
	</ul>
</div>

<section>
	<div class="separa-40"></div>
	<div class="separa-40"></div>
	<div class="container">
		<div class="row">

			<h2 class="titular text-center"><?=t($portada,"titulo_bienvenida") ?></h2>
			<div class="horizontal center"></div>
			<p class="subtitulo"><?=t($portada,"texto_bienvenida") ?></p>

		</div>
		<div class="row">
			<div class="separa-40"></div>
			<div class="separa-40"></div>
			<div class="call-to-action-portada">
				<div class="col-sm-10 col-sm-offset-1">
					<div class="row">
						<ul>

							<?  foreach($portada["bloques_de_servicio"] as $cont => $servicio): ?>
							<li class="col-sm-4 wow fadeIn" data-wow-delay="0s">
								<a href="<?=t($servicio, "info2");?>">
									<div>
										<div class="image-wrapper"><div class="imagen" style="background-image: url('<?=parsea_imagen($servicio["urlPath"]); ?>');"></div></div>
										<h3><?=t($servicio,"info1") ?></h3>

									</div>
								</a>
							</li>

							<? endforeach; ?> 

						</ul>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="separa-40"></div>
	<div class="separa-40"></div>
</section>

<section id="newsletter" style="background-image: url('<?=parsea_imagen($portada["fondo_newsletter"][0]["urlPath"]) ?>');">
	<div class="separa-40"></div>
	<div class="separa-40"></div>
	<div class="container">
		<h2 class="text-center"><?=t($portada,"titulo_newsletter") ?></h2>
		<div class="separa-40"></div>
		<p class="subtitulo"><?=t($portada,"texto_newsletter")?></p>
		<div class="separa-40"></div>
		<form class="form-inline" method="post" action="http://mailing.abanico.net/t/y/s/sdtlud/">
			<div>
				<div class="form-group">
				    <input type="text" class="form-control" placeholder="Nombre" name="cm-name">
					<input type="email" class="form-control" placeholder="Email" name="cm-sdtlud-sdtlud" required>
				</div>
				<button type="submit" class="btn btn-primary">Suscribirme</button>
			</div>
		</form>
	</div>
	<div class="separa-40"></div>
	<div class="separa-40"></div>
</section>

<section>

	<div class="separa-40"></div>
	<div class="separa-40"></div>
	<div class="container">
		<h2 class="titular text-center"><?=t($portada,"titulo_proyectos");?></h2>
		<div class="horizontal center"></div>
		<p class="subtitulo"><?=t($portada,"texto_ultimos_proyectos") ?></p>
		<div class="separa-40"></div>
        <? if (@$proyectos){?>
		<ul class="flex">
		    
			<? foreach ($proyectos as $count => $proyecto): ?>
			<? $enlace = @$proyecto["enlace"] ? t($proyecto, "enlace") : RUTA_RAIZ."/".t($configuracionRecord, "proyectos")."/".parsea_enlace(t($proyecto, "title"))."/".$proyecto["num"].".html";?>
			<? if($count%2 == 0){ ?>
			<li class="bloque-servicio">
				<a href="<?=$enlace;?>">
					<div class="flex">
						<div class="image-wrapper"><div class="imagen" style="background-image: url('<?=parsea_imagen($proyecto["foto_principal"][0]["urlPath"]);?>');"></div></div>

						<div class="content wrapper-flex triangle-left">

							<div class="wow fadeIn">
								<h3><?=t($proyecto,"title") ?></h3>
								<div class="separa-10"></div>
								<p><?=t($proyecto,"descripcion_corta") ?></p>
							</div>
						</div>
					</div>
				</a>
			</li>
			<? } endforeach; ?>

			<? foreach($proyectos as $count => $proyecto): ?>
			<? $enlace = @$proyecto["enlace"] ? t($proyecto, "enlace") : RUTA_RAIZ."/".t($configuracionRecord, "proyectos")."/".parsea_enlace(t($proyecto, "title"))."/".$proyecto["num"].".html";?>
			<? if($count%2 != 0){ ?>
			
			<li class="bloque-servicio">
				<a href="<?=$enlace;?>">
					<div class="flex">
						<div class="content wrapper-flex triangle-right">

							<div class="wow fadeIn">
								<h3><?=t($proyecto,"title") ?></h3>
								<div class="separa-10"></div>
								<p><?=t($proyecto,"descripcion_corta") ?></p>
							</div>
						</div>
						<div class="image-wrapper"><div class="imagen" style="background-image: url('<?=(@$proyecto["foto_principal"]) ? parsea_imagen($proyecto["foto_principal"][0]["urlPath"]) : RUTA_PLANTILLA."/images/image_default.jpg";?>');"></div></div>


					</div>
				</a>
			</li>
			<? } endforeach; ?>

		</ul>
		<?}else{?>
		<pre>Los proyectos no han sido definidos en el controlador</pre>
		<?}?>
	</div>
</section>


<section>
	<div class="separa-40"></div>
	<div class="separa-40"></div>
	<div class="container">
		<h2 class="titular text-center"><?=t($portada,"titulo_ultimas_noticias"); ?></h2>
		<div class="horizontal center"></div>
		<p class="subtitulo"><?=t($portada,"texto_ultimas_noticias") ?></p>
		<div class="separa-40"></div>
		<? if (@$blog){?>
		<ul>
			<? foreach($blog as $noticia): ?>
			<? $enlace = @$noticia["enlace"] ? t($noticia, "enlace") : RUTA_RAIZ."/".t($configuracionRecord, "noticias")."/".parsea_enlace(t($noticia, "title"))."/".$noticia["num"].".html";?>
			<li class="bloque-noticia wow fadeIn" data-wow-delay="0.25s">
				<a href="<?=$enlace;?>">
					<div>
						<div class="imagen" style="background-image: url(<?=parsea_imagen($noticia["foto_principal"][0]["urlPath"]) ?>);"></div>
						<div class="overlay wrapper-flex">
							<div>
								<h3><?=t($noticia,"title") ?>.</h3>
								<div class="horizontal"></div>
								<p><?=t($noticia,"subtitulo") ?></p>
							</div>
						</div>
					</div>
				</a>
			</li>
			<? endforeach ?>

		</ul>
		<? }else{?>
		<pre>Las noticias no han sido definidas en el controlador</pre>
		<? }?>
	</div>
	<div class="separa-40"></div>
	<div class="separa-40"></div>
</section>
