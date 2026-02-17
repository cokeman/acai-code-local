<?
global $schema,$SETTINGS,$CURRENT_USER,$TABLE_PREFIX,$menu;

$pluginConfig = PluginsAPI::getConfig("builder_saas");  
$menusAlowed = ["apartados","builder_custom","mail_marketing"];
$isAppVersion = false;
if (@$_GET["builder"]) $menusAlowed[] = $menu;
if (@$pluginConfig["tablas_asignadas_editor"]){
    $menusAlowed = array_merge($menusAlowed,array_map(function($record){ return trim($record); },array_filter(explode(",",$pluginConfig["tablas_asignadas_editor"]))));
}
if (@$pluginConfig["tablas_asignadas_modo_app"]){
    $isAppVersion = in_array($menu,array_map(function($record){ return trim($record); },array_filter(explode(",",$pluginConfig["tablas_asignadas_modo_app"]))));
}

if (@$action=="uploadList" && in_array($menu,$menusAlowed)){
    include("uploadList.php");
    die();
}

if (@$action=="uploadModify" && in_array($menu,$menusAlowed)){
    include("uploadModify.php");
    die();
}
if (($menu=="mail_marketing" || $menu == "proyectos" || $menu == "k12_eventos_v2") && $action=="list") return;
$acaiToken = is_acai_code();
if (@$_REQUEST['action'] == 'acai_builder' && $acaiToken) {
    $action = 'acai_builder';
}

if (!@$schema["enlace"]) return;

require_once "funciones.php";

// CustomColors REQUEST
if (@$_REQUEST["getColorsFromModule"] && @$_REQUEST["num"] && @$_REQUEST["section_id"]){
    die(json_encode(getExtraDataFromModule(@$_REQUEST["getColorsFromModule"],intval(@$_REQUEST["num"]),@$_REQUEST["section_id"])));
}
if (@$_REQUEST["setColorsFromModule"] && @$_REQUEST["num"] && @$_REQUEST["section_id"]){
    $data = file_get_contents("php://input");
    if (@$data) $colors = json_decode($data,true);
    die(json_encode(setExtraDataFromModule(@$_REQUEST["setColorsFromModule"],intval(@$_REQUEST["num"]),@$_REQUEST["section_id"],@$colors["colors"])));
}
// FIN CustomColors REQUEST

// CustomMargins REQUEST
if (@$_REQUEST["getMarginsFromModule"] && @$_REQUEST["num"] && @$_REQUEST["section_id"]){
    die(json_encode(getExtraDataFromModule(@$_REQUEST["getMarginsFromModule"],intval(@$_REQUEST["num"]),@$_REQUEST["section_id"],'custom-margins')));
}
if (@$_REQUEST["setMarginsFromModule"] && @$_REQUEST["num"] && @$_REQUEST["section_id"]){
    $data = file_get_contents("php://input");
    if (@$data) $margins = json_decode($data,true);
    die(json_encode(setExtraDataFromModule(@$_REQUEST["setMarginsFromModule"],intval(@$_REQUEST["num"]),@$_REQUEST["section_id"],@$margins["margins"],'custom-margins')));
}
// FIN CustomMargins REQUEST

if ($action == 'erase')  { 
    // REGISTRO DE ACTIVIDAD
    if ((@$CURRENT_USER["domain"]["plugins"]["registro_actividad"][0]["campo"] == "enabled") && (@$CURRENT_USER["domain"]["plugins"]["registro_actividad"][0]["valor"] == "1")){
        
        require_once __DIR__."./../registro_actividad/registro_act.php";
        //eraseRecordActivity($menu,@$_REQUEST["num"]);
    }
    eraseRecord($menu, $_REQUEST['num']); 
    header("Location:admin.php?menu=".$menu);
    die();
    //include("tpl/list.tpl"); die();
}

checkAndRepairSchemaData();

$auxPlugin = null;

if (@$action=="list"){
    include("tpl/list.tpl");
    die();
}

if (@$_REQUEST["oldEditor"]) return;

$myConfig = loadMyDataConfig();

//************************************************
// FIN ESTO HAY QUE REVISAR PORQUE ESTA REPETIDO
//************************************************

require_once "requests.php";

if (!in_array($menu,$menusAlowed)) return;

showHeader();

addPlugins("edit_header_builder",$auxPlugin);

$apartado = @$_REQUEST["num"] ? mysql_query_fetch_all_assoc("SELECT * FROM ".$TABLE_PREFIX.$menu." WHERE num=".intval(@$_REQUEST["num"]))[0] : [];
?>
<link rel="stylesheet" href="<?=h($options["templatePath"]."/css/glide.core.min.css");?>">
<link rel="stylesheet" href="<?=h($options["templatePath"]."/css/component.css");?>">

