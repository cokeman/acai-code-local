<?
global $APP, $SETTINGS, $TABLE_PREFIX, $CURRENT_USER;

require_once dirname(__FILE__)."/../../init.php";
require_once dirname(__FILE__)."/../../user_functions.php";
require_once dirname(__FILE__)."/../../admin_functions.php";
require_once dirname(__FILE__)."/xls_writter.php";

$CURRENT_USER = getCurrentUserAndLogin();

if (!isset($CURRENT_USER["domains"])){
    die(json_encode(["error" => "Not domains for that user","code" => 403]));
}

connectToMysql();

$configPlugin = [];

if (@$CURRENT_USER["domain"]["plugins"]["csv_plugin"]){
    foreach($CURRENT_USER["domain"]["plugins"]["csv_plugin"] as $cont => $arrayValue){
        $configPlugin[$arrayValue["campo"]] = $arrayValue["valor"];
    }
}

if (file_exists(__DIR__."/schema.ini.php")){
    if ($CURRENT_USER["isSuperAdmin"]){
        $schemaPlugin = loadINI(__DIR__."/schema.ini.php");
        foreach($schemaPlugin["config"] as $key => $value){
            if (!isset($configPlugin[$key])) $configPlugin[$key] = $value;
        }
    }
}

$delimiter = @$configPlugin["separador"] ?: ";";

if (@$_REQUEST["exportCSVPlugin"]){
    $campos = @$_REQUEST["campos"];
    $seleccion = @$_REQUEST["seleccion"];
    $tabla = mysql_real_escape_string(@$_REQUEST["tabla"]);
    $exportar_todo = mysql_real_escape_string(@$_REQUEST["exportar_todo"]);

    header("Content-type:application/json");

    $result = array("result" => 0);
    
    if (!@$campos || !@$seleccion || @$tabla==""){
        die(json_encode($result));
    } else {
        if($exportar_todo == '1') {
            $data = mysql_query_fetch_all_assoc("SELECT ".join(",",$campos)." from ".$TABLE_PREFIX.$tabla." where num in(".join(",",$seleccion).") order by field(num,".join(",",$seleccion).")");
        } else {
            $data = mysql_query_fetch_all_assoc("SELECT ".join(",",$campos)." from ".$TABLE_PREFIX.$tabla);
        }
        
        $schema = loadSchema($tabla);
        
        foreach ($data as $i => $row) {
            foreach ($row as $key => $value) {
                switch(@$schema[$key]["type"]) {
                    case "list":
                        if ($schema[$key]["optionsType"]=="text") {
                            $options = explode("\n", $schema[$key]["optionsText"]);
                            foreach($options as $option) {
                                $sep = explode("|",$option);
                                if ($sep[0]==$value) {
                                    $data[$i][$key] = $sep[1];
                                }
                            }
                        } else if ($schema[$key]["optionsType"]=="table") {
                            $op_table = $schema[$key]["optionsTablename"];
                            $op_label = $schema[$key]["optionsLabelField"];
                            $op_value = $schema[$key]["optionsValueField"];
                            if (!is_array($value)) {
                                $all = array_filter(explode("\t", $value));
                            }else{
                                $all = $value;
                            }
                            $data[$i][$key] = "";
                            foreach($all as $a){
                                if ($a){
                                    $resultAA = @mysql_fetch_assoc(mysql_query("SELECT ".$op_label." FROM ".$TABLE_PREFIX.$op_table." WHERE ".$op_value." = '".$a."'"))[$op_label];
                                    $data[$i][$key].= $resultAA ? $resultAA."\n" : $a;
                                }
                            }
                        }
                        break;
                    case "multitext":
                        if ($value=="[]") $line[$key] = "";
                        if ($key=="datos") break;
                        if (!$value) break;
                        $lineSep = json_decode($value,true);
                        if (!@$lineSep) break;
                        $value2 = "";
                        foreach($lineSep as $va){
                            foreach($va as $ke => $val){
                                $esTabla = multiValorEsTabla($schema[$key],$ke,$va);
                                if (!@$esTabla){
                                    $value2.=strtoupper($ke)." : ".$val."\n";    
                                }else{
                                    $op_table = $esTabla[0];
                                    $op_label = $esTabla[1];
                                    $op_value = $esTabla[2];
                                    $value2.= strtoupper($ke)." : ".@mysql_fetch_assoc(mysql_query("SELECT ".$op_label." FROM ".$TABLE_PREFIX.$op_table." WHERE ".$op_value." = '".$val."'"))[$op_label]."\n";

                                }
                                
                            }
                            $value2.="\n";
                        }
                        $data[$i][$key] = $value2;
                        
                        break;
                    default:
                        $data[$i][$key] = $value;
                }
            }
        }
        
        array_unshift($data, $campos);
        
        $writer = new XLSXWriter();
        $writer->writeSheet($data);
        $writer->writeToFile('output.xlsx');

        $result["result"] = 1;
        $result["file"] = "output.xlsx";
        
        die(json_encode($result));
    }
}

if(@$_REQUEST["eliminarCsvAbierto"]){
    $files = glob('*.xlsx'); //busco ficheros csv
    foreach($files as $file){
        // if(is_file($file))
        //     unlink($file); //elimino el fichero
    }
    die();
}

function multiValorEsTabla($schema,$ke,$va){
    return false;
}

function array2csv($data, $delimiter = ',', $enclosure = '"', $escape_char = "\\")
{
    $f = fopen('php://memory', 'r+');
    foreach ($data as $item) {
        fputcsv($f, $item, $delimiter, $enclosure, $escape_char);
    }
    rewind($f);
    return stream_get_contents($f);
}