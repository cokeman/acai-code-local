<?
$valid_operators = [];
// $valid_operators_proto = ['=','!=','&gt;','&lt;','&gt;=','&lt;=','~'];
$valid_operators_proto = ['=','!=','~'];
$max_key = count($valid_operators_proto);
foreach ($valid_operators_proto as $key => $value) {
    if(($key + 1) == $max_key)
        $valid_operators[$value] = $valid_operators_proto[0];
    else
        $valid_operators[$value] = $valid_operators_proto[$key + 1];
}
?>
<script>
    window.addEventListener('load', function() {
        var FiltradoVitaminado = {};
        var valid_operators = JSON.parse('<?=json_encode($valid_operators)?>');
        if (!$("#example-datatable_wrapper>div.row").length) return;
        document.querySelector('#example-datatable_wrapper>div.row').innerHTML += `<? include 'barra_de_filtro.php'; ?>`;
        FiltradoVitaminado.escapeRegExp = function(str) { return str.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1"); }
        FiltradoVitaminado.replaceAll = function(str, find, replace) { return str.replace(new RegExp(FiltradoVitaminado.escapeRegExp(find), 'g'), replace); }
        FiltradoVitaminado.replaceAllAdvanced = function(str, find, replace) { return str.replace(new RegExp(find, 'g'), replace); }
        FiltradoVitaminado.submit = function() {$('#page-content #formulario_buscar_aux').submit();};
        FiltradoVitaminado.recargar_eventos = function() {
            init();
            $(".__filtrar_vitaminado").keypress(function (e) {
                if (e.which == 13) {
                    $('#page-content #formulario_buscar_aux').submit();
                }
            });
            var dateFilterList = document.querySelectorAll('.filter_date');
            var timeFilterList = document.querySelectorAll('.filter_time');
            $(dateFilterList).each(function() {
                $(this).datepicker({
                    weekStart: 1,
                    format: 'yyyy-mm-dd'
                });
            });
            // $('.select-select2').each(el => {
            //     try {
            //         $(el).select2('destroy');
            //         $(el).select2();
            //     } catch (err) {
            //         console.warn(err);
            //     }
            // });
            try {
                //$('.select-select2').select2();
            } catch (err) {
                console.warn(err);
            }
        }
        FiltradoVitaminado.cambiar_tipo = function(event) {
            var item = event.target.innerHTML;
            item = valid_operators[event.target.innerHTML];
            if(item == '' || typeof item == 'undefined')
                item = '~';
            event.target.parentElement.firstElementChild.value = item;
            event.target.innerHTML = item;
        }
        FiltradoVitaminado.arreglar_indices = function() {
            var elementos_padre = document.querySelectorAll('.filtro_vitaminado_container');
            for (var i = 0; i < elementos_padre.length; i++) {
                var elementos_hijo = elementos_padre[i].querySelectorAll('[name]');
                for (var j = 0; j < elementos_hijo.length; j++) {
                    elementos_hijo[j].name = FiltradoVitaminado.replaceAllAdvanced(elementos_hijo[j].name, /advanced_filter\[[0-9]]/g,'advanced_filter['+(i)+']');
                }
            }
        }
        FiltradoVitaminado.nuevo_filtro = function(event) {
            // var cloned = event.target.parentElement.parentElement.outerHTML;
            // cloned = FiltradoVitaminado.replaceAll(cloned, 'primero','');
            // event.target.parentElement.parentElement.parentElement.innerHTML += (cloned);
            var cloned = event.target.parentElement.parentElement.cloneNode(true);
            cloned.classList.remove('primero');
            /*var selects = cloned.querySelectorAll('.select2-container_aux');
            for (var i = 0; i < selects.length; i++) {
                try {
                    selects[i].remove();
                } catch (err) {
                    console.warn(err);
                }
            }
            var selects = cloned.querySelectorAll('.select-select2_aux');
            for (var i = 0; i < selects.length; i++) {
                try {
                    //$(selects[i]).select2();
                } catch (err) {
                    console.warn(err);
                }
            }*/
            event.target.parentElement.parentElement.parentElement.append(cloned);
            FiltradoVitaminado.arreglar_indices();
            FiltradoVitaminado.recargar_eventos();
        }
        FiltradoVitaminado.eliminar_filtro = function(event) {
            event.target.parentElement.parentElement.remove();
            FiltradoVitaminado.arreglar_indices();
            FiltradoVitaminado.recargar_eventos();
        }
        FiltradoVitaminado.recargar_eventos();
        window.FiltradoVitaminado = FiltradoVitaminado;
    });
</script>
<style>
	#example-datatable_filter #buscador{ display:none; }
	#example-datatable_filter #buscador + .input-group-addon{ display:none; }
	#example-datatable_filter #buscador + .input-group-addon + .reiniciar{ border:solid 1px; border-radius:3px; color:#666; border-color:#dbe1e8; padding:9px 10px;}
    .filtro_vitaminado {border: 1px dashed #f3f5f7; padding: 20px 0px; margin: 15px; background: #fff;}
    .filtro_vitaminado_container {display: flex; justify-content: space-between;}
    .filtro_vitaminado_container > span {width: 100%; padding: 5px;}
    .filtro_vitaminado_container .__aciones {width: auto; display: flex;}
    .filtro_vitaminado_container label {position: absolute; top: -20px; display: none;}
    .filtro_vitaminado_container.primero {margin-top: 20px;}
    .filtro_vitaminado_container.primero label {display: inline-block;}
    .filtro_vitaminado_container input, .filtro_vitaminado_container .select2-container {width: calc(100% - 34px)!important;}
    .filtro_vitaminado_container .btn {padding: 7px; min-width: 34px;}
    .filtro_vitaminado_container .btn .fa {pointer-events: none;}
    .filtro_vitaminado_container .btn.wrapper-flex {display: inline-flex!important; justify-content: center; align-items: center;}
    .filtro_vitaminado_container .filtro_vitaminado__operador, .filtro_vitaminado_container .filter_time {margin-left: -5px;}
    .filtro_vitaminado_container .filter_list {display: inline-block; width: auto;}
    .filtro_vitaminado_container .filter_list > a {border-radius: 0;}
    .filtro_vitaminado_container .select2-container .select2-choice .select2-arrow {height: 80%;}
    .filtro_vitaminado_container.primero .filtro_vitaminado__eliminar_filtro {display: none!important; pointer-events: none;}
    .filtro_vitaminado_container.primero .filtro_vitaminado__nuevo_filtro {width: 68px;}
    .filtro_vitaminado__submit {margin: 5px 20px; width: calc(100% - 40px); padding: 10px;}
    .separa-10 {height: 10px; clear: both;}
</style>