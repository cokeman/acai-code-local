<?
if (session_status() == PHP_SESSION_NONE) { session_start(); }

$num = (@$num) ? $num : @$_REQUEST["num"];
$familia = (@$familia) ? $familia : @$_REQUEST["familia"];
if ($familia) $_REQUEST["familia"] = $familia;
if ($num) $_REQUEST["num"] = $num;

require_once(dirname(__FILE__)."/lib/Module.php");
require_once(dirname(__FILE__)."/lib/Resource.class.php");
require_once(dirname(__FILE__)."/lib/variables.php");
require_once(dirname(__FILE__)."/lib/minifier.php");
require_once(dirname(__FILE__)."/idiomas/espanol.php");

if (!isset($_SESSION["user"])) {$_SESSION["user"]=null;}

if (isset($_GET["cerrarsesion"])){
	unset($_SESSION["user"]);
	session_destroy();
}
?>