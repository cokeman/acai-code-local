<?php

global $TABLE_PREFIX, $SETTINGS, $configuracionRecord;

if (!@$configuracionRecord['pagina_publicada'] && !@$_SESSION['pruebas']) {
    http_response_code(404);
    die();
}

$tablasIgnoradas = array_map(function($tabla) {
    return $tabla['table_name'];
}, mysql_query_fetch_all_assoc("SELECT DISTINCT `table_name` FROM {$TABLE_PREFIX}seo_toolkit_sitemap"));

$tablasEliminadas = array_merge(["{$TABLE_PREFIX}alias_urls"], $tablasIgnoradas);

$enlacesIgnorados = array_filter(array_map(function($link) {
    return @$link['link'];
}, mysql_query_fetch_all_assoc("SELECT DISTINCT `link` FROM {$TABLE_PREFIX}seo_toolkit_sitemap")));

$enlaces = [];
$idiomas = [];
$fechas = [];

$schemaResult = mysql_query("
    SELECT TABLE_NAME
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE COLUMN_NAME = 'enlace'
        AND TABLE_SCHEMA = '{$SETTINGS['mysql']['database']}'
");
while ($schema = mysql_fetch_assoc($schemaResult)) {
    if (in_array($schema["TABLE_NAME"], $tablasEliminadas)) return;

    if ($schema['TABLE_NAME'] === "{$TABLE_PREFIX}landing_multiubicacion_v2") {
        $ubicaciones = mysql_query_fetch_all_assoc("SELECT enlace_custom FROM {$TABLE_PREFIX}ubicaciones");
    }

    $recordResult = mysql_query("SELECT * FROM {$schema['TABLE_NAME']}");
    while ($record = mysql_fetch_assoc($recordResult)) {
        $enlace = $record['enlace'];
        $key = "{$schema['TABLE_NAME']}-{$record['num']}";

        if (
            $schema['TABLE_NAME'] === "{$TABLE_PREFIX}landing_multiubicacion_v2" &&
            $record['visible'] === '1' &&
            strpos($record['enlace_custom'], '/:enlace_custom') !== false
        ) {
            foreach ($ubicaciones as $ubicacion) {
                $enlaces[$key][] = str_replace('/:enlace_custom', $ubicacion['enlace_custom'], $record['enlace_custom']);
                $idiomas[$key][] = 'es';
                $fechas[$key] = $record['updatedDate'];
            }
        } else {
            if (
                (isset($record['visible_en_el_menu']) && $record['visible_en_el_menu'] === '0') ||
                (isset($record['activo']) && $record['activo'] === '0') ||
                (isset($record['visible']) && $record['visible'] === '0') ||
                (isset($record['oculto']) && $record['oculto'] === '1') ||
                trim($enlace) === '' ||
                strpos($enlace, '#') !== false ||
                strpos($enlace, 'http:') !== false ||
                strpos($enlace, 'https:') !== false ||
                in_array($enlace, $enlacesIgnorados) === true
            ) continue;

            $enlaces[$key][] = $enlace;
            $idiomas[$key][] = 'es';
            $fechas[$key] = $record['updatedDate'];

            $tabla = str_replace($TABLE_PREFIX, '', $schema['TABLE_NAME']);
            $traduccionResult = mysql_query("
                SELECT prefix, fieldValue
                FROM {$TABLE_PREFIX}traducciones
                WHERE tableName = '{$tabla}' AND recordNum = {$record['num']} AND fieldName = 'enlace'
            ");
            while ($traduccion = mysql_fetch_assoc($traduccionResult)) {
                $enlaces[$key][] = base64_decode($traduccion["fieldValue"]);
                $idiomas[$key][] = $traduccion["prefix"];
            }
        }
    }
}

header("Content-type: text/xml");
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
foreach ($enlaces as $key => $links) {
    foreach($links as $index => $link) {
        echo '<url>';
        echo "<loc>https://{$_SERVER['HTTP_HOST']}{$link}</loc>";
        $date = date(DATE_ATOM, strtotime($fechas[$key]));
        echo "<lastmod>{$date}</lastmod>";
        foreach ($links as $tmpIndex => $tmpLink) {
            if ($tmpIndex === $index || isset($idiomas[$key][$tmpIndex]) === false) continue 1;
            echo "<xhtml:link rel=\"alternate\" hreflang=\"{$idiomas[$key][$tmpIndex]}\" href=\"https://{$_SERVER['HTTP_HOST']}{$tmpLink}\" />";
        }
        echo '<changefreq>monthly</changefreq>';
        echo '</url>';
    }
}
echo '</urlset>';
die();
