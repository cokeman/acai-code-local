<?php
// Forma vieja de actualizar plugin.
// SE COMENTA TEMPORALMENTE HASTA QUE JORDAN LO ESTUDIE
return;
/*global $TABLE_PREFIX;
$api = SchemaAPI::getInstance();
$schemas = array_slice(scandir(__DIR__.'/schemas'), 2);
$installed = false;
foreach ($schemas as $schemaFile) {
    $schemaTable = pathinfo($schemaFile, PATHINFO_FILENAME);
    $table_exists = mysql_num_rows(mysql_query("SHOW TABLES LIKE '".$TABLE_PREFIX.$schemaTable."'")) > 0;
    if ($table_exists) continue;
    try {
        $schema = $api->loadSchema($schemaTable);
        continue;
    }
    catch(Exception $e) {
        $installed = true;
        $schema = json_decode(file_get_contents(__DIR__.'/schemas/'.$schemaFile), true);
        $api->saveSchema($schemaTable, $schema);
    }
}
if ($installed) createMissingSchemaTablesAndFields();*/


/*
global $TABLE_PREFIX;
$api = SchemaAPI::getInstance();
$schemas = array_slice(scandir(__DIR__.'/schemas'), 2);
$shouldCreateAndUpdate = false;

foreach ($schemas as $schemaFile) {
    $schema_json = file_get_contents($schemaFile);
    echo "<pre style='display: none;'>"; var_dump($schema_json); echo "</pre>";

    // $schemaTable = pathinfo($schemaFile, PATHINFO_FILENAME);
    // $table_exists = mysql_num_rows(mysql_query("SHOW TABLES LIKE '".$TABLE_PREFIX.$schemaTable."'")) > 0;
    // if ($table_exists) continue;
    // try {
    //     $schema = $api->loadSchema($schemaTable);
    //     continue;
    // }
    // catch(Exception $e) {
    //     $shouldCreateAndUpdate = true;
    //     $schema = json_decode(file_get_contents(__DIR__.'/schemas/'.$schemaFile), true);
    //     $api->saveSchema($schemaTable, $schema);
    // }
}
*/
// if ($shouldCreateAndUpdate) createMissingSchemaTablesAndFields();