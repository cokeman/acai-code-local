<?
class CocoEnlace {
    function __construct(){}

    static function _seteaCampoEnlace($valores,$sufijoForzado="",$table = null,$generateAlias = true){
        global $tableName,$TABLE_PREFIX,$SETTINGS,$CURRENT_USER,$schema;

        if (!$table) $table = $tableName;
        
        $enlaceNuevo = "";
        $enlaceAnterior = "";

        // ESTABLECEMOS CUAL ES EL CAMPO DETERMINANTE 
        $campo = self::_getTitleField($valores,$table,$schema);

        if (@$valores["num"]){
            // ALMACENAMOS SI HAY UN REGISTRO ANTERIOR
            $registroAnterior = mysql_query("select * from ".$TABLE_PREFIX.$table." where num=".$valores["num"]);
            if($registroAnterior) {
                $registroAnterior = mysql_fetch_assoc(mysql_query("select * from ".$TABLE_PREFIX.$table." where num=".$valores["num"]));
                if (@$registroAnterior) $enlaceAnterior = $registroAnterior["enlace"];
            }
        }
        
        $prefijo = self::_getLinkPrefix($valores,$table);
        $prefijoAnterior = isset($registroAnterior) ? self::_getLinkPrefix($registroAnterior,$table) : null;

        $sufijo = "/";
        if (!@$valores["enlace"]){
            // SI NO HA ESCRITO NADA LO SETEAMOS POR DEFECTO
            $estado = "Si no ponemos enlace";
            $estadoNum = 0;
            $nohay = true;
            $valor = (@$campo&&@$valores[$campo]) ? "/".self::parsea_enlace($valores[$campo]) : "/".time();
            
            $enlaceNuevo = $prefijo.$valor.$sufijoForzado.$sufijo;
        }else{
            // SI EN CAMBIO HA ESCRITO ALGO VAMOS A VER QUE PASA
            $nohay = false;
            if (@$valores["preSaveTempId"]){
                // SI ES UN REGISTRO NUEVO LO DEJAMOS TAL CUAL
                $estado = "Si ponemos enlace pero es nuevo registro";
                $estadoNum = 1;
                $enlaceNuevo = $valores["enlace"];
            }else{
                // SI ES UN REGISTRO EXISTENTE VAMOS A COMPROBAR SI HA CAMBIADO ALGO

                if (@$registroAnterior){                
                    if ($valores["enlace"]!=$registroAnterior["enlace"]){
                        // SI EL ENLACE ES DISTINTO AL ANTERIOR LO SETEAMOS TAL CUAL
                        $estado = "Si ponemos enlace, registro existente, enlace distinto";
                        $estadoNum = 2;
                        $enlaceNuevo = $valores["enlace"];
                    }else if ($valores[$campo]!=$registroAnterior[$campo]){
                        // SI EL ENLACE ES IGUAL AL ANTERIOR COMPROBAMOS EL TITLE ( O CAMPO QUE SEA ) 
                        // A VER SI ES DISTINTO
                        if (strpos($valores["enlace"],self::parsea_enlace($registroAnterior[$campo]))){
                            // SI EL ANTIGUO TITLE TENIA RELACION CON EL ENLACE LO SISTITUIMOS
                            // POR EL NUEVO TITLE
                            $estado = "Si ponemos enlace, registro existente, title distinto con title anterior relacionado con el enlace anterior";
                            $estadoNum = 3;
                            $enlaceNuevo = str_replace(self::parsea_enlace($registroAnterior[$campo]),self::parsea_enlace($valores[$campo]),$valores["enlace"]);
                            $enlaceNuevo = preg_replace("|_([a-zA-z0-9]*)$|","",$enlaceNuevo);
                        }else{
                            // SI NO TENIA RELACION LO DEJAMOS TAL CUAL
                            $estado = "Si ponemos enlace, registro existente, title distinto con title anterior NO relacionado con el enlace anterior";
                            $estadoNum = 4;
                            $enlaceNuevo = $valores["enlace"];
                        }
                    }else{
                        $estado = "Si ponemos enlace, registro existente, enlace igual";
                        $estadoNum = 5;
                        $enlaceNuevo = $valores["enlace"];
                    }
                    
                    if (@$prefijo && @$prefijoAnterior && strpos($enlaceNuevo,$prefijoAnterior) !== false) {
                        // SI EL ANTIGUO CAMPO BREADCRUMB TENIA RELACION CON EL ENLACE LO SISTITUIMOS
                        // POR EL NUEVO VALOR DEL CAMPO BREADCRUMB
                        $estado = "Si ponemos enlace, registro existente, campo breadcrumb distinto con campo breadcrumb anterior relacionado con el enlace anterior";
                        $estadoNum = 7;
                        $enlaceNuevo = str_replace($prefijoAnterior,$prefijo,$enlaceNuevo);
                        $enlaceNuevo = preg_replace("|_([a-zA-z0-9]*)$|","",$enlaceNuevo);
                    }
                }else{
                    // SI NO ENCUENTRO EL REGISTRO ANTERIOR
                    $estado = "Si ponemos enlace, registro existente, pero no lo encontramos";
                    $estadoNum = 6;
                    $enlaceNuevo = $valores["enlace"];
                }
            }
        }

        $idiomasViejo = self::_getEnlaceIdiomas($valores,$enlaceNuevo,$enlaceAnterior,$estadoNum,$table);
        
        $idiomasNuevo = self::_seteaEnlaceIdiomas($valores,$enlaceNuevo,$enlaceAnterior,$estadoNum,$table);    

        /**************************/
        // YA TENEMOS EL CAMPO SETEADO AHORA VAMOS A COMPROBAR SI YA HAY UNO
        // AHORA COMPRUEBO SI HAY OTRO IGUAL EN LA BASE DE DATOS
        /**************************/

        $sql = "
        SELECT DISTINCT TABLE_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE COLUMN_NAME IN ('enlace')
            AND TABLE_SCHEMA='".$SETTINGS["mysql"]["database"]."'
        ";

        $result = mysql_query($sql) or die(mysql_error());

        while($record = mysql_fetch_assoc($result)){

            if ($record["TABLE_NAME"]==$TABLE_PREFIX.$table){
                $cadenaBusqueda = (@$valores["preSaveTempId"]) ? "num!=0" : "num!=".@$valores["num"];
                $encontrado = mysql_fetch_assoc(mysql_query("SELECT * FROM ".$record["TABLE_NAME"]." WHERE enlace='".$enlaceNuevo."' AND ".$cadenaBusqueda." LIMIT 1"));
            }else{
                $encontrado = mysql_fetch_assoc(mysql_query("SELECT * FROM ".$record["TABLE_NAME"]." WHERE enlace='".$enlaceNuevo."' LIMIT 1"));
            }
            if (@$encontrado){
                if ($nohay) {
                    $enlaceNuevo = self::_seteaCampoEnlace($valores,"_".base_convert(time(),10,36),$table,$generateAlias);
                }else{
                    $enlaceNuevo = $enlaceNuevo."_".base_convert(time(),10,36).$sufijo; // str_replace(".html","_".base_convert(time(),10,36).".html",$enlaceNuevo);
                }
            }
        }

        /**************************/
        // AHORA CREAMOS LOS ALIAS
        /**************************/
        if ($generateAlias){
            
            $pruebas = @mysql_fetch_assoc(mysql_query("SELECT pagina_publicada FROM ".$TABLE_PREFIX."configuracion LIMIT 1"));
            if (@$pruebas["pagina_publicada"]){
                $idiomasViejo["es"] = $enlaceAnterior;
                $idiomasNuevo["es"] = $enlaceNuevo;

                foreach($idiomasNuevo as $idioma => $idiomaNuevo){
                    if (isset($idiomasViejo[$idioma]) && isset($idiomasNuevo[$idioma]) && $idiomasViejo[$idioma] != $idiomasNuevo[$idioma]){
                        $preSQL = "num=null, createdDate='".date("Y-m-d H:i:s")."', updatedDate='".date("Y-m-d H:i:s")."', createdByUserNum=1, updatedByUserNum=1, dragSortOrder=".time();
                        mysql_query("DELETE FROM ".$TABLE_PREFIX."alias_urls where url_alias='".$idiomasNuevo[$idioma]."'");
                        mysql_query("DELETE FROM ".$TABLE_PREFIX."alias_urls where url_alias='".$idiomasViejo[$idioma]."' AND url_destino='".$idiomasNuevo[$idioma]."'");
                        mysql_query("INSERT INTO ".$TABLE_PREFIX."alias_urls set ".$preSQL.", url_alias='".$idiomasViejo[$idioma]."', url_destino='".$idiomasNuevo[$idioma]."'");
                        mysql_query("UPDATE ".$TABLE_PREFIX."alias_urls set url_destino='".$idiomasNuevo[$idioma]."' where url_destino='".$idiomasViejo[$idioma]."'");
                        mysql_query("DELETE FROM ".$TABLE_PREFIX."alias_urls where url_alias=''");
                        
                    }
                }

                
            }
        }
        return $enlaceNuevo;

    }

