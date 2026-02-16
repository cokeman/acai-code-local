;<?php die('This is not a program file.'); exit; ?>

_detailPage = ""
_disableAdd = 0
_disableErase = 0
_filenameFields = "breadcrumb"
_hideRecordsFromDisabledAccounts = 0
_listPage = ""
_maxRecords = ""
_maxRecordsPerUser = ""
apartadodemenu = 0
breadcrumbByLink = ""
breadcrumbField = ""
breadcrumbParentNum = ""
controller = "cms/lib/plugins/builder_saas/controlador_tabla.php"
listPageFields = "visible, name"
listPageOrder = "globalOrder"
listPageSearchFields = "name, content"
menuDesc = ""
menuDisplay = ""
menuHidden = 0
menuName = "Categorías Noticias"
menuOrder = 35
menuType = "category"
tableName = "categorias_noticias"

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

[visible]
order = 6
label = "Visible"
type = "checkbox"
checkedByDefault = 0
description = ""
checkedValue = 1
uncheckedValue = 0

[enlace]
order = 7
label = "Enlace externo"
type = "textfield"
adminOnly = 0
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

[globalOrder]
order = 8
label = "_globalOrder"
type = "none"
customColumnType = "int(10) unsigned NOT NULL"
isSystemField = 1

[siblingOrder]
order = 9
label = "_siblingOrder"
type = "none"
customColumnType = "int(10) unsigned NOT NULL"
isSystemField = 1

[lineage]
order = 10
label = "_lineage"
type = "none"
customColumnType = "varchar(255) NOT NULL"
isSystemField = 1

[depth]
order = 11
label = "_depth"
type = "none"
customColumnType = "int(10) unsigned NOT NULL"
isSystemField = 1

[parentNum]
order = 12
label = "Parent Category"
type = "parentCategory"
customColumnType = "int(10) unsigned NOT NULL"
isSystemField = 1

[breadcrumb]
order = 13
label = "Breadcrumb"
type = "none"
customColumnType = "varchar(255) NOT NULL"
isSystemField = 1

[layout]
order = 14
label = "Layout"
type = "none"
customColumnType = "int(10) unsigned NULL"
isSystemField = 1

[name]
order = 15
label = "Nombre"
type = "textfield"
defaultValue = ""
description = ""
fieldWidth = ""
isPasswordField = 0
isRequired = 1
isUnique = 0
minLength = ""
maxLength = 0
charsetRule = ""
charset = ""

[content]
order = 16
label = "Contenido"
type = "wysiwyg"
defaultContent = ""
allowUploads = 1
isRequired = 0
isUnique = 0
minLength = ""
maxLength = ""
fieldHeight = 300
allowedExtensions = "gif,jpg,png,wmv,mov,swf"
checkMaxUploadSize = 1
maxUploadSizeKB = 5120
checkMaxUploads = 1
maxUploads = 25
resizeOversizedImages = 1
maxImageHeight = 800
maxImageWidth = 600
createThumbnails = 1
maxThumbnailHeight = 150
maxThumbnailWidth = 150
useCustomUploadDir = 0
customUploadDir = ""
customUploadUrl = ""

[__separator002__]
order = 17
label = ""
type = "separator"
separatorType = "header bar"
separatorHeader = "PALABRAS CLAVE Y METATAGS"
separatorHTML = "<tr>\\1n <td colspan='2'>\\1n </td>\\1n</tr>"

[titulo_de_pagina]
order = 18
label = "Titulo de pagina"
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

[metatag_descripcion]
order = 19
label = "Descripcion de pagina"
type = "textbox"
defaultContent = ""
description = ""
isRequired = 0
isUnique = 0
minLength = ""
maxLength = ""
fieldHeight = ""
autoFormat = 0
