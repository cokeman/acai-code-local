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
listPageFields = "fullname, username, email"
listPageOrder = "fullname, username"
listPageSearchFields = "fullname, username, email"
menuDesc = ""
menuDisplay = ""
menuHidden = 0
menuName = "User Accounts"
menuOrder = 2
menuType = "multi"
tableHidden = 0
tableName = "accounts"

[num]
order = 1
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

[username]
order = 8
label = "Username"
type = "textfield"
isSystemField = 0
defaultValue = ""
isPasswordField = 0
isRequired = 1
isUnique = 1
minLength = ""
maxLength = ""
charsetRule = ""
charset = ""

[password]
order = 9
label = "Password"
type = "textfield"
defaultValue = ""
description = "<a href='javascript:void(0);' class='codifica button'>Codifcar clave</a> "
fieldWidth = ""
isPasswordField = 1
isRequired = 1
isUnique = 0
minLength = ""
maxLength = ""
charsetRule = ""
charset = ""

[email]
order = 10
label = "Email"
type = "textfield"
isSystemField = 0
defaultValue = ""
isPasswordField = 0
isRequired = 1
isUnique = 1
minLength = ""
maxLength = ""
charsetRule = ""
charset = ""

[fullname]
order = 11
label = "Full Name"
type = "textfield"
isSystemField = 0
defaultValue = ""
isPasswordField = 0
isRequired = 1
isUnique = 0
minLength = ""
maxLength = ""
charsetRule = ""
charset = ""

[__separator002__]
order = 12
type = "separator"
isSystemField = 0
separatorType = "blank line"

[expiresDate]
order = 13
label = "Account Expires"
type = "date"
isSystemField = 0
isUnique = 0
showTime = 0
showSeconds = 0
use24HourFormat = 0
yearRangeStart = 2008
yearRangeEnd = 2016

[neverExpires]
order = 14
label = "Never Expires"
type = "checkbox"
isSystemField = 0
checkedByDefault = 1
description = "Ignore Account Expiry (account never expires)"

[__separator004__]
order = 15
type = "separator"
isSystemField = 0
separatorType = "blank line"

[isAdmin]
order = 17
label = "Admin Access"
type = "checkbox"
isSystemField = 0
checkedByDefault = 0
description = "Allow access to Admin menus (including this section)"
adminOnly = 1

[disabled]
order = 18
label = "Disable Access"
type = "checkbox"
isSystemField = 0
checkedByDefault = 0
description = "Disable account (user won't be able to login)"

[__separator005__]
order = 19
label = ""
type = "separator"
separatorType = "blank line"
separatorHeader = ""
separatorHTML = "<tr>\\1n <td colspan='2'>\\1n </td>\\1n</tr>"

[accessList]
order = 20
label = "Section Access"
type = "accessList"
