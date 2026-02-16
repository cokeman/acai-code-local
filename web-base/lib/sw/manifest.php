<?
require_once dirname(__FILE__)."/../../funciones.php";
header("Content-Type: application/json");
if (!@$configuracionRecord["usar_service_worker"]) die(json_encode(array()));
?>

{
    "short_name": "<?=addslashes($configuracionRecord["pwa_short_name"]);?>",
    "name": "<?=addslashes($configuracionRecord["tienda_nombre_empresa"]);?>",
    "icons": [
        {
            "src": "template/estandar/icons/launcher-icon-1x.png",
            "type": "image/png",
            "sizes": "48x48"
        },
        {
            "src": "template/estandar/icons/launcher-icon-2x.png",
            "type": "image/png",
            "sizes": "96x96"
        },
        {
            "src": "template/estandar/icons/launcher-icon-4x.png",
            "type": "image/png",
            "sizes": "192x192"
        },
        {
            "src": "template/estandar/icons/launcher-icon.png",
            "type": "image/png",
            "sizes": "512x512"
        }
    ],
    "start_url": "/",
    "background_color": "<?=$configuracionRecord["pwa_background_color"];?>",
    "theme_color": "<?=$configuracionRecord["pwa_theme_color"];?>",
    "display":"fullscreen",
    "orientation":"portrait"
}