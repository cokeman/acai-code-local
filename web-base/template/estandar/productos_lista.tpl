<div id="contenido_principal">
    <div class="separa-40"></div>
    <div class="container">
        <? if (@$categoria) {?>
            <? muestra_breadcrumb($categoria, array($apartado));?>
        <? }else{?>
            <? muestra_breadcrumb($apartado);?>
        <? }?>
        <div class="separa-20"></div>
        <? if (@$categoria["subcategorias"]) {?>
        <div class="col-md-3">
            <?=modulo("sidebar_categorias", array("categoria" => $categoria));?>
        </div>
        <? }?>
        <div class="<?=@$categoria["subcategorias"] ? 'col-md-9' : 'col-md-12';?>">
            <? if (!@$categoria["subcategorias"]) {?>
            <h1 class="titular"><?=t($categoria, "name");?></h1>
            <? }?>
            <? if (@$categoria["descripcion"]) {?>
                <p class="subtitulo"><?=t($categoria, "descripcion");?></p>
            <? }?>
            <? if (@$productos) {?>
            <div class="col-md-12">
                <?=modulo("select-order-by", array("form" => "formOrderBy"));?>
            </div>
            <ul class="lista-productos">
                <? foreach ($productos as $producto):?>
                <li class="<?=@$categoria["subcategorias"] ? 'col-md-4 col-sm-6 col-xs-12' : 'col-md-3 col-sm-6 col-xs-12';?>">
                    <?=modulo("bloque_producto", array("producto" => $producto));?>
                </li>
                <? endforeach;?>
            </ul>
                <? if (@$categoria["content"]) {?>
                <div class="separa-20"></div>
                <div class="col-md-12">
                    <?=t($categoria, "content");?>
                </div>
                <? }?>
            <? }else {?>
            <?=modulo("sin_resultados");?>
            <? }?>
        </div>
        <div class="separa-40"></div>
    </div>
</div>

<? if (@$productos) {?>
<script>
    document.querySelector('#contenido_principal select[name=order]').addEventListener('change', function() {
        document.getElementById('formOrderBy').submit();
    });
</script>
<? }?>