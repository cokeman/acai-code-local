<?
ini_set('display_errors', 1); // t
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);  
global $enlace;
global $tabla;
global $registro;
if (!defined("BASE_PATH")) define("BASE_PATH","");
require_once __DIR__."/../../../../sesion.php";
require_once __DIR__."/../../../../funciones.php";
require_once __DIR__."/builder_functions.php";

if (file_exists(__DIR__."/../cms_api/v3/CmsApi.class.php")) require_once __DIR__."/../cms_api/v3/CmsApi.class.php";

if (isset($enlace) && strpos($enlace,"/errorcode/") === 0){
    die(BuilderModule('../../../cms/lib/plugins/builder_saas/modulos/errorcode',[
        "imageUrl" => "data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pg0KPCEtLSBHZW5lcmF0b3I6IEFkb2JlIElsbHVzdHJhdG9yIDE5LjAuMCwgU1ZHIEV4cG9ydCBQbHVnLUluIC4gU1ZHIFZlcnNpb246IDYuMDAgQnVpbGQgMCkgIC0tPg0KPHN2ZyB2ZXJzaW9uPSIxLjEiIGlkPSJDYXBhXzEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIHg9IjBweCIgeT0iMHB4Ig0KCSB2aWV3Qm94PSIwIDAgNTExLjk5OSA1MTEuOTk5IiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCA1MTEuOTk5IDUxMS45OTk7IiB4bWw6c3BhY2U9InByZXNlcnZlIj4NCjxnPg0KCTxnPg0KCQk8Zz4NCgkJCTxwYXRoIGQ9Ik0yMDMuMDMsODguNzA4Yy00LjE0MywwLTcuNSwzLjM1Ny03LjUsNy41djEuMjk4YzAsNC4xNDMsMy4zNTcsNy41LDcuNSw3LjVjNC4xNDMsMCw3LjUtMy4zNTcsNy41LTcuNXYtMS4yOTgNCgkJCQlDMjEwLjUzLDkyLjA2NSwyMDcuMTcyLDg4LjcwOCwyMDMuMDMsODguNzA4eiIvPg0KCQkJPHBhdGggZD0iTTQzMS4wOTMsMTA0Ljg1NWgtMjkuMTI2Yy0yMC4yMjIsMC0zOC45NTUtMTAuMDctNTAuMTA5LTI2LjkzN2MtMjAuNDc4LTMwLjk2OC01NC44NjctNDkuNDU1LTkxLjk5My00OS40NTVoLTQyLjQxMw0KCQkJCWMtMTkuNzkyLDAtMzkuMTE1LDUuMDAxLTU2LjMwNywxNC40OWMtMC42MDQtMC42NzMtMS4yMjYtMS4zMzUtMS44NzItMS45ODJsLTI0LjA1NC0yNC4wNTRjLTYuMTctNi4xNjktMTYuMjA4LTYuMTY5LTIyLjM4LDANCgkJCQlMODguNzg1LDQwLjk2OWMtMTkuNDMzLDE5LjQzNC0xOS40MzMsNTEuMDU0LDAsNzAuNDg4YzQuMjk0LDQuMjk0LDkuMTg1LDcuNjM1LDE0LjQwNSwxMC4wMzENCgkJCQljLTEuNiw3Ljc2Mi0yLjQxOSwxNS42ODktMi40MTksMjMuNjU1djUwLjE5OGMwLDMzLjAzNCwxNS40ODYsNjMuMDQ3LDM5LjkwMSw4Mi41MzljLTEwLjI0OSwxNi4yMzItMTUuODI3LDM0LjU1Mi0xNi4yNzEsNTMuNTE4DQoJCQkJYy0yNS4wMDYsMTcuNjIyLTYyLjkyNiwyNC45ODYtMTAyLjM2OSwxOS43MjNjLTkuMjQtMS4yMy0xNy42MDYsMy44MTMtMjAuODA1LDEyLjU2Yy0zLjE2Nyw4LjY2My0wLjA2MywxNy44MjMsNy43MjUsMjIuNzk0DQoJCQkJYzQ4Ljc1NywzMS4xMjksMTA5LjI0MSwzNy43NjYsMTU2Ljg5NiwzOS45MzJjMS4xOTksMC41OCwyLjQyNCwxLjE0MiwzLjY4MiwxLjY4MmMwLjU0MywxNC44MzMsMC42MDQsMzAuODA1LDAuMTA5LDM3LjI2MQ0KCQkJCWMtMS4zNDksMTcuNTQ0LDExLjgyNywzMi45MTUsMjkuMzcyLDM0LjI2NWMwLjgxNCwwLjA2MywxLjY0NiwwLjA5NSwyLjQ3MiwwLjA5NWMxNi41NTUsMCwzMC41Mi0xMi45NDMsMzEuNzkyLTI5LjQ2OQ0KCQkJCWMwLjE1Mi0xLjk5NSwwLjUzMS0xNS4xNTUsMC4zNTEtMzAuNzg4YzMuMTQ4LDAuMDgyLDYuMzcyLDAuMTI2LDkuNjc5LDAuMTI2YzkuMTU1LDAsMTcuNjg3LTAuMzE5LDI1LjY0Mi0wLjk1Ng0KCQkJCWM1LjEwNSwxNS4wMTUsNS41MDcsMTkuOTI0LDUuNTMxLDIwLjI5YzAuMDA5LDguNDc1LDMuMzAzLDE2LjQ0OCw5LjI4MiwyMi40NjJjNi4wMSw2LjA0NCwxNC4wMTUsOS4zODYsMjIuNjMsOS40MQ0KCQkJCWMxMS40MDQsMCwyMi4wMTYtNi4xNSwyNy42OTQtMTYuMDUxYzIuMDYxLTMuNTkzLDAuODE4LTguMTc3LTIuNzc0LTEwLjIzN2MtMy41OTQtMi4wNjEtOC4xNzctMC44MTktMTAuMjM3LDIuNzc1DQoJCQkJYy0zLjAxMyw1LjI1Mi04LjYzOSw4LjUxNS0xNC43MzEsOC41MTVjLTQuNTE5LTAuMDEzLTguNzYxLTEuNzg0LTExLjk0NS00Ljk4N2MtMy4xODYtMy4yMDItNC45MzItNy40NTQtNC45MTktMTEuOTcyDQoJCQkJYzAtMC4wNDksMC0wLjA5OC0wLjAwMS0wLjE0N2MtMC4wMzMtMS45NTctMC42NjktNy42ODItNS4yOTItMjEuNzhjMTIuNjA4LTEuODg2LDIzLjQ2OC00Ljc4MiwzMi43MjEtOC43MzgNCgkJCQljMS41NCw0LjgwOSwyLjgwOSw5LjE5NCwzLjc3OCwxMy4wODRjMC44NSwzLjQxLDMuOTA4LDUuNjg5LDcuMjcyLDUuNjg5YzAuNjAxLDAsMS4yMS0wLjA3MywxLjgxOS0wLjIyNQ0KCQkJCWM0LjAxOS0xLjAwMSw2LjQ2Ni01LjA3MSw1LjQ2NC05LjA5MWMtMS4yMjQtNC45MTQtMi44NzItMTAuNTA3LTQuOTA2LTE2LjY3MWM1LjEtMy40MjIsOS41Mi03LjMyNywxMy4yOS0xMS43MzkNCgkJCQljNC4yOTEtNS4wMjEsNy42LTEwLjU0MSwxMC4xNTEtMTYuNDE5YzMuODY0LDIuNzIsOC41MTgsNC4yMzcsMTMuNDQ2LDQuMjM3YzQuMzE1LDAsOC41NDUtMS4xOTksMTIuMjI4LTMuNDY2DQoJCQkJYzUuMzE2LTMuMjcsOS4wNDEtOC40MTYsMTAuNDg4LTE0LjQ4N3MwLjQ0Mi0xMi4zNDUtMi44MjktMTcuNjU4Yy02LjA3Ni05Ljg3Ny0xNC40My0xOC44Ny0yNC44NTMtMjYuNzg1DQoJCQkJYzAtMC4yNTksMC4wMDEtMC41MTcsMC4wMDEtMC43NzZjMC0xMS4zMjMtMS44MDgtMjIuMzg2LTUuMzgzLTMzLjAyM2g3NC4yMjRjNDQuNjExLDAsODAuOTA2LTM2LjI5NSw4MC45MDYtODAuOTA2di0zNC4xNjQNCgkJCQlDNTExLjk5OSwxNDEuMTQ5LDQ3NS43MDUsMTA0Ljg1NSw0MzEuMDkzLDEwNC44NTV6IE0zNjEuNzc3LDM1My45MDFjNS4wMDksNC44MDksOS4yMTQsOS45NDksMTIuNTUzLDE1LjM3NQ0KCQkJCWMxLjE3MSwxLjkwMywxLjUzMSw0LjE0OCwxLjAxMyw2LjMyMmMtMC41MTgsMi4xNzQtMS44NTIsNC4wMTctMy43NTgsNS4xODljLTEuMzE5LDAuODExLTIuODI5LDEuMjQxLTQuMzY3LDEuMjQxDQoJCQkJYy0yLjkzOCwwLTUuNjA3LTEuNDg5LTcuMTQyLTMuOTg0Yy0wLjM1OS0wLjU4NC0wLjc0LTEuMTY3LTEuMTMyLTEuNzVDMzYwLjQ5NSwzNjkuMDYsMzYxLjMzMywzNjEuNTI4LDM2MS43NzcsMzUzLjkwMXoNCgkJCQkgTTExMy45MywxMDkuNTczYy0wLjAyMS0wLjAwNi0wLjA0My0wLjAwOC0wLjA2NC0wLjAxNGMtNS40MDktMS42NDEtMTAuMzY1LTQuNTk4LTE0LjQ3NC04LjcwNw0KCQkJCWMtMTMuNTg2LTEzLjU4Ni0xMy41ODYtMzUuNjkxLDAtNDkuMjc1bDI0LjA1My0yNC4wNTRjMC4xNjEtMC4xNjEsMC4zNzMtMC4yNDEsMC41ODQtMC4yNDFjMC4yMTIsMCwwLjQyMywwLjA4LDAuNTg0LDAuMjQxDQoJCQkJbDI0LjA1NCwyNC4wNTRjMTMuNTg2LDEzLjU4NCwxMy41ODYsMzUuNjg5LDAsNDkuMjc0Yy02LjU4MSw2LjU4MS0xNS4zMywxMC4yMDYtMjQuNjM4LDEwLjIwNmMtMy40MzYsMC02Ljc5NS0wLjQ5OC05Ljk5OS0xLjQ1Mg0KCQkJCUMxMTMuOTk2LDEwOS41OTYsMTEzLjk2NSwxMDkuNTgyLDExMy45MywxMDkuNTczeiBNMTcuMDIyLDM3My44MzVjLTIuNTg5LTEuNjUyLTIuMDU4LTQuMDUtMS43MDktNS4wMDMNCgkJCQljMC4zNjUtMC45OTYsMS41NTctMy4yNiw0LjczMy0yLjg0M2MzOC45Niw1LjIwMSw3Ni44OTItMS4xMTYsMTA0LjU0Ny0xNi45NjRjMC44MzksMjIuMTE4LDQuMzk1LDQzLjgxLDE4LjM5MSw2MC4xODcNCgkJCQljMC4yMzUsMC4yNzUsMC40ODMsMC41NCwwLjcyMywwLjgxMkMxMDIuNTA0LDQwNi43NDEsNTUuNDY1LDM5OC4zOCwxNy4wMjIsMzczLjgzNXogTTIxOC4zMTYsNDY5LjA5Ng0KCQkJCWMtMC42NzQsOC43NTctOC4wNjksMTUuNjE2LTE2LjgzNiwxNS42MTZjLTAuNDQ0LDAtMC44ODktMC4wMTctMS4zMjItMC4wNWMtOS4yOTgtMC43MTYtMTYuMjgtOC44NjItMTUuNTY2LTE4LjE1OQ0KCQkJCWMwLjUxOC02LjczNCwwLjQ2NS0yMC41MDQsMC4xMDktMzMuMjIyYzkuODg0LDIuNjQ4LDIxLjE1Myw0LjQ0MSwzMy45MTQsNS40MTZDMjE4LjgyMSw0NTQuNjYzLDIxOC40MTcsNDY3Ljc2MywyMTguMzE2LDQ2OS4wOTYNCgkJCQl6IE00MzEuMDkzLDI4NS44MzJIMjI3LjYxOGMtNC4xNDMsMC03LjUsMy4zNTctNy41LDcuNWMwLDQuMTQzLDMuMzU3LDcuNSw3LjUsNy41aDExMy4yNzQNCgkJCQljNC4yMjMsMTAuNTQ5LDYuMzYxLDIxLjYzNSw2LjM2MSwzMy4wMjNjMCwyOS42NDYtMi41MTYsNTAuOTYyLTE1LjAzNSw2NS42MTJjLTMuNzYyLDQuNDAyLTguNDMsOC4xOTYtMTQuMDU4LDExLjQwOQ0KCQkJCWMtMC4zMjUsMC4xNS0wLjYzLDAuMzI1LTAuOTI0LDAuNTE1Yy0xMC44MTksNS45NDUtMjUuMDk4LDkuODE1LTQzLjMxNCwxMS43NGMtMC4zNjcsMC4wMDgtMC43MzUsMC4wNDctMS4xMDQsMC4xMTENCgkJCQljLTguODk5LDAuODk1LTE4LjcxLDEuMzQxLTI5LjUxNCwxLjM0MWMtNS43NywwLTExLjI2LTAuMTI2LTE2LjQ3OS0wLjM3OWMtMC4yNzQtMC4wMy0wLjU1Mi0wLjA1LTAuODM0LTAuMDUNCgkJCQljLTAuMDE5LDAtMC4wMzgsMC0wLjA1NywwLjAwMWMtMTguNTkyLTAuOTYtMzMuNzA5LTMuNTY1LTQ1LjcyOC03LjkxN2MtMC41LTAuMjU0LTEuMDMtMC40NTMtMS41ODQtMC41ODkNCgkJCQljLTIuNDI5LTAuOTMyLTQuNzI2LTEuOTM5LTYuODk1LTMuMDIyYy0wLjM0Ni0wLjIxNi0wLjcxMi0wLjQtMS4wOTItMC41NThjLTYuNjIxLTMuNDU0LTEyLjAwOS03LjY0My0xNi4yNDgtMTIuNjAzDQoJCQkJYy0xMi41MjEtMTQuNjQ5LTE1LjAzNS0zNS45NjctMTUuMDM1LTY1LjYxMmMwLTE2Ljc4Miw0LjcxMy0zMy4wNTcsMTMuNjgyLTQ3LjQ2OGMxMS43MTQsNi44ODksMjQuODk3LDExLjU5NCwzOS4wMjgsMTMuNDk1DQoJCQkJYzAuMzQsMC4wNDYsMC42NzcsMC4wNjgsMS4wMTEsMC4wNjhjMy42OTcsMCw2LjkxNi0yLjczNSw3LjQyMy02LjVjMC41NTMtNC4xMDUtMi4zMjctNy44ODEtNi40MzItOC40MzQNCgkJCQljLTQ0LjYzMy02LjAxLTc4LjI5MS00NC41NjEtNzguMjkxLTg5LjY3NnYtNTAuMTk4YzAtNi41NzQsMC42MzYtMTMuMTE2LDEuODg1LTE5LjUzYzIuMTE2LDAuMjcxLDQuMjQ1LDAuNDE5LDYuMzc1LDAuNDE5DQoJCQkJYzEyLjc2NCwwLDI1LjUyNi00Ljg1OCwzNS4yNDQtMTQuNTc0di0wLjAwMWMxNS4wOTYtMTUuMDk2LDE4LjQ1OC0zNy41NDYsMTAuMS01NS45MjRjMTQuNzI3LTcuOTE1LDMxLjIwOC0xMi4wNzIsNDguMDc5LTEyLjA3Mg0KCQkJCWg0Mi40MTNjMzIuMDc2LDAsNjEuNzg5LDE1Ljk3Myw3OS40ODEsNDIuNzI4YzEzLjk0LDIxLjA3OSwzNy4zNSwzMy42NjQsNjIuNjIxLDMzLjY2NGgyOS4xMjYNCgkJCQljMzYuMzQyLDAsNjUuOTA3LDI5LjU2Niw2NS45MDcsNjUuOTA4djM0LjE2N0g0OTdDNDk3LDI1Ni4yNjcsNDY3LjQzNSwyODUuODMyLDQzMS4wOTMsMjg1LjgzMnoiLz4NCgkJCTxwYXRoIGQ9Ik0yMTYuODQxLDM1Ny44MzVjLTIuNTc3LDMuMjQyLTIuMDM3LDcuOTYxLDEuMjA2LDEwLjUzOGM1LjYwMyw0LjQ1MiwxNy42MSw5LjY1MiwzMy4wMTMsOS42NTINCgkJCQljMTAuNjMxLDAsMjAuNTk4LTIuNTA4LDI5LjYyNC03LjQ1NWMxMS4yOTktNi4xOTEsMTUuNDUyLTIwLjQyMSw5LjI2Mi0zMS43MjNjLTMuMDAxLTUuNDc0LTcuOTUzLTkuNDUxLTEzLjk0My0xMS4yDQoJCQkJYy01Ljk5LTEuNzUtMTIuMzA2LTEuMDYxLTE3Ljc3NywxLjk0Yy02LjE5NSwzLjM5Mi0xMy4zOTksMC42NzMtMTMuNDY3LDAuNjQ1Yy0zLjgyOS0xLjU5MS04LjIxNSwwLjIyNi05LjgwMiw0LjA1Mg0KCQkJCWMtMS41ODgsMy44MjYsMC4yMjcsOC4yMTUsNC4wNTIsOS44MDJjNS4wNjYsMi4xMDQsMTYuMzMyLDQuMTg4LDI2LjQyNi0xLjM0NWMxLjk1OS0xLjA3Miw0LjIyMi0xLjMxOSw2LjM2NS0wLjY5NA0KCQkJCWMyLjE0NiwwLjYyNywzLjkyLDIuMDUyLDQuOTk0LDQuMDExYzIuMjE3LDQuMDQ3LDAuNzI5LDkuMTQzLTMuMzE2LDExLjM2Yy02Ljc5MiwzLjcyMi0xNC4zMzIsNS42MDktMjIuNDE1LDUuNjA5DQoJCQkJYy0xMS40NywwLTIwLjQ0NS0zLjgyNS0yMy42ODEtNi4zOTZDMjI0LjEzNiwzNTQuMDU0LDIxOS40MiwzNTQuNTkyLDIxNi44NDEsMzU3LjgzNXoiLz4NCgkJCTxjaXJjbGUgY3g9IjQ0MC41MDkiIGN5PSIxNTAuNjk4IiByPSI3LjUxNCIvPg0KCQkJPHBhdGggZD0iTTQyMy42NDIsMjI4LjAwNmgtMTQuMTUyYy00LjE0MywwLTcuNSwzLjM1Ny03LjUsNy41YzAsNC4xNDMsMy4zNTcsNy41LDcuNSw3LjVoMTQuMTUzYzQuMTQzLDAsNy41LTMuMzU3LDcuNS03LjUNCgkJCQlDNDMxLjE0MSwyMzEuMzYzLDQyNy43ODUsMjI4LjAwNiw0MjMuNjQyLDIyOC4wMDZ6Ii8+DQoJCQk8cGF0aCBkPSJNMjgyLjEyNSw4OC43MDhjLTQuMTQzLDAtNy41LDMuMzU3LTcuNSw3LjV2MS4yOThjMCw0LjE0MywzLjM1Nyw3LjUsNy41LDcuNWM0LjE0MywwLDcuNS0zLjM1Nyw3LjUtNy41di0xLjI5OA0KCQkJCUMyODkuNjI1LDkyLjA2NSwyODYuMjY3LDg4LjcwOCwyODIuMTI1LDg4LjcwOHoiLz4NCgkJCTxjaXJjbGUgY3g9IjM5Mi42MjIiIGN5PSIxNTAuNjk4IiByPSI3LjUxNCIvPg0KCQk8L2c+DQoJPC9nPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPGc+DQo8L2c+DQo8Zz4NCjwvZz4NCjxnPg0KPC9nPg0KPC9zdmc+DQo=",
        "title" => "Ups! No encuentro la conexión",
        "text" => "Ha debido de ocurrir algo muy importante porque no puedo comunicarme con mi modelo de datos. <br><br> Espera un poco a ver si me arreglo solo pero si no es así por favor contacta con el departamento técnico."
    ]));
    exit;
}

