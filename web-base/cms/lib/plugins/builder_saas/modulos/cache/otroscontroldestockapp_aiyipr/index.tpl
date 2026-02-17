<? if (@$_REQUEST["clave"] && @$_REQUEST["clave"] == "Guayaba26") $_SESSION["user"] = "okStock";?>
<? if (@$_REQUEST["reiniciar"]) $_REQUEST["filter"] = null;?>

<div class="container2 mx-auto px-4 lg:px-0 ">
    
    <? if (@$_SESSION["user"] == "okStock"){?>
        
        
        <style type="text/css" media="all">
            .min-w-64{min-width:250px;width:250px;}
        </style>

        <div class="pt-10 pb-20  mx-auto">
            <div class="container mx-auto">
                <form action="" method="post" class="flex flex-col lg:flex-row justify-between text-xl">
                    <select name="filter[categoria]" id="categoria" class="appearance-none py-2 px-8 text-black border my-2 lg:my-0 lg:rounded-l-full focus:outline-none text-sm">
                        <? $categorias = mysql_query_fetch_all_assoc("SELECT * FROM cms_categorias_productos where depth < 3 ORDER BY globalOrder ASC,siblingOrder DESC");?>
                        <option value="">Seleccionar familia</option>
                        <? 
                        $categorias_aux = [];
                        $categorias_aux2 = [];
                        foreach($categorias as $categoria){ $categorias_aux[$categoria["id"]] = $categoria; }
                        foreach($categorias_aux as $categoria){ 
                            $ids = array_filter(explode(":",$categoria["lineage_id"]));
                            foreach($ids as $id){
                                if (!@$categorias_aux[$id] || !mysql_query_fetch_all_assoc("SELECT * FROM cms_productos where categoria_lineage_id like '%".$id."%' and visible=1 LIMIT 1")) {
                                    unset($categorias_aux[$categoria["id"]]);
                                }
                            }
                        }
                        foreach($categorias_aux as $categoria){
                            ?><option value="<?=$categoria["id"];?>" <?=@$_REQUEST["filter"]["categoria"] == @$categoria["id"] ? "SELECTED" : "";?>><?=@$categoria["breadcrumb"];?></option><?
                        }
                        ?>
                    </select>
                    <input name="filter[termino]" id="termino" type="text" class="w-full appearance-none py-2 text-black border px-4 focus:outline-none" value="<?=@$_REQUEST["filter"]["termino"];?>" placeholder="Filtrar por CB o Descripción">
                    <button type="button" onclick="document.getElementById('categoria').value = '';document.getElementById('termino').value = '';" class="py-2 px-8 bg-gray-300 text-gray-600 my-2 lg:my-0  border border-gray-500 focus:outline-none hover:bg-gray-400">Reiniciar</button>
                    <button class="py-2 px-8 bg-orange-500 text-white  lg:rounded-r-full border border-orange-600 focus:outline-none hover:bg-orange-600 my-2 lg:my-0">Buscar</button>
                </form>
            </div>
            <div class="containers mx-auto flex flex-col justify-between pt-6">
                <? 
                
                $articulos = [];
                if (@$_REQUEST["filter"]){
                    $where = "visible = '1'";
                    if (@$_REQUEST["filter"]["categoria"]) $where .= " AND (categoria_lineage_id LIKE '%".mysql_real_escape_string(@$_REQUEST["filter"]["categoria"])."%')";
                    if (@$_REQUEST["filter"]["termino"]) $where .= " AND (name like '%".mysql_real_escape_string($_REQUEST["filter"]["termino"])."%' or codigo like '%".mysql_real_escape_string($_REQUEST["filter"]["termino"])."%') ";
                    
                    $articulos = mysql_query_fetch_all_assoc("SELECT codigo,name,disponible_para_reservas FROM `cms_productos` where ".$where." ORDER BY codigo DESC limit 100");
                    
                }
                                            
                if (@$articulos){
                    $cbs = array_filter(array_map(function($articulo){ return $articulo["codigo"]; },$articulos));
                    $cbs2 = [];
                    foreach($articulos as $cb){
                        if (!@$cb["codigo"]) continue;
                        $cbs2[$cb["codigo"]] = $cb;
                    }
                    
                    $stocks = mysql_query_fetch_all_assoc("SELECT * FROM cms_stock WHERE codigobanana in ('".join("','",$cbs)."')");
                    
                    
                    foreach($stocks as $stock){
                        
                        if (!@$cbs2[$stock["codigobanana"]]) continue;
                        if (!@$cbs2[$stock["codigobanana"]]["data"]) $cbs2[$stock["codigobanana"]]["data"] = [];
                        if (!@$cbs2[$stock["codigobanana"]]["data"][$stock["tienda"]]) {
                            $cbs2[$stock["codigobanana"]]["data"][$stock["tienda"]] = $stock["stock"];
                        }else{
                            $cbs2[$stock["codigobanana"]]["data"][$stock["tienda"]] += $stock["stock"];
                        }
                        if (!@$cbs2[$stock["codigobanana"]]["datac"]) $cbs2[$stock["codigobanana"]]["datac"] = [];
                        if (!@$cbs2[$stock["codigobanana"]]["datac"][$stock["tienda"]]) {
                            $cbs2[$stock["codigobanana"]]["datac"][$stock["tienda"]] = $stock["ventas_no_facturadas"];
                        }else{
                            $cbs2[$stock["codigobanana"]]["datac"][$stock["tienda"]] += $stock["ventas_no_facturadas"];
                        }
                        
                    }
                    

                    if (@$cbs2){
                        $tiendas = [
                            "T1" => "Plaza España",
                            "T2" => "Triana",
                            "T3" => "Castillo",
                            "T4" => "La Laguna",
                            "T5" => "Safari",
                        ];
                        ?>
                        <h3 class="text-center text-gray-600 text-sm ">Ultima actualización <?=date("d-m-Y H:i:s",strtotime(@$stocks[0]["updatedDate"]));?> - Límite 100 productos</h3>
                        <div class="container mx-auto">
                            <div class="overflow-auto whitespace-nowrap my-6 border rounded-lg shadow-xl">
                                <table class="w-full ">
                                    <thead class="sticky top-0">
                                        <tr class="bg-gray-700 text-sm text-white">
                                            <td class="p-2 font-bold min-w-64 sticky left-0 bg-gray-700">Descripcion</td>
                                            <td class="p-2 font-bold text-center">Código</td>
                                            <? for ($i=1;$i<=5;$i++){?>
                                            <td  class="p-2 font-bold text-center"><?=str_replace(" ","&nbsp;",$tiendas["T".$i]);?></td>
                                            <? }?>
                                            <td class="p-2 font-bold text-center">Total</td>
                                            <td class="p-2 font-bold text-center">Comprometido</td>
                                            <td class="p-2 font-bold text-center">Disponible</td>
                                            <td class="p-2 font-bold text-center">Disp. para Reservar</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?
                                        $cont = 0;
                                        foreach($cbs2 as $cb){
                                            $suma = 0;
                                            $sumac = 0;
                                            ?>
                                            <tr class="text-sm text-gray-600 border-b  <?=@$cont%2 != 0 ? "bg-gray-100" : "bg-white";?>">
                                                <td class="p-2 border min-w-64 text-white font-bold text-xs sticky left-0 border-r z-20 <?=@$cont%2 != 0 ? "bg-gray-700" : "bg-gray-600";?>"><?=$cb["name"];?></td>
                                                <td class="p-2 border text-center" style="width:150px">
                                                    <div class="block flex flex-shrink-0 justify-center">
                                                        <span><?=$cb["codigo"];?> </span>
                                                        <a href="https://tienda.bananacomputer.com/?searchCB=<?=$cb["codigo"];?>" target="_blank">
                                                            <svg class="inline-block ml-2 fill-current w-6 h-6 text-orange-500 svg-icon hover:text-black" viewBox="0 0 20 20">
                                    							<path d="M12.323,2.398c-0.741-0.312-1.523-0.472-2.319-0.472c-2.394,0-4.544,1.423-5.476,3.625C3.907,7.013,3.896,8.629,4.49,10.102c0.528,1.304,1.494,2.333,2.72,2.99L5.467,17.33c-0.113,0.273,0.018,0.59,0.292,0.703c0.068,0.027,0.137,0.041,0.206,0.041c0.211,0,0.412-0.127,0.498-0.334l1.74-4.23c0.583,0.186,1.18,0.309,1.795,0.309c2.394,0,4.544-1.424,5.478-3.629C16.755,7.173,15.342,3.68,12.323,2.398z M14.488,9.77c-0.769,1.807-2.529,2.975-4.49,2.975c-0.651,0-1.291-0.131-1.897-0.387c-0.002-0.004-0.002-0.004-0.002-0.004c-0.003,0-0.003,0-0.003,0s0,0,0,0c-1.195-0.508-2.121-1.452-2.607-2.656c-0.489-1.205-0.477-2.53,0.03-3.727c0.764-1.805,2.525-2.969,4.487-2.969c0.651,0,1.292,0.129,1.898,0.386C14.374,4.438,15.533,7.3,14.488,9.77z"></path>
                                    						</svg>
                                                        </a>
                                                    </div>
                                                </td>
                                                <? for ($i=1;$i<=5;$i++){?>
                                                <? $total = intval(@$cb["data"]["T".$i]);?>
                                                <? $totalc = intval(@$cb["datac"]["T".$i]);?>
                                                <? $clase = intval(@$total) > 3 ? "text-green-500" : "text-gray-500";?>
                                                <? if (intval(@$total) <= 2) $clase = "text-red-600";?>
                
                                                <? $suma+=$total;?>
                                                <? $sumac+=$totalc;?>
                                                <td class="p-2 border text-center <?=$clase;?>"><?=$total;?></td>
                                                <? }?>
                                                
                                                <?
                                                $cont++;
                                                $clase = intval(@$suma - $sumac) > 3 ? "text-green-500" : "text-gray-500";
                                                if (intval(@$suma) <= 2) $clase = "text-red-600";
                                                ?>
                                                <td  class="p-2 border text-center <?=$clase;?>"><?=$suma;?></tdv>
                                                <td  class="p-2 border text-center <?=$clase;?>"><?=$sumac;?></td>
                                                <td  class="p-2 border text-center <?=$clase;?>"><?=$suma - $sumac;?></td>
                                                <td  class="p-2 border text-center "><?=$cb["disponible_para_reservas"];?></td>
                                                
                                            </tr>
                                            <?
                                        }
                                        ?>
                                    </tbody>
                                    
                                </table>
                            </div>
                        </div>
                        <?
                        
                        

                    }else{
                        ?><h3 class="text-center text-gray-600 text-xl">No se encuentran productos</h3><?    
                    }
                }else if (@$_REQUEST["filter"]){
                    ?><h3 class="text-center text-gray-600 text-xl">No se encuentran productos</h3><?
                }
                ?>
            </div>
            
            
        </div>
    <? }else{?>
        <div class="pb-20 max-w-xl mx-auto">
            <div class="">
                <form action="" method="post" class="flex justify-between text-xl">
                    <input name="clave" type="password" class="w-full appearance-none py-2 text-black border px-4 rounded-l-full focus:outline-none" value="" placeholder="Clave">
                    <button class="py-2 px-8 bg-orange-500 text-white rounded-r-full border border-orange-600 focus:outline-none hover:bg-orange-600">Entrar</button>
                </form>
            </div>
        </div>
    <? }?>
    
</div>
