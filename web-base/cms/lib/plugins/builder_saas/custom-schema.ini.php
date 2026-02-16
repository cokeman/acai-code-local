;<?php die('This is not a program file.'); exit; ?>

version = .65
name = "Builder"
description = "Creador de Landings por módulos"
adminOnly = 0
enabled = 1
notCopy = 1
onlyCopy = ".htaccess,controlador.php,controlador_mail.php,builder_functions.php,slug.php,controlador_tabla.php,funciones.php,replace_code.php,ws_respond.php,mcp_respond.php,simple_html_dom.php,pre_render.php"
CMSReady = 0
saasReady = 1

[checkPoints]
actionHandler = "editor.php"
admin = "admin.php"
list_header = ""
list_filter = ""
list_record = "list_record.php"
list_footer = ""
edit_header = ""
edit_field = ""
edit_footer = ""
home = ""
menu = "menu.php"
head = ""
footer = ""
save_pre = ""
save = ""
slug = "slug.php"
handler_ws = "ws_respond.php"
save_post = ""
tpl_head_body = "replace_code.php"
tpl_foot_body = "replace_code.php"
tpl_head = "replace_code.php"
tpl_foot = "replace_code.php"
pre_render = "pre_render.php"

[menuFilter]
actionHandler = "apartados,builder_custom,otros_contenidos"

[actionFilter]
actionHandler = "edit,add,list,erase,uploadList,uploadModify"

[config]
acceso_a_slide = ""
acceso_a_plugins_generales = 1
acceso_a_plugins_especiales = ""
ejecucion_php = ""
tablas_asignadas_editor = ""
tablas_asignadas_boton_builder = "blog"
tablas_asignadas_modo_app = ""
modulePath = "/../../../../template/estandar/modulos/"
modulePathAux = "template/estandar/modulos/"
webp = 0
amp = 0
minimalCSSTail = 0
