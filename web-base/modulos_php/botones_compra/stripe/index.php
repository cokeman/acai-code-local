<?
require_once "defaults.php";
// ENVIO DEL PAGO
require_once "post.php";

if (@$stop) return;
// MUESTRA DEL BOTON DE PAGO
?>

<? require_once "template.tpl";?>

<script>var stripe = Stripe('<?=$defaults["credenciales"]["PUB_KEY"];?>'); var options = <?=json_encode($options);?>;</script>
<script src="<?=str_replace(str_replace("private_html", "public_html", $_SERVER["DOCUMENT_ROOT"]),"/",dirname(__FILE__))."/js/main.js";?>"></script>

<link rel="stylesheet" href="<?=str_replace(str_replace("private_html", "public_html", $_SERVER["DOCUMENT_ROOT"]),"/",dirname(__FILE__))."/css/style.css";?>">
<?