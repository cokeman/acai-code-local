<?php
  require_once str_replace("/idiomas","",dirname(__FILE__))."/".CMS_FOLDER."/lib/viewer_functions.php";

  define('PREFIJO',"");

list($apartadosRecords, $apartadosMetaData) = getRecords(array(
  'tableName'   => 'textos_generales',
  'allowSearch' => 0,
));	  
  foreach ($apartadosRecords as $record):
  	if (!defined($record["identificador"])) define ($record["identificador"],t($record,"texto"));
  endforeach;
?>
