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
breadcrumbField = ""
controller = "apartados.php"
listPageFields = "dragSortOrder, enlace_relativo, incluir_querypath"
listPageOrder = "dragSortOrder DESC"
listPageSearchFields = "enlace_relativo"
menuDesc = ""
menuDisplay = ""
menuHidden = 0
menuName = "No index 🤖"
menuOrder = 11
menuType = "multi"
tableName = "seo_toolkit_no_index"

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

[enlace_relativo]
order = 1558371779
label = "Enlace relativo"
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
tipoAtributo = 0

[incluir_querypath]
order = 1558371789
label = "Incluir querypath"
type = "checkbox"
checkedByDefault = 1
description = "Si se marca, también se pondrá noindex en las URLs con QueryPath"
checkedValue = 1
uncheckedValue = 0
