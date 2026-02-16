;<?php die('This is not a program file.'); exit; ?>

_detailPage = ""
_disableAdd = 0
_disableErase = 0
_filenameFields = "breadcrumb"
_hideRecordsFromDisabledAccounts = 0
_listPage = ""
_maxRecords = ""
_maxRecordsPerUser = ""
listPageFields = "name"
listPageOrder = "globalOrder"
listPageSearchFields = "name, content"
menuName = "Cuentas"
menuOrder = 14
menuType = "separador"
tableName = "cuentas"

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
