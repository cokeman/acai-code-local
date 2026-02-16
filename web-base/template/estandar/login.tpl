<section id="contenido_principal">
	<div class="container">
		<div class="separa-40"></div>
		<? muestra_breadcrumb($record);?>
		<div class="separa-40"></div>
		<h1 class="titular"><?=@$record["titulo_alternativo"] ? t($record, "titulo_alternativo") : t($record, "name");?></h1>
		<div class="separa-20"></div>
		<?=t($record, "content");?>
		<div class="col-md-8 col-md-offset-2 col-sm-10 col-sm-offset-1">
			<div class="login">
				<form action="<?=$_SERVER["REQUEST_URI"];?>" method="post">
					<div class="form-group">
						<label class="full-width">
							<?=t_var("Email");?>
							<input type="email" class="form-control" name="user" required value="<?=@$_POST["user"];?>">
						</label>
					</div>
					<div class="form-group">
						<label class="full-width">
							<?=t_var("Contraseña");?>
							<input type="password" class="form-control" name="password" required>
						</label>
						<small><a href="<?=RUTA_RAIZ;?>/recordar.php"><?=t_var("Olvidé mi contraseña");?></a></small>
					</div>
					<div class="text-center">
						<button type="submit" class="btn btn-success"><?=t_var("Iniciar sesión");?></button>
					</div>
				</form>
			</div>
		</div>	
	</div>
	<div class="separa-40"></div>
</section>


<? if (@$error) {?>
<script>
	window.onload = function() {
		swal('<?=t_var("Error");?>', '<?=addslashes($error);?>', 'error');
	};
</script>
<? }?>