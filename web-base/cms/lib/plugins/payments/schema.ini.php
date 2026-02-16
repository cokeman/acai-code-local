;<?php die('This is not a program file.'); exit; ?>

version = 1.0
name = "Payments"
description = "Permite el uso de clases Helper para facilitar los pagos online. Para STRIPE importante apuntar webhook a https://dominio.com/cms/lib/plugins/payments/ipn.php"
adminOnly = 0
enabled = 0

[checkPoints]
actionHandler = ""
admin = "admin_actionHandler.php"
list_header = ""
list_filter = ""
list_record = ""
list_footer = ""
edit_header = ""
edit_field = ""
edit_footer = ""
home = ""
menu = "menu.php"
footer = ""
save_pre = ""
save = ""
save_post = ""
slug = ""
tpl_head = ""
codigos_en_linea = ""

[config]
;Activar test general:
test = ""

;Clave para pruebas test:
clave_debug = "Coco$olut10n"


;Redsys:
tpv_merchant = ""
tpv_currency = ""
tpv_terminal = ""
tpv_key = ""

test_tpv_merchant = "066424615"
test_tpv_currency = "978"
test_tpv_terminal = "001"
test_tpv_key = "sq7HjrUOBfKmC576ILgskD5srU870gJ7"


;Paypal:
paypal_account = ""

test_paypal_account = "jordan_1342086706_biz@gmail.com"


;Stripe:
stripe_pk = ""
stripe_sk = ""
webhook_sk = ""

test_stripe_pk = ""
test_stripe_sk = ""
test_webhook_sk = ""


;Aplazame:
aplazame_public_key = ""

aplazame_private_key = ""


;Cetelem:
cetelem_codcentro = ""


;Iberent
iberent_token = ""