if (isset($_GET["compiletwig"])){
    $path = realpath(__DIR__."/../../../../template/estandar/modulos/")."/";
    $files = @scandir($path);
    $files = array_values(array_filter($files,function($rec){ return $rec != "." && $rec != ".."; }));
    $ignored = [];
    $generated = [];
    if ($files){
        foreach($files as $file){
            if (file_exists($path.$file."/index.tpl") && file_exists($path.$file."/index-twig.tpl") && (file_exists($path.$file."/builder.json") || strpos($file,"custom-") === 0)){
                if (file_exists($path.$file."/builder.json")){
                    $dataJson = json_decode(file_get_contents($path.$file."/builder.json"),true);
                    if (!isset($dataJson["notParseComponents"]) || $dataJson["notParseComponents"] != 2) {
                        $ignored[] = ["file" => $file,"lexical" => $dataJson["notParseComponents"]];
                        continue;
                    }
                }
                $data = file_get_contents($path.$file."/index.tpl");
                $generated[] = ["file" => $file,"lexical" => @$dataJson["notParseComponents"]];

                $content = compileTWIG($data,$path.$file);
                file_put_contents($path.$file."/index-twig.tpl", $content);

            }else{
                $ignored[] = ["file" => $file,"lexical" => @$dataJson["notParseComponents"]];
            }
        }
    }
    header("Content-Type:application/json");
    die(json_encode(["success" => true,"ignored" => $ignored,"generated" => $generated]));
    
}
// AMP

