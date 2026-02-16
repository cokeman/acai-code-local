<?php
require_once __DIR__."/../../../../sesion.php";
require_once __DIR__."/../../../../funciones.php";
require_once __DIR__."/autoload.php";

#header('Location: /');
if (!@$_REQUEST['cancel']) {
    http_response_code(403);
}

try {
    $payment = IPNAction::get($_REQUEST['cancel']);
}catch(Exception $e) {
    die("Error: ".$e->getMessage());
}

if ($payment['status'] == 'Esperando' || $payment['status'] == 'Error') {
    mysql_query("UPDATE `aux_plg_payments` SET `status`='Cancelado' WHERE num=$payment[num]") or die(mysql_error());
}

// Dani (2024-07-24): Cuando enviamos a un usario a la pasarela de pago de
// Paypal y este cancela el pago, Paypal no pasa actualmente por `ipn.php` y
// redirige directamtente a `cancel.php`, por lo que realizamos aquí la llamada
// a `performCancel`.
if (empty($payment['ipn_action']) === false && $payment['method'] === '2') {
    $class = new ReflectionClass($payment['ipn_action']);
    $class = $class->newInstance();
    $class->performCancel($payment, $message);
}

if (@$_REQUEST['url']) {
    header('Location: '.base64_decode($_REQUEST['url']), true, 301);
    die();
}

// include __DIR__.'/../../../../header.php';
// echo tpl('apartados', [
//     'apartado' => [
//         'name' => t_var('Pago cancelado')
//     ]
// ]);
// include __DIR__.'/../../../../footer.php';
