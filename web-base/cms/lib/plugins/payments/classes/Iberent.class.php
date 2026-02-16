<?php
require_once __DIR__ . "/vendor/iberent/Client.php";
require_once __DIR__ . "/PaymentMethod.class.php";
class Iberent extends PaymentMethod
{
    public $order = [];
    public $transaction = [];
    public $token = "";

    // Force updateState URL
    // https://ibe.rent/order/test/<access_token>/<order_id>/<status>
    
    // Example : 
    // https://ibe.rent/order/test/{{token}}/{{iberent_code}}/pending
    // IPN Url event : /cms/lib/plugins/payments_banana/ipn.php?ipn={{payment_num}}


    function __construct()
    {
        self::get_config();
        $this->token = self::$config['iberent_token'];
        $this->init(43, 'Iberent', 'https://api-test.iberent.es', [], 'https://api.iberent.es', []);
    }

    function can_be_used() {
        $cred = $this->get_credentials();
        return true;
    }

    function setTransaction($quantity, $insertData = null, $payment = null)
    {
        $this->transaction = array(
            'lifetime' => 1800, //in seconds
            'callback_urls' => json_encode(array(
                'pending' => $this->get_success_url(),
                'canceled' => $this->get_cancel_url($this->payment_record_id),
                'completed' => $this->get_success_url(),
            )),
            'meta' => array(
                /*'account_creation_date' => '2020-07-10', //YYYY-MM-DD
                'pickup' => true, //boolean
                'succeed_past_order' => 2,
                'last_past_order' => '2020-09-21', //YYYY-MM-DD*/)
        );

        return $this->transaction;
    }

    function setOrder($quantity, $insertData = null, $payment = null)
    {
        $items = [];
        $usuario_email = @CocoDB::get('usuarios', ['num' => $insertData["records"]["user"]], '', 1, ['ignoreSchema' => true])[0]['correo'];
        foreach ($insertData["records"]["productos"] as $producto) {
            $items[] = [
                'product' => array(
                    'sku_number' => $producto["referencia"],
                    'part_number' => '',
                    'model' => '',
                    'description' => $producto["title"],
                    //'brand' => 'Apple',
                    //'category' => $this->getProductMainCategory($producto, "name")
                ),
                'unit_price' => floatval($producto["price"]),
                'quantity' => $producto["quantity"],
                //'discount_percent' => round(@$producto["campanaPrice"] && @$producto["campanaPrice"] != $producto["originalPrice"] ? (100 - ((float) self::parse_number($producto["campanaPrice"]) * 100 / (float) self::parse_number($producto["originalPrice"]))) : 0, 2),
                'total_price' => floatval(self::parse_number($producto["price"]) * $producto["quantity"])
            ];
        }
        $this->order = array(
            'amount' => ((float) self::parse_number($quantity)),
            'client' => array(
                'company_name' => @$insertData["records"]["direccion_facturacion"]["razonSocial"] ?: @$insertData["records"]["direccion_facturacion"]["nombre"] . " " . @$insertData["records"]["direccion_facturacion"]["apellidos"],
                'vat_id_number' => $insertData["records"]["direccion_facturacion"]["dni"],
                'contact' => array(
                    'name' => @$insertData["records"]["direccion_facturacion"]["nombre"] . " " . @$insertData["records"]["direccion_facturacion"]["apellidos"],
                    'email' => @$usuario_email,
                    'phone' => @$insertData["records"]["direccion_facturacion"]["telefono"]
                ),
            ),
            'events_url' => $this->get_ipn_url($this->payment_record_id),
            'items' => $items,
            'addresses' => array(
                'billing' => array(
                    'street' => $insertData["records"]["direccion_facturacion"]["direccion"],
                    'postal_code' => $insertData["records"]["direccion_facturacion"]["codigo_postal"],
                    'city' => trim(@explode(":", $insertData["records"]["direccion_facturacion"]["poblacion_bd"][0]["breadcrumb"])[1] ?: $insertData["records"]["direccion_facturacion"]["poblacion_bd"][0]["breadcrumb"]),
                    'province' => trim(@explode(":", $insertData["records"]["direccion_facturacion"]["poblacion_bd"][0]["breadcrumb"])[0] ?: $insertData["records"]["direccion_facturacion"]["poblacion_bd"][0]["breadcrumb"]),
                    'country' => 'España',
                ),
                'shipping' => array(
                    'street' => $insertData["records"]["direccion_envio"]["direccion"],
                    'postal_code' => $insertData["records"]["direccion_envio"]["codigo_postal"],
                    'city' => trim(@explode(":", $insertData["records"]["direccion_envio"]["poblacion_bd"][0]["breadcrumb"])[1] ?: $insertData["records"]["direccion_envio"]["poblacion_bd"][0]["breadcrumb"]),
                    'province' => trim(@explode(":", $insertData["records"]["direccion_envio"]["poblacion_bd"][0]["breadcrumb"])[0] ?: $insertData["records"]["direccion_envio"]["poblacion_bd"][0]["breadcrumb"]),
                    'country' => 'España',
                )
            )
        );

        return $this->order;
    }

    function pay($quantity, $insertData = null, $payment  = null, $curl = false)
    {
        parent::pay($quantity, $insertData, $payment);

        $client = new Iberent\Client($this->token, '', self::$isTest ? false : true);

        $transaction = $this->setTransaction($quantity, $insertData, $payment);
        $order = $this->setOrder($quantity, $insertData, $payment);
        $response = $client->openTransaction($this->reference, $transaction, $order);

        $checkout_url = @$response["result"]["checkout_url"];
        if (!$checkout_url) {
            if(self::$isTest) {
                var_dump($checkout_url);
            }
            die("Error de petición. contacte con un administrador.");
        }

        $this->helper->paypal_url = $checkout_url;

        if (self::$isTest) {
            $this->helper->add_field("ORDER", json_encode($order,JSON_PRETTY_PRINT));
            $this->helper->add_field("TRANSACTION", json_encode($transaction,JSON_PRETTY_PRINT));
            $this->helper->dump_fields();
        }

        $this->helper->submit_paypal_post($curl);
    }
}
