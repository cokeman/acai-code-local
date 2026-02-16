<section id="contenido_principal">
    <div class="separa-20"></div>
    <div class="container">
        <div class="separa-40"></div>
        <h1 class="titular"><?=@$apartado["titulo_alternativo"] ? t($apartado, "titulo_alternativo") : t($apartado, "name");?> <span class="cesta-count"></span></h1>
        <div class="separa-20"></div>
        <div class="contenido row"> <!-- CONTENIDO -->
            <div class="col-md-12">
                <div id="cesta"></div>
                <div id="pago" class="hidden">
                    <div class="separa-30"></div>
                    <h3 class="titular"><?=t_var("Datos de envío");?></h3>
                    <form action="#" method="post" id="formulario-compra">
                        <div class="row">
                            <div class="form-group col-sm-12">
                                <label>
                                    <?=t_var("Nombre");?> *
                                    <input type="text" class="form-control" name="datos[nombre]" placeholder="<?=t_var("Nombre");?>" required value="<?=@$user["nombre"];?>">
                                </label>
                            </div>
                            <div class="form-group col-sm-6">
                                <label>
                                    <?=t_var("Email");?> *
                                    <input type="email" class="form-control" name="datos[email]" placeholder="<?=t_var("Email");?>" required value="<?=@$user["email"];?>">
                                </label>
                            </div>
                            <div class="form-group col-sm-6">
                                <label>
                                    <?=t_var("Teléfono");?> *
                                    <input type="tel" class="form-control" name="datos[telefono]" placeholder="<?=t_var("Teléfono");?>" required value="<?=@$user["telefono"];?>">
                                </label>
                            </div>
                            <div class="form-group col-sm-6">
                                <label>
                                    <?=t_var("País");?> *
                                    <input type="text" class="form-control" name="datos[pais]" placeholder="<?=t_var("País");?>" required value="<?=@$user["pais"];?>">
                                </label>
                            </div>
                            <div class="form-group col-sm-6">
                                <label>
                                    <?=t_var("Provincia");?> *
                                    <input type="text" class="form-control" name="datos[provincia]" placeholder="<?=t_var("Provincia");?>" required value="<?=@$user["provincia"];?>">
                                </label>
                            </div>
                            <div class="form-group col-sm-6">
                                <label>
                                    <?=t_var("Dirección");?> *
                                    <input type="text" class="form-control" name="datos[direccion]" placeholder="<?=t_var("Dirección");?>" required value="<?=@$user["direccion"];?>">
                                </label>
                            </div>
                            <div class="form-group col-sm-6">
                                <label>
                                    <?=t_var("Código postal");?> *
                                    <input type="text" class="form-control" name="datos[codigo_postal]" placeholder="<?=t_var("Código postal");?>" required value="<?=@$user["codigo_postal"];?>">
                                </label>
                            </div>
                        </div>
                    </form>
                    <div class="separa-20"></div>
                    <div id="metodos-pago">
                        <? if (@$configuracionTienda["pago_por_paypal"]) {?>
                        <a href="<?=RUTA_RAIZ;?>/cesta.php?tipo_de_pago=paypal&paypal_checkout=1"><img src="<?=RUTA_PLANTILLA;?>/images/paypal.svg" alt="<?=t_var("Pago por Paypal");?>"><?=t_var("Pago por Paypal");?></a>
                        <? }?>
                        <? if (@$configuracionTienda["pago_por_tpv"]) {?>
                        <a href="<?=RUTA_RAIZ;?>/cesta.php?tipo_de_pago=tpv&paypal_checkout=1"><img src="<?=RUTA_PLANTILLA;?>/images/tpv.svg" alt="<?=t_var("Pago por TPV");?>"><?=t_var("Pago por TPV");?></a>
                        <? }?>
                        <? if (@$configuracionTienda["pago_por_stripe"]) {?>
                        <a href="<?=RUTA_RAIZ;?>/cesta.php?tipo_de_pago=stripe&paypal_checkout=1" id="stripe-button"><img src="<?=RUTA_PLANTILLA;?>/images/stripe.svg" alt="<?=t_var("Pago por Stripe");?>"><?=t_var("Pago por Stripe");?></a>
                        <? }?>
                        <? if (@$configuracionTienda["pago_por_transferencia"]) {?>
                        <a href="<?=RUTA_RAIZ;?>/cesta.php?tipo_de_pago=transferencia&paypal_checkout=1" id="transferencia-button"><img src="<?=RUTA_PLANTILLA;?>/images/transferencia.png" alt="<?=t_var("Pago por transferencia");?>"><?=t_var("Pago por transferencia");?></a>
                        <? }?>
                    </div>
                </div>
                <div id="no-products" class="hidden">
                    <p class="text-center"><?=t_var("Sin productos");?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="separa-40"></div>
