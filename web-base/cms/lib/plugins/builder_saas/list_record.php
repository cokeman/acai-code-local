<?
global $menu;

$pluginConfig = PluginsAPI::getConfig("builder_saas");
$menusAlowed = [];

if (@$pluginConfig["tablas_asignadas_boton_builder"]){
    $menusAlowed = array_merge($menusAlowed,array_map(function($record){ return trim($record); },array_filter(explode(",",$pluginConfig["tablas_asignadas_boton_builder"]))));
}

if (!in_array($menu, $menusAlowed)) return;

$clase = "btn-black";
if (@$var["builder"] && $var["builder"] !== "[]") $clase = "btn-warning";

if (!@$var["list_actions"]) $var["list_actions"] = [];
$var["list_actions"][] = [
        "link" => "/admin.php?menu=$menu&action=edit&num=".$var["num"]."&builder=1",
        "class" => "btn btn-xs ".$clase,
        "style" => "padding:4px;border-color:transparent;",
        "text" => "Modular"
    ];