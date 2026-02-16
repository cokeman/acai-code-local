;<?php die('This is not a program file.'); exit; ?>

version = 1.0
name = "CMS API"
description = "Acceso a la API de Acai CMS con información de lectura y escritura desde aplicaciones de terceros.<br> <strong>IMPORTANTE</strong> Entra <a href='/admin.php?menu=cms_api_plugin'>aquí</a> tras actualizar el plugin. <strong>Información para el administrador: </strong> Es necesario generar el hash del id del usuario en sha1 con permisos y añadirlo a la configuración del plugin para darle permisos"
adminOnly = 0
enabled = 1

[checkPoints]
save_pre = ""
db_type_config = ""
upload_file = ""
menu = "menu.php"
home = ""
head = ""
footer = ""
upload_list_record = ""
menuDisplay = ""
db_type = ""
edit_field = ""
list_header = ""
actionHandler = ""
edit_footer = ""
edit_header = ""
list_record = ""
list_footer = ""
list_buttons = ""
list_filter = ""
slug = ""
pre_codigos_en_linea = ""
login = ""
post_codigos_en_linea = ""
admin = "admin_actionHandler.php"
admin_menus = "admin_menus.php"
admin_actionHandler = ""
codigos_en_linea = ""

[menuFilter]
admin_actionHandler = "cms_api_plugin"

[config]
hash = "0d92114801574773e3f584c3a58c8dc9bfd49ba1"
masterKey = ""
