<?php
if (@$_REQUEST["__amp_source_origin"] && @$_REQUEST["dynamicForm"]){
    CocoParser::amp();
}
class CocoParser {
    static $captchaValido = false;
    static $hayCaptcha = false;
    static $parseaCodigosEnLinea = true;

    static function parsea_codigos_en_linea($cadena,$options=array()){
        if (!self::$parseaCodigosEnLinea) return $cadena;
        if (is_array($cadena)) return $cadena;
        if (is_null($cadena)) $cadena = '';
        $cadena = str_replace("ql-align-", "text-", $cadena);
        $cadena = str_replace("<ul>", "<ul class='bullet'>", $cadena);
        $cadena = str_replace("<ol>", "<ol class='bullet'>", $cadena);
        $cadena = preg_replace("/(target=\"_blank\")/", "$1 rel=\"noopener\"", $cadena);
        $cadena = preg_replace_callback("|(\{FORMULARIO\_)([A-Z_]*)(\})|",
                                        function($matches) use ($options){
                                            return "<div data-code-replace='".$matches[0]."'>".self::dame_boton_formulario($matches,$options)."</div>";
                                        },$cadena);
        $dummy = array("cadena" => $cadena, "options" => $options);

        // addPlugins("codigos_en_linea", $dummy);
        // Activando este plugin el tiempo de carga aumenta CONSIDERABLEMENTE ya que se pide en cada content

        $cadena = $dummy["cadena"];
        return $cadena;
    }

