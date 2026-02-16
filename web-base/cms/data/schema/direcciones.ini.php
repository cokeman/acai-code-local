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
breadcrumbField = ""
controller = "apartados.php"
listPageFields = "dragSortOrder, usuario, direccion, telefono"
listPageOrder = "dragSortOrder DESC"
listPageSearchFields = "title, content"
menuDesc = ""
menuDisplay = ""
menuHidden = 0
menuName = "Direcciones"
menuOrder = 15
menuType = "multi"
tableName = "direcciones"

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
order = 7
label = "Usuario"
type = "list"
isRequired = 0
isUnique = 0
listType = "pulldown"
optionsType = "table"
optionsTablename = "usuarios"
optionsValueField = "num"
optionsLabelField = "correo"

[nombre]
order = 10
label = "Nombre"
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

[telefono]
order = 11
label = "Telefono"
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

[__separator001__]
order = 12
label = ""
type = "separator"
separatorType = "header bar"
separatorHeader = "DATOS DE FACTURACION"
separatorHTML = "<tr>\n <td colspan='2'>\n </td>\n</tr>"

[razonSocial]
order = 13
label = "Razón Social"
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

[apellidos]
order = 14
label = "Apellidos"
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

[telefono_fijo]
order = 15
label = "Teléfono Fijo"
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

[tipo]
order = 16
label = "Tipo de cliente"
type = "list"
isRequired = 0
isUnique = 0
listType = "pulldown"
optionsType = "text"
optionsText = "0|Particular\n1|Empresa"

[dni]
order = 17
label = "DNI"
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

[siiIDType]
order = 18
label = "Tipo identificativo"
type = "list"
isRequired = 0
isUnique = 0
listType = "pulldown"
optionsType = "text"
optionsText = "03|PASAPORTE\n04|DOCUMENTO OFICIAL DE IDENTIFICACIÓN EXPEDIDO POR EL PAIS O TERRITORIO DE RESIDENCI\n05|CERTIFICADO DE RESIDENCIA\n06|OTRO DOCUMENTO PROBATORIO\n07|NO CENSADO"

[siiCodigoPais]
order = 19
label = "Pais de expedición"
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

[tipo_de_documento]
order = 20
label = "Tipo de documento"
type = "list"
isRequired = 0
isUnique = 0
listType = "pulldown"
optionsType = "text"
optionsText = "1|DNI/CIF\n2|NIE\n3|OTROS"

[direccion]
order = 21
label = "Direccion"
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

[numero]
order = 22
label = "Número"
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

[pais]
order = 23
label = "Pais"
type = "list"
isRequired = 0
isUnique = 0
listType = "pulldown"
optionsType = "table"
optionsTablename = "paises"
optionsValueField = "num"
optionsLabelField = "title"

[provincia]
order = 24
label = "Provincia"
type = "list"
isRequired = 0
isUnique = 0
listType = "pulldown"
optionsType = "query"
optionsQuery = "SELECT num, breadcrumb FROM <?php echo $TABLE_PREFIX ?>provincias_y_poblaciones where parentNum=0"

[poblacion]
order = 25
label = "Población"
type = "list"
isRequired = 0
isUnique = 0
listType = "pulldown"
optionsType = "query"
optionsQuery = "SELECT num, breadcrumb FROM <?php echo $TABLE_PREFIX ?>provincias_y_poblaciones where parentNum!=0"
filterField = "provincia"

[codigo_postal]
order = 26
label = "Código Postal"
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
