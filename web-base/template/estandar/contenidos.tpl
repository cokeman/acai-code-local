<?
if ($contenido["banner"]){
    echo modulo("banner_interior",array("apartado" => $contenido));	
}else{
    echo modulo("banner_interior",array("apartado" => $apartado));	
}
?>
<section id="contenido_principal">
    <div class="container">
        <div class="row">
            <div class="separa-40"></div>
            <? muestra_breadcrumb($contenido);?>
            <div class="separa-40"></div>
            <div class="col-md-3">
                <ul class="nav nav-pills nav-stacked servicios">
                    <? muestraservicios($apartado,false,$contenido["num"]);?>
                </ul>
                <div class="separa-40"></div>
                <div class="separa-40"></div>
            </div>
            <div class="col-md-9">
                <h1 class="titular"><?=@$contenido["titulo_alternativo"] ? t($contenido, "titulo_alternativo") : t($contenido,"name");?></h1>
                <div class="separa-20"></div>
                <?=t($contenido,"content");?>
                <div class="separa-30"></div>
                <? if ($contenido["galeria_de_fotos"]) {?>
                    <?=modulo("galeria_interno",array("galeria"	=>	$contenido["galeria_de_fotos"],"apartado" => $apartado,"interior_contenidos" => true,"col" => "col-md-3"));?>
                    <div class="separa-30"></div>
                <? }?>
                <? if ($contenido["archivos_adjuntos"]) {?>
                    <?=modulo("adjuntos_interno",array("adjuntos"	=>	$contenido["archivos_adjuntos"],"apartado" => $apartado,"interior_contenidos" => true));?>
                    <div class="separa-30"></div>
                <? }?>
            </div>
        </div>
    </div>
</section>