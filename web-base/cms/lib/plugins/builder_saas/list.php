<?

if (!function_exists("ordenarArray")){
    function ordenarArray ($toOrderArray, $field, $inverse = false) {
        $position = array();
        $newRow = array();
        foreach ($toOrderArray as $key => $row) {
            $position[$key]  = $row[$field];
            $newRow[$key] = $row;
        }
        if ($inverse) {
            arsort($position);
        }
        else {
            asort($position);
        }
        $returnArray = array();
        foreach ($position as $key => $pos) {
            $returnArray[] = $newRow[$key];
        }
        return $returnArray;
    }
}
if (@$_REQUEST["newOrder"]){
    require_once __DIR__."/../../classes/CocoDB.php";
    header("Content-type:application/json");
    $data = json_decode(file_get_contents('php://input'),true);
    $sibling = [];
    $schema = loadSchema($menu);
    
    if (@$schema["menuType"]!="category") $data = array_reverse($data);
    
    foreach($data as $cont => $value){
        $sql = "UPDATE ".$TABLE_PREFIX.$menu." SET num=num";
        
        switch($schema["menuType"]){
            case "multi":
                unset($data[$cont]["parentNum"]);
                unset($data[$cont]["lineage"]);
                unset($data[$cont]["depth"]);
                unset($data[$cont]["breadCrumb"]);
                $data[$cont]["dragSortOrder"] = $cont + 1;
                break;
            default:
                if (!isset($sibling[$value["parentNum"]])) 
                    $sibling[$value["parentNum"]] = 1; 
                else 
                    $sibling[$value["parentNum"]]+=1;

                $data[$cont]["globalOrder"] = $cont + 1;
                $data[$cont]["siblingOrder"] = $sibling[$value["parentNum"]];
        }
        
        foreach($data[$cont] as $key => $val){
            if ($key == "num") continue;
            $sql.=",".$key."='".$val."'";
        }
        $sql.=" WHERE num=".$value["num"]." LIMIT 1";
        mysql_query($sql) or die(mysql_error());
        //$result = CocoDB::updateRecords("apartados", $data, "num=".$value["num"]); 
        //var_dump($result);
    }
    //updateCategoryMetadata();
    
    die(json_encode($data));
}

