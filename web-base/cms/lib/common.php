<?php
global $cachedSchemas;
$cachedSchemas = [];
function protocol() {
	return isHTTPS() ? "https" : "http";
}
function isHTTPS() {
	return
		(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
		|| $_SERVER['SERVER_PORT'] == 443;
}
function loadPlugins() {
	// load plugins
	if (file_exists($GLOBALS["APP"]["pluginsdir"]."/index.php")){
		require_once $GLOBALS["APP"]["pluginsdir"]."/index.php";
	}
}
function loadINI($filepath) {
	global $cachedSchemas;
	if(isset($cachedSchemas[$filepath])) return $cachedSchemas[$filepath];
	// error checking
	if (!file_exists($filepath)) { die("Error: Couldn't find ini file '$filepath'"); }

	// load ini file
	$iniValues = @parse_ini_file($filepath, true);
	if (@$php_errormsg) { die(__FUNCTION__ . ": $php_errormsg"); }
	$iniValues = _decodeIniValues($iniValues);

	$cachedSchemas[$filepath] = $iniValues;
	return $iniValues;
}
function saveINI($filepath, $array, $sortKeys = 0) {
	$globals  = '';
	$sections = '';
	$invalidKeyRegexp   = '/[^a-zA-Z0-9\-\_\.]/i'; # dis-allowed chars[{}|&~![()"] (from https://ca.php.net/parse_ini_file)
	$filename = pathinfo($filepath, PATHINFO_BASENAME);

	// encode values
	$array = _encodeIniValues($array);

	### get ini data
	if ($sortKeys) { ksort($array); }
	foreach ($array as $key => $value) {

		# save sub-sections
		if (is_array($value)) {
			$sections .= "\n[{$key}]\n";

			$childArray = $value;
			foreach ($childArray as $childKey => $childValue) {
				if (preg_match($invalidKeyRegexp, $childKey)) { die("Error: Invalid character(s) in key '".htmlspecialchars($childKey)."', only the following chars are allowed in key names: a-z, A-Z, 0-9, -, _, . Filename: $filename"); }

				$needsQuotes = !is_numeric($childValue) && !is_bool($childValue);
				if ($needsQuotes) { $sections .= "$childKey = \"$childValue\"\n"; }
				else              { $sections .= "$childKey = $childValue\n"; }
			}
		}

		# save global keys
		else {
			if (preg_match($invalidKeyRegexp, $key)) { die("Error: Invalid character(s) in key '".htmlspecialchars($key)."', the following chars aren't allowed in key names: a-z, A-Z, 0-9, -, _, . Filename: $filename"); }

			$needsQuotes = !is_numeric($value) && !is_bool($value);
			if ($needsQuotes) { $globals .= "$key = \"$value\"\n"; }
			else              { $globals .= "$key = $value\n"; }
		}

	}

	# create ini content
	$content = ";<?php die('This is not a program file.'); exit; ?>\n\n";  # prevent file from being executed
	$content .= $globals;
	$content .= $sections;

	# error checking

	# save ini file
	if (file_exists($filepath) && !is_writable($filepath)) { die("Error writing to '$filepath'!<br/>\nFile isn't writable, check permissions!"); }

	file_put_contents($filepath, $content) || die("Error writing to '$filepath'!");
}
function _encodeIniValues($array) {
	static $matches      = array("\\",   "\n",  "\r", '"');
	static $replacements = array('\\\\', '\\n', '',   '\\q');

	foreach (array_keys($array) as $key) {
		$value = &$array[$key];
		if (!@$value) continue;
		if (is_array($value)) { $value = _encodeIniValues($value); }
		else                  { $value = str_replace($matches, $replacements, $value); }
	}

	return $array;
}
function _decodeIniValues($array) {
	static $replacements = array('\\\\' => "\\", '\\n' => "\n", '\\q' => '"');
	static $replaceCode  = "array_key_exists('\\1', \$replacements) ? \$replacements['\\1'] : '\\1';";
	$serializedData = serialize($array);
	if (!preg_match("/\\\\\\\\|\\\\n|\\\\q/", $serializedData)) { return $array; }
	foreach (array_keys($array) as $key) {
		$value = &$array[$key];
		if (is_array($value)) { 
			$value = _decodeIniValues($value); 
		}else{ 
			$value = preg_replace_callback('|(\\\\.)|', function($m){ 
				return str_replace("\\q","\"",str_replace("\\n","\n",str_replace("\\\\","\\",$m[1]))); 
			}, $value); 
		}
	}
	return $array;
}
function getAdjustedLocalTimeOffsetSeconds() {
	global $SETTINGS;
	if (function_exists('date_default_timezone_set')) {
		return 0;
	}

	$secondsOffset = 0;
	$secondsOffset += @$SETTINGS['timezoneOffsetHours'] * 60*60;
	$secondsOffset += @$SETTINGS['timezoneOffsetMinutes'] * 60;
	$secondsOffset  = @$SETTINGS['timezoneOffsetAddMinus'] . $secondsOffset;

	return $secondsOffset;
}
function convertSecondsToTimezoneOffset($seconds) {

	$offsetAddMinus = ($seconds < 0) ? '-' : '+';
	$offsetHours    = (int) abs($seconds / (60*60));
	$offsetMinutes  = (int) abs(($seconds % (60*60)) / 60);
	$offsetString   = sprintf("%s%02d:%02d", $offsetAddMinus, $offsetHours, $offsetMinutes);

	return $offsetString;
}
function getEvalOutput($string) {
	global $TABLE_PREFIX, $ESCAPED_FILTER_VALUE;

	//
	$containsNoPHPCode = (strpos($string,"php") === false);
	if($containsNoPHPCode) { return $string; }

	// eval php code
	ob_start();
	eval('?>' . $string);
	$output = ob_get_clean();

	return $output;

}
function saveResampledImageAs($targetPath, $sourcePath, $maxWidth, $maxHeight) {
	global $SETTINGS;

	// create target dir
	$dir = dirname($targetPath);
	if (!file_exists($dir)) {
		try{
			mkdir_recursive($dir);
		}catch(Exception $e){
			throw new Exception("Error creating dir '" .htmlspecialchars($dir). "'.  Check permissions or try creating directory manually.");	
		}
		
	}

	// open source image
	$sourceImage = null;
	list($sourceWidth, $sourceHeight, $imageType) = getimagesize($sourcePath);

	// get new height/width
	$widthScale   = $maxWidth / ($sourceWidth?:1);
	$heightScale  = $maxHeight / ($sourceHeight?:1);

	$scaleFactor  = min($widthScale, $heightScale, 1);  # don't scale above 1:1
	$targetHeight = ceil($sourceHeight * $scaleFactor); # round up
	$targetWidth  = ceil($sourceWidth * $scaleFactor);  # round up

	if ($scaleFactor == 1) {
		try{ copy($sourcePath, $targetPath); }catch(Exception $e){ throw new Exception(__FUNCTION__ . ": error copying image '$sourcePath' - $php_errormsg"); }
		return array($sourceWidth, $sourceHeight);
	}

	// create new image
	switch($imageType) {
		case IMAGETYPE_JPEG: $sourceImage = imagecreatefromjpeg($sourcePath); break;
		case IMAGETYPE_GIF:  $sourceImage = imagecreatefromgif($sourcePath); break;
		case IMAGETYPE_PNG:  $sourceImage = imagecreatefrompng($sourcePath); break;
		default:             throw new Exception(__FUNCTION__ . ": Unknown image type!"); break;
	}
	if (!$sourceImage) { throw new Exception("Error opening image file!"); }
	$targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

	// save transparency - based on code from: https://ca3.php.net/manual/en/function.imagecolortransparent.php#80935
	if($imageType == IMAGETYPE_GIF) {
		$transparentIndex = imagecolortransparent($sourceImage);
		$transparentColor = @imagecolorsforindex($sourceImage, $transparentIndex);
		if ($transparentColor) {
			$newTransparentIndex = imagecolorallocate($targetImage, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);
			imagefill($targetImage, 0, 0, $newTransparentIndex);
			imagecolortransparent($targetImage, $newTransparentIndex);
		}
	}
	else if ($imageType == IMAGETYPE_PNG) {
		imagealphablending($targetImage, false);
		$transparentColor = imagecolorallocatealpha($targetImage, 0, 0, 0, 127);
		imagefill($targetImage, 0, 0, $transparentColor);
		imagesavealpha($targetImage, true);
	}

	// resample image
	try{ imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight); }catch(Exception $e){ throw new Exception("There was an error resizing the uploaded image!"); }

	// save target image
	$savedFile = false;
	switch($imageType) {
		case IMAGETYPE_JPEG: $savedFile = imagejpeg($targetImage, $targetPath, $SETTINGS['advanced']['imageResizeQuality']); break;
		case IMAGETYPE_GIF:  $savedFile = imagegif($targetImage, $targetPath); break;
		case IMAGETYPE_PNG:  $savedFile = imagepng($targetImage, $targetPath); break;
		default:             throw new Exception(__FUNCTION__ . ": Unknown image type!"); break;
	}
	if (!$savedFile) { throw new Exception("Error saving file!"); }
	imagedestroy($sourceImage);
	imagedestroy($targetImage);

	//
	return array($targetWidth, $targetHeight);

}
?>
