<?php
if(!defined('COCO_EMAIL_SERVER_HTTP_HOST')) {
    define("COCO_EMAIL_SERVER_HTTP_HOST", $_SERVER["HTTP_HOST"]);
}
class CocoEmail {
    static $table = 'correos';
    static $field_key = 'identificador';
    static $field_subject = 'asunto';
    static $field_header = 'cabecera';
    static $field_content = 'cuerpo';
    static $field_footer = 'pie';
    static $field_styles = 'estilos';
    //Error en clase de phpmailer en la funcion encodeFile() por set_magic_quotes y get_magic_quotes se eliminaron en php 8
    //Esto espera un array de filePaths de archivos a adjuntar
    static $attach_files = [];

    static $encode_with_base64 = false;
    static $debug = false;
    static $smtp = false;
    static $verify = false;
    static $show_variables = false;

    static $stop_sending_emails = false;

    static $webhook_url = "";
    static $webhook_function = null;

    static $smtp_data = [
        "host" => "smtp.gmail.com",
        "secure" => "ssl",
        "port" => 465,
        "username" => "soporte@cocosolution.com",
        "password" => "",
        "from" => "soporte@cocosolution.com",
        "from_name" => "",
    ];

    static $use_dkim = false;

    static $dkim = [
        'DKIM_domain' => 'cocosolution.com',
        'DKIM_selector' => 'default',
        'DKIM_private' => __DIR__ . '/default',
        'DKIM_identity' => 'soporte@cocosolution.com',
    ];

    static $bloqued_emails = [];

    static $mail_data = [
        "from" => "",
        "from_name" => "",
    ];

    static $replyTo = [
        "to" => "",
        "to_name" => "",
    ];

    static $send_copy_to = [];
    static $send_blind_copy_to = [];

    static $template = "
        <html>
        <head>
            <title>{{TITLE}}</title>
            <style>{{STYLES}}</style>
        </head>
        <body>
            <div id='contenido'>
                {{HEADER}}
                {{CONTENIDO}}
                {{FOOTER}}
            </div>
        </body>
        </html>
    ";

