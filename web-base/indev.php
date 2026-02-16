<?php

exit;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'sesion.php';
require_once 'funciones.php';

CocoEmail::$debug = true;
CocoEmail::send_email('dleeram@cocosolution.com', 'Test', 'Test');

exit;

$uploads = mysql_query_fetch_all_assoc("SELECT * from cms_uploads");
$uploads = array_filter($uploads,function($rec){ return @$rec["recordNum"] && @$rec["tableName"] ? count(mysql_query_fetch_all_assoc("SELECT num from cms_".$rec["tableName"]." WHERE num = ".$rec["recordNum"])) : false; });

$uploads = array_map(function($rec){ return basename($rec["filePath"]); }, $uploads);

$uploads = array_unique($uploads);

$path = __DIR__ . "/cms/uploads";

$zipname = tempnam(sys_get_temp_dir(), 'zip') . '.zip';

$zip = new ZipArchive;
if ($zip->open($zipname, ZipArchive::CREATE) === TRUE) {
    foreach (scandir($path) as $file) {
        if ($file == ".." || $file == ".") continue;
        if (in_array($file, $uploads)) {
            $zip->addFile($path . "/" . $file, $file);
        }
    }
    $zip->close();
} else {
    die("No se pudo crear el archivo ZIP");
}

// Configurar las cabeceras para forzar la descarga del archivo ZIP
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="uploads.zip"');
header('Content-Length: ' . filesize($zipname));

// Leer el archivo ZIP y enviarlo al navegador
readfile($zipname);

// Eliminar el archivo ZIP temporal después de enviarlo
unlink($zipname);

exit;

// $uploads = CocoDB::get('uploads', '', '', '', ['ignoreSchema' => true]);


// $uploads_resumen = [];
// foreach ($uploads as $upload) {
//     if(!isset($uploads_resumen[$upload['tableName']])) $uploads_resumen[$upload['tableName']] = [];
//     $uploads_resumen[$upload['tableName']][] = [
//         'num' => $upload['num'], 
//         'fieldName' => $upload['fieldName'], 
//         'tableName' => $upload['tableName'], 
//         'recordNum' => $upload['recordNum']
//     ];
// }

// $uploads_sin_registro = [];

// foreach ($uploads_resumen as $tabla => $table_uploads) {
//     foreach ($table_uploads as $upload) {
//         $existe = count(mysql_query_fetch_all_assoc("SELECT * FROM cms_{$tabla} WHERE num = " . intval($upload['recordNum'])));
//         if(!$existe) {
//             $uploads_sin_registro[] = $upload['num'];
//         }
//     }
// }

// var_dump(json_encode($uploads_sin_registro));

CocoEmail::$debug = true;
CocoEmail::send_email('ehernandez@cocosolution.com', 'Test', 'Test');

// var_dump('test');

exit;
