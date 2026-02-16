<section id="contenido_principal">
	<div class="container">
		<div class="separa-40"></div>
		<? muestra_breadcrumb($apartado);?>
		<div class="separa-40"></div>
		<h1 class="titular"><?=@$apartado["titulo_alternativo"] ? t($apartado, "titulo_alternativo") : t($apartado, "name");?></h1>
		<div class="separa-20"></div>
		<?=t($apartado, "content");?>
		<div class="col-md-8 col-md-offset-2 col-sm-10 col-sm-offset-1">
			<div class="registro">
				<form action="<?=$_SERVER["REQUEST_URI"];?>" method="post">
					<div class="form-group">
						<label class="full-width">
							<?=t_var("Email");?>
							<input type="email" class="form-control" name="datos[email]" required value="<?=@$_POST["datos"]["user"];?>" placeholder="<?=t_var("Email");?>">
						</label>
					</div>
					<div class="form-group">
						<label class="full-width">
							<?=t_var("Contraseña");?>
							<input type="password" class="form-control" name="datos[password]" required placeholder="<?=t_var("Contraseña");?>">
						</label>
					</div>
					<div class="form-group">
						<label class="full-width">
							<?=t_var("Confirmar contraseña");?>
							<input type="password" class="form-control" name="datos[confirmar_password]" required placeholder="<?=t_var("Confirmar contraseña");?>">
						</label>
					</div>
					<div class="text-center">
						<button type="submit" class="btn btn-success"><?=t_var("Registrarse");?></button>
					</div>
				</form>
			</div>
		</div>	
	</div>
	<div class="separa-40"></div>
</section>


<script>
	window.onload = function() {
		<? if (isset($res)) {?>
			<? if (@$res["error"]) {?>swal('<?=t_var("Error");?>', '<?=$res["error"];?>', 'error');<?}?>
			<? if (!@$res["error"]) {?>swal('<?=t_var("Registro completado");?>', '<?=t_var("El registro ha sido completado correctamente");?>', 'success');<?}?>
		<? }?>
	}
</script>