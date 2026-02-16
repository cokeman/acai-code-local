<?php
require_once __DIR__ . "/vendor/apiRedsys.php";
require_once __DIR__ . "/PaymentMethod.class.php";
class TPV extends PaymentMethod
{
    function __construct()
    {
        self::get_config();
        $this->init(1, 'TPV', 'https://sis-t.redsys.es:25443/sis/realizarPago', [
            'merchant' => self::$config['test_tpv_merchant'],
            'currency' => self::$config['test_tpv_currency'],
            'terminal' => self::$config['test_tpv_terminal'],
            'key' => self::$config['test_tpv_key']
        ], 'https://sis.redsys.es/sis/realizarPago', [
            'merchant' => self::$config['tpv_merchant'],
            'currency' => self::$config['tpv_currency'],
            'terminal' => self::$config['tpv_terminal'],
            'key' => self::$config['tpv_key'],
        ]);
    }

    function can_be_used()
    {
        $cred = $this->get_credentials();
        return @$cred['merchant'] && @$cred['currency'] && @$cred['terminal'] && @$cred['key'];
    }
    function insert_payments($records = null, $email = null, $ipn = null, $callback = null, $tipo = 0, $price = 0, $payment_id = null)
    {
        // Mat (2024-08-06): Creamos esta función para poder generar un payment sin el pay previo al generar compra con stripe
        parent::insert($records, $email, $ipn, $callback, $tipo, $price, $payment_id);
        return mysql_insert_id();
    }
    function pay($quantity, $insertData = null, $payment = null, $curl = false)
    {
        parent::pay($quantity, $insertData, $payment);
        $cred = $this->get_credentials();
        $isBizum = $this->is_bizum();
        $miObj = new RedsysAPI;
        $miObj->setParameter("DS_MERCHANT_AMOUNT", ((float) floor(self::parse_number($quantity) * 100)));
        $miObj->setParameter("DS_MERCHANT_ORDER", $this->reference);
        $miObj->setParameter("DS_MERCHANT_MERCHANTCODE", $cred['merchant']);
        $miObj->setParameter("DS_MERCHANT_CURRENCY", $cred['currency']);
        $miObj->setParameter("DS_MERCHANT_TRANSACTIONTYPE", 0);
        $miObj->setParameter("DS_MERCHANT_TERMINAL", $cred['terminal']);
        $miObj->setParameter("DS_MERCHANT_PRODUCTDESCRIPTION", $this->get_product_description());

        // Dani (2024-07-24): Si realizamos el pago por CURL, modificamos los
        // endpoints de Redsys y no enviamos los campos `URLOK` y `URLKO`.
        if ($curl) {
            $this->test_url = 'https://sis-t.redsys.es:25443/sis/rest/trataPeticionREST';
            $this->prod_url = 'https://sis.redsys.es/sis/rest/trataPeticionREST';
        } else {
            // Dani (2025-02-06): Mostramos la pasarela de pago en el idioma del
            // usuario, siendo español por defecto.
            // https://github.com/creagia/redsys-php/blob/main/src/Enums/ConsumerLanguage.php
            $idiomas = [
                'es' => 1,
                'en' => 2,
                'fr' => 4,
                'de' => 5,
            ];
            $idioma = $_REQUEST['idioma'] ?? 'es';
            $consumerLanguage = array_key_exists($idioma, $idiomas)
                ? $idiomas[$idioma]
                : $idiomas['es'];
            $miObj->setParameter("DS_MERCHANT_CONSUMERLANGUAGE", $consumerLanguage);
            $miObj->setParameter("DS_MERCHANT_URLOK", $this->get_success_url());
            $miObj->setParameter("DS_MERCHANT_URLKO", $this->get_cancel_url($this->payment_record_id));
        }

        // Dani (2024-10-04): Añadido parámetro $extraParams para obtener más
        // información en la petición que hace el banco a nuestro ipn.php
        $miObj->setParameter("DS_MERCHANT_MERCHANTURL", $this->get_ipn_url($this->payment_record_id, $insertData['ipnExtraParams'] ?? []));

        if ($isBizum)
            $miObj->setParameter("DS_MERCHANT_PAYMETHODS", "z");

        if ($payment && $payment['card_id'] && $payment['card_caduc']) {
            $caducidad = strtotime(substr($payment['card_caduc'], 0, 2) . "-" . substr($payment['card_caduc'], 2) . "-28 23:59:59");
            if ($caducidad >= time()) { // Aún no ha caducado
                $miObj->setParameter("DS_MERCHANT_IDENTIFIER", $payment['card_id']);
                $miObj->setParameter("DS_MERCHANT_DIRECTPAYMENT", true); // Pago sin pantallas adicionales
                // Dani (2024-07-04): `DS_MERCHANT_EXCEP_SCA` es necesario para
                // indicar que la transacción ha sido iniciada por nosotros sin
                // intervención del titular de la tarjeta (e.j pagos domiciliados)
                // https://pagosonline.redsys.es/desarrolladores-inicio/documentacion-funcionalidades-avanzadas/tokenizacion/
                $miObj->setParameter("DS_MERCHANT_EXCEP_SCA", "MIT");
            }
        }
        $params = $miObj->createMerchantParameters();
        $signature = $miObj->createMerchantSignature($cred['key']);
        $this->helper->paypal_url = $this->get_url();
        $this->helper->add_field('Ds_SignatureVersion', 'HMAC_SHA256_V1');
        $this->helper->add_field('Ds_MerchantParameters', $params);
        $this->helper->add_field('Ds_Signature', $signature);
        if (self::$isTest) {
            $this->helper->dump_fields();
        }

        // Dani (2024-07-24): Añadido `return` para devolver la respuesta del
        // CURL si se ha realizado el pago por CURL.
        return $this->helper->submit_paypal_post($curl);
    }