    static function _getEnlaceIdiomas($valores,$enlaceNuevo,$enlaceAnterior,$estado=0,$table = null){
        global $SETTINGS,$tableName,$TABLE_PREFIX;
        $result = [];
        if (!$table) $table = $tableName;
        $result = mysql_query_fetch_all_assoc("SELECT prefix,fieldValue FROM ".$TABLE_PREFIX."traducciones WHERE tableName='".$table."' and fieldName='enlace' and recordNum='".$valores["num"]."'");
        if (!empty($result)) {
            
            $result2 = [];
            foreach($result as $rec){
                $result2[$rec["prefix"]] = base64_decode($rec["fieldValue"]);
            }
            return $result2;
        }
        return $result;
    }
    
    static function _seteaEnlaceIdiomas($valores,$enlaceNuevo,$enlaceAnterior,$estado=0,$table = null){
        global $SETTINGS,$tableName,$TABLE_PREFIX;
        $result = [];
        if (!$table) $table = $tableName;

        // REINICIAMOS LOS ENLACES DE LOS IDIOMAS
        //die("Aun falta establecer los enlaces para los idiomas así que el cdn está inservible hasta que se haga");
        switch($estado){
            case 5:
            case 6:
            case 4:
            case 2:
                // EN ESTE CASO NO HACEMOS NADA
                break;
            case 1:
                // AL PONER ENLACE DE FORMA MANUAL NO HACEMOS NADA EN IDIOMAS
                break;
            case 3:
            default:

                mysql_query("DELETE FROM ".$TABLE_PREFIX."traducciones where tableName='".$table."' and fieldName='enlace' and recordNum='".$valores["num"]."'");

                foreach($SETTINGS["idiomas"] as $key => $value):

                if ($value&&$value!="www"){
                    $enlace = base64_encode("/".$value.$enlaceNuevo);
                    $result[$value] = "/".$value.$enlaceNuevo;
                    mysql_query("INSERT INTO ".$TABLE_PREFIX."traducciones set num=null,prefix='".$value."', tableName='".$table."', fieldName='enlace', fieldValue='".$enlace."', recordNum='".intval(@$valores["num"])."', preSaveTempId='".@$valores["preSaveTempId"]."'") or die(mysql_error());
                }
                endforeach;
        }
        return $result;
    }

