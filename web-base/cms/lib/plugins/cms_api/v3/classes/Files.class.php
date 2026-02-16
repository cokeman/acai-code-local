<?
require_once __DIR__."/../lib/upload/class.upload.php";

class Files {
    static $uploadsDir = "/uploads";
    static $baseUrl = "/cms/uploads";
    static $allowed = [
        'application/pdf' => 'pdf',
        'image/bmp' => 'bmp',
        'image/gif' => 'gif',
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png','ico'],
        'image/svg+xml' => 'svg',
        'image/svg' => 'svg',        
        'image/tiff' => 'tiff',
        'video/x-m4v' => 'm4v',
        'video/x-ms-wmv' => 'wmv',
        'video/mpeg' => 'mpeg',
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/ogg' => 'ogg',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/msword' => 'doc',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx'
    ];
    
    static function upload($request){
        
        $result = [];

        if (isset($_POST["file_b64"])){

            if (!isset($_POST["filename"])) {
                Api::addWarning("Es necesario añadir el campo filename");
            }else{
                $filename = $_POST["filename"];
                if (!strpos($filename,".")) Api::addWarning("Debes añadir una extensión al filename");
                $extension = strtolower(trim(@explode(".",$filename)[1]));
                if (!$extension) Api::addWarning("Debes añadir una extensión al filename 2");
                $filebase = strtolower(trim(@explode(".",$filename)[0]));
                if (!$filebase) Api::addWarning("Debes añadir una extensión al filename 3");
                $fileTemp = time();

                $data = base64_decode($_POST["file_b64"]);
                $f = finfo_open();
                $mime_type = finfo_buffer($f, $data, FILEINFO_MIME_TYPE);
                finfo_close($f);

                if (self::$allowed[$mime_type]){
                    if (file_put_contents(realpath(__DIR__."/../../../../../".self::$uploadsDir)."/".$fileTemp.".".$extension,$data)){
                        $result[] = [
                            "urlPath" => self::$baseUrl."/".$fileTemp.".".$extension,
                            "original" => $_POST["filename"]
                        ];
                    }else{
                        Api::addWarning("No ha podido guardarse el archivo ".$filename." en ".realpath(__DIR__."/../../../../../".self::$uploadsDir).". ".json_encode(error_get_last()));
                    }

                }else{
                    Api::addWarning("El mime del archivo no está permitido ");
                    
                } 
            }            
        }else{

            if (!isset($request["field"])) $request["field"] = "file";
            if (!isset($_FILES[$request['field']])) Api::error(new ApiError('No se encuentra el campo del archivo en el envio'));
        
            if (!is_array($_FILES[$request["field"]]["name"])) {
                $files = [$_FILES[$request["field"]]];
            } else {
                $files = [];
                foreach ($_FILES[$request["field"]] as $k => $l) {
                    foreach ($l as $i => $v) {
                        if (!array_key_exists($i, $files)) $files[$i] = array();
                        $files[$i][$k] = $v;
                    }
                }
            }
            
            foreach ($files as $file) {
                try{
                    $result[] = self::uploadFile($request,$file);    
                }catch(Exception $e){
                    Api::addWarning("Ha ocurrido un error al subir el fichero ".$file["name"]);    
                }
            }
        }
        return $result;
        
        
    }
    static function uploadFile($request,$file){
        $handle = new \verot\Upload\Upload($file);
        if ($handle->uploaded) {

            $handle->file_name_body_add   = time();
            $handle->image_resize         = true;
            $handle->image_x              = 800;
            $handle->image_ratio_y        = true;

            if (isset($request["options"])){
                foreach($request["options"] as $key => $value){
                    if (property_exists($handle,$key)){
                        $handle[$key] = $value;
                    }else{
                        Api::addWarning("La propiedad ".$key." no se puede establecer en uploads");
                    }
                }
            }

            $handle->allowed = [
                $handle->mime_types['pdf'],
                $handle->mime_types['doc'],
                $handle->mime_types['docx'],
                $handle->mime_types['xls'],
                $handle->mime_types['xlsx'],
                $handle->mime_types['csv'],
                'image/*'
            ];

            $handle->process(realpath(__DIR__."/../../../../../".self::$uploadsDir."/"));

            if ($handle->processed) {
                $fileName = self::$baseUrl."/".$handle->file_dst_name;
                $handle->clean();
                return ["urlPath" => $fileName,"original" => $file["name"]];
            } else {
                throw new ApiError($handle->error);
            }
        }else{
            Api::addWarning("No se ha podido subir el archivo ");
        }
    }
}