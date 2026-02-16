<?=modulo("banner_interior", array("apartado" => $apartado));?>
<section id="contenido_principal">
    <div class="container">
        <div class="separa-40"></div>
        <? muestra_breadcrumb($apartado);?>
        <div class="separa-40"></div>
        <h1 class="titular"><?=@$apartado["titulo_alternativo"] ? t($apartado, "titulo_alternativo") : t($apartado, "name");?></h1>
        <?=t($apartado, "content");?>
        <ul class="lista-productos"></ul>
        <div class="separa-40"></div>
    </div>
</section>


<script>
    window.onload = function() {
        Favorites.getProducts()
        .then(function(json) {
            var lista = document.querySelector('.lista-productos');
            lista.innerHTML = json.html;
            Favorites.bindNodes();
            Cesta.bindNodes();
        });
    };
</script>
