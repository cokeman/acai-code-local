<?php
header('Content-Type: text/html; charset=utf-8');

function dame_array_cesta($cesta){
   $respuesta = array();
   $productos = explode("|",$cesta);
   for ($i=0;$i<count($productos);$i++){
      if ($productos[$i]!=""){
         $caracteristicas = explode("&",$productos[$i]);
         $respuesta[$i]["cantidad"] = $caracteristicas[0];
         $respuesta[$i]["num"] = $caracteristicas[1];
         $respuesta[$i]["foto"] = $caracteristicas[3];
         $respuesta[$i]["opciones"] = $caracteristicas[4];
         $respuesta[$i]["comentarios"] = $caracteristicas[5];
         $respuesta[$i]["precio"] = $caracteristicas[6]*intval($respuesta[$i]["cantidad"]);
         $respuesta[$i]["producto"] = $caracteristicas[2];
      }
   }
   return $respuesta;
}  

define('PAYPAL_EMAIL_ADD', $cuenta_paypal);

require_once('paypal_class.php');  

$p = new paypal_class( ); 				
$p->paypal_url = $entorno_paypal;   // testing paypal url
$p->admin_mail = EMAIL_ADD; 

switch (@$_GET['action']) {
    
   default:
      $estado_de_pedido=4;
      require_once "insercion.php";
      if (@$_POST["ident"]){
         $_REQUEST["custom"] = intval($_POST["ident"]);
         $_REQUEST["clave"] = "ingd2c";
         require_once "ipn.php";  
         ?><script type="text/javascript">document.location.href="/cesta.php?paypal_checkout=1&action=success_presupuesto";</script><?    
      }
      break;
   
   case 'ipn':          // Paypal is calling page for IPN validation...
   
      require_once "ipn.php";
      break;
 }     



?>