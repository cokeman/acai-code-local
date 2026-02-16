<?
if ($menu!="cms_api_plugin") return;

if (!@$external) showHeader(true);

$data = file_get_contents("https://".$CURRENT_USER["domain"]["domain"]."/cms/lib/plugins/cms_api/v3/schemaBase.json?t=".time());
global $json;
$json = [];
try{
	$json = json_decode($data,true);
}catch(Exception $e){
	?>
	<style>#page-container.header-fixed-top{padding:0px;}</style>
	<div id="page-content">	
		<h3 class="font-bolg text-2xl"> Ha ocurrido un error al recuperar los datos</h3>
	</div>
	<?
	die();
}

?>
<style>#page-container.header-fixed-top{padding:0px;}</style>
<div id="page-content">
	<link rel="stylesheet" type="text/css" href="https://cms.cocosolution.com/lib/plugins/cms_api/cmsAPI/production.min.css">
	<link rel="stylesheet" type="text/css" href="https://cms.cocosolution.com/lib/plugins/cms_api/cmsAPI/custom.scss">

	<link rel="stylesheet" href="https://cms.cocosolution.com/lib/plugins/cms_api/cmsAPI/button.css">

	<script src="https://cms.cocosolution.com/lib/plugins/cms_api/cmsAPI/messenger-setup.js" nonce=""></script>

	<script id="_pmPostmanRunObject" async="" src="https://cms.cocosolution.com/lib/plugins/cms_api/cmsAPI/button.js"></script>



	<div class="layout">


		<div class="container-fluid no-gutter">
			<div class="row no-gutter">
				<div class="col-xs-12 info no-gutter">
					<div class="pm-persistent-notification-container"></div>
					<div class="pm-global-notification-container"></div>
					<div id="mobile-controls">

						<label>Environment</label>
						<div class="environment-dropdown dropdown">
							<button class="btn pm-btn pm-btn-secondary hard--sides disabled" type="button">
								<div class="dropdown-button ellipsis active-environment" data-environment-id="0">No environment</div>
							</button>
						</div>


					</div>

					<div id="error-view">
					</div>

					<div id="doc-body" class="">
						<div class="row row-no-padding row-eq-height" id="intro">
							<div class="col-md-12 col-xs-12 section">
								<div class="api-information">
									<div class="collection-name">
										<p>CmsApi</p>
									</div>
									<?
									if ($json["info"]){
										foreach($json["info"] as $info){
											?>
											<div class="collection-description">
												<h1 id="introduction"><?=$info["title"];?></h1>
												<p><?=$info["description"];?></p>
											</div>
											<?
										}
									}
									?>
									<div class="collection-name">
										<p>EndPoints</p>
									</div>
									
									<div class="py-4 m-0">
									<?

									foreach($json["endPoints"] as $url => $endPoint){
										
										$hasAdminAccess = userHasSectionAdminAccess( @$url );
										$hasUserAccess  = userHasSectionWriterAccess( @$url );
										$hasAllAdminAccess = userHasAllAdminAccess();
										$hasMenuAccess  = $hasAdminAccess || $hasUserAccess;

										if ($hasAllAdminAccess){
											if (@$endPoint["custom"] || @$url == "upload" || @$url == "bulk" || @$url == "auth") $hasMenuAccess = true;
										}else{
											if (@$url == "auth") $hasMenuAccess = true;
										}
										

										if (userHasSectionReadAccess(@$url)){
											$hasMenuAccess = true;
											unset($endPoint["methods"]["POST"]);
											unset($endPoint[":"]["methods"]["PATCH"]);
											unset($endPoint[":"]["methods"]["DELETE"]);
										}

										if (!$hasMenuAccess) continue;
										
										if (@$endPoint["pipes"] && userHasAllAdminAccess()){
											foreach($endPoint["pipes"] as $pipe => $method){
												if (@$method["params"]) $key = "POST"; else $key = "GET";
												if (@$method["method"]) $key = @$method["method"];
												$endPoint["title"] = $method["title"];
												$endPoint["description"] = $method["description"];
												paintMethod($url,$endPoint,$key,$method,$pipe);
											}
											continue;
										}
										foreach($endPoint["methods"] as $key => $method){
											paintMethod($url,$endPoint,$key,$method);
										}
										if (@$endPoint["search"]){
											foreach($endPoint["search"]["methods"] as $key => $method){
												paintMethod($url,$endPoint,$key,$method,"search");
											}
										}
										if (@$endPoint[":"]){
											foreach($endPoint[":"]["methods"] as $key => $method){
												paintMethod($url,$endPoint,$key,$method,":");
											}
										}
									}
									
									?>
									
									</div>
									
								</div>
							</div>
						</div>
						
						
					</div>
				</div>
				
				<div class="sidebar py-1 select-none" id="nav-sidebar">
					<?
					$cont = 0;
					foreach($json["endPoints"] as $keyEnd => $endPoint){
						$hasAdminAccess = userHasSectionAdminAccess( @$keyEnd );
						$hasUserAccess  = userHasSectionWriterAccess( @$keyEnd );
						$hasAllAdminAccess = userHasAllAdminAccess();
						$hasMenuAccess  = $hasAdminAccess || $hasUserAccess;
						if ($hasAllAdminAccess){
							if (@$endPoint["custom"] || @$keyEnd == "upload" || @$keyEnd == "bulk" || @$keyEnd == "auth") $hasMenuAccess = true;
						}else{
							if ( @$keyEnd == "auth") $hasMenuAccess = true;
						}
						
						if (userHasSectionReadAccess(@$keyEnd)){
							$hasMenuAccess = true;
							unset($endPoint["methods"]["POST"]);
							unset($endPoint[":"]["methods"]["PATCH"]);
							unset($endPoint[":"]["methods"]["DELETE"]);
						}
						
						if (!$hasMenuAccess) continue;
						?>
						<div onclick="toggleSidebar('endPoint<?=$keyEnd;?>');" class="flex justify-stretch relative cursor-pointer mt-1 bg-gray-300 rounded py-2 px-4 font-bold text-black mx-2">
							<p class="w-full text-md uppercase m-0"><?=$endPoint["title"];?></p>
							<span class="p-2 flex-shrink-0 text-sm text-gray-500 <?=$cont ? "" : "hidden";?>" data-for="endPoint<?=$keyEnd;?>"><i class="fa fa-chevron-down"></i></span>
							<span class="p-2 flex-shrink-0 text-sm text-gray-500 <?=!$cont ? "" : "hidden";?>" data-for="endPoint<?=$keyEnd;?>"><i class="fa fa-chevron-up"></i></span>
						</div>
						<ul class="p-4 m-0 hidden" id="endPoint<?=$keyEnd;?>">
						<?
						
						if (@$endPoint["pipes"]){
							foreach($endPoint["pipes"] as $pipe => $method){
								if (@$method["params"]) $key = "POST"; else $key = "GET";
								if (@$method["method"]) $key = @$method["method"];
								
								?>
								<li class="flex justfy-start items-center text-xs font-normal">
									<div class="<?=$key;?> w-20 font-semibold" title="POST">
										<span><?=$key;?></span>
									</div>
									<div class="text-lg" title="Login">
										<a class="anclaApi text-gray-600 hover:text-black hover:no-underline" href="#<?=md5($keyEnd.$key.$pipe);?>" data="<?=$keyEnd.$key.$pipe;?>">
											<span>
												<? 
												echo $method["title"];
												?>
											</span>
										</a>
									</div>
								</li>
								<?
								$cont++;
							}
							echo "</ul>";
							continue;
						}
						foreach($endPoint["methods"] as $key => $method){
							paintMenu($keyEnd,$endPoint,$key,$method);
						}
						if (@$endPoint["search"]){
							foreach($endPoint["search"]["methods"] as $key => $method){
								paintMenu($keyEnd,$endPoint,$key,$method,"search");
							}
						}
						if (@$endPoint[":"]){
							foreach($endPoint[":"]["methods"] as $key => $method){
								paintMenu($keyEnd,$endPoint,$key,$method,":");
							}		
						}
						?>
						</ul>
						<?	
					}
					?>
				</div>
				
			</div>
		</div>
	</div>
