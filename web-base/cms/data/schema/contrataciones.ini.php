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
listPageFields = "dragSortOrder, usuario, chat_conf_num,plan,subproducto"
listPageOrder = "dragSortOrder DESC"
listPageSearchFields = "title, content"
menuDesc = ""
menuDisplay = ""
menuHidden = 0
menuName = "Contrataciones"
menuOrder = 33
menuType = "multi"
tableName = "contrataciones"

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

[usuario]
order = 1750096284
label = "Usuario"
type = "list"
isRequired = 0
isUnique = 0
description = ""
listType = "pulldown"
optionsType = "table"
optionsTablename = "usuarios"
optionsValueField = "num"
optionsLabelField = "correo"

[chat_conf_num]
order = 1750096353
label = "Chat Conf Num"
type = "list"
isRequired = 0
isUnique = 0
description = ""
listType = "pulldown"
optionsType = "table"
optionsTablename = "chat_configuraciones"
optionsValueField = "num"
optionsLabelField = "dominio"

[plan]
order = 1750096362
label = "Plan"
type = "list"
isRequired = 0
isUnique = 0
description = ""
listType = "pulldown"
optionsType = "table"
optionsTablename = "planes"
optionsValueField = "num"
optionsLabelField = "title"

[subproducto]
order = 1750096375
label = "Subproducto"
type = "list"
isRequired = 0
isUnique = 0
description = ""
listType = "pulldown"
optionsType = "table"
optionsTablename = "subproductos"
optionsValueField = "num"
optionsLabelField = "title"

[suscripcion]
order = 1750198280
label = "Suscripcion"
type = "list"
isRequired = 0
isUnique = 0
description = ""
listType = "pulldown"
optionsType = "text"
optionsText = "'activa'|Activa\n'cancelada'|Cancelada"

[payment_num]
order = 1752006689
label = "Payment Num"
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

[recurrencia]
order = 1752825891
label = "Recurrencia"
type = "list"
isRequired = 0
isUnique = 0
description = ""
listType = "pulldown"
optionsType = "text"
optionsText = "anual|Anual\nmensual|Mensual"

[moneda]
order = 1753209745
label = "Moneda"
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
