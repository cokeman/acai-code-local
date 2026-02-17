<?php

global $menu, $tableName,$TABLE_PREFIX;

// error checking
if (!array_key_exists('menu', $_REQUEST))           { die("no 'menu' value specified!"); }
if (!@$_REQUEST['fieldName'])                       { die("no 'fieldName' value specified!"); }
if (!@$_REQUEST['num'] && !@$_REQUEST['preSaveTempId']) { die("No record 'num' or 'preSaveTempId' was specified!"); }
if (@$_REQUEST["infoLabels"]) $infoLabels = json_decode(@$_REQUEST["infoLabels"],true); else $infoLabels = ["info1","info2","info3","info4","info5"];
$schemaFields = json_decode(@$_REQUEST["schema"],true);

?>
<!DOCTYPE html>
<html xmlns="https://www.w3.org/1999/xhtml">
	<head>
		<title></title>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />

		<link href="/css/font-awesome-420.min.css" rel="stylesheet">

		<link rel="stylesheet" type="text/css" href="css/ui.css" />
		<script src="/js/lazysizes.min.js"></script>
		<style type="text/css">
			body,html{margin:0px;width:100%;height:100%;}
			body  { background-color: #FFFFFF; margin: 0px; }
			body.hover{background-color: #ddd;}
			.label { width: 125px; float: left; };
			.listheader1{border-radius: 0px;}
			#sortableRows{border:none;}
			form{z-index:1;}
			.preview{background-color:#f3f3f3;margin:0 10px;padding:3px;}
			.preview img{height:65px;width:65px;object-fit: contain;}
			.sinimagen{text-align:center;height:90px;display:flex;align-items: center;justify-content: center;color:#888;}
			.drag{position: absolute;top:0px;left:0px;width:100%;height:100%;border:solid 2px red;z-index:0;}
			#status{display:none;}
		</style>
		<script>
			var infoLabels = <?=json_encode($infoLabels);?>;
		</script>
	</head>
	<body id="drop1" style="height:auto;min-height:90px;">
		<DIV id="status"></DIV>
		<form method="post" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" autocomplete="off">
			<input type="hidden" name="tableName" id="tableName" value="<?php echo htmlspecialchars($tableName) ?>" />
			<input type="hidden" name="fieldName" id="fieldName" value="<?php echo htmlspecialchars(@$_REQUEST['fieldName']) ?>" />
			<input type="hidden" name="num"  id="num" value="<?php echo htmlspecialchars(@$_REQUEST['num']) ?>" />
			<input type="hidden" name="preSaveTempId"  id="preSaveTempId" value="<?php echo htmlspecialchars(@$_REQUEST['preSaveTempId']) ?>" />
			<input type="hidden" name="idTemp"  id="idTemp" value="<?php echo htmlspecialchars(@$_REQUEST['idTemp']) ?>" />

			<div id="sortableRows">
				<?php
				//
				$fieldSchema = $schema[$_REQUEST['fieldName']];

				// get results
				$uploadCount = 0;
				$result = getUploads($tableName, $_REQUEST['fieldName'], @$_REQUEST['num'], @$_REQUEST['preSaveTempId'], null);
			  	$cont = 0;
				while ($row = mysql_fetch_assoc($result)):
			  		$cont++;
					addPlugins("upload_list_record",$row);
					$bgColorClass = (@$bgColorClass == "oddRow") ? 'evenRow' : 'oddRow'; # rotate bgclass
					$uploadCount++;
			  		$domain = "https://".$CURRENT_USER["domain"]["domain"];
			  		$mimeSep = 	explode(".",$domain.$row["urlPath"]);
			  		$mime = strtolower($mimeSep[count($mimeSep)-1]);
					?>
					<div class="sortableRow" id="upload_<?php echo ($row['num']) ?>">
						<table  border="0" cellspacing="0" cellpadding="0" class="<?php echo $bgColorClass ?>" style="width:100%;">
							<tbody id="tableAAA">
								<tr >
									<td width="9%" align="center" class="notPointer dragHandle"><img src="images/drag.gif" height="6" width="19" alt="Order <?php echo @$field['order'] ?>" /></td>
									<td width="14%" align="center" class="notPointer">
										<div class="preview" >
											
											<? if ($mime == "png" || $mime == "jpg" || $mime == "jpeg" || $mime == "gif"){?>
												<? __showUploadPreview($row);?>											
											<? }else if($mime == "svg"){?>
											<a href="<?=$domain.$row["urlPath"];?>" target="_blank" rel="noopener"><img src="<?=$domain.$row["urlPath"];?>" alt=""></a>
											<? }else{?>
											<a href="<?=$domain.$row["urlPath"];?>" target="_blank" rel="noopener">descargar</a>
											<? }?>
										</div>
									</td>
									<td width="60%" style="font-size:10px;color:#888;">
										<?
										foreach (range(1,5) as $num):
											//$fieldLabel = @$fieldSchema["infoField$num"];
											$fieldLabel = @$infoLabels[$num-1] ?: @$fieldSchema["infoField$num"];
											$fieldValue = @$row["info$num"];
											if (!$fieldLabel) { continue; }
											if (@$infoLabels[$num-1]) echo htmlspecialchars($fieldLabel).":".htmlspecialchars($fieldValue)."<br/>";
										endforeach 
										?>
									</td>
									<?php

									if (@$row["list_actions"]){
										foreach($row["list_actions"] as $action):
										print "<td width='10%' align='right'><a href='".$action["link"]."' class='".$action["class"]."'>".$action["text"]."</a></td>";
										endforeach;
									}
									$hasModifyFields = @$fieldSchema["infoField1"] || @$fieldSchema["infoField2"] || @$fieldSchema["infoField3"] || @$fieldSchema["infoField4"] || @$fieldSchema["infoField5"];
									$modifyClickCode = "modifyUpload('{$row['num']}', '" .addcslashes(htmlspecialchars(pathinfo($row['filePath'], PATHINFO_BASENAME)), '\\\''). "', this); return false;";
									$removeClickCode = "removeUpload('{$row['num']}', '" .addcslashes(htmlspecialchars(pathinfo($row['filePath'], PATHINFO_BASENAME)), '\\\''). "', this); return false;";
									$translateClickCode = "modifyTranslate('{$row['num']}', '" .addcslashes(htmlspecialchars(pathinfo($row['filePath'], PATHINFO_BASENAME)), '\\\''). "', this); return false;";

									if ($hasModifyFields):
									?>
									<td width="10%" class="notPointer" align="right">&nbsp;&nbsp;<a href="#" onclick="<?php echo $modifyClickCode; ?>"><i class="fa fa-pencil-square-o" style="font-size:18px;"></i></a>&nbsp;&nbsp;</td>
									<td width="10%" class="notPointer" align="left" style="min-width:60px;">
										&nbsp;&nbsp;<a href="#" onclick="<?php echo $removeClickCode; ?>"><i class="fa fa-remove" style="font-size:18px;"></i></a>&nbsp;&nbsp;
										<!-- COMPROBAMOS IDIOMAS -->
										<?
										$contador_idiomas=0;
										$resultado["idiomas"] = array();
										foreach ($SETTINGS["idiomas"] as $idioma):
										if ($idioma!="www"&&$idioma!=""){
											$contador_idiomas+=1;
											$sql = "select * from ".$TABLE_PREFIX."traducciones where tableName='uploads' and (fieldName='info1' or fieldName='info2' or fieldName='info3' or fieldName='info4' or fieldName='info5') AND uploadNum='".$row['num']."'";
											$result2 = mysql_query($sql);
											while ($record = mysql_fetch_assoc($result2)){
												array_push($resultado["idiomas"],$record);
											}
										}
										endforeach;
										if ($contador_idiomas>0){
											if ($resultado["idiomas"]){
										?>&nbsp;&nbsp;<a href="#" onclick="<?php echo @$translateClickCode; ?>"><i class="fa fa-globe" style="color:#9ccb7a;font-size:18px;"></i></a>&nbsp;&nbsp;<?
											}else{
										?>&nbsp;&nbsp;<a href="#" onclick="<?php echo @$translateClickCode; ?>"><i class="fa fa-globe" style="color:#aaa;font-size:18px;"></i></a>&nbsp;&nbsp;<?
											}
										}
										?>

									</td>
									<?php else: ?>
									<td class="notPointer" width="20%" align="center" colspan="2"><a href="#" onclick="<?php echo $removeClickCode; ?>"><i class="fa fa-remove" style="font-size:18px;"></i></a></td>
									<?php endif ?>
								</tr>
							</tbody>
						</table>
					</div>
				<?
				endwhile;
				if (!$cont) echo "<div class='sinimagen'>Arrastra una foto para subirla</div>";
				if (is_resource($result)) { mysql_free_result($result); } 
				?>
			</div>
			<script type="text/javascript"><!-- // language strings
					lang_confirm_erase_image = '<?php echo addslashes(__("Remove file: %s")) ?>';
				//--></script>
			<script type="text/javascript" src="lib/jquery.js"></script>
			<script type="text/javascript" src="lib/jqueryInterfaceSortables.js"></script>
			<script type="text/javascript" src="lib/plugins/builder_saas/js/uploadList_functions.js"></script>
			<script>
				if (window.FileReader) {
					var drop;
					var fileNumber = 0;
					addEventHandler(window, 'load', function () {
						var status = document.getElementById('status');
						drop = document.getElementById('drop1');
						var list = document.getElementById('sortableRows');
						/*drop.addEventListener("click",function() {
							// creating input on-the-fly
							var input = $(document.createElement("input"));
							input.attr("type", "file");
							// add onchange handler if you wish to get the file :)
							input.trigger("click"); // opening dialog
							return false; // avoiding navigation
						});*/

						function cancel(e) {
							if (e.preventDefault) {
								e.preventDefault();
							}
							return false;
						}

						// Tells the browser that we *can* drop on this target
						addEventHandler(drop, 'dragover', function (e) {
							e = e || window.event; // get window.event if e argument missing (in IE) 
							if (e.preventDefault) {
								e.preventDefault();
							}
							fileNumber = fileNumber + 1;
							status.innerHTML = fileNumber;
							$("#drop1").css("background-color", '#ddd');
							
							return false;
						});
						// Tells the browser that we *can* drop on this target
						addEventHandler(drop, 'dragleave', function (e) {
							e = e || window.event; // get window.event if e argument missing (in IE) 
							if (e.preventDefault) {
								e.preventDefault();
							}
							fileNumber = fileNumber + 1;
							status.innerHTML = fileNumber;
							$("#drop1").css("background-color", '#fff');

							return false;
						});

						addEventHandler(drop, 'dragenter', cancel);
						addEventHandler(drop, 'drop', function (e) {
							
							e = e || window.event; // get window.event if e argument missing (in IE)   
							if (e.preventDefault) {
								e.preventDefault();
							} // stops the browser from redirecting off to the image.

							var dt = e.dataTransfer;
							var files = dt.files;
							for (var i = 0; i < files.length; i++) {
								var file = files[i];
								var reader = new FileReader();

								//attach event handlers here...  
								reader.readAsDataURL(file);

								addEventHandler(reader, 'loadend', function (e, file) {
									
									$(".sinimagen").remove();
									$("#drop1").css("background-color", '#fff');
									
									var bin = this.result;
									
									var newFile = document.createElement('div');
									newFile.classList.add("sortableRow");
									var image = "";
									newFile.innerHTML = `
											<table  border="0" cellspacing="0" cellpadding="0" class="<?php echo $bgColorClass ?>" style="width:100%;">
												<tbody id="tableAAA">
													<tr>
														<td width="9%" align="center" class="notPointer dragHandle" style="user-select: none;"><img src="images/drag.gif" height="6" width="19" alt="Order "></td>
														<td width="14%" align="center" class="notPointer">
															<div class="preview">

															</div>
														</td>
														<td width="60%" style="font-size:10px;color:#888;">Cargado : ${file.name}</td>
														<td width="10%" class="notPointer" align="right"></td>
														<td width="10%" class="notPointer" align="left"></td>
													</tr>
											</table>
									`;
									list.appendChild(newFile);
									var fileNumber = list.getElementsByTagName('div').length;
									status.innerHTML = fileNumber < files.length ? 'Loaded 100% of file ' + fileNumber + ' of ' + files.length + '...' : 'Done loading. processed ' + fileNumber + ' files.';

									var img = document.createElement("img");
									img.file = file;
									img.src = bin;
									var list2 = newFile.querySelector(".preview");
									list2.appendChild(img);
									uploadFile(file);
									
									self.parent.resizeIframe("<?=@$_REQUEST["idTemp"];?>_iframe");
								}.bindToEventHandler(file));
							}
							return false;
						});
						
						function uploadFile(file) {
							let url = '/lib/menus/modals/plupload/multiupload/upload.php?menu=<?=@$_REQUEST["menu"];?>&fieldName=<?=@$_REQUEST["fieldName"];?>';
							<?
							if (@$_REQUEST["num"]){
								?>url+='&num=<?=@$_REQUEST["num"];?>&preSaveTempId=<?=@$_REQUEST["preSaveTempId"];?>';<?
							}else if (@$_REQUEST["preSaveTempId"]){
								?>url+='&preSaveTempId=<?=@$_REQUEST["preSaveTempId"];?>';<?
							}
							?>
							let formData = new FormData()

							formData.append('file', file)

							fetch(url, {
							method: 'POST',
							body: formData
							})
							.then(() => { console.log("Todo bien");document.location.reload(); })
							.catch(() => { console.log("Error"); })
						}

						Function.prototype.bindToEventHandler = function bindToEventHandler() {
							var handler = this;
							var boundParameters = Array.prototype.slice.call(arguments);
							//create closure
							return function (e) {
								e = e || window.event; // get window.event if e argument missing (in IE)   
								boundParameters.unshift(e);
								handler.apply(this, boundParameters);
							}
						};
					});
				} else {
					document.getElementById('status').innerHTML = 'Your browser does not support the HTML5 FileReader.';
				}

				function addFile(evt) {
					e = e || window.event; // get window.event if e argument missing (in IE)   
					if (e.preventDefault) {
						e.preventDefault();
					} // stops the browser from redirecting off to the image.

					var dt = e.dataTransfer;
					var files = dt.files;
					for (var i = 0; i < files.length; i++) {
						var file = files[i];
						var reader = new FileReader();

						//attach event handlers here...  
						reader.readAsDataURL(file);

						addEventHandler(reader, 'loadend', function (e, file) {
							var bin = this.result;
							var newFile = document.createElement('div');
							newFile.innerHTML = 'Loaded : ' + file.name + ' size ' + file.size + ' B';
							list.appendChild(newFile);
							var fileNumber = list.getElementsByTagName('div').length;
							status.innerHTML = fileNumber < files.length ? 'Loaded2 100% of file ' + fileNumber + ' of ' + files.length + '...' : 'Done loading. processed ' + fileNumber + ' files.';

							var img = document.createElement("img");
							img.file = file;
							img.src = bin;
							list.appendChild(img);
						}.bindToEventHandler(file));
					}
					return false;
				}

				//seperate event
				function addEventHandler(obj, evt, handler) {
					if (obj.addEventListener) {
						// W3C method
						obj.addEventListener(evt, handler, false);
					} else if (obj.attachEvent) {
						// IE method.
						obj.attachEvent('on' + evt, handler);
					} else {
						// Old school method.
						obj['on' + evt] = handler;
					}
				}		
				window.addEventListener("load",function(){
					self.parent.resizeIframe("<?=@$_REQUEST["idTemp"];?>_iframe");
				});
			</script>
		</form>
	</body>
</html>
<?
function __redimensiona_imagen_larga($file,$info,$maxWidth=1200,$maxHeight=1200){
	if (@$info[0]<=$maxWidth && @$info[0]<=$maxHeight) return $file;
	
	$newFileSep = explode("/",$file);
	$fileName = $newFileSep[count($newFileSep)-1];
	array_pop($newFileSep);
	$newPath = join("/",$newFileSep);
	if (!file_exists($newPath."/big")){
		mkdir($newPath."/big",0777);
	}
	$newPath = $newPath."/big/".$fileName;
	if (!file_exists($newPath)){
		copy($file,$newPath);
	}
	$result = exec("mogrify -resize ".$maxWidth."x".$maxHeight." ".$file);
	return $file;
}
function __parsea_imagen($imagen,$url_completa=false,$thumb=null){
	if(is_null($imagen)) return;
	if ($thumb){
		if (strpos($imagen,"http")!==false) return $imagen;
		$file = $imagen;
		if (file_exists($_SERVER['DOCUMENT_ROOT'].'/'.$file)) {
			$timestamp = filemtime($_SERVER['DOCUMENT_ROOT'].'/'.$file);
		}
		if (@$timestamp) { // Archivo encontrado y todo ok
			$timestamp = base_convert($timestamp, 10, 36);

			// Añadimos -timestamp al final del nombre del archivo
			$parts = pathinfo($file);
			$carpeta = str_replace($parts['basename'], '', $file);
			$path = $carpeta.$parts['filename'].'-'.$thumb.'-'.$timestamp.'.'.$parts['extension'];
			if (!file_exists($_SERVER['DOCUMENT_ROOT'].$path)){
				
				$info = getimagesizefromstring(file_get_contents(realpath(dirname(__FILE__)."/../../../..".$file))); 
				$file = str_replace(realpath(dirname(__FILE__)."/../../../.."),"",__redimensiona_imagen_larga(realpath(dirname(__FILE__)."/../../../..".$file),$info));

				$resultado = saveResampledImageAs($_SERVER['DOCUMENT_ROOT'].$path, $_SERVER['DOCUMENT_ROOT'].'/'.$file, $thumb, $thumb);
			}
			return $path;
		}	
	}else{
		if (!@$imagen) $imagen = RUTA_PLANTILLA."/images/default.png";
		if ($url_completa)
			return protocol()."://".$_SERVER["HTTP_HOST"].str_replace("plupload/multiupload/../../","",$imagen);
		else
			return str_replace("plupload/multiupload/../../","",$imagen);
	}
}
function __showUploadPreview($record, $previewWidth = 50) {
	
	global $CURRENT_USER;

	$domain = @$CURRENT_USER['domain']['domain'];
	$isImage      = preg_match("/\.(gif|jpg|jpeg|png|svg)$/i", $record['urlPath']);
	
	$filename     = pathinfo($record['filePath'], PATHINFO_BASENAME);
	$image = API::getThumb($record['urlPath'],$previewWidth);
	$fullImage = strpos($record["urlPath"],"http") !== false ? $record["urlPath"] : "https://".$domain.$record['urlPath'];
	if ($isImage) {
		print "<a href='$fullImage' target='_BLANK'><img data-src='" .$image. "' class='lazyload' border='0' alt='' title='Click to view " .htmlspecialchars($filename). "'  /></a>\n";	
	}
	else {
		print "<a href='$fullImage' target='_BLANK'>download</a>\n";
	}
}
?>
