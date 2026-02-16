<?
require_once "Schema.php";
require_once dirname(__FILE__)."/../funciones.php";

class BlogPostSchema implements Schema {
    private $post;

    function __construct($post) {
        $this->post = $post;
    }

    function printJSON() {
        $json = array(
            "@context" => "http://schema.org",
            "@type" => "BlogPosting",
            "headline" => t($this->post, "title"),
            "url" => protocol()."://".$_SERVER["HTTP_HOST"].t($this->post, "enlace")
        );
        
        $this->_setImage($json);
        $this->_setMainEntityOfPage($json);
        $this->_setPublisher($json);
        $this->_setAuthor($json);
        $this->_setArticleBody($json);
        $this->_setWordCount($json);
        $this->_setDatePublished($json);
        $this->_setKeywords($json);
        
        echo '<script type="application/ld+json">';
        echo json_encode($json);
        echo '</script>';
    }
    
    /**
     * An image of the item. This can be a URL or a fully described ImageObject.
     */
    private function _setImage(&$json) {
        $json["image"] = parsea_imagen(@$this->post["foto_principal"][0]["urlPath"], true);
    }
    
    /**
     * Indicates a page (or other CreativeWork) for which this thing is the main entity being described.
     */
    private function _setMainEntityOfPage(&$json) {
        $json["mainEntityOfPage"] = array(
            "@type" => "WebPage",
            "@id" => protocol()."://".$_SERVER["HTTP_HOST"]
        );
    }
    
    /**
     * The publisher of the creative work.
     */
    private function _setPublisher(&$json) {
        global $configuracionRecord;
        $json["publisher"] = array(
            "@type" => "Organization",
            "name" => $configuracionRecord["tienda_nombre_empresa"],
            "email" => $configuracionRecord["correo_admin"]
        );
        
        if (@$configuracionRecord["direccion"]) {
            $json["publisher"]["address"] = t($configuracionRecord, "direccion");
        }
        
        if (@$configuracionRecord["telefono"]) {
            $json["publisher"]["telephone"] = t($configuracionRecord, "telefono");
        }
        
        $json["publisher"]["logo"] = array(
            "@type" => "ImageObject"
        );
        if (file_exists(dirname(__FILE__)."/..".RUTA_PLANTILLA."/images/logo.png")) {
            $json["publisher"]["logo"]["url"] = parsea_imagen(RUTA_PLANTILLA."/images/logo.png", true);
        }
        else if (file_exists(dirname(__FILE__)."/..".RUTA_PLANTILLA."/images/logo.svg")) {
            $json["publisher"]["logo"]["url"] = parsea_imagen(RUTA_PLANTILLA."/images/logo.svg", true);
        }
        else if (file_exists(dirname(__FILE__)."/..".RUTA_PLANTILLA."/images/logo.jpg")) {
            $json["publisher"]["logo"]["url"] = parsea_imagen(RUTA_PLANTILLA."/images/logo.jpg", true);
        }
    }
    
    /**
     * The author of this content or rating.
     * Please note that author is special in that HTML 5 provides a special
     * mechanism for indicating authorship via the rel tag. 
     * That is equivalent to this and may be used interchangeably.
     */
    private function _setAuthor(&$json) {
        global $configuracionRecord;
        $json["author"] = array(
            "@type" => "Organization",
            "name" => $configuracionRecord["tienda_nombre_empresa"],
            "email" => $configuracionRecord["correo_admin"]
        );
        
        if (@$configuracionRecord["direccion"]) {
            $json["author"]["address"] = t($configuracionRecord, "direccion");
        }
        
        if (@$configuracionRecord["telefono"]) {
            $json["author"]["telephone"] = t($configuracionRecord, "telefono");
        }
        
        $json["author"]["logo"] = array(
            "@type" => "ImageObject"
        );
        if (file_exists(dirname(__FILE__)."/..".RUTA_PLANTILLA."/images/logo.png")) {
            $json["author"]["logo"]["url"] = parsea_imagen(RUTA_PLANTILLA."/images/logo.png", true);
        }
        else if (file_exists(dirname(__FILE__)."/..".RUTA_PLANTILLA."/images/logo.svg")) {
            $json["author"]["logo"]["url"] = parsea_imagen(RUTA_PLANTILLA."/images/logo.svg", true);
        }
        else if (file_exists(dirname(__FILE__)."/..".RUTA_PLANTILLA."/images/logo.jpg")) {
            $json["author"]["logo"]["url"] = parsea_imagen(RUTA_PLANTILLA."/images/logo.jpg", true);
        }
    }
    
    /**
     * The actual body of the article.
     */
    private function _setArticleBody(&$json) {
        if (@$this->post["content"]) {
            $json["articleBody"] = strip_tags(t($this->post, "content"));
        }
    }
    
    /**
     * The number of words in the text of the Article.
     */
    private function _setWordCount(&$json) {
        if (@$this->post["content"]) {
            $json["wordCount"] = str_word_count(strip_tags(t($this->post, "content")));
        }
    }
    
    /**
     * Date of first broadcast/publication.
     */
    private function _setDatePublished(&$json) {
        $json["datePublished"] = date("Y-m-d H:i:s", strtotime($this->post["fecha"]));
        $json["dateModified"] = date("Y-m-d H:i:s", strtotime($this->post["fecha"]));
    }
    
    /**
     * Keywords or tags used to describe this content.
     * Multiple entries in a keywords list are typically delimited by commas.
     */
    private function _setKeywords(&$json) {
        if (@$this->post["tags_bd"]) {
            $json["keywords"] = join(",", $this->post["tags_bd"]);
        }
    }
}
?>