</section>

<script>
    var jsonCesta = null;
    window.onload = function() {
        <? if (@$gastos_de_envio) {?>
        var getGastosDeEnvio = (selected = 0) => {
            var res = `<select name="gastos_de_envio" class="form-control envio">
<?
    foreach ($gastos_de_envio as $gasto) {
?>
<option value="<?=$gasto["num"];?>" ${selected == <?=$gasto["num"];?> ? 'selected' : ''}><?=t($gasto, "zona");?></option>
        <?
                                         }
    ?>
    </select>`;
        return res;
    }
        <? }?>

    var getBasketItemHTML = (product) => {
        var res = `<div class="producto-cesta wrapper-flex"><button class="remove wrapper-flex" data-producto="${product.num}" data-variacion="${product.variacionNum}"><i class="fa fa-times"></i></button>`;
        res += `<div class="img">
<img src="${product.foto[0]}" alt="${product.name}">
    </div>
<div>
<h3>${product.name}</h3>`;
        if (product.descripcion_corta) {
            res += `<p>${product.descripcion_corta}</p>`;
        }

        if (product.opciones) {
            res += '<ul class="bullet opciones">';
            for (const opcion of product.opciones) {
                res += `<li>${opcion.campo}: ${opcion.valor}</li>`;
            }
            res += '</ul>';
        }

        if (product.stock > 0) {
            res += `<label><?=t_var("Cantidad");?></label><select class="form-control cantidad" value="${product.cantidad}" data-producto="${product.num}" data-variacion="${product.variacionNum}">`;
            var max = Math.min(product.stock, 30);
            for (var i = 1; i <= max; i++)
                res += `<option value="${i}" ${(product.cantidad == i ? 'selected' : '')}>${i}</option>`;
            res += '</select>';
        }

        if (product.oferta) {
            res += `<p class="precio alt"><?=t_var("Precio");?>: <span class="tachado" aria-label"<?=t_var("Precio original");?>">${product.precioTexto}/u.</span> ${product.oferta.precio}/u.<br><small>Descuento ${product.oferta.descuento} %</small></p>`;
        }
        else {
            res += `<p class="precio"><?=t_var("Precio");?>: ${product.precioTexto}/u.</p>`;
        }
        res += '</div></div>';
        return res;
    };

    function downloadBasket(firstTime = false) {
        document.getElementById('pago').classList.add('hidden');
        Cesta.getProducts('<?=session_id();?>')
            .then(function(json) {
            jsonCesta = json.cesta;
            var products = Object.keys(json.cesta.productos);
            var cesta = document.getElementById('cesta');
            cesta.innerHTML = '';
            if (products.length) {
                products.forEach(function(id) {
                    cesta.innerHTML += getBasketItemHTML(json.cesta.productos[id]);
                });

                <? if (@$configuracionTienda["codigos_descuento"]) {?>
                cesta.innerHTML += `
    <div class="descuento">
    <div class="form-group-flex">
    <label><?=t_var("Códigos descuento");?></label>
    <input class="form-control" placeholder="<?=t_var("Códigos descuento");?>" type="text" value="${json.cesta.codigo}">
    <button class="btn btn-success canjear"><?=t_var("Canjear");?></button>
        </div>
        </div>
    `;
                <? }?>

                cesta.innerHTML += `
    <div class="checkout">
    <div>
    <?=t_var("Subtotal");?>: <span class="precio">${json.cesta.desglose.subtotalTexto}</span>
        </div>
    ${json.cesta.desglose.descuento ? '<div><?=t_var("Descuento");?>: <span class="precio">'+json.cesta.desglose.descuentoTexto+'</span></div>' : ''}
    <? if (@$gastos_de_envio) {?>
    <div>
    <?=t_var("Envío");?>: ${getGastosDeEnvio(json.cesta.desglose.numEnvio)}<span class="precio">${json.cesta.desglose.envioTexto}</span>
        </div>
    <? }?>
    <div>
    <?=t_var("Total");?>: <span class="precio">${json.cesta.desglose.totalTexto}</span>
        </div>
        </div>
    `;

                bindRemoveButtons();
                bindQuantitySelectors();
                bindShipping();
                document.getElementById('pago').classList.remove('hidden');
                <? if (@$configuracionTienda["codigos_descuento"]) {?>bindDiscount();<?}?>
                if (firstTime) downloadBasket();
            }
            else {
                document.getElementById('no-products').classList.remove('hidden');
            }
        });
    }
    downloadBasket(true);

    function bindRemoveButtons() {
        var buttons = document.querySelectorAll('.remove[data-producto]');
        if (!buttons) return;
        for (const button of buttons) {
            button.addEventListener('click', function(e) {
                swal({
                    title: "<?=t_var("Atención");?>",
                    text: "<?=t_var("¿Deseas borrar este producto de tu cesta?");?>",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true
                })
                    .then((willDelete) => {
                    if (willDelete) {
                        var b = this;
                        while (!b.getAttribute('data-producto')) b = b.parentNode;
                        var clickedProduct = parseInt(b.getAttribute('data-producto'));
                        Cesta.remove(clickedProduct);
                        downloadBasket();
                    }
                });
            });
        }
    }

    function bindQuantitySelectors() {
        var quantitySelectors = document.querySelectorAll('.cantidad[data-producto]');
        if (!quantitySelectors) return;
        for (const selector of quantitySelectors) {
            selector.addEventListener('change', function(e) {
                var select = this;
                var productID = parseInt(select.getAttribute('data-producto'));
                var variacionID = parseInt(select.getAttribute('data-variacion'));
                Cesta.setQuantity(productID, variacionID, select.value);
                downloadBasket();
            });
        }
    }

    function bindShipping() {
        var shippingSelector = document.querySelector('select[name=gastos_de_envio]');
        if (!shippingSelector) return;
        shippingSelector.addEventListener('change', function(e) {
            downloadBasket();
        });
    }

    <? if (@$configuracionTienda["codigos_descuento"]) {?>
    function bindDiscount() {
        var discountInput = document.querySelector('#cesta .descuento');
        if (!discountInput) return;
        discountInput.querySelector('button').addEventListener('click', function(e) {
            downloadBasket();
        });
    }
    <? }?>
    };

    var metodosPago = document.querySelectorAll('#metodos-pago a:not(#stripe-button)');
    for (const metodo of metodosPago) {
        metodo.addEventListener('click', function(e) {
            e.preventDefault();
            const data = getFormData();
            const keys = Object.keys(data);
            for (const key of keys) {
                if (!data[key] || data[key] === '') {
                    swal('<?=t_var("Error");?>', '<?=t_var("Por favor, rellena todos los campos");?>', 'error');
                    return;
                }
            }

            const form = document.getElementById('formulario-compra');
            form.setAttribute('action', metodo.href);
            form.submit();
        });
    }

    function getFormData() {
        const form = document.getElementById('formulario-compra');
        const inputs = form.querySelectorAll('input');
        const data = {};

        for (const input of inputs) {
            const index = input.getAttribute('name').replace(/(datos\[)([\w]+)(\])/, '$2');
            data[index] = input.value.trim();
        }

        return data;
    }