    static function _getLinkPrefix($record,$table = null) {
        global $tableName, $TABLE_PREFIX;

        if (!$table) $table = $tableName;

        $enlaces = array();
        
        $record["tableName"] = $table;
        $cont = 0;
        while (true && $cont++ <= 50) { // Contador de seguridad para evitar el bucle infinito (que en teoría nunca pasará, jaja)
            // Comprobamos si la tabla ha cambiado para no volver a cargar el schema
            if ($record["tableName"] != @$tabla) {
                
                $tabla = $record["tableName"];
                if(class_exists('SchemaAPI')) {
                    $schema = SchemaAPI::getInstance()->loadSchema($tabla);
                } else {
                    $schema = loadSchema($tabla);
                }
                $breadcrumbField = @$schema["breadcrumbField"];

                if ($breadcrumbField == "parentNum") {
                    $schema[$breadcrumbField]["optionsTablename"] = $tabla;
                    $schema[$breadcrumbField]["optionsValueField"] = "num";
                }
                
                if (@$schema[$breadcrumbField]["optionsType"] == "query"){
                    preg_match("/SELECT ([0-9a-z_]*),[\s?]([_0-9a-z]*) FROM ([a-z_]*)/",$schema[$breadcrumbField]["optionsQuery"],$matches);
                    
                    if (@$matches[1]) $schema[$breadcrumbField]["optionsValueField"] = $matches[1];
                    if (@$matches[3]) $schema[$breadcrumbField]["optionsTablename"] = str_replace($TABLE_PREFIX,"",$matches[3]);
                    
                    
                }
            }
            
            if (!@$breadcrumbField || !@$schema[$breadcrumbField]["optionsTablename"]) {
                break;
            }
            
            
            $record = @mysql_fetch_assoc(mysql_query("SELECT * FROM ".$TABLE_PREFIX.$schema[$breadcrumbField]["optionsTablename"]." WHERE `".$schema[$breadcrumbField]["optionsValueField"]."`='".$record[$breadcrumbField]."'"));
            if (!@$record) break;
            $record["tableName"] = $schema[$breadcrumbField]["optionsTablename"];
            array_unshift($enlaces, $record);
        }
        
        if (!@$enlaces) return "";
        
        $prefix = "/".join("/", array_map(function($a) {
            return self::parsea_enlace($a[self::_getTitleField($a, $a["tableName"])]);
        }, $enlaces));
        
        return $prefix;
    }
    
