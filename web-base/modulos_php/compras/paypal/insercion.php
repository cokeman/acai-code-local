<?php
include_once "redsys/apiRedsys.php";

if (@$_SESSION["user"]) {
	$user = dame_registros("usuarios", "SHA1(CONCAT(num, email, password))='".$_SESSION["user"]."'");
	$user = @$user[0];
}

if (!@$pedido_anterior){
    $created = 1;
    $sql = "INSERT INTO cms_posibles_pedidos SET num=NULL, createdDate='".date("Y-m-d H:i:s")."', createdByUserNum=".$created.", updatedDate='".date("Y-m-d H:i:s")."', updatedByUserNum=".$created.", dragSortOrder='".time()."'";
    if (@$_SESSION["cesta"]["desglose"]["codigoUsado"]) {
        $sql.=", codigo_descuento='".$_SESSION["cesta"]["desglose"]["codigoUsado"]["num"]."', valor_descuento='".$_SESSION["cesta"]["desglose"]["descuento"]."'";
    }

    if (@$user) {
        $sql .= ", usuario=".$user["num"];
    }

    if (@$_REQUEST["datos"]){
        if (is_array($_REQUEST["datos"])) $_REQUEST["datos"] = json_encode($_REQUEST["datos"]);
        $datos_usuario = json_decode($_REQUEST["datos"]);
        //$sql.=", usuario='".$datos_usuario->id."'";
        $sql.=", nombre='".mysql_real_escape_string($datos_usuario->nombre)."'";
        $sql.=", correo='".mysql_real_escape_string($datos_usuario->email)."'";
        $sql.=", telefono='".mysql_real_escape_string($datos_usuario->telefono)."'";
        $sql.=", direccion_completa='".mysql_real_escape_string($datos_usuario->direccion)."'";
        $sql.=", provincia='".mysql_real_escape_string($datos_usuario->provincia)."'";
        $sql.=", c_postal='".mysql_real_escape_string($datos_usuario->codigo_postal)."'";
        $sql.=", pais='".mysql_real_escape_string($datos_usuario->pais)."'";
    }

    $sql.= ", title='".time()."', precio=".round($_SESSION["cesta"]["desglose"]["total"], 2).", estado_pedido=0, tipo_de_pago='".$tipo_de_pago."'";

    $lineas_referencia = array();
    foreach ($_SESSION["cesta"]["productos"] as $num => $producto):
    if (@$producto["variaciones"] && is_array($producto["variaciones"])) {
        foreach ($producto["variaciones"] as $variacion):
        $lineas_referencia[] = array("referencia" => t($producto, "codigo")." - ".@$variacion["referencia"], "cantidad" => $variacion["cantidad"]);
        endforeach;
    }
    else {
        $lineas_referencia[] = array("referencia" => t($producto, "codigo"), "cantidad" => $producto["cantidad"]);
    }
    endforeach;
    $sql.=", lineas_referencia='".mysql_real_escape_string(json_encode($lineas_referencia))."'";

    mysql_query($sql) or die(mysql_error());
    $_POST["ident"] = mysql_insert_id();

    // LINEAS DE COMPRA
    foreach ($_SESSION["cesta"]["productos"] as $num => $producto):
    $sql = "INSERT INTO cms_lineas_de_compra SET num=NULL, createdDate='".date("Y-m-d H:i:s")."', createdByUserNum=".$created.", updatedDate='".date("Y-m-d H:i:s")."', updatedByUserNum=".$created.", dragSortOrder='".time()."'";
    if (@$producto["variaciones"] && is_array($producto["variaciones"])) {
        foreach ($producto["variaciones"] as $variacion):
        $sql.=",pedido='".mysql_real_escape_string($_POST["ident"])."', producto='".$producto["num"]."', variacion='".$variacion["num"]."', cantidad=".$variacion["cantidad"];
        endforeach;
    }
    else {
        $sql.=",pedido='".mysql_real_escape_string($_POST["ident"])."', producto='".$producto["num"]."', variacion=0, cantidad=".$producto["cantidad"];
    }
    mysql_query($sql) or die(mysql_error());
    endforeach;

    /**** REGISTRO DE USUARIO ****/
    if (!@$user && @$datos_usuario->email) {
        $usuarioYaExiste = mysql_fetch_assoc(mysql_query("SELECT * FROM cms_usuarios WHERE email='".mysql_real_escape_string($datos_usuario->email)."'"));
        if (!@$usuarioYaExiste) {
            $pass = base_convert(time(), 10, 36);
            $date = date("Y-m-d H:i:s");
            mysql_query("INSERT INTO cms_usuarios SET num=NULL, createdDate='".$date."', createdByUserNum=1, updatedDate='".$date."', updatedByUserNum=1, dragSortOrder='".time()."', activo=1, email='".mysql_real_escape_string($datos_usuario->email)."', password='".sha1($pass)."', nombre='".mysql_real_escape_string($datos_usuario->nombre)."', apellidos='', telefono='".mysql_real_escape_string($datos_usuario->telefono)."', pais='".mysql_real_escape_string($datos_usuario->pais)."', provincia='".mysql_real_escape_string($datos_usuario->provincia)."', direccion='".mysql_real_escape_string($datos_usuario->direccion)."', codigo_postal='".mysql_real_escape_string($datos_usuario->codigo_postal)."'") or die(mysql_error());

            $correo = dame_registros("correos", "clave='REGISTRO_COMPRA'");
            $correo = t(@$correo[0], "content");
            if (@$correo) {
                $correo = strtr($correo, array(
                    "{CONTRASENA}" => $pass,
                    "{EMAIL}" => $datos_usuario->email,
                    "{NOMBRE}" => $datos_usuario->nombre
                ));
                enviarcorreo($datos_usuario->email, t_var("Gracias por su compra"), $correo);
            }
        }
    }

    $pedido_reg = dame_registros("posibles_pedidos","num=".mysql_real_escape_string($_POST["ident"]),"",1);
}else{
    $pedido_reg = dame_registros("posibles_pedidos","num=".$pedido_anterior,"",1);
}
$pedido_reg = @$pedido_reg[0];

