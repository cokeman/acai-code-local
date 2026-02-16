;<?php die('This is not a program file.'); exit; ?>

_detailPage = ""
_disableAdd = 0
_disableErase = 0
_filenameFields = ""
_hideRecordsFromDisabledAccounts = 0
_listPage = ""
_maxRecords = ""
_maxRecordsPerUser = ""
apartadodemenu = 0
breadcrumbByLink = 0
breadcrumbField = ""
controller = "apartados.php"
listPageFields = ""
listPageOrder = ""
listPageSearchFields = ""
menuDesc = "Tablas necesarias: correos, solicitudes, productos, categorias_productos, valoraciones_productos, codigos_descuento, posibles_pedidos, lineas_de_compra, usuarios, ofertas, gastos_de_envio.<br>Correos necesarios: REGISTRO_COMPRA, RECORDAR_PASS, CONFIRMAR_COMPRA, CONFIRMACION_REGISTRO"
menuDisplay = ""
menuHidden = 0
menuName = "Configuración Acai"
menuOrder = 3
menuType = "single"
tableName = "configuracion_tienda"

[num]
order = 1
type = "none"
label = "Record Number"
isSystemField = 1

[createdDate]
order = 2
type = "none"
label = "Created"
isSystemField = 1

[createdByUserNum]
order = 3
type = "none"
label = "Created By"
isSystemField = 1

[updatedDate]
order = 4
type = "none"
label = "Last Updated"
isSystemField = 1

[updatedByUserNum]
order = 5
type = "none"
label = "Last Updated By"
isSystemField = 1

[tienda_activa]
order = 6
label = "Tienda activa"
type = "checkbox"
checkedByDefault = 0
description = ""
checkedValue = 1
uncheckedValue = 0

[codigos_descuento]
order = 7
label = "Códigos Descuento"
type = "checkbox"
checkedByDefault = 0
description = "En la cesta aparecerá la posibilidad de añadir codigos descuento. Es necesario que exista la tabla codigos descuento (&nbsp;codigos_descuento&nbsp;)"
checkedValue = 1
uncheckedValue = 0

[valoraciones]
order = 8
label = "Valoraciones"
type = "checkbox"
checkedByDefault = 0
description = "Valoraciones de productos. El usuario registrado podrá valorar la compra ( usuarios,productos,valoraciones_productos )"
checkedValue = 1
uncheckedValue = 0

[__separator001__]
order = 9
label = ""
type = "separator"
separatorType = "header bar"
separatorHeader = "Stripe"
separatorHTML = "<tr>\n <td colspan='2'>\n </td>\n</tr>"

[pago_por_stripe]
order = 10
label = "Pago por stripe"
type = "checkbox"
adminOnly = 1
checkedByDefault = 0
description = ""
checkedValue = 1
uncheckedValue = 0

[categorias_nav]
order = 11
label = "Categorías Nav"
type = "list"
isRequired = 0
isUnique = 0
listType = "pulldownMulti"
optionsType = "text"
optionsText = "option one\noption two\noption three"

[clave_publica_stripe]
order = 12
label = "Clave Pública Stripe"
type = "textfield"
defaultValue = ""
description = ""
fieldWidth = ""
tipoTags = 0
isPasswordField = 0
isRequired = 0
isUnique = 0
minLength = ""
maxLength = ""
charsetRule = ""
charset = ""
tipoIcono = 0

[clave_privada_stripe]
order = 13
label = "Clave Privada Stripe"
type = "textfield"
defaultValue = ""
description = ""
fieldWidth = ""
tipoTags = 0
isPasswordField = 0
isRequired = 0
isUnique = 0
minLength = ""
maxLength = ""
charsetRule = ""
charset = ""
tipoIcono = 0

[__separator002__]
order = 14
label = ""
type = "separator"
separatorType = "header bar"
separatorHeader = "Paypal"
separatorHTML = "<tr>\n <td colspan='2'>\n </td>\n</tr>"

[pago_por_paypal]
order = 15
label = "Pago por Paypal"
type = "checkbox"
adminOnly = 1
checkedByDefault = 0
description = ""
checkedValue = 1
uncheckedValue = 0

[cuenta_paypal]
order = 16
label = "Cuenta Paypal"
type = "textfield"
defaultValue = ""
description = ""
fieldWidth = ""
tipoTags = 0
isPasswordField = 0
isRequired = 0
isUnique = 0
minLength = ""
maxLength = ""
charsetRule = ""
charset = ""
tipoIcono = 0

