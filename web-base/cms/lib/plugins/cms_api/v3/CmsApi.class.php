<?
$classes = array_slice(scandir(__DIR__ . '/classes/'), 2);

foreach ($classes as $class) :
    if (!is_dir(__DIR__ . "/classes/$class")) {
        require_once __DIR__ . "/classes/$class";
    }
endforeach;
if (file_exists(__DIR__ . "/custom_classes/")) {
    $classes = array_slice(scandir(__DIR__ . '/custom_classes/'), 2);
    foreach ($classes as $class) :
        if (!is_dir(__DIR__ . "/custom_classes/$class")) {
            require_once __DIR__ . "/custom_classes/$class";
        }
    endforeach;
}
API::setLanguage();
API::generateJsonConfig([__DIR__ . "/schemaBase.json", __DIR__ . "/schemaCustom.json"]);


class CmsApi
{
    static function request($endPoint, $request = [], $method = "GET")
    {
        $urlParams = array_filter(explode("/", $endPoint));
        $jsonEndPoint = API::$jsonConfig["endPoints"];
        $jsonSelectedMethod = null;
        $variablesEndPoint = [];
        $tableName = null;
        $where = [];
        $notEditableFields = [];
        $controller = null;
        $pipes = [];

        foreach ($urlParams as $key => $value) {
            if (!isset($jsonEndPoint[$value])) {
                if (isset($jsonEndPoint[":"])) {
                    $jsonEndPoint = $jsonEndPoint[":"];
                    $variablesEndPoint[$jsonEndPoint["variable"]] = $value;
                    $where[] = [
                        "column" => $jsonEndPoint["variable"],
                        "operator" => "=",
                        "value" => $value
                    ];
                    continue;
                } else if (isset($controller)) {
                    $pipes[] = $value;
                    continue;
                }
                return API::error(new ApiError("No existe el método elegido"));
            }
            $jsonEndPoint = $jsonEndPoint[$value];
            if (isset($jsonEndPoint["controller"])) {
                $controller = $jsonEndPoint["controller"];
                $pipes = [];
            }
            $jsonEndPoint["key"] = $value;
            if (!$tableName) $tableName = $value;
            if (isset($jsonEndPoint["hiddenFields"])) CmsCRUD::setHiddenFields($jsonEndPoint["hiddenFields"]);
            if (isset($jsonEndPoint["notEditableFields"])) $notEditableFields = $jsonEndPoint["notEditableFields"];
        }
        if (!isset($jsonEndPoint["methods"])) return API::error(new ApiError("EndPoint no válido"));



        if ($controller) {
            if (class_exists($controller)) {
                $resultPipes = null;
                if (empty($pipes)) $pipes = [$urlParams[0]];
                foreach ($pipes as $pipe) {

                    if (!method_exists($controller, $pipe) && property_exists($controller, "defaultMethod")) $pipe = get_class_vars($controller)["defaultMethod"];

                    if (!method_exists($controller, $pipe)) return API::error(new ApiError("El método " . $pipe . " personalizado no existe"));
                    try {
                        $resultPipes = call_user_func([$controller, $pipe], $resultPipes ? $resultPipes : $request);
                    } catch (Exception $e) {
                        return API::error($e);
                    }
                }
                return $resultPipes;
            } else {
                return API::error(new ApiError("La clase " . $controller . " no existe"));
            }
        }

        if (!isset($jsonEndPoint["methods"][$method])) return API::error(new ApiError("Método no válido -> " . json_encode($method)));

        $jsonSelectedMethod = $jsonEndPoint["methods"][$method];
        $notEditableFields = array_flip($notEditableFields);
        if (isset($jsonSelectedMethod["body"]["data"])) {
            $requiredFields = array_flip(array_keys(array_filter($jsonSelectedMethod["body"]["data"], function ($rec) {
                return isset($rec["required"]) && $rec["required"];
            })));
            $fields = array_diff_key($requiredFields, $request);
            if (!empty($fields)) return API::error(new ApiError("Los campos ( " . join(", ", array_keys($fields)) . " ) son requeridos"));
            foreach ($jsonSelectedMethod["body"]["data"] as $defaultKeyField => $defaultValueField) {
                if (!isset($request[$defaultKeyField]) && isset($defaultValueField["default"]) && $method !== 'PATCH') $request[$defaultKeyField] = $defaultValueField["default"];
            }
        }

        switch ($method) {
            case "GET":
                try {
                    $data = CmsCRUD::listRecords($where, $tableName);
                    return $data;
                } catch (Exception $e) {
                    return API::error($e);
                }
                break;
            case "POST":
                try {
                    switch ($jsonEndPoint["key"]) {
                        case "search":
                            $data = CmsCRUD::listRecords(@$request["where"], $tableName, true, @$request["order"], @$request["limit"], (@$request["options"]?:[]));
                            return $data;
                            break;
                        default:
                            $data = [];
                            if (!@$request) return API::error(new ApiError("Se debe enviar datos"));
                            $invalidFields = array_intersect_key($request, $notEditableFields);
                            $request = array_diff_key($request, $notEditableFields);
                            if (!@$request) return API::error(new ApiError("Se debe enviar datos"));

                            if (!@$request["options"]) $request["options"] = [];

                            $request["options"]["return_last_id"] = 1;

                            $data = CocoDB::insertRecords($tableName, $request, [], $request["options"]);

                            if ($data) {
                                $data = CmsCRUD::listRecords([["column" => "num", "operator" => "=", "value" => $data]], $tableName);
                                if (!empty($invalidFields)) {
                                    API::addWarning("Los campos ( " . join(", ", array_keys($invalidFields)) . " ) no se han podido insertar");
                                }
                            }
                            return $data;
                    }
                } catch (Exception $e) {
                    return API::error($e);
                }
                break;
            case "PATCH":
                try {
                    $data = [];
                    if (!@$request) return API::error(new ApiError("Se debe enviar datos"));
                    if (empty($variablesEndPoint)) return API::error(new ApiError("Debes asignar un where"));
                    $invalidFields = array_intersect_key($request, $notEditableFields);
                    $request = array_diff_key($request, $notEditableFields);
                    $data = CocoDB::updateRecords($tableName, $request, $variablesEndPoint, [], @$request["options"] ?: []);
                    if ($data) {
                        $data = CmsCRUD::listRecords($where, $tableName);
                        if (!empty($invalidFields)) {
                            API::addWarning("Los campos ( " . join(", ", array_keys($invalidFields)) . " ) no se han podido actualizar");
                        }
                    }
                } catch (ApiError $e) {
                    return API::error($e);
                }

                return $data;
            case "DELETE":
                $data = [];
                if (empty($variablesEndPoint)) return API::error(new ApiError("Debes asignar un registro"));
                try {
                    $data = CocoDB::deleteRecords($tableName, $variablesEndPoint);
                } catch (Exception $e) {
                    return API::error($e);
                }

                return $data;
                break;
        }
        return [$method, $jsonEndPoint, $variablesEndPoint, $tableName];
    }
    static function insert($table, $records, $functions = [], $options = [])
    {
        return CocoDB::insertRecords($table, $records, $functions, $options);
    }
    static function update($table, $records, $where, $functions = [], $options = [])
    {
        return CocoDB::updateRecords($table, $records, $where, $functions, $options);
    }
    static function delete($table, $where, $options = [])
    {
        return CocoDB::deleteRecords($table, $where, $options);
    }
    static function get($table, $where = null, $order = null, $limit = null, $options = [])
    {
        return CocoDB::get($table, $where, $order, $limit, $options);
    }
    static function setBacktracePoint($string = null)
    {
        if ($string) CocoDB::setBacktracePoint($string);
    }
    static function fullCache($expireTime = 60)
    {
        CocoDB::fullCache($expireTime);
    }
    static function generateBaseConfig()
    {
        $lastSaved = 0;

        foreach (scandir(__DIR__) as $file) {
            if ($file == ".." || $file == ".") continue;
            $time = filemtime(__DIR__ . "/" . $file);
            if ($time > $lastSaved) $lastSaved = $time;
        }
        foreach (scandir(__DIR__ . "/classes") as $file) {
            if ($file == ".." || $file == ".") continue;
            $time = filemtime(__DIR__ . "/classes/" . $file);
            if ($time > $lastSaved) $lastSaved = $time;
        }
        foreach (scandir(__DIR__ . "/custom_classes/") as $file) {
            if ($file == ".." || $file == ".") continue;
            $time = filemtime(__DIR__ . "/custom_classes/" . $file);
            if ($time > $lastSaved) $lastSaved = $time;
        }
        if ($lastSaved > filemtime(__DIR__ . "/schemaBase.json")) {
            ApiJsonBuilder::generateBaseConfig();
        }
    }
    static function showDebug($formated = false, $index = -1)
    {
        return CocoDB::showDebug($formated, $index);
    }
    /**
    Debug: Muestra la consulta en un <pre> junto al tiempo que ha tardado, errores si hubiera y el resultado que devuelve
    BONUS: Ahora que está todo centralizado -> Habilitar un flag en CocoDB o CmsApi para guardar un log de *todas*
        las consultas que se hagan y el tiempo que tardan etc
     */
    /*CmsApi::get('reservas','visible=1','dragSortOrder DESC',["limit" => 10,"offset" => 20], ["debug" => true, "with" => ["translates", "uploads", "relations" => "*", "relations" => ['islas'], "relationsDepth" => 1]]);
    
    CmsApi::get('reservas R, precios P', 'P.reserva=R.num AND NOT EXISTS(SELECT * FROM reservas WHERE num > R.num)', 'R.num DESC', 10)*/
}
