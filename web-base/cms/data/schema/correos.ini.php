;<?php die('This is not a program file.'); exit; ?>

_detailPage = ""
_disableAdd = 0
_disableErase = 0
_filenameFields = "identificador"
_hideRecordsFromDisabledAccounts = 0
_listPage = ""
_maxRecords = ""
_maxRecordsPerUser = ""
apartadodemenu = 0
breadcrumbField = ""
controller = "apartados.php"
listPageFields = "dragSortOrder, identificador"
listPageOrder = "dragSortOrder DESC"
listPageSearchFields = "identificador"
menuDesc = ""
menuDisplay = ""
menuHidden = 0
menuName = "Correos"
menuOrder = 8
menuType = "multi"
tableName = "correos"

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

[identificador]
order = 7
label = "Identificador"
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

[asunto]
order = 8
label = "Asunto"
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

[cuerpo]
order = 1582884404
label = "Cuerpo"
type = "wysiwyg"
defaultContent = ""
allowUploads = 1
wysywigAvanzado = 1
isRequired = 0
isUnique = 0
minLength = ""
maxLength = ""
fieldHeight = ""
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