[entorno_tienda_paypal]
order = 17
label = "Tienda Paypal"
type = "list"
isRequired = 0
isUnique = 0
listType = "pulldown"
optionsType = "text"
optionsText = "https://www.sandbox.paypal.com/cgi-bin/webscr|Sandbox\nhttps://www.paypal.com/cgi-bin/webscr|Real"

[__separator003__]
order = 18
label = ""
type = "separator"
separatorType = "header bar"
separatorHeader = "TPV"
separatorHTML = "<tr>\n <td colspan='2'>\n </td>\n</tr>"

[pago_por_tpv]
order = 19
label = "Pago por TPV"
type = "checkbox"
adminOnly = 1
checkedByDefault = 0
description = ""
checkedValue = 1
uncheckedValue = 0

[merchant_code]
order = 20
label = "Merchant Code"
type = "textfield"
defaultValue = ""
description = ""
fieldWidth = ""
tipoTags = 0
isPasswordField = 0
isRequired = 0
isUnique = 0
minLength = ""
maxLength = ""
charsetRule = ""
charset = ""
tipoIcono = 0

[merchant_currency]
order = 21
label = "Merchant Currency"
type = "textfield"
defaultValue = ""
description = ""
fieldWidth = ""
tipoTags = 0
isPasswordField = 0
isRequired = 0
isUnique = 0
minLength = ""
maxLength = ""
charsetRule = ""
charset = ""
tipoIcono = 0

[terminal]
order = 22
label = "Terminal"
type = "textfield"
defaultValue = ""
description = ""
fieldWidth = ""
tipoTags = 0
isPasswordField = 0
isRequired = 0
isUnique = 0
minLength = ""
maxLength = ""
charsetRule = ""
charset = ""
tipoIcono = 0

[entorno_tpv]
order = 23
label = "Entorno TPV"
type = "list"
isRequired = 0
isUnique = 0
listType = "pulldown"
optionsType = "text"
optionsText = "https://sis-t.redsys.es:25443/sis/realizarPago|Sandbox\nhttps://sis.redsys.es/sis/realizarPago|Real"

[clave_tpv]
order = 24
label = "Clave TPV"
type = "textfield"
defaultValue = ""
description = ""
fieldWidth = ""
tipoTags = 0
isPasswordField = 0
isRequired = 0
isUnique = 0
minLength = ""
maxLength = ""
charsetRule = ""
charset = ""
tipoIcono = 0

[__separator004__]
order = 25
label = ""
type = "separator"
separatorType = "header bar"
separatorHeader = "Transferencia"
separatorHTML = "<tr>\n <td colspan='2'>\n </td>\n</tr>"

[pago_por_transferencia]
order = 26
label = "Pago por transferencia"
type = "checkbox"
checkedByDefault = 0
description = ""
checkedValue = 1
uncheckedValue = 0

[__separator005__]
order = 27
label = ""
type = "separator"
separatorType = "header bar"
separatorHeader = "Estilo Web"
separatorHTML = "<tr>\n <td colspan='2'>\n </td>\n</tr>"

[favicon]
description = ""
order = 28
label = "Favicon"
type = "upload"
isRequired = 0
plUpload = 1
allowedExtensions = "gif,jpg,png,wmv,mov,swf,pdf,ico"
checkMaxUploadSize = 1
maxUploadSizeKB = 5120
checkMaxUploads = 1
maxUploads = 25
resizeOversizedImages = 1
maxImageHeight = 1024
maxImageWidth = 1024
createThumbnails = 1
maxThumbnailHeight = 150
maxThumbnailWidth = 150
createThumbnails2 = 0
maxThumbnailHeight2 = 150
maxThumbnailWidth2 = 150
createThumbnails3 = 0
maxThumbnailHeight3 = 150
maxThumbnailWidth3 = 150
createThumbnails4 = 0
maxThumbnailHeight4 = 150
maxThumbnailWidth4 = 150
useCustomUploadDir = 0
customUploadDir = ""
customUploadUrl = ""
infoField1 = "Medidas"
infoField2 = "Nombre del archivo"
infoField3 = ""
infoField4 = ""
infoField5 = ""