    static function _getTitleField($record, $tabla, $schema = null) {
        if (@$record["name"]) return "name";
        if (@$record["title"]) return "title";
        if (@$record["titulo"]) return "titulo";
        if (@$record["nombre"]) return "nombre";

        if (!@$campo) {
            
            if(class_exists('SchemaAPI')) {
                if (!@$schemaAux) $schemaAux = SchemaAPI::getInstance()->loadSchema($tabla);
            } else {
                if (!@$schemaAux) $schemaAux = loadSchema($tabla);
            }
            foreach ($schemaAux as $key => $value):
                if (@$value["type"] == "textfield" && $key != "enlace") {
                    return $key;
                    break;
                }
            endforeach;
        }
    }

    static function parsea_enlace($txt) {
        
        $transliterationTable = array("’" => "", ' ' => '',' ' => '','' => '', 'á' => 'a', 'Á' => 'A', 'à' => 'a', 'À' => 'A', 'ă' => 'a', 'Ă' => 'A', 'â' => 'a', 'Â' => 'A', 'å' => 'a', 'Å' => 'A', 'ã' => 'a', 'Ã' => 'A', 'ą' => 'a', 'Ą' => 'A', 'ā' => 'a', 'Ā' => 'A', 'ä' => 'a', 'Ä' => 'A', 'æ' => 'ae', 'Æ' => 'AE', 'ḃ' => 'b', 'Ḃ' => 'B', 'ć' => 'c', 'Ć' => 'C', 'ĉ' => 'c', 'Ĉ' => 'C', 'č' => 'c', 'Č' => 'C', 'ċ' => 'c', 'Ċ' => 'C', 'ç' => 'c', 'Ç' => 'C', 'ď' => 'd', 'Ď' => 'D', 'ḋ' => 'd', 'Ḋ' => 'D', 'đ' => 'd', 'Đ' => 'D', 'ð' => 'dh', 'Ð' => 'Dh', 'é' => 'e', 'É' => 'E', 'è' => 'e', 'È' => 'E', 'ĕ' => 'e', 'Ĕ' => 'E', 'ê' => 'e', 'Ê' => 'E', 'ě' => 'e', 'Ě' => 'E', 'ë' => 'e', 'Ë' => 'E', 'ė' => 'e', 'Ė' => 'E', 'ę' => 'e', 'Ę' => 'E', 'ē' => 'e', 'Ē' => 'E', 'ḟ' => 'f', 'Ḟ' => 'F', 'ƒ' => 'f', 'Ƒ' => 'F', 'ğ' => 'g', 'Ğ' => 'G', 'ĝ' => 'g', 'Ĝ' => 'G', 'ġ' => 'g', 'Ġ' => 'G', 'ģ' => 'g', 'Ģ' => 'G', 'ĥ' => 'h', 'Ĥ' => 'H', 'ħ' => 'h', 'Ħ' => 'H', 'í' => 'i', 'Í' => 'I', 'ì' => 'i', 'Ì' => 'I', 'î' => 'i', 'Î' => 'I', 'ï' => 'i', 'Ï' => 'I', 'ĩ' => 'i', 'Ĩ' => 'I', 'į' => 'i', 'Į' => 'I', 'ī' => 'i', 'Ī' => 'I', 'ĵ' => 'j', 'Ĵ' => 'J', 'ķ' => 'k', 'Ķ' => 'K', 'ĺ' => 'l', 'Ĺ' => 'L', 'ľ' => 'l', 'Ľ' => 'L', 'ļ' => 'l', 'Ļ' => 'L', 'ł' => 'l', 'Ł' => 'L', 'ṁ' => 'm', 'Ṁ' => 'M', 'ń' => 'n', 'Ń' => 'N', 'ň' => 'n', 'Ň' => 'N', 'ñ' => 'n', 'Ñ' => 'N', 'ņ' => 'n', 'Ņ' => 'N', 'ó' => 'o', 'Ó' => 'O', 'ò' => 'o', 'Ò' => 'O', 'ô' => 'o', 'Ô' => 'O', 'ő' => 'o', 'Ő' => 'O', 'õ' => 'o', 'Õ' => 'O', 'ø' => 'o', 'Ø' => 'O', 'ō' => 'o', 'Ō' => 'O', 'ơ' => 'o', 'Ơ' => 'O', 'ö' => 'o', 'Ö' => 'O', 'ṗ' => 'p', 'Ṗ' => 'P', 'ŕ' => 'r', 'Ŕ' => 'R', 'ř' => 'r', 'Ř' => 'R', 'ŗ' => 'r', 'Ŗ' => 'R', 'ś' => 's', 'Ś' => 'S', 'ŝ' => 's', 'Ŝ' => 'S', 'š' => 's', 'Š' => 'S', 'ṡ' => 's', 'Ṡ' => 'S', 'ş' => 's', 'Ş' => 'S', 'ș' => 's', 'Ș' => 'S', 'ß' => 'SS', 'ť' => 't', 'Ť' => 'T', 'ṫ' => 't', 'Ṫ' => 'T', 'ţ' => 't', 'Ţ' => 'T', 'ț' => 't', 'Ț' => 'T', 'ŧ' => 't', 'Ŧ' => 'T', 'ú' => 'u', 'Ú' => 'U', 'ù' => 'u', 'Ù' => 'U', 'ŭ' => 'u', 'Ŭ' => 'U', 'û' => 'u', 'Û' => 'U', 'ů' => 'u', 'Ů' => 'U', 'ű' => 'u', 'Ű' => 'U', 'ũ' => 'u', 'Ũ' => 'U', 'ų' => 'u', 'Ų' => 'U', 'ū' => 'u', 'Ū' => 'U', 'ư' => 'u', 'Ư' => 'U', 'ü' => 'u', 'Ü' => 'U', 'ẃ' => 'w', 'Ẃ' => 'W', 'ẁ' => 'w', 'Ẁ' => 'W', 'ŵ' => 'w', 'Ŵ' => 'W', 'ẅ' => 'w', 'Ẅ' => 'W', 'ý' => 'y', 'Ý' => 'Y', 'ỳ' => 'y', 'Ỳ' => 'Y', 'ŷ' => 'y', 'Ŷ' => 'Y', 'ÿ' => 'y', 'Ÿ' => 'Y', 'ź' => 'z', 'Ź' => 'Z', 'ž' => 'z', 'Ž' => 'Z', 'ż' => 'z', 'Ż' => 'Z', 'þ' => 'th', 'Þ' => 'Th', 'µ' => 'u', 'а' => 'a', 'А' => 'a', 'б' => 'b', 'Б' => 'b', 'в' => 'v', 'В' => 'v', 'г' => 'g', 'Г' => 'g', 'д' => 'd', 'Д' => 'd', 'е' => 'e', 'Е' => 'E', 'ё' => 'e', 'Ё' => 'E', 'ж' => 'zh', 'Ж' => 'zh', 'з' => 'z', 'З' => 'z', 'и' => 'i', 'И' => 'i', 'й' => 'j', 'Й' => 'j', 'к' => 'k', 'К' => 'k', 'л' => 'l', 'Л' => 'l', 'м' => 'm', 'М' => 'm', 'н' => 'n', 'Н' => 'n', 'о' => 'o', 'О' => 'o', 'п' => 'p', 'П' => 'p', 'р' => 'r', 'Р' => 'r', 'с' => 's', 'С' => 's', 'т' => 't', 'Т' => 't', 'у' => 'u', 'У' => 'u', 'ф' => 'f', 'Ф' => 'f', 'х' => 'h', 'Х' => 'h', 'ц' => 'c', 'Ц' => 'c', 'ч' => 'ch', 'Ч' => 'ch', 'ш' => 'sh', 'Ш' => 'sh', 'щ' => 'sch', 'Щ' => 'sch', 'ъ' => '', 'Ъ' => '', 'ы' => 'y', 'Ы' => 'y', 'ь' => '', 'Ь' => '', 'э' => 'e', 'Э' => 'e', 'ю' => 'ju', 'Ю' => 'ju', 'я' => 'ja', 'Я' => 'ja', "!" => "", "|" => "", "'" => "", "\"" => "", "'" => "", "@" => "", "·" => "", "#" => "", "$" => "", "¢" => "", "%" => "", "∞" => "", "¬" => "", "/" => "", "÷" => "", "(" => "", "“" => "", ")" => "", "”" => "", "≠" => "", "?" => "", "'" => "", "¡" => "", "¿" => "", "‚" => "", "´" => "", "^" => "", "`" => "", "[" => "", "*" => "", "+" => "", "]" => "", "¨" => "", "´" => "", "{" => "", "}" => "", "," => "", ";" => "", "„" => "", "." => "", ":" => "", "…" => "", "<" => "", ">" => "", "≤" => "", "≥" => "", "»" => "", "«" => "", "œ" => "", "æ" => "", "®" => "", "†" => "", "¥" => "", "π" => "", "∫" => "", "" => "", "™" => "", "¶" => "", "§" => "", "~" => "", "Ω" => "", "∑" => "", "©" => "", "√" => "", "µ" => "", "=" => "", "&" => "", " " => "-", "–" => "-", "_" => "-", " " => "-", '€' => 'e', 'º' => '', '°' => '', 'ª' => '', '&' => 'y', '\'' => '');
        $enlace = trim(strtolower(str_replace(array_keys($transliterationTable), array_values($transliterationTable), $txt)));
        $enlace = preg_replace("/([\-]+)/", "-", $enlace);
        if (substr($enlace,strlen($enlace)-1) == "-") $enlace = substr($enlace,0,strlen($enlace)-1);
        $enlace = str_replace("-/","/",$enlace);
        $enlace = str_replace("/-","/",$enlace);
        $enlace = urlencode($enlace);
        $enlace = str_replace("%C2","",$enlace);
        $enlace = str_replace("%A0","",$enlace);
        $enlace = str_replace("%250D","",$enlace);
        return urlencode($enlace);
    }

}
