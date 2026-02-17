<? 
global $schema, $menu, $listFieldLabels, $TABLE_PREFIX, $tableName, $CURRENT_USER;

// 12/12/2019 -> Fix porque solo salen 25 registros en la lista y no hay paginación
if (!isset($_REQUEST['perPage'])) $_REQUEST['perPage'] = 100000;
require_once "lib/menus/default/list_functions.php";

require_once __DIR__."/../list.php";

showHeader(true); 
?>
<style>html{font-size: 10px;}</style>

<header class="navbar navbar-default navbar-fixed-top">
    <h4 class="titular hidden lg:block"><?php echo @$schema['menuName'] ?></h4>

    <ul class="nav navbar-nav-custom absolute top-0 pl-0">
        <li>
            <a href="javascript:void(0)" onclick="App.sidebar('toggle-sidebar');">
                <i class="fa fa-chevron-left p-6 mt-1"></i>
            </a>
        </li>
        <li>
            <? echo API::getWebSiteSelector();?>
        </li>
    </ul>

    <form method="get" name="preview" action="<?php echo @$schema['_listPage'] ?>" target="_blank"></form>

    <? 
    $pluginData = array("buttons" => array(),"list-header-content" => "","schema" => $schema);
    addPlugins("list_header",$pluginData);
    $schema = @$pluginData["schema"] ?: $schema;
    ?>

    <ul class="nav navbar-nav-custom pull-right">
        <?
        foreach($pluginData["buttons"] as $data):
            echo "<li>".$data."</li>";
        endforeach;
        ?>
    </ul>

</header>

<div id="page-content" class="bg-gray-300" style="padding:0px">
    <? /*if (@$CURRENT_USER["isAdmin"] && !isset($_SESSION["acaiCodeReferer"])){?>
    <div class="absolute top-0 right-0 hidden lg:flex -mt-1 items-center justify-end">
        <button type="button" class="relative bg-theme h-full text-white px-12 py-6" @click="toggleTableLayout(true)"><div class="absolute top-0 left-0 w-full h-full bg-black opacity-25 pointer-events-none z-0"></div><i class="fa fa-code relative z-10"></i>&nbsp;&nbsp;<span class="relative z-10">Editar HTML</span></button>
        <button type="button" class="relative bg-theme h-full text-white px-12 py-6" @click="toggleTableLayout(false)"><div class="absolute top-0 left-0 w-full h-full bg-black opacity-50 pointer-events-none z-0"></div><i class="fa fa-code relative z-10"></i>&nbsp;&nbsp;<span class="relative z-10">Estructura</span></button>
    </div>
    <? }*/?>
    <ul class="container mx-auto rounded-lg text-gray-600 m-0 p-0 py-10">
        <div class="bg-white shadow rounded lg:mt-8 lg:mt-0 mb-12 mx-8 overflow-hidden flex flex-wrap transitionAppear">
            <span class="hidden p-4 w-full md:w-auto lg:text-3xl bg-gray-100 shadow flex-shrink-0 lg:flex items-center"><i class="fa fa-search"></i></span>
            <input type="text" class="appearance-none p-4 bg-white md:text-3xl text-gray-800 flex-grow" ref="search" placeholder='Buscar ( Presiona "ç" para escribir )' v-model="searchString">
            <?
            if (!@$schema['_disableAdd']): 
                ?>
                <form method="POST" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" autocomplete="off"  class="flex" role="search">
                    <input type="hidden" name="menu" value="<?php echo htmlspecialchars($menu) ?>" />
                    <button type="submit" class="bg-theme text-white px-12" id="crear" name="action=add"><i class="fa fa-plus"></i>&nbsp;&nbsp;Crear</button>
                </form>
                <?
            endif;
            ?>
            <div class="relative hidden lg:block">               
                <button v-if="!vistaSEO" v-on:click="vistaSEO = !vistaSEO" type="button" class="relative bg-theme h-full text-white px-12" ><div class="absolute top-0 left-0 w-full h-full bg-black opacity-25 pointer-events-none z-0"></div><i class="fa fa-list-alt relative z-10"></i>&nbsp;&nbsp;<span class="relative z-10">Vista avanzada</span></button>
                <button v-if="vistaSEO" v-on:click="vistaSEO = !vistaSEO" type="button" class="relative bg-theme h-full text-white px-12" ><div class="absolute top-0 left-0 w-full h-full bg-black opacity-25 pointer-events-none z-0"></div><i class="fa fa-list relative z-10"></i>&nbsp;&nbsp;<span class="relative z-10">Vista simple</span></button>
            </div>
            
        </div>
        <ul v-if="vistaSEO" class="py-10 flex justify-center">
            <li v-for="vistaSEOScore in vistaSEOScores" class="flex flex-col items-center">
                <div class="mb-4 w-32 h-32 flex items-center text-4xl justify-center border-4 rounded-full bg-white shadow-xl" :class="{'border-green-400 text-green-700':vistaSEOScore.score > 80,'border-orange-400 text-orange-700':vistaSEOScore.score > 50 && vistaSEOScore.score <= 80, 'border-red-400 text-red-700':vistaSEOScore.score <= 50 }">{{ vistaSEOScore.score }}</div>
                <span class="text-md">{{ vistaSEOScore.label }}</span>
            </li>
        </ul>
        <Tree :data="filteredItems" draggable="draggable" cross-tree="cross-tree" @change="sendNewOrder" :indent="30" :space="0" :ondragstart="startDrag">
            <div slot-scope="{data, store}" class="relative">
                <list-item :data="data" :store="store" @update-data="updateData" @select_record="selectRecord" @erase_record="eraseRecord" :filtered_items="filteredItems" :tables_cache="tablesCache" :vista_seo="vistaSEO" :campo_visible="campoVisible" :campo_name="campoName" :view_squema="viewSquema" :is_admin="<?=$CURRENT_USER["isAdmin"] ? 'true' : 'false';?>" :disable_erase="<?=@$schema['_disableErase'];?>"></list-item>
            </div>
        </Tree>

        <? if (!@$schema['_disableAdd'] && @$CURRENT_USER["licencia"]): ?>
        <div class="px-8 relative" v-if="selectedRecords.length">
            <div class="max-w-sm relative">
                <i class="fa fa-chevron-down absolute top-0 right-0 p-5"></i>
                <select v-model="advancedSelection" @change="advancedOption" class="appearance-none w-full p-4 bg-white pr-16" id="">
                    <option value="">Opciones de selección</option>
                    <option value="eliminar">Eliminar seleccionados</option>
                    <option value="duplicar">Duplicar seleccionados</option>
                </select>
            </div>
        </div>
        <? endif;?>
    </ul>
    
