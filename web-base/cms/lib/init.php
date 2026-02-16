<?php
$START_TIME = microtime();  // needs to be first to get accurate time reading
define('REQUIRED_PHP_VERSION',   '5.6'); // Released May 2003
define('REQUIRED_MYSQL_VERSION', '4.1.7'); // Released 23 October 2004 - First Production Release

define("RUTA_PLANTILLA_FROM_INIT","/template/estandar");

enableErrorReporting();
updatePHPSettings();
displayInitErrors();
renameAndRemoveDefaultFiles();
$programDir = _getProgramDir();
require_once "$programDir/lib/classes/Db.php";
require_once "$programDir/lib/common.php";
require_once "$programDir/lib/database_functions.php";
global $SETTINGS;
setRequestGlobal();     // this must be called _AFTER_ updatePHPSettings() - uses fixed PATH_INFO _and_ undo magic_quotes
setProgramGlobals();    // this must be called _AFTER_ load libraries - uses loadINI
loadPlugins();
if (function_exists('date_default_timezone_set')) {
	if (!@date_default_timezone_set($SETTINGS['timezone'])) {
		@$GLOBALS['APP']['alerts'] .= "Error setting timezone to '{$SETTINGS['timezone']}', Invalid timezone name.<br/>\n";
		date_default_timezone_set('UTC');
	}
}
displaySocketErrors();
if ($SETTINGS['isInstalled']) {
	connectToMySQL();
}
function enableErrorReporting() {
	if (!defined('E_STRICT')) { define('E_STRICT', 2048); } // define E_STRICT for PHP < 5
	error_reporting(E_ALL | E_STRICT);  // display all errors
	ini_set('display_errors', '1');
	ini_set('display_startup_errors', '1');
	ini_set('track_errors', '1');     // store last error in $php_errormsg
	ini_set('html_errors', '0');      // don't output html links in error messages
	ini_set('mysql.trace_mode', '0'); // when this is enabled SQL_CALC_FOUND_ROWS and FOUND_ROWS doesn't work: https://bugs.php.net/bug.php?id=33021
}
function updatePHPSettings() {
	umask(0);                            // use default permission on created files
	ini_set('memory_limit', '128M');     // raise memory limit (if allowed)
	ini_set('magic_quotes_runtime', 0);  // disable magic_quotes_runtime (backslashes quotes in input)
	ini_set('default_charset', 'utf-8'); //
	ini_set('mysql.connect_timeout', 5); // PHP default of 60 ties up browser for too long - mysql.connect_timeout may not work on windows
	if (!ini_get('sendmail_from')) { ini_set('sendmail_from', ''); }
	set_include_path(_getProgramDir() . PATH_SEPARATOR . get_include_path());
	/*
	// Anael: Esto ya no debería usarse para nada y da problemas en nuevas versiones de PHP
	if (get_magic_quotes_gpc()) {              // undo deprecated php magic_quotes feature
		$in = array(&$_GET, &$_POST, &$_COOKIE); // code from: https://talks.php.net/show/php-best-practices/26
		while (list($k,$v) = each($in)) {
			foreach ($v as $key => $val) {
				if (!is_array($val)) {
					$in[$k][$key] = stripslashes($val);
					continue;
				}
				$in[] =& $in[$k][$key];
			}
		}
		unset($in);
	}
	*/
	$isCgiMode = PHP_SAPI == 'cgi' || PHP_SAPI == 'cgi-fcgi';
	if ($isCgiMode && @$_SERVER['REQUEST_URI']) {
		list($scriptUri) = explode('?', $_SERVER['REQUEST_URI']);             // remove query string
		if (@$_SERVER["PATH_INFO"] && @$_SERVER["PATH_INFO"] != $scriptUri) { // remove PATH_INFO
			$escapedPathInfo = preg_quote($_SERVER['PATH_INFO'], '/');
			$scriptUri = preg_replace("/$escapedPathInfo$/", "", $scriptUri);
		}
		$_SERVER['SCRIPT_NAME'] = $scriptUri;
	}
	$_SERVER['PHP_'.'SELF'] = @$_SERVER['SCRIPT_NAME'];
	if (@$_SERVER["PATH_INFO"] == @$_SERVER['PHP_'.'SELF']) { $_SERVER["PATH_INFO"] = ""; }
	if (!@$_SERVER['DOCUMENT_ROOT']) {
		$_SERVER['DOCUMENT_ROOT'] = substr(__FILE__, 0, 0-strlen(@$_SERVER['SCRIPT_NAME']));
	}
	$_SERVER['DOCUMENT_ROOT'] .= "/"; // make sure we have trailing slash
	$_SERVER['DOCUMENT_ROOT'] = preg_replace('/[\\\\\/]+/', '/', @$_SERVER['DOCUMENT_ROOT']); // replace and collapse slashes
}
function displayInitErrors() {
	$errors = "";
	if (version_compare(phpversion(), REQUIRED_PHP_VERSION) < 0) {
		$errors .= "This program requires PHP v" .REQUIRED_PHP_VERSION. " or greater. You have PHP v" .phpversion(). " installed.<br/>\n";
		$errors .= "Please ask your server adminstrator to upgrade PHP to a newer version.<br/><br/>\n";
		die($errors);
	}
	$missingExtensions = "";
	if (!extension_loaded("gd"))    { $missingExtensions .= "This program requires the PHP 'GD' extension.<br/>\n"; }
	if ($missingExtensions) {
		$errors .= "$missingExtensions";
		$errors .= "Please ask your server administrator to install missing PHP extension(s).<br/><br/>\n";
	}
	if (!function_exists('imagecreatefromjpeg')) {
		$errors .= "PHP doesn't support jpegs (or thumbnails). Please ask your server administrator to recompile PHP with jpeg support.<br/>\n";
		$errors .= "<b>Server Administrators:</b> imagecreatefromjpeg() isn't defined.\n";
		$errors .= "Search these <a href='https://www.php.net/imagecreatefromjpeg'>comments</a> on php.net for 'undefined function' for more details.<br/><br/>\n";
	}
	if (ini_get('magic_quotes_runtime')) { $errors .= "Please disable the 'magic_quotes_runtime' setting in php.ini or with .htaccess.<br/>\n"; }
	if (ini_get('magic_quotes_sybase'))  { $errors .= "Please disable the 'magic_quotes_sybase' setting in php.ini or with .htaccess.<br/>\n"; }
	if (ini_get('safe_mode'))            { $errors .= "Please disable the 'safe_mode' setting in php.ini.<br/>\n"; }
	if ($errors) { die($errors); }
}
function renameAndRemoveDefaultFiles() {
	$defaultFiles = array('data/settings.dat.php');
	$programDir   = _getProgramDir();
	foreach ($defaultFiles as $targetFile) {
		$targetPath   = "$programDir/$targetFile";
		$defaultPath  = "$targetPath.default";
		if (!file_exists($defaultPath)) {
			continue;
		}

		if (!file_exists($targetPath)) {
			rename($defaultPath, $targetPath) || die("Error renaming '$defaultPath' : $php_errormsg!  Make sure this file and it's directory are writable!");
		}
		else {
			unlink($defaultPath) || die("Error deleting '$defaultPath' : $php_errormsg! Make sure this file and it's directory are writable!");
		}
	}

}
function setRequestGlobal() {

	$_REQUEST = $_POST + $_GET;

	// define form
	if (@$_SERVER['PATH_INFO']) { // add form values from PATH_INFO (example app.php/name-value/city-Vancouver/)
		$pairs = explode("/", $_SERVER['PATH_INFO']);
		foreach ($pairs as $pair) {
			if (!$pair) { continue; } // skip blank
			@list($encodedName, $encodedValue) = explode('-', $pair, 2);
			$name  = urldecode($encodedName);
			$value = urldecode($encodedValue);
			if (array_key_exists($name, $_REQUEST)) { continue; } // skip if already defined in GET/POST
			$_REQUEST[$name] = $value;
		}
	}

}
function setProgramGlobals() {
	global $TABLE_PREFIX, $PROGRAM_DIR, $APP, $SETTINGS, $MENU_OPTIONS;
	$settingsPath = _getProgramDir() . "/data/settings.dat.php";

	// set globals
	$SETTINGS      = loadINI($settingsPath);
	$TABLE_PREFIX  = $SETTINGS['mysql']['tablePrefix'];
	$PROGRAM_DIR   = _getProgramDir();
	$MENU_OPTIONS  = array();

	$APP  = array(
		'version'           => '1.26',
		'build'             => '1',
		'id'                => '19',
		'key'               => '35809',
		'datadir'           => "$PROGRAM_DIR/data/",
		'pluginsdir'        => "$PROGRAM_DIR/lib/plugins",
		'settingsFilepath'  => $settingsPath,  // set this ABOVE and BELOW!
	);


}
function displaySocketErrors() {

	if (!$GLOBALS['SETTINGS']['isInstalled']) {
		$handle = @fsockopen("tcp://www.google.com", 80, $errno, $errstr, 5);
		if (!$handle) {
			$errors  = "Error: PHP fsockopen() and/or network connectivity disabled!<br/>\n";
			$errors .= "Test connection to www.google.com return error #" .@$errno. " - " .@$errstr. "<br/><br/>\n";
			$errors .= "Please ask your server administrator to enable network connectivity for PHP.<br/><br/>\n";
			die($errors);
		}
	}

}
function _getProgramDir() {
	static $programDir;

	if (!$programDir) {
		$programDir = pathinfo(pathinfo(__FILE__, PATHINFO_DIRNAME), PATHINFO_DIRNAME);

		$isUncPath  = substr($programDir, 0, 2) == '\\\\';
		$programDir = preg_replace('/[\\\\\/]+/', '/', $programDir); // replace and collapse slashes
		if ($isUncPath) { $programDir = substr_replace($programDir, '\\\\', 0, 1); } // replace UNC prefix
	}

	return $programDir;
}
?>
