<?php
global $menu, $tableName, $errors, $TABLE_PREFIX;
require_once "lib/menus/modals/uploadModify_functions.php";

function UPLOADS_ordenarArray ($toOrderArray, $field, $inverse = false) {
	$position = array();
	$newRow = array();
	foreach ($toOrderArray as $key => $row) {
		$position[$key]  = $row[$field];
		$newRow[$key] = $row;
	}
	if ($inverse) {
		arsort($position);
	}
	else {
		asort($position);
	}
	$returnArray = array();
	foreach ($position as $key => $pos) {
		$returnArray[] = $newRow[$key];
	}
	return $returnArray;
}

if (@$_REQUEST["infoLabels"]) $infoLabels = json_decode(@$_REQUEST["infoLabels"],true); else $infoLabels = ["info1","info2","info3","info4","info5"];

$tablasLinks = mysql_query_fetch_all_assoc("SELECT DISTINCT TABLE_NAME,COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE COLUMN_NAME IN ('enlace') AND TABLE_SCHEMA='".$SETTINGS["mysql"]["database"]."'");
$tablasLinks = array_map(function($rec){ return $rec["TABLE_NAME"]; },$tablasLinks);
$resultLinks = [];
foreach($tablasLinks as $tablaLink){
	if (strpos($tablaLink,$TABLE_PREFIX)!==false){
		$recs = mysql_query_fetch_all_assoc("SELECT num,enlace FROM ".$tablaLink." WHERE enlace NOT LIKE '%#%'");
		$resultLinks+=array_map(function($rec) use ($tablaLink,$TABLE_PREFIX){ return ["value" => str_replace($TABLE_PREFIX,"",$tablaLink).",".$rec["num"],"label" => $rec["enlace"]];},$recs);
	}
}
$resultLinks = UPLOADS_ordenarArray($resultLinks,"label");