    static function parsea_campo2($txt,$espacio="_") {
        $transliterationTable = array('á' => 'a', 'Á' => 'A', 'à' => 'a', 'À' => 'A', 'ă' => 'a', 'Ă' => 'A', 'â' => 'a', 'Â' => 'A', 'å' => 'a', 'Å' => 'A', 'ã' => 'a', 'Ã' => 'A', 'ą' => 'a', 'Ą' => 'A', 'ā' => 'a', 'Ā' => 'A', 'ä' => 'a', 'Ä' => 'A', 'æ' => 'ae', 'Æ' => 'AE', 'ḃ' => 'b', 'Ḃ' => 'B', 'ć' => 'c', 'Ć' => 'C', 'ĉ' => 'c', 'Ĉ' => 'C', 'č' => 'c', 'Č' => 'C', 'ċ' => 'c', 'Ċ' => 'C', 'ç' => 'c', 'Ç' => 'C', 'ď' => 'd', 'Ď' => 'D', 'ḋ' => 'd', 'Ḋ' => 'D', 'đ' => 'd', 'Đ' => 'D', 'ð' => 'dh', 'Ð' => 'Dh', 'é' => 'e', 'É' => 'E', 'è' => 'e', 'È' => 'E', 'ĕ' => 'e', 'Ĕ' => 'E', 'ê' => 'e', 'Ê' => 'E', 'ě' => 'e', 'Ě' => 'E', 'ë' => 'e', 'Ë' => 'E', 'ė' => 'e', 'Ė' => 'E', 'ę' => 'e', 'Ę' => 'E', 'ē' => 'e', 'Ē' => 'E', 'ḟ' => 'f', 'Ḟ' => 'F', 'ƒ' => 'f', 'Ƒ' => 'F', 'ğ' => 'g', 'Ğ' => 'G', 'ĝ' => 'g', 'Ĝ' => 'G', 'ġ' => 'g', 'Ġ' => 'G', 'ģ' => 'g', 'Ģ' => 'G', 'ĥ' => 'h', 'Ĥ' => 'H', 'ħ' => 'h', 'Ħ' => 'H', 'í' => 'i', 'Í' => 'I', 'ì' => 'i', 'Ì' => 'I', 'î' => 'i', 'Î' => 'I', 'ï' => 'i', 'Ï' => 'I', 'ĩ' => 'i', 'Ĩ' => 'I', 'į' => 'i', 'Į' => 'I', 'ī' => 'i', 'Ī' => 'I', 'ĵ' => 'j', 'Ĵ' => 'J', 'ķ' => 'k', 'Ķ' => 'K', 'ĺ' => 'l', 'Ĺ' => 'L', 'ľ' => 'l', 'Ľ' => 'L', 'ļ' => 'l', 'Ļ' => 'L', 'ł' => 'l', 'Ł' => 'L', 'ṁ' => 'm', 'Ṁ' => 'M', 'ń' => 'n', 'Ń' => 'N', 'ň' => 'n', 'Ň' => 'N', 'ñ' => 'n', 'Ñ' => 'N', 'ņ' => 'n', 'Ņ' => 'N', 'ó' => 'o', 'Ó' => 'O', 'ò' => 'o', 'Ò' => 'O', 'ô' => 'o', 'Ô' => 'O', 'ő' => 'o', 'Ő' => 'O', 'õ' => 'o', 'Õ' => 'O', 'ø' => 'o', 'Ø' => 'O', 'ō' => 'o', 'Ō' => 'O', 'ơ' => 'o', 'Ơ' => 'O', 'ö' => 'o', 'Ö' => 'O', 'ṗ' => 'p', 'Ṗ' => 'P', 'ŕ' => 'r', 'Ŕ' => 'R', 'ř' => 'r', 'Ř' => 'R', 'ŗ' => 'r', 'Ŗ' => 'R', 'ś' => 's', 'Ś' => 'S', 'ŝ' => 's', 'Ŝ' => 'S', 'š' => 's', 'Š' => 'S', 'ṡ' => 's', 'Ṡ' => 'S', 'ş' => 's', 'Ş' => 'S', 'ș' => 's', 'Ș' => 'S', 'ß' => 'SS', 'ť' => 't', 'Ť' => 'T', 'ṫ' => 't', 'Ṫ' => 'T', 'ţ' => 't', 'Ţ' => 'T', 'ț' => 't', 'Ț' => 'T', 'ŧ' => 't', 'Ŧ' => 'T', 'ú' => 'u', 'Ú' => 'U', 'ù' => 'u', 'Ù' => 'U', 'ŭ' => 'u', 'Ŭ' => 'U', 'û' => 'u', 'Û' => 'U', 'ů' => 'u', 'Ů' => 'U', 'ű' => 'u', 'Ű' => 'U', 'ũ' => 'u', 'Ũ' => 'U', 'ų' => 'u', 'Ų' => 'U', 'ū' => 'u', 'Ū' => 'U', 'ư' => 'u', 'Ư' => 'U', 'ü' => 'u', 'Ü' => 'U', 'ẃ' => 'w', 'Ẃ' => 'W', 'ẁ' => 'w', 'Ẁ' => 'W', 'ŵ' => 'w', 'Ŵ' => 'W', 'ẅ' => 'w', 'Ẅ' => 'W', 'ý' => 'y', 'Ý' => 'Y', 'ỳ' => 'y', 'Ỳ' => 'Y', 'ŷ' => 'y', 'Ŷ' => 'Y', 'ÿ' => 'y', 'Ÿ' => 'Y', 'ź' => 'z', 'Ź' => 'Z', 'ž' => 'z', 'Ž' => 'Z', 'ż' => 'z', 'Ż' => 'Z', 'þ' => 'th', 'Þ' => 'Th', 'µ' => 'u', 'а' => 'a', 'А' => 'a', 'б' => 'b', 'Б' => 'b', 'в' => 'v', 'В' => 'v', 'г' => 'g', 'Г' => 'g', 'д' => 'd', 'Д' => 'd', 'е' => 'e', 'Е' => 'E', 'ё' => 'e', 'Ё' => 'E', 'ж' => 'zh', 'Ж' => 'zh', 'з' => 'z', 'З' => 'z', 'и' => 'i', 'И' => 'i', 'й' => 'j', 'Й' => 'j', 'к' => 'k', 'К' => 'k', 'л' => 'l', 'Л' => 'l', 'м' => 'm', 'М' => 'm', 'н' => 'n', 'Н' => 'n', 'о' => 'o', 'О' => 'o', 'п' => 'p', 'П' => 'p', 'р' => 'r', 'Р' => 'r', 'с' => 's', 'С' => 's', 'т' => 't', 'Т' => 't', 'у' => 'u', 'У' => 'u', 'ф' => 'f', 'Ф' => 'f', 'х' => 'h', 'Х' => 'h', 'ц' => 'c', 'Ц' => 'c', 'ч' => 'ch', 'Ч' => 'ch', 'ш' => 'sh', 'Ш' => 'sh', 'щ' => 'sch', 'Щ' => 'sch', 'ъ' => '', 'Ъ' => '', 'ы' => 'y', 'Ы' => 'y', 'ь' => '', 'Ь' => '', 'э' => 'e', 'Э' => 'e', 'ю' => 'ju', 'Ю' => 'ju', 'я' => 'ja', 'Я' => 'ja', "!" => "", "|" => "", "'" => "", "\"" => "", "'" => "", "@" => "", "·" => "", "#" => "", "$" => "", "¢" => "", "%" => "", "∞" => "", "¬" => "", "/" => "", "÷" => "", "(" => "", "“" => "", ")" => "", "”" => "", "≠" => "", "?" => "", "'" => "", "¡" => "", "¿" => "", "‚" => "", "´" => "", "^" => "", "`" => "", "[" => "", "*" => "", "+" => "", "]" => "", "¨" => "", "´" => "", "{" => "", "}" => "", "," => "", ";" => "", "„" => "", "." => "", ":" => "", "…" => "", "<" => "", ">" => "", "≤" => "", "≥" => "", "»" => "", "«" => "", "œ" => "", "æ" => "", "®" => "", "†" => "", "¥" => "", "π" => "", "∫" => "", "" => "", "™" => "", "¶" => "", "§" => "", "~" => "", "Ω" => "", "∑" => "", "©" => "", "√" => "", "µ" => "", "=" => "", "&" => "", " " => "-", "–" => "-", "_" => "-", " " => "-", '€' => 'e', 'º' => '', 'ª' => '', '&' => 'y');
        $newString = strtolower(str_replace(array_keys($transliterationTable), array_values($transliterationTable), $txt));
        $newString = preg_replace("/([\-]+)/", $espacio, $newString);
        return urlencode($newString);
    }


