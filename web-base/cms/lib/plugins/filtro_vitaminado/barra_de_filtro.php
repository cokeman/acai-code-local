<?
global $schema, $tableName;
$avoid_fields = ['dragSortOrder'];
$valor_operador_por_defecto = '~';
if (!isset($GLOBALS["advanced_filter"])) return false;
?>
<div class="clearfix"></div>
<div class="filtro_vitaminado">
    <? foreach ($GLOBALS['advanced_filter'] as $key => $filter): ?>
    <div>
        <div class="col-xs-12 filtro_vitaminado_container <?=($key == 0)?'primero':''?>">
            <!-- <span class="__check"></span> -->
            <!-- <span class="__mover"></span> -->
                <? foreach (explode(",", $schema['listPageFields']) as $campo): ?>
                    <?
                    $campo = trim($campo);
                    if(in_array($campo, $avoid_fields)) continue;
                    
                    if (@$schema[$campo]["type"] == "upload") continue;
                    ?>
                    <span class="__filter_for_<?=$campo?>">
                        <input type="hidden" name="advanced_filter[<?=$key?>][<?=$campo?>][operator]" value="<?=@$filter[$campo]['operator']?:$valor_operador_por_defecto?>">
                        <label for=""><?=$schema[$campo]['label']?></label>
                        <?
                        $val = @$filter[$campo]['value'];
                        //Elimnar los ->  data-num="'.$var["num"].'"
                        switch (@$schema[$campo]['type']) {
                            case "list":
                                // if ($schema[$campo]["listType"] == "pulldownMulti") continue;
                                $options = getListOptionsFromSchema($schema[$campo]);
                                echo '<select class="appearance-none form-control select2-container filter_list" name="advanced_filter['.$key.']['.$campo.'][value]">';
                                echo '<option value="-_@ESTO_ES_UN_VALOR_IMPOSIBLE@_-">Seleccionar...</option>';
                                foreach ($options as $option):
                                    $selected = isset($filter[$campo]['value']) && $option[0] == $val ? 'SELECTED' : '';
                                    echo '<option value="' . $option[0] . '" '.$selected.'>' . $option[1] . '</option>';
                                endforeach;
                                echo '</select>';
                            break;
                            // case "date":
                            //     $fecha = preg_replace("/([\d]{1,2}):([\d]{1,2})([:]?)([\d]{1,2})/", "", $val);
                            //     $hora = preg_replace("/([\d]{4})-([\d]{2})-([\d]{2})/", "", $val);
                            //     echo '<span>
                            //         <input type="text" class="form-control filter_date" value="' . htmlspecialchars($fecha) . '" data-menu="'.$menu.'" data-field="'.$campo.'" placeholder="yyyy-mm-dd" data-value="'.htmlspecialchars($fecha).'">
                            //         <input type="text" class="form-control filter_time" value="' . htmlspecialchars($hora) . '" data-menu="'.$menu.'" data-field="'.$campo.'" placeholder="hh:ii:ss" data-value="'.htmlspecialchars($hora).'">
                            //     </span>
                            //     ';
                            // break;
                            default:
                                ?>
                                <input type="search" class="form-control __filtrar_vitaminado" placeholder="Buscar <?=$campo?>..." name="advanced_filter[<?=$key?>][<?=$campo?>][value]" value="<?=@$filter[$campo]['value']?>">
                                <?
                                break;
                        }
                        ?>
                        <a class="btn btn-xs btn-default form-control wrapper-flex filtro_vitaminado__operador" href="javascript:void(0)" onclick="FiltradoVitaminado.cambiar_tipo(event)"><?=@$filter[$campo]['operator']?:$valor_operador_por_defecto?></a>
                    </span>
                <? endforeach ?>
            <span class="__aciones">
                <a class="btn btn-xs btn-default form-control wrapper-flex filtro_vitaminado__nuevo_filtro" href="javascript:void(0)" onclick="FiltradoVitaminado.nuevo_filtro(event)"><i class="fa fa-plus"></i></a>
                <a class="btn btn-xs btn-default form-control wrapper-flex filtro_vitaminado__eliminar_filtro" href="javascript:void(0)" onclick="FiltradoVitaminado.eliminar_filtro(event)"><i class="fa fa-close"></i></a>
            </span>
        </div>
    </div>
    <? endforeach ?>
    <? global $listDetails;?>
    <a class="col-xs-12 btn btn-xs btn-default filtro_vitaminado__submit" href="javascript:void(0)" onclick="FiltradoVitaminado.submit()">Aplicar filtro avanzado</a>
    <div class="text-center"><? if (@$listDetails && @$listDetails["totalMatches"]) echo "&nbsp;<br>Resultado : ".$listDetails["totalMatches"]." registros";?></div>
    
    <div class="clearfix"></div>
</div>
<?
// echo "<pre style='display: block;position: absolute;z-index: 100000;bottom: 0px;max-height: 50%;overflow: scroll;resize:both;'>"; var_dump($schema); echo "</pre>";
