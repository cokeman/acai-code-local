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
listPageFields = "dragSortOrder, visible, categoria_principal, title"
listPageOrder = "dragSortOrder DESC"
listPageSearchFields = "title, content"
menuDesc = ""
menuDisplay = ""
menuHidden = 0
menuName = "Noticias"
menuOrder = 36
menuType = "multi"
tableName = "blog"

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

[admin_only_section]
order = 6
label = "Admin Only"
type = "checkbox"
checkedByDefault = 0
description = ""
checkedValue = 1
uncheckedValue = 0

[autosaved]
order = 7
label = "Auto Save"
type = "checkbox"
checkedByDefault = 0
description = ""
checkedValue = 1
uncheckedValue = 0

[dragSortOrder]
order = 8
label = "Order"
type = "none"
default = "{TIMESTAMP}"

[visible]
order = 9
label = "Visible"
type = "checkbox"
checkedByDefault = 0
description = ""
checkedValue = 1
uncheckedValue = 0

[enlace]
order = 10
label = "Enlace"
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

[fecha]
order = 11
label = "Fecha"
type = "date"
isUnique = 0
showTime = 1
description = ""
showSeconds = 1
use24HourFormat = 1
yearRangeStart = 1900
yearRangeEnd = 2100

[foto_principal]
description = ""
order = 12
label = "Foto Principal"
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

[title]
order = 13
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

[tags]
order = 14
label = "Tags"
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

[categoria_principal]
order = 15
label = "Categoría Principal"
type = "list"
isRequired = 0
isUnique = 0
description = ""
listType = "pulldown"
optionsType = "table"
optionsTablename = "categorias_noticias"
optionsValueField = "num"
optionsLabelField = "name"

[subtitulo]
order = 16
label = "Subtitulo"
type = "textbox"
defaultContent = ""
description = ""
isRequired = 0
isUnique = 0
minLength = ""
maxLength = ""
fieldHeight = ""
autoFormat = 1

[content]
order = 17
label = "Contenido"
type = "wysiwyg"
default = "Lorem ipsum dolor sit amet, consectetur adipisicing elit. Nihil quia necessitatibus qui sequi? Alias nostrum tenetur reprehenderit, provident sint id error voluptas, esse nisi repellendus ea cumque sequi ducimus excepturi."
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

[galeria_de_fotos]
description = ""
order = 18
label = "Galería de fotos"
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
description = ""
order = 19
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
infoField1 = ""
infoField2 = ""
infoField3 = ""
infoField4 = ""
infoField5 = ""

[__separator002__]
order = 20
label = ""
type = "separator"
separatorType = "header bar"
separatorHeader = "PALABRAS CLAVE Y METATAGS"
separatorHTML = "<tr>\\1n <td colspan='2'>\\1n </td>\\1n</tr>"

[titulo_de_pagina]
order = 21
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
order = 22
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

[visitas]
order = 1753801552
label = "Visitas"
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

[builder]
order = 1753801571
label = "Builder Schema"
type = "textbox"
defaultContent = ""
description = ""
isRequired = 0
isUnique = 0
minLength = ""
maxLength = ""
fieldHeight = ""
autoFormat = 1

[precontrolador]
order = 1753801604
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

[controlador]
order = 1753801621
label = "Controlador"
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

[metatag_palabras]
order = 1753801646
label = "Metatag Palabras"
type = "none"