if (isset($_REQUEST["builderHasReferences"])){
    header("Content-type:application/json");
    $data = json_decode(file_get_contents("php://input"),true);
    if (!@$data["pageNums"]) die(json_encode(["error" => 1]));
    if (!@$data["menu"]) die(json_encode(["error" => 1]));
    $sections = CocoDB::get($data["menu"],"num in (".join(",",$data["pageNums"]).")");
    if (!@$sections) die(json_encode(["error" => 1]));
    
    $existenReferencias = [];
    
    $sql = "
    SELECT DISTINCT TABLE_NAME,COLUMN_NAME 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE COLUMN_NAME IN ('builder')
        AND TABLE_SCHEMA='".$SETTINGS["mysql"]["database"]."'
    ";
    
    $result = mysql_query($sql) or die(mysql_error());
    $records = [];



    while($record = mysql_fetch_assoc($result)){
        $dataAux = mysql_query_fetch_all_assoc("SELECT num,enlace,builder FROM ".$record["TABLE_NAME"]);
        
        foreach($dataAux as $dat){
            $records[] = ["num" => $dat["num"],"builder" => $dat["builder"],"enlace" => $dat["enlace"],"menu" => str_replace($TABLE_PREFIX,"",$record["TABLE_NAME"])];
        }
    }
    
    foreach($sections as $section){
        if (!@$section["builder"]) continue;
        $builder = json_decode($section["builder"],true);
        foreach($builder as $modulo => $moduloData){
            
            if (@$moduloData["modulo"] && !@$moduloData["referenciada"]){
                
                foreach($records as $record){
                    if ($record["num"] == $section["num"] && $record["menu"] == $data["menu"]) continue;
                    
                    preg_match_all('|\"referenciada\"\:\"'.$data["menu"].'\|'.$section["num"].'\|?\d+?\"|',$record["builder"],$salida);
                    if (array_filter($salida,function($rec){ return @$rec; })) {
                        $existenReferencias[] = ["num" => $record["num"],"enlace" => "https://".$CURRENT_USER["domain"]["domain"].$record["enlace"], "enlace_seccion" => '/admin.php?menu='.$record["menu"] . '&num='.$record["num"]. '&action=edit'];
                    }
                }
            }
            
        }
    }
    
    die(json_encode(["result" => count($existenReferencias),"data" => $existenReferencias]));
}
if (@$_REQUEST["getPageThumbnail"]){
    /*header("Content-type:application/json");
    $apartados = mysql_query_fetch_all_assoc("select * from ".$TABLE_PREFIX.$menu." where num in(".mysql_real_escape_string($_REQUEST["getPageThumbnail"]).")");
    $apartados = array_map(function($rec) use($CURRENT_USER) {
        
        $rec["enlace"] = "https://".$CURRENT_USER["domain"]["domain"].$rec["enlace"]."?pruebas=1"; 
        $hashBuild = "";
        $cacheFile = md5($rec["enlace"].$hashBuild);
        if (@$rec["builder"]){
            $hashBuild = md5($rec["builder"]);
            $cacheFile = md5($cacheFile.$hashBuild);
            $rec["enlace"].="&hashBuild=".$hashBuild;
        }
        
        $pathCache = "uploads/webscreenshots/".$cacheFile.".jpg";
        
        
        $thumbnail = @file_get_contents(__DIR__."/../../../".$pathCache);
        if (!@$thumbnail){
            
            $jpeg = API::generateThumbnail($rec["enlace"]);
            
            if (@$jpeg["image"]) file_put_contents(__DIR__."/../../../".$pathCache, base64_decode($jpeg["image"]));
            
        }
        
        $rec["thumbnail"] = API::getThumb("https://".$_SERVER["HTTP_HOST"]."/".$pathCache,100); 
        
        return $rec;
    },$apartados);
    if (!@$apartados) die(json_encode([]));
    
    die(json_encode($apartados));*/
    die(json_encode([]));
}
if (@$_REQUEST["getPages"]){
    header("Content-type:application/json");
    $config = @mysql_query_fetch_all_assoc("SELECT * FROM ".$TABLE_PREFIX."configuracion LIMIT 1")[0];
    $listAux = [];
    foreach($listRecords as $record):
        $aliases = @mysql_query_fetch_all_assoc("SELECT * FROM ".$TABLE_PREFIX."alias_urls WHERE url_destino = '".$record["enlace"]."'");
        if (@$aliases) $record["aliases"] = array_map(function($rec){ return $rec["url_alias"]; },$aliases);

        $record["titulo_de_pagina"] = @$record["titulo_de_pagina"] ? : @$config["titulo_de_pagina"] ? : 'Sin Título de página disponible';
        $record["metatag_descripcion"] = @$record["metatag_descripcion"] ? : @$config["metatag_descripcion"] ? : 'Sin Meta Descripción disponible';
        $record["thumbnail"] = "lib/plugins/builder_saas/images/404.png";
        if (@$CURRENT_USER["domain"]["plugins"]["pychecker"][0]["valor"] == "1"){ $record["pychecker"] = 0;}
        addPlugins("list_record",$record);
        if (@$record["parentNum"]){
            foreach($listAux as $cont => $lAux){
                if ($lAux["num"] == $record["parentNum"]) $listAux[$cont]["children"][] = $record;
            }
        }else{
            $listAux[] = $record;    
        }
    endforeach;

    die(json_encode($listAux));
}
if (@$_REQUEST["getConfig"]){
    header("Content-type:application/json");
    die(json_encode(mysql_query_fetch_all_assoc("SELECT * FROM ".$TABLE_PREFIX."configuracion LIMIT 1")));
}