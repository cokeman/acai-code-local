<?
require_once ("sesion.php");
require_once "funciones.php";

$apartado = dame_registros(str_replace($TABLE_PREFIX,"",$tabla), "num=".intval(@$_REQUEST["num"]), "num DESC", 1);
$apartado = @$apartado[0]; // get first record
$configuracionRecord["titulo_de_pagina"] = t($apartado,"name")." - ".t($configuracionRecord,"titulo_de_pagina");

if (@$apartado["titulo_de_pagina"]!="") $configuracionRecord["titulo_de_pagina"] = t($apartado,"titulo_de_pagina");
if (@$apartado["metatag_descripcion"]!="") $configuracionRecord["metatag_descripcion"] = t($apartado,"metatag_descripcion");
if (@$apartado["metatag_palabras"]!="") $configuracionRecord["metatag_palabras"] = t($apartado,"metatag_palabras");

if (!@$apartado["builder"] || !isJson($apartado["builder"])) {
	header("HTTP/1.0 404 Not Found");
	include("header.php");
	echo tpl("apartados",array("apartado" => array("name" => "Error","enlace" => ""), "nombre" => PAGINA_NO_ENCONTRADA,"contenido_texto" => PAGINA_NO_ENCONTRADA_TEXTO));
	include "footer.php";
	die();
}

include("header.php");
if (@$_REQUEST["onlyModule"]) {
?><style>body{padding:0px !important} body>*{display:none !important; } .builderModule{ display:block !important; }</style><?
}

$jsonConfig = json_decode($apartado["builder"],true);
$cacheQuery = [];
$result = "";



foreach($jsonConfig as $section):
	if (@$_REQUEST["onlyModule"] && $_REQUEST["onlyModule"]!=$section["modulo"]) continue;
	if (!file_exists(".".RUTA_PLANTILLA."/modulos/".$section["modulo"].'/builder.json')){
		echo "<div class='builderModule text-center'><i class='fa fa-warning'></i> El módulo solicitado no existe</div>";
		continue;
	}
	$data = [];
	$configModulo = modulo_builder_config($section["modulo"]);
	if (!@$configModulo) continue;
	
	$variables = [];
	if (@$section["referenciada"]){
		$apartadoReferencia = @dame_registros("apartados","num=".intval(@$section["referenciada"]),"num desc",1)[0];
		if (@$apartadoReferencia){
			$jsonConfigAux = json_decode($apartadoReferencia["builder"],true);
			foreach($jsonConfigAux as $contAux => $sectionAux){
				if ($sectionAux["modulo"] == $section["modulo"]) $section["config-vars"] = $sectionAux["config-vars"];
			}
		}
	}

	dame_variables_config($configModulo,$section["config-vars"],$variables,$section);
	
	if (@$_REQUEST["onlyModule"]){
		echo "<div class='builderModule'>".modulo($section["modulo"],$variables)."</div>";
	}else{
		$variables["apartadoWrapper"] = $apartado;
		$variables["section_id"] = @$section["section_id"];
		echo modulo($section["modulo"],$variables);
	}
	//echo "<pre>";print_r($jsonConfig);echo "</pre>"; // comentario
	//echo "<pre>";print_r($configModulo);echo "</pre>"; // comentario
	//echo "<pre>";print_r($variables);echo "</pre>"; // comentario
endforeach;

echo $result;

include("footer.php");
if (@$_REQUEST["onlyModule"]) {
	?><style>body>*{display:none !important; } .builderModule{ display:block !important; }</style><?
}


