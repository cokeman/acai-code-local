<?
if (@$_REQUEST["userId"]) {
    $_SESSION["userId"] = $_REQUEST["userId"];
}
if (!defined("NOMBRE_TABLA"))
    define("NOMBRE_TABLA",  "cms_valoraciones_productos");

if (!defined("ARCHIVO"))
    define("ARCHIVO", dirname(__FILE__)."/../".CMS_FOLDER."/data/schema/valoraciones_productos.ini.php");

if (@$_REQUEST["valorar"]) {
    session_start();
    require_once(dirname(__FILE__)."/../lib/variables.php");
    require_once(dirname(__FILE__)."/../idiomas/espanol.php");
    
    if (intval($_REQUEST["valorar"]) <= 0) die("-1");
    if (intval($_REQUEST["valoracion"]) <= 0 || intval($_REQUEST["valoracion"]) > 5) die("-2");
    
    $val = $_REQUEST["valorar"];
    $puntos = $_REQUEST["valoracion"];
    
    $valoracion = mysql_fetch_assoc(mysql_query("SELECT * FROM ".NOMBRE_TABLA." WHERE producto='".mysql_real_escape_string($val)."' AND id_usuario='".$_SESSION["userId"]."'"));
    if (!@$valoracion) {
        mysql_query("INSERT INTO ".NOMBRE_TABLA." SET num=NULL, dragSortOrder='".time()."', createdDate='".date("Y-m-d H:i:s")."', updatedDate='".date("Y-m-d H:i:s")."', createdByUserNum=1, updatedByUserNum=1, producto='".mysql_real_escape_string($val)."', valoracion='".mysql_real_escape_string($puntos)."', id_usuario='".$_SESSION["userId"]."', comentario='".mysql_real_escape_string(@$_REQUEST["comentario"])."'") or die(mysql_error());
    }
    else {
        mysql_query("UPDATE ".NOMBRE_TABLA." SET valoracion='".mysql_real_escape_string($puntos)."', comentario='".mysql_real_escape_string(@$_REQUEST["comentario"])."', updatedDate='".date("Y-m-d H:i:s")."' WHERE producto='".mysql_real_escape_string($val)."' AND id_usuario='".$_SESSION["userId"]."'") or die(mysql_error());
    }
    die("ok");
}
if (@$_REQUEST["producto"]) {
    $producto = mysql_fetch_assoc(mysql_query("SELECT * FROM cms_productos WHERE num='".mysql_real_escape_string($_REQUEST["producto"])."'"));
}
if (!@$producto) return;

$configuracionTienda = dame_registros("configuracion_tienda");
$configuracionTienda = @$configuracionTienda[0];
if (!@$configuracionTienda["valoraciones"]) return;
// Creamos la tabla
$sql = "CREATE TABLE IF NOT EXISTS ".NOMBRE_TABLA." (
    num int(10) NOT NULL AUTO_INCREMENT,
    createdDate datetime DEFAULT NULL,
    createdByUserNum int(10) DEFAULT 1,
    updatedDate datetime DEFAULT NULL,
    updatedByUserNum int(10) DEFAULT 1,
    dragSortOrder int(10) DEFAULT NULL,
    producto int(10) NOT NULL,
    valoracion int(10) NOT NULL,
    id_usuario mediumtext NOT NULL,
    comentario mediumtext NOT NULL,
    PRIMARY KEY(num)
)";
mysql_query($sql);

// Creamos el schema

$schema = ';<?php die(\'This is not a program file.\'); exit; ?>

_detailPage = ""
_disableAdd = 0
_disableErase = 0
_filenameFields = ""
_hideRecordsFromDisabledAccounts = 0
_listPage = ""
_maxRecords = ""
_maxRecordsPerUser = ""
apartadodemenu = 0
listPageFields = "dragSortOrder, producto, valoracion"
listPageOrder = "dragSortOrder DESC"
listPageSearchFields = ""
menuDesc = ""
menuDisplay = ""
menuHidden = 0
menuName = "Valoraciones"
menuOrder = 1510076463
menuType = "multi"

[num]
order = 1
type = "none"
label = "Record Number"
isSystemField = 1

