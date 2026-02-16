<?
global $TABLE_PREFIX,$menu,$schema,$CURRENT_USER;

$configPlugin = [];
if (@$CURRENT_USER["domain"]["plugins"]["csv_plugin"]){
    foreach($CURRENT_USER["domain"]["plugins"]["csv_plugin"] as $cont => $arrayValue){
        $configPlugin[$arrayValue["campo"]] = $arrayValue["valor"];
    }
}
if (file_exists(__DIR__."/schema.ini.php")){
    if ($CURRENT_USER["isSuperAdmin"]){
        $schemaPlugin = loadINI(__DIR__."/schema.ini.php");
        foreach($schemaPlugin["config"] as $key => $value){
            if (!isset($configPlugin[$key])) $configPlugin[$key] = $value;
        }
    }
}

?>
<div class="modal fade" id="modal_seleccion_exportar" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-body">

				<form>
					<div class="form-group">
						<div class="relative">
							<span class="absolute top-0 right-0 p-4"><i class="fa fa-chevron-down"></i></span>
							<select name="exportar_todo" class="appearance-none py-2 px-4 w-full text-4xl pr-8" id="exportar_todo">
								<option value="1">Exportar solo lo visible/seleccionado.</option>
								<option value="2">Exportar toda la tabla.</option>
							</select>
						</div>
						<label for="recipient-name" class="text-2xl font-thin text-gray-600 col-form-label my-12 block text-center">Selecciona los campos que deseas visualizar en el documento</label>
						<div class="flex flex-wrap -mx-2">
							<?
							foreach ($schema as $key=>$value) {
								if (is_array($value) && !@$value["adminOnly"] && @$value["type"]!="separator" && @$value["type"]!="upload" && @$value["label"] && substr(@$value["label"],0,1)!="_" &&
								((!@$value["isSystemField"]) || (@$value["isSystemField"] && $key=="createdDate"))){
									?>
									<div class="w-full lg:w-1/2 ">
										<label for="html<?=$key;?>" class="flex flex-start precheck relative rounded-lg font-thin m-2 text-2xl bg-gray-200">
											<div class="relative w-12 flex-shrink-0 flex items-center justify-center">
												<input type="checkbox" id="html<?=$key;?>" value="<?=$key;?>" class="opacity-0 absolute supercheck" checked>
												<div class="w-8 h-8 bg-white rounded flex items-center justify-center"><i class="fa fa-check"></i></div>
											</div>
											<div class="w-full p-2"><?=$value["label"]?></div>
										</label>
									</div>
									<?
								}
							}
							?>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
				<button type="button" class="btn btn-primary btn-exportar">Exportar</button>
			</div>
		</div>
	</div>
</div>
<script>
	$("#list-head").append('<li><a href="javascript:void(0)" data-toggle="modal" data-target="#modal_seleccion_exportar" ><i class="fi fi-xml"></i>Exportar datos</a></li>');
	<? if (@$CURRENT_USER["domain"]){?>
		$(".navbar-nav-custom.pull-right").prepend('<li><button type="button" class="btn btn-black btn-crear btn-exportar-seleccion" data-toggle="modal" data-target="#modal_seleccion_exportar" style="margin:7px;"><i class="fi fi-xml"></i>&nbsp;&nbsp;Exportar Selección</button></li>');
	<? }?>
	$(".btn-exportar-seleccion").click(function(e){
		e.stopPropagation();
		e.preventDefault();
		e.stopImmediatePropagation();
		$("#modal_seleccion_exportar").modal("show");
	});
	$("#modal_seleccion_exportar .btn-exportar").click(function(e){
		var campos = [];
		$("#modal_seleccion_exportar [type='checkbox']").each(function(){
			if ($(this).prop("checked")) campos.push($(this).val());
		});

		var seleccion = [];
		$("#listTable .selectRecordCheckbox").each(function(){
			if ($(this).prop("checked")) seleccion.push($(this).val());
		});
		if (!seleccion.length){
			$("#listTable .selectRecordCheckbox").each(function(){
				seleccion.push($(this).val());
			});
		}

		var exportar_todo = document.querySelector('#exportar_todo').value;
		var rutaCMS = "<?=@$CURRENT_USER["domain"] ? "" : "/cms";?>";
		var url = rutaCMS + "/<?=$options["templatePath"];?>/export.php";
		
		$.post(url,{ exportCSVPlugin: 1 ,campos:campos, seleccion : seleccion, tabla : '<? echo $menu;?>', exportar_todo: exportar_todo},function(data){
			console.log(data);
			if (!data["result"]){
				alert("Error al exportar los datos");
			}else{

				location.replace(rutaCMS + "/<?=$options["templatePath"];?>/"+data["file"]+"?timestamp=<?=time();?>");
				$("#modal_seleccion_exportar").modal("hide");
				$.post(rutaCMS + "/<?=$options["templatePath"];?>/export.php",{ eliminarCsvAbierto: 1 },function(data){

				});

			}

		});
	});
</script>
<style>
	#modal_seleccion_exportar .precheck input[type="checkbox"]{margin:0px;}
	#modal_seleccion_exportar .supercheck + div>i{display:none;}
	#modal_seleccion_exportar .supercheck:checked + div{background-color:#718096;color:white;}
	#modal_seleccion_exportar .supercheck:checked + div>i{display:block;}
</style>