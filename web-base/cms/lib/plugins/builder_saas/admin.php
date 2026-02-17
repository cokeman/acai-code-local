<?
global $key,$value,$action,$menu;
if ($menu == "module_marketplace"){
    require_once __DIR__."/tpl/standAloneModuleMarketPlace.tpl";
    die();
}
if (@$_REQUEST["action"] != "edit") return;

$pluginConfig = PluginsAPI::getConfig("builder_saas");
$menusAlowed = [];

if (@$pluginConfig["tablas_asignadas_boton_builder"]){
    $menusAlowed = array_merge($menusAlowed,array_map(function($record){ return trim($record); },array_filter(explode(",",$pluginConfig["tablas_asignadas_boton_builder"]))));
}

if (!in_array($menu, $menusAlowed)) return;

if (@PluginsAPI::$resultPlugins["builder_saas"]["menuFilter"]["actionHandler"]){
    $menuFilter = array_filter(explode(",",PluginsAPI::$resultPlugins["builder_saas"]["menuFilter"]["actionHandler"]));
    $menuFilter[] = $menu;
    PluginsAPI::$resultPlugins["builder_saas"]["menuFilter"]["actionHandler"] = join(",",$menuFilter);
}