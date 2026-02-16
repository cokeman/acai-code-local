<?php
use Stripe\Stripe;
require_once __DIR__."/../../../../sesion.php";
require_once __DIR__."/../../../../funciones.php";
require_once __DIR__ . "/autoload.php";
//Stripe
require __DIR__ . '/Stripe/vendor/autoload.php';

// Leer el payload de Stripe
$payload = @file_get_contents('php://input');

// Lógica original para eventos con IPN
if (isset($_REQUEST["data"])) {
    // Esto es para stripe
    if (isset($_REQUEST['data']["object"]["metadata"]["payment_num"])) {
        $_REQUEST['ipn'] = $_REQUEST['data']["object"]["metadata"]["payment_num"];
    }else if (isset($_REQUEST['data']["object"]["parent"]["subscription_details"]["subscription"])) {
        // En caso de que sea una suscripción, obtenemos el número de pago por la suscripción y buscamos el card_id con el id de esa suscripcion para obtener el payment
        $_REQUEST['ipn'] = IPNAction::get_num_by_subscription($_REQUEST['data']["object"]["parent"]["subscription_details"]["subscription"]);
    }else if (isset($_REQUEST['data']["object"]["payment_intent"])) {
        // En caso de que sea una suscripción, obtenemos el número de pago por la suscripción y buscamos el card_id con el id de esa suscripcion para obtener el payment
        $_REQUEST['ipn'] = IPNAction::get_num_by_subscription($_REQUEST['data']["object"]["payment_intent"]);
    }else if (isset($_REQUEST['data']["object"]["id"])) {
        // En caso de que sea una suscripción, obtenemos el número de pago por la suscripción y buscamos el card_id con el id de esa suscripcion para obtener el payment
        $_REQUEST['ipn'] = IPNAction::get_num_by_subscription($_REQUEST['data']["object"]["id"]);
    }
}

if (!@$_REQUEST['ipn']) {
    http_response_code(404);
    die();
}

try {
    $payment = IPNAction::get($_REQUEST['ipn']);
}catch(Exception $e) {
    http_response_code(404);
    die("Error mat lo encontro: ".$e->getMessage());
}

$config = TPV::get_config();