    static function envia_curl($datos){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, protocol()."://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$datos);
        curl_setopt($ch, CURLOPT_TIMEOUT,30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (protocol() == "https"){
            curl_setopt($ch,CURLOPT_RESOLVE, [
                $_SERVER["HTTP_HOST"].":443:".$_SERVER["SERVER_ADDR"]
            ]);
        }else{
            curl_setopt($ch,CURLOPT_RESOLVE, [
                $_SERVER["HTTP_HOST"].":80:".$_SERVER["SERVER_ADDR"]
            ]);
        }

        $respond = curl_exec ($ch);
        curl_close ($ch);
        return $respond;
    }

    static function dame_boton_formulario($matches,$options=array()){
        global $configuracionRecord;
        $identificador = @$matches[2];
        if (!$identificador) return $matches[0];

        $result2 = mysql_query("SELECT * FROM cms__formularios where identificador='".$identificador."' limit 1");
        $result = "";
        if (mysql_num_rows($result2)>0){
            $rec = mysql_fetch_assoc($result2);
            if (@$options["clases"]) $rec["clase"] = $options["clases"];

            $form = $rec;
            $form["tableName"] = "cms__formularios";
            $form["tipo"] = (@$options["tipo"]) ? $options["tipo"] : $form["tipo"];

            $resultPlugin = addPlugins("pre_codigos_en_linea",$form);
            echo @$resultPlugin["html"];

            $datos = array(
                "id"            => "form_".$form["identificador"],
                "title"         => t($form,"title"),
                "numForm"         => $form["num"],
                "formulario"    => json_decode($form["campos"],true),
                "tipo"          => $form["tipo"],
                "clase"         => $form["clase"],
                "widget"        => (@$options["widget"]) ? true : false
            );
            foreach ($options as $index => $option):
            $datos[$index] = $option;
            endforeach;
            if (!@$options["yaPuesto"]) {
                if (!@$options["amp"]){
                    $respond = self::envia_curl("modulo=modal&clave=wscO4QaF&datos=".base64_encode(json_encode($datos)));
                }else{
                    require_once realpath(dirname(__FILE__)."/../../plugins/amp/amp_static functions.php");
                    $respond = modulo_amp("modal",$datos);
                }
            }

            // ENVIO DE DATOS POR CORREO

            if (@$_REQUEST["dynamicForm"]){

                if ($form["num"]==@$_REQUEST["numForm"]){
                    unset($_REQUEST["numForm"]);
                    // Comprobamos el captcha
                    self::$captchaValido = true;
                    if (!@$options["amp"]) {
                        if ((!isset($options["captcha"]) || @$options["captcha"] == true)) {
                            if (@hasRecaptcha()) {
                                $captcha = json_decode(file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".$configuracionRecord["secret_key_recaptcha"]."&response=".@$_REQUEST["g-recaptcha-response"]), true);
                                if (!@$captcha["success"]) self::$captchaValido = false;
                            }
                            else {
                                if (md5(@$_POST["captcha"]) != @$_SESSION["key_captcha"]) self::$captchaValido = false;
                            }
                        }
                    }
                    if (self::$captchaValido) {
                        $datosCadena="<ul>";
                        $correosCliente = array();
                        $campos = json_decode($form["campos"],true);
                        $tableName = self::parsea_campo2($form["tablaDestino"]);
                        $schema = loadSchema($tableName);
                        foreach(@$_REQUEST["dynamicForm"] as $key => $value):
                            foreach($campos as $cont => $campo):
                                if ($campo["tipo"]=="email" && $key == self::parsea_campo2($campo["nombre"],"-") && !in_array($value,$correosCliente)) {
                                    $correosCliente[] = $value;
                                }

                                if ($key==self::parsea_campo2($campo["nombre"],"-")) {
                                    $schemaKey = self::parsea_campo2($campo["nombre"]);
                                    $campos[$cont]["datosCliente"] = $value;
                                }
                            endforeach;

                            if (isset($schema[$schemaKey])) {
                                switch (@$schema[$schemaKey]['type']) {
                                    case 'list':
                                        $options = getListOptions($tableName, $schemaKey);
                                        if (isset($options[$value])) $value = $options[$value];
                                        $datosCadena.="<li><b>".$schema[$schemaKey]['label']."</b>: ".$value."</li>";
                                    break;
                                    case 'checkbox':
                                        $datosCadena.="<li><b>".$schema[$schemaKey]['label']."</b>: ".(@$value ? 'Sí' : 'No')."</li>";
                                    break;
                                    default:
                                        $datosCadena.="<li><b>".$schema[$schemaKey]['label']."</b>: ".$value."</li>";
                                }
                            } else {
                                $datosCadena.="<li><b>".$key."</b>: ".$value."</li>";
                            }
                        endforeach;
                        $datosCadena.="<li><b>URL de solicitud</b>: <a href='https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."'>https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."</a></li>";
                        $datosCadena.="</ul>";

                        $contenido = str_replace("{DATOS}",$datosCadena,$form["contenidoEmail"]);
                        if (!$form["enviarACliente"]) $correosCliente=array();
                        if ($form["enviarAEmpresa"]) {
                            $result = mysql_query("select correo_admin from cms_configuracion limit 1");
                            $correo = mysql_fetch_assoc($result);
                            if (strpos($correo["correo_admin"],",")){
                                $sepp = explode(",",$correo["correo_admin"]);
                                foreach($sepp as $seppp):
                                    if (@$seppp){
                                        if (!in_array($seppp,$correosCliente)) $correosCliente[]=$seppp;
                                    }
                                endforeach;
                            }else{
                                if (!in_array($correo["correo_admin"],$correosCliente)) $correosCliente[]=$correo["correo_admin"];
                            }
                        }
                        if (@$options['correos'] && is_array($options['correos'])) {
                            foreach($options['correos'] as $c):
                                if (filter_var($c, FILTER_VALIDATE_EMAIL)) $correosCliente[] = $c;
                            endforeach;
                        }
                        if (count($correosCliente)>0){
                            $datos = array(
                                "destinatarios" =>  $correosCliente,
                                "numForm"       =>  $form["num"],
                                "identificador"       =>  $form["identificador"],
                                "asunto"        =>  $form["title"],
                                "contenido"     =>  base64_encode($contenido)
                            );
                            $respond2 = self::envia_curl("enviar_correo=1&clave=wscO4QaF&datos=".base64_encode(json_encode($datos)));
                            $respond = @$respond2.@$respond;
                        }

                        // AHORA LO INSERTAMOS EN LA BASE DE DATOS

                        if (@$form["tablaDestino"]){
                            $sql = "
                        INSERT INTO cms_".self::parsea_campo2($form["tablaDestino"])." SET
                        num=NULL,
                        createdDate='".date("Y-m-d H:i:s")."',
                        updatedDate='".date("Y-m-d H:i:s")."',
                        dragSortOrder='".time()."',
                        url='https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."',
                        numFormulario='".$form["num"]."'
                        ";

                            $sql_busqueda = "SELECT num FROM cms_".self::parsea_campo2($form["tablaDestino"])." WHERE numFormulario='".$form["num"]."'";
                            $sql_busqueda.=" AND url='https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."'";

                            foreach($campos as $campo):
                            if (@$campo["datosCliente"]){
                                $sql.=",".self::parsea_campo2($campo["nombre"])."='".$campo["datosCliente"]."'";
                                $sql_busqueda.=" AND ".self::parsea_campo2($campo["nombre"])."='".$campo["datosCliente"]."'";
                            }
                            endforeach;

                            $result3 = @mysql_fetch_assoc(mysql_query("SHOW TABLES LIKE 'cms_".self::parsea_campo2($form["tablaDestino"])."'"));
                            if ($result3){
                                $resultadoBusqueda = mysql_fetch_assoc(mysql_query($sql_busqueda));
                                if (!@$resultadoBusqueda){
                                    mysql_query($sql) or die(mysql_error());
                                }
                            }
                        }
                        $resultPlugin = addPlugins("post_codigos_en_linea",$form);

                        if (!@$options["amp"] && !@$options['sin_gracias']) {
                            $apartadoGracias = dame_registros("otros_contenidos", "controlador='gracias.php'");
                            $apartadoGracias = @$apartadoGracias[0];
                            if (@$apartadoGracias) {
                                echo '<script>window.location.href = "'.t($apartadoGracias, "enlace").'"</script>';
                            }
                        }
                        echo @$form["html_post"];
                    }else{
                        echo "<script>alert('".t_var("El captcha introducido no es válido")."')</script>";
                    }
                }
            }

            switch ($form["tipo"]){
                case "fields":
                    $result=@$respond;
                    break;
                case "inline":
                    $result=@$respond;
                    break;
                default:
                    $result =@$respond;
                    if (@$options["textoBoton"]){
                        $result.= "<a href='javascript:void(0)' class='".$rec["clase"]."' data-toggle='modal' data-target='#form_".$rec["identificador"]."'>".$options["textoBoton"]."</a>";
                    }else{
                        $result.= "<a href='javascript:void(0)' class='".$rec["clase"]."' data-toggle='modal' data-target='#form_".$rec["identificador"]."'>".$rec["textoBoton"]."</a>";
                    }


            }
        }
        return $result;
    }

    static function amp(){
        require_once realpath(dirname(__FILE__)."/../../../../funciones.php");

        $result2 = @mysql_fetch_assoc(mysql_query("SELECT * FROM cms__formularios where num='".intval(@$_REQUEST["numForm"])."' limit 1"));
        $domain_url = protocol()."://".$_SERVER["HTTP_HOST"];
        header("Content-type: application/json");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Origin:" . str_replace('.', '-', $domain_url) .".cdn.ampproject.org");
        header("AMP-Access-Control-Allow-Source-Origin: " . $domain_url);

        $apartadoGracias = dame_registros("otros_contenidos", "controlador='gracias.php'");
        $apartadoGracias = @$apartadoGracias[0];
        if (@$apartadoGracias) {
            header("AMP-Redirect-To: " . protocol()."://".$_SERVER["HTTP_HOST"].t($apartadoGracias, "enlace"));
            header("Access-Control-Expose-Headers: AMP-Redirect-To, AMP-Access-Control-Allow-Source-Origin");
        }
        else {
            header("Access-Control-Expose-Headers: AMP-Access-Control-Allow-Source-Origin");
        }

        if (@$result2){
            $result = self::dame_boton_formulario(array(null,null,$result2["identificador"]),array("amp" => true,"yaPuesto" => true));
            die(json_encode(array('successmsg'=>'ok',"result" => @$result)));


        }else{
            die(json_encode(array('successmsg'=>'Error')));
        }
    }

    static function cocoForm($data = []){
        global $configuracionRecord;
        $defaults = [
            "sendTo" => @$configuracionRecord["correo_admin"],
            "messageOK" => t_var("Mensaje enviado"),
            "messageKO" => t_var("Los campos son requeridos"),
            "attachFiles" => false
        ];

        foreach($data as $key => $value){
            if (!is_string($value) || $value != "null") $defaults[$key] = $value;
        }

        $data = $defaults;

        if (@$data["captcha"]) self::$hayCaptcha = true;

        if (@$_REQUEST["cocoForm"] && @$_REQUEST["cocoForm"]["form"] == $data["id"]){

            try{
                $cocoForm = @$_REQUEST["cocoForm"];
                if ($cocoForm["form"] !== $data["id"]) return;

                if (@$data["captcha"] && !self::cocoFormValidateCaptcha($cocoForm)) throw new Exception(t_var('El Captcha no es válido')); // CAPTCHA

                // Anael: Variable estandar que usamos de Honeypot, en caso de que exista es que la ha rellenado un bot.
                if (@$cocoForm["full_user_name"]) throw new Exception(t_var('El Captcha no es válido')); 

                $errors = []; // COMPROBAMOS LOS REQUIRED
                foreach($data["variables"] as $key => $value){
                    if (strpos($key,"[]") !== false) $key = str_replace("[]","",$key);
                    if ($value["type"]=="file" && @$_FILES["cocoForm"] && array_filter($_FILES["cocoForm"]["name"][$key]) ) {
                        $uploadFiles = self::upload("cocoForm",$key);
                        foreach($uploadFiles as $uploadFile){
                          if ($uploadFile['success'] === false) throw new Exception(t_var('Error en la subida de archivo'));
                          if (@$cocoForm[$key]) $cocoForm[$key][]=$uploadFile["urlPath"]; else $cocoForm[$key] = [$uploadFile["urlPath"]];
                          if(!$data["attachFiles"]){
                            $link = "<a href='https://".$_SERVER["HTTP_HOST"].$uploadFile["urlPath"]."'>".t_var("Descargar")." ".basename($uploadFile["urlPath"])."</a><br>";
                            // En banana educación hay que arreglarlo poniendo en el correo el _text
                            if (@$cocoForm[$key."_text"]) $cocoForm[$key."_text"].=$link; else $cocoForm[$key."_text"] = $link;
                          } else {
                            CocoEmail::$attach_files[] = $uploadFile["filePath"];
                          }
                        }
                        continue;
                    }
                    if (isset($value["required"]) && empty($cocoForm[$key]) && @$cocoForm[$key] !== '0' && @$cocoForm[$key] !== 0) $errors[$key] = $value;
                }


                if (!empty($errors)) throw new Exception(t_var('Los campos').' '.join(", ",array_map(function($rec){ return t_var($rec); },array_keys($errors))).' '.t_var('son oblitagorios'));

                if (isset($data["action"])) return hook($data["action"],$cocoForm); // ACTION A HOOK

                if (isset($data["tableName"])) $resultInsert = self::cocoFormInsertRecords($data,$cocoForm); // INSERCION

                if (isset($data["mailRecord"]) && count($data["mailRecord"]) == 2) {
                    if (isset($data["tableName"]) && @$resultInsert){
                        //$insertedRecord = mysql_insert_id();
                        $recordInserted = @CocoDB::get($data["tableName"],"num=".intval($resultInsert),null,1,["relationsDepth" => 2])[0];
                    }
                    if (@$recordInserted){
                        self::cocoFormEmail($data, array_merge($cocoForm, $recordInserted)); // EMAIL
                    }else{
                        self::cocoFormEmail($data,$cocoForm); // EMAIL
                    }
                }
                if (isset($data["redirectTo"])) { // REDIRECT
                    echo "<script>document.location.href='".$data["redirectTo"]."'</script>";
                }

                echo strip_tags($data["messageOK"]) == $data["messageOK"] ? "<script>alert('".addslashes($data["messageOK"])."');</script>" : $data["messageOK"];

            }catch(Exception $e){
                echo strip_tags($data["messageKO"]) == $data["messageKO"] ? "<script>alert('".$e->getMessage()."');</script>" : "<div class='p-12 bg-red-600 rounded-lg'>".$e->getMessage()."</div>";
            }
        }
    }

    static function cocoFormValidateCaptcha($cocoForm = []){
        global $configuracionRecord;
        if (@hasRecaptcha()) {
            $captcha = json_decode(file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".$configuracionRecord["secret_key_recaptcha"]."&response=".@$_REQUEST["g-recaptcha-response"]), true);
            if (!@$captcha["success"]) return false;
        }
        else {
            if (md5(@$_POST["captcha"]) != @$_SESSION["key_captcha"]) return false;
        }
        return true;
    }

    static function cocoFormInsertRecords($data = [],$cocoForm = []){
        if (!@loadSchema($data["tableName"])) throw new Exception(t_var('La tabla de destino no existe'));
        $cocoForm["url"] = $_SERVER["REQUEST_URI"];
        return CocoDB::insertRecords($data["tableName"],$cocoForm,[],['return_last_id' => true]);
    }

    static function cocoFormEmail($data = [],$cocoForm = []){

        if (@$data["header"]) CocoEmail::$header = @$data["header"];
        if (@$data["footer"]) CocoEmail::$footer = @$data["footer"];
        if (@$data["styles"]) CocoEmail::$styles = @$data["styles"];

        $recipients = [];
        if (isset($data["sendToClient"]) && isset($cocoForm[$data["sendToClient"]])) $recipients[] = $cocoForm[$data["sendToClient"]];
        if (isset($data["sendTo"])){
            foreach(array_filter(explode(",",$data["sendTo"])) as $email){
                $recipients[] = trim($email);
            }
        }

        if (empty($recipients)) throw new Exception(t_var('No se encuentran destinatarios para el envío del correo'));

        if (!empty($recipients) && $data["mailRecord"][1]){
            if (!@loadSchema($data["mailRecord"][0])) throw new Exception(t_var('La tabla de correos no existe'));
            $auxTableCocoEmail = CocoEmail::$table;
            if (@loadSchema($data["mailRecord"][0])){
                CocoEmail::$table = $data["mailRecord"][0];
            }

            $options = [];
            if (trim(strtolower(@$data["emailMode"]?:'')) == "twig") $options["twig"] = true;
            if (@$data["emailB64"]) $options["base64Decode"] = true;
            //CocoEmail::$debug = true;
            CocoEmail::send($data["mailRecord"][1],$cocoForm,$recipients,null,null,false,$options ?: []);
            CocoEmail::$table = $auxTableCocoEmail;
        }
        return null;
    }

    static function slugify($text) {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        // trim
        $text = trim($text, '-');
        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);
        // lowercase
        $text = strtolower($text);
        if (empty($text)) {
            return substr(str_shuffle(MD5(microtime())), 0, 10);
        }

        return $text;
    }

    static function check_if_file_exist_and_get_new_name(&$file_info, $path, $count = 0) {
        if(!$count) {
            if(file_exists($path . $file_info['filename'] . '.' . $file_info['extension'])) {
                return self::check_if_file_exist_and_get_new_name($file_info, $path, $count + 1);
            }
        } else {
            if(file_exists($path . $file_info['filename'] . '-' . $count . '.' . $file_info['extension'])) {
                return self::check_if_file_exist_and_get_new_name($file_info, $path, $count + 1);
            }
        }
        if($count) $file_info['filename'] .= '-' . $count;
    }

    static function upload($prefix = null,$file = 'file') {
        $arrayFiles = $prefix ? $_FILES[$prefix] : $_FILES;
        if(!isset($arrayFiles["error"][$file])) return [];

        $allowed = [
            'application/pdf' => 'pdf',
            'audio/x-aac' => 'aac',
            'application/vnd.amazon.ebook' => 'azw',
            'audio/x-aiff' => 'aiff',
            'audio/mp3' => 'mp3',
            'audio/mpeg' => 'mp3',
            'image/bmp' => 'bmp',
            'text/css' => 'css',
            'text/csv' => 'csv',
            'text/plain' => 'csv',
            'application/epub+zip' => 'epub',
            'image/gif' => 'gif',
            'image/x-icon' => 'ico',
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => 'png',
            'image/heic' => 'heic',
            'image/svg+xml' => 'svg',
            'image/tiff' => 'tiff',
            'image/webp' => 'webp',
            'video/x-m4v' => 'm4v',
            'video/x-ms-wmv' => 'wmv',
            'video/mpeg' => 'mpeg',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/ogg' => 'ogg',
            'application/vnd.oasis.opendocument.text' => 'odt',
            'application/vnd.oasis.opendocument.graphics' => 'odg',
            'application/vnd.oasis.opendocument.spreadsheet' => 'ods',
            'application/vnd.oasis.opendocument.presentation' => 'odp',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/msword' => 'doc',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx'
        ];

        $countfiles = count($arrayFiles['name'][$file]);

        $files_to_upload = [];
        // $uploads_dir = __DIR__ . '/../../../../../uploads/';
        $uploads_dir = __DIR__ . '/../../uploads/';
        // Loop de archivos.
        for($i=0;$i<$countfiles;$i++){
            $files_to_upload[$i] = ['urlPath' => '', 'filePath' => '', 'success' => false];
            // Si hay un error saltamos el archivo.
            if($arrayFiles['error'][$file][$i] !== 0 || !$arrayFiles['name'][$file][$i]) continue;

            // Obtenemos la info del archivo.
            $file_name = $arrayFiles['name'][$file][$i];
            $tmp_file = $arrayFiles['tmp_name'][$file][$i];

            $file_info = pathinfo($file_name);

            // Obtenemos el mime_type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $tmp_file);
            finfo_close($finfo);

            // Si este archivo no está permitod continuamos.
            if (!isset($allowed[$mime_type])) continue;

            // Comprobamos si existe ya el archivo con este nombre para cambiarlo en tal caso.
            $file_info['filename'] = self::slugify($file_info['filename']);
            self::check_if_file_exist_and_get_new_name($file_info, $uploads_dir);

            // Movemos el archivo porque ha sido validado
            $new_name = $file_info['filename'] . '.' . $file_info['extension'];
            move_uploaded_file($tmp_file, $uploads_dir.$new_name);
            $filePath = realpath($uploads_dir.$new_name);
            $urlPath = '/cms/uploads/'.$new_name;
            $files_to_upload[$i] = ['urlPath' => $urlPath, 'filePath' => $filePath, 'success' => true];
        }
        return $files_to_upload;
    }

}
