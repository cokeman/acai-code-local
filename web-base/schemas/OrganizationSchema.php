<?
require_once "Schema.php";
require_once dirname(__FILE__)."/../funciones.php";

class OrganizationSchema implements Schema {
    private $config;

    function __construct($config) {
        $this->config = $config;
    }

    function printJSON() {
        $json = array(
            "@context" => "http://schema.org",
            "@type" => "Organization",
            "name" => $this->config["tienda_nombre_empresa"],
            "email" => $this->config["correo_admin"],
            "url" => protocol()."://".$_SERVER["HTTP_HOST"].t($this->config, "enlace")
        );
        
        if (@$configuracionRecord["direccion"]) {
            $json["address"] = t($this->config, "direccion");
        }
        
        if (@$configuracionRecord["telefono"]) {
            $json["telephone"] = t($this->config, "telefono");
        }
        
        $json["logo"] = array(
            "@type" => "ImageObject"
        );
        if (file_exists(dirname(__FILE__)."/..".RUTA_PLANTILLA."/images/logo.png")) {
            $json["logo"]["url"] = parsea_imagen(RUTA_PLANTILLA."/images/logo.png", true);
        }
        else if (file_exists(dirname(__FILE__)."/..".RUTA_PLANTILLA."/images/logo.svg")) {
            $json["logo"]["url"] = parsea_imagen(RUTA_PLANTILLA."/images/logo.svg", true);
        }
        else if (file_exists(dirname(__FILE__)."/..".RUTA_PLANTILLA."/images/logo.jpg")) {
            $json["logo"]["url"] = parsea_imagen(RUTA_PLANTILLA."/images/logo.jpg", true);
        }
        
        echo '<script type="application/ld+json">';
        echo json_encode($json);
        echo '</script>';
    }
}
?>