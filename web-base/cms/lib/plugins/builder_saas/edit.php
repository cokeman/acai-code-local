<?php
global $menu, $tableName, $schema;
require_once "lib/menus/default/edit_functions.php";
$tieneDefaults = false;

?>

	<div id="page-content">
		
		<form method="post" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" autocomplete="off" >
			<? if (@$_REQUEST["linked"]){?><input type="hidden" name="linked" id="linked" value="<?php echo htmlspecialchars($_REQUEST["linked"]) ?>" /><?}?>
			<input type="hidden" name="menu" id="menu" value="<?php echo htmlspecialchars($menu) ?>" />
			<input type="hidden" name="_defaultAction" value="save" />
			<input type="hidden" name="num"           id="num"           value="<?php echo htmlspecialchars(@$_REQUEST['num']) ?>" />
			<input type="hidden" name="preSaveTempId" id="preSaveTempId" value="<?php echo htmlspecialchars($preSaveTempId) ?>" />
		
			<div class="row">
				<div class="col-md-12">
					
					<div class="block">
						<div class="block-title">

							<? if (@$schema['menuDesc']){?>
							<h2 style="text-align:center;display:block;color:#aaa;"><?php echo @$schema['menuDesc'] ?></h2>
							<? }else{ ?>
							<h2>&nbsp;</h2>
							<?}?>

						</div>
						<div>

							<div class="form-horizontal form-bordered">
								<fieldset>
									<?php $record = showFields(); ?>
								</fieldset>
								<div class="form-group form-actions" style="text-align:right;">
									<input type="submit" name="action=save" style="margin:0 7px;" value="<?php _e('Save') ?>" class="btn btn-success"  />
									<input type="button" name="cancel" style="margin:0 7px;" value="<?php _e('Cancel') ?>"  class="btn btn-warning"  onclick="window.location='?menu=<?php print urlencode($menu) ?>'" />
								</div>
							</div>

						</div>
					</div>
				</div>
			</div>
		</form>
		
		<script>
			
			// ACTUALIZA WYSYWIG
			$("form").submit(function(e){
				$(".textarea-codigo").each(function(){
					$(this).val(Base64.encode($(this).val()));

				});

				for(var instanceName in CKEDITOR.instances) {
					var datos = CKEDITOR.instances[instanceName].getData();
					var nombre = instanceName;
					$("textarea[name="+nombre+"]").val(datos);

				}

				$(".textarea-editor").each(function(){
					var datos = $(this).parent().find("iframe").contents().find('.wysihtml5-editor').html();
					$("textarea[name="+$(this).attr("for")+"]").val(datos);

				});
			});

			function actualizaCkeditor(name,data){
				var datos = CKEDITOR.instances[name].setData(data.html);
				//console.log(JSON.parse(data.styles));
			}

			<?
	$idiomas = 0;
										   foreach ($SETTINGS["idiomas"] as $idioma):
										   if ($idioma!="") $idiomas+=1;
										   endforeach;

										   if ($idiomas>1){

			?>
			function comprueba_idiomas(){
				$('iframe').each(function(){
					$(this).attr("src",$(this).attr("src"));
				});
				$("[data-translate]").each(function(){
					//if (!$(this).parent().hasClass('premundo')) $(this).wrap("<div class='premundo' style='position:relative'></div>");
					if (!$(this).parent().hasClass('premundo')) $(this).parent().addClass("premundo").css("position","relative");

					datos = {
						fieldname     : $(this).attr("data-translate-field"),
						tablename     : '<?=$_REQUEST["menu"];?>',
						recordnum     : '<?=@$_REQUEST["num"];?>',
						presavetempid : '<?=@$preSaveTempId;?>',
						type          : $(this).attr("data-translate-type")
					};



					$.ajax({
						type:"post",
						url:"lib/ajax.php",
						data:{comprueba_idiomas:1,data:datos},
						success:function(data){

							var modifyUrl = "?menu=<?=$_REQUEST["menu"];?>"
							+ "&action=translateModify"
							+ "&fieldName=" + data["fieldname"]
							+ "&num=" + data["recordnum"]
							+ "&preSaveTempId=" + data["presavetempid"]
							+ "&type=" + data["type"]
							+ "&TB_iframe=true&width=900&height=600&modal=true";

							if (data["resultado"]=="ok"){
								var posicion = "top:0px;right:20px;";
								if (data["type"]=="wysiwyg") posicion="top:17px;right:20px;";
								if (data["type"]!="textfield") posicion="top:20px;right:20px;";
								if (data["type"]=="codigo") posicion="top:3px;right:3px;";
								if (data["type"]=="multitext") posicion="top:auto;bottom:7px;right:20px;";
								if (data["type"]=="multitextv2") posicion="top:auto;top:30px;right:35px;";
								$("[name="+data["fieldname"]+"]").before(
									"<div class='mundo' style='position:absolute;"+posicion+"font-size:24px;z-index:100;'>"+
									"<a href='"+modifyUrl+"' class='thickbox'>"+
									"<i class='fa fa-globe'></i>"+
									"</a>"+
									"</div>"
								);

							}else{
								var posicion = "top:0px;right:20px;";
								if (data["type"]=="wysiwyg") posicion="top:17px;right:20px;";
								if (data["type"]=="multitext") posicion="top:auto;bottom:7px;right:20px;";
								if (data["type"]=="codigo") posicion="top:3px;right:3px;";
								if (data["type"]=="multitextv2") posicion="top:auto;top:30px;right:35px;";
								$("[name="+data["fieldname"]+"]").before(
									"<div class='mundo' style='position:absolute;"+posicion+"font-size:24px;z-index:100;'>"+
									"<a href='"+modifyUrl+"' class='thickbox' style='color:#aaa'>"+
									"<i class='fa fa-globe'></i>"+
									"</a>"+
									"</div>"
								);     


							}
							tb_init('a.thickbox, area.thickbox, input.thickbox');//pass where to apply thickbox      
						}
					});

				});
			}

			//comprueba_idiomas();

			<?
			}
			?>


		</script>
		
	</div>
