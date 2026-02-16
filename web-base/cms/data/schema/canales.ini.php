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
listPageFields = "dragSortOrder, name"
listPageOrder = "dragSortOrder DESC"
listPageSearchFields = "title, content"
menuDesc = ""
menuDisplay = ""
menuHidden = 0
menuName = "Canales"
menuOrder = 21
menuType = "multi"
tableName = "canales"

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

[domain]
order = 6
label = "Domain"
type = "textfield"
isSystemField = 1
adminOnly = 1
defaultValue = ""
description = ""
fieldWidth = "null"
tipoTags = 0
isPasswordField = 0
isRequired = 1
isUnique = 1
minLength = "null"
maxLength = "null"
charsetRule = ""
charset = ""
tipoAtributo = 0

[name]
order = 7
label = "Nombre del canal"
type = "list"
isRequired = 1
isUnique = 0
description = ""
listType = "radios"
optionsType = "text"
optionsText = "whatsapp|Whatsapp\nmessenger|Facebook messenger\ninstagram|Instagram direct\ntelegram|Telegram"

[channel_config]
order = 8
label = "Configuración del canal"
type = "codigo"
defaultContent = ""
description = ""
isRequired = 1
isUnique = 0
minLength = ""
maxLength = ""
fieldHeight = 300

[chat_conf_num]
order = 1734086004
label = "Chat configuracion"
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

[activo]
order = 1750201466
label = "Activo"
type = "checkbox"
checkedByDefault = 0
description = ""
checkedValue = 1
uncheckedValue = 0