echo "<?xml version=\"1.0\"?>\n";
?>
<!DOCTYPE html
PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"https://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="https://www.w3.org/1999/xhtml">
	<head>
		<title></title>
		<meta http-equiv="Content-Type" content="text/html;charset=UTF-8" />
		<link rel="stylesheet" href="css/bootstrap.min.css">
		<link rel="stylesheet" type="text/css" href="css/ui.css" />
		<script type="text/javascript" src="lib/jquery.js"></script>
		<script type="text/javascript" src="js/lazysizes.min.js"></script>
		
		<style>
			html,body{height:100%;}
			body  { 
				background-color: #f5f5f5; margin: 0px; 
				display:flex;
				align-items: center;
				justify-content: center;
			}
			.label { width: 125px; float: left; };
			.form-control{margin-top:10px !important;box-shadow:none;border:none;}
			.listRowOdd{background:none;}
			td{padding:5px;}
			.listHeader{padding:10px;}
			.btn-primary{background-color:#333;border:none;}
			.btn-primary:hover{background-color:#555;}
			input[type="file"]{
				font-size: 20px;
				color: transparent;
			}
			.fieldsTable tr>td:first-child{min-width:90px;}
			.fieldsTable td.preview{width:150px;}
		</style>
	</head>
	<body>

		<form method="post" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" enctype="multipart/form-data" autocomplete="off">
			<input type="hidden" name="_defaultAction" value="uploadModify" />
			<input type="hidden" name="menu"          value="<?php echo htmlspecialchars($menu); ?>" />
			<input type="hidden" name="fieldName"     value="<?php echo htmlspecialchars(@$_REQUEST['fieldName']) ?>" />
			<input type="hidden" name="num"           value="<?php echo htmlspecialchars(@$_REQUEST['num']) ?>" />
			<input type="hidden" name="preSaveTempId" value="<?php echo htmlspecialchars(@$_REQUEST['preSaveTempId']) ?>" />
			<input type="hidden" name="save" value="1" />

			<script type="text/javascript">
				window.focus();
			</script>

			<table border="0" cellspacing="0" cellpadding="5" width="100%" bgcolor="#FFFFFF">
				<tr><td>
					<table border="0" cellpadding="5" cellspacing="1" width="100%">
						<?php
						$uploadCount = 0;
						$result = getUploads($tableName, $_REQUEST['fieldName'], @$_REQUEST['num'], @$_REQUEST['preSaveTempId'], $_REQUEST['uploadNums']);
						while ($row = mysql_fetch_assoc($result)):
							$bgColorClass = (@$bgColorClass == "listRowOdd") ? 'listRowEven' : 'listRowOdd'; # rotate bgclass
							$uploadCount++;
							addPlugins("upload_modify_record",$row);
							?>
							<tr>

								<td class="<?php echo $bgColorClass ?> preview" align="center" style="width:150px;padding: 2px">
									<?php showUploadPreview($row, 150); ?>
								</td>

								<td class="<?php echo $bgColorClass ?>" style="padding: 10px" valign="top">
									<input type="hidden" name="uploadNums[]" value="<?php echo $row['num']; ?>">
									<table class="fieldsTable" border="0" cellspacing="0" cellpadding="0">
										<tr>
											<td height="23">Archivo &nbsp;</td>
											<td><?php echo pathinfo($row['filePath'], PATHINFO_BASENAME); ?></td>
										</tr>
										<tr>
											<td>Cambiar foto</td>
											<td>
												<input type="file" name="<?php echo "{$row['num']}_file"; ?>" value="">
												<input type="hidden" name="<?php echo "{$row['num']}_name"; ?>" value="<?=$row["filePath"];?>">
												<input type="hidden" name="<?php echo "{$row['num']}_num"; ?>" value="<?=$row["num"];?>">
											</td>
										</tr>
										<? $cont = 0;?>
										<?php foreach (getUploadInfoFields($_REQUEST['fieldName']) as $infoFieldname => $label): ?>
										<tr>
											<?
											if (@$infoLabels[$cont]){
												?>
												<td><?php echo htmlspecialchars($infoLabels[$cont]); ?></td>
												<td>
													<?
													$type = "text";
													if (strpos(strtolower($infoLabels[$cont]),"enlace") !== false){
														$type = "link";
													}

													switch($type){
														case "link":
															?>
															<select class="form-control" name="<?php echo "{$row['num']}_$infoFieldname"; ?>">
																<option value="">Selecciona link</option>
																<? foreach($resultLinks as $linkRecord){?>
																	<option value="<?=$linkRecord["value"];?>"><?=$linkRecord["label"];?></option>
																<? }?>
															</select>
															<!--<input type="text" name="<?php echo "{$row['num']}_$infoFieldname"; ?>" value="<?php echo htmlspecialchars($row[$infoFieldname]); ?>" size="55" maxlength="255">-->
															<?
															break;
														default:
															?>
															<input type="text" class="form-control" name="<?php echo "{$row['num']}_$infoFieldname"; ?>" value="<?php echo htmlspecialchars($row[$infoFieldname]); ?>" size="55" maxlength="255">
															<?
													}
													?>
												</td>
												<?
											}
											?>
										</tr>
										<? $cont++;?>
										<?php endforeach ?>
										<?php 
										if (@$row["list_actions"]){
											foreach($row["list_actions"] as $action){
												echo "<tr>";
												echo "<td>".$action["text"]."</td>";
												echo "<td><a href='".$action["link"]."' class='".$action["class"]."'>Acceder</a></td>";
												echo "</tr>";
											}
										}
										?>
										<tr>
											<td></td>
											<td>
												<? if (@$_REQUEST["callbackFunc"]){?><input type="hidden" name='callbackFunc' value='<?=$_REQUEST["callbackFunc"];?>'><?}?>
												<? if (@$_REQUEST["callbackEvent"]){?>
													<input type="hidden" name='callbackEvent' value='<?=$_REQUEST["callbackEvent"];?>'>
													<input type="hidden" name='builder_ref' value='<?=$_REQUEST["builder_ref"];?>'>
												<?}?>
												<input type="submit" class="btn btn-primary" name="action=uploadModify" value="<?php _e('Save') ?>"  />
												<input type="button" class="btn btn-default" onclick='<? if (@$_REQUEST["callbackFunc"]){?>self.parent.<?=$_REQUEST["callbackFunc"];?>;<?}?>self.parent.tb_remove()' value="<?php _e('Cancel') ?>"  />
											</td>
										</tr>
									</table>
								</td>
							</tr>
						<?
						endwhile;
						if (is_resource($result)) { mysql_free_result($result); }

						if (!$uploadCount): 
							?>
							<tr>
								<td class="<?php echo $bgColorClass ?>" align="center" style="height: 100px">
									No se encuentran registros
								</td>
							</tr>
						<?
						endif 
						?>

					</table>
				</td></tr>
			</table>
		</form>
		<style>
			.form-control{box-shadow:none;border:solid 1px #eee;}
		</style>
		
	</body>
</html>
