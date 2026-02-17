<div class="newWrapperContainer">
    <div>
        <!-- Nav tabs -->
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation"  style="display:none"><a href="#mismodulos" aria-controls="mismodulos" role="tab" data-toggle="tab"><i class="fa fa-user-o"></i>&nbsp;&nbsp;&nbsp;Mis módulos</a></li>
            
            <? if (@$menu !== "mail_marketing"){?>
                <? if (@$configPlugin["acceso_a_plugins_especiales"]){?><li role="presentation"><a href="#generales" aria-controls="generales" role="tab" data-toggle="tab"><i class="fa fa-globe"></i>&nbsp;&nbsp;&nbsp;Generales</a></li><?}?>
                <li role="presentation" class="active"><a href="#editables" aria-controls="editables" role="tab" data-toggle="tab"><i class="fa fa-code"></i>&nbsp;&nbsp;&nbsp;Mis módulos</a></li>
            <? }?>
            
            <? if (@$menu == "mail_marketing"){?><li role="presentation" class="active"><a href="#mjml" aria-controls="mjml" role="tab" data-toggle="tab"><i class="fa fa-envelope"></i>&nbsp;&nbsp;&nbsp;Mail</a></li><?}?>
            
            <? /*if (@$CURRENT_USER["isAdmin"] && !isset($_SESSION["acaiCodeReferer"])){?><li><a href="javascript:void(0);" onclick="crearModulo();"><i class="fa fa-code"></i>&nbsp;&nbsp;&nbsp;Crear módulo</a></li><? }*/?>
            
            <? if (@$menu !== "mail_marketing"){?>
                <? if (@$configPlugin["acceso_a_plugins_especiales"]){?><li role="presentation"><a href="#especiales" aria-controls="especiales" role="tab" data-toggle="tab"><i class="fa fa-diamond"></i>&nbsp;&nbsp;&nbsp;Especiales</a></li><?}?>
            <? }?>

        </ul>

        <!-- Tab panes -->
        <div class="tab-content">
            <div role="tabpanel" class="tab-pane " id="mismodulos">
                <!--<div class="separa-30"></div>
                <p class="text-center">Galería de módulos instalados en tu web. Arrastra el módulo deseado a la sección izquierda</p>
                <div class="separa-40"></div>
                <div class="spinner"><div class="cube1"></div><div class="cube2"></div></div>
                <ul class="list-modules drag-sort-enable-secondary mismodulos"></ul>
                <div class="separa-40"></div>
                <div class="separa-40"></div>-->
            </div>
            <div role="tabpanel" class="tab-pane" id="generales">
                <div class="separa-30"></div>
                <p class="text-center">Galería de módulos generales.</p>
                <div class="separa-40"></div>
                <div class="spinner"><div class="cube1"></div><div class="cube2"></div></div>
                <ul class="list-modules drag-sort-enable-secondary modulosgenerales"></ul>
                <div class="separa-40"></div>
                <div class="separa-40"></div>
            </div>
            <div role="tabpanel" class="tab-pane <? if (@$menu !== "mail_marketing"){?>active<?}?>" id="editables">
                <div class="separa-30"></div>
                <p class="text-center">Galería de módulos instalados en tu web. Arrastra el módulo deseado a la sección izquierda</p>
                <div class="separa-40"></div>
                <? /* ?>
                    <div class="flex justify-between items-center w-full mx-auto max-w-6xl  mb-8 text-2xl">
                        <div class="text-center w-full border border-gray-700 py-4 px-8 text-white  rounded-full flex justify-between items-center bg-black shadow-lg">
                            <span class="block tw flex-shrink-0 text-gray-500 px-4">Selecciona Web : </span>
                            <select class="appearance-none  bg-transparent  w-full" id="newModuleWebSelector" onchange="activateTab('editables',this.value);">
                                <option value="<?=$CURRENT_USER["domain"]["num"];?>" class="text-black" ><?=$CURRENT_USER["domain"]["domain"];?></option>
                                <option value="" disabled="true" class="text-black" ></option>
                                <? $domains = _ordenarArray($CURRENT_USER["domains"],"domain");?>
                                <? foreach($domains as $domain):?>
                                <? if ($domain["num"] == $CURRENT_USER["domain"]["num"]) continue;?>
                                <option value="<?=$domain["num"];?>" class="text-black" <?=$domain["num"] == $CURRENT_USER["domain"]["num"] ? "SELECTED data-actual='true' " : "";?> ><?=$domain["domain"];?></option>
                                <? endforeach;?>
                            </select>
                            <i class="fa fa-chevron-down"></i>
                        </div>
                    </div>
                <? */ ?>
                
                <div class="spinner"><div class="cube1"></div><div class="cube2"></div></div>
                <ul class="list-modules drag-sort-enable-secondary moduloseditables"></ul>
                <div class="separa-40"></div>
                <div class="separa-40"></div>
            </div>
            <div role="tabpanel" class="tab-pane <? if (@$menu == "mail_marketing"){?>active<?}?>" id="mjml">
                <div class="separa-30"></div>
                <p class="text-center">Galería de módulos de email marketing</p>
                <div class="separa-40"></div>
                <div class="spinner"><div class="cube1"></div><div class="cube2"></div></div>
                <ul class="list-modules drag-sort-enable-secondary moduloseditables"></ul>
                <div class="separa-40"></div>
                <div class="separa-40"></div>
            </div>
            
            <? if (@$configPlugin["acceso_a_plugins_especiales"]){?>
            <div role="tabpanel" class="tab-pane" id="especiales">
                <div class="separa-30"></div>
                <p class="text-center">Galería de módulos especiales</p>

                <div class="separa-40"></div>
                <div class="spinner"><div class="cube1"></div><div class="cube2"></div></div>
                <ul class="list-modules drag-sort-enable-secondary special"></ul>

                <div class="separa-40"></div>
                <div class="separa-40"></div>
            </div>
            <? }?>
            
        </div>

    </div>
</div>