    static $styles = "
        body{font-family:Arial; color:#777; background-color:#F6F8FB;padding:20px;}
        #contenido{max-width:640px; margin:0 auto; padding:20px; border: 1px solid #F6F8FB; background-color:#fff; border-radius:25px; -webkit-box-shadow:0px 0px 20px rgba(0,0,0,0.1); box-shadow:0px 0px 20px rgba(0,0,0,0.1);}
        #contenido img {max-width: 100%;}
        h3{font-weight:normal; color:#111;}
        a{color:#CE482F; text-decoration:none;}
        table td{border:solid 1px #ddd; padding:5px; width:100%; margin:0px;}
    ";

    static $header = "
        <center>
            <img alt='' src='https://".COCO_EMAIL_SERVER_HTTP_HOST."/template/estandar/images/logo.png' style='max-width:100%; width:200px;'>
        </center>
        <br>
    ";

    static $footer = "";

    /**
     * Envía un correo y parsea su contenido con las variables pasadas por parámetro
     *
     * @param string $key
     * @param array $params
     * @param array $to
     * @param string $subject
     * @param boolean $returnHTML
     * @param array options : [
            "twig" : Boolean -> Twig Mode,
            "base64Decode" : Boolean -> Decode content from base64 to ascii
       ]
     * @return void
     */
    static function send($key = null, $params = [], $to = [], $subject = null, $content = null, $returnHTML = false, $options = []) {
        global $TABLE_PREFIX;
        if ($key) {
            $key = mysql_real_escape_string($key);
            $record = mysql_fetch_assoc(mysql_query("SELECT * FROM $TABLE_PREFIX".self::$table." WHERE `".self::$field_key."` LIKE '$key'"));
            if (!$record) {
                throw new Exception('Correo no encontrado');
            }
            $record["tableName"] = self::$table;
        }
        else if ($content) {
            $record = [
                self::$field_subject => $subject,
                self::$field_content => $content
            ];
        }
        else {
            throw new Exception('Tienes que enviar la clave o el contenido');
        }

        if (!is_array($to)) {
            $to = explode(',', $to);
        }
        $to = array_filter(array_map('trim', $to), function($a) {
            return filter_var($a, FILTER_VALIDATE_EMAIL);
        });

        if (@$options["base64Decode"]) {
            $content = base64_decode(t($record,self::$field_content));
        }else{
            $content = t($record, self::$field_content);
        }

        $content = self::parse($content, $params,@$options ?: []);

        $subject = t($record, self::$field_subject);
        $subject = self::parse($subject, $params, $options);

        $header = t($record, self::$field_header);
        if(@$header) self::$header = self::parse($header, $params);

        $footer = t($record, self::$field_footer);
        if(@$footer) self::$footer = self::parse($footer, $params);

        $styles = t($record, self::$field_styles);
        if(@$styles) self::$styles = self::parse($styles, $params);

        if ($returnHTML) {
            return $content;
        }
        if (empty($to)) {
            throw new Exception('No hay destinatarios válidos');
        }

        if (!$subject && $record) $subject = t($record, self::$field_subject);

        $result = [];
        foreach ($to as $destinatario) {
            $resultString = self::send_email_coco_proxy($destinatario, $subject, $content);
            $result[] = @json_decode($resultString,true) ?: ["success" => false, "message" => "Error decoding response", "raw_response" => $resultString];
        }
        self::$attach_files = [];
        return $result;
    }
    /*
        options : [
            "twig" : Boolean -> Twig Mode
        ]
    */
    static function parse($content, $params, $options = []) {
        if (@$options["twig"]){
            if (!function_exists("compileTWIG") && file_exists(__DIR__."/../plugins/builder_saas")) require_once __DIR__."/../plugins/builder_saas/builder_functions.php";
            $tempFolder = sys_get_temp_dir()."/".md5($content);
            $content = html_entity_decode($content);
            if (!file_exists($tempFolder)) mkdir($tempFolder);
            if (!file_exists($tempFolder."/index.twig")){
                $php = compileTWIG($content,$tempFolder);
                file_put_contents($tempFolder."/index.twig",$php);
            }else{
                $php = file_get_contents($tempFolder."/index.twig");
            }

            ob_start();
            require($tempFolder."/index.twig");
            $acaiResultData->doDisplay($params);
            $resultado = ob_get_clean();

            return $resultado;
        }else{
            $params = self::array_change_key_case_recursive($params);
            $params_parsed = [];
            foreach ($params as $key => $param) {
                if (isset($param['tablename'])) {
                    $params_parsed[strtolower($param['tablename'])] = $param;
                }
                else {
                    $key = str_replace(['{', '}'], ['', ''], $key);
                    $params_parsed[$key] = $param;
                }
            }

            return preg_replace_callback("/{([^}]+)}/", function($matches) use($params_parsed) {
                $token = explode(".", strtolower($matches[1]));
                // Comprobamos si es un token simple o compuesto
                if (count($token) === 1) {
                    // Token simple. Comprobamos si se refiere a una tabla o un valor fijo
                    if (isset($params_parsed[$token[0]])) {
                        if (is_array($params_parsed[$token[0]])) {
                            // Es un array. Devolvemos su mainField o, en su defecto, el primer campo que encontremos
                            if (isset($params_parsed[$token[0]]['mainfieldbreadcrumb'])) {
                                return $params_parsed[$token[0]]['mainfieldbreadcrumb'];
                            }
                            reset($params_parsed);
                            return t($params_parsed, key($params_parsed));
                        }
                        else {
                            // Es un valor fijo
                            return $params_parsed[$token[0]];
                        }
                    }
                    else {
                        return self::$show_variables ? '{'.$matches[1].'}' : '-';
                    }
                }
                else {
                    // Es un token compuesto. Lo recorremos hasta que no queden más tokens y devolvemos el resultado
                    if (!isset($params_parsed[$token[0]]) || !is_array($params_parsed[$token[0]])) return '{'.$matches[1].'}';

                    $i = 0;
                    $current = $params_parsed;
                    do {
                        $tok = $token[$i];
                        if (!isset($current[$tok])) return '{'.$matches[1].'}';
                        $current = $current[$tok];
                        $i += 1;
                    } while ($i < count($token));
                    return $current;
                }
            }, $content);
        }
    }

    static function array_change_key_case_recursive($arr) {
        return array_map(function($item){
            if(is_array($item))
                $item = self::array_change_key_case_recursive($item);
            return $item;
        }, array_change_key_case($arr));
    }

    function encrypt($string, $key) {
        $result = '';
        for($i=0; $i<strlen($string); $i++) {
            $char = substr($string, $i, 1);
            $keychar = substr($key, ($i % strlen($key))-1, 1);
            $char = chr(ord($char)+ord($keychar));
            $result.=$char;
        }
        return base64_encode($result);
    }

    static function send_email_coco_proxy($destinatario="soporte@cocosolution.com", $asunto="Error al enviar correo", $contenido="", $respuesta="") {
        
        $data = [
            "smtp_data" => self::$smtp_data,
            "mail_data" => self::$mail_data,
            "replyTo" => self::$replyTo,
            "send_copy_to" => self::$send_copy_to,
            "params" => [
                "to" => $destinatario,
                "subject" => $asunto,
                "body" => $contenido
            ],
            "bloqued_emails" => self::$bloqued_emails,
            "encode_with_base64" => self::$encode_with_base64,
            "template" => self::$template,
            "styles" => self::$styles,
            "header" => self::$header,
            "footer" => self::$footer
        ];

        //$encryptData = self::encrypt(json_encode($data), "Analiticaempresas17");

        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-Type: application/json',
                'content' => json_encode($data)
            )
        );        

        $context  = stream_context_create($opts);
        $result = file_get_contents("https://cocosolution.com/?sendQuantumEmail=1", false, $context);
        return $result;
    }

    static function send_email($destinatario="soporte@cocosolution.com", $asunto="Error al enviar correo", $contenido="", $respuesta="") {
        global $configuracionRecord;

        if (in_array($destinatario,self::$bloqued_emails)) die("Testing");

        if (!isset($configuracionRecord)){
            $configuracionRecord = @CocoDB::get("configuracion","num != 0")[0];
        }

        require_once __DIR__ . '/../vendor/PHPMailer/PHPMailerAutoload.php';

        /*$mensaje = "
            <html>
            <head>
                <title>".$asunto."</title>
                <style>
                    body{font-family:Arial;color:#777;background-color:#F6F8FB}
                    #contenido{max-width:640px;margin:0 auto;padding:20px;background-color:#fff;border-radius:25px;-webkit-box-shadow:0px 0px 20px rgba(0,0,0,0.1);box-shadow:0px 0px 20px rgba(0,0,0,0.1);}
                    h3{font-weight:normal;color:#111;}
                    a{color:#CE482F;text-decoration:none;}
                    table td{border:solid 1px #ddd;padding:5px;width:100%;margin:0px;}
                </style>
            </head>
            <body>
                <div id='contenido'>
                    <center><img alt='' src='https://".$_SERVER["HTTP_HOST"]."/template/estandar/images/logo.png' style='max-width:100%; width:200px;'></center>
                    <br>
                    ".$contenido."
                </div>
            </body>
            </html>
        ";*/

        $mensaje = self::$template;
        $mensaje = str_replace('{{STYLES}}', self::$styles, $mensaje);
        $mensaje = str_replace('{{HEADER}}', self::$header, $mensaje);
        $mensaje = str_replace('{{TITLE}}', $asunto, $mensaje);
        $mensaje = str_replace('{{CONTENIDO}}', $contenido, $mensaje);
        $mensaje = str_replace('{{FOOTER}}', self::$footer, $mensaje);

        try {
            $mail = new PHPMailer();
            // Basic
            $mail->CharSet = "UTF-8";
            if(self::$encode_with_base64) $mail->Encoding = "base64";
            $mail->IsHTML(true);

            //Debug
            if(self::$debug) {
                $mail->SMTPDebug  = 4;
                echo "<br>";
                echo $mensaje;
                echo "<br><br>";
                var_dump([
                    'To:' => $destinatario,
                    'Asunto' => $asunto,
                    'Contenido' => $mensaje
                ]);
                echo "<br>";
            }

            // SMTP
            if(self::$smtp) {
                $mail->Host = self::$smtp_data['host'];
                $mail->IsSMTP();
                $mail->SMTPAuth  = true;
                if(self::$smtp_data['secure'])
                    $mail->SMTPSecure  = self::$smtp_data['secure'];
                $mail->Helo = @self::$smtp_data['helo'] ?:'webs.cocosolution.com';
                $mail->Port = self::$smtp_data['port'];
                $mail->Username = self::$smtp_data['username'];
                $mail->Password = self::$smtp_data['password'];
                $mail->SetFrom(self::$smtp_data['from'], self::$smtp_data['from_name']);
            }

            // DKIM
            if (self::$use_dkim) {
                $mail->DKIM_domain = self::$dkim['DKIM_domain'];
                $mail->DKIM_private = self::$dkim['DKIM_private'];
                $mail->DKIM_selector = self::$dkim['DKIM_selector'];
                $mail->DKIM_identity = self::$dkim['DKIM_identity'];
            }

            // Config
            if(!self::$smtp) {
                $mail->setFrom(self::$mail_data["from"] ?: $configuracionRecord["correo_admin"], self::$mail_data["from_name"] ?: $configuracionRecord["tienda_nombre_empresa"]);
            }
            $mail->addReplyTo(self::$replyTo["to"] ?: $configuracionRecord["correo_admin"], self::$replyTo["to_name"] ?: $configuracionRecord["tienda_nombre_empresa"]);

            if(self::$verify) {
                $mail->AddAddress('check-auth-soporte=cocosolution.com@verifier.port25.com');
            } else {
                $mail->AddAddress($destinatario);
                foreach (self::$send_copy_to as $email) {
                    $mail->AddCC($email);
                }

                foreach (self::$send_blind_copy_to as $email) {
                    $mail->addBCC($email);
                }

            }

            $mail->Subject = $asunto;

            $mail->msgHTML($mensaje);
            foreach (self::$attach_files as $file) {
              $mail->addAttachment($file,basename($file));
            }

            if(!self::$stop_sending_emails) {
                $resultMail = $mail->Send();

                try{
                    if (self::$webhook_url || self::$webhook_function){
                        $dataMail = [
                            "from" => $mail->From,
                            "from_name" => $mail->FromName,
                            "sender" => $mail->Sender,
                            "subject" => $mail->Subject,
                            "to" => $destinatario,
                            "body" => $mail->Body,
                            "alt_body" => $mail->AltBody,
                            "sended" => @$mail->ErrorInfo ? false : true,
                            "error" => @$mail->ErrorInfo
                        ];
                        
                        if (!empty(self::$webhook_url) && filter_var(self::$webhook_url, FILTER_VALIDATE_URL)) {

                            $opts = array('http' =>
                                array(
                                    'method'  => 'POST',
                                    'header'  => 'Content-Type: application/json',
                                    'content' => json_encode($dataMail)
                                )
                            );

                            $context  = stream_context_create($opts);
                            $result = file_get_contents(self::$webhook_url, false, $context);

                        }

                        if (is_callable(self::$webhook_function)) {

                            $resultFunction = call_user_func(self::$webhook_function,$dataMail);

                        }
                        
                    }
                } catch (Exception $e){
                    // En caso de error no hacemos nada
                }
                

            }

        } catch (phpmailerException $e) {
            echo $e->errorMessage(); //Pretty error messages from PHPMailer
        } catch (Exception $e) {
            echo $e->getMessage(); //Boring error messages from anything else!
        }
    }
}