switch (intval($payment['method'])) {
    case 1: # TPV
        $object = new TPV();
        if(@$config['test']) $object->set_test(true);
        $credentials = $object->get_credentials();
        $miObj = new RedsysAPI;

        $version = @$_REQUEST["Ds_SignatureVersion"];
        $datos = @$_REQUEST["Ds_MerchantParameters"];
        $signatureRecibida = @$_REQUEST["Ds_Signature"];

        $decodec = $miObj->decodeMerchantParameters($datos);
        $firma = $miObj->createMerchantSignatureNotif($credentials['key'], $datos);

        if ($firma === $signatureRecibida || @$_REQUEST["clave"] == $config['clave_debug']){
            $datos = json_decode($decodec,true);
            $codigo_respuesta = intval(@$datos['Ds_Response']);
            if ($codigo_respuesta >= 0 && $codigo_respuesta < 100 && !@$datos['Ds_ErrorCode']) { // Pago ok
                $caducidad = mysql_real_escape_string(@$datos['Ds_ExpiryDate'] ?: '');
                $card_id = mysql_real_escape_string(@$datos['Ds_Merchant_Identifier'] ?: '');
                if (!@$_REQUEST['clave']) {
                    mysql_query("UPDATE `aux_plg_payments` SET `status`='Pagado', card_id='$card_id', card_caduc='$caducidad' WHERE num=$payment[num]") or die(mysql_error());
                }
                process_ipn($datos);
            }
            else {
                $error = mysql_real_escape_string("Ds_Response: $datos[Ds_Response] - $datos[Ds_ErrorCode]: ".@$object->helper->errores_tpv[$datos["Ds_ErrorCode"]]);
                mysql_query("UPDATE `aux_plg_payments` SET `status`='Error', `error`='$error' WHERE num=$payment[num]") or die(mysql_error());
                process_cancel_ipn(@$object->helper->errores_tpv[$datos["Ds_ErrorCode"]]);
            }
        } else {
            mysql_query("UPDATE `aux_plg_payments` SET `status`='Error', `error`='La firma no es igual al signature' WHERE num=$payment[num]") or die(mysql_error());
        }
    break;
    case 2: # PayPal
        mysql_query("UPDATE `aux_plg_payments` SET `status`='Pagado' WHERE num=$payment[num]") or die(mysql_error());
        process_ipn();
    break;
    case 3: # Transferencia
        process_ipn();
    break;
    case 5: # Stripe

        $prefix = '';

        if(@$config['test']) {
            $prefix = 'test_';
        }

        $stripe = new \Stripe\StripeClient($config[$prefix . "stripe_sk"]);
        $endpoint_secret = $config[$prefix . "webhook_sk"];
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            http_response_code(400);
            die(json_encode([
                'error' => 'Invalid payload',
                'message' => $e->getMessage()
            ]));
            exit();
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            http_response_code(400);
            die(json_encode([
                'error' => 'Invalid signature',
                'message' => $e->getMessage(),
                'sig_header' => $sig_header,
                'endpoint_secret' => $endpoint_secret,
                'payload' => $payload
            ]));
            exit();
        }

        

        // Solo si es suscripción
        if ($event->type == "checkout.session.completed" && @$event->data->object['subscription']) {
            $card_id = $event->data->object['subscription'];
        }else if ($event->type == "checkout.session.completed" && @$event->data->object['payment_intent']) {
            $card_id = $event->data->object['payment_intent'];
        }

        switch ($event->type) {
            case 'invoice.paid':
            case 'customer.subscription.updated':
            case 'checkout.session.completed':
                $sql = "UPDATE `aux_plg_payments` SET `status`='Pagado', `ipn_response`='".mysql_real_escape_string(json_encode($event->data->object))."'";
                if (@$card_id) {
                    $sql .= ", card_id='$card_id'";
                }
                $sql .= " WHERE num=$payment[num]";
                mysql_query($sql) or die(mysql_error());
            case 'invoice.created':
                $result = process_ipn($event->data->object);
                if (is_array($result)) {
                    header('Content-Type: application/json');
                    http_response_code(200);
                    die(json_encode($result));
                }
                break;
            case 'customer.subscription.deleted':
            case 'payment_intent.canceled':
            case 'refund.created':
                mysql_query("UPDATE `aux_plg_payments` SET `status`='Cancelado', `error`='', `ipn_response`='".mysql_real_escape_string(json_encode($event->data->object))."' WHERE num=$payment[num]");
                $result = process_cancel_ipn("Pago cancelado",$event->data->object);
                if (is_array($result)) {
                    header('Content-Type: application/json');
                    http_response_code(200);
                    die(json_encode($result));
                }
                break;
            case 'invoice.payment_failed':
                mysql_query("UPDATE `aux_plg_payments` SET `status`='Error', `error`='Error en invoice payment' WHERE num=$payment[num]");
                $result = process_cancel_ipn("Error en invoice payment",$event->data->object);
                if (is_array($result)) {
                    header('Content-Type: application/json');
                    http_response_code(200);
                    die(json_encode($result));
                }
                break;
            // ... handle other event types
            default:
                echo 'Received unknown event type ' . $event->type;
                break;
        }
        
        break;

    case 41: # Financiación Aplázame
        $data = json_decode(file_get_contents('php://input'),true);
        
        if( $data["status"] == "pending" ) {
            $data["info"] = "OPERACION PREAUTORIZADA";
            mysql_query("UPDATE `aux_plg_payments` SET `status`='Financiacion Solicitada',`ipn_response`='".json_encode($data)."' WHERE num=$payment[num]") or die(mysql_error());
            ob_start();
                process_ipn($data,true);
            $result = ob_get_clean();
            header('Content-Type: application/json');
            http_response_code(200);
            die(json_encode(["status" => "ok"]));

        }else if ($data["status"] == "ok"){
            $data["info"] = "OPERACION FINANCIADA";
            mysql_query("UPDATE `aux_plg_payments` SET `status`='Pagado',`ipn_response`='".json_encode($data)."' WHERE num=$payment[num]") or die(mysql_error());
            ob_start();
                process_ipn($data,true);
            $result = ob_get_clean();
            header('Content-Type: application/json');
            http_response_code(200);
            die(json_encode(["status" => "ok"]));

        }else{
            $data["info"] = "OPERACION RECHAZADA";
            mysql_query("UPDATE `aux_plg_payments` SET `status`='Cancelado',`ipn_response`='".json_encode($data)."' WHERE num=$payment[num]") or die(mysql_error());
            ob_start();
                process_ipn($data,true);
            $result = ob_get_clean();
            header('Content-Type: application/json');
            http_response_code(400);
            die(json_encode(["status" => "ko"]));
        }
    break;

    case 42: # Financiación Cetelem
        if (@$_REQUEST["IdTransaccion"]){
            
            $ipnResponse = @json_decode($payment["ipn_response"],true) ?: [];
            if(@$ipnResponse["IdTransaccion"]){
                $ipnCodResultado = intval($ipnResponse["codResultado"]);
                $requestCodResultado = intval(@$_REQUEST["codResultado"]);
                if ($ipnCodResultado > $requestCodResultado) $_REQUEST["codResultado"] = $ipnCodResultado;
            }
                
            $data = ["codResultado" => @$_REQUEST["codResultado"],"IdTransaccion" => $_REQUEST["IdTransaccion"]];

            if (@$_REQUEST["clave"] == $config['clave_debug'] && @$ipnResponse){
                $data["interno"] = "CAMBIADO EL ".date("Y-m-d H:i:s");
            }
            
            switch(@$_REQUEST["codResultado"]){
                case "50":
                    $data["info"] = "OPERACION FINANCIADA";
                    mysql_query("UPDATE `aux_plg_payments` SET `status`='Pagado',`ipn_response`='".json_encode($data)."' WHERE num=$payment[num]") or die(mysql_error());
                    break;
                case "00":
                    $data["info"] = "OPERACION PREAUTORIZADA";
                    mysql_query("UPDATE `aux_plg_payments` SET `status`='Esperando',`ipn_response`='".json_encode($data)."' WHERE num=$payment[num]") or die(mysql_error());
                    break;
                case "99":
                    $data["info"] = "OPERACION DENEGADA";
                    mysql_query("UPDATE `aux_plg_payments` SET `status`='Cancelado',`ipn_response`='".json_encode($data)."' WHERE num=$payment[num]") or die(mysql_error());
                    break;
                case "51":
                    $data["info"] = "OPERACION RECHAZADA";
                    mysql_query("UPDATE `aux_plg_payments` SET `status`='Cancelado',`ipn_response`='".json_encode($data)."' WHERE num=$payment[num]") or die(mysql_error());
                    break;
                default:
                    $data["info"] = "DESCONOCIDO";
                    mysql_query("UPDATE `aux_plg_payments` SET `status`='Error',`ipn_response`='".json_encode($data)."' WHERE num=$payment[num]") or die(mysql_error());
            }
            process_ipn($data);
        } else {
            mysql_query("UPDATE `aux_plg_payments` SET `status`='Financiacion Solicitada' WHERE num=$payment[num]") or die(mysql_error());    
            process_ipn();
        }
    break;

    case 43: # Financiación Iberent
        if (@$_REQUEST["events"]){
            $events = @$_REQUEST["events"];
            $order = @$_REQUEST["order"];
            $data = ["codResultado" => @$events[count($events)-1]["type"],"orderId" => $order["order_id"]];

            switch(@$data["codResultado"]){
                case "pending":
                    $data["info"] = "OPERACION INICIADA";
                    mysql_query("UPDATE `aux_plg_payments` SET `status`='Esperando',`ipn_response`='".json_encode($data)."' WHERE num=$payment[num]") or die(mysql_error());
                    break;

                case "completed":
                case "reviewing":
                case "authorized":
                    $data["info"] = "OPERACION EN ESTUDIO";
                    mysql_query("UPDATE `aux_plg_payments` SET `status`='Esperando',`ipn_response`='".json_encode($data)."' WHERE num=$payment[num]") or die(mysql_error());
                    break;    

                case "signed":
                    $data["info"] = "OPERACION APROBADA";
                    mysql_query("UPDATE `aux_plg_payments` SET `status`='Pagado',`ipn_response`='".json_encode($data)."' WHERE num=$payment[num]") or die(mysql_error());
                    break;
                
                case "declined":
                case "canceled":
                    $data["info"] = "OPERACION DENEGADA";
                    mysql_query("UPDATE `aux_plg_payments` SET `status`='Cancelado',`ipn_response`='".json_encode($data)."' WHERE num=$payment[num]") or die(mysql_error());
                    break;
                
                case "paid":  
                case "activated":  
                case "updated":
                case "expired":
                    //No hacemos nada en estos casos.
                    break;
                
                default:
                    $data["info"] = "DESCONOCIDO";
                    mysql_query("UPDATE `aux_plg_payments` SET `status`='Error',`ipn_response`='".json_encode($data)."' WHERE num=$payment[num]") or die(mysql_error());
            }
            process_ipn($data);
        }else{
            mysql_query("UPDATE `aux_plg_payments` SET `status`='Renting Solicitado' WHERE num=$payment[num]") or die(mysql_error());    
            process_ipn();
        }
    break;

    default:
    break;
}




