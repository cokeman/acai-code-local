<?
if (!function_exists("color_luminance")) {
	function color_luminance( $hex, $percent ) {
		$hex = preg_replace( '/[^0-9a-f]/i', '', $hex );
		$new_hex = '#';

		if ( strlen( $hex ) < 6 ) {
			$hex = $hex[0] + $hex[0] + $hex[1] + $hex[1] + $hex[2] + $hex[2];
		}

		// convert to decimal and change luminosity
		for ($i = 0; $i < 3; $i++) {
			$dec = hexdec( substr( $hex, $i*2, 2 ) );
			$dec = min( max( 0, $dec + $dec * $percent ), 255 );
			$new_hex .= str_pad( dechex( $dec ) , 2, 0, STR_PAD_LEFT );
		}
		return $new_hex;
	}
}

$config = $value["config"];
$colorBase = $config["color_base"];
$colorBaseDark = strpos($colorBase, "#") === 0 ? color_luminance($colorBase, -0.1) : $colorBase;
$colorBaseLight = strpos($colorBase, "#") === 0 ? color_luminance($colorBase, 0.1) : $colorBase;
$colorEnlaces = $config["color_enlaces"];
$colorWarning = @$config["color_warning"];
$colorSuccess = @$config["color_success"];
$colorDanger = @$config["color_danger"];
$colorInfo = @$config["color_info"];
?>
<style>
#page-container,
#sidebar,
#sidebar-alt,
.navbar.navbar-default {
	background:none !important;
    background-color: <?=$colorBase;?> !important;
}
pixie-editor toolbar{background:none;background-color: <?=$colorBase;?> !important;}
.sidebar-nav a.active{border-left-color: <?=$colorBaseLight;?> !important;}
.nav.navbar-nav-custom > li.open > a, .nav.navbar-nav-custom > li > a:hover, .nav.navbar-nav-custom > li > a:focus{background-color: <?=$colorBaseLight;?> !important;}
.text-primary, .text-primary:hover, a, a:hover, a:focus, a.text-primary, a.text-primary:hover, a.text-primary:focus{color: <?=$colorBaseDark;?>;}
.sidebar-nav a, .nav.navbar-nav-custom > li > a, .sidebar-user-links a{color: <?=$colorEnlaces;?> !important;}
.select2-container-multi .select2-choices .select2-search-choice{background-color: <?=$colorBase;?> !important; color: <?=$colorEnlaces;?> !important;}
.sidebar-header .sidebar-header-title, .sidebar-user-name, .navbar.navbar-default h4{color: <?=$colorEnlaces;?> !important;}
.switch-primary input:checked + span{background-color: <?=$colorBase;?> !important;}
.switch-primary span{border-color: <?=$colorBase;?> !important;}

.btn.btn-primary:not(.btn-alt):not(label){background:none !important;background-color: <?=$colorBase;?> !important; border-color: <?=$colorBase;?>}
.btn.btn-primary:not(.btn-alt):not(label):hover, .btn.btn-primary:not(label):not(.btn-alt):focus{background:none !important;background-color: <?=$colorBaseLight;?> !important; border-color: <?=$colorBaseLight;?>}
.btn.btn-primary:not(.btn-alt):not(label):active{background:none !important;background-color: <?=$colorBaseDark;?> !important; border-color: <?=$colorBaseDark;?>}

<? if (@$colorWarning) {?>
.btn.btn-warning:not(.btn-alt){background-color: <?=$colorWarning;?> !important; border-color: <?=$colorWarning;?>}
.btn.btn-warning:not(.btn-alt):hover, .btn.btn-warning:not(.btn-alt):focus{background-color: <?=color_luminance($colorWarning, 0.08);?> !important; border-color: <?=color_luminance($colorWarning, 0.08);?>}
.btn.btn-warning:not(.btn-alt):active{background-color: <?=color_luminance($colorWarning, -0.08);?> !important; border-color: <?=color_luminance($colorWarning, -0.08);?>}
<? }?>
<? if (@$colorSuccess) {?>
.btn.btn-success:not(.btn-alt){background-color: <?=$colorSuccess;?> !important; border-color: <?=$colorSuccess;?>}
.btn.btn-success:not(.btn-alt):hover, .btn.btn-success:not(.btn-alt):focus{background-color: <?=color_luminance($colorSuccess, 0.08);?> !important; border-color: <?=color_luminance($colorSuccess, 0.08);?>}
.btn.btn-success:not(.btn-alt):active{background-color: <?=color_luminance($colorSuccess, -0.08);?> !important; border-color: <?=color_luminance($colorSuccess, -0.08);?>}
<? }?>
<? if (@$colorDanger) {?>
.btn.btn-danger:not(.btn-alt){background-color: <?=$colorDanger;?> !important; border-color: <?=$colorDanger;?>}
.btn.btn-danger:not(.btn-alt):hover, .btn.btn-danger:not(.btn-alt):focus{background-color: <?=color_luminance($colorDanger, 0.08);?> !important; border-color: <?=color_luminance($colorDanger, 0.08);?>}
.btn.btn-danger:not(.btn-alt):active{background-color: <?=color_luminance($colorDanger, -0.08);?> !important; border-color: <?=color_luminance($colorDanger, -0.08);?>}
<? }?>
<? if (@$colorInfo) {?>
.btn.btn-info:not(.btn-alt){background-color: <?=$colorInfo;?> !important; border-color: <?=$colorInfo;?>}
.btn.btn-info:not(.btn-alt):hover, .btn.btn-info:not(.btn-alt):focus{background-color: <?=color_luminance($colorInfo, 0.08);?> !important; border-color: <?=color_luminance($colorInfo, 0.08);?>}
.btn.btn-info:not(.btn-alt):active{background-color: <?=color_luminance($colorInfo, -0.08);?> !important; border-color: <?=color_luminance($colorInfo, -0.08);?>}
<? }?>
</style>
