<section id="perfil">
	<div class="container">
		<div class="separa-40"></div>
		<? muestra_breadcrumb($apartado);?>
		<div class="separa-40"></div>
		<h3 class="titular"><?=t_var("Bienvenido");?> <?=$usuario["nombre"];?></h3>
		<div class="separa-20"></div>
		<p class="text-center"><?=t_var("Texto datos cliente");?></p>
		<form action="<?=$_SERVER["REQUEST_URI"];?>" method="post" id="profile-form">
			<div class="form-group">
				<label class="col-md-6">
					<?=t_var("Nombre");?>
					<input type="text" name="perfil[nombre]" class="form-control" placeholder="<?=t_var("Nombre");?>" value="<?=@$usuario["nombre"];?>">
				</label>
			</div>
			<div class="form-group">
				<label class="col-md-6">
					<?=t_var("Apellidos");?>
					<input type="text" name="perfil[apellidos]" class="form-control" placeholder="<?=t_var("Apellidos");?>" value="<?=@$usuario["apellidos"];?>">
				</label>
			</div>
			<div class="form-group">
				<label class="col-md-6">
					<?=t_var("Email");?>
					<input type="email" name="perfil[email]" class="form-control" placeholder="<?=t_var("Email");?>" value="<?=@$usuario["email"];?>">
				</label>
			</div>
			<div class="form-group">
				<label class="col-md-6">
					<?=t_var("Teléfono");?>
					<input type="tel" name="perfil[telefono]" class="form-control" placeholder="<?=t_var("Teléfono");?>" value="<?=htmlspecialchars(@$usuario["telefono"]);?>">
				</label>
			</div>
			<div class="form-group">
				<label class="col-md-6">
					<?=t_var("País");?>
					<input type="text" name="perfil[pais]" class="form-control" placeholder="<?=t_var("País");?>" value="<?=htmlspecialchars(@$usuario["pais"]);?>">
				</label>
			</div>
			<div class="form-group">
				<label class="col-md-6">
					<?=t_var("Provincia");?>
					<input type="text" name="perfil[provincia]" class="form-control" placeholder="<?=t_var("Provincia");?>" value="<?=htmlspecialchars(@$usuario["provincia"]);?>">
				</label>
			</div>
			<div class="form-group">
				<label class="col-md-6">
					<?=t_var("Código Postal");?>
					<input type="text" name="perfil[codigo_postal]" class="form-control" placeholder="<?=t_var("Código Postal");?>" value="<?=htmlspecialchars(@$usuario["codigo_postal"]);?>">
				</label>
			</div>
			<div class="form-group">
				<label class="col-md-6">
					<?=t_var("Dirección");?>
					<input type="text" name="perfil[direccion]" class="form-control" placeholder="<?=t_var("Dirección");?>" value="<?=htmlspecialchars(@$usuario["direccion"]);?>">
				</label>
			</div>
			<div class="form-group">
				<label class="col-md-6">
					<?=t_var("Contraseña");?>
					<input type="password" name="perfil[password]" class="form-control" placeholder="<?=t_var("Contraseña");?>">
				</label>
			</div>
			<div class="form-group">
				<label class="col-md-6">
					<?=t_var("Confirmar contraseña");?>
					<input type="password" name="perfil[confirmar_password]" class="form-control" placeholder="<?=t_var("Confirmar contraseña");?>">
				</label>
			</div>
			<div class="separa-40"></div>
			<div class="text-center">
				<button class="btn btn-success" id="save-profile"><?=t_var("Guardar datos");?></button>
				<div class="separa-40"></div>
			</div>
			<div class="text-center">
				<a class="btn btn-danger" href="<?=RUTA_RAIZ;?>/?cerrarsesion=1"><?=t_var("Cerrar sesión");?></a>
			</div>
		</form>
	</div>
	<div class="separa-40"></div>
</section>

<section>
	<div class="container">
		<div class="col-md-12" style="margin-top:40px;">
			<h3 class="titular"><?=t_var("Mis pedidos");?></h3>
			<div class="separa-20"></div>
			<? muestra_historial($usuario);?>
		</div>
	</div>
	<div class="separa-40"></div>
</section>


<script>
	var actualFunction = window.onload;
    window.onload = function() {
        if (typeof actualFunction === "function") actualFunction();
		var saveBtn = document.getElementById('save-profile');
		var form = document.getElementById('profile-form');
		if (!saveBtn) return;
		saveBtn.addEventListener('click', function(e) {
			e.preventDefault();
			fetch(`<?=$_SERVER["REQUEST_URI"];?>?token=${"<?=sha1($usuario["num"].$usuario["email"].$usuario["password"]);?>"}&${$(form).serialize()}`)
				.then(function(data) { return data.json(); })
				.then(function(json) {
					if (json.error) {
						swal('<?=t_var("Error");?>', json.error, 'error');
						return;
					}
					swal('<?=t_var("¡Hecho!");?>', '<?=t_var("Sus datos han sido correctamente actualizados");?>', 'success');
				});
		});
	};
</script>
