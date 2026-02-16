;<?php die('This is not a program file.'); exit; ?>

_detailPage = ""
_disableAdd = 0
_disableErase = 0
_disableborrar = ""
_filenameFields = "breadcrumb"
_hideRecordsFromDisabledAccounts = 0
_listPage = ""
_maxRecords = ""
_maxRecordsPerUser = ""
apartadodemenu = 1
breadcrumbField = "parentNum"
controller = "cms/lib/plugins/builder_saas/controlador_tabla.php"
controller_alt = "apartados.php"
listPageFields = "visible_en_el_menu,layout,name"
listPageOrder = "globalOrder"
listPageSearchFields = "name, content"
menuDesc = "Listado de apartados del menú principal de la web"
menuDisplay = ""
menuHidden = 0
menuName = "Apartados"
menuOrder = 4
menuType = "category"
tableName = "apartados"

[admin_only_section]
order = 1
label = "Admin Only"
type = "checkbox"
checkedByDefault = 0
description = ""
checkedValue = 1
uncheckedValue = 0
adminOnly = 1

[autosaved]
order = 2
label = "Auto Save"
type = "checkbox"
checkedByDefault = 0
description = ""
checkedValue = 1
uncheckedValue = 0

[lineage]
order = 3
label = "_lineage"
type = "none"
customColumnType = "varchar(255) NOT NULL"
isSystemField = 1

[globalOrder]
order = 4
label = "_globalOrder"
type = "none"
customColumnType = "int(10) unsigned NOT NULL"
isSystemField = 1

[siblingOrder]
order = 5
label = "_siblingOrder"
type = "none"
customColumnType = "int(10) unsigned NOT NULL"
isSystemField = 1

[depth]
order = 6
label = "_depth"
type = "none"
customColumnType = "int(10) unsigned NOT NULL"
isSystemField = 1

[num]
order = 7
type = "none"
label = "Record Number"
isSystemField = 1

[createdDate]
order = 8
type = "none"
label = "Created"
isSystemField = 1

[createdByUserNum]
order = 9
type = "none"
label = "Created By"
isSystemField = 1

[updatedDate]
order = 10
type = "none"
label = "Last Updated"
isSystemField = 1

[updatedByUserNum]
order = 11
type = "none"
label = "Last Updated By"
isSystemField = 1

[breadcrumb]
order = 12
label = "Breadcrumb"
type = "none"
customColumnType = "varchar(255) NOT NULL"
isSystemField = 1

[layout]
order = 13
label = "Layout"
type = "none"
isSystemField = 1

[conectar_a_seccion]
order = 14
label = "Conectar a Sección"
type = "textfield"
adminOnly = 1
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

[controlador]
order = 15
label = "Controlador"
type = "textfield"
adminOnly = 
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

[enlace]
order = 16
label = "Enlace externo"
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

[visible_en_el_menu]
order = 17
label = "Visible en el Menu"
type = "checkbox"
checkedByDefault = 0
description = ""
checkedValue = 1
uncheckedValue = 0

[visible_en_el_footer]
order = 18
label = "Visible en el footer"
type = "checkbox"
checkedByDefault = 0
description = ""
checkedValue = 1
uncheckedValue = 0

[parentNum]
customColumnType = "int(10) unsigned NOT NULL"
order = 19
label = "Categoria Padre"
type = "parentCategory"
isSystemField = 1
adminOnly = 0

[name]
order = 20
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
maxLength = 0
charsetRule = ""
charset = ""

[titulo_alternativo]
order = 21
label = "Título Alternativo"
type = "textfield"
defaultValue = ""
description = "Título que aparece dentro del apartado"
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

[content]
order = 22
label = "Contenido"
type = "wysiwyg"
defaultContent = ""
allowUploads = 1
wysywigAvanzado = 1
isRequired = 0
isUnique = 0
minLength = ""
maxLength = ""
fieldHeight = 300
allowedExtensions = "gif,jpg,png,wmv,mov,swf,pdf"
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

[galeria_de_fotos]
order = 23
label = "Galeria de fotos"
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

[archivos_adjuntos]
order = 24
label = "Archivos Adjuntos"
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
infoField1 = "Titulo"
infoField2 = ""
infoField3 = ""
infoField4 = ""
infoField5 = ""

[__separator002__]
order = 25
label = ""
type = "separator"
separatorType = "header bar"
separatorHeader = "PALABRAS CLAVE Y METATAGS"
separatorHTML = "<tr>\\1n <td colspan='2'>\\1n </td>\\1n</tr>"

[titulo_de_pagina]
order = 26
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

[metatag_palabras]
order = 26

[metatag_descripcion]
order = 27
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

[builder]
order = 28
label = "Builder Schema"
type = "textbox"
defaultContent = ""
description = ""
isRequired = 0
isUnique = 0
minLength = ""
maxLength = ""
fieldHeight = 500
autoFormat = 0

[precontrolador]
order = 29
label = "Controlador auxiliar ( para reestablecer )"
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
