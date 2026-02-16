<?
require_once str_replace("/modulos_php/favoritos","",dirname(__FILE__))."/sesion.php";
require_once str_replace("/modulos_php/favoritos","",dirname(__FILE__))."/funciones.php";

// SABER SI LO TENGO EN FAVORITO

function es_favorito($usuario=0,$producto=0){
	$favoritos = dame_registros("favoritos","usuario='".$usuario."' AND producto=".$producto,"dragSortOrder ASC",1);
	if (!@$favoritos) $favoritos = array();
	return $favoritos;
}

// LISTAR FAVORITOS

function dame_favoritos($usuario=0){
	$favoritos = dame_registros("favoritos","usuario='".$usuario."'","dragSortOrder ASC");
	if (!@$favoritos) $favoritos = array();
	return $favoritos;
}

// INSERTAR FAVORITOS

function insertar_favorito($usuario=0,$producto=0){

	if (intval($producto)>0){
		$sql = "insert into cms_favoritos set num=null, createdDate='".date("Y-m-d H:i:s",time())."',updatedDate='".date("Y-m-d H:i:s",time())."', createdByUserNum=1, updatedByUserNum=1, dragSortOrder=".time();
		$sql.="producto=".intval($producto).", usuario=".$usuario;

		if (mysql_query($sql)){
			return 1;
		}

	}

	return 0;
}

// ELIMINAR FAVORITO

function eliminar_favorito($num=0){

	if (intval($num>0)){
		$sql = "delete from cms_favoritos where num=".$num;
		
		if (mysql_query($sql)){
			return 1;

		}

	}

	return 0;
}




?>