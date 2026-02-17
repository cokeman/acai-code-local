<?php
global $headerLayout; 

// Definir el límite global para el tamaño de imágenes WebP
if (!defined('LIMIT_WEBP_SIZE')) {
    define('LIMIT_WEBP_SIZE', 3000);
} 

if (!class_exists("CustomCode")){
    class CustomCode {
        private static $active = false;
        private static $headerLayout = [];
        public static $webp = false;
        public static $CMS_FOLDER = "cms";
        public static $iniConfig = null;
        /**
         * Inicializa y carga las variables
         * @return null
         */
        static function init(){
            $headerLayout = @file_get_contents(realpath(__DIR__."/layout.json"));
            $headerLayout = json_decode($headerLayout,true);
            if (@$headerLayout["active"]) self::$active = true;
            if (@$headerLayout) self::$headerLayout = $headerLayout;
        }
        /**
         * Muestra el custom style
         * @param header: Booleana que decide si se llevan cabeceras o no
         * @return cadena del estilo
         */
        static function getStyle($header) {
            if ($header) header("Content-type: text/css",true);
            echo (isset(self::$headerLayout["style"])) ? self::$headerLayout["style"] : "";
        }
        /**
         * Muestra el custom javascript
         * @param header: Booleana que decide si se llevan cabeceras o no
         * @return cadena del javascript
         */
        static function getJavascript($header) {
            if ($header) header("Content-type: text/javascript",true);
            if (!@session_id()) session_start();
            echo (isset(self::$headerLayout["javascript"])) ? @$preCode.self::$headerLayout["javascript"] : "";
        }
        
        /**
         * Muestra el menu de la web con TailWind
         * @param parentNum: Nivel de profundidad
         * @param where: parametro de búsqueda
         * @return cadena menu
         */
        static function Menu($parentNum=0,$options = [],$where = "AND visible_en_el_menu=1"){
            global $configuracionRecord, $configuracionTienda, $categorias_cursos_id;
            
            $optionsDefault = [
                "tableName"         => "apartados",
                "where"             => 'parentNum='.$parentNum.' '.$where,
                "allowSearch"       => 0,
                "liClass"           => 'block px-4',
                "liClass:active"    => 'active',
                'aClass'            => '',
                'aDropDownClass'    => '',
                'ulDropDownClass'   => ''
            ];
            
            if (@$options["bootstrap"]){
                $optionsDefault["liClass"]          = 'list-inline-item';
                $optionsDefault["liClass:active"]   = 'active';
                $optionsDefault['aClass']           = '';
                $optionsDefault['aDropDownClass']   = 'dropdown-toggle';
                $optionsDefault['ulDropDownClass']  = 'dropdown-menu multi-level';
            }
            
            foreach($options as $key => $value){
                $optionsDefault[$key] = $value;
            }
            
            list($apartadosRecords, $apartadosMetaData) = getRecords([
                "tableName"     =>  $optionsDefault["tableName"],
                "where"         =>  $optionsDefault["where"],
                "allowSearch"   =>  $optionsDefault["allowSearch"]
            ]);
            
            
            
            $cont2=0;
            
            foreach ($apartadosRecords as $cont => $record):

                $icono = '';
                $aclass = $optionsDefault["aClass"];
                $hasChilds = self::MenuHasChilds($record,0,$where);
                
                if ($record["parentNum"]==0) {
                    $clasedrop = @$optionsDefault["liClass"]; 
                }else{

                    $clasedrop=@$optionsDefault["submenuLiClass"] ?: @$optionsDefault["liClass"];
                }
                
                if (!$hasChilds){
                    
                    echo '<li class="'.@$clasedrop.self::checkIsActive($record, ' '.$optionsDefault["liClass:active"]).'">';
                    $child = self::MenuHasChilds($record,1,$where);
                    $child=@$child[0];
                    if (@$child){
                        if (@$child["enlace"]){
                            $enla = t($child,"enlace");
                            if ($enla==$child["enlace"]) $enla = RUTA_RAIZ.$enla;
                            echo '<a href="'.$enla.'" class="'.$aclass.'">'.$icono.''.t($record,"name").'</a>';
                        }else{
                            echo '<a href="'.RUTA_RAIZ.'/'.t($configuracionRecord,"contenidos").'/'.parsea_enlace(t($record,"name")).'/'.parsea_enlace(t($child,"name")).'/'.$record["num"].'/'.$child["num"].'.html" class="'.$aclass.'">'.$icono.''.t($record,"name").'</a>';
                        }
                    }else{
                        if ($record["enlace"]){
                            $enla = t($record,"enlace");
                            if ($enla==$record["enlace"]) $enla = RUTA_RAIZ.$enla;
                            echo '<a href="'.$enla.'" class="'.$aclass.'">'.$icono.''.t($record,"name").'</a>';
                        }else{
                            echo '<a href="'.RUTA_RAIZ.'/'.t($configuracionRecord,"apartados")."/".parsea_enlace(t($record,"name")).'/'.$record["num"].'.html" class="'.$aclass.'">'.$icono.''.t($record,"name").'</a>';
                        }
                    }
                    

                    echo '</li>';
                }else{
                    echo '<li class="'.$clasedrop.self::checkIsActive(" active").'" onclick="this.querySelector(\'ul\').classList.toggle(\'hidden\')" onmouseleave="if (!this.querySelector(\'ul\').classList.contains(\'hidden\')) this.querySelector(\'ul\').classList.add(\'hidden\')">';
                    echo '<a href="javascript:void(0);" class="'.$aclass.' _hasChilds">'.$icono.''.t($record,"name").'</a>';
                    
                    echo '<ul class="'.$optionsDefault["submenuUlClass"].'" role="menu">';
                    
                    self::Menu($record["num"],$options,$where);
                    echo '</ul>';
                    echo '</li>';

                }
            endforeach;

            if (HAY_TIENDA) {
                foreach ($configuracionTienda["categorias_nav_bd"] as $categoria):
                echo '<li class="visible-xs'.self::checkIsActive($categoria, ' '.$optionsDefault["liClass:active"]).'">';
                echo '<a href="'.t($categoria, "enlace").'" data-category="'.$categoria["num"].'">';
                if (@$categoria["icono"]) {
                    echo '<img src="'.parsea_imagen($categoria["icono"][0]["urlPath"]).'" alt="'.addslashes(t($categoria, "name")).'">';
                }
                echo t($categoria, "name");
                echo '</a></li>';
                endforeach;
            }

            return $apartadosRecords;
        }
        /**
         * Muestra registros de una tabla con busqueda
         * @param tableName: tabla
         * @param where: parametro de búsqueda
         * @param campos: parametro con los campos a recoger
         * @param cadena: parametro con la cadena a repetir : $1 - $2 con los indices de reemplazo de los campos
         * @return cadena 
         */
        static function getRecords($tableName=null,$where = '',$order = '',$limit = '',$campos = [],$filtros = '', $html = ''){
            global $TABLE_PREFIX;
            $html = base64_decode($html);
            
            $filtros = base64_decode($filtros);
            
            if ($filtros) $filtros = json_decode($filtros,true);
            
            if (!$campos) {echo $html;return $html;}
            if (!$tableName) {echo $html;return $html;}
            $tableName = str_replace($TABLE_PREFIX,"",$tableName);
            $tableName = $TABLE_PREFIX.$tableName;
            $prefixCampos = "";
            if (!in_array("num",$campos)) $prefixCampos = "num,";
            $sql = "SELECT ".$prefixCampos.strtolower(join(",",$campos))." FROM ".$tableName;
            if ($where) $sql.=" WHERE ".$where;
            if ($order) $sql.=" ORDER BY ".$order;
            if ($limit) $sql.=" LIMIT ".$limit;
            
            try{
                $records = mysql_query_fetch_all_assoc($sql);
                if (!@$records) {echo $html;return $html;}
                foreach($records as $record){
                    $result = $html;
                    foreach($campos as $index => $campo){
                        
                        $result = str_replace("$".intval($index+1),self::filterDataRecord(t($record,$campo),@$filtros[$index]),$result);
                    }
                    echo $result."\n";
                }
            }catch(Exception $e){
                echo $html;
            }
            return $html;
        }
        
        static function filterDataRecord($data = null,$filters = null){
            if (!$data) return null;
            if (!$filters) return $data;
            foreach($filters as $filter){
                if (!@$filter["fn"]) continue;
                switch(strtolower($filter["fn"])){
                    case "uppercase" : $data = strtoupper($data); break;
                    case "date" : 
                        if (!@$filter["params"][0]) continue 2;
                        
                        $data = @date($filter["params"][0],strtotime($data));
                    break;
                }
            }
            return $data;
        }
        /**
         * Valida si el menú tiene hijos
         * @param record: Registro de apartado
         * @param layout: tipo de layout
         * @param where: parametro de búsqueda     
         * @return true o false
         */    
        static function MenuHasChilds($record,$layout=0,$where="AND visible_en_el_menu=1"){

            if ($record["layout"]==$layout){
                list($apartadosRecords, $apartadosMetaData) = getRecords(array(
                    'tableName'   => 'apartados',
                    'where'     => 'parentNum='.$record["num"].' '.$where,
                    'allowSearch' => 0,
                ));
                return $apartadosRecords;
            }else{
                return false;
            }
        }
        /**
         * Separa una cadena por un caracter y devuelve el indice elegido en string
         * @param string: caracter separador
         * @param string: cadena a separar
         * @param int: indice a mostrar
         * @return cadena parseada
         */    
        static function explode($character,$string,$index=0){
            return explode(str_replace("_punto_",".",$character),$string)[$index];
        }
        /**
         * Comprueba si el enlace del registro pasado por parámetro es al que estamos accediendo
         * o es un padre del que estamos accediendo.
         * @param $record: Registro con el enlace
         * @param $html: HTML a devolver en caso de match
         */
        static function checkIsActive($record, $html = " class='active'") {
            if (!is_array($record)) return "";
            if ($_SERVER["REQUEST_URI"] === RUTA_RAIZ."/" && @$record["controlador"] === "index.php") return $html;
            if (@t($record,"enlace") && strpos($_SERVER["REQUEST_URI"], t($record, "enlace")) !== false && $record["enlace"] != "/") {
                return $html;
            }
            return "";
        }
        /**
         * Divide una variable en 2 insertando un SPAN al que añadir un texto delante y una clase
         * o es un padre del que estamos accediendo.
         * @param $preHTML: HTML antes del corte
         * @param $clase: clase para el span
         * @param $cadena: cadena a separar
         */
        static function halfSpan($preHTML = '', $clase = '', $cadena = '') {
            $sep = explode(" ",$cadena);
            $total = count($sep);
            $mitad = intval($total/2);
            $result = '';
            
            foreach($sep as $cont => $se){
                
                if ($cont == $mitad) $result.=$preHTML."<span class='".$clase."'>";
                $result.=$se." ";
            }
            $result.="</span>";
            return $result;
        }
        /**
         * Divide una variable en 2 insertando un SPAN al que añadir un texto delante y una clase
         * o es un padre del que estamos accediendo.
         * @param $preHTML: HTML antes del corte
         * @param $clase: clase para el span
         * @param $cadena: cadena a separar
         */
        static function image($width = null, $url = '') {
            if ($width){
                
            }else{
                return parsea_imagen($url);    
            }
            
        }
        static function redimensiona_imagen_larga($file,$info,$maxWidth=2800,$maxHeight=2800){
            if (@$info[0]<=$maxWidth && @$info[0]<=$maxHeight) return $file;

            /*$newFileSep = explode("/",$file);
            $fileName = $newFileSep[count($newFileSep)-1];
            array_pop($newFileSep);
            $newPath = join("/",$newFileSep);
            if (!file_exists($newPath."/big")){
                mkdir($newPath."/big",0777);
            }
            $newPath = $newPath."/big/".$fileName;
            if (!file_exists($newPath)){
                copy($file,$newPath);
            }*/
            $result = exec("mogrify -resize ".$maxWidth."x".$maxHeight." ".$file);
            return $file;
        }
        static function webp($img,$path){
            if (strpos(@$_SERVER['HTTP_USER_AGENT'], 'Chrome') === false && strpos(@$_SERVER['HTTP_USER_AGENT'], 'CriOS') === false) {
                return $img;
            }

            // Conseguimos el nombre del archivo y le quitamos la extensión
            $filename = pathinfo($img);
            $extension = $filename["extension"];
            $filename = $filename["filename"];
            
            if (strpos($img,self::$CMS_FOLDER."/uploads/") !== false){
                $newPath = "/".self::$CMS_FOLDER."/uploads/webp/".$filename.".webp";    
            }else{
                $pathInfo = pathinfo($img);
                $newPath = $pathInfo["dirname"]."/".$filename.".webp";
            }
            
            if (!file_exists($path.$newPath)){
                $result = shell_exec("cwebp -q 80 ".$path.$img." -o ".$path.$newPath);
                return $newPath;
            }else{
                return $newPath;
            }
        }
        static function imagec_inv($imagen,$thumb = null){

            return self::imagec($thumb,$imagen);
        }
        static function imagec($thumb=null,$imagen='',$url_completa=false){
            if (is_null(self::$iniConfig)) self::$iniConfig = file_exists(__DIR__."/custom-schema.ini.php") ? loadINI(__DIR__."/custom-schema.ini.php") : [];
            if (@self::$iniConfig["config"]["webp"] == 1 && !self::$webp) self::$webp = true;

            if(is_null($imagen) || $imagen == "") return self::defaultImage();

            if (preg_match('/(\-[0-9]{2,4}\-[a-z0-9]{6,7}\.)/',$imagen)) return $imagen;
            
            //if (USAR_WEBP || self::$webp) $imagen = self::webp($imagen);
            
            if ($thumb){
                
                if (strpos($imagen,"http")!==false) return $imagen;
                $file = $imagen;
                $filePath = realpath(__DIR__."/../../../../".$file);
                $pathPath = realpath(__DIR__."/../../../..");
                
                if (file_exists($filePath)) {
                    $timestamp = filemtime($filePath);
                }
                
                if (@$timestamp) { // Archivo encontrado y todo ok
                    $timestamp = base_convert($timestamp, 10, 36);

                    // Añadimos -timestamp al final del nombre del archivo
                    $parts = pathinfo($filePath);
                    
                    $carpeta = str_replace($parts['basename'], '', $file);
                    
                    if (!isset($parts["extension"])) return self::defaultImage();
                    if (!in_array(strtolower($parts["extension"]),["jpg","png","jpeg"])) return $imagen;
                    
                    //if (strtolower($parts["extension"]) == "pdf" || strtolower($parts["extension"]) == "svg" || strtolower($parts["extension"]) == "webp" || strtolower($parts["extension"]) == "gif") return $imagen;
                    $path = $carpeta.$parts['filename'].'-'.$thumb.'-'.$timestamp.'.'.$parts['extension'];
                    
                    $image_size = @getimagesize($filePath);
                    
                    if (!@$image_size) {
                        return $imagen;
                    }
                    if (@$image_size[0] > LIMIT_WEBP_SIZE || @$image_size[1] > LIMIT_WEBP_SIZE) return $imagen;
                
                    
                    if (!file_exists($pathPath.$path)){
                        
                        // 2025-12-05 - Anael, meti info y file dentro del try, estaban fuera.
                        try{
                            ob_start();
                            $info = getimagesizefromstring(file_get_contents(realpath(__DIR__."/../../../../".$file))); 
                            ob_end_clean();
                            $file = str_replace($pathPath,"",self::redimensiona_imagen_larga($filePath,$info));
                            $resultado = saveResampledImageAs($pathPath.$path, $filePath, $thumb, $thumb);    
                        }catch(Exception $e){
                            
                        }
                        
                    }
                    if (USAR_WEBP || self::$webp) {
                        return self::webp($path,$pathPath);
                    }else{
                        return $path;   
                    }
                }   
            }else{
                if (USAR_WEBP || self::$webp) $imagen = self::webp($imagen);
                
                if (!@$imagen) $imagen = RUTA_PLANTILLA."/images/default.png";
                if ($url_completa)
                    return protocol()."://".$_SERVER["HTTP_HOST"].str_replace("plupload/multiupload/../../","",$imagen);
                else
                    return str_replace("plupload/multiupload/../../","",$imagen);
            }
        }
        static function defaultImage(){
            return file_exists(__DIR__."/../../../..".RUTA_PLANTILLA."/images/sinimagen.png") ? RUTA_PLANTILLA."/images/sinimagen.png" : 'http://via.placeholder.com/640x360/dbe2ea/fff.png?text=Sin%20Imagen';
        }
    }
}

if (@$_REQUEST["getStyle"]){
    CustomCode::init();
    CustomCode::getStyle(true);
    die();
}
if (@$_REQUEST["getJavascript"]){
    CustomCode::init();
    CustomCode::getJavascript(true);
    die();
}

?>
