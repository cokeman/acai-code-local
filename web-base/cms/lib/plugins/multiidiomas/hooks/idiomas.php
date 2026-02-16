<?php
global $TABLE_PREFIX, $menu, $action, $schema, $CURRENT_USER;
if (@$_REQUEST["action"]) {
    switch ($_REQUEST["action"]) {
        case 'listIdiomasWebsite':
            try {
                $idiomas = Api::request('idiomas', ['action' => 'listIdiomasWebsite']);
                die(json_encode($idiomas));
            } catch (Exception $e) {
                $error = json_decode($e->getMessage(), true);
                die(json_encode([
                    'error' => $error['error'],
                    'code' => @$error['code']
                ]));
            }
            break;
        case 'updatePluginConfig':
            try {
                $idiomas = Api::request('idiomas', ['action' => 'listIdiomasWebsite']);
                if($idiomas["success"]){
                    $plugin_config = [];
                    foreach($idiomas["data"] as $idioma){
                        if(@$idioma["locked"] === false){
                            $plugin_config[] = $idioma["prefix"];
                        }
                    }
                    $plugin_config_idiomas = implode(",",$plugin_config);
                    $plugin_conf_new = getSchemaPlugins()["multiidiomas"];
                    $plugin_conf_new["config"]["idiomas"] = $plugin_config_idiomas;
                    $result = PluginsAPI::saveConfig("multiidiomas",$plugin_conf_new);
                    var_dump($result);

                    $response = PluginsAPI::syncPlugin("multiidiomas",true);
                    die(json_encode($response));
                }
            } catch (Exception $e) {
                $error = json_decode($e->getMessage(), true);
                die(json_encode([
                    'error' => $error['error'],
                    'code' => @$error['code']
                ]));
            }
            break;
        case 'verifyPluginPurchased':
            try {
                $idiomas = Api::request('idiomas', ['action' => 'verifyPluginPurchased']);
                die(json_encode($idiomas));
            } catch (Exception $e) {
                $error = json_decode($e->getMessage(), true);
                die(json_encode([
                    'error' => $error['error'],
                    'code' => @$error['code']
                ]));
            }
            break;
        case 'payLink':
            try {
                $result = API::request(@$CURRENT_USER["isSuperAdmin"] ? "pay_test" : "pay", ["action" => "PayLang", "data" => @$_REQUEST["idiomas"], "backTo" => "https://" . $_SERVER["HTTP_HOST"] . "/admin.php?menu=multiidiomas"], "POST");
            } catch (Exception $e) {
                $error = json_decode($e->getMessage(), true);
                die(json_encode([
                    'error' => $error['error'],
                    'code' => @$error['code']
                ]));
            }
            die(json_encode($result));
            break;

        default:
            # code...
            break;
    }
}