<link rel="stylesheet" href="<?=h($options["templatePath"]."/css/style.css");?>">
<script type="text/javascript" src="<?=h($options["templatePath"]."/js/glide.min.js");?>"></script>
<script src='<?=h("/js/httpVueLoader.js");?>' ></script>
<script src='<?=h("/js/vue-color-picker-board.min.js");?>' ></script>
<script type="text/javascript" src="<?=h("lib/thickbox.js");?>"></script>

<!-- DRAGGABLE -->
<script src="//cdn.jsdelivr.net/npm/sortablejs@1.8.4/Sortable.min.js"></script>
<script src="<?=h($options["templatePath"]."/js/vuedraggable.umd.min.js");?>"></script>

<script src="<?=h("js/vue2Dropzone.js");?>"></script>
<link rel="stylesheet" href="<?=h("css/vue2Dropzone.min.css");?>" type="text/css" />

<!--<script src="<?=h($options["templatePath"]."/js/vueace.js");?>"></script>-->

<div id="page-content" class="splitWrapper padding-0 ">
    <? include "tpl/navbar.tpl"; ?>
    <? include "tpl/modalLink.tpl"; ?>
    <? include "tpl/customColors.tpl"; ?>
    <? include "tpl/customMargins.tpl"; ?>
    <div class="split left flex-shrink-0">
        <div class="wrapper">
            <ul class="toolBar">
                <li><a href="javascript:void(0);" onclick="toggleNewModuleModal()"><i class="fa fa-plus"></i>&nbsp;&nbsp;Añadir</a></li>
               
                <li><a href="javascript:void(0);" onclick="toggleCustomColorsModal()"><i class="fa fa-paint-brush"></i>&nbsp;&nbsp;Colores</a></li>

                <li><a href="javascript:void(0);" onclick="toggleCustomMarginsModal()"><i class="fa fa-paint-brush"></i>&nbsp;&nbsp;Márgenes</a></li>
                
                <li data-remove="1"><a href="javascript:void(0);" onclick="removeModules()"><i class="fa fa-minus"></i>&nbsp;&nbsp;Eliminar</a></li>

            </ul>
            <div class="clearfix"></div>
            <ul class="list-modules-conf">
                <li class="bloque <? if (@$CURRENT_USER["isAdmin"]){?>corto<?}?>" data-toggle-tab="2" data-no-module onclick="toggleEditTabs(2,false,this)">
                    <i class="fa fa-remove fa-hidden"></i>
                    <img src="<?=$options["templatePath"];?>/images/icono-settings.jpg" width="80" height="80">
                    <h4>Configuración General</h4>
                    <? if (@$CURRENT_USER["isAdmin"]){?>
                        <p>Nombre, Descripción, SEO, etc...</p>
                    <? }else{?>
                        <p>Configuración general de la página tal como metatags, nombre, descripción y otros campos de personalización</p>
                    <? }?>
                </li>
                <? /*if (@$CURRENT_USER["isAdmin"]){?>
                <li class="bloque corto" data-no-module onclick="appLayout.editarLayout()">
                    <i class="fa fa-remove fa-hidden"></i>
                    <img src="<?=$options["templatePath"];?>/images/icono-layout.jpg" width="80" height="80">
                    <h4>Estructura base</h4><p>Librerías, Cabecera, Pie, etc...</p>
                </li>
                <?}*/?>
                <? // AQUI VAMOS A HACER UN BOTON PARA LA GESTION DE METAS ?>
                
            </ul>
            <div class="separator">
                <div class="truncate w-64 text-left">
                    <span id="nombrePagina">Landing Page</span>
                    <button type="button" class="pl-4" onclick="externalViews(5)" data-toggle="tooltip" data-placement="bottom" title="" data-original-title="Ver Web"><i class="gi gi-eye_open"></i></button>
                </div>
                <!--<div class="pull-right">
                    <a href="javascript:void(0)" class="btn-link inline-block" onclick="linkToWeb(this);"><i class="fa fa-share"></i> Ir</a>
                    <a href="javascript:void(0)" class="btn-fullPreview inline-block" onclick="toggleFullPreview(this);"><i class="fa fa-eye"></i> Ver</a>
                </div>-->
                <div class="clearfix"></div>
            </div>
            <ul class="list-modules drag-sort-enable"></ul>
        </div>
    </div>
    <div class="split right xl:flex-shrink-0 <?=$isAppVersion ? "appVersion" : "";?>">
       <? include "tpl/newWrapperModuleVue.tpl"; ?>
       
        <div class="saveButtons">
            <a class="save-records cancel btn btn-primary" href="?menu=<?=$menu;?>"><i class="fa fa-remove"></i>&nbsp;Cancelar</a>
            <? if (@$_REQUEST["num"]){?><a class="save-records btn btn-primary <?=@$action!="edit" ? "warning" : "";?>" onclick="saveRecord()"><i class="fa fa-save"></i>&nbsp;Guardar</a><?}?>
            <a class="save-records exit btn btn-primary <?=@$action!="edit" ? "warning" : "";?>" onclick="saveRecord(1)"><i class="fa fa-save"></i>&nbsp;Guardar/Salir</a>
            
        </div>
        <div class="newModule">
            <ul class="toolBar">
                <li><a href="javascript:void(0);" onclick="toggleNewModuleModal()"><i class="fa fa-chevron-up"></i>&nbsp;&nbsp;Volver</a></li>
            </ul>
            
            <? include "tpl/newWrapperModule.tpl";?>
            
        </div>
        
        <? // if (!@$_REQUEST["indev"]){ include "tpl/customColors.tpl"; } ?>
        <script src="<?=h("/lib/plugins/builder_saas/js/customColors.js");?>"></script>
        <script src="<?=h("/lib/plugins/builder_saas/js/customMargins.js");?>"></script>
        <link rel="stylesheet" href="<?=h("/lib/plugins/builder_saas/css/customColors.css");?>">
        <link rel="stylesheet" href="<?=h("/lib/plugins/builder_saas/css/customMargins.css");?>">
    
        <div class="editModule">
            <ul class="toolBar">
                <li><a href="javascript:void(0);" onclick="toggleEditModuleModal()"><i class="fa fa-chevron-up"></i>&nbsp;&nbsp;Cancelar</a></li>
                <li><a href="javascript:void(0);" class="btn btn-primary" onclick="document.getElementById('iframeEditModal').contentWindow.guardar();"><i class="fa fa-floppy-o"></i>&nbsp;&nbsp;Guardar</a></li>
            </ul>
            <div class="editWrapperContainer"></div>
        </div>
        <div class="wrapper hidden">
            <ul class="toolBar">
                <li class="toggleMobile visible-xs"><a href="javascript:void(0);" onclick="toggleMenuMobile();"><i class="fa fa-bars"></i></a></li>
                <!--<li class="toggleWrapper"><a href="javascript:void(0);" data-toggle-tab="0" class="toggle active bg-primary" onclick="toggleEditTabs(0,true,this)"><i class="fa fa-cog"></i>&nbsp;&nbsp;Configuración Módulo <span class='teclas hidden-xs'>( CTRL+1 )</span></a></li>-->
                <!--<li class="toggleWrapper"><a href="javascript:void(0);" data-toggle-tab="1" class="toggle" onclick="toggleEditTabs(1,true,this)"><i class="fa fa-eye"></i>&nbsp;&nbsp;Previsualizar Módulo <span class='teclas hidden-xs'>( CTRL+2 )</span></a></li>-->
                <!--<li class="toggleWrapper"><a href="javascript:void(0);" data-toggle-tab="3" class="toggle" onclick="toggleEditTabs(3,true,this)"><i class="fa fa-pencil"></i>&nbsp;&nbsp;CSS Módulo <span class='teclas hidden-xs'>( CTRL+3 )</span></a></li>-->
            </ul>
            <div class="wrapperContainer" data-tab-id="0"></div>
            <div class="wrapperContainerPreview hidden" data-tab-id="1"></div>
            <div class="wrapperContainerPreview css hidden" data-tab-id="3"></div>
            <div class="wrapperContainerPreview hidden" data-tab-id="4"></div>
            <div class="wrapperContainerPreview full editor hidden overflow-scroll" data-tab-id="2"><p>No es posible cargar los contenidos de edición</p></div>
            <div class="wrapperContainerPreview full layout hidden" data-tab-id="5">
                <label for="codeHeader">Código para la cabecera</label>
                <textarea name="" class="form-control editorTextarea" placeholder="Código de cabecera" id="codeHeader" cols="30" rows="10"></textarea>
                <div class="separa-20"></div>
                <label for="codeFooter">Código para el pié de página</label>
                <textarea name="" class="form-control editorTextarea" placeholder="Código de pie" id="codeFooter" cols="30" rows="10"></textarea>
                <div class="separa-20"></div>
                <label for="codeLibraries">Librerías y metas generales</label>
                <textarea name="" class="form-control editorTextarea" placeholder="Código de librerías" id="codeLibraries" cols="30" rows="10"></textarea>
                <div class="separa-40"></div>
            </div>
        </div>
    </div>
    <? /*if ($_SERVER["REMOTE_ADDR"] == "80.39.189.255")*/ include "tpl/splitIframe.tpl";?>
    <? //include "tpl/layoutEditor.tpl";?>
