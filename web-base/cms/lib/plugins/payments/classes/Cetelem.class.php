<?php

require_once __DIR__."/PaymentMethod.class.php";
class Cetelem extends PaymentMethod {
    function __construct() {
        self::get_config();
        $this->init(42, 'Cetelem', "https://test.cetelem.es/eCommerceLite/configuracion.htm", [
            'COMANDO' => "INICIO",
            'CodCentro' => self::$config['cetelem_codcentro']//,
            // 'Modalidad' => "G"
        ], 'https://www.cetelem.es/eCommerceLite/configuracion.htm', [
            'COMANDO' => "INICIO",
            'CodCentro' => self::$config['cetelem_codcentro']//,
            // 'Modalidad' => "G"
        ]);
    }

    function can_be_used() {
        $cred = $this->get_credentials();
        return @$cred['merchant'] && @$cred['currency'] && @$cred['terminal'] && @$cred['key'];
    }

    function pay($quantity, $insertData = null, $payment  = null, $curl = false) {
        parent::pay($quantity, $insertData, $payment);
        $cred = $this->get_credentials();
        $this->helper->add_field("COMANDO",$cred["COMANDO"]);
        $this->helper->add_field("CodCentro",$cred["CodCentro"]);
        $this->helper->add_field("IdTransaccion",str_pad($this->reference, 12, "0", STR_PAD_RIGHT));
        if (@$cred["Modalidad"]) $this->helper->add_field("Modalidad",$cred["Modalidad"]);
        $this->helper->add_field("CuotaPago","PC"); // Esta dani
        // $this->helper->add_field("Material","330"); 
        // $this->helper->add_field("Material","322"); // Esta dani
        $this->helper->add_field("Importe", ((float) floor(self::parse_number($quantity) * 100)));
        $this->helper->add_field("ReturnURL", $this->get_success_url());
        $this->helper->add_field("ReturnOK", $this->get_ipn_url($this->payment_record_id));
        
        if (@$insertData["userData"]){
            foreach($insertData["userData"] as $key => $value){
                $this->helper->add_field($key,$value);
            }
        }
        $this->helper->paypal_url = $this->get_url();
        if (self::$isTest) {
            $this->helper->dump_fields();
        }
        
        $this->helper->submit_paypal_post($curl);
    }

}