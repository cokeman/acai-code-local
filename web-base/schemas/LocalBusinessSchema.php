<?
require_once "Schema.php";
require_once dirname(__FILE__)."/../funciones.php";

class LocalBusinessSchema implements Schema {
    private $config;
    
    function __construct($config) {
        $this->config = $config;
    }

    function printJSON() {
        $json = array(
            "@context" => "http://schema.org",
            "@type" => "LocalBusiness",
            "name" => t($this->config, "tienda_nombre_empresa"),
            "url" => "https://".$_SERVER["HTTP_HOST"]
        );
        
        $this->_setImage($json);
        $this->_setPhone($json);
        if (!$this->_setAddress($json)) return;
        $this->_setPriceRange($json);
        
        echo '<script type="application/ld+json">';
        echo json_encode($json);
        echo '</script>';
    }
    
    private function _setImage(&$json) {
        if (file_exists(dirname(__FILE__)."/..".RUTA_PLANTILLA."/images/logo.png")) {
            $json["image"] = parsea_imagen(RUTA_PLANTILLA."/images/logo.png", true);
        }
        else if (file_exists(dirname(__FILE__)."/..".RUTA_PLANTILLA."/images/logo.svg")) {
            $json["image"] = parsea_imagen(RUTA_PLANTILLA."/images/logo.svg", true);
        }
        else if (file_exists(dirname(__FILE__)."/..".RUTA_PLANTILLA."/images/logo.jpg")) {
            $json["image"] = parsea_imagen(RUTA_PLANTILLA."/images/logo.jpg", true);
        }
    }
    
    private function _setPhone(&$json) {
        if (@$this->config["telefono"]) {
            $telefono = $this->config["telefono"];
            $telefono = preg_replace("([\(\) ])", "", $telefono);
            if (strpos($telefono, "+34") === false) {
                $telefono = "+34".$telefono;
            }
            $json["telephone"] = $telefono;
        }
    }
    
    private function _setAddress(&$json) {
        if (!@$this->config["localbusiness_localidad"] || !@$this->config["localbusiness_region"] || !@$this->config["localbusiness_codigo_postal"] || !@$this->config["localbusiness_pais"]) return false;
        $json["address"] = array(
            "@type" => "PostalAddress",
            "streetAddress" => $this->config["direccion"],
            "addressLocality" => t($this->config, "localbusiness_localidad"),
            "addressRegion" => t($this->config, "localbusiness_region"),
            "postalCode" => t($this->config, "localbusiness_codigo_postal"),
            "addressCountry" => t($this->config, "localbusiness_pais")
        );
        return true;
    }
    
    private function _setPriceRange(&$json) {   
        if (@$this->config["localbusiness_rango_de_precios"]) {
            $json["priceRange"] = t($this->config, "localbusiness_rango_de_precios");
        }
    }
}
?>