[createdDate]
order = 2
type = "none"
label = "Created"
isSystemField = 1

[createdByUserNum]
order = 3
type = "none"
label = "Created By"
isSystemField = 1

[updatedDate]
order = 4
type = "none"
label = "Last Updated"
isSystemField = 1

[updatedByUserNum]
order = 5
type = "none"
label = "Last Updated By"
isSystemField = 1

[dragSortOrder]
order = 6
label = "Order"
type = "none"

[producto]
order = 7
label = "Producto"
type = "list"
isRequired = 1
isUnique = 0
listType = "pulldown"
optionsType = "table"
optionsTablename = "productos"
optionsValueField = "num"
optionsLabelField = "name"

[valoracion]
order = 1510076528
label = "Valoracion"
type = "textfield"
defaultValue = ""
description = ""
fieldWidth = ""
tipoAtributo = 0
tipoTags = 0
isPasswordField = 0
isRequired = 1
isUnique = 0
minLength = ""
maxLength = ""
charsetRule = ""
charset = ""

[id_usuario]
order = 1510079389
label = "ID Usuario"
type = "textfield"
adminOnly = 1
defaultValue = ""
description = ""
fieldWidth = ""
tipoAtributo = 0
tipoTags = 0
isPasswordField = 0
isRequired = 1
isUnique = 0
minLength = ""
maxLength = ""
charsetRule = ""
charset = ""

[comentario]
order = 1510079390
label = "Comentario"
type = "textfield"
defaultValue = ""
description = ""
fieldWidth = ""
tipoAtributo = 0
tipoTags = 0
isPasswordField = 0
isRequired = 1
isUnique = 0
minLength = ""
maxLength = ""
charsetRule = ""
charset = ""
';
if (!file_exists(ARCHIVO)) {
    file_put_contents(ARCHIVO, $schema);
}

$valoracion = dame_valoracion_producto($producto);

?>



<div class="valoraciones">
    <p class="text-center"><?=t_var("Valora este producto según tu compra:");?></p>
    <div class="separa-20"></div>
    <textarea name="comentario" class="form-control" rows="4" placeholder="<?=t_var("Comentario");?>"><?=@$valoracion["comentario"];?></textarea>
    <div class="separa-20"></div>
    <div class="stars">
        <div>
            <? for ($i=1;$i<=5;$i++) {?>
            <button data-value="<?=$i;?>" <? if (intval(@$valoracion["valoracion"]) >= $i) echo 'class="active"';?> data-producto="<?=$producto["num"];?>"><i class="fa fa-star"></i></button>
            <? }?>
        </div>
    </div>
</div>

<script>
    window.onload = function() {
        if (typeof valorar !== 'function') {
            function valorar() {
                $(".stars button").click(function(e) {
                    e.preventDefault();
                    var valor = parseInt($(this).data("value"));
                    var producto = parseInt($(this).data("producto"));
                    var comentario = $(this);
                    while (!$(comentario).hasClass("valoraciones")) {
                        comentario = $(comentario).parent();
                    }
                    comentario = $(comentario).find("textarea").val();

                    $.ajax({
                        url: "/modulos_php/modulo_valoraciones.php",
                        method: "post",
                        data: {valoracion: valor, valorar: producto, id: "<?=$_SESSION["userId"];?>", comentario: comentario},
                        success: function(data) {
                            switch (data) {
                                case "-1":
                                    swal("Error", "<?=t_var("No se ha seleccionado ningún producto");?>", "error");
                                    break;
                                case "-2":
                                    swal("Error", "<?=t_var("No se ha enviado ninguna valoración");?>", "error");
                                    break;
                                case "ok":
                                    $(".stars button").each(function() {
                                        if (parseInt($(this).data("value")) <= valor) {
                                            $(this).addClass("active");
                                        }
                                        else {
                                            $(this).removeClass("active");
                                        }
                                    });
                                    swal("<?=t_var("¡Hecho!");?>", "<?=t_var("Valoración enviada");?>", "success");
                                    break;
                                default:
                                    console.log(data);
                            }
                        },
                        error: function(data) {
                            console.log(data);
                        }
                    });
                });
            }
        }
        valorar();
    };
</script>