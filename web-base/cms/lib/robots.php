<?php
require_once __DIR__."/../sesion.php";
require_once __DIR__."/../funciones.php";

if (!function_exists("protocol")) {
    function protocol() {
            return _isHTTPS() ? "https" : "http";
    }
    function _isHTTPS() {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
    }
}
header('Content-Type: text/plain');
$filePath = __DIR__."/../robots.txt";
if (file_exists($filePath)) {
    echo file_get_contents($filePath);
    die();
}
?>
# <?=strtoupper($configuracionRecord["tienda_nombre_empresa"]);?>

User-agent: *
<? if (strpos($_SERVER["HTTP_HOST"], "plandeweb.com")) {?>
Disallow: /
<? }?>
<? if (@$configuracionRecord['pagina_publicada']) {?>
# SITEMAP
Sitemap: <?=protocol();?>://<?=$_SERVER["HTTP_HOST"];?>/sitemap.xml
<? }?>