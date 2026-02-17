<html lang="es">
	<head>

		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="description" content="Descripción de quienes somos">
		<meta name="keywords" content="">
		<meta name="author" content="Coco Solution">
		<title>Página web en matenimiento</title>

		<link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">

		<style>
			body, html{height: 100%;}
			body{background-image: url('/template/estandar/images/mantenimiento.jpg'); background-size: cover; background-position: center; background-repeat: no-repeat; padding: 20px;}
			.wrapper-flex{display: flex; align-items: center; -webkit-align-items: center; justify-content: center; -webkit-justify-content: center;}
			.bloque{background-color: #fff; max-width: 600px; width: 100%; padding: 40px; box-shadow: 0 0 30px 5px rgba(0,0,0,0.3); -webkit-box-shadow: 0 0 30px 5px rgba(0,0,0,0.3);}
			.bloque img{display: block; margin: 0 auto; width: 100%; max-width: 300px; max-height: 200px; object-fit: contain;}
		</style>
	</head>
	<body class="wrapper-flex">
		<div class="bloque rounded-lg">
		    <?
		    
		    $configuracion_tienda = CmsApi::get("configuracion_tienda","num!=0")[0];
		    $logo = @$configuracion_tienda["logo"] ? $configuracion_tienda["logo"][0]["urlPath"] : '/template/estandar/images/logo.png';
			if (@$imageUrl){
				?><img src="<?=@$imageUrl;?>" class="h-8 lg:h-20"><?
			}else{
				?><img src="<?=@$logo;?>" class="h-8 lg:h-20"><?	
			}
		    ?>
			
			<h1 class="text-center mb-8 text-3xl mt-8"><? echo @$title ?: 'Actualmente en mantenimiento';?></h1>
			<p class="text-center text-gray-600 text-lg"><? echo @$text ?: 'Actualmente estamos en mantenimiento.<br>Disculpen las molestias';?></p>
		</div>
	</body>
</html>