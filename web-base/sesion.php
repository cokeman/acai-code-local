<?
if (session_status() == PHP_SESSION_NONE) { session_start(); }

require_once(dirname(__FILE__)."/lib/Module.php");
require_once(dirname(__FILE__)."/lib/Resource.class.php");
require_once(dirname(__FILE__)."/lib/variables.php");
require_once(dirname(__FILE__)."/lib/minifier.php");

if (!isset($_SESSION["user"])) {$_SESSION["user"]=null;}

if (isset($_GET["cerrarsesion"])){
	unset($_SESSION["user"]);
	session_destroy();
}
?>