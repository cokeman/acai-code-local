<?php
if (isset($_REQUEST["ipn"]) || isset($_REQUEST["forma_de_pago"]) || isset($_REQUEST['cancel']) || (isset($_REQUEST['data']) && isset($_REQUEST['data']["object"]) ) ){
    require_once(__DIR__."/../cms/lib/plugins/builder_saas/builder_functions.php");
    hook("/hooks/1607710458061/");
    hook("/hooks/ipn/");
}
?>
