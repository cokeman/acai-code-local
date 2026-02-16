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
breadcrumbByLink = 0
breadcrumbField = ""
breadcrumbParentNum = ""
controller = "cms/lib/plugins/builder_saas/controlador_tabla.php"
listPageFields = "dragSortOrder, rol, usuario, chat_conf_num"
listPageOrder = "dragSortOrder DESC"
listPageSearchFields = "title, content"
menuDesc = ""
menuDisplay = ""
menuHidden = 0
menuName = "Chatbots"
menuOrder = 19
menuType = "multi"
tableName = "chatbots"

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
order = 1731513737
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
order = 1734085792
label = "Chat configuracion"
type = "list"
isRequired = 0
isUnique = 0
description = ""
listType = "pulldown"
optionsType = "table"
optionsTablename = "chat_configuraciones"
optionsValueField = "num"
optionsLabelField = "dominio"

[rol]
order = 1736337195
label = "Rol"
type = "list"
isRequired = 0
isUnique = 0
description = ""
listType = "pulldown"
optionsType = "text"
optionsText = "option one\noption two\noption three"

[activo]
order = 1750195467
label = "Activo"
type = "checkbox"
checkedByDefault = 0
description = ""
checkedValue = 1
uncheckedValue = 0
