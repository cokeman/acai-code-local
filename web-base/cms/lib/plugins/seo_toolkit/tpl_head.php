<?php
global $TABLE_PREFIX;
$urlActual = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
$urlActualQuery = empty($query) ? $urlActual : $urlActual."?".$query;

$urlActual = mysql_real_escape_string($urlActual);
$urlActualQuery = mysql_real_escape_string($urlActualQuery);

// Buscamos los noindex que pueda haber
$noindex = mysql_fetch_assoc(mysql_query("SELECT *
    FROM {$TABLE_PREFIX}seo_toolkit_no_index
    WHERE `enlace_relativo` LIKE '$urlActual' -- URLs que coincidan con la actual
        OR `enlace_relativo` LIKE '$urlActual?%' AND incluir_querypath=1 -- URLs con querypath que coincidan con la actual
        OR `enlace_relativo` LIKE '$urlActualQuery' -- URLs que tengan querypath igual"));

if ($noindex) {
    echo '<meta name="robots" content="noindex, nofollow">';
}

global $domain_for_canonicals;

$domain_for_canonicals = $_SERVER['HTTP_HOST'];

if(file_exists(__DIR__ . '/_custom_metas.php')) {
    // Este archivo no existe en el plugin base, solo se crea en la web para sus personalizaciones.
    require_once __DIR__ . '/_custom_metas.php';
}

$domain_for_canonicals = 'https://' . $domain_for_canonicals;

// Buscamos las canónicas que puedan haber
$canonica = mysql_fetch_assoc(mysql_query("SELECT *
    FROM {$TABLE_PREFIX}seo_toolkit_canonicals
    WHERE `original` LIKE '$urlActual'
    OR `original` LIKE '$urlActualQuery'"));

if ($canonica) {
    echo '<link rel="canonical" href="'.$domain_for_canonicals.$canonica['canonico'].'">';
}
else {
    global $otros_metas;
    if (strpos($otros_metas ?: '',"canonical") === false){
        echo '<link rel="canonical" href="'.$domain_for_canonicals.$urlActual.'">';
    }
}
