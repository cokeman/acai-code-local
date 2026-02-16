<?
require_once dirname(__FILE__).'/../../../sesion.php';
require_once dirname(__FILE__).'/../../../funciones.php';
require_once 'defaults.php';

$data = json_decode(file_get_contents("php://input"), true);
if (@$data["stripeToken"]){
    $_REQUEST = $data;
    die(var_dump($_REQUEST));
}


if (@$_REQUEST["stripeToken"]){
    require_once('stripe/init.php');

    $error = $defaults["error"]["message"];
    $success = $defaults["success"]["message"];

    $stripe = array(
        "secret_key"      => $defaults["credenciales"]["SECRET"],
        "publishable_key" => $defaults["credenciales"]["PUB_KEY"]
    );

    \Stripe\Stripe::setApiKey($stripe['secret_key']);

    $token  = $_REQUEST['stripeToken'];

    // PRECIO
    if ($defaults["producto"]["registro"]["campoPrecio"]){
        $producto = dame_registros($defaults["producto"]["registro"]["tableName"],"num='".intval($defaults["producto"]["registro"]["recordNum"])."'","num desc",1);
        $producto = @$producto[0];
        $precio = @$producto[$defaults["producto"]["registro"]["campoPrecio"]];
        if (@$defaults["producto"]["parseaPrecio"]) $precio = $defaults["producto"]["parseaPrecio"]($precio);
    }else{
        $precio = $defaults["producto"]["precio"];
    }

    // REFERENCIA
    if ($defaults["producto"]["registro"]["campoReferencia"]){
        
        $referencia = @$producto[$defaults["producto"]["registro"]["campoReferencia"]];
        
    }else{
        $referencia = $defaults["producto"]["referenciaDefault"];
    }
    
    // ORDER ID
    $order_id = time();

    if (!$precio) { echo str_replace("{ERROR}",t_var("precio"),$error); $stop=true; return; }
    if (!isset($_REQUEST["stripeEmail"])) { echo str_replace("{ERROR}",t_var("Correo"),$error); $stop=true; return; }
    
    try {
        
        // CARGO
        
        $customer = \Stripe\Customer::create(array(
            'email'   => @$_REQUEST["stripeEmail"],
            'source'  => $token
        ));
        $charge = \Stripe\Charge::create(array(
            'customer'      =>  $customer->id,
            'description'   =>  $referencia,
            'amount'        =>  $precio*100,
            'currency'      =>  'eur'
        ));
        
        // REGISTRO
        if (@$defaults["registro"]["tableName"]){
            if(mysql_num_rows(mysql_query("SHOW TABLES LIKE '".$TABLE_PREFIX.$defaults["registro"]["tableName"]."'"))==1) {


                $sql = "INSERT INTO ".$TABLE_PREFIX.@$defaults["registro"]["tableName"]." SET num=null,createdDate='".date("Y-m-d H:i:s")."',updatedDate='".date("Y-m-d H:i:s")."',createdByUserNum=1,updatedByUserNum=1,dragSortOrder=".time();
                if (@$defaults["registro"]["campoEstado"] && @mysql_num_rows(mysql_query("SHOW COLUMNS FROM ".$TABLE_PREFIX.$defaults["registro"]["tableName"]." LIKE '".@$defaults["registro"]["campoEstado"]."'"))) {
                    $sql.= ",".@$defaults["registro"]["campoEstado"]."=1";
                }
                if (@$defaults["registro"]["campoReferencia"] && @mysql_num_rows(mysql_query("SHOW COLUMNS FROM ".$TABLE_PREFIX.$defaults["registro"]["tableName"]." LIKE '".@$defaults["registro"]["campoReferencia"]."'"))) {
                    $sql.= ",".@$defaults["registro"]["campoReferencia"]."='".$order_id."'";
                }
                if (@$defaults["registro"]["campoPrecio"] && @mysql_num_rows(mysql_query("SHOW COLUMNS FROM ".$TABLE_PREFIX.$defaults["registro"]["tableName"]." LIKE '".@$defaults["registro"]["campoPrecio"]."'"))) {
                    $sql.= ",".@$defaults["registro"]["campoPrecio"]."='".$precio."'";
                }
                if (@$producto){
                    if (@$defaults["registro"]["campoProducto"]  && @mysql_num_rows(mysql_query("SHOW COLUMNS FROM ".$TABLE_PREFIX.$defaults["registro"]["tableName"]." LIKE '".@$defaults["registro"]["campoProducto"]."'"))) {
                        $sql.= ",".@$defaults["registro"]["campoProducto"]."=".$producto["num"];
                    }
                }
                if (@$defaults["registro"]["fields"]) {
                    foreach($defaults["registro"]["fields"] as $field){
                        if (@mysql_num_rows(mysql_query("SHOW COLUMNS FROM ".$TABLE_PREFIX.$defaults["registro"]["tableName"]." LIKE '".@$field["name"]."'"))){
                            $sql.= ",".$field["name"]."='".mysql_real_escape_string($field["value"])."'";
                        }
                    }
                }
                if (@$_REQUEST["dynamicForm"]){
                    foreach($_REQUEST["dynamicForm"] as $key => $value):
                        if (@mysql_num_rows(mysql_query("SHOW COLUMNS FROM ".$TABLE_PREFIX.$defaults["registro"]["tableName"]." LIKE '".$key."'"))){
                            if (!is_array($value)) $sql.= ",`".$key."`='".mysql_real_escape_string($value)."'";
                        }
                    endforeach;
                }
                mysql_query($sql) or die(mysql_error());

            }
        } 
        
        // EMAIL
        if (@$defaults["email"]["clave"] && @$defaults["email"]["tableName"]){
            try{
                $email = dame_registros(@$defaults["email"]["tableName"],@$defaults["email"]["campoClave"]."='".@$defaults["email"]["clave"]."'","num desc",1);
                $email = @$email[0];
                if (@$email){
                    $cuerpo = @$email[$defaults["email"]["campoCuerpo"]];
                    $cuerpo = str_replace("{REFERENCIA}",@$order_id,$cuerpo);
                    $cuerpo = str_replace("{CONCEPTO}",@$referencia,$cuerpo);
                    $cuerpo = str_replace("{PRECIO}",@$precio,$cuerpo);
                    if (@$_REQUEST["dynamicForm"]){
                        foreach($_REQUEST["dynamicForm"] as $key => $value):
                            $cuerpo = str_replace("{".strtoupper($key)."}",$value,$cuerpo);
                        endforeach;
                        if (@$producto){
                            foreach($producto as $key => $value):
                                if (!is_array($value)) $cuerpo = str_replace("{PRODUCTO_".strtoupper($key)."}",$value,$cuerpo);
                            endforeach;
                        }
                    }
                    
                    if (@$defaults["email"]["cambios"]){
                        foreach($defaults["email"]["cambios"] as $key => $value){
                            $cuerpo = str_replace("{PEDIDO}",@$order_id,str_replace($key,$value,$cuerpo));        
                        }
                    }
                    
					if (@$_REQUEST["dynamicForm"]["email"]) {
						if (!is_array(@$defaults["email"]["destinatarios"])) $defaults["email"]["destinatarios"] = array();
						$defaults["email"]["destinatarios"][] = $_REQUEST["dynamicForm"]["email"];
					}
                    foreach(@$defaults["email"]["destinatarios"] as $destinatario):
                        enviarcorreo($destinatario,@$email[$defaults["email"]["campoAsunto"]],$cuerpo);
                    endforeach;
                }else{
                    echo str_replace("{ERROR}",t_var("Correo"),$error);
                    $stop=true;return;
                }
            }catch(Exception $e){
                echo str_replace("{ERROR}",t_var("Correo"),$error);
                $stop=true;return;
            }
        }
        
    }catch (Exception $e) {
        echo str_replace("{ERROR}",t_var("Pago"),$error);
        $stop=true;return;
    }
    echo $success;
    $stop=true;
}
?>