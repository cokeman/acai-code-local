<?
global $menu;
echo "install.php exec";

  # Reseteamos el cache completo del sistema super guay
  SchemaAPI::resetSessionData();
  PluginsAPI::resetSessionData();
  SchemaAPI::getInstance()->createMissingSchemaTablesAndFields();
  clearAlertsAndNotices(); // don't display alerts about adding new fields


?>