</script>


<? if (@$configuracionTienda["pago_por_stripe"]) {?>
<script src="https://checkout.stripe.com/checkout.js"></script>
<script>
    <?
    $posiblesLogos = array("logo.svg", "logo.png", "logo.jpg");
    $foto = "";
    foreach ($posiblesLogos as $logo):
        if (file_exists(dirname(__FILE__)."/images/".$logo)) {
            $foto = parsea_imagen(RUTA_PLANTILLA."/images/".$logo, true);
            break;
        }
    endforeach;
    ?>
    var handler = StripeCheckout.configure({
        key: '<?=$configuracionTienda["clave_publica_stripe"];?>',
        image: '<?=$foto;?>',
        locale: 'auto',
        token: function(token) {
            // You can access the token ID with `token.id`.
            // Get the token ID to your server-side code for use.
            var link = document.getElementById('stripe-button').href;
            link += `&stripeToken=${token.id}&stripeEmail=${token.email}`;

            const form = document.getElementById('formulario-compra');
            form.setAttribute('action', link);
            form.submit();
        }
    });

    document.getElementById('stripe-button').addEventListener('click', function(e) {
        e.preventDefault();
        // Open Checkout with further options:
        const data = getFormData();
        const keys = Object.keys(data);
        for (const key of keys) {
            if (!data[key] || data[key] === '') {
                swal('<?=t_var("Error");?>', '<?=t_var("Por favor, rellena todos los campos");?>', 'error');
                return;
            }
        }

        var precio = Math.floor(jsonCesta.desglose.total*100);
        handler.open({
            name: '<?=addslashes($configuracionRecord["tienda_nombre_empresa"]);?>',
            description: '',
            zipCode: true,
            currency: 'eur',
            amount: precio
        });
    });

    // Close Checkout on page navigation:
    window.addEventListener('popstate', function() {
        handler.close();
    });
</script>
<? }?>
