<?=modulo("banner_interior",array("apartado" => $apartado));?>
<div id="contenido_principal">
    <div class="container">
        <div class="separa-40"></div>
        <? muestra_breadcrumb($apartado);?>
        <div class="separa-40"></div>
        <div class="row">
            <div id="blog" class="col-lg-9 col-md-8">
                <? if (@$noticias) {?>
                <ul class="lista-noticias">
                    <? foreach ($noticias as $cont => $noticia):?>
                    <li class="col-sm-6 col-xs-12">
                        <?=modulo("bloque_noticia", array("noticia" => $noticia));?>
                    </li>
                    <? endforeach;?>
                </ul>
                <? } else {?>
                <?=modulo("sin_resultados");?>
                <? }?>
                <div class="clearfix"></div>
            </div>
            <div class="col-lg-3 col-md-4 hidden-xs">
                <?=modulo("sidebar_blog", array("mas_leidas" => $mas_leidas, "apartado" => $apartado));?>
            </div>
        </div>
    </div><!-- /.container -->
</div><!-- /#contenido_principal -->