<?
require_once __DIR__."/../../../viewer_functions.php";
if (@$_REQUEST["url"]){
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
            
        }
        if (@$resultCSS["css"]) {
            $resultCSS["css"] = base64_decode($resultCSS["css"]);
            $resultCSS["css"] = str_replace('url(images','url('.$_REQUEST["url"].'/images',$resultCSS["css"]);
            $resultCSS["css"] = str_replace("url('images","url('".$_REQUEST["url"]."/images",$resultCSS["css"]);
            $resultCSS["css"] = str_replace("url('/images","url('".$_REQUEST["url"]."/images/",$resultCSS["css"]);
            $resultCSS["css"] = str_replace("url(/images","url(".$_REQUEST["url"]."/images/",$resultCSS["css"]);
            
        }
    }
}
?>
<html>
    <head>
        <!-- EDITOR DE CODIGO -->
        <script type="text/javascript" src="/lib/jquery.js"></script>
        <script src="<?=h("/js/ace3/ace.js");?>"></script>
        <script src="https://cloud9ide.github.io/emmet-core/emmet.js"></script>
        
        <script src="<?=h("/js/ace3/theme-monokai.js");?>"></script>
        
        
        
        <script src="<?=h("/js/ace3/mode-html.js");?>"></script>
        

        <script src="<?=h("/js/vendor/sweetalert2.min.js");?>" async defer></script>
        <script src="<?=h("/js/ace3/ext-emmet.js");?>"></script>
    </head>
    <body>
        <div class="wrapper">
            <div class="grid-container">
                <div id="editorHTML"><?=@htmlentities($resultHTML["html"]);?></div>
                <div id="editorJS"></div>
                <div id="editorCSS"><?=@$resultCSS["css"];?></div>
            </div>
            <div class="result"></div>
        </div>
        <style>
            body{margin:0px;padding:0px;}
            .wrapper{display:flex;flex-direction: column;height:100vh;width:100vw;background-color:#43484b}
            .grid-container{height:100%; display:flex;margin:5px;}
            .result{height:100%;max-height:50vh;background-color:#fff;margin:10px;position: relative;}
            #editorHTML,#editorJS,#editorCSS{height:100%;width:100%;position:relative;margin:5px;}
            .result iframe{border:solid 1px;width:100%;height:100%;}
            <?
            if (@$resultHTML["html"]){
                ?>
                .grid-container{}
                .result{max-height:75vh;}
                <?
            }
            ?>
        </style>
        <script>
            var editorHTML = ace.edit("editorHTML");
            editorHTML.setTheme("ace/theme/monokai");
            editorHTML.session.setMode("ace/mode/html");
            editorHTML.setOption("enableEmmet", true);

            var editorJS = ace.edit("editorJS");
            editorJS.setTheme("ace/theme/monokai");
            editorJS.session.setMode("ace/mode/javascript");

            var editorCSS = ace.edit("editorCSS");
            editorCSS.setTheme("ace/theme/monokai");
            editorCSS.session.setMode("ace/mode/css");
            
            <? if (!@$resultHTML["html"]){?>
                var localDataString = localStorage.getItem("localData");
                var localData = {};

                if (localDataString){
                    localData = JSON.parse(localDataString);
                    editorHTML.setValue(localData.html);
                    editorCSS.setValue(localData.css);
                    editorJS.setValue(localData.js);
                    paint();
                }
            <? }else{?>
                var localDataString = localStorage.getItem("localData");
                var localData = {};

                if (localDataString){
                    localData = JSON.parse(localDataString);
                    localData.html = editorHTML.getValue();
                    localData.css = editorCSS.getValue();
                    editorJS.setValue(localData.js);
                    paint();
                }
            <? }?>
            
            function processData(){
                localData = {
                    html:editorHTML.getValue(),
                    css:editorCSS.getValue(),
                    js:editorJS.getValue()
                }
                
                localStorage.setItem("localData",JSON.stringify(localData));
                console.log("GUARDADO");
                
                paint();
            }
            function paint(){
                var result = document.querySelector(".result");
                var iframe = document.createElement("iframe");
                iframe.srcdoc = `${localData.html}<style>${localData.css}</style><script>${localData.js}<\/script>`;
                iframe.sandbox = "allow-scripts allow-modals allow-popups allow-same-origin allow-top-navigation allow-pointer-lock allow-forms";
                result.innerHTML = "";
                result.appendChild(iframe);
            }

            window.onkeydown = function(e){
                var evtobj = window.event? event : e;
                
                if (evtobj.keyCode == 83 && (evtobj.ctrlKey || evtobj.metaKey)) {
                    e.preventDefault();
                    e.stopPropagation();
                    processData();
                    return false;
                }
            }
            
        </script>
    </body>
</html>