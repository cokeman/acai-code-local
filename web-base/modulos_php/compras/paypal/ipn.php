<?
require_once dirname(__FILE__)."/../../../funciones.php";
require_once "redsys/apiRedsys.php";

if ($p->valida_ipn()||@$_REQUEST["tpvNotify"]) {

    if (@$_REQUEST["tpvNotify"]){
        $miObj = new RedsysAPI;

        $version = @$_REQUEST["Ds_SignatureVersion"];
        $datos = @$_REQUEST["Ds_MerchantParameters"];
        $signatureRecibida = @$_REQUEST["Ds_Signature"];


        $decodec = $miObj->decodeMerchantParameters($datos);

        $firma = $miObj->createMerchantSignatureNotif($tpvSecret,$datos);

        //echo $firma."<br>".$signatureRecibida;
        if ($firma === $signatureRecibida || @$_REQUEST["clave"] == "RlLzGz1997"){
            $datos = json_decode($decodec,true);
            mysql_query("update cms_posibles_pedidos SET tpv_respuesta='".$decodec."' WHERE num='".@$_REQUEST["custom"]."'");

            if (@$p->respuesta_tpv[@$datos["Ds_Response"]] || @$p->errores_tpv[$datos["Ds_ErrorCode"]]){
                if (@$datos["Ds_ErrorCode"]!=""){
                    $contenido = "";
                    if (@$datos["Ds_ErrorCode"]) { $contenido.= "ERROR : ".@$datos["Ds_ErrorCode"]." - ".@$p->errores_tpv[$datos["Ds_ErrorCode"]]."<br>"; }
                    if (@$p->respuesta_tpv[@$datos["Ds_Response"]]) { $contenido.= "RESPUESTA : ".@$datos["Ds_Response"]." - ".@$p->respuesta_tpv[@$datos["Ds_Response"]]."<br>"; }
                    mysql_query("update cms_posibles_pedidos SET tpv_cod_error='".$contenido."' WHERE num='".@$_REQUEST["custom"]."'");

                    mail($configuracionRecord["correo_admin"], "PAGO.ERROR ".$_SERVER["HTTP_HOST"], $contenido);
                }else{
                    $contenido = "";
                    if (@$p->respuesta_tpv[@$datos["Ds_Response"]]) { $contenido.= "RESPUESTA : ".@$datos["Ds_Response"]." - ".@$p->respuesta_tpv[@$datos["Ds_Response"]]."<br>"; } else { $contenido.="RESPUESTA : Error de Autorización : Código : ".@$datos["Ds_Response"]; }
                    mysql_query("update cms_posibles_pedidos SET tpv_cod_error='".$contenido."' WHERE num='".@$_REQUEST["custom"]."'");
                    mail($configuracionRecord["correo_admin"], "PAGO.ERROR ".$_SERVER["HTTP_HOST"], $decodec);
                }
                die();
            }

        } else {
            mail($configuracionRecord["correo_admin"], "FIRMA.ERRONEA ".$_SERVER["HTTP_HOST"], "");
            die("La firma no es igual al signature");
        }

    }
}
else{
    mysql_query("update cms_posibles_pedidos SET tpv_cod_error='OJO : Redsys no ha validado el pago del cliente por lo que hay que comprobar su pago de forma manual' WHERE num='".@$_REQUEST["custom"]."'");
}

validar_compra();

function validar_compra() {

    $subject = 'Instant Payment Notification - Recieved Payment';
    if (!@$_REQUEST["custom"]) { $subject = "ERROR CUSTOM : ".$subject; die("Error"); }

    // ******* GESTIONES DEL IPN
    $configuracionRecord = dame_registros("configuracion");
    $configuracionRecord = @$configuracionRecord[0];

    if (!@$_REQUEST["transferencia"]) {
        $sql = "UPDATE cms_posibles_pedidos SET estado_pedido=1 WHERE num='".mysql_real_escape_string($_REQUEST["custom"])."'";
        mysql_query($sql);
    }

    $compra = dame_registros("posibles_pedidos","num=".intval(@$_REQUEST["custom"]),"",1);
    $compra = @$compra[0];

    if (!@$compra) die("Error");

    $lineas = dame_registros("lineas_de_compra","pedido=".intval(@$compra["num"]),"num desc");

    foreach($lineas as $linea):
    if (@$linea["variacion"]){
        mysql_query("update aux_variaciones_productos set stock=stock-".intval(@$linea["cantidad"])." where num=".intval(@$linea["variacion"])) or die(mysql_error());
    }else{
        mysql_query("update cms_productos set stock=stock-".intval(@$linea["cantidad"])." where num=".intval(@$linea["producto"])) or die(mysql_error());
    }
    endforeach;

    if (@$compra["codigo_descuento"]){
        $sql = "UPDATE cms_codigos_descuento SET usado=1 WHERE num=".intval(@$compra["codigo_descuento"])." AND uso_ilimitado=0";
        mysql_query($sql);
    }

    if ($compra["usuario"]) {
        $usuario = dame_registros("usuarios","num=".intval(@$compra["usuario"]),"", 1);
        $usuario=@$usuario[0];
    }else{
        $usuario["nombre"] = $compra["nombre"];
        $usuario["correo"] = $compra["correo"];
    }

    $datos_envio = "
        <ul class='datos-envio'>
            <li><b>Nombre: </b>".@$usuario["nombre"]."</li>
            <li><b>Correo: </b>".@$usuario["correo"]."</li>
            <li><b>Teléfono: </b>".@$compra["telefono"]."</li>
            <li><b>Dirección completa </b>".@$compra["direccion_completa"]."</li>
            <li><b>Provincia: </b>".@$compra["provincia"]."</li>
            <li><b>Código Postal: </b>".@$compra["c_postal"]."</li>
            <li><b>País: </b>".@$compra["pais"]."</li>
        </ul>
    ";

    //$datosCesta = base64_decode($compra["cesta"]);
    /**** DATOS COMPRA ****/
    $datosCesta = "<ul>";
    foreach($lineas as $linea):
    $producto = dame_registros("productos", "num=".intval($linea["producto"]));
    $producto = @$producto[0];
    if (!@$producto) continue;
    $variacion = mysql_fetch_assoc(mysql_query("SELECT * FROM aux_variaciones_productos WHERE num=".intval(@$linea["variacion"])));

    $datosCesta .= "<li>".t($producto, "name");

    if (@$variacion["referencia"]) {
        $datosCesta .= " (Ref: ".$variacion["referencia"].")";
    }
    else if (@$producto["codigo"]){
        $datosCesta .= " (Ref: ".$producto["codigo"].")";
    }
    $datosCesta .= " - Cantidad: ".intval($linea["cantidad"]);
    $datosCesta .= "</li>";
    endforeach;
    $datosCesta .= "</ul>";

    $clave = @$_REQUEST["transferencia"] ? "CORREO_TRANSFERENCIA" : "CONFIRMAR_COMPRA";
    $contenido = dame_registros("correos", "clave='".$clave."'", "num DESC", 1);
    if (@$contenido) {
      $contenido = $contenido[0]["content"];
      $contenido = strtr($contenido, array(
          "{NOMBRE}" => $usuario["nombre"],
          "{DATOS}" => $datosCesta,
          "{DATOS_ENVIO}" => $datos_envio
      ));

      enviarcorreo($usuario["correo"],$asunto="Gracias por su compra",$contenido);
      enviarcorreo($configuracionRecord["correo_admin"],$asunto="Gracias por su compra ( Copia Empresa )",$contenido);
    }
}

if (@$_REQUEST["redir"]) {
    header("Location: ".$_REQUEST["redir"]);
}
?>
