<?php
require_once __DIR__."/PaymentMethod.class.php";
class PayPal extends PaymentMethod {
    function __construct() {
        self::get_config();
        $this->init(2, 'PayPal', 'https://www.sandbox.paypal.com/cgi-bin/webscr', [
            'paypal_account' => self::$config['test_paypal_account'],
        ], 'https://www.paypal.com/cgi-bin/webscr', [
            'paypal_account' => self::$config['paypal_account']
        ]);
    }

    function can_be_used() {
        $cred = $this->get_credentials();
        return @$cred['paypal_account'] ? true : false;
    }

    function pay($quantity, $insertData = null, $payment  = null, $curl = false) {
        parent::pay($quantity, $insertData);
        
        $url = @$insertData['urlok'] ?: protocol()."://".$_SERVER['HTTP_HOST'];
        $cred = $this->get_credentials();
        
        $this->helper->paypal_url = $this->get_url();
        $this->helper->admin_mail = $cred['paypal_account'];
        $this->helper->add_field('business', $cred['paypal_account']);
        $this->helper->add_field('return', $url);
        $this->helper->add_field('cancel_return', $this->get_cancel_url($this->payment_record_id));
        $this->helper->add_field('notify_url', $this->get_ipn_url($this->payment_record_id));
        $this->helper->add_field('item_name', "REF.".$this->reference);
        $this->helper->add_field('currency_code', "EUR");
        $this->helper->add_field('cmd', '_xclick');
        $this->helper->add_field('amount', (float) $quantity);
        $this->helper->add_field('custom', $this->payment_record_id);
        $this->helper->add_field('rm', '2');
        if (self::$isTest) {
            $this->helper->dump_fields();
        }
        $this->helper->submit_paypal_post($curl);
    }

    function subscribe($quantity, $insertData = null) {
        die("No implementado");
        parent::subscribe($quantity, $insertData);
        $url = @$insertData['urlok'] ?: protocol()."://".$_SERVER['HTTP_HOST'];
        $cred = $this->get_credentials();

        $this->helper->paypal_url = $this->get_url();
        $this->helper->add_field('return', $url);
        $this->helper->add_field('cancel_return', $this->get_cancel_url($this->payment_record_id));
        $this->helper->add_field('notify_url', $this->get_ipn_url($this->payment_record_id));
        $this->helper->add_field('item_name', "REF.".$this->reference);
        $this->helper->add_field('currency_code', "EUR");
        $this->helper->add_field('cmd', '_xclick');
        $this->helper->add_field('amount', (float) $quantity);
        $this->helper->add_field('custom', $this->payment_record_id);
        $this->helper->add_field('rm', '2');
        if (self::$isTest) {
            $this->helper->dump_fields();
        }
        $this->helper->submit_paypal_post();
    }
}