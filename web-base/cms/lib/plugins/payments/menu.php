<?
global $CURRENT_USER;

if (@$CURRENT_USER['isAdmin']) {
    array_push($var, array(
        'menuName'  => 'CMS Payments',
        'menuOrder' => '13213230', // sort first
        'tableName' => 'cms_payments',
        'plugin' => true,
        'adminOnly' => $value["adminOnly"]
    ));
}