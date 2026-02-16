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
breadcrumbField = ""
controller = "apartados.php"
listPageFields = "name,precio_gastos,gratis_a_partir_de"
listPageOrder = "globalOrder"
listPageSearchFields = "name, content"
menuDesc = ""
menuDisplay = ""
menuHidden = 0
menuName = "Provincias y Poblaciones"
menuOrder = 17
menuType = "category"
tableName = "provincias_y_poblaciones"

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

[globalOrder]
order = 6
label = "_globalOrder"
type = "none"
customColumnType = "int(10) unsigned NOT NULL"
isSystemField = 1

[siblingOrder]
order = 7
label = "_siblingOrder"
type = "none"
customColumnType = "int(10) unsigned NOT NULL"
isSystemField = 1

[lineage]
order = 8
label = "_lineage"
type = "none"
customColumnType = "varchar(255) NOT NULL"
isSystemField = 1

[depth]
order = 9
label = "_depth"
type = "none"
customColumnType = "int(10) unsigned NOT NULL"
isSystemField = 1

[parentNum]
order = 10
label = "Parent Category"
type = "parentCategory"
customColumnType = "int(10) unsigned NOT NULL"
isSystemField = 1

[breadcrumb]
order = 11
label = "Breadcrumb"
type = "none"
customColumnType = "varchar(255) NOT NULL"
isSystemField = 1

[layout]
order = 12
label = "Layout"
type = "none"
customColumnType = "int(10) unsigned NULL"
isSystemField = 1

[name]
order = 13
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

[precio_gastos]
order = 1585509663
label = "Precio Gastos"
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

[gratis_a_partir_de]
order = 1585509678
label = "Gratis a partir de"
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
