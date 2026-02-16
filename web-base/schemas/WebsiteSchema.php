<?
require_once "Schema.php";
require_once dirname(__FILE__)."/../funciones.php";

class WebsiteSchema implements Schema {
    private $config;

    function __construct($config) {
        $this->config = $config;
    }

    function printJSON() {
        $json = array(
            "@context" => "http://schema.org",
            "@type" => "Website",
            "@id" => protocol()."://".$_SERVER["HTTP_HOST"],
            "name" => t($this->config, "titulo_de_pagina"),
            "about" => t($this->config, "metatag_descripcion"),
            "url" => protocol()."://".$_SERVER["HTTP_HOST"],
        );
        
        echo '<script type="application/ld+json">';
        echo json_encode($json);
        echo '</script>';
    }
}
?>