    function refundAmount($referencia, $amount)
    {
        // Mat (2024-08-06): Creamos esta función para poder generar una devolución mediante el identificador y la cantidad de un pago ya realizado.
        $cred = $this->get_credentials();
        $miObj = new RedsysAPI;
        $miObj->setParameter("DS_MERCHANT_AMOUNT", ((float) floor(self::parse_number($amount) * 100)));
        $miObj->setParameter("DS_MERCHANT_ORDER", $referencia);
        $miObj->setParameter("DS_MERCHANT_MERCHANTCODE", $cred['merchant']);
        $miObj->setParameter("DS_MERCHANT_CURRENCY", $cred['currency']);
        $miObj->setParameter("DS_MERCHANT_TRANSACTIONTYPE", 3);
        $miObj->setParameter("DS_MERCHANT_TERMINAL", $cred['terminal']);
        $params = $miObj->createMerchantParameters();
        $signature = $miObj->createMerchantSignature($cred['key']);

        if (self::$isTest) {
            $this->helper->paypal_url = 'https://sis-t.redsys.es:25443/sis/rest/trataPeticionREST';
        } else {
            $this->helper->paypal_url = 'https://sis.redsys.es/sis/rest/trataPeticionREST';
        }

        $this->helper->add_field('Ds_SignatureVersion', 'HMAC_SHA256_V1');
        $this->helper->add_field('Ds_MerchantParameters', $params);
        $this->helper->add_field('Ds_Signature', $signature);

        $res = $this->helper->submit_paypal_post(true, false);
        $res = json_decode($res, true);
        return $res;
    }

    function is_bizum()
    {
        return @$this->bizum ? true : false;
    }

    function subscribe($quantity, $insertData = null, $subscription_args = [])
    {
        parent::subscribe($quantity, $insertData);
        $cof_ini = @$subscription_args["cof_ini"] ? $subscription_args["cof_ini"] : "S";
        $cof_type = @$subscription_args["cof_type"] ? $subscription_args["cof_type"] : "I";
        $cred = $this->get_credentials();
        $miObj = new RedsysAPI;
        $miObj->setParameter("DS_MERCHANT_AMOUNT", ((float) floor(self::parse_number($quantity) * 100)));
        $miObj->setParameter("DS_MERCHANT_ORDER", $this->reference);
        $miObj->setParameter("DS_MERCHANT_MERCHANTCODE", $cred['merchant']);
        $miObj->setParameter("DS_MERCHANT_CURRENCY", $cred['currency']);
        $miObj->setParameter("DS_MERCHANT_TRANSACTIONTYPE", 0);
        $miObj->setParameter("DS_MERCHANT_IDENTIFIER", 'REQUIRED');
        $miObj->setParameter("DS_MERCHANT_TERMINAL", $cred['terminal']);
        // Datos de suscripcion
        $miObj->setParameter("DS_MERCHANT_IDENTIFIER", 'REQUIRED'); //Pedir que banco guarde tarjeta
        $miObj->setParameter("DS_MERCHANT_COF_INI", $cof_ini);  // Es una petición inicial de tokenizacion
        // $miObj->setParameter("DS_MERCHANT_COF_TYPE", 'I'); // Pago aplazado, un solo pago
        // $miObj->setParameter("DS_MERCHANT_COF_TYPE", 'R'); // Pago aplazado, un solo pago
        $miObj->setParameter("DS_MERCHANT_COF_TYPE", $cof_type); // Pago aplazado, pago recurrente
        // Dani (2024-10-04): Añadido parámetro $extraParams para obtener más
        // información en la petición que hace el banco a nuestro ipn.php
        $miObj->setParameter("DS_MERCHANT_MERCHANTURL", $this->get_ipn_url($this->payment_record_id, $insertData['ipnExtraParams'] ?? []));
        $miObj->setParameter("DS_MERCHANT_PRODUCTDESCRIPTION", $this->get_product_description());
        $miObj->setParameter("DS_MERCHANT_URLOK", $this->get_success_url());
        $miObj->setParameter("DS_MERCHANT_URLKO", $this->get_cancel_url($this->payment_record_id));
        $params = $miObj->createMerchantParameters();
        $signature = $miObj->createMerchantSignature($cred['key']);
        $this->helper->paypal_url = $this->get_url();
        $this->helper->add_field('Ds_SignatureVersion', 'HMAC_SHA256_V1');
        $this->helper->add_field('Ds_MerchantParameters', $params);
        $this->helper->add_field('Ds_Signature', $signature);
        if (self::$isTest) {
            $this->helper->dump_fields();
        }
        $this->helper->submit_paypal_post();
    }
}
