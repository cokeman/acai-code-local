<?
echo modulo("banner_interior",array("apartado" => $apartado)); 
?>
<section id="contenido_principal">

    <div class="separa-40"></div>
    <!-- Page Content -->
    <div class="container">

      <!-- Jumbotron Header -->
      <header class="jumbotron my-4">
        <h1 class="display-3"><?=t($portada,"title");?></h1>
        <p class="lead"><?=t($portada,"banner_subtitulo");?></p>
        <a href="<?=t($portada,"banner_enlace");?>" class="btn btn-primary btn-lg"><?=t_var("Saber más");?></a>
      </header>
        <?=t($portada,"content");?>
        <div class="separa-20"></div>
      <!-- Page Features -->
      <div class="row text-center">
        <? if (@$portada["bloques"]){?>
        <? foreach($portada["bloques"] as $bloque):?>
        <div class="col-lg-3 col-md-6 mb-4">
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
      </div>
      <!-- /.row -->

    </div>
    <!-- /.container -->

</section>