function dame_variables_config($configModulo,$configVars,&$variables,$section = null){
	global $cacheQuery,$TABLE_PREFIX;
	
	foreach($configModulo["vars"] as $var => $content){
		if (isset($content["vars"])) {
			$variables[$var] = [];
			if (isset($configVars[$var]) && is_array($configVars[$var])) 
				dame_variables_config($content,$configVars[$var],$variables[$var]);
			else if (isset($configVars[$var]) && is_string($configVars[$var]))
				$variables[$var] = dame_variables_automaticas($configModulo["vars"][$var],$configVars[$var],$configVars);
		}else{
			if (isset($configVars[0][$var])){
				foreach($configVars as $cont => $configVar){
					dame_variables_config($configModulo,$configVar,$variables[$cont]);
				}
			}else if (isset($configVars[$var]["tableName"])){
				$configuracion = @$configVars[$var];
				if (@$content["controller"]){
					$variables[$var] = modulo_builder_controller($section["modulo"],$content["controller"],array("var" => $configuracion,"field" => $content["relations"]["builder_custom"]));
					continue;
				}
				
				if (strstr($content["relations"][$configuracion["tableName"]],'}}')){
					// QUERY
					$query = str_replace("{{","",str_replace("}}","",$content["relations"][$configuracion["tableName"]]));
					$query = str_replace("{TABLE_PREFIX}",$TABLE_PREFIX,$query);
					
					preg_match("/\{(.*)\}/",$query,$matches);
					$subResult = "";
					if (@$matches){
						$result = @getQuery($configuracion["tableName"],$configuracion["recordNum"])[strtolower($matches[1])];
						$query = str_replace($matches[0],$result,$query);
						if (@$result){
							$subResult = mysql_query_fetch_all_assoc($query." LIMIT 1")[0];
							$subResult = @array_values($subResult)[0];
						}
					}
					$variables[$var] = $subResult;			
					
				}else if (strstr($content["relations"][$configuracion["tableName"]],'}')){
					// SET VALUE
					$miValue = str_replace("{","",str_replace("}","",$content["relations"][$configuracion["tableName"]]));
					$variables[$var] = @$miValue ? t_var($miValue) : "";
				}else{
					// SET BY BBDD FIELDNAME
					if (@$configuracion["recordNum"]){
						$variables[$var] = @getQuery($configuracion["tableName"],$configuracion["recordNum"])[$content["relations"][$configuracion["tableName"]]];
						
						// TRAUDCCION
						if (!is_array($variables[$var])){
							$recordAux = ["num" => $configuracion["recordNum"],"tableName" => $configuracion["tableName"]];
							$recordAux[$var] = $variables[$var];
							$variables[$var] = nl2br(t($recordAux,$var));
							
						}else if (isset($variables[$var]["info1"])){
							$variables[$var]["info1"] = t($variables[$var],"info1");
							$variables[$var]["info2"] = t($variables[$var],"info2");
							$variables[$var]["info3"] = t($variables[$var],"info3");
							$variables[$var]["info4"] = t($variables[$var],"info4");
							$variables[$var]["info5"] = t($variables[$var],"info5");
							
						}
						
					}else if (@$configuracion["value"]){
						$variables[$var] = @$configuracion["value"];
					}
					// PARSEO DE CAMPOS
					switch($content["type"]){
						case "link":
							$variables[$var] = parseLink($variables[$var]);
							break;
						case "upload":
							if (@$content["infoLabels"]){
								foreach($content["infoLabels"] as $cont => $infoLabel){
									if (strpos("enlace",strtolower($infoLabel)) !== false){
										$infoNum = $cont+1;
										foreach($variables[$var] as $cont2 => $image){
											$variables[$var][$cont2]["info".$infoNum] = parseLink("2|".$variables[$var][$cont2]["info".$infoNum]);
										}
									}
								}
								
							}
							break;
					}
				}
			}
		}
	}
}

