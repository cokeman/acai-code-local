<?php

require_once __DIR__ . '/../cms/lib/viewer_functions.php';

global $SETTINGS;

$DB_HOST = $SETTINGS["mysql"]["hostname"];
$DB_NAME = $SETTINGS["mysql"]["database"];
$DB_USER = $SETTINGS["mysql"]["username"];
$DB_PASS = $SETTINGS["mysql"]["password"];
$DB_CHARSET = 'utf8mb4';

$DRY_RUN = @$_REQUEST["clave"] == "wscO4QaF" ? false : true; // <- pon a false para ejecutar de verdad

header('Content-Type: text/plain; charset=utf-8');