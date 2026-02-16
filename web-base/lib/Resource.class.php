<?php
class Resource {
    static function link($path, $async = false, $hash = true) {
        $existMinimalCSSFile = array_filter(Module::$css,function($rec){ return strpos($rec,"cache") !== false; });
        if ($existMinimalCSSFile) $existMinimalCSSFile = array_values($existMinimalCSSFile);
        if (strpos($path,"cache") !== false && $existMinimalCSSFile) return;
        if (strpos($path,"tailwind") !== false && defined("USE_MIN_TAILWIND") && USE_MIN_TAILWIND) $path = str_replace("tailwind","cocotail",$path);        
        if (strpos($path,"tailwind") !== false && $existMinimalCSSFile) $path = $existMinimalCSSFile[0];
        if (strpos($path,"tailwind") == false && !$existMinimalCSSFile && @$_REQUEST["generateMinCss"]) return;
        $absolute = strpos($path, 'http') === 0 || strpos($path, '//') === 0;
        if ($absolute) $hash = false;

        if ($hash) $path = h($path);
        if ($async) {
            echo '<link rel="preload" as="style" href="'.$path.'" onload="this.onload=null;this.rel=\'stylesheet\'">
            <noscript><link rel="stylesheet" href="'.$path.'"></noscript>
            ';
        }
        else {
            echo '<link rel="stylesheet" href="'.$path.'">';
        }
    }
}