<?php
require_once __DIR__."/vendor/apiRedsys.php";
require_once __DIR__."/PaymentMethod.class.php";
class Transfer extends PaymentMethod {
    function __construct() {
        self::get_config();
        $this->init(3, 'Transfer', "", ['method' => 'transfer'], "", ['method' => 'transfer']);
    }

    function can_be_used() {
        $cred = $this->get_credentials();
        return true;
    }

    function pay($quantity, $insertData = null, $payment  = null, $curl = false) {
        parent::pay($quantity, $insertData, $payment);
        self::process_ipn(sha1($this->payment_record_id));

        $cred = $this->get_credentials();
        $this->helper->paypal_url = $this->get_success_url();
        $this->helper->add_field('ReferenceOrder', $this->reference);
        if (self::$isTest) {
            $this->helper->dump_fields();
        }

        $this->helper->submit_paypal_post($curl,false);
    }

    function process_ipn($paymentId,$datos = []){
        $payment = IPNAction::get($paymentId);

        $response = mysql_real_escape_string(is_array($datos) ? json_encode($datos) : $datos);
        if (!@$_REQUEST['clave']) {
            mysql_query("UPDATE `aux_plg_payments` SET ipn_response='$response' WHERE num=$payment[num]");
        }
        if ($payment['ipn_action']) {
            try {
                $class = new ReflectionClass($payment['ipn_action']);
                $class = $class->newInstance();
                $class->performAction($payment);
            }catch(Exception $e) {
                $error = mysql_real_escape_string($e->getMessage());
                mysql_query("UPDATE `aux_plg_payments` SET error='$error' WHERE num=$payment[num]");
                if (!file_exists(__DIR__."/../ipn_log.txt")) {
                    touch(__DIR__."/../ipn_log.txt");
                }
                file_put_contents(__DIR__."/../ipn_log.txt", "------\n".date('Y-m-d H:i:s')." - ".$e->getMessage()."\n\n", FILE_APPEND);
            }
        }
    }
}
