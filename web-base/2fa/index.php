<?php
require_once 'vendor/autoload.php';

use PragmaRX\Google2FA\Google2FA;

session_start();

// Aquí debes introducir la clave secreta que obtuviste de Facebook durante la configuración de 2FA
$facebookSecret = '4HFGOO43YIQRINFGNF3O7MLBWFJH4RZH';
$facebookSecret = 'N2YRLDSMSJV3GJ55RIJNYDIBBLVDTTOS';

$google2fa = new Google2FA();

// Genera un código TOTP actual para mostrarlo
$codigoGenerado = $google2fa->getCurrentOtp($facebookSecret);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="30"> <!-- Refresca cada 30 segundos -->
    <title>2FA Code</title>
    <style>
        body {
            background-color: #111;
            color: #00ffcc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
        }
        .codigo {
            font-size: 100px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="codigo"><?= $codigoGenerado ?></div>
</body>
</html>