<?php
global $CURRENT_USER;

if ($var !== 'multiidiomas') return false;
if (@$_REQUEST['action']) {
    include 'hooks/idiomas.php';
    // set_time_limit(120);
}
if (!@$_REQUEST['action']) {
    // schemaCheck();

    showHeader();
}

if (!@$_REQUEST['action']) {
    // die(var_dump($CURRENT_USER));
    include 'view/index.php';
    showFooter();
}

die();