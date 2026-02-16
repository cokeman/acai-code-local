<?
class FileJoiner {
    private $_files;
    private $_fileContent;
    private $_type;
    private $_target;
    
    public function __construct($files, $type, $target) {
        $this->_files = $files;
        $this->_type = $type;
        $this->_target = $target;
    }
    
    private function _joinFiles() {
        $this->_fileContent = "";
        foreach ($this->_files as $file):
        $file = $file;
        if (file_exists($file)) {
            $this->_fileContent .= file_get_contents($file)."\n";
        }
        endforeach;
    }
    
    public function getFile() {
        $target = $this->_target;
        if (file_exists($target)) {
            $targetTime = filemtime($target);
            // Recorremos la lista de archivos a ver si hay alguno más nuevo que el generado
            foreach ($this->_files as $file):
            $file = $file;
            if (file_exists($file)) {
                if (filemtime($file) > $targetTime) {
                    $hayNuevos = true;
                    break;
                }
            }
            endforeach;
            if (!@$hayNuevos) return file_get_contents($target);
        }
        // Generamos un nuevo archivo
        $this->_joinFiles();
        file_put_contents($target, $this->_fileContent);
        
        return file_get_contents($target);
    }
    
    public function getContentType() {
        $ct = "Content-Type: ";
        switch ($this->_type) {
            case "js":
                return $ct."application/javascript";
            case "css":
                return $ct."text/css";
            default: return $ct."text/plaintext";
        }
    }
}
?>