<?php
require_once __DIR__.'/../Stripe/vendor/autoload.php';

require_once __DIR__."/PaymentMethod.class.php";
class Stripe extends PaymentMethod {
    function __construct() {
        self::get_config();
        $this->init(5, 'Stripe', '', [
            'stripe_pk' => self::$config['test_stripe_pk'],
            'stripe_sk' => self::$config['test_stripe_sk'],
            'webhook_sk' => self::$config['test_webhook_sk']
        ],
        '', 
        [
            'stripe_pk' => self::$config['stripe_pk'],
            'stripe_sk' => self::$config['stripe_sk'],
            'webhook_sk' => self::$config['webhook_sk']
        ]);
    }

    function can_be_used() {
        $cred = $this->get_credentials();
        return @$cred['stripe_sk'] ? true : false;
    }

    function pay($quantity, $insertData = null, $payment  = null, $curl = false, $return_result = false) {
        $inserted_payment = parent::pay($quantity, $insertData);
        $cred = $this->get_credentials();
        \Stripe\Stripe::setApiKey($cred["stripe_sk"]);

  
        $paymentData = [
            'metadata' => [
                'payment_num' => sha1($inserted_payment),
            ],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => "Cesta de productos",
                    ],
                    'unit_amount' => round($quantity * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' =>"https://".$_SERVER["HTTP_HOST"].$insertData['urlok'],
            'cancel_url' => "https://".$_SERVER["HTTP_HOST"].$insertData['urlko'],
        ];
        $pay = \Stripe\Checkout\Session::create($paymentData);
        if(!$return_result){
            header("location: ".$pay->url);
        }else{
            return [
                'url' => $pay->url,
                'session_id' => $pay->id,
                'customer_id' => null,
                'type' => 'payment',
                'total_pago' => round($quantity * 100)
            ];
    
        }
    }

    function payProduct($priceId, $isSubscription, $quantity, $insertData = null, $payment  = null, $curl = false, $return_result = false, $taxStripeId = null) {
        $inserted_payment = parent::pay($quantity, $insertData);
        $cred = $this->get_credentials();
        \Stripe\Stripe::setApiKey($cred["stripe_sk"]);

        // Crear una sesión en stripe dado un identificador de producto de Stripe y una cantidad
        
        $paymentData = [
            'metadata' => [
                'payment_num' => sha1($inserted_payment),
            ],
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'mode' => $isSubscription ? 'subscription' : 'payment',
            'success_url' => strpos($insertData['urlok'], "http") === 0 ? $insertData['urlok'] : "https://".$_SERVER["HTTP_HOST"].$insertData['urlok'],
            'cancel_url' => strpos($insertData['urlko'], "http") === 0 ? $insertData['urlko'] : "https://".$_SERVER["HTTP_HOST"].$insertData['urlko'],
            'allow_promotion_codes' => true
        ];

        // Si hay tax y es suscripción, añade así:
        if ($taxStripeId && $isSubscription) {
            $paymentData['subscription_data'] = [
                'default_tax_rates' => [$taxStripeId]
            ];
        } elseif ($taxStripeId) {
            foreach($paymentData["line_items"] as &$item) {
                $item['tax_rates'] = [$taxStripeId];
            }
        }

        if (@$insertData['customer_email']) {
            $paymentData['customer_email'] = $insertData['customer_email'];
        }
        if (@$insertData['customer_id']) {
            $paymentData['customer'] = $insertData['customer_id'];
        }

        try{
            $pay = \Stripe\Checkout\Session::create($paymentData);
            if(!$return_result){
                header("location: ".$pay->url);
            }else{
                return [
                    'url' => $pay->url,
                    'session_id' => $pay->id,
                    'customer_id' => null,
                    'type' => $isSubscription ? 'subscription' : 'payment',
                    'total_pago' => round($quantity * 100)
                ];
        
            }
        }catch(\Stripe\Exception\ApiErrorException $e){
            return [
                'error' => $e->getMessage(),
                'type' => $isSubscription ? 'subscription' : 'payment',
                'total_pago' => round($quantity * 100)
            ];
        }


    }

