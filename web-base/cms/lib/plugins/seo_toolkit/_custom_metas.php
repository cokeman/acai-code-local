<?
// Este archivo no existe en el plugin base, solo se crea en la web para sus personalizaciones.

global $domain_for_canonicals;

if($_SERVER["HTTP_HOST"] == 'carolina-marrero.com' | $_SERVER["HTTP_HOST"] == 'www.carolina-marrero.com'){
    $domain_for_canonicals = 'carolinamarrero.com';
}
