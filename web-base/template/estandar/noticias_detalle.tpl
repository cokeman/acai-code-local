<?=modulo("banner_interior", array("apartado" => $apartado));?>

<div id="contenido_principal">
    <div class="container">
        <div class="separa-40"></div>
        <? muestra_breadcrumb($noticia, array($apartado));?>
        <div class="separa-40"></div>
        <div class="row">
            <div id="blog" class="col-lg-9 col-md-8 content">
                <ul class="col-xs-12">
                    <li>
                        <?=modulo("bloque_noticia", array("noticia" => $noticia, "interior" => true));?>
                    </li>
                </ul>
                <div class="col-md-12">
                    <h4 class="titular"><?=t_var("Comparte esta noticia en tus redes");?></h4>
                    <img src="<?=RUTA_PLANTILLA;?>/images/separador.png" alt="" class="separador">
                    <?=modulo("redes_sociales", array("url" => urlencode(protocol()."://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]), "titulo" => t($noticia, "title")));?>
                </div>
                <div class="separa-40"></div>
            </div>     
            <div class="col-lg-3 col-md-4 hidden-xs">
                <?=modulo("sidebar_blog", array("mas_leidas" => $mas_leidas, "apartado" => $apartado));?>
            </div>
        </div>
    </div>
</div>