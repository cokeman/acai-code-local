<article id="producto">
    <div class="container">
        <div class="separa-40"></div>
        <? muestra_breadcrumb($producto, array($apartado));?>
        <div class="separa-40"></div>
        <div class="row">
            <div class="col-md-6">
                <div class="image-holder">
                    <div class="otras-fotos">
                        <a href="<?=parsea_imagen(@$producto["foto"][0]["urlPath"]);?>" data-rel="swipebox">
                            <div class="img">
                                <img src="<?=parsea_imagen(@$producto["foto"][0]["urlPath"]);?>" alt="<?=t($producto, "name");?>">
                            </div>
                        </a>
                        <? if (@$producto["otras_fotos"]) {?>
                            <? foreach ($producto["otras_fotos"] as $foto):?>
                            <a href="<?=parsea_imagen($foto["urlPath"]);?>" data-rel="swipebox">
                                <div class="img">
                                    <img src="<?=parsea_imagen($foto["urlPath"]);?>" alt="<?=t($producto, "name");?>">
                                </div>
                            </a>
                            <? endforeach;?>
                        <? }?>
                    </div>
                    <div class="img">
                        <img src="<?=parsea_imagen(@$producto["foto"][0]["urlPath"]);?>" alt="<?=t($producto, "name");?>">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <h1><?=t($producto, "name");?></h1>
                <?=t($producto, "descripcion");?>
                <div class="separa-20"></div>
                <span class="precio"><?=t_var("Precio");?>:&nbsp;&nbsp;</span>
                <? if (@$producto["oferta"]) {?>
                    <span class="precio alt"><span class="tachado"><?=parsea_precio(parsea_decimales($producto["precio"]));?></span> <?=parsea_precio(parsea_decimales($producto["precio"])*(1-parsea_decimales($producto["oferta"]["descuento"])/100));?></span>
                <? }else{?>
                    <span class="precio"><?=$producto["precio"];?></span>
                <? }?>
                <div class="separa-20"></div>
                <? if (intval(@$producto["stock"]) > 0) {?>
                    <button class="btn btn-success add-to-basket" data-producto="<?=$producto["num"];?>" data-variacion="0"><?=t_var("Añadir a la cesta");?></button>
                <? }else{?>
                    <button class="btn btn-danger" data-producto="<?=$producto["num"];?>" data-variacion="0"><?=t_var("Sin Stock");?></button>
                <? }?>
                <button class="btn btn-like add-to-favorites" data-producto="<?=$producto["num"];?>"><i class="fa fa-heart-o"></i></button>
                <? if (@$producto["opciones"]) {?>
                <div class="separa-40"></div>
                <h2><?=t_var("Otros modelos");?></h2>
                <ul class="otros-modelos">
                    <? foreach ($producto["opciones"] as $opcion):?>
                    <li>
                        <div>
                            <div class="img">
                                <img src="<?=parsea_imagen(@$opcion["foto"] ? $opcion["foto"] : @$producto["foto"][0]["urlPath"]);?>" alt="<?=t($producto, "name");?>">
                            </div>
                            <div>
                                <? if (@$opcion["oferta"]) {?>
                                <span class="precio alt"><span class="tachado"><?=parsea_precio(parsea_decimales($opcion["precio"]));?></span> <?=parsea_precio(parsea_decimales($opcion["precio"])*(1-parsea_decimales($opcion["oferta"]["descuento"])/100));?></span>
                                <? }else{?>
                                <span class="precio"><?=parsea_precio(parsea_decimales($opcion["precio"]));?></span>
                                <? }?>
                                <? if (@$opcion["variaciones"]) {?>
                                <? if (!is_array($opcion["variaciones"])) $opcion["variaciones"] = json_decode($opcion["variaciones"], true);?>
                                <? $schema = loadSchema($producto["tableName"]);?>
                                <ul class="bullet">
                                <? foreach ($opcion["variaciones"] as $var):?>
                                    <? if (!@$schema[$var["campo"]]["label"]) continue;?>
                                    <li><?=$schema[$var["campo"]]["label"];?>: <?=$var["valor"];?></li>
                                <? endforeach;?>
                                </ul>
                                <? }?>
                                <? if (intval(@$opcion["stock"])) {?>
                                <button class="btn btn-success add-to-basket" data-producto="<?=$producto["num"];?>" data-variacion="<?=$opcion["num"];?>"><?=t_var("Añadir a la cesta");?></button>
                                <? }else{?>
                                <button class="btn btn-danger" data-producto="<?=$producto["num"];?>" data-variacion="<?=$opcion["num"];?>"><?=t_var("Sin Stock");?></button>
                                <? }?>
                            </div>
                        </div>
                    </li>
                    <? endforeach;?>
                </ul>
                <div class="separa-10"></div>
                <? }?>
            </div>
            <div class="separa-40"></div>
        </div>
    </div>
    <? if (@$producto["valoraciones"]["lista"] && @$configuracionTienda["valoraciones"]) {?>
    <div class="bg-gray">
        <div class="separa-40"></div>
        <div class="separa-40"></div>
        <div class="container">
            <div class="col-sm-8 col-sm-offset-2">
                <h2 class="titular"><?=t_var("Valoraciones");?></h2>
                <p class="subtitulo"><?=t_var("Valoración media");?>: <?=number_format($producto["valoraciones"]["media"], 2);?> / 5.00</p>
                <img src="<?=RUTA_PLANTILLA;?>/images/separador.png" alt="" class="separador">
                <ul class="lista-valoraciones">
                    <? foreach ($producto["valoraciones"]["lista"] as $valoracion):?>
                    <li>
                        <p><?=$valoracion["comentario"];?></p>
                        <ul class="stars">
                            <li>
                                <? for ($i = 1; $i <= intval($valoracion["valoracion"]); $i++) {?>
                                <i class="fa fa-star"></i>
                                <? }?>
                                <? for ($i = intval($valoracion["valoracion"])+1; $i <= 5; $i++) {?>
                                <i class="fa fa-star-o"></i>
                                <? }?>
                            </li>
                        </ul>
                    </li>
                    <? endforeach;?>
                </ul>
            </div>
        </div>
        <div class="separa-40"></div>
        <div class="separa-40"></div>
    </div>
    <? }?>
