<!DOCTYPE html>
<html lang="<?=(@$_REQUEST["idioma"]) ? @$_REQUEST["idioma"] : "es";?>" <?=@$_REQUEST["viewAMP"] ? "⚡" : "";?> >
    <head>
        <?
        if (@$_REQUEST["viewAMP"]){
            ?>
            <script async src="https://cdn.ampproject.org/v0.js"></script>
            {AMP_LIBRARIES}
            <link rel="canonical" href="https://<?=$_SERVER["HTTP_HOST"].str_replace("/amp/","/",$_SERVER["REQUEST_URI"]);?>">
            <?
        }?>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="description" content="<?=htmlentities($meta_descripcion);?>">

        <?php
            global $ocultar_metas_og, $tabla, $TABLE_PREFIX;
            //Politicas no indexables
            if($tabla == "cms_otros_contenidos"){ 
                ?><meta name="robots" content="noindex,nofollow"><? 
            } 
            $TEMPLATE = realpath(dirname(__FILE__)."/../../");
            $logo = $TEMPLATE."/images/logo.png?t=1";

            if (@$configuracionTienda["logo"]){
                $logo = $configuracionTienda["logo"][0]['urlPath'];
            }

            if (!@$ocultar_metas_og) {
                echo '<meta property="og:title" content="'.@$titulo_de_pagina.'">';
                echo '<meta property="og:description" content="'.htmlentities(@$meta_descripcion).'">';

                if (!class_exists("CustomCode")) require_once __DIR__."/../../../../cms/lib/plugins/builder_saas/replace_code.php";

                // ESTO HAY QUE MEJORARLO PORQUE SI LA IMAGEN ES DE IDIOMA MOSTRARA LA IMAGEN EN ESPAÑOL

                // Buscamos en el campo 'meta_image' del registro
                $imageExists = @mysql_fetch_assoc(mysql_query("SELECT urlPath FROM ".$TABLE_PREFIX."uploads WHERE tableName='".str_replace($TABLE_PREFIX,"",$tabla)."' and recordNum=".intval(@$_REQUEST["num"])." and urlPath not like '%.pdf%' and fieldName='meta_image' ORDER BY `order` ASC LIMIT 1"));

                if (!@$imageExists) {
                    // Buscamos en el primer upload del registro
                    $imageExists = @mysql_fetch_assoc(mysql_query("SELECT urlPath FROM ".$TABLE_PREFIX."uploads WHERE tableName='".str_replace($TABLE_PREFIX,"",$tabla)."' and recordNum=".intval(@$_REQUEST["num"])." and urlPath not like '%.pdf%' ORDER BY `order` ASC LIMIT 1"));
                }

                if (!@$imageExists) {
                    // Buscamos en el campo 'meta_imagen' de configuración
                    $imageExists = @mysql_fetch_assoc(mysql_query("SELECT urlPath FROM ".$TABLE_PREFIX."uploads WHERE tableName='configuracion' and fieldName='meta_image' ORDER BY `order` ASC LIMIT 1"));
                }

                if (@$imageExists) {
                    $image = CustomCode::imagec_inv($imageExists['urlPath'], 1200);
                } else {
                    $image = CustomCode::imagec_inv($logo, 1200);
                }

                echo '<meta property="og:image" content="https://'.$_SERVER["HTTP_HOST"].$image.'">';

                echo '<meta property="twitter:card" content="summary_large_image">';
                echo '<meta property="twitter:site" content="https://'.$_SERVER["HTTP_HOST"].'">';
                echo '<meta property="twitter:title" content="'.$titulo_de_pagina.'">';
                echo '<meta property="twitter:description" content="'.htmlentities(@$meta_descripcion).'">';
                echo '<meta property="twitter:image" content="https://'.$_SERVER["HTTP_HOST"].$image.'">';
            }
        ?>

        <meta name="keywords" content="<?=$meta_palabras;?>">
        <meta name="author" content="<?=@$configuracionRecord["tienda_nombre_empresa"]?:'Coco Solution';?>">

        <? if (@$configuracionRecord["pwa_theme_color"]) {?>
            <meta name="theme-color" content="<?=$configuracionRecord["pwa_theme_color"];?>">
        <? }?>

        <? global $otros_metas,$modulosParaAMP,$redirectToAMP;?>
        <?=@$otros_metas;?>
        <title><?=$titulo_de_pagina;?></title>
        <? if (@$redirectToAMP){?><link rel="amphtml" content="<?="/amp".$_SERVER["REQUEST_URI"];?>"><?}?>

        <? if (@$configuracionRecord["usar_service_worker"]) {?>
            <link rel="manifest" href="/manifest.json">
        <? }?>

        <? $dummy = []; addPlugins("tpl_head",$dummy);?>

        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
            <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
        <?
        if (@$configuracionTienda["favicon"]){
            foreach($configuracionTienda["favicon"] as $favicon){
                if (strpos(strtolower($favicon["urlPath"]),".ico")){
                    ?><link rel="shortcut icon" href="<?=$favicon["urlPath"];?>" type="image/x-icon" /><?
                }else{
                    ?><link rel="apple-touch-icon" <?=@$favicon["info1"] ? "sizes='".$favicon["info1"]."'" : "";?> href="<?=$favicon["urlPath"];?>" /><?
                }
            }
        }else{
            ?>
            <link rel="shortcut icon" href="<?=RUTA_PLANTILLA;?>/icons/favicon.ico" type="image/x-icon" />
            <link rel="apple-touch-icon" href="<?=RUTA_PLANTILLA;?>/icons/apple-touch-icon.png" />
            <link rel="apple-touch-icon" sizes="57x57" href="<?=RUTA_PLANTILLA;?>/icons/apple-touch-icon-57x57.png" />
            <link rel="apple-touch-icon" sizes="72x72" href="<?=RUTA_PLANTILLA;?>/icons/apple-touch-icon-72x72.png" />
            <link rel="apple-touch-icon" sizes="76x76" href="<?=RUTA_PLANTILLA;?>/icons/apple-touch-icon-76x76.png" />
            <link rel="apple-touch-icon" sizes="114x114" href="<?=RUTA_PLANTILLA;?>/icons/apple-touch-icon-114x114.png" />
            <link rel="apple-touch-icon" sizes="120x120" href="<?=RUTA_PLANTILLA;?>/icons/apple-touch-icon-120x120.png" />
            <link rel="apple-touch-icon" sizes="144x144" href="<?=RUTA_PLANTILLA;?>/icons/apple-touch-icon-144x144.png" />
            <link rel="apple-touch-icon" sizes="152x152" href="<?=RUTA_PLANTILLA;?>/icons/apple-touch-icon-152x152.png" />
            <?
        }
        if (@$alternate) {
            foreach ($alternate as $alt):
            ?>
            <link rel="alternate" href="<?=protocol()."://".$_SERVER["HTTP_HOST"].base64_decode($alt["fieldValue"]);?>" hreflang="<?=$alt["prefix"];?>" />
            <?
            endforeach;
        }
        
        // Add self-referenced hreflang
        $current_lang = @$_REQUEST["idioma"] ?: "es";
        $current_url = protocol()."://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
        ?>
        <link rel="alternate" href="<?=$current_url;?>" hreflang="<?=$current_lang;?>" />
        <?
        if (!@$_REQUEST["viewAMP"]){
        ?>
        {TOP_LIBRARIES}
        <?
        }
        if (@$configuracionRecord["otros_scripts"] && !@$_REQUEST["viewAMP"]) {
            echo base64_decode($configuracionRecord["otros_scripts"]);
        }
        $idioma = ["url" => RUTA_RAIZ];
        $idiomas = ["es" => "Español","en" => "English","de" => "Deutsch","fr" => "Français","ru" => "Pусский","it" => "Italiano"];
        $idioma_actual = ["corto" => @$_REQUEST["idioma"] ?: "es"];
        $idioma_actual["largo"] = @$idiomas[$idioma_actual['corto']];
        $session = @$_SESSION;
        $get = @$_GET;
        $GLOBALS["session"] = @$session;
        $GLOBALS["get"] = $get;
        global $thisrecord,$root,$request;
        $thisrecord = @$GLOBALS["apartado"];
        $root = $GLOBALS;
        $request = @$_REQUEST;
        if (!@$_REQUEST["viewAMP"]){
            ?>
            <script>
                var hooksToken = '<?=sha1(session_id().$_SERVER["HTTP_HOST"]);?>';
                var language = '<?=@$_REQUEST['idioma']?:'www'?>';
                {CMSAPI_JS}
            </script>
            <?
        }else{
            ?>
            <style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>

            <?
        }
        ?>
    </head>
    <? if (empty($_REQUEST["enlace"]) || $_REQUEST["enlace"] == "/") $index = true;?>
    <body class="<? if (@$index) {?>portada<?}?> antialiased">
        <? if (!@$_REQUEST["viewAMP"]){?>

        {CODE}

        <? }else{ ?>

        <?
        $apartados = dame_registros("apartados", "visible_en_el_menu=1", "globalOrder ASC");

        $logo_information = getimagesize($logo);
        $logo_ratio = @$logo_information ? $logo_information[1] / $logo_information[0] : 1;

        ?>
        <header class="amp-header">
            <button on="tap:header-sidebar.toggle" tabindex="0" class="amp-header--hamburger" aria-label="<?=t_var('Mostrar menú');?>">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a href="<?=RUTA_RAIZ;?>/" class="amp-header-logo">
                <amp-img src="<?=$logo?>" width="<?=33 / $logo_ratio;?>" height="33" layout="fixed" alt="<?=t($configuracionRecord, 'tienda_nombre_empresa');?>"></amp-img>
            </a>
        </header>

        <amp-sidebar id="header-sidebar" class="ampstart-sidebar px3 md-hide lg-hide" layout="nodisplay">
            <div>
                <button on="tap:header-sidebar.toggle" tabindex="0" class="amp-sidebar--close items-start" aria-label="<?=t_var('Cerrar menú');?>">✕</button>
            </div>
            <ul class="amp-sidebar">
            <li class="amp-sidebar--logo">
                <a href="<?=RUTA_RAIZ;?>/">
                    <amp-img src="<?=$logo?>" width="<?=33 / $logo_ratio;?>" height="33" layout="fixed" alt="<?=t($configuracionRecord, 'tienda_nombre_empresa');?>"></amp-img>
                </a>
            </li>
            <? foreach ($apartados as $apartado):
                $hijo = dame_registros("apartados", "parentNum=$apartado[num] AND visible_en_el_menu=1", "siblingOrder ASC", 1);
                $hijo = @$hijo[0];
                ?>
                <li class="amp-sidebar--item">
                    <a href="<?=@$hijo ? t($hijo, 'enlace') : t($apartado, "enlace");?>">
                        <?=str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", intval($apartado["depth"]));?><?=t($apartado, "name");?>
                    </a>
                </li>
                <? endforeach;?>
            </ul>
        </amp-sidebar>
        <? }?>