</div>

<!--<script src="https://cdn.jsdelivr.net/npm/@nano-sql/core@2.3.6/dist/nano-sql.min.js" integrity="sha256-Ua+lzcp0TwbaEtqZifYWjTe+74eh3qmdzIrsURkk36Y=" crossorigin="anonymous"></script>-->
<script src="<?=$options["templatePath"];?>/js/loadData.js?t=<?=time();?>"></script>
<script>
    var links = <?=json_encode(getProyectLinks());?>;
    var MENU = "<?=@$_REQUEST["menu"];?>";
    var NUM = "<?=@$_REQUEST["num"];?>";
    var IDIOMAS = <?=json_encode($SETTINGS["idiomas"]);?>;
    var TEMPLATE = "<?=$options["templatePath"];?>";
    var myConfig = <?=$myConfig;?>;
    var REFRESH_THUMBS = <?=@$_REQUEST["refreshThumbs"] ? "true" : "false";?>;
    var CURRENT_USER = <?=json_encode($CURRENT_USER_filtrado);?>;
    var ACAI_REFERER = <?=isset($_SESSION["acaiCodeReferer"]) ? "true" : "false";?>;
    var USER_PLUGINS = <?=json_encode(parseaConfigPlugins(@$CURRENT_USER["domain"]["plugins"]));?>;
    var APARTADO_DATA = <?=json_encode($apartado);?>;
    var APARTADO_LINK = "<?=isset($apartado["enlace"]) ?: "";?>";
    var ALL_SCHEMAS = <?=json_encode(SchemaAPI::getInstance()->getSchemaTables(null,"menu"));?>;
    var SCHEMA = <?=json_encode($schema);?>;
    var CAMPO_TITLE = "<?=dameCampoTitle();?>";
    var CONTROLADOR_SCHEMA = "<?=@$schema["controller"];?>";
    var PRESAVETEMPID = "<?=!@$_REQUEST['num'] ? uniqid('x') : "";?>";
    var TABLE_PREFIX = "<?=@$TABLE_PREFIX;?>";
    var IS_MAIL_MARKETING = <?=$menu=="mail_marketing" ? "true" : "false";?>;
    var CDN_MODULES_WEBSITE = <?=CDN_MODULES_WEBSITE;?>;
