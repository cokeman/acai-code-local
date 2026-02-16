    <? 
    $dummy = []; addPlugins("tpl_foot_body",$dummy); 
    ?>
    <footer>
        <div class="separa-40"></div>
        <div class="container">
            <ul class="redes-sociales list-inline">
                <? foreach($configuracionRecord["redes_sociales_bd"] as $red):?>
                <li><a href="<?=$red["enlace"];?>" target="_blank" rel="noopener"><i class="fa fa-<?=$red["nombre"];?>"></i></a></li>
                <? endforeach;?>
            </ul>
            <div class="separa-20"></div>
            <ul class="datos-contacto list-inline">
                <li><a href="mailto:<?=t($configuracionRecord, "correo_admin");?>"><?=t($configuracionRecord, "correo_admin");?></a></li>
                <li><?=preg_replace_callback("/([0-9 ]+)/", function($matches) {
                        return '<a href="tel:'.str_replace(" ", "", $matches[1]).'">'.$matches[1].'</a> ';
                    }, t($configuracionRecord, "telefono"));?></li>
            </ul>
            <?=t($configuracionRecord, "direccion");?>
            <div class="separa-30"></div>
            <ul class="otros-contenidos list-inline">
                <? foreach($otros_contenidos as $otro):?>
                <li><a href="<?=t($otro, "enlace");?>"<? if (@$otro["no_indexar_en_google"]) {?> rel="nofollow"<?}?>><?=t($otro, "name");?></a></li>
                <? endforeach;?>
            </ul>
            <div class="separa-10"></div>
            <p><?=$pie_de_pagina;?><br>Made with <i class="fa fa-heart"></i> by <a href="https://www.cocosolution.com" target="_blank" rel="noopener">Coco Solution - Diseño web Freelance</a></p>
        </div>
        <div class="separa-40"></div>
    </footer>
    <? 
    if (@$configuracionRecord["telefono"]) {
        ?><a href="tel:<?=str_replace(" ", "", t($configuracionRecord, "telefono"));?>" id="callCTA" class="wrapper-flex" aria-label="<?=t_var("Llámanos");?>"><i class="fa fa-phone"></i></a><? 
    }

    ?><style>#loading{display:none;}</style><?

    if (HAY_TIENDA) {
        echo modulo("modal_livesearch");
    }
    echo Module::links();
    $dummyLib = []; addPlugins("tpl_foot",$dummyLib); 
    
    Resource::link('https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css', true);
    Resource::link('/css/bootstrap.min.css');
    Resource::link('/css/bxslider.min.css');
    Resource::link('/css/swipebox.min.css', true);
    Resource::link('/css/animate.css', true);
    Resource::link('/css/rrssb.css', true);
    if (HAY_TIENDA) {
        Resource::link('/css/cesta.css', true);
    }
    Resource::link('/style-base.css');
    Resource::link('/style.css');
    ?>

    <script data-cache src="https://code.jquery.com/jquery-2.2.4.min.js" integrity="sha256-BbhdlvQf/xTY9gja0Dq3HiwQF8LaCRTXxZKRutelT44=" crossorigin="anonymous"></script>
    <script data-cache src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

    <script src="<?=h("/js/bootstrap.min.js");?>"></script>
    <script data-cache src="<?=h("/js/bxslider.min.js");?>"></script>
    <script data-cache src="<?=h("/js/jquery.swipebox.min.js");?>"></script>
    <script data-cache src="<?=h("/js/wow.min.js");?>"></script>
    <script src="<?=h("/js/rrssb.min.js");?>" async defer></script>
    <script data-cache src="<?=h("/js/fetch.js");?>"></script>
    
    <? if (HAY_TIENDA) {?>
        <script data-cache src="<?=h("/js/liveSearchModal.js");?>" async defer></script>
        <script data-cache src="<?=h("/js/cesta_class.js");?>"></script>
    <? }?>

    <script src="<?=h("/js/mis-scripts.js");?>"></script>
    <?
     
    if (HAY_TIENDA) {
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
    }
    echo Module::scripts();
    ?>
    <script data-cache src="<?=h("/js/cookies.js");?>"></script>
    <? 
    if (@$configuracionRecord["usar_service_worker"] && isHTTPS()) {
        ?><script data-cache src="<?=h('/js/sw-controller.js');?>"></script><? 
    }
    if (hasRecaptcha()) {
        ?><script src='https://www.google.com/recaptcha/api.js?hl=<?=@$_REQUEST['idioma'] ?: 'es';?>'></script><? 
    }
    ?>

</body>
</html>