    function updateInvoiceFooter($invoiceId, $footerText) {
        $cred = $this->get_credentials();
        \Stripe\Stripe::setApiKey($cred["stripe_sk"]);

        // Actualizar el pie de página de una factura en stripe
        try {
            $invoice = \Stripe\Invoice::update($invoiceId, [
                'footer' => $footerText
            ]);
            return ["success" => true, "invoice" => $invoice];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    function createInvoice($invoiceItems,$customer_id,$taxStripeId, $payment_intent = null){
        $cred = $this->get_credentials();
        \Stripe\Stripe::setApiKey($cred["stripe_sk"]);

        // Crear una factura en stripe
        try {
            $invoice = \Stripe\Invoice::create([
                'customer' => $customer_id,
                'auto_advance' => true, // Auto-advance the invoice
                'collection_method' => 'send_invoice',
                'days_until_due' => 30,
                'metadata' => [
                    'payment_intent' => $payment_intent,
                    'created_by' => 'Quantum Asis'
                ]
            ]);

            foreach ($invoiceItems as $item) {
                $invoiceItem = \Stripe\InvoiceItem::create([
                    'customer' => $customer_id,
                    'amount' => round($item['amount'] * 100),
                    'currency' => 'eur',
                    'description' => $item['description'],
                    'invoice' => $invoice->id,
                    'tax_rates' => [$taxStripeId]
                ]);
            }

            try{
                $invoice = \Stripe\Invoice::finalizeInvoice($invoice->id, []);
            }catch(\Stripe\Exception\ApiErrorException $e){
                return [
                    'error' => $e->getMessage(),
                ];
            }
            

            //$invoice = \Stripe\Invoice::retrieve($invoice->id);
            $link = $invoice->hosted_invoice_url;

            return ["success" => true, "invoice" => $invoice,"url" => $link];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    function checkCustomer($customerId) {
        $cred = $this->get_credentials();
        \Stripe\Stripe::setApiKey($cred["stripe_sk"]);

        // Comprobar si un cliente existe en stripe dado un identificador de cliente de Stripe
        try {
            $customer = \Stripe\Customer::retrieve($customerId);
            return ["success" => true, "customer" => $customer];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
    function insertCustomer($invoiceData){
        $cred = $this->get_credentials();
        \Stripe\Stripe::setApiKey($cred["stripe_sk"]);

        // Insertar un cliente en stripe
        try {
            $customer = \Stripe\Customer::create([
                'name' => $invoiceData['nombre'],
                'email' => $invoiceData['email'],
                'phone' => '',
                'address' => [
                    'line1' => $invoiceData['direccionFiscal'],
                    'postal_code' => $invoiceData['codigoPostal'],
                    'city' => $invoiceData['ciudad'],
                    'state' => $invoiceData['provincia'],
                    'country' => $invoiceData['pais']
                ],
                'metadata' => [
                    'nif' => $invoiceData['nif']
                ]
            ]);
            return ["success" => true, "customer" => $customer];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    function updateCustomerAddress($customer_id,$invoiceData){
        $cred = $this->get_credentials();
        \Stripe\Stripe::setApiKey($cred["stripe_sk"]);

        // Actualizar la dirección de un cliente en stripe
        try {
            $customer = \Stripe\Customer::update($customer_id, [
                'address' => [
                    'line1' => $invoiceData['direccionFiscal'],
                    'postal_code' => $invoiceData['codigoPostal'],
                    'city' => $invoiceData['ciudad'],
                    'state' => $invoiceData['provincia'],
                    'country' => $invoiceData['pais']
                ],
                'metadata' => [
                    'nif' => $invoiceData['nif']
                ]
            ]);
            return ["success" => true, "customer" => $customer];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
    function cancelSubscriptionAtEnd($subscriptionID, $reactivate = false) {
        $cred = $this->get_credentials();
        \Stripe\Stripe::setApiKey($cred["stripe_sk"]);

        try {
            $subscription = \Stripe\Subscription::update($subscriptionID, [
                'cancel_at_period_end' => $reactivate ? false : true, // 👈 Esta es la clave
            ]);

            return [
                "success" => true,
                "cancel_at" => date('Y-m-d H:i:s', $subscription->cancel_at),
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    function cancelSubscription($subscriptionID) {
        $cred = $this->get_credentials();
        \Stripe\Stripe::setApiKey($cred["stripe_sk"]);

        // Cancelar una suscripción en stripe dado un identificador de suscripción de Stripe
        try{
            $subscription = \Stripe\Subscription::retrieve($subscriptionID);
            $subscription->cancel();
            return ["success" => true];
        }catch(\Stripe\Exception\ApiErrorException $e){
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    function createTaxRate($name, $description, $percentage, $inclusive = false, $country = "ES", $state = "CN") {
        $cred = $this->get_credentials();
        \Stripe\Stripe::setApiKey($cred["stripe_sk"]);

        // Crear una tasa de impuesto en stripe
        try {
            $tax_rate = \Stripe\TaxRate::create([
                'display_name' => $name,
                'percentage' => $percentage,
                'inclusive' => $inclusive,
                'country' => $country ?? 'ES', // Default to Spain
                'state' => $state ?? 'CN', // Default to Canarias
                'description' => $description ?? 'Impuesto',
            ]);
            return ["success" => true, "tax_rate" => $tax_rate];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    function getCustomerPortal($customerId, $returnUrl) {
        
        $cred = $this->get_credentials();
        \Stripe\Stripe::setApiKey($cred["stripe_sk"]);

        // Crear una sesión de portal de cliente
        try{
            $session = \Stripe\BillingPortal\Session::create([
                'customer' => $customerId,
                'return_url' => $returnUrl,
            ]);
        }catch(\Stripe\Exception\ApiErrorException $e){
            return [
                'error' => $e->getMessage(),
            ];
        }
        

        return ["success" => true,"url" => $session->url,"session" => $session];
    }

    function updateSubscription($subscriptionID, $allPricesID, $insertData = null, $payment  = null, $curl = false, $return_result = false) {
        $cred = $this->get_credentials();
        \Stripe\Stripe::setApiKey($cred["stripe_sk"]);

        // Actualizar una suscripción en stripe dado un identificador de suscripción de Stripe y una lista de precios
        $subscription = \Stripe\Subscription::retrieve($subscriptionID);
        
        // Obtener los items actuales de la suscripción
        $currentItems = $subscription->items->data;
        
        // Preparar los items para la actualización
        $itemsToUpdate = [];
        
        // Marcar items existentes para eliminar
        foreach ($currentItems as $item) {
            $itemsToUpdate[] = [
            'id' => $item->id,
            'deleted' => true
            ];
        }
        
        
        // Agregar los nuevos precios
        foreach ($allPricesID as $priceId) {
            $itemsToUpdate[] = [
            'price' => $priceId,
            'quantity' => 1
            ];
        }

        $itemsToUpdateWithQuantityRepeated = [];
        foreach ($itemsToUpdate as $item) {
            if (isset($item['price'])) {
                $priceId = $item['price'];
                if (!isset($itemsToUpdateWithQuantityRepeated[$priceId])) {
                    $itemsToUpdateWithQuantityRepeated[$priceId] = [
                        'price' => $priceId,
                        'quantity' => 0
                    ];
                }
                $itemsToUpdateWithQuantityRepeated[$priceId]['quantity'] += $item['quantity'] ?? 1;
            } else {
                $itemsToUpdateWithQuantityRepeated[] = $item;
            }
        }
        $itemsToUpdate = array_values($itemsToUpdateWithQuantityRepeated);

        $subscription = \Stripe\Subscription::update(
            $subscriptionID,
            [
            'items' => $itemsToUpdate,
            'proration_behavior' => 'always_invoice'
            ]
        );

        if(!$return_result){
            header("location: ".$insertData['urlok']);
        }else{
            return [
            'url' => $insertData['urlok'],
            'session_id' => null,
            'customer_id' => null,
            'type' => 'subscription',
            'total_pago' => 0
            ];
        
        }
    }

    function getClientSubscriptionInfo($customerId) {
        $cred = $this->get_credentials();
        \Stripe\Stripe::setApiKey($cred["stripe_sk"]);

        try {
            $subscriptions = \Stripe\Subscription::all(['customer' => $customerId]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }

        return ["success" => true, "subscriptions" => $subscriptions];
    }

    function pay_embed($quantity, $insertData = null, $payment  = null, $curl = false) {
        $inserted_payment = parent::pay($quantity, $insertData);
        $cred = $this->get_credentials();
        \Stripe\Stripe::setApiKey($cred["stripe_sk"]);

  
        $paymentData = [
            'metadata' => [
                'payment_num' => sha1($inserted_payment),
            ],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => "Cesta de productos",
                    ],
                    'unit_amount' => $quantity * 100,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'ui_mode' => 'embedded',
            'return_url' =>"https://".$_SERVER["HTTP_HOST"].$insertData['urlok']
        ];
        $pay = \Stripe\Checkout\Session::create($paymentData);
        echo json_encode(array('clientSecret' => $pay->client_secret));
        die();
    }
    
    function subscribe($quantity, $insertData = null, $payment = null) {
        
        return ["error" => "Este sistema aun no se ha probado al 100%. Continuar bajo tu responsabilidad."];
        

        $cred = $this->get_credentials();
        \Stripe\Stripe::setApiKey($cred["stripe_sk"]);
        
        $records = $insertData['records'];
        
        // Analizar productos correctamente
        $recurring_items = [];
        $onetime_items = [];
        
        $inserted_payment = parent::pay($quantity, $insertData);
    
    
        // 1. PLAN BASE (siempre recurrente si existe)
        if (isset($records['plan_seleccionado']) && !empty($records['plan_seleccionado'])) {
            $plan = $records['plan_seleccionado'];
            
            // Usar el precio correcto del plan según el período
            $precio_plan = $records['periodo_facturacion'] === 'year' 
                ? floatval($plan['price_year']) 
                : floatval($plan['price']);
                
            if ($precio_plan > 0) {
                $recurring_items[] = [
                    'name' => $plan['title'],
                    'amount' => $precio_plan,
                    'interval' => $records['periodo_facturacion'] === 'year' ? 'year' : 'month',
                    'quantity' => 1
                ];
            }
        }
        
        // 2. PACKS ADICIONALES
        if (isset($records['packs_seleccionados']) && is_array($records['packs_seleccionados'])) {
            foreach ($records['packs_seleccionados'] as $pack) {
                if (isset($pack['cantidad']) && $pack['cantidad'] > 0) {
                    $pack_item = [
                        'name' => $pack['title'],
                        'amount' => floatval($pack['price']),
                        'quantity' => intval($pack['cantidad'])
                    ];
                    
                    if (strpos($pack['id_plan'], 'PRODUCTO_TOKENS') !== false) {
                        // Es un producto de tokens (pago único)
                        $onetime_items[] = $pack_item;
                    } else {
                        // Es un pack recurrente
                        $pack_item['interval'] = 'month';
                        $recurring_items[] = $pack_item;
                    }
                }
            }
        }
        if (!empty($recurring_items) && !empty($onetime_items)) {
            return $this->handleMixedPayment($recurring_items, $onetime_items, $insertData, $inserted_payment, $records);
        }
        if (!empty($recurring_items)) {
            return $this->handleSubscriptionOnly($recurring_items, $insertData, $inserted_payment, $records);
        }
    }

    function getProduct($productId) {
        $cred = $this->get_credentials();
        \Stripe\Stripe::setApiKey($cred["stripe_sk"]);

        return \Stripe\Product::retrieve($productId);
    }

    function getPrice($priceId) {
        $cred = $this->get_credentials();
        \Stripe\Stripe::setApiKey($cred["stripe_sk"]);

        return \Stripe\Price::retrieve($priceId);
    }   

    function createProduct($name, $description = '', $amount = 0, $recurring = false, $interval = 'month', $currency = 'eur') {
        try{
            $cred = $this->get_credentials();
            \Stripe\Stripe::setApiKey($cred["stripe_sk"]);
            
            // Crear un producto en Stripe
            $product = \Stripe\Product::create([
                'name' => $name,
                'description' => $description
            ]);
            
            // Crear un precio para el producto
            $productData = [
                'product' => $product->id,
                'currency' => $currency,
                'unit_amount' => round($amount * 100)
            ];
            if ($recurring) {
                $productData['recurring'] = ['interval' => $interval];
            }
            $price = \Stripe\Price::create($productData);

            return [
                'product_id' => $product->id,
                'price_id' => $price->id
            ];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    function createPrice($productId, $amount, $recurring = false, $interval = 'month', $currency = 'eur') {
        $cred = $this->get_credentials();
        \Stripe\Stripe::setApiKey($cred["stripe_sk"]);
        
        // Crear un precio para el producto
        $productData = [
            'product' => $productId,
            'currency' => $currency,
            'unit_amount' => round($amount * 100)
        ];
        if ($recurring) {
            $productData['recurring'] = ['interval' => $interval];
        }
        return \Stripe\Price::create($productData);
    }

    function updatePrice($productId, $priceId, $amount, $recurring = false, $interval = 'month', $currency = 'eur') {
        $cred = $this->get_credentials();
        \Stripe\Stripe::setApiKey($cred["stripe_sk"]);

        // Actualizar el precio del producto
        $productData = [
            'active' => false
        ];
        
        $result = \Stripe\Price::update($priceId, $productData);

        $resultCreate = $this->createPrice($productId, $amount, $recurring, $interval, $currency);
        return [
            'product_id' => $productId,
            'price_id' => $resultCreate->id
        ];
    
    }

    private function handleSubscriptionOnly($recurring_items, $insertData, $inserted_payment, $records) {
        $customer = $this->createCustomer($records, $inserted_payment, [
            'payment_type' => 'subscription'
        ]);    $total_pago = 0;
        $line_items = [];
        foreach ($recurring_items as $item) {
            $product = \Stripe\Product::create(['name' => $item['name']]);
            $product_price = round(($item['amount'] * 1.21) * 100);
            $price = \Stripe\Price::create([
                'product' => $product->id,
                'currency' => 'eur',
                'unit_amount' => $product_price,
                'recurring' => ['interval' => $item['interval']]
            ]);
            $total_pago += $product_price;
            $line_items[] = [
                'price' => $price->id,
                'quantity' => $item['quantity'] ?? 1
            ];
        }
        $session_data = [
            'customer' => $customer->id,
            'payment_method_types' => ['card'],
            'line_items' => $line_items,
            'mode' => 'subscription',
            'success_url' => "https://".$_SERVER["HTTP_HOST"].$insertData['urlok'],
            'cancel_url' => "https://".$_SERVER["HTTP_HOST"].$insertData['urlko'],
            'metadata' => [
                'payment_num' => sha1($inserted_payment),
                'user_id' => $records['user'],
                'payment_type' => 'subscription'
            ]
        ];
        
        if ($this->shouldAddTrial()) {
            $session_data['subscription_data'] = ['trial_period_days' => 30];
        }
        
        $session = \Stripe\Checkout\Session::create($session_data);
        
        return [
            'url' => $session->url,
            'session_id' => $session->id,
            'customer_id' => $customer->id,
            'type' => 'subscription',
            'total_pago' => $total_pago
        ];
    }

    private function handleMixedPayment($recurring_items, $onetime_items, $insertData, $inserted_payment, $records) {
        $total_recurring = 0;
        $total_onetime = 0;
        
        foreach ($recurring_items as $item) {
            $total_recurring += $item['amount'] * ($item['quantity'] ?? 1);
        }
        
        foreach ($onetime_items as $item) {
            $total_onetime += $item['amount'] * ($item['quantity'] ?? 1);
        }
        $total_recurring_con_iva = $total_recurring * 1.21;
        $total_onetime_con_iva = $total_onetime * 1.21;
        $precio_primer_mes = $total_recurring_con_iva + $total_onetime_con_iva;
        
        $customer = $this->createCustomer($records, $inserted_payment, [
            'recurring_amount' => $total_recurring_con_iva,
            'onetime_amount' => $total_onetime_con_iva,
            'payment_type' => 'mixed_simple'
        ]);    
        // Crear descripción detallada
        $description = sprintf(
            "Productos mensuales: %.2f€ (Base: %.2f€ + IVA: %.2f€)  Productos de pago único: %.2f€ (Base: %.2f€ + IVA: %.2f€)  A partir del próximo mes solo: %.2f€",
            $total_recurring_con_iva,
            $total_recurring,
            $total_recurring * 0.21,
            $total_onetime_con_iva,
            $total_onetime,
            $total_onetime * 0.21,
            $total_recurring_con_iva
        );
        
        $product = \Stripe\Product::create([
            'name' => 'Plan LIME Completo (Primer mes + Setup)',
            'description' => $description
        ]);
        
        $price = \Stripe\Price::create([
            'product' => $product->id,
            'currency' => 'eur',
            'unit_amount' => round($precio_primer_mes * 100),
            'recurring' => ['interval' => 'month']
        ]);
        
        $session = \Stripe\Checkout\Session::create([
            'customer' => $customer->id,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $price->id,
                'quantity' => 1
            ]],
            'mode' => 'subscription',
            'success_url' => "https://".$_SERVER["HTTP_HOST"].$insertData['urlok'],
            'cancel_url' => "https://".$_SERVER["HTTP_HOST"].$insertData['urlko'],
            'metadata' => [
                'payment_num' => sha1($inserted_payment),
                'user_id' => $records['user'],
                'payment_type' => 'mixed_simple',
                'recurring_amount' => $total_recurring_con_iva,
                'onetime_amount' => $total_onetime_con_iva,
                'total_first_month' => $precio_primer_mes
            ]
        ]);
        
        return [
            'url' => $session->url,
            'session_id' => $session->id,
            'customer_id' => $customer->id,
            'type' => 'mixed_simple',
            'total_pago' => $precio_primer_mes
        ];
    }


    private function buildDetailedLineItems($items, $type = 'recurring') {
        $line_items = [];
        
        foreach ($items as $item) {
            $precio_con_iva = $item['amount'] * 1.21;
            $description = $this->buildItemDescription($item, $type);
            
            if ($type === 'recurring') {
                $product = \Stripe\Product::create([
                    'name' => $item['name'],
                    'description' => $description
                ]);
                
                $price = \Stripe\Price::create([
                    'product' => $product->id,
                    'currency' => 'eur',
                    'unit_amount' => round($precio_con_iva * 100),
                    'recurring' => ['interval' => $item['interval'] ?? 'month']
                ]);
                
                $line_items[] = [
                    'price' => $price->id,
                    'quantity' => $item['quantity'] ?? 1
                ];
            } else {
                // Para pagos únicos, crear con más detalle
                $line_items[] = [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $item['name'],
                            'description' => $description
                        ],
                        'unit_amount' => round($precio_con_iva * 100),
                    ],
                    'quantity' => $item['quantity'] ?? 1
                ];
            }
        }
        
        return $line_items;
    }

    private function buildItemDescription($item, $type) {
        $precio_sin_iva = $item['amount'];
        $iva = $precio_sin_iva * 0.21;
        $precio_con_iva = $precio_sin_iva + $iva;
        
        $description = sprintf(
            "Base: %.2f€ + IVA (21%%): %.2f€ = Total: %.2f€",
            $precio_sin_iva,
            $iva,
            $precio_con_iva
        );
        
        if ($type === 'recurring') {
            $interval = $item['interval'] ?? 'month';
            $description .= " - Cobro " . ($interval === 'month' ? 'mensual' : 'anual');
        } else {
            $description .= " - Pago único";
        }
        
        return $description;
    }

    private function shouldAddTrial() {
        return false; 
    }
        
    private function createCustomer($records, $inserted_payment, $extra_metadata = []) {
        $metadata = [
            'payment_num' => sha1($inserted_payment),
            'user_id' => $records['user']
        ];
        
        // Añadir metadatos extra si existen
        if (!empty($extra_metadata)) {
            $metadata = array_merge($metadata, $extra_metadata);
        }
        
        return \Stripe\Customer::create([
            'email' => $records['email'] ?? '',
            'name' => $records['datos_facturacion']['razonSocial'] ?? '',
            'metadata' => $metadata
        ]);
    }
}