<?
require_once "variables.php";
require_once "FileJoiner.php";

$fileType = "css";
switch (@$_REQUEST["fileType"]) {
    case "css":
        $files = array(
            "..".RUTA_PLANTILLA."/css/bootstrap.min.css",
            "..".RUTA_PLANTILLA."/css/bxslider.min.css",
            "..".RUTA_PLANTILLA."/css/swipebox.min.css",
            "..".RUTA_PLANTILLA."/css/animate.css",
            "..".RUTA_PLANTILLA."/css/rrssb.css",
            "..".RUTA_PLANTILLA."/css/cesta.css",
            "..".RUTA_PLANTILLA."/style-base.css",
            "..".RUTA_PLANTILLA."/style.css"
        );
        $fileName = "..".RUTA_PLANTILLA."/css/main.css";
        break;
    case "js":
        $fileType = "js";
        $files = array(
            "..".RUTA_PLANTILLA."/js/bootstrap.min.js",
            "..".RUTA_PLANTILLA."/js/bxslider.min.js",
            "..".RUTA_PLANTILLA."/js/jquery.swipebox.min.js",
            "..".RUTA_PLANTILLA."/js/wow.min.js",
            "..".RUTA_PLANTILLA."/js/rrssb.min.js",
            "..".RUTA_PLANTILLA."/js/fetch.js",
            "..".RUTA_PLANTILLA."/js/lazy-loader.js",
            "..".RUTA_PLANTILLA."/js/liveSearchModal.js",
            "..".RUTA_PLANTILLA."/js/cesta_class.js",
            "..".RUTA_PLANTILLA."/js/mis-scripts.js"
        );

        $fileName = "..".RUTA_PLANTILLA."/js/main.js";
        break;
    default: die("");
}
$fj = new FileJoiner($files, $fileType, @$fileName);
header($fj->getContentType());
die($fj->getFile());
?>