</div>

<script>
    var getVars = <?=@$_GET ? json_encode($_GET) : json_encode([]);?>;
    var postVars = <?=@$_POST ? json_encode($_POST) : json_encode([]);?>;
    var MENU = "<?=@$menu;?>";
    var PYCHECKER = <?=@$CURRENT_USER["domain"]["plugins"]["pychecker"] ? "true" : "false";?>;
    var KWTRACKER = <?=@$CURRENT_USER["domain"]["plugins"]["keyword_tracker"] ? "true" : "false";?>;
    var LINKBUILDING = <?=@$CURRENT_USER["domain"]["plugins"]["linkbuilding"] ? "true" : "false";?>;
    var LEADS = <?=@$CURRENT_USER["domain"]["plugins"]["geomarketing"] ? "true" : "false";?>;
    var ANALYTICS = <?=@$CURRENT_USER["domain"]["plugins"]["google_analytics"] ? "true" : "false";?>;
    var TEMPLATE = "<?=$options["templatePath"];?>";
    var CONTROLADOR_SCHEMA = "<?=@$schema["controller"];?>";
    var CURRENT_USER = <?=json_encode($CURRENT_USER_filtrado);?>;
    <? 
    if (@$schema["listPageFields"]){
        $sepListPageFields  = array_map(function($e){ return trim($e);},array_filter(explode(",",$schema["listPageFields"])));
        $array = [];
        $nomostrados = ["none","upload","checkbox"];
        foreach($sepListPageFields as $key){
            if (@$schema[$key] && !in_array($schema[$key]["type"],$nomostrados)) $array[$key] = $schema[$key];
        }
        ?>var VIEW_SCHEMA = <?=json_encode($array);?>;<?
    }else{
        ?>var VIEW_SCHEMA = [];<?
    }
    ?>
</script>


<script src="<?=h($options["templatePath"]."/js/vue-draggable-nested-tree.min.js");?>"></script>
<script src="<?=h($options["templatePath"]."/js/tree-helper.js");?>"></script>
<script src="<?=h($options["templatePath"]."/js/list.js");?>"></script>
<link rel="stylesheet" href="<?=$options["templatePath"];?>/css/list.css?t=<?=time();?>">

<? /*
<!-- EDITOR DE CODIGO -->
<script src="//cdn.jsdelivr.net/npm/sortablejs@1.8.4/Sortable.min.js"></script>
<script src="<?=h($options["templatePath"]."/js/vuedraggable.umd.min.js");?>"></script>

<script src="<?=h("js/ace/ace.js");?>"></script>
<script src="<?=h("js/ace/emmet-core/emmet.js");?>"></script>
<script src="<?=h("js/ace/theme-twilight.js");?>"></script>
<script src="<?=h("js/ace/mode-html.js");?>"></script>
<script src="<?=h("js/ace/ext-emmet.js");?>"></script>
<script src="<?=h("js/ace/ext-language_tools.js");?>"></script>
<script src="<?=h($options["templatePath"]."/js/beautify-html.min.js");?>"></script>

<? include "layoutEditor.tpl";?>
<script src="<?=h($options["templatePath"]."/js/vueace.js");?>"></script>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script src="<?=h($options["templatePath"]."/js/mixins/vuecomponents.js");?>"></script>
<script src="<?=h($options["templatePath"]."/js/mixins/builderdata.js");?>"></script>
<script src="<?=h($options["templatePath"]."/js/mixins/filters.js");?>"></script>
<script src="<?=h($options["templatePath"]."/js/parseDocument.js");?>"></script>
<script src="<?=h($options["templatePath"]."/js/layoutEditor.js");?>"></script>
<!-- HASTA AQUI -->
*/ ?>
<? 
$dummy = null;
addPlugins("list_footer_builder",$dummy);
showFooter(true);

blockStandAlone();
?>