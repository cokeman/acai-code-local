<?php
require_once __DIR__."/../../../../sesion.php";
require_once __DIR__."/../../../../funciones.php";
require_once __DIR__."/autoload.php";

if (@$_REQUEST['url']) {
    header('Location: '.base64_decode($_REQUEST['url']), true, 301);
    die();
}

// include __DIR__.'/../../../../header.php';
// echo tpl('apartados', [
//     'apartado' => [
//         'name' => t_var('Pago realizado')
//     ]
// ]);
// include __DIR__.'/../../../../footer.php';