[logo]
order = 29
label = "Logo"
type = "upload"
isRequired = 0
plUpload = 1
allowedExtensions = "gif,jpg,png,wmv,mov,swf,pdf"
checkMaxUploadSize = 1
maxUploadSizeKB = 5120
checkMaxUploads = 1
maxUploads = 25
resizeOversizedImages = 1
maxImageHeight = 1024
maxImageWidth = 1024
createThumbnails = 1
maxThumbnailHeight = 150
maxThumbnailWidth = 150
createThumbnails2 = 0
maxThumbnailHeight2 = 150
maxThumbnailWidth2 = 150
createThumbnails3 = 0
maxThumbnailHeight3 = 150
maxThumbnailWidth3 = 150
createThumbnails4 = 0
maxThumbnailHeight4 = 150
maxThumbnailWidth4 = 150
useCustomUploadDir = 0
customUploadDir = ""
customUploadUrl = ""
infoField1 = ""
infoField2 = ""
infoField3 = ""
infoField4 = ""
infoField5 = ""

[logo_footer]
description = ""
order = 30
label = "Logo Footer"
type = "upload"
isRequired = 0
plUpload = 1
allowedExtensions = "gif,jpg,png,wmv,mov,swf,pdf"
checkMaxUploadSize = 1
maxUploadSizeKB = 5120
checkMaxUploads = 1
maxUploads = 25
resizeOversizedImages = 1
maxImageHeight = 1024
maxImageWidth = 1024
createThumbnails = 1
maxThumbnailHeight = 150
maxThumbnailWidth = 150
createThumbnails2 = 0
maxThumbnailHeight2 = 150
maxThumbnailWidth2 = 150
createThumbnails3 = 0
maxThumbnailHeight3 = 150
maxThumbnailWidth3 = 150
createThumbnails4 = 0
maxThumbnailHeight4 = 150
maxThumbnailWidth4 = 150
useCustomUploadDir = 0
customUploadDir = ""
customUploadUrl = ""
infoField1 = ""
infoField2 = ""
infoField3 = ""
infoField4 = ""
infoField5 = ""

[fondo_mantenimiento]
description = ""
order = 31
label = "Fondo Mantenimiento"
type = "upload"
isRequired = 0
plUpload = 1
allowedExtensions = "gif,jpg,png,wmv,mov,swf,pdf"
checkMaxUploadSize = 1
maxUploadSizeKB = 5120
checkMaxUploads = 1
maxUploads = 25
resizeOversizedImages = 1
maxImageHeight = 1024
maxImageWidth = 1024
createThumbnails = 1
maxThumbnailHeight = 150
maxThumbnailWidth = 150
createThumbnails2 = 0
maxThumbnailHeight2 = 150
maxThumbnailWidth2 = 150
createThumbnails3 = 0
maxThumbnailHeight3 = 150
maxThumbnailWidth3 = 150
createThumbnails4 = 0
maxThumbnailHeight4 = 150
maxThumbnailWidth4 = 150
useCustomUploadDir = 0
customUploadDir = ""
customUploadUrl = ""
infoField1 = ""
infoField2 = ""
infoField3 = ""
infoField4 = ""
infoField5 = ""

[color_principal]
order = 32
label = "Color Principal"
type = "textfield"
defaultValue = ""
description = ""
fieldWidth = ""
tipoTags = 0
isPasswordField = 0
isRequired = 0
isUnique = 0
minLength = ""
maxLength = ""
charsetRule = ""
charset = ""
tipoAtributo = 0

[color_principal_claro]
order = 33
label = "Color Principal Claro"
type = "textfield"
defaultValue = ""
description = ""
fieldWidth = ""
tipoTags = 0
isPasswordField = 0
isRequired = 0
isUnique = 0
minLength = ""
maxLength = ""
charsetRule = ""
charset = ""
tipoAtributo = 0

[color_principal_oscuro]
order = 34
label = "Color Principal Oscuro"
type = "textfield"
defaultValue = ""
description = ""
fieldWidth = ""
tipoTags = 0
isPasswordField = 0
isRequired = 0
isUnique = 0
minLength = ""
maxLength = ""
charsetRule = ""
charset = ""
tipoAtributo = 0

[proporcion_bloque_productos]
order = 35
label = "Proporcion bloque productos"
type = "list"
isRequired = 0
isUnique = 0
listType = "pulldown"
optionsType = "text"
optionsText = "p-1/15|Largo\np-1/10|Cuadrado\np-1/6|Apaisado"

[productos_con_cta]
order = 36
label = "Productos con CTA"
type = "checkbox"
checkedByDefault = 0
description = ""
checkedValue = 1
uncheckedValue = 0
