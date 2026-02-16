<?php
$configuracionAcai = CocoDB::get('configuracion_tienda', '');
$mantenimiento = $configuracionAcai[0]['fondo_mantenimiento'][0]['urlPath'] ?? '/template/estandar/images/mantenimiento.jpg';
require_once "{$_SERVER['DOCUMENT_ROOT']}/cms/lib/plugins/builder_saas/replace_code.php";
$logo = CustomCode::imagec(1000, $configuracionAcai[0]['logo'][0]['urlPath'] ?? '/template/estandar/images/logo.png');
$favicon = $configuracionAcai[0]['favicon'][0]['urlPath'] ?? '/favicon.ico';
$betarealty = $_SERVER['SERVER_NAME'] === 'betarealty.es';
?>
<html lang="es">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="description" content="Descripción de quienes somos" />
        <meta name="keywords" content="" />
        <meta name="author" content="Coco Solution" />
        <title>Página web en matenimiento</title>
        <link rel="icon" href="<?= $favicon ?>" />
        <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet" />
        <style>
            body,
            html {
                height: 100%;
            }
            body {
                background-image: url("<?= $mantenimiento ?>");
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
                padding: 20px;
            }
            .wrapper-flex {
                display: flex;
                align-items: center;
                -webkit-align-items: center;
                justify-content: center;
                -webkit-justify-content: center;
            }
            .bloque {
                background-color: <?= $betarealty ? '#000' : '#fff' ?>;
                max-width: 600px;
                width: 100%;
                padding: 40px;
                box-shadow: 0 0 30px 5px rgba(0, 0, 0, 0.3);
                -webkit-box-shadow: 0 0 30px 5px rgba(0, 0, 0, 0.3);
            }
            .bloque img {
                display: block;
                margin: 0 auto;
                max-width: 300px;
                max-height: 200px;
                object-fit: contain;
            }
        </style>
    </head>
    <body class="wrapper-flex">
        <div class="bloque rounded-lg">
            <?php if ($betarealty): ?>
                <img src="<?= $logo ?>" />
                <h1 class="text-center text-white my-4 text-3xl">Actualmente en mantenimiento</h1>
                <p class="text-center text-white text-lg">Actualmente estamos en mantenimiento.<br /> Pueden contactar con nosotros a través del correo electrónico <a href="mailto:soluciones@betarealty.es" class="underline">soluciones@betarealty.es</a> o través del teléfono <a href="tel:686062550" class="underline">686 06 25 50</a></p>
            <?php else: ?>
                <img src="<?= $logo ?>" />
                <h1 class="text-center mb-8 text-3xl mt-8">Actualmente en mantenimiento</h1>
                <p class="text-center text-gray-600 text-lg">Actualmente estamos en mantenimiento.<br />Disculpen las molestias</p>
            <?php endif ?>
        </div>
    </body>
</html>
