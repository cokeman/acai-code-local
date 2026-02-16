<?=modulo("banner_portada", array("portada" => $portada));?>

<section>
    <div class="separa-40"></div>
    <div class="separa-40"></div>
    <div class="container">
        <h2 class="titular"><?=t($portada, "titulo_seccion_iconos");?></h2>
        <p class="subtitulo"><?=t($portada, "subtitulo_seccion_iconos");?></p>
        <? if (@$portada["iconos"]) {?>
        <ul class="bloques-portada">
            <? foreach ($portada["iconos"] as $icono):?>
            <li>
                <? if (@$icono["info2"]) {?>
                <a href="<?=t($icono, "info2");?>">
                <? }?>
                    <div class="wrapper-flex">
                        <img src="<?=parsea_imagen($icono["urlPath"]);?>" alt="<?=addslashes(t($icono, "info1"));?>">
                        <h3><?=t($icono, "info1");?></h3>
                    </div>
                <? if (@$icono["info2"]) {?>
                </a>
                <? }?>
            </li>
            <? endforeach;?>
        </ul>
        <? }?>
    </div>
    <div class="separa-40"></div>
</section>

<section class="bg-gray">
    <div class="separa-40"></div>
    <div class="separa-40"></div>
    <div class="container">
        <h2 class="titular"><?=t($portada, "titulo_productos");?></h2>
        <? if (@$portada["subtitulo_productos"]) {?>
        <p class="subtitulo"><?=t($portada, "subtitulo_productos");?></p>
        <? }?>
        <? if (@$portada["productos"]) {?>
        <div class="relative">
            <ul class="lista-productos slider">
                <? foreach ($portada["productos_bd"] as $producto):?>
                <li>
                    <?=modulo("bloque_producto", array("producto" => $producto));?>
                </li>
                <? endforeach;?>
            </ul>
            <button id="nextProduct">&gt;</button>
            <button id="prevProduct">&lt;</button>
        </div>
        <? }?>
    </div>
    <div class="separa-40"></div>
    <div class="separa-40"></div>
</section>
<? if (@$portada["bloques_imagenes"]) {?>
<section>
    <ul class="bloques-full">
        <? foreach ($portada["bloques_imagenes"] as $bloque):?>
        <li>
            <div class="img">
                <img src="<?=parsea_imagen($bloque["urlPath"]);?>" alt="<?=addslashes(@$bloque["info5"] ? t($bloque, "info5") : t($bloque, "info1"));?>">
                <div class="wrapper-flex">
                    <div class="content">
                        <h2><?=t($bloque, "info1");?></h2>
                        <? if (@$bloque["info2"]) {?>
                        <h3><?=t($bloque, "info2");?></h3>
                        <? }?>
                        <? if (@$bloque["info3"]) {?>
                        <div class="text-center">
                            <a href="<?=t($bloque, "info3");?>" class="btn btn-default"><?=@$bloque["info4"] ? t($bloque, "info4") : t_var("Ver más");?></a>
                        </div>
                        <? }?>
                    </div>
                </div>
            </div>
        </li>
        <? endforeach;?>
    </ul>
</section>
<? }?>
<? if (@$portada["noticias"]) {?>
<section>
    <div class="separa-40"></div>
    <div class="separa-40"></div>
    <div class="container">
        <h2 class="titular"><?=t($portada, "titulo_noticias");?></h2>
        <? if (@$portada["subtitulo_noticias"]) {?>
        <p class="subtitulo"><?=t($portada, "subtitulo_noticias");?></p>
        <? }?>
        <ul class="lista-noticias">
            <? foreach ($portada["noticias_bd"] as $noticia):?>
            <li class="col-md-4 col-sm-6">
                <?=modulo("bloque_noticia", array("noticia" => $noticia));?>
            </li>
            <? endforeach;?>
        </ul>
    </div>
</section>
<? }?>