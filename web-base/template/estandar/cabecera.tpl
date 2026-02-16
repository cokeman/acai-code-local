<!DOCTYPE html>
<html lang="<?=(@$_REQUEST["idioma"]) ? @$_REQUEST["idioma"] : "es";?>">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="description" content="<?=$meta_descripcion;?>">
        <meta name="keywords" content="<?=$meta_palabras;?>">
        <meta name="author" content="Coco Solution">
        
        <? if (@$configuracionRecord["pwa_theme_color"]) {?>
            <meta name="theme-color" content="<?=$configuracionRecord["pwa_theme_color"];?>">
        <? }?>
        
        <?=@$otros_metas;?>
        
        <title><?=$titulo_de_pagina;?></title>
        
        <? if (@$configuracionRecord["usar_service_worker"]) {?>
            <link rel="manifest" href="/manifest.json">
        <? }?>

        <? $dummy = []; addPlugins("tpl_head",$dummy);?>
        
        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
            <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->

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
        if (@$alternate) {
            foreach ($alternate as $alt):
                ?>
                <link rel="alternate" href="<?=protocol()."://".$_SERVER["HTTP_HOST"].base64_decode($alt["fieldValue"]);?>" hreflang="<?=$alt["prefix"];?>" />
                <?
            endforeach;
        }
        ?>
        <link rel="preconnect" href="https://fonts.gstatic.com/" crossorigin>
        <link rel="preconnect" href="https://maxcdn.bootstrapcdn.com/" crossorigin>

        <style>#loading{position: fixed; z-index: 9999; transform: translateZ(100px); -webkit-transform: translateZ(100px); -moz-transform: translateZ(100px); background-color: #fff; left: 0; top: 0; width: 100%; height: 100%; display: flex; justify-content: center; -webkit-justify-content: center; align-items: center; -webkit-align-items: center;}.sk-folding-cube{margin:20px auto;width:40px;height:40px;position:relative;-webkit-transform:rotateZ(45deg);transform:rotateZ(45deg)}.sk-folding-cube .sk-cube{float:left;width:50%;height:50%;position:relative;-webkit-transform:scale(1.1);-ms-transform:scale(1.1);transform:scale(1.1)}.sk-folding-cube .sk-cube:before{content:'';position:absolute;top:0;left:0;width:100%;height:100%;background-color:#40A83C;-webkit-animation:sk-foldCubeAngle 2.4s infinite linear both;animation:sk-foldCubeAngle 2.4s infinite linear both;-webkit-transform-origin:100% 100%;-ms-transform-origin:100% 100%;transform-origin:100% 100%}.sk-folding-cube .sk-cube2{-webkit-transform:scale(1.1) rotateZ(90deg);transform:scale(1.1) rotateZ(90deg)}.sk-folding-cube .sk-cube3{-webkit-transform:scale(1.1) rotateZ(180deg);transform:scale(1.1) rotateZ(180deg)}.sk-folding-cube .sk-cube4{-webkit-transform:scale(1.1) rotateZ(270deg);transform:scale(1.1) rotateZ(270deg)}.sk-folding-cube .sk-cube2:before{-webkit-animation-delay:.3s;animation-delay:.3s}.sk-folding-cube .sk-cube3:before{-webkit-animation-delay:.6s;animation-delay:.6s}.sk-folding-cube .sk-cube4:before{-webkit-animation-delay:.9s;animation-delay:.9s}@-webkit-keyframes sk-foldCubeAngle{0%,10%{-webkit-transform:perspective(140px) rotateX(-180deg);transform:perspective(140px) rotateX(-180deg);opacity:0}25%,75%{-webkit-transform:perspective(140px) rotateX(0);transform:perspective(140px) rotateX(0);opacity:1}100%,90%{-webkit-transform:perspective(140px) rotateY(180deg);transform:perspective(140px) rotateY(180deg);opacity:0}}@keyframes sk-foldCubeAngle{0%,10%{-webkit-transform:perspective(140px) rotateX(-180deg);transform:perspective(140px) rotateX(-180deg);opacity:0}25%,75%{-webkit-transform:perspective(140px) rotateX(0);transform:perspective(140px) rotateX(0);opacity:1}100%,90%{-webkit-transform:perspective(140px) rotateY(180deg);transform:perspective(140px) rotateY(180deg);opacity:0}}</style>
        <?
        
        if (@$configuracionRecord["otros_scripts"]) {
            echo base64_decode(t($configuracionRecord, "otros_scripts"));
        }
        ?>

    </head>
    <body <? if (@$index) {?>class="portada"<?}?>>
        
        <? if (@$configuracionRecord["pagina_publicada"] || true) {?>
        <div id="loading">
            <div class="sk-folding-cube">
                <div class="sk-cube1 sk-cube"></div>
                <div class="sk-cube2 sk-cube"></div>
                <div class="sk-cube4 sk-cube"></div>
                <div class="sk-cube3 sk-cube"></div>
            </div>
        </div>
        <? }?>
        
        <header>
            <nav class="navbar navbar-default navbar-fixed-top">
                <div class="container">
                    <!-- Brand and toggle get grouped for better mobile display -->
                    <div class="navbar-header">
                        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false">
                            <span class="sr-only">Toggle navigation</span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                        </button>
                        <a class="navbar-brand" href="<?=RUTA_RAIZ;?>/"><img src="<?=RUTA_PLANTILLA;?>/images/logo.png" alt="<?=t($configuracionRecord, "tienda_nombre_empresa");?>"></a>
                    </div>

                    <!-- Collect the nav links, forms, and other content for toggling -->
                    <div class="collapse navbar-collapse" id="navbar">
                        <ul class="nav navbar-nav navbar-right">
                            <? muestramenu();?>
                        </ul>
                    </div><!-- /.navbar-collapse -->
                </div><!-- /.container-fluid -->
                <? if (@$configuracionTienda["categorias_nav"] && HAY_TIENDA) {?>
                <div id="postnav">
                    <div class="container">
                        <ul class="lista-destacadas">
                            <? foreach ($configuracionTienda["categorias_nav_bd"] as $categoria):?>
                            <li<?=comprueba_si_activo($categoria)?>>
                                <a href="<?=t($categoria, "enlace");?>" data-category="<?=$categoria["num"];?>">
                                    <? if (@$categoria["icono"]) {?>
                                    <img src="<?=parsea_imagen($categoria["icono"][0]["urlPath"]);?>" alt="<?=addslashes(t($categoria, "name"));?>">
                                    <? }?>
                                    <?=t($categoria, "name");?>
                                </a>
                            </li>
                            <? endforeach;?>
                        </ul>
                    </div>
                </div>
                <? }?>
            </nav>
        </header>