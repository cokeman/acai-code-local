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
listPageFields = "dragSortOrder, agent, chat, text"
listPageOrder = "dragSortOrder DESC"
listPageSearchFields = "title, content"
menuDesc = ""
menuDisplay = ""
menuHidden = 0
menuName = "Notas internas"
menuOrder = 25
menuType = "multi"
tableName = "notas_internas"

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

[text]
order = 1742838336
label = "Text"
type = "textbox"
defaultContent = ""
description = ""
isRequired = 0
isUnique = 0
minLength = ""
maxLength = ""
fieldHeight = ""
autoFormat = 1

[agent]
order = 1742838352
label = "Agente"
type = "list"
isRequired = 0
isUnique = 0
description = ""
listType = "pulldown"
optionsType = "table"
optionsTablename = "usuarios"
optionsValueField = "num"
optionsLabelField = "correo"

[chat]
order = 1742838372
label = "Chat"
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

[deleted_at]
order = 1754906019
label = "deleted_at"
type = "date"
isUnique = 0
showTime = 1
description = ""
showSeconds = 1
use24HourFormat = 1
yearRangeStart = 1900
yearRangeEnd = 2100

[deleted_by]
order = 1754906030
label = "deleted_by"
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
