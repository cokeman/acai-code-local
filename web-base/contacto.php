<?
require_once ("sesion.php");
require_once "funciones.php";

$apartado = dame_registros("apartados", "controlador='contacto.php'");
$apartado = $apartado[0];

$configuracionRecord["titulo_de_pagina"] = t($apartado,"name")." - ".$configuracionRecord["titulo_de_pagina"];

if (@$apartado["titulo_de_pagina"]!="") $configuracionRecord["titulo_de_pagina"] = t($apartado,"titulo_de_pagina");
if (@$apartado["metatag_descripcion"]!="") $configuracionRecord["metatag_descripcion"] = t($apartado,"metatag_descripcion");
if (@$apartado["metatag_palabras"]!="") $configuracionRecord["metatag_palabras"] = t($apartado,"metatag_palabras");

include("header.php");

$portada = dame_registros("portada","","",1);$portada = @$portada[0];

echo tpl("contacto",array(
						"portada"				=>	@$portada,
						"apartado"				=>	$apartado,
						"apartado_nombre"		=>	t($apartado,PREFIJO."name"),
						"apartado_contenido"	=>	t($apartado,PREFIJO."content"),
						"contacto_facebook"		=>	@$configuracionRecord["contacto_facebook"],
						"contacto_mapa_google"	=>	@$configuracionRecord["contacto_mapa_google"],
						"contacto_foto"			=>	@$configuracionRecord["contacto_foto"],
						"delegaciones"			=>	@$delegaciones,
						"banner"				=>	@$apartado["fotos_cabecera"],
					)
);

$correoadmin=$configuracionRecord['correo_admin'];
if (isset($_REQUEST["enviar"])){
	if ($_SESSION['key_captcha']!=md5($_POST["captcha"])){
		?><script>alert("El código escrito no es válido");</script><?
	}else{
		$mensaje ="<b>Tipo de Contacto</b>: ".$_POST["tipo_de_contacto"]."<br><br>";
		$mensaje .="<b>Nombre</b>: ".$_POST["nombre"]."<br>";
		$mensaje .="<b>Apellidos</b>: ".$_POST["apellidos"]."<br>";
		$mensaje .="<b>Correo</b>: ".$_POST["correo"]."<br>";
		$mensaje .="<b>Teléfono</b>: ".$_POST["telefono"]."<br>";
		$mensaje .="<b>Asunto</b>: ".$_POST["asunto"]."<br>";
		if (@$_REQUEST["mensaje"]) $mensaje .="<b>Mensaje</b>: ".$_REQUEST["mensaje"]."<br>";
        enviarcorreo($correoadmin,"Contacto desde la web",$mensaje);
		echo "<script>alert('".t_var("Mensaje recibido")."');</script>";
	}
}

?>

<?include("footer.php");?>
<script>$(".tabs li.active a").click();</script>

<script>
	
	$("form").submit(function(e){
		var error=0;
		$(this).find("[required]").each(function(){
			if ($(this).val()==""){
				error+=1;
				$(this).css("border-color","#ff4000").focus(function(){ $(this).css("border-color","#ddd"); });
			}
		});
		if (error) {
			e.preventDefault();
			alert("<?=DEBE_RELLENAR;?>");
		}
	});

	$(".captcha").click(function(){
		$(this).attr("src","/captcha.php");
	});
	$("iframe").width("100%").height(200);
</script>
