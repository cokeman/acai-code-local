<?php
require_once __DIR__ . '/../../../../sesion.php';
require_once __DIR__ . '/../../../../funciones.php';

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
$imagenes = [];

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

        $imagenes[$key] = [];

        // Buscamos todas las imagenes del record.
        $imagenes_del_registro = mysql_query_fetch_all_assoc("SELECT urlPath FROM {$TABLE_PREFIX}uploads WHERE tableName='".str_replace('cms_','',$schema['TABLE_NAME'])."' AND recordNum = '".intval($record['num'])."'");
        $imagenes[$key] = array_merge($imagenes[$key], $imagenes_del_registro);

        // Si tiene builder, accedemos a todos sus builder_custom y buscamos todas las imagenes de estos.
        if(@$record['builder']) {
            $builder_records = json_decode($record['builder'], true);
            if($builder_records) {
                $nums_del_record_en_builder = [];
                foreach($builder_records as $builder_record) {
                    if(@$builder_record['oculto'] == true) continue;
                    if($builder_record['config-vars']) {
                        foreach($builder_record['config-vars'] as $builder_fields) {
                            if(@$builder_fields['recordNum']) $nums_del_record_en_builder[] = intval($builder_fields['recordNum']);
                            if(@$builder_fields['newValues']['builder_custom']['recordNum']) $nums_del_record_en_builder[] = intval($builder_fields['newValues']['builder_custom']['recordNum']);
                        }
                        if(@$builder_record['config-vars']['records']) {
                            foreach($builder_record['config-vars']['records'] as $builder_records_record) {
                                foreach($builder_records_record as $builder_records_record_field) {
                                    if(@$builder_records_record_field['recordNum']) $nums_del_record_en_builder[] = intval($builder_records_record_field['recordNum']);
                                    if(@$builder_records_record_field['newValues']['builder_custom']['recordNum']) $nums_del_record_en_builder[] = intval($builder_records_record_field['newValues']['builder_custom']['recordNum']);
                                }
                            }
                        }
                    }
                }
                if(count($nums_del_record_en_builder)) {
                    $imagenes_del_registro = mysql_query_fetch_all_assoc("SELECT urlPath FROM {$TABLE_PREFIX}uploads WHERE tableName='builder_custom' AND recordNum in (".implode(',', $nums_del_record_en_builder).")");
                    $imagenes[$key] = array_merge($imagenes[$key], $imagenes_del_registro);   
                }
            }
        }


        if (
            $schema['TABLE_NAME'] === "{$TABLE_PREFIX}landing_multiubicacion_v2" &&
            $record['visible'] === '1' &&
            strpos($record['enlace_custom'], '/:enlace_custom') !== false
        ) {
            foreach ($ubicaciones as $ubicacion) {
                $new_enlace = str_replace('/:enlace_custom', $ubicacion['enlace_custom'], $record['enlace_custom']);
                $enlaces[$key][] = $new_enlace;
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

            $real_schema = loadSchema($schema['TABLE_NAME']);

            // Buscamos todas las imagenes del record.
            $imagenes_del_registro = mysql_query_fetch_all_assoc("SELECT urlPath FROM {$TABLE_PREFIX}uploads WHERE tableName='".str_replace('cms_','',$schema['TABLE_NAME'])."' AND recordNum = '".intval($record['num'])."'");
            $imagenes[$key] = array_merge($imagenes[$key], $imagenes_del_registro);

            // Si tiene builder, accedemos a todos sus builder_custom y buscamos todas las imagenes de estos.
            if(@$record['builder']) {
                $builder_records = json_decode($record['builder'], true);
                if($builder_records) {
                    $nums_del_record_en_builder = [];
                    foreach($builder_records as $builder_record) {
                        if(@$builder_record['oculto'] == true) continue;
                        if($builder_record['config-vars']) {
                            foreach($builder_record['config-vars'] as $builder_fields) {
                                if(@$builder_fields['recordNum']) $nums_del_record_en_builder[] = intval($builder_fields['recordNum']);
                                if(@$builder_fields['newValues']['builder_custom']['recordNum']) $nums_del_record_en_builder[] = intval($builder_fields['newValues']['builder_custom']['recordNum']);
                            }
                            if(@$builder_record['config-vars']['records']) {
                                foreach($builder_record['config-vars']['records'] as $builder_records_record) {
                                    foreach($builder_records_record as $builder_records_record_field) {
                                        if(@$builder_records_record_field['recordNum']) $nums_del_record_en_builder[] = intval($builder_records_record_field['recordNum']);
                                        if(@$builder_records_record_field['newValues']['builder_custom']['recordNum']) $nums_del_record_en_builder[] = intval($builder_records_record_field['newValues']['builder_custom']['recordNum']);
                                    }
                                }
                            }
                        }
                    }
                    if(count($nums_del_record_en_builder)) {
                        $imagenes_del_registro = mysql_query_fetch_all_assoc("SELECT urlPath FROM {$TABLE_PREFIX}uploads WHERE tableName='builder_custom' AND recordNum in (".implode(',', $nums_del_record_en_builder).")");
                        $imagenes[$key] = array_merge($imagenes[$key], $imagenes_del_registro);   
                    }
                }
            }


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

if(@$_REQUEST['indev'] == 1) {
    ?>
    <style>
        url {display: block;}
        url > * {display: block; padding-left: 10px;}
        url > loc {padding-left: 0px;}
    </style>
    <?
} else {
    header("Content-type: text/xml");
    echo '<?xml version="1.0" encoding="UTF-8"?'.'>';
}


echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
foreach ($enlaces as $key => $links) {
    foreach($links as $index => $link) {
        echo '<url>';
        echo "<loc>https://{$_SERVER['HTTP_HOST']}{$link}</loc>";
        foreach ($imagenes[$key] as $imagen) {
            echo "<image:image><image:loc>https://{$_SERVER['HTTP_HOST']}{$imagen['urlPath']}</image:loc></image:image>";
        }
        echo '</url>';
    }
}
echo '</urlset>';
die();
