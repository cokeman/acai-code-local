<section id="contacto">
	<div class="container">
		<? if (@$apartado["banner"]) {?>
		<div class="flex no-flex-xs">
			<div class="col-sm-6 relative">
				<div class="imagen" style="background-image: url('<?=parsea_imagen(@$apartado["banner"][0]["urlPath"]);?>');"></div>
			</div>
			<div class="col-sm-6">
		<? }?>
				<div class="separa-40"></div>
				<div class="separa-40"></div>
				<?=$apartado_contenido;?>
				<div class="separa-40"></div>
				<div class="contact-form">
					<?= t(array("contenido" => "{FORMULARIO_SOLICITAR}"), "contenido", array("clases" => "btn btn-success btn-lg", "widget" => false, "tipo" => "inline")) ?>
					<style>.contact-form .btn{display:block;margin:0 auto;}</style>
				</div>
				<div class="separa-40"></div>
				<div class="separa-40"></div>
		<? if (@$apartado["banner"]) {?>
			</div>
		</div>
		<? }?>
	</div>
</section>