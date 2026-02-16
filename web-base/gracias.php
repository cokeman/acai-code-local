<?
require_once "sesion.php";
require_once "funciones.php";

$apartado = dame_registros("otros_contenidos", "controlador='gracias.php'");
$apartado = @$apartado[0];

include "header.php";

echo tpl("gracias", array(
    "apartado" => $apartado
));

include "footer.php";
?>