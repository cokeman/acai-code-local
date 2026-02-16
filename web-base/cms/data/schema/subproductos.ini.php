;<?php die('This is not a program file.'); exit; ?>

_detailPage = ""
_disableAdd = 0
_disableErase = 0
_filenameFields = "title"
_hideRecordsFromDisabledAccounts = 0
_listPage = ""
_maxRecords = ""
_maxRecordsPerUser = ""
apartadodemenu = 0
breadcrumbByLink = ""
breadcrumbField = ""
breadcrumbParentNum = ""
controller = "cms/lib/plugins/builder_saas/controlador_tabla.php"
listPageFields = "dragSortOrder, title, precio, producto_id,producto_limit, producto_type, visible"
listPageOrder = "dragSortOrder DESC"
listPageSearchFields = "title, content"
menuDesc = ""
menuDisplay = ""
menuHidden = 0
menuName = "Subproductos"
menuOrder = 24
menuType = "multi"
tableName = "subproductos"

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
default = "{DATE}"

[createdByUserNum]
order = 3
type = "none"
label = "Created By"
isSystemField = 1
default = 1

[updatedDate]
order = 4
type = "none"
label = "Last Updated"
isSystemField = 1
default = "{DATE}"

[updatedByUserNum]
order = 5
type = "none"
label = "Last Updated By"
isSystemField = 1
default = 1

[dragSortOrder]
order = 6
label = "Order"
type = "none"
default = "{TIMESTAMP}"

[title]
order = 7
label = "Nombre"
type = "textfield"
defaultValue = ""
description = ""
fieldWidth = ""
tipoTags = 0
isPasswordField = 0
isRequired = 1
isUnique = 0
minLength = ""
maxLength = ""
charsetRule = ""
charset = ""
tipoAtributo = 0

[precio]
order = 8
label = "Precio"
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

[producto_id]
order = 10
label = "Producto ID"
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

[producto_limit]
order = 11
label = "Producto Limit"
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

[producto_type]
order = 12
label = "Producto Tipo"
type = "list"
isRequired = 0
isUnique = 0
description = ""
listType = "pulldown"
optionsType = "text"
optionsText = "mensual|Mensual\nunico|Pago unico"

[visible]
order = 13
label = "Visible"
type = "checkbox"
checkedByDefault = 0
description = ""
checkedValue = 1
uncheckedValue = 0

[descripcion]
order = 14
label = "Descripción"
type = "textbox"
defaultContent = ""
description = ""
isRequired = 0
isUnique = 0
minLength = ""
maxLength = ""
fieldHeight = ""
autoFormat = 1

[stripe_price_id]
order = 15
label = "Stripe Price ID"
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

[__separator001__]
order = 1752785824
label = ""
type = "separator"
separatorType = "header bar"
separatorHeader = "Anual"
separatorHTML = "<tr>\n <td colspan='2'>\n </td>\n</tr>"

[precio_ano]
order = 1752785837
label = "Precio"
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

[stripe_price_id_ano]
order = 1752785852
label = "Stripe Price ID"
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
