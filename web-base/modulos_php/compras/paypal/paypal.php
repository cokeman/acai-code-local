<?php
require_once dirname(__FILE__)."/../../../sesion.php";
require_once dirname(__FILE__)."/../../../funciones.php";
header('Content-Type: text/html; charset=utf-8');
define('EMAIL_ADD', $configuracionRecord["correo_admin"]);

require_once('paypal_class.php');

if (!@$tipo_de_pago) $tipo_de_pago = "paypal";

$p = new paypal_class();
if (!@$_REQUEST["transferencia"]) {
    switch ($tipo_de_pago) {
    case "stripe":
        require_once "stripe/init.php";

        if (!@$configuracionTienda["pago_por_stripe"]) {
            die("Tipo de pago no permitido");
        }
        if (!@$_REQUEST["stripeToken"] || !@$_REQUEST["stripeEmail"]) {
            die("Error en la comunicación con Stripe");
        }
        \Stripe\Stripe::setApiKey($configuracionTienda["clave_privada_stripe"]);

        $customer = \Stripe\Customer::create(array(
            'email' => $_REQUEST["stripeEmail"],
            'source'  => $_REQUEST["stripeToken"]
        ));

        $charge = \Stripe\Charge::create(array(
            'customer' => $customer->id,
            'amount'   => floor($_SESSION["cesta"]["desglose"]["total"]*100),
            'currency' => 'eur'
        ));
        break;
    case "tpv":
        if (!@$configuracionTienda["pago_por_tpv"]) {
            die("Tipo de pago no permitido");
        }
        $p->paypal_url = $configuracionTienda["entorno_tpv"];
        $merchantCode = $configuracionTienda["merchant_code"];
        $merchantCurrency = $configuracionTienda["merchant_currency"];
        $terminal = $configuracionTienda["terminal"];
        $tpvSecret = $configuracionTienda["clave_tpv"];
        break;
    case "transferencia":
        if (!@$configuracionTienda["pago_por_transferencia"]) {
            die("Tipo de pago por transferencia no permitido");
        }
        break;
    default:
        if (!@$configuracionTienda["pago_por_paypal"]) {
            die("Tipo de pago no permitido");
        }
        $p->paypal_url = $configuracionTienda["entorno_tienda_paypal"];
        $p->admin_mail = EMAIL_ADD;
        $p->add_field('business', $configuracionTienda["cuenta_paypal"]);
        break;
    }
}

require_once($_SERVER["DOCUMENT_ROOT"].CMS_FOLDER."/lib/viewer_functions.php");

switch (@$_GET['action']) {
    default:
        require_once "insercion.php";
        break;
    case 'ipn':
        require_once "ipn.php";
        break;
}
?>
