<?
require_once $_SERVER["DOCUMENT_ROOT"]."/".CMS_VIEWER_LIB;

class ServiceWorker {
    /// Nombre de la cache
    private $_cache_name;
    /// Lista de archivos para cachear
    private $_files;
    /// Versión de la cache
    private $_version;
    /// Base para usar en versiones de archivos
    private $_base = 36;

    public function __construct($files = null, $cache_name = null) {
        if (@$files) {
            $this->_files = $files;
        }
        else {
            $this->_files = array(
                "template/estandar/style.css",
                "template/estandar/css/bootstrap.min.css",
                "template/estandar/css/swipebox.min.css",
                "template/estandar/css/jquery.bxslider.css",
                "template/estandar/css/animate.min.css",
                "template/estandar/css/images/loader.gif",
                "template/estandar/css/images/icons.svg",
                "template/estandar/css/images/controls.png",
                "template/estandar/css/images/bx_loader.gif",
                'template/estandar/js/sw-controller.php',
                'template/estandar/js/mis-scripts.js',
                'template/estandar/js/wow.min.js',
                'template/estandar/js/jquery.swipebox.min.js',
                'template/estandar/js/jquery.bxslider.min.js',
                'template/estandar/js/bootstrap.min.js',
            );
        }

        $this->_cache_name = @$cache_name ? $cache_name : parsea_enlace($_SERVER["HTTP_HOST"]);
        $this->_version = 0;
        
        $this->_hashFiles();
        $this->_calculateVersion();
    }
    
    public function getFiles() { return $this->_files; }
    public function getCacheName() { return $this->_cache_name; }
    public function getVersion() { return $this->_version; }
    
    private function _hashFiles() {
        /// Recorremos la lista de los archivos para añadirle el hash al final
        foreach ($this->_files as $cont => $file):
        if (!file_exists($_SERVER["DOCUMENT_ROOT"].'/'.$file)) {
            // Lo eliminamos del array
            unset($this->_files[$cont]);
        }
        else {
            $this->_files[$cont] = h($file, false, $this->_version);
        }
        endforeach;
    }
    
    
    
    private function _calculateVersion() {
        $this->_checkDBModifications();
        $this->_version = base_convert($this->_version, 10, $this->_base);
    }
    
    private function _checkDBModifications() {
        $mod = mysql_fetch_assoc(mysql_query("SELECT MAX(UPDATE_TIME) AS updTime FROM information_schema.tables WHERE TABLE_SCHEMA='raullg'"));
        $this->_version += strtotime($mod["updTime"]);
    }
}

?>