// ****** FIN INSERCION DE DATOS INICIALES

// LE AÑADIMOS EL TIMESTAMP AL CANCEL PARA PODER PASARLO A ESTADO CANCELADO
$url_ko = $url_ko."&hash=".md5($pedido_reg["num"].strtotime($pedido_reg["createdDate"]));
$notify = $notify.$pedido_reg["num"];

$this_script = protocol().'://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

switch($tipo_de_pago){
    case "stripe":
        header("Location: ".$notify."&redir=".urlencode(protocol()."://".$_SERVER["HTTP_HOST"]."/cesta.php?paypal_checkout=1&action=success"));
    case "tpv":
        $miObj = new RedsysAPI;
        $miObj->setParameter("DS_MERCHANT_AMOUNT", ((float) floor(parsea_decimales($pedido_reg["precio"])*100)));
        $miObj->setParameter("DS_MERCHANT_ORDER", $pedido_reg["title"]);
        $miObj->setParameter("DS_MERCHANT_MERCHANTCODE", $merchantCode);
        $miObj->setParameter("DS_MERCHANT_CURRENCY", $merchantCurrency); // EUROS
        $miObj->setParameter("DS_MERCHANT_TRANSACTIONTYPE", 0);
        $miObj->setParameter("DS_MERCHANT_TERMINAL", $terminal);
        $miObj->setParameter("DS_MERCHANT_MERCHANTURL", $notify."&tpvNotify=1");
        $miObj->setParameter("DS_MERCHANT_URLOK", $url_ok);
        $miObj->setParameter("DS_MERCHANT_URLKO", $url_ko);
        $params = $miObj->createMerchantParameters();
        $signature = $miObj->createMerchantSignature($tpvSecret);
        $p->add_field('Ds_SignatureVersion', 'HMAC_SHA256_V1');
        $p->add_field('Ds_MerchantParameters', $params);
        $p->add_field('Ds_Signature', $signature);
        break;
    case "transferencia":
        header("Location: ".$notify."&transferencia=1&redir=".urlencode(protocol()."://".$_SERVER["HTTP_HOST"]."/cesta.php?paypal_checkout=1&action=success"));
        break;
    default:
        $p->add_field('return', $url_ok);
        $p->add_field('cancel_return', $url_ko);
        $p->add_field('notify_url', $notify."&paypalNotifiy=1");
        $p->add_field('item_name', "REF.".$pedido_reg["title"]);
        $p->add_field('currency_code', "EUR");
        $p->add_field('cmd', '_xclick');
        $p->add_field('amount', (float) parsea_decimales($pedido_reg["precio"]));

        $p->add_field('custom', $pedido_reg["num"]);
        $p->add_field('rm', '2');	// Return method = POST

        break;
}
$p->submit_paypal_post(); // submit the fields to paypal
$p->dump_fields();      // for debugging, output a table of all the fields
?>