function process_cancel_ipn($message,$data = null) {
    $payment = IPNAction::get($_REQUEST['ipn']);
    if ($payment['ipn_action']) {
        try {
            $class = new ReflectionClass($payment['ipn_action']);
            $class = $class->newInstance();
            $result = $class->performCancel($payment, $message);
            if (@$result){
                return $result;
            }
        }catch(Exception $e) {
            if (!file_exists(__DIR__."/../ipn_log.txt")) {
                touch(__DIR__."/../ipn_log.txt");
            }
            file_put_contents(__DIR__."/../ipn_log.txt", "------\n".date('Y-m-d H:i:s')." - ".$e->getMessage()."\n\n", FILE_APPEND);
        }
    }
}
function process_ipn($datos = null, $dummy = false) {
    if (!$datos) $datos = $_REQUEST;
    
    // DEBUG: Log que se está ejecutando process_ipn
    file_put_contents(__DIR__."/stripe_process_debug.txt", 
        date('Y-m-d H:i:s')." - process_ipn called with ipn: ".($_REQUEST['ipn'] ?? 'NO_IPN')."\n", 
        FILE_APPEND);
    
    $payment = IPNAction::get($_REQUEST['ipn']);
    
    file_put_contents(__DIR__."/stripe_process_debug.txt", 
        date('Y-m-d H:i:s')." - Payment found: ".json_encode(['num' => $payment['num'], 'ipn_action' => $payment['ipn_action']])."\n", 
        FILE_APPEND);
    
    $response = mysql_real_escape_string(is_array($datos) ? json_encode($datos) : $datos);
    if (!@$_REQUEST['clave']) {
        mysql_query("UPDATE `aux_plg_payments` SET ipn_response='$response' WHERE num=$payment[num]");
        $payment['ipn_response'] = json_encode($datos);
    }
    
    if ($payment['ipn_action']) {
        try {
            file_put_contents(__DIR__."/stripe_process_debug.txt", 
                date('Y-m-d H:i:s')." - About to call reflection for: ".$payment['ipn_action']."\n", 
                FILE_APPEND);
            
            $class = new ReflectionClass($payment['ipn_action']);
            $class = $class->newInstance();
            $result = $class->performAction($payment);
            
            file_put_contents(__DIR__."/stripe_process_debug.txt", 
                date('Y-m-d H:i:s')." - Reflection completed successfully\n", 
                FILE_APPEND);

            if (@$result){
                return $result;
            }
            
        }catch(Exception $e) {
            $error = mysql_real_escape_string($e->getMessage());
            mysql_query("UPDATE `aux_plg_payments` SET error='$error' WHERE num=$payment[num]");
            
            file_put_contents(__DIR__."/stripe_process_debug.txt", 
                date('Y-m-d H:i:s')." - ERROR in reflection: ".$e->getMessage()."\n", 
                FILE_APPEND);
        }
    } else {
        file_put_contents(__DIR__."/stripe_process_debug.txt", 
            date('Y-m-d H:i:s')." - No ipn_action found in payment\n", 
            FILE_APPEND);
    }
}
// function process_ipn($datos = null) {
//     if (!$datos) $datos = $_REQUEST;
//     $payment = IPNAction::get($_REQUEST['ipn']);
//     $response = mysql_real_escape_string(is_array($datos) ? json_encode($datos) : $datos);
//     if (!@$_REQUEST['clave']) {
//         mysql_query("UPDATE `aux_plg_payments` SET ipn_response='$response' WHERE num=$payment[num]");

//         // Dani (2024-07-24): Asignamos la misma respuesta de IPN al objeto
//         // `$payment` tras actualizar su valor en la base de datos.
//         $payment['ipn_response'] = json_encode($datos);
//     }
//     if ($payment['ipn_action']) {
//         try {
//             $class = new ReflectionClass($payment['ipn_action']);
//             $class = $class->newInstance();
//             $class->performAction($payment);
//         }catch(Exception $e) {
//             $error = mysql_real_escape_string($e->getMessage());
//             mysql_query("UPDATE `aux_plg_payments` SET error='$error' WHERE num=$payment[num]");
//             if (!file_exists(__DIR__."/ipn_log.txt")) {
//                 touch(__DIR__."/ipn_log.txt");
//             }
//             file_put_contents(__DIR__."/ipn_log.txt", "------\n".date('Y-m-d H:i:s')." - ".$e->getMessage()."\n\n", FILE_APPEND);
//         }
//     }
// }

die("Fin");