<?php

	define("CMS_FOLDER","cms");
	define("CMS_VIEWER_LIB",CMS_FOLDER."/lib/viewer_functions.php");
	define('USAR_WEBP', false);

	require_once str_replace("/lib","",dirname(__FILE__))."/".CMS_VIEWER_LIB;

	CocoParser::$parseaCodigosEnLinea = false;
	CocoDB::$defaultRelationsDepth = 1;

//  if (isset($_REQUEST["enlace"]) && $_REQUEST["enlace"] == "") define("USE_MIN_TAILWIND",true);

    define("BUILDER_JSON_RESPONSE",@$_REQUEST["builderJson"] ? true : false); 
    
    if(property_exists('CocoDB', 'force_json_cache_uploads')) {
        CocoDB::$force_json_cache_uploads = false;
    }

    // EN SERVIDORES EXTERNOS HAY QUE DEFINIR LA RUTA DE TAILWINDCSS SINO NO FUNCIONARA EL MINCSS
    // define("TAILWIND_PATH","./node_modules/.bin/tailwindcss");


	/*****************/
	/* CACHE HANDLER */
	/*****************/

	// Cache local que cachea las consultas en cada petición cuando se repitan (sin Redis)

//	CocoDB::$noCacheURIS = ["\/mi(s)?-(.*)(\/)(.*)","\/hooks\/(.*)?","\/login\/(.*)?"];
//	CocoDB::$noCacheTABLES = ["aux_plg_payments","cms_cestas_abandonadas","cms_direcciones","cms_posibles_pedidos","cms_stock","cms_usuarios_metas","cms_usuarios"];
//
//	// Activamos el Cache en general y de consultas
//	CocoDB::fullCache(3);
//
//	// Activamos el Cache local que cache las consultas en cada carga cuando se repitan
//	CocoDB::localCache();
//
//	CocoDB::$force_redis_module = @CocoDB::bloquedCacheByURL($_SERVER["REQUEST_URI"]) ? false : true;
//	CocoDB::$force_redis_html = @CocoDB::bloquedCacheByURL($_SERVER["REQUEST_URI"]) ? false : true;

	CocoEmail::$smtp = true;
	CocoEmail::$smtp_data["host"] = "mail.quantumasis.com";
	CocoEmail::$smtp_data["port"] = "587";
	CocoEmail::$smtp_data["secure"] = "STARTTLS";
	CocoEmail::$smtp_data["username"] = "noreply@quantumasis.com";
	CocoEmail::$smtp_data["password"] = "uK5DDSa7sjPm7YtBbkRR";
	CocoEmail::$smtp_data["from"] = "noreply@quantumasis.com";
	CocoEmail::$smtp_data["from_name"] = "QuantumASIS";

	
	$plantilla = "template/estandar";
	define("PLANTILLA",$plantilla);
	define("RUTA_PLANTILLA","/".$plantilla);

    if (@$_REQUEST["idioma"]=="es") $_REQUEST["idioma"] = "";

	if (substr($_SERVER["REQUEST_URI"],0,4)=="/en/") $_REQUEST["idioma"]="en";
    if (substr($_SERVER["REQUEST_URI"],0,4)=="/es/") $_REQUEST["idioma"]="es";
	if (substr($_SERVER["REQUEST_URI"],0,4)=="/de/") $_REQUEST["idioma"]="de";
	if (substr($_SERVER["REQUEST_URI"],0,4)=="/fr/") $_REQUEST["idioma"]="fr";
	if (substr($_SERVER["REQUEST_URI"],0,4)=="/pt/") $_REQUEST["idioma"]="pt";
    if (substr($_SERVER["REQUEST_URI"],0,4)=="/se/") $_REQUEST["idioma"]="se";
	if (substr($_SERVER["REQUEST_URI"],0,5)=="/cat/") $_REQUEST["idioma"]="cat";
	if (substr($_SERVER["REQUEST_URI"],0,4)=="/it/") $_REQUEST["idioma"]="it";
	if (substr($_SERVER["REQUEST_URI"],0,4)=="/ko/") $_REQUEST["idioma"]="ko";
	if (substr($_SERVER["REQUEST_URI"],0,4)=="/ch/") $_REQUEST["idioma"]="ch";
	if (substr($_SERVER["REQUEST_URI"],0,4)=="/nu/") $_REQUEST["idioma"]="nu";
	if (substr($_SERVER["REQUEST_URI"],0,4)=="/ru/") $_REQUEST["idioma"]="ru";
	if (substr($_SERVER["REQUEST_URI"],0,4)=="/ni/") $_REQUEST["idioma"]="ni";

	if (@$_REQUEST["idioma"]) define("RUTA_RAIZ","/".$_REQUEST["idioma"]); else define("RUTA_RAIZ","");

    if (@$_REQUEST["idioma"]) define("ROOT","/".$_REQUEST["idioma"]); else define("ROOT","");

?>