</article>

<? if(@$producto["precio_por_cantidades_bd"]){?>
<section>
    <div class="separa-40"></div>
    <div class="separa-10"></div>
    <div class="container">
        <h2 class="text-center"><?=t_var("Precios por cantidades");?></h2>
        <div class="separa-40"></div>
        <table class="table">
          <thead>
            <tr>
                <th><?=t_var("Cantidad");?></th>
                <th><?=t_var("Precio");?></th>
            </tr>
          </thead>
          <tbody>
            <? foreach($producto["precio_por_cantidades_bd"] as $precio_cantidad):?>
            <tr>
                <td><?=t($precio_cantidad, "cantidad");?></td>
                <td><?=parsea_precio(parsea_decimales(t($precio_cantidad, "precio")));?></td>
            </tr>
            <? endforeach;?>
          </tbody>
        </table>
    </div>
    <div class="separa-40"></div>
    <div class="separa-10"></div>
</section>
<?}?>


<? if (@$relacionados) {?>
<section>
    <div class="separa-40"></div>
    <div class="separa-40"></div>
    <div class="container">
        <h2 class="titular"><?=t_var("Productos relacionados");?></h2>
        <ul class="lista-productos">
            <? foreach ($relacionados as $producto):?>
            <li class="col-md-3 col-sm-6">
                <?=modulo("bloque_producto", array("producto" => $producto));?>
            </li>
            <? endforeach;?>
        </ul>
    </div>
    <div class="separa-40"></div>
    <div class="separa-40"></div>
</section>
<? }?>