function parseLink($string){
	$sep = array_filter(explode("|",$string));
	if (@$sep[1]){
		if ($sep[0]=="2"){
			$sep2 = array_filter(explode(",",$sep[1]));
			if (@$sep2[1]){
				$link = @getQuery($sep2[0],$sep2[1])["enlace"];
				$recordAux = ["num" => $sep2[1],"tableName" => $sep2[0]];
				
				$link = @$_REQUEST["idioma"] ? t($recordAux,"enlace") : $link;
				
				return $link;
			}else{
				return @$sep2[0];
			}
		}else{
			return $sep[1];
		}
	}else if (@$sep[0]){
		return $sep[0];
	}
	return $string;
}
function dame_variables_automaticas($configModuloVar,$configVarsVar,$configVars){
	global $TABLE_PREFIX;
	if (@$configModuloVar[0]) $configModuloVar = $configModuloVar[0];
	if (!$configModuloVar["auto"][$configVarsVar]) die("<pre>ERROR en Selección automática</pre>");
	if (!is_array($configModuloVar["auto"][$configVarsVar]["query"])) die("<pre>ERROR la búsqueda automatica no es un objeto</pre>");
	
	$query = $configModuloVar["auto"][$configVarsVar]["query"];
	$result = @dame_registros($query["tableName"],@$query["where"] ?: "",@$query["order"] ?: "",@$query["limit"] ?: @$configModuloVar["maxLimit"] ?: "");
	
	$query["BD_relations"] = [];
	$query["relationsStrings"] = [];
	$query["relationsQuery"] = [];
	
	foreach($configModuloVar["vars"] as $key => $value){	
		if (strpos($value["relations"][$query["tableName"]],"{")===false){
			$query["BD_relations"][$key] = $value["relations"][$query["tableName"]];
		}else{
			if (strpos($value["relations"][$query["tableName"]],"{{")===false){
				$miValue = str_replace("{","",str_replace("}","",$value["relations"][$query["tableName"]]));
				$query["relationsStrings"][$key] = @$miValue ? t_var($miValue) : "";
			}else{
				// QUERY
				$queryAux = str_replace("{{","",str_replace("}}","",$value["relations"][$query["tableName"]]));
				$queryAux = str_replace("{TABLE_PREFIX}",$TABLE_PREFIX,$queryAux);
				
				preg_match("/\{(.*)\}/",$queryAux,$matches);
				
				if (@$matches){
					$query["relationsQuery"][$key] = ["query" => $queryAux,"field" => $matches[1]];
				}			
			}
		}
	}
	
	foreach($result as $cont => $record):
		foreach($query["BD_relations"] as $resultKey => $resultValue){
			$result[$cont][$resultKey] = $result[$cont][$resultValue];
		}
		foreach($query["relationsStrings"] as $resultKey => $resultValue){
			$result[$cont][$resultKey] = $resultValue;
		}
		foreach($query["relationsQuery"] as $resultKey => $resultValue){
			
			$queryAux2 = str_replace("{".$resultValue["field"]."}",$result[$cont][strtolower($resultValue["field"])],$resultValue["query"]);
			$subResult = @mysql_query_fetch_all_assoc($queryAux2." LIMIT 1")[0];
			$subResult = @array_values($subResult)[0];
			$result[$cont][$resultKey] = $subResult;
		}
	endforeach;
	
	return $result;
	
}
function getQuery($tableName,$recordNum){
	global $cacheQuery;
	
	$hashQuery = md5($tableName.$recordNum);
	if (!isset($cacheQuery[$hashQuery])){
		$cacheQuery[$hashQuery] = @dame_registros($tableName,"num=".intval($recordNum),"num desc",1)[0];
	}
	return $cacheQuery[$hashQuery];	
}

function modulo_builder_config($p){
	ob_start();
	require(".".RUTA_PLANTILLA."/modulos/".$p.'/builder.json');
	return json_decode(ob_get_clean(),true);
}
function modulo_builder_controller($p,$c,$d=array()){
	extract($d);
	ob_start();
	require(".".RUTA_PLANTILLA."/modulos/".$p.'/'.$c);
	$resultado = ob_get_clean();
	return $resultado;
}

function isJson($string) {
	json_decode($string);
	return (json_last_error() == JSON_ERROR_NONE);
}
?>