$userAgent = strtolower(@$_SERVER["HTTP_USER_AGENT"] ?: "");

$estamosEnAMP = strpos($_SERVER["REQUEST_URI"], "/amp/") !== false;

$configBBDD = @mysql_query_fetch_all_assoc("select * from aux_plg_config where plugin='builder_saas'")[0];
if (@$configBBDD) $configBBDD = json_decode($configBBDD["config"],true);

$puedoAMP = @array_filter($configBBDD,function($rec){ return @$rec["campo"] == "amp" && @$rec["valor"]; });
if (@$puedoAMP){
    
    global $TABLE_PREFIX;
    global $modulosParaAMP;
    global $redirectToAMP;
    
    $redirectToAMP = false;
    $modulosParaAMP = [];
    
    if (isset($registro) && isset($tabla)){
        $apartadoAux = @CocoDB::get(str_replace($TABLE_PREFIX,"",$tabla),"num=".intval(@$registro),"num desc",1)[0];
        if (@json_decode($apartadoAux["builder"], true)){
            $modulosParaAMP = array_map(function($rec){
                return $rec["modulo"];
            },json_decode($apartadoAux["builder"],true));
        }else{
            $modulosParaAMP = ["custom-".str_replace($TABLE_PREFIX,"",$tabla)];
        }
        
        if (file_exists(__DIR__."/ampModules.json")){
            $modules = json_decode(file_get_contents(__DIR__."/ampModules.json"),true);
            if (@$modules){
                $redirectToAMP = true;
                foreach($modulosParaAMP as $moduloParaAMP){
                    if (!in_array($moduloParaAMP,$modules)) {
                        $redirectToAMP = false;
                    }
                }
            }
        }
    }
    
    if (!preg_match("/(android|blackberry|googlebot\-mobile|iemobile|iphone|ipod|\#opera\ mobile|palmos|webos)/", $userAgent)) {
        if ($estamosEnAMP) {
            header("Location: ".str_replace("/amp/","/",$_SERVER["REQUEST_URI"]), true, 301);
            die();
        }
    }else if (@$estamosEnAMP){
        $_REQUEST["viewAMP"] = true;
    }else{
        
        if (@$modulosParaAMP && $redirectToAMP){
            header("Location: /amp".$_SERVER["REQUEST_URI"]);
            die();
        }
    }
}
 
