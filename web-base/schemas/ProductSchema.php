<?
require_once "Schema.php";
require_once dirname(__FILE__)."/../funciones.php";

class ProductSchema implements Schema {
    private $product;

    function __construct($product) {
        $this->product = $product;
    }

    function printJSON() {
        $json = array(
            "@context" => "http://schema.org",
            "@type" => "Product",
            "name" => t($this->product, "name"),
            "url" => protocol()."://".$_SERVER["HTTP_HOST"].t($this->product, "enlace")
        );

        $this->_setProductID($json);
        $this->_setProductDescription($json);
        $this->_setImage($json);
        $this->_setCategory($json);
        $this->_setColor($json);
        $this->_setOffers($json);
        $this->_setReviews($json);

        echo '<script type="application/ld+json">';
        echo json_encode($json);
        echo '</script>';
    }

    /**
     * The product identifier, such as ISBN. For example: meta itemprop="productID" content="isbn:123-456-789".
     */
    private function _setProductID(&$json) {
        if (@$this->product["referencia"]) {
            $json["productID"] = $this->product["referencia"];
        }
        else if (@$this->product["codigo"]) {
            $json["productID"] = $this->product["codigo"];
        }
        else if (@$this->product["title"] && @$this->product["name"]) {
            $json["productID"] = $this->product["title"];
        }
    }

    /**
     * A description of the item.
     */
    private function _setProductDescription(&$json) {
        if (@$this->product["descripcion"]) {
            $json["description"] = strip_tags(t($this->product, "descripcion"));
        }
    }

    /**
     * An image of the item. This can be a URL or a fully described ImageObject.
     */
    private function _setImage(&$json) {
        if (@$this->product["foto"]) {
            $json["image"] = parsea_imagen($this->product["foto"][0]["urlPath"], true);
        }
    }

    /**
     * A category for the item.
     * Greater signs or slashes can be used to informally indicate a category hierarchy.
     */
    private function _setCategory(&$json) {
        $category = $this->_getCategoryString();
        if ($category !== null) {
            $json["category"] = $category;
        }
    }

    /**
     * Creates a String with the categories separated by $separator
     */
    private function _getCategoryString($separator = ">") {
        $cat = intval(@$this->product["categoria"]);
        $cat = dame_registros("categorias_productos", "num=".$cat);
        $cat = @$cat[0];
        if (!@$cat) return null;

        $categories = array();
        array_unshift($categories, $cat);
        while (@$cat["parentNum"]) {
            $cat = dame_registros("categorias_productos", "num=".$cat["parentNum"]);
            $cat = @$cat[0];
            if (!@$cat) break;
            array_unshift($categories, $cat);
        }
        return join($separator, array_map(function($a) { return t($a, "name"); }, $categories));
    }

    /**
     * The color of the product.
     */
    private function _setColor(&$json) {
        if (@$this->product["color"]) {
            $json["color"] = t($this->product, "color");
        }
    }

    /**
     * An offer to provide this item—for example, an offer to sell a product, rent the DVD of a movie,
     * perform a service, or give away tickets to an event.
     */
    private function _setOffers(&$json) {
        $basicOfferPrice = parsea_decimales(@$this->product["precio_oferta"]);
        $seasonOffer = dame_oferta_producto($this->product);
        if (!@$seasonOffer["descuento"] && @$basicOfferPrice <= 0.0) {
            return;
        }

        $json["offers"] = array();

        // La oferta por temporada tiene preferencia sobre la normal
        if (@$seasonOffer["descuento"]) {
            $json["offers"]["price"] = round(parsea_decimales($this->product["precio"])*(1-parsea_decimales($seasonOffer)/100), 2);
            $json["offers"]["priceValidUntil"] = date("Y-m-d", strtotime($seasonOffer["hasta"]));
        }
        else { // Oferta normal
            $json["offers"]["price"] = parsea_decimales($basicOfferPrice);
        }
        $json["offers"]["priceCurrency"] = "EUR";
        if (@$this->product["stock"]) {
            $json["offers"]["inventoryLevel"] = intval(@$this->product["stock"]);
        }
    }

    /**
     * An offer to provide this item—for example, an offer to sell a product, rent the DVD of a movie,
     * perform a service, or give away tickets to an event.
     */
    private function _setReviews(&$json) {
        $reviews = dame_valoraciones_producto($this->product);
        if (!@$reviews) return;
        $avg = dame_media_valoracion_producto($this->product);
        $json["aggregateRating"] = array(
            "@type" => "AggregateRating",
            "ratingValue" => $avg,
            "reviewCount" => count($reviews)
        );
        $json["review"] = array();
        foreach ($reviews as $review):
        $r = array(
            "@type" => "Review",
            "datePublished" => date("Y-m-d", strtotime($review["updatedDate"])),
            "author" => "Anonymous",
            "reviewRating" => array(
                "@type" => "Rating",
                "bestRating" => "5",
                "ratingValue" => $review["valoracion"],
                "worstRating" => "1"
            )
        );
        if (@$review["comentario"]) $r["description"] = $review["comentario"];
        $json["review"][] = $r;
        endforeach;
    }
}
?>