</div>
<script>
	
	function toggleSidebar(id){
		document.getElementById(id).classList.toggle('hidden'); 
		var flechas = document.querySelectorAll("[data-for='"+id+"']");
		console.log(flechas);
		for (flecha of flechas){
			flecha.classList.toggle('hidden');
		}
	}
</script>
<? showFooter(true);?>
<?
function paintMenu($url,$endPoint,$key,$method,$type=""){
	if ($type==":") $url.="/{".@$endPoint[":"]["variable"]."}";
	?>
	<li class="flex justfy-start items-center text-xs font-normal">
		<div class="<?=$key;?> w-20 font-semibold" title="POST">
			<span><?=$key;?></span>
		</div>
		<div class="text-lg" title="Login">
			<a class="text-gray-600 hover:text-black hover:no-underline" href="#<?=md5($url.$key.$type);?>" data="<?=$url.$key.$type;?>">
				<span>
					<? 
					switch($key){
						case "GET": echo $type==":" ? "Obtener registro por id" : "Obtener todos los registros";break;
						case "POST": 
							if ($url == "auth") {echo "Login";break;}
							if ($url == "upload") {echo "Subir una imagen al servidor web";break;}
							if ($url == "bulk") {echo "Búsquedas en masa";break;}
							echo $type=="search" ? "Buscar registros" : "Insertar registro";
							break;
						case "PATCH": echo "Actualizar registro";break;
						case "DELETE": echo "Eliminar registro";break;
						default: echo "Otro...";
					}
					?>
				</span>
			</a>
		</div>
	</li>
	<?
}
function paintMethod($url,$endPoint,$key,$method,$type = ""){
	global $CURRENT_USER;
	global $json;
	
	if ($type==":") $url.="/{".@$endPoint[":"]["variable"]."}";
	
	?>
	<div class="px-0 pb-12" id="<?=md5($url.$key.$type);?>" id2="<?=$url.$key.$type;?>">
		<div class="heading">
			<div class="name">
				<span class="<?=$key;?> method" title="<?=$key;?>"><?=$key;?></span>
				<? $sufix = $type;?>
				<? if ($sufix == ":") $sufix = $endPoint[":"]["variable"];?>
				<? echo $endPoint["title"];?> <? if (@$sufix!=""){?><span class="text-lg text-gray-600">(<?=$sufix;?>)</span><?}?>
			</div>
		</div>
		
		<? if (@$endPoint["custom"] || @$type == "search") $url.="/".$type;?>
		
		<div class="url">https://<?=$CURRENT_USER["domain"]["domain"]."/cms/lib/plugins/cms_api/v3/".$url;?>/</div>
		<div class="description">
			<p>
			<?
			if (@$endPoint["custom"]){
				echo @$method["description"] ?: @$method["title"] ?: $endPoint["description"] ?: "Sin descripción asignada";
			}else{
				switch($key){
					case "GET": 
						switch($type){
							case ":": 
								echo "Obtener registro por id";
							break;
							case "":
								echo "Obtener todos los registros";
							break;
							default:
								echo @$method["title"] ?: $endPoint["description"] ?: "Sin descripción asignada";
						}

					break;
					case "POST": 
						switch($type){
							case "search":
								echo "Buscar registros en el CMS utilizando criterios de búsqueda";
								break;
							case "":
								switch($url){
									case "auth":
										echo "Login que permite obtener el token necesario {BEARER} para todas las peticiones de la API";
										break;
									case "upload":
										echo "Subir una imagen al servidor";
										break;
									case "bulk":
										echo "
										Recoger registros en masa desde distintas secciones. 
										Se pueden añadir tantas tablas {tableName} como sea necesario con los parámetros de búsqueda independientes por cada tabla.";
										break;
									default:
										echo "Insertar un registro en la sección <b>".$url."</b> del Gestor de Contenidos";
								}

								break;
							default:
								echo @$method["description"] ?: "Sin descripción asignada";
						}
					break;
					case "PATCH": echo "Actualizar un registro pasando el identificador (".$endPoint[":"]["variable"].") como referencia";break;
					case "DELETE": echo "Eliminar registro pasando el identificador (".$endPoint[":"]["variable"].") como referencia";break;
					default:
				}	
			}
			
			?>
		</div>
		<?
		if (@$endPoint["custom"]) {
			if (isset($method["params"])) $method["body"]["data"] = $method["params"];
			$firstMethod = array_keys($endPoint["methods"])[0];
			if (isset($endPoint["methods"])) $method["headers"] = $endPoint["methods"][$firstMethod]["headers"];
		}
		?>
		<? if (@$method["headers"]){?>
		<div class="headers pb-8">
			<div class="heading">HEADERS</div>
			<hr>
			<? 
			
			if (!is_array($method["headers"]) && isset($json["variables"][$method["headers"]])){
				$method["headers"] = $json["variables"][$method["headers"]];
			}
			if (is_array($method["headers"])){?>
				<? foreach($method["headers"] as $headerKey => $headerValue){?>
				<div class="param row flex flex-start">
					<div class="name w-64 flex-shrink-0"><?=$headerKey;?></div>
					<div class="value w-1/2 border-b rounded mx-1 px-2"><?=$headerValue["value"];?></div>
					<div class="value w-1/2 border-b rounded px-2"><?=$headerValue["required"] ? "<b class='text-red-600'>obligatorio</b>" : "<span class='text-gray-600'>opcional</span>";?></div>
					<div class="value w-64 flex-shrink-0 text-right border-b rounded px-2"></div>
				</div>
				<? }?>
			<? }?>
		</div>
		<? }?>
		<? if (@$method["body"]){?>
		<div class="headers">
			<div class="heading">BODY</div>
			<hr>
			<? if ($type=="search") $method["body"]["data"] = $method["body"][array_keys($method["body"])[0]];?>
			<? if ($url=="upload") $method["body"]["data"] = $method["body"];?>
			<? 
			if ($url=="bulk" || $url == "bulk_sync") {
				$method["body"]["data"] = $method["body"];
				$method["body"]["data"]["{tableName}"]["required"] = false;
			}
			?>
			<? foreach($method["body"]["data"] as $bodyKey => $bodyValue){?>
			<div class="param row flex flex-start">
				<div class="name w-64 flex-shrink-0"><?=$bodyKey;?></div>
				<div class="value w-1/2 border-b rounded mx-1 px-2">
					<?
					if (isset($bodyValue["value"])){
						if (is_array($bodyValue["value"])){
							echo "<pre style='padding:0px;border:none;background:none'>";
							echo json_encode($bodyValue["value"],JSON_PRETTY_PRINT);
							echo "</pre>";
						}else{
							echo $bodyValue["value"];
						}
					}else{
						if (is_array($bodyValue)){
							echo "<pre style='padding:0px;border:none;background:none'>";
							echo json_encode($bodyValue,JSON_PRETTY_PRINT);
							echo "</pre>";
						}else{
							echo $bodyValue;
						}
					}
					?>
				</div>
				
				<div class="value w-1/2 border-b rounded px-2"><?=@$bodyValue["required"] ? "<b class='text-red-600'>obligatorio</b>" : "<span class='text-gray-600'>opcional</span>";?></div>

				
				<div class="value w-64 flex-shrink-0 text-right border-b rounded px-2  italic"><span class="text-gray-600"><?=@$bodyValue["schema"]["type"];?></span></div>
				
			</div>
			<? }?>
		</div>
		<? }?>
	</div>
	<?
}
?>
<? if (!@$external) die(); ?>
