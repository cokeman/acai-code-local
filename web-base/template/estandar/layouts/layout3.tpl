<?
echo modulo("banner_interior",array("apartado" => $apartado)); 
?>
<section id="contenido_principal">
    <!-- Page Content -->
    <div class="container">
        <div class="separa-40"></div>
        <!-- Heading Row -->
        <div class="row">
            <div class="col-md-8">
                <img class="img-responsive img-rounded" src="<?=parsea_imagen(t(@$portada["banner"][0],"urlPath"));?>" alt="">
            </div>
            <!-- /.col-md-8 -->
            <div class="col-md-4">
                <h1><?=t($portada,"title");?></h1>
                <p><?=t($portada,"texto_bienvenida");?></p>
                <a class="btn btn-primary btn-lg" href="<?=t($portada,"enlace_call_to_action");?>"><?=t_var("Saber más");?></a>
            </div>
            <!-- /.col-md-4 -->
        </div>
        <!-- /.row -->

        <hr>

        <!-- Call to Action Well -->
        <div class="row">
            <div class="col-lg-12">
                <div class="well text-center">
                    <?=t($portada,"mensaje");?>
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <!-- /.row -->

        <!-- Content Row -->
        <div class="row">
            <? if (@$portada["bloques"]){?>
            <? foreach($portada["bloques"] as $bloque):?>
            <div class="col-md-4">
                <div class="card">
                    <div class="imagen" style="background-image:url('<?=parsea_imagen(t($bloque,"urlPath"));?>')"></div>
                    <div class="card-body">
                        <h4 class="card-title"><?=t($bloque,"info1");?></h4>
                        <p class="card-text"><?=t($bloque,"info2");?></p>
                    </div>
                    <div class="card-footer">
                        <a href="<?=t($bloque,"info3");?>" class="btn btn-primary"><?=t_var("Saber más");?></a>
                    </div>
                </div>
            </div>
            <? endforeach;?>
            <? }?>
            <!-- /.col-md-4 -->
        </div>
        <!-- /.row -->

    </div>
    <!-- /.container -->
</section>

