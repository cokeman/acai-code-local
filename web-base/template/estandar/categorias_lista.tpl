<section>
    <div class="container">
        <div class="separa-40"></div>
        <? muestra_breadcrumb($apartado);?>
        <div class="separa-40"></div>
        <h1 class="titular"><?=@$apartado["titulo_alternativo"] ? t($apartado, "titulo_alternativo") : t($apartado, "name");?></h1>
        <div class="separa-10"></div>
        <?=t($apartado, "content");?>
        <div class="separa-30"></div>
        <ul class="lista-categorias">
            <? foreach ($categorias as $categoria):?>
            <li class="col-md-4 col-sm-6 col-xs-12">
                <?=modulo("categoria", array("categoria" => $categoria));?>
            </li>
            <? endforeach;?>
        </ul>
    </div>
</section>