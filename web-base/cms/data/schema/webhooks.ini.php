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
listPageFields = "dragSortOrder, title,url"
listPageOrder = "dragSortOrder DESC"
listPageSearchFields = "title, content"
menuDesc = ""
menuDisplay = ""
menuHidden = 0
menuName = "Webhooks"
menuOrder = 27
menuType = "multi"
tableName = "webhooks"

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
label = "Titulo"
type = "textfield"
default = "Lorem ipsum dolor sit atmet {NUM}"
defaultValue = ""
description = ""
fieldWidth = ""
isPasswordField = 0
isRequired = 1
isUnique = 0
minLength = ""
maxLength = ""
charsetRule = ""
charset = ""

[url]
order = 8
label = "URL"
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

[chat_conf_num]
order = 9
label = "Chat Conf Num"
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

[channel]
order = 1749494063
label = "Channel"
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
