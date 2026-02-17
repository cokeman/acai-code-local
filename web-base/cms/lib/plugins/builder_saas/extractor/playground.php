<?
require_once __DIR__."/../../../viewer_functions.php";
if (@$_REQUEST["url"]){
    $cachefile = __DIR__."/html_cache/".md5($_REQUEST["url"]).".html";
    
    
    $resultHTML = file_get_contents("https://node.cocosolution.com/extract?type=html&url=".@$_REQUEST["url"]);
    $resultCSS = file_get_contents("https://node.cocosolution.com/extract?type=css&url=".@$_REQUEST["url"]);
    //$result = file_get_contents("https://node.cocosolution.com/extract");
    
    
    if (@$resultHTML || @$resultCSS){
        $resultHTML = json_decode($resultHTML,true);
        $resultCSS = json_decode($resultCSS,true);
        
        if (@$resultHTML["html"]) {
            $resultHTML["html"] = base64_decode($resultHTML["html"]);
            $resultHTML["html"] = str_replace('http://','https://',$resultHTML["html"]);
            $resultHTML["html"] = str_replace("url('/","url('".@$_REQUEST["url"]."/",$resultHTML["html"]);
            $resultHTML["html"] = str_replace("url(/","url(".@$_REQUEST["url"]."/",$resultHTML["html"]);
            $resultHTML["html"] = str_replace('data-src="/','data-src="'.@$_REQUEST["url"]."/",$resultHTML["html"]);
            /*$resultHTML["html"] = str_replace('href="','href="'.@$_REQUEST["url"],$resultHTML["html"]);
            $resultHTML["html"] = str_replace('src="','src="'.@$_REQUEST["url"],$resultHTML["html"]);
            $resultHTML["html"] = str_replace("url('","url('".@$_REQUEST["url"],$resultHTML["html"]);
            $resultHTML["html"] = str_replace('/one-page/..','',$resultHTML["html"]);
            $resultHTML["html"] = str_replace('http://','https://',$resultHTML["html"]);
            $resultHTML["html"] = str_replace('"images','"'.@$_REQUEST["url"].'images',$resultHTML["html"]);*/
            /*$resultHTML["html"] = str_replace('href="css','href="https://themes.semicolonweb.com/html/canvas/one-page/css',$resultHTML["html"]);
            $resultHTML["html"] = str_replace('href="../css','href="https://themes.semicolonweb.com/html/canvas',$resultHTML["html"]);
            $resultHTML["html"] = str_replace('src="images','src="https://themes.semicolonweb.com/html/canvas/one-page/images',$resultHTML["html"]);
            $resultHTML["html"] = str_replace('src="../images','src="https://themes.semicolonweb.com/html/canvas/images',$resultHTML["html"]);
            $resultHTML["html"] = str_replace('src="js','src="https://themes.semicolonweb.com/html/canvas/one-page/js',$resultHTML["html"]);
            $resultHTML["html"] = str_replace('src="../js','src="https://themes.semicolonweb.com/html/canvas/js',$resultHTML["html"]);
            $resultHTML["html"] = str_replace('href="../','https://themes.semicolonweb.com/html/canvas/',$resultHTML["html"]);*/
            $html = $resultHTML["html"];
            $html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);
            $html = preg_replace('#<script>(.*?)</script>#is', '', $html);
            $html = preg_replace('#<link(.*?)>#is', '', $html);
            $html = preg_replace('#<style(.*?)>(.*?)</style>#is', '', $html);
            $html = preg_replace('#<style>(.*?)</style>#is', '', $html);
            echo $html;
        }
        if (@$resultCSS["css"]) {
            $resultCSS["css"] = base64_decode($resultCSS["css"]);
            $resultCSS["css"] = str_replace('url(images','url('.$_REQUEST["url"].'/images',$resultCSS["css"]);
            $resultCSS["css"] = str_replace("url('images","url('".$_REQUEST["url"]."/images",$resultCSS["css"]);
            $resultCSS["css"] = str_replace("url('/images","url('".$_REQUEST["url"]."/images/",$resultCSS["css"]);
            $resultCSS["css"] = str_replace("url(/images","url(".$_REQUEST["url"]."/images/",$resultCSS["css"]);
            
            echo "<style data-type>";
            echo $resultCSS["css"];
            echo "</style>";
            
        }
        ?>
        
        <?
    }
}

?>