</script>
<?
showFooter();
?>
<script src="<?=h("/js/sweetalert-2.1.2.min.js");?>"></script>
<script src="<?=h("/js/coco-ckeditor5_2021/build/ckeditor.js");?>"></script>
<link rel="stylesheet" href="<?=h("/js/coco-ckeditor5_2021/build/ckeditor.css");?>">
<script src="<?=h("/lib/menus/default/edit_functions_ckeditorv2-coco.js");?>"></script>
<script src="<?=h("/js/vendor/choices.min.js");?>"></script>
<link href="<?=h("/css/vendor/choices.min.css");?>" rel="stylesheet">
<!--
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="<?=h($options["templatePath"]."/js/quill.min.js");?>"></script>
<script src="<?=h($options["templatePath"]."/js/QuillJS.class.js");?>"></script>
-->


<script src="<?=h($options["templatePath"]."/js/rest.js");?>"></script>
<script src="<?=h($options["templatePath"]."/js/modulos.js");?>"></script>


<script src="<?=h($options["templatePath"]."/js/campos.js");?>"></script>
<script src="<?=h($options["templatePath"]."/js/cScriptLoader.js");?>"></script>
<script src="<?=h($options["templatePath"]."/js/mis-scripts.js");?>"></script>
<script src="<?=h($options["templatePath"]."/js/beautify-html.min.js");?>"></script>
<script src="<?=h($options["templatePath"]."/js/mixins/vuecomponents.js");?>"></script>
<script src="<?=h($options["templatePath"]."/js/mixins/builderdata.js");?>"></script>
<script src="<?=h($options["templatePath"]."/js/mixins/filters.js");?>"></script>
<script src="<?=h($options["templatePath"]."/js/parseDocument.js");?>"></script>
<!--<script src="<?=h($options["templatePath"]."/js/layoutEditor.js");?>"></script>-->
<!--<script src="<?=h($options["templatePath"]."/js/crearModuloModal.js");?>"></script>-->
<script src="<?=h($options["templatePath"]."/js/idleTasks.js");?>"></script>
<?

die();