// HOOKS 
if (!@$tabla && $key == "builder_saas"){
    
    if (file_exists(__DIR__."/layout.json")){
            
        try{
            $request = [];
            //if (!@session_id()) session_start();
            if (session_status() == PHP_SESSION_NONE) { session_start(); }
            
            // moduloBuilder es un apaño para que permita los hooks si se llama desde el previsualizador de módulos. Arreglar algún día
            if (@$_SERVER["HTTP_X_HOOKS_TOKEN"] != sha1(session_id().$_SERVER["HTTP_HOST"]) && !strpos(@$_SERVER["HTTP_REFERER"] ?: '',"moduloBuilder")) {
                if (in_array($enlace,array_map(function($rec){ return $rec["endPoint"]; },@$layout["hooks"] ?: []))){
                    throw new Exception('Operación no permitida',403);    
                }else{
                    return;
                }
            }

            require_once __DIR__."/replace_code.php";
            header('Content-type: application/json; charset=utf-8'); 

            try{
                $request = json_decode(file_get_contents('php://input'),true);
            }catch(Exception $e){
                throw new Exception($e,400);
            }
            
            $dataVars = @$request;            
            
            // Añadido porque daba problemas en servidores como el de Smile por llamarse a si mismo.
            if (isset($dataVars["remote"]) || (isset($dataVars[0]) && isset($dataVars[0]["remote"])) ){ 
                $remoteHost = @$dataVars["remote"] ?: @$dataVars[0]["remote"];
                if($remoteHost == $_SERVER['HTTP_HOST']) {
                    if(isset($dataVars["remote"])) unset($dataVars["remote"]);
                    if(isset($dataVars["remote"][0]) && isset($dataVars[0]["remote"])) unset($dataVars[0]["remote"]);
                }
            }

            if (isset($dataVars["remote"]) || (isset($dataVars[0]) && isset($dataVars[0]["remote"])) ){ 
            
                $remoteHost = @$dataVars["remote"] ?: @$dataVars[0]["remote"];
                // OBGLIGATORIO EL USO DE REDIS
                $hash = CocoDB::cacheGenerateHash("REMOTE_LAYOUT");

                if (!class_exists("CocoDB") ) return ["error" => "Falta librería CocoDB"];
                try{
                    CocoDB::initCache();    
                    if (!CocoDB::$redis) throw new Exception("Error Processing Request", 1);
                }catch(Exception $e){
                    return ["error" => "El servidor debe disponer del sistema de caché redis para usar módulos remotos"];
                }

                // MODULOS REMOTOS

                if (defined("REMOTE_CACHE") && REMOTE_CACHE == true) $result = CocoDB::cacheGet($hash);
                
                if (!@$result){
                    $result = sendRemoteBuilder($remoteHost,"getLayoutData");
                    CocoDB::cacheSet($hash,$result);             
                }

                $layout = json_decode($result,true);
                if (!@$layout["data"]) {
                    $layout = [];
                }else{
                    $layout = $layout["data"]; 
                }
                
            }else{
                
                if (class_exists("CocoDB") && isset(CocoDB::$force_redis) && CocoDB::$force_redis){
                    $hash = CocoDB::cacheGenerateHash("LAYOUT");
                    $data = CocoDB::cacheGet($hash);
                    if (@$data) $layout  = json_decode($data,true);
                }
                if (!@$layout) {
                    $layout = json_decode(file_get_contents(__DIR__."/layout.json"),true);
                    if (class_exists("CocoDB") && isset(CocoDB::$force_redis) && CocoDB::$force_redis){
                        CocoDB::cacheSet($hash,json_encode($layout));
                    }
                }
            }

            /* HOOKS DE MODULOS */
            // AUN NO SE HA HECHO PARA REMOTOS... HACE FALTA AÑADIRLO EN EL getLayoutData de ws_respond PERO SOLO CUANDO SE LE PASE UNA VARIABLE
            // POR QUE SI NO APARECERÍAN EN LOS HOOKS GENERALES Y NO NOS INTERESA
            $hookNameSep = array_values(array_filter(explode("/",$enlace ?? '')));
            $hookName = $hookNameSep[count($hookNameSep)-1] ?? '';
            addModulesHooksToLayout($hookName,$layout["hooks"],$dataVars);

            // HOOKS_FILES
            if (!@$layout["hooks"]) $layout["hooks"] = [];
            addFilesHooksToLayout($layout["hooks"]); // Añadimos también los hooks de archivos para que se ejecuten aunque no se hayan añadido al layout ( por ejemplo en el caso de módulos nuevos añadidos sin editar el layout )
            
            if (@$layout["hooks"]){
                
                
                
                foreach($layout["hooks"] as $hook){
                    if ($enlace === $hook["endPoint"]){
                        
                        $php = str_replace("|*","",$hook["code"]);
                        $php = str_replace("*|","",$php);
                        $php = base64_decode($php);
                        // Dani 29/10/2024: uft8_encode deprecated.
                        $php = mb_check_encoding($php,"UTF-8") ? $php : mb_convert_encoding($php, 'UTF-8', 'ISO-8859-1');
                        $php = str_replace("<?php","",$php);
                        $php = str_replace("<?","",$php);
                        $php = str_replace("?>","",$php);
                        $php = str_replace("shell_exec(","echo(",$php);
                        $php = str_replace("exec(","echo(",$php);
                        $php = str_replace("unlink(","echo(",$php);
                        $php = str_replace("curl_echo(","curl_exec(",$php);
                        $data = [];
                        if (@$hook["entryParams"]){
                            foreach($hook["entryParams"] as $entry){
                                if (!@$entry["variable"]) continue;
                                $data[$entry["variable"]] = addcslashes(@$request[$entry["variable"]]?:'',"'\\");
                            }
                        }
                         
                        if (class_exists("CmsApi")) CmsApi::setBacktracePoint($hook["endPoint"]);
                        
                        $functionVars = @$data ? "$".join(",$",array_keys($data)) : "";
                        $functionValues = @$data ? "'".join("','",array_values($data))."'" : "";
                        $php = "
                        function returnData(".$functionVars."){ ".$php."}; 
                        echo @json_encode(returnData(".$functionValues."));
                        ";
                        
                        
                        $output = "";
                        extract($data);
                        ob_start();
                        eval($php);
                        $output.=ob_get_clean();

                        addPlugins("post_hooks", $output);
                        
                        echo $output;    

                        die("");              
                    }
                }
            }
        }catch(Exception $e){
            if (ini_get('display_errors') === '1') throw $e;
            if ($e->getCode() == 403 || $e->getCode() == 400) die(json_encode(["error" => "Error de peticion ".$e->getMessage()]));
        }
    }
}else if (@$tabla  && $key == "builder_saas"){
    try{
        if (class_exists("CocoDB") && isset(CocoDB::$force_redis) && CocoDB::$force_redis){
            $hash = CocoDB::cacheGenerateHash("LAYOUT");
            $data = CocoDB::cacheGet($hash);
            if (@$data) $layout  = json_decode($data,true);
        }
        if (!@$layout) {
            $layout = json_decode(file_get_contents(__DIR__."/layout.json"),true);
            if (class_exists("CocoDB") && isset(CocoDB::$force_redis) && CocoDB::$force_redis){
                CocoDB::cacheSet($hash,json_encode($layout));
            }
        }
        
        if (@$layout["hooks"]){
            if (!@$var["num"] || !@$var["tabla"]) return;
            $keyMiddle = $var["tabla"]."-".$var["num"];

            // HOOKS_FILES -> 
            if (!@$layout["hooks"]) $layout["hooks"] = [];
            addFilesHooksToLayout($layout["hooks"]); // Añadimos también los hooks de archivos para que se ejecuten aunque no se hayan añadido al layout ( por ejemplo en el caso de módulos nuevos añadidos sin editar el layout )
            
            foreach($layout["hooks"] as $hook){
                if (!@$hook["middleWare"]) continue;
                foreach($hook["middleWare"] as $middleWare){
                    if ($middleWare == $keyMiddle || $middleWare == "allurls"){
                        
                        $php = str_replace("|*","",$hook["code"]);
                        $php = str_replace("*|","",$php);
                        $php = base64_decode($php);
                        // Dani 29/10/2024: uft8_encode deprecated.
                        $php = mb_check_encoding($php,"UTF-8") ? $php : mb_convert_encoding($php, 'UTF-8', 'ISO-8859-1');
                        $php = str_replace("<?php","",$php);
                        $php = str_replace("<?","",$php);
                        $php = str_replace("?>","",$php);
                        $php = str_replace("shell_exec(","echo(",$php);
                        $php = str_replace("exec(","echo(",$php);
                        $php = str_replace("unlink(","echo(",$php);
                        $php = str_replace("curl_echo(","curl_exec(",$php);
                        $output = "";
                        if (class_exists("CmsApi")) CmsApi::setBacktracePoint($hook["endPoint"]);
                        ob_start();
                        eval($php);
                        $output.=ob_get_clean();

                        addPlugins("post_hooks", $output);
                        
                        echo $output;
                    }else{
                        continue;
                    }
                }
            }
            
        }
    }catch(Exception $e){
        if (ini_get('display_errors') === '1') throw $e;
        if ($e->getCode() == 403 || $e->getCode() == 400) die(json_encode(["error" => "Error en la ejecución del hook"]));
    }
}
?>
