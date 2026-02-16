<section id="banner-portada2" class="relative">
    <ul class="slideshow2">
        <? foreach($portada["banner"] as $cont => $banner):
            ?>
            <li>
                <div class="overlay wrapper-flex">
                    <div class="container">
                        <div class="col-xs-6">
                            <h2 class="wow fadeInLeft"><?=t($banner,"info1");?></h2>
                            <div class="separa-10"></div>
                            <p class="wow fadeInUp" data-wow-delay="1s"><?=t($banner,"info2");?></p>
                            <?=($banner["info3"]) ? '<div class="separa-20"></div><p class="wow fadeInUp" data-wow-delay="1s"><a class="btn btn-outline" href="'.t($banner,"info3").'">'.t_var("Más info").'</a></p>' : "";?>
                            <?=@$web ?  t(array("contenido" => "{FORMULARIO_SOLICITAR}"), "contenido", array("clases" => "btn btn-outline btn-lg wow fadeInUp", "widget" => false, "tipo" => "modal")) : "";?>
                        </div>
                        <div class="asset wow fadeInRight" data-wow-duration="2s" style="background-image: url('<?=parsea_imagen(@$banner["urlPath"]);?>');"></div>
                    </div>
                </div>
                <div class="imagen" style="background-image: url('<?=parsea_imagen(@$portada["fondos_banner"][$cont]["urlPath"]);?>');"></div>
            </li>
            <? 
        endforeach;?>
    </ul>
</section>
<section id="bienvenida">

    <div class="container">

        <div class="separa-40 "></div>
        <div class="separa-40 hidden-xs hidden-sm"></div>
        <div class="row">

            <div class="col-md-10 col-md-offset-1">
                <h2 class="text-primary text-center"><?=t($portada,"title");?></h2>
                <p class="lead text-center"><?=t($portada,"subtitulo_bienvenida");?></p>

            </div>

        </div>
        <div class="separa-40"></div>
        <div class="separa-40 hidden-xs"></div>
        <div class="row wrapper-flex flex-nomobile">
            <div class="col-md-5 ">
                <p class=""><?=t($portada,"texto_bienvenida");?></p>
            </div>
            <div class="col-md-6 col-md-offset-1 estiloimagen relative">
                <img src="<?=parsea_imagen(@$portada["foto"][0]["urlPath"]);?>" alt="<?=t($portada,"titulo_bienvenida");?>" class="outline full-width">
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="separa-40"></div>
        <div class="separa-40"></div>
    </div>
</section>     


<section id="servicios-web" class="grey">
    <div class="separa-40"></div>
    <div class="separa-40"></div>
    <div class="container">

        <div id="accordion-holder-web" class="row">
            <div class="col-md-6 text-center wow estiloimagen inverso oscuro fadeInLeft">
                <img class="full-width" src="<?=parsea_imagen(@$portada["foto_servicios"][0]["urlPath"]);?>" alt="<?=t($portada,"titulo_bienvenida");?>">
            </div>		
            <div class="col-md-6">	
                <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="false">
                    <?
                    $servicios = json_decode(t($portada,"servicios"),true);
                    foreach($servicios as $cont => $servicio):
                    ?>
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="heading<?=$cont;?>">
                            <h4 class="panel-title">
                                <a <?=$cont!=0 ? "class='collapsed'": "";?> role="button" data-toggle="collapse" data-parent="#accordion" href="#collapse<?=$cont;?>" aria-expanded="<?=$cont!=0 ? "false" : "true";?>" aria-controls="collapse<?=$cont;?>">
                                    <i class="fa fa-check"></i>&nbsp;<?=$servicio["titulo"];?>
                                </a>
                            </h4>
                        </div>
                        <div id="collapse<?=$cont;?>" class="panel-collapse collapse <?=$cont!=0 ? "" : "in";?>" role="tabpanel" aria-labelledby="heading<?=$cont;?>">
                            <div class="panel-body">
                                <?=$servicio["texto"];?>
                            </div>
                        </div>
                    </div>

                    <?
                    endforeach;
                    ?>
                </div>   
            </div>
        </div>

    </div>
    <div class="separa-40"></div>
    <div class="separa-40"></div>    
</section>

<section class="black">
    <div class="container">

        <div class="separa-40"></div>
        <div class="separa-40 hidden-xs hidden-sm"></div>
        <h2 class=" text-center text-white"><?=t($portada,"titulo_productos");?></h2>
        <p class="lead text-center"><?=t($portada,"subtitulo_productos");?></p>

        <div class="separa-20"></div>


        <div class="separa-40"></div>

        <ul class="bxslider">
            <? $productos = $portada["productos"]; ?>  
            <? foreach ($productos as $cont => $producto): ?>
            <li class="col-md-3">
                <a class="swipebox" rel="swipebox" href="<?= parsea_imagen($portada["productos"][$cont]["urlPath"]) ?>" title="<?=$portada["productos"][$cont]["info1"] ?>">
                    <div class="imagen" style="background-image:url('<?= parsea_imagen($portada["productos"][$cont]["urlPath"]) ?>'); background-size:contain;">
                    </div>

                </a>

            </li>

            <? endforeach ?>
        </ul>
        <div class="separa-40"></div>
        <div class="separa-40 hidden-xs" ></div>
    </div>
</section>



<section id="services">
    <div class="separa-40"></div>
    <div class="separa-40"></div>    
    <div class="container">

        <div class="row">
            <div class="col-md-6">
                <?
                $textos = json_decode(t($portada,"textos_bloque_final"),true);
                foreach($textos as $texto):
                    ?>

                    <div class="service-box animated fadeInUp visible" data-animation="fadeInUp" data-animation-delay="300">
                        <h4><i class="fa fa-<?=$texto["icono"];?> text-info"></i>&nbsp;&nbsp;<?=$texto["titulo"];?></h4>
                        <div class="separa"></div>
                        <p><?=$texto["texto"];?></p>
                    </div>
                    <div class="separa-30"></div>

                    <? 
                endforeach;
                ?>
            </div>
            <div class="col-md-6 text-center">
                <div class="row">
                    <div class="col-md-10 col-md-offset-1">
                        <h3 class="titular"><?=t_var("Solicita más información");?></h3>
                        <div class="separa-20"></div>
                        <?=t(array("contenido" => "{FORMULARIO_SOLICITAR}"), "contenido", array("clases" => "btn btn-info btn-outline btn-lg wow fadeInUp", "widget" => true, "tipo" => "inline"));?>    
                    </div>
                </div>

            </div>
        </div>	
    </div>
    <div class="separa-40"></div>
    <div class="separa-40"></div>     
</section>
