<?
require_once str_replace("/modulos_php/favoritos","",dirname(__FILE__))."/sesion.php";
require_once str_replace("/modulos_php/favoritos","",dirname(__FILE__))."/funciones.php";

$resultado = array();
header('Content-Type: application/json');
if (@$_SESSION["usernum"]&&dame_registros("usuarios","num=".@$_SESSION["usernum"],"",1)&&dame_registros("productos","","dragSortOrder ASC",1)){
	
	// LISTAR FAVORITOS

	if (@$_REQUEST["dame_favoritos"]){
		$favoritos = dame_registros("favoritos","usuario='".@$_SESSION["usernum"]."'","dragSortOrder ASC");
		if (!@$favoritos) $favoritos = array();
		$resultado["resultado"] = "ok";
		$resultado["datos"] = $favoritos;		
		echo json_encode($resultado);
		die();
	}

	// INSERTAR FAVORITOS

	if (@$_REQUEST["insertar_favorito"]){

		if (intval(@$_REQUEST["producto"])>0){
			$sql = "insert into cms_favoritos set num=null, createdDate='".date("Y-m-d H:i:s",time())."',updatedDate='".date("Y-m-d H:i:s",time())."', createdByUserNum=1, updatedByUserNum=1, dragSortOrder=".time();
			$sql.=", producto=".intval(@$_REQUEST["producto"]).", usuario=".$_SESSION["usernum"];
			
			if (mysql_query($sql)){
				$resultado["resultado"] = "ok";
				echo json_encode($resultado);
				die();

			}

		}

	}

	// ELIMINAR FAVORITO

	if (@$_REQUEST["eliminar_favorito"]){
		if (intval(@$_REQUEST["producto"])>0){
			$sql = "delete from cms_favoritos where usuario=".$_SESSION["usernum"]." AND producto=".intval(@$_REQUEST["producto"]);
			
			if (mysql_query($sql)){
				$resultado["resultado"] = "ok";
				echo json_encode($resultado);
				die();

			}

		}

	}

}

$resultado["resultado"] = "error";
echo json_encode($resultado);
die();


?>