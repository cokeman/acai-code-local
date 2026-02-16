<?
global $TABLE_PREFIX;
global $newRecordValues;

$actionBD = $var ? "INSERT" : "UPDATE";
$schemaForPlugin = loadSchema($_REQUEST["menu"]);

if ($actionBD=="INSERT"){
    foreach($schemaForPlugin as $key => $value){
        if (@$value["type"] == "editor_password" && @$newRecordValues[$key]){
            $newRecordValues[$key] = sha1($newRecordValues[$key]);
        }
    }
}else{
    $prevRecord = @mysql_fetch_assoc(mysql_query("SELECT * FROM ".$TABLE_PREFIX.$_REQUEST["menu"]." WHERE num=".$_REQUEST["num"]." LIMIT 1"));
    
    
    foreach($schemaForPlugin as $key => $value){
        if (@$value["type"] == "editor_password" && @$newRecordValues[$key]){
            if ($prevRecord[$key] != $newRecordValues[$key]){
                $newRecordValues[$key] = sha1($newRecordValues[$key]);
            }
        }
    }
    
    
}

?>