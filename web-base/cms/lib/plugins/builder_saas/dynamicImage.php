<?
require_once __DIR__."/../../init.php";
require_once __DIR__."/../../database_functions.php";
require_once __DIR__."/../../admin_functions.php";
$current_user = getCurrentUserAndLogin();
$GLOBALS["CURRENT_USER"] = $current_user;

connectToMySQL();

header("Cache-Control: max-age=2592000");
header("Pragma: cache");

function noImage(){
    $my_img = imagecreate( 80, 80 );
    $background = imagecolorallocate( $my_img, 220, 220, 220 );
    $text_colour = imagecolorallocate( $my_img, 130, 130, 130 );

    imagestring( $my_img, 4, 28, 30, "...", $text_colour );
    imagesetthickness ( $my_img, 5 );


    header( "Content-type: image/png" );
    imagepng( $my_img );
    imagecolordeallocate( $text_color );
    imagecolordeallocate( $background );
    imagedestroy( $my_img );
}
if (@$_REQUEST["thumburl"]){
    
    $image =  @file_get_contents(API::getThumb(base64_decode($_REQUEST["thumburl"]),@$_REQUEST["width"] ? : 180));
    
    if (@$image){
        $typeString = null;
        $typeInt = exif_imagetype(base64_decode($_REQUEST["thumburl"]));
        switch($typeInt) {
          case 1:
            $typeString = 'image/gif';
            break;
          case 2:
            $typeString = 'image/jpg';
            break;
          case 3:
            $typeString = 'image/png';
            break;
          default: 
        }
        if ($typeString){
            header( "Content-type: ".$typeString );
            die($image);
        }
    }
}
if (@$_REQUEST["external"]){
    header('Access-Control-Allow-Origin: *');
    header( "Content-type: image/png" );
    die(file_get_contents($_REQUEST["external"]));
}

if (!@$_REQUEST["url"] || !@$_REQUEST["domain"] || !@$_REQUEST["module"]){
    die(noImage());
}
$url = base64_decode($_REQUEST["domain"]).base64_decode(@$_REQUEST["url"])."?pruebas=1&onlyModule=".base64_decode($_REQUEST["module"]);

$hash = md5($url);
$hash2 = md5(base64_decode($_REQUEST["module"]));

if (@$_REQUEST["uploadFoto"]){
    
    $target_file = __DIR__."/images/cache/".$hash2.".svg";
    if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
        unlink(__DIR__."/images/cache/".$hash.".jpg");
        die(1);
    }else{
        die(0);
    }
    
}

if (@$_REQUEST["refresh"]){
    unlink(__DIR__."/images/cache/".$hash.".jpg");
}
if (@$_REQUEST["seeUrl"]){
    die($url);
}

if (file_exists(__DIR__."/images/cache/".$hash2.".svg")){
    header( "Content-type: image/svg+xml" );
    die(file_get_contents(__DIR__."/images/cache/".$hash2.".svg"));
}else if (file_exists(__DIR__."/images/cache/".$hash.".jpg")){
    header( "Content-type: image/jpeg" );
    die(file_get_contents(__DIR__."/images/cache/".$hash.".jpg"));
}else if (file_exists(__DIR__."/images/cache/".$hash.".svg")){
    header( "Content-type: image/svg+xml" );
    die(file_get_contents(__DIR__."/images/cache/".$hash.".svg"));
}else{
    $external_link = base64_decode($_REQUEST["domain"])."/template/estandar/modulos/".base64_decode($_REQUEST["module"])."/thumbnail.jpg";
    if (@getimagesize($external_link)) {
        header( "Content-type: image/jpeg" );
        $jpeg = file_get_contents($external_link);
        file_put_contents(__DIR__."/images/cache/".$hash.".jpg", $jpeg);
        die($jpeg);
    }
    
    $external_link = base64_decode($_REQUEST["domain"])."/template/estandar/modulos/".base64_decode($_REQUEST["module"])."/thumbnail.png";
    $resultExternal = @file_get_contents($external_link);
    if (@$resultExternal) {
        header( "Content-type: image/png" );
        $jpeg = $resultExternal;
        die($jpeg);
    }
    $external_link = base64_decode($_REQUEST["domain"])."/template/estandar/modulos/".base64_decode($_REQUEST["module"])."/thumbnail.svg";
    $resultExternal = @file_get_contents($external_link);
    if (@$resultExternal) {
        header( "Content-type: image/svg+xml" );
        $jpeg = $resultExternal;
        die($jpeg);
    }
    
    $apikey = "abb7a37f62e014373a6fa48eec3083a3e9ba32fb643d";
    $fetchUrl = "https://api.thumbnail.ws/api/".$apikey ."/thumbnail/get?url=".urlencode($url)."&width=100";
    $jpeg = file_get_contents($fetchUrl);
    file_put_contents(__DIR__."/images/cache/".$hash.".jpg", $jpeg);
                    
    header( "Content-type: image/jpeg" );
    die(file_get_contents(__DIR__."/images/cache/".$hash.".jpg"));
}
?>