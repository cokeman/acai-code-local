<?
require_once dirname(__FILE__)."/../sesion.php";
require_once dirname(__FILE__)."/../funciones.php";
if (!@$configuracionRecord['pagina_publicada'] && !@$_REQUEST['dev']) {
    http_response_code(404);
    die();
}
$dummy = [];
addPlugins('sitemap', $dummy);
$tablasEliminadas = array("cms_alias_urls");
$sql = "
    SELECT DISTINCT TABLE_NAME,COLUMN_NAME 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE COLUMN_NAME IN ('enlace')
        AND TABLE_SCHEMA='".$SETTINGS["mysql"]["database"]."'
    ";
$result = mysql_query($sql) or die(mysql_error());
$resultados = array();
$enlaces = array();
$idiomas = array();
while($record = mysql_fetch_assoc($result)){
    if (!in_array($record["TABLE_NAME"],$tablasEliminadas)){
        $res = mysql_query("SELECT * FROM ".$record["TABLE_NAME"]);
        while($rec = mysql_fetch_assoc($res)){
            $enlaceAnadido = false;
            if (@$rec['no_indexar_en_google']) continue;
            if ((isset($rec["visible"]) || isset($rec["oculto"])) && @$rec[$record["COLUMN_NAME"]]) {
                if ((@$rec["visible"]==1 || isset($rec["oculto"]) && !$rec["oculto"]) && !strpos("a".$rec[$record["COLUMN_NAME"]],"#")) {
                    if (compruebaWeb(@$rec[$record["COLUMN_NAME"]])){
                        $enlaces[$record["TABLE_NAME"]."-".$record["COLUMN_NAME"]."-".$rec["num"]][] = @$rec[$record["COLUMN_NAME"]];
                        $idiomas[$record["TABLE_NAME"]."-".$record["COLUMN_NAME"]."-".$rec["num"]][] = "es";
                        $fechas[$record["TABLE_NAME"]."-".$record["COLUMN_NAME"]."-".$rec["num"]][] = @$rec["updatedDate"];
                        $enlaceAnadido = true;
                    }
                }
            }else if (@$rec[$record["COLUMN_NAME"]]){
                if (!strpos("a".$rec[$record["COLUMN_NAME"]],"#")){
                    if (compruebaWeb(@$rec[$record["COLUMN_NAME"]])){
                        $enlaces[$record["TABLE_NAME"]."-".$record["COLUMN_NAME"]."-".$rec["num"]][] = @$rec[$record["COLUMN_NAME"]];
                        $idiomas[$record["TABLE_NAME"]."-".$record["COLUMN_NAME"]."-".$rec["num"]][] = "es";
                        $fechas[$record["TABLE_NAME"]."-".$record["COLUMN_NAME"]."-".$rec["num"]][] = @$rec["updatedDate"];
                        $enlaceAnadido = true;
                    }
                }
            }
            if ($enlaceAnadido){
                $enlaces = dameEnlacesIdiomas($enlaces,$record,$rec);
            }
        }
    }
}

function compruebaWeb($enlace){
    return 1;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, protocol()."://".$_SERVER["HTTP_HOST"].$enlace);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $respond = curl_exec ($ch);
    curl_close ($ch);
    //$wget = file_get_contents(protocol()."://".$_SERVER["HTTP_HOST"].$enlace);
    return $respond;
}
function dameEnlacesIdiomas($enlaces,$schema,$record){
    global $TABLE_PREFIX,$idiomas;
    $sql = "SELECT prefix,fieldValue FROM ".$TABLE_PREFIX."traducciones where tableName='".str_replace($TABLE_PREFIX,"",$schema["TABLE_NAME"])."' and recordNum='".$record["num"]."' and fieldName='".$schema["COLUMN_NAME"]."'";

    $res = mysql_query($sql);

    if (@$res){
        while($rec = mysql_fetch_assoc($res)){
            if (compruebaWeb(base64_decode($rec["fieldValue"]))){
                $idiomas[$schema["TABLE_NAME"]."-".$schema["COLUMN_NAME"]."-".$record["num"]][] = $rec["prefix"];
                $enlaces[$schema["TABLE_NAME"]."-".$schema["COLUMN_NAME"]."-".$record["num"]][] = base64_decode($rec["fieldValue"]);
            }
        }
    }
    return $enlaces;
}
/*
echo "<pre>";
print_r($fechas);
echo "</pre>";
die();*/

header("Content-type: text/xml");
echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';

foreach($enlaces as $key => $valores):
    
    foreach($valores as $cont => $valor):
        echo "<url>";
            echo "<loc>".protocol()."://".$_SERVER["HTTP_HOST"].$valor."</loc>";
            echo '<lastmod>'.date("Y-m-d",strtotime($fechas[$key][0])).'T'.date("H:i:s",strtotime($fechas[$key][0])).'+00:00</lastmod>';
            foreach($valores as $cont2 => $valor2):
                if ($cont2!=$cont){
                    echo '<xhtml:link rel="alternate" hreflang="'.$idiomas[$key][$cont2].'" href="'.protocol()."://".$_SERVER["HTTP_HOST"].$valor2.'"/>';
                    //echo "<link>".$idiomas[$key][$cont2]."</link>";
                }
            endforeach;
            echo '<changefreq>monthly</changefreq>';
        echo "</url>";
    endforeach;
    /*if (@$idiomas[$cont]!="es"){
        echo '<xhtml:link rel="alternate" hreflang="de" href="'.$enlace.'"/>';
    }else{
        echo "<loc>".$enlace."</loc>";
    }*/
    
endforeach;

echo '</urlset>';
die();
?>
<script>
    /*var listas = document.querySelectorAll("li");
    for (i=0;i<listas.length;i++){
        fetch('https://top-car-hire.com' + listas[i].innerHTML).then(function(response) {
            
            response.text().then(function(str){
                if (response.status==200){
                    str = str.toLowerCase();
                    if (str.indexOf("undefined ")!=-1){
                        console.log("UNDEFINED EN " + response.url);
                    }
                }else{
                    console.log("ERROR EN " + response.url);
                }
            });
        }).then(function(str) {
            
        });
    }*/
</script>