<? if (!@$_REQUEST["viewAMP"]){ ?>
    {CODE}
<? }else{ ?>
    <? $otros_contenidos = dame_registros("otros_contenidos", "visible_en_el_menu=1"); ?>
    <footer id="footer">
        <ul class="otros-contenidos">
            <? foreach ($otros_contenidos as $otro):?>
            <li><a href="<?=t($otro, 'enlace');?>" <? if (@$otro["no_indexar_en_google"]) {?>rel="nofollow"<?}?>><?=t($otro, "name");?></a></li>
            <? endforeach;?>
        </ul>
        <?=t($configuracionRecord, "pie_de_pagina");?><br>Made with love by <a href="https://www.cocosolution.com" rel="noopener noreferer" target="_blank">Coco Solution</a>
    </footer>
    <? if (@$configuracionRecord["telefono"]) {?>
    <a href="tel:<?=str_replace(" ", "", t($configuracionRecord, "telefono"));?>" id="amp-callCTA">
        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="phone_svg" x="0px" y="0px" width="512px" height="512px" viewBox="0 0 348.077 348.077" style="enable-background:new 0 0 348.077 348.077;" xml:space="preserve"><g><g><g><path d="M340.273,275.083l-53.755-53.761c-10.707-10.664-28.438-10.34-39.518,0.744l-27.082,27.076     c-1.711-0.943-3.482-1.928-5.344-2.973c-17.102-9.476-40.509-22.464-65.14-47.113c-24.704-24.701-37.704-48.144-47.209-65.257     c-1.003-1.813-1.964-3.561-2.913-5.221l18.176-18.149l8.936-8.947c11.097-11.1,11.403-28.826,0.721-39.521L73.39,8.194     C62.708-2.486,44.969-2.162,33.872,8.938l-15.15,15.237l0.414,0.411c-5.08,6.482-9.325,13.958-12.484,22.02     C3.74,54.28,1.927,61.603,1.098,68.941C-6,127.785,20.89,181.564,93.866,254.541c100.875,100.868,182.167,93.248,185.674,92.876     c7.638-0.913,14.958-2.738,22.397-5.627c7.992-3.122,15.463-7.361,21.941-12.43l0.331,0.294l15.348-15.029     C350.631,303.527,350.95,285.795,340.273,275.083z" fill="#FFFFFF"/></g></g></g></svg>
    </a>
    <? }?>
<? }

if (!@$_REQUEST["viewAMP"]){ 
    ?>
    {BOTTOM_LIBRARIES}
    <?
}
    echo '<script>var MODULES_LOADED = '.json_encode(array_keys(Module::$loaded)).';</script>';
    echo Module::links();
    echo Module::scripts();

    /*if (HAY_TIENDA && !@$_REQUEST["viewAMP"]) {
        echo modulo("modal_livesearch");
        ?>
        <script data-cache src="<?=h("/js/liveSearchModal.js");?>" async defer></script>
        <script data-cache src="<?=h("/js/cesta_class.js");?>"></script>
        <?
        Resource::link('/css/cesta.css', true);
        ?>
        <script>
            var RUTA_RAIZ = '<?=RUTA_RAIZ;?>';
            var ENLACE_CESTA = '<?=t(@$apartadoCesta, "enlace");?>';
            var TEXTOS_CESTA = {
                productoAnadido: '<?=t_var("Producto añadido");?>',
                textoProductoAnadido: '<?=t_var("El producto ha sido añadido a la cesta");?>',
                cancel: '<?=t_var("Continuar comprando");?>',
                ok: '<?=t_var("Ir a la cesta");?>'
            }
        </script>
        <? if (@$configuracionTienda["pago_por_stripe"]) {?>
        <script src="https://js.stripe.com/v3/"></script>
        <? }
    }*/

    if (!@$_REQUEST["viewAMP"]){ ?> 
        <? if (!defined('USE_MIN_TAILWIND') || (defined('USE_MIN_TAILWIND') && !USE_MIN_TAILWIND)) { ?> {STYLE} <? } ?> 
        <? if (defined('USE_MIN_TAILWIND') && USE_MIN_TAILWIND == "2") { 
            generateMinCssV2(); 
            ?> <link rel="stylesheet" href="<?=h("/css/cocotail.min.css");?>" data-value="<?=USE_MIN_TAILWIND;?>"> 
        <? } ?> 

        <? if (!defined('USE_MIN_JS_TAILWIND') || (defined('USE_MIN_JS_TAILWIND') && !USE_MIN_JS_TAILWIND)) { ?> {JAVASCRIPT} <? } ?> 
        <? if (defined('USE_MIN_JS_TAILWIND') && USE_MIN_JS_TAILWIND == "2") { 
            if (function_exists("generateMinJsV2")) generateMinJsV2(); 
            ?> <script src='<?=h("/js/cocotail.min.js");?>'></script> 
        <? } ?> 
        
    <?}

    $dummyLib = []; addPlugins("tpl_foot",$dummyLib);

    if (!@$_SESSION["pruebas"] && !@$_REQUEST["viewAMP"]){?><script defer data-cache src="<?=h("/js/cookies.js");?>"></script><?}

    if (@$configuracionRecord["usar_service_worker"] && isHTTPS() && !@$_REQUEST["viewAMP"]) {
        ?><script data-cache src="<?=h('/js/sw-controller.js');?>"></script><?
    }
    if (hasRecaptcha() && !@$_REQUEST["viewAMP"] && property_exists('CocoParser', 'hayCaptcha') && @CocoParser::$hayCaptcha) {
        ?><script async defer data-cache src='https://www.google.com/recaptcha/api.js'></script><?
    }
    if (@$_REQUEST["tokenEditor"]){ $_SESSION["tokenEditor"] = @$_REQUEST["tokenEditor"]; }
    if (@$_REQUEST["uploadToken"]){ $_SESSION["uploadToken"] = @$_REQUEST["uploadToken"]; }
    if (@$_SESSION["tokenEditor"] && !@$_REQUEST["cerrarsesion"]){
        CocoWS::setToken(@$_SESSION["tokenEditor"]);
        if (CocoWS::validateUploadToken(@$_SESSION["uploadToken"])) {
            
            ?>
            <script>
                var tokenEditor = "<?=@$_SESSION["tokenEditor"];?>";
                var uploadToken = "<?=@$_SESSION["uploadToken"];?>";
            </script>
            <script src="https://cdn.ckeditor.com/ckeditor5/17.0.0/balloon/ckeditor.js"></script>
            <script src="https://cms.cocosolution.com/js/realtime-ckeditor.js"></script>
            <link rel="stylesheet" href="https://cms.cocosolution.com/css/realtime-ckeditor.css">
            <?
        }else{
            ?><!-- token not validated --><?
        }
    }
    ?>

</body>
</html>
