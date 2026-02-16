<?
global $CURRENT_USER;

array_push($var, array(
    'menuName'  => 'CMS Api',
    'menuOrder' => '13213231', // sort first
    'tableName' => 'cms_api_plugin',
    'plugin' => true,
    'adminOnly' => $value["adminOnly"]
));
?>