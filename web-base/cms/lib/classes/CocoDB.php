<?php
class CocoDB
{
    public static $tableCache = [];
    public static $uploadColumns = [];
    public static $queryCaches = [];
    public static $getCaches = [];
    public static $storeDebugData = false;
    public static $debugData = [];
    public static $trackData = [];
    public static $redis = null;
    public static $redis_expireTime = 0;
    public static $force_load_cache = false;
    public static $force_redis = false;
    public static $force_redis_module = false;
    public static $force_redis_html = false;
    public static $force_json_cache_uploads = false;
    public static $pluginsConfig = [];
    public static $backTracePoint = null;
    public static $defaultRelationsDepth = 2;
    public static $noCacheURIS = []; // Array de expresiones regulares que contengan las URLS
    public static $noCacheTABLES = []; // Array de Tablas
    public static $allowedTranslateFields = null; // Campos permitidos para la traducción
    /**
     * Inserta registros en una tabla
     * @destacar
     * @category DB
     * @param table: Tabla de inserción
     * @param records: Lista de registros a insertar
     * @param functions: Array asociativo con funciones a aplicar a cada key
     * @param options: Lista de opciones posibles que pasarle al método
     * @return Número de registros insertados
     */
    static function insertRecords($table, $records, $functions = [], $options = [])
    {

        if (!isset($records[0])) {
            $records = [$records];
        }
        if (@$options["preSaveTempId"]) {
            $preSave = true;
        }

        list($ignoreFields, $ignoreSchema, $prefix) = self::parse_options($options);

        if (!$ignoreSchema) {
            $schema = @loadSchema($table);
            if (!@$schema) self::error(["error" => 'Error. Tabla no encontrada']);
        }


        $result = 0;
        $lastSaved = 0;

        foreach ($records as $record) :
            $sqlBase = self::prepareBaseSQL($prefix, $table, @$schema, false, $ignoreFields, $record);
            $record = self::unsetKeys($record, $ignoreFields);
            if (!in_array("num", @$ignoreFields) && isset($record["num"])) {
                $sqlBase = str_replace("num=NULL", "num=" . intval(@$record["num"]), $sqlBase);
                $record = self::unsetKeys($record, ["num"]);
            }
            self::insertOrUpdate($record, $sqlBase, $result, null, $prefix . $table, $functions, $ignoreSchema, @$schema, $options);
            $lastSaved = mysql_insert_id();
            foreach (self::$uploadColumns as $keyColumn => $uploadColumn) {
                foreach ($uploadColumn as $keyCol => $urlPath) {
                    if (!@$urlPath) continue;
                    self::insertRecords("uploads", [
                        "urlPath" => $urlPath,
                        "filePath" => realpath(__DIR__ . "/../../../" . $urlPath),
                        "fieldName" => $keyColumn,
                        "recordNum" => $lastSaved,
                        "tableName" => $table,
                        "createdTime" => date("Y-m-d H:i:s"),
                        "order" => time() + $keyCol,
                        "width" => 640,
                        "height" => 480
                    ], [], ["ignoreSchema" => true]);
                }
            }

        endforeach;


        if (@$preSave) {
            $query = "UPDATE {$prefix}uploads "
                . "   SET recordNum = LAST_INSERT_ID(), preSaveTempId = '' "
                . " WHERE tableName     = '" . mysql_real_escape_string($table) . "' AND "
                . "       preSaveTempId = '" . mysql_real_escape_string($options["preSaveTempId"]) . "'";

            mysql_query($query) or self::error(["error" => "MySQL Error: " . htmlspecialchars(mysql_error()) . "\n"]);

            $query = "UPDATE {$prefix}traducciones "
                . "   SET recordNum = LAST_INSERT_ID(), preSaveTempId = '' "
                . " WHERE tableName     = '" . mysql_real_escape_string($table) . "' AND "
                . "       preSaveTempId = '" . mysql_real_escape_string($options["preSaveTempId"]) . "'";
            mysql_query($query) or self::error(["error" => "MySQL Error: " . htmlspecialchars(mysql_error()) . "\n"]);
        }

        if (@$options['generate_category_metadata']) {
            self::updateCategoryMetadata($table);
        }

        if (@$options['return_last_id']) {
            return $lastSaved;
        }

        return $result;
    }

    /**
     * Elimina registros de una tabla
     * @destacar
     * @category DB
     * @param table: Tabla de la que vamos a eliminar registros
     * @param where: Array asociativo que recoge campo => valor, operador, or
     * @return boolean que indica si se pudo ejecutar la consulta
     */
    static function deleteRecords($table, $where, $options = [])
    {
        list($_, $_, $prefix) = self::parse_options($options);

        $where = self::parse_where($where, $table, $prefix);

        if (!@$where) return false;

        $sql = "DELETE FROM $prefix$table WHERE $where";

        if (@$options['dieBeforeQuery'] && $table == "uploads") {
            self::error(["info" => $sql]);
        }

        $q = mysql_query($sql);
        if (!$q) return false;
        return true;
    }

    /**
     * Actualiza registros en una tabla
     * @destacar
     * @category DB
     * @param table: Tabla de inserción
     * @param records: Lista de registros a insertar
     * @param functions: Array asociativo con funciones a aplicar a cada key
     * @param options: Lista de opciones posibles que pasarle al método
     * @return Número de registros insertados
     */
    static function updateRecords($table, $records, $where, $functions = [], $options = [])
    {
        global $TABLE_PREFIX;

        if (!isset($records[0])) {
            $records = [$records];
        }

        list($ignoreFields, $ignoreSchema, $prefix) = self::parse_options($options);

        if (!$ignoreSchema) {
            $schema = @loadSchema($table);
            if (!@$schema) self::error(["error" => 'Error. Tabla no encontrada']);
        }

        $result = 0;
        foreach ($records as $record) :
            $sqlBase = self::prepareBaseSQL($prefix, $table, @$schema, true, $ignoreFields, $record);
            $lastNum = @$record['num'];
            $record = self::unsetKeys($record, $ignoreFields);
            // Está comentado, pero no se si hace falta, si se descomenta se rompe el guardar del Builder (Plugin)
            // if (@$schema['menuType'] == 'category' && !isset($record['parentNum'])) {
            //     continue;
            // }

            self::insertOrUpdate($record, $sqlBase, $result, $where, $prefix . $table, $functions, $ignoreSchema, @$schema, $options);

            if (@$lastNum && @$options['delete_upload_nums']) {
                // El Prefix se añade "por la cara" porque el show_columns peta sin el
                self::deleteRecords("uploads", [
                    ["column" => "num", "value" => $options['delete_upload_nums'], "operator" => "IN"]
                ], ["prefix" => $TABLE_PREFIX]);
            }

            foreach (self::$uploadColumns as $keyColumn => $uploadColumn) {
                // El Prefix se añade "por la cara" por lo mismo que el delete_upload_nums aunque este no está probado
                if (@$lastNum && @$options['delete_old_uploads']) {
                    self::deleteRecords("uploads", [
                        "fieldName" => $keyColumn,
                        "recordNum" => $lastNum,
                        "tableName" => $table,
                    ], ["prefix" => $TABLE_PREFIX]);
                }

                foreach ($uploadColumn as $keyCol => $urlPath) {

                    if (@$lastNum && @$options['insert_new_uploads']) {
                        self::insertRecords("uploads", [
                            "urlPath" => $urlPath,
                            "filePath" => realpath(__DIR__ . "/../../../" . $urlPath),
                            "fieldName" => $keyColumn,
                            "recordNum" => $lastNum,
                            "tableName" => $table,
                            "createdTime" => date("Y-m-d H:i:s"),
                            "order" => time() + $keyCol,
                            "width" => 640,
                            "height" => 480
                        ], [], ["ignoreSchema" => true]);
                    }
                }
            }
        endforeach;

        if (@$options['generate_category_metadata']) {
            self::updateCategoryMetadata($table);
        }

        return $result;
    }


    /**
     * Hace el insert o update de un único registro
     * @param record: Registro con el que vamos a operar
     * @param sqlBase: SQL Base
     * @param result: Número de registros con los que hemos operado. In-out
     * @param where: Where de la operación (solo si es un UPDATE)
     * @param table: Tabla de la operación
     * @param functions: Lista de funciones con las que podemos parsear un determinado valor
     * @param ignoreSchema: Boolean que indica si vamos a ignorar el Schema o no
     * @param schema: Schema de la tabla
     */
    public static function insertOrUpdate($record, $sqlBase, &$result, $where = null, $table = null, $functions = null, $ignoreSchema = false, $schema = null, $options = [])
    {
        $sql = $sqlBase;
        self::$uploadColumns = [];
        foreach ($record as $key => $value) :
            $column_exists = self::column_exists($key, @$schema, $table);
            if (!$column_exists) continue;
            if (is_array($column_exists) && $column_exists["type"] === "upload") {
                if (!is_array($value)) $value = [$value];
                if (!isset(self::$uploadColumns[$key])) self::$uploadColumns[$key] = [];
                foreach ($value as $val) {
                    self::$uploadColumns[$key][] = $val;
                }
                continue;
            }

            if (is_array($functions) && isset($functions[$key]) && is_callable($functions[$key])) {
                $value = $functions[$key]($value);
            } else if (!$ignoreSchema) {
                $value = self::parse_value_schema($value, $schema, $key);
            }

            if ($value === null) {
                continue;
                // return false;
            }

            if (is_array($value)) {
                $value = json_encode($value);
            }

            $sql .= ", `$key`='" . mysql_real_escape_string($value) . "'";
        endforeach;

        if (@$where) {
            $where = self::parse_where($where, $table);
            $sql .= " WHERE " . $where;
        }

        if (@$options['dieBeforeQuery'])
            self::error(["info" => $sql]);

        if (mysql_query($sql)) {
            $result++;
        } else {
            self::error(["error" => mysql_error(), "sql" => $sql]);
        }
    }

    public static function error($array = [])
    {
        if (class_exists('API') && class_exists('ApiError')) {
            API::$die = true;
            API::error(new ApiError(json_encode($array)));
        } else {
            die(json_encode($array));
        }
    }
    /**
     * Función que prepara el SQL base dependiendo del schema y de si es INSERT o UPDATE
     * @param prefix: Prefijo de la tabla
     * @param table: Tabla de la operación
     * @param schema: Schema de la tabla
     * @param update: Boolean que indica si vamos a actualizar o insertar
     * @return sql
     */
    public static function prepareBaseSQL($prefix, $table, $schema = null, $update = false, $ignoreFields = [], $record = [])
    {
        $operation = $update ? "UPDATE" : "INSERT INTO";
        if (@$schema) {
            $d = date('Y-m-d H:i:s');
            $t = time();

            $user = 1;
            if(class_exists('API')) {
                $user = @Api::$user["num"] ?: 1;
            }

            $keysBase = ["createdDate" => $d, "createdByUserNum" => $user];


            $keys = [
                "category" => ["globalOrder" => 0, "siblingOrder" => 0, "lineage" => '', "depth" => 0, "breadcrumb" => '', "parentNum" => 0],
                "multi" => ["dragSortOrder" => $t]
            ];

            $sqlBase = "$operation $prefix$table SET updatedDate='$d', updatedByUserNum='$user'";

            if (!$update) {
                $sqlBase .= ", num=NULL";

                foreach ($keysBase as $keyBase => $valueBase) {
                    if (isset($record[$keyBase])) continue;
                    if (is_string($valueBase)) {
                        $sqlBase .= ", `" . $keyBase . "`='" . $valueBase . "'";
                    } else {
                        $sqlBase .= ", `" . $keyBase . "`=" . ($valueBase ?: 'NULL');
                    }
                }

                if (@$keys[$schema['menuType']]) {
                    foreach ($keys[$schema['menuType']] as $key => $value) {
                        if (isset($record[$key])) continue;
                        if (is_string($value)) {
                            $sqlBase .= ", `" . $key . "`='" . $value . "'";
                        } else {
                            $sqlBase .= ", `" . $key . "`=" . $value;
                        }
                    }
                }
            }
        } else {
            $sqlBase = "$operation $prefix$table SET num=" . ($update ? "num" : "NULL");
        }
        return $sqlBase;
    }

    /**
     * Parsea las opciones de los métodos
     * @return lista con las opciones ignoreFields, ignoreSchema y prefix
     */
    public static function parse_options($options)
    {
        global $TABLE_PREFIX;

        $ignoreFields = @$options["forceNum"] ? [] : ['num'];

        if (@$options['ignoreFields']) {
            $ignoreFields = array_merge($ignoreFields, $options['ignoreField']);
        }

        $ignoreSchema = @$options['ignoreSchema'] ?: false;
        $prefix = isset($options["prefix"]) ? $options["prefix"] : $TABLE_PREFIX;
        return [$ignoreFields, $ignoreSchema, $prefix];
    }

    /**
     * Comprueba si una columna existe en una tabla
     * @return boolean que indica si existe o no la columna
     */
    public static function column_exists($key, $schema, $table, $prefix = "")
    {
        global $TABLE_PREFIX;

        if (isset(self::$tableCache[$table]) && isset(self::$tableCache[$table][$key])) {
            return self::$tableCache[$table][$key];
        }
        if ($schema && isset($schema[$key])) {
            return $schema[$key];
        }

        $result = mysql_query("SHOW COLUMNS FROM `$prefix$table` LIKE '$key'");

        $exists = mysql_num_rows($result) > 0;
        self::cache_column($table, $key, $exists);

        return $exists;
    }

    /**
     * Cachea la comprobación de una columna en una tabla
     */
    public static function cache_column($table, $column, $exists)
    {
        if (!isset(self::$tableCache[$table])) {
            self::$tableCache[$table] = [];
        }
        if (!isset(self::$tableCache[$table][$column])) {
            self::$tableCache[$table][$column] = $exists;
        }
    }

    /**
     * Parsea el valor dependiendo del tipo de campo
     * @return valor parseado
     */
    public static function parse_value_schema($value, $schema, $key)
    {
        if (isset($schema[$key]) && isset($schema[$key]['type'])) {
            switch ($schema[$key]['type']) {
                case 'list':
                    switch ($schema[$key]['listType']) {
                        case 'pulldownMulti':
                            if (is_array($value)) {
                                $value = "\t" . join("\t", $value) . "\t";
                            }
                            break;
                        default:
                            break;
                    }
                    break;
                case 'multitext':
                    if (is_array($value)) {
                        $value = json_encode($value);
                    }
                    break;
                case 'checkbox':
                    $value = @$value ? 1 : 0;
                    break;
                default:
                    break;
            }
        }
        return $value;
    }

    /**
     * Parsea el where pasado por parámetro
     * - or: Si se envía a true usa OR como enlace en lugar de AND
     * - not: Si se envía a true se usa
     * - operador: LIKE, IN, != o =
     * - raw_key: evita que ponga la comillas en la key y que no compruebe si existe
     *
     * @param where: String o array
     * @return where
     */
    public static function parse_where($where, $table, $prefix = "")
    {
        $builtWhere = "";
        $add_parenthesis = false;
        if (is_array($where)) {

            foreach ($where as $key => $w) :
                if (is_array($w)) {
                    if (!isset($w["value"]) || !isset($w["column"])) return false;
                    if (!isset($w["operator"])) $w["operator"] = '=';
                    $key = $w["column"];

                    if ($table && !@$w["raw_key"] && !self::column_exists($key, null, $table, $prefix)) return false;

                    $value = $w["value"];
                    $enlace = @$w["or"] ? ") OR (" : "AND";
                    if (@$w["or"]) $add_parenthesis = true;
                    $not = @$w["not"] ? "NOT " : "";

                    switch (strtoupper(@$w["operator"])) {
                        case "LIKE":
                            // Cambiado para DAXAuto... aunque no recuerdo el motivo exacto.
                            // $value = "'".mysql_real_escape_string($value)."'";
                            $value = "'" . str_replace('\\\\t', '\\t', mysql_real_escape_string($value)) . "'";
                            $operador = "LIKE";
                            break;
                        case "IS NULL":
                            $value = "NULL";
                            if ($not == '') {
                                $operador = "IS";
                            } else {
                                $operador = "IS NOT";
                                $not = '';
                            }
                            break;
                        case "IN":
                            if (is_array($value)) {
                                $value = join(", ", array_map(function ($a) {
                                    if (is_int($a)) return intval($a);
                                    if (is_numeric($a)) return floatval($a);
                                    return "'" . mysql_real_escape_string($a) . "'";
                                }, $value));
                            }
                            $value = "(" . $value . ")";
                            $operador = "IN";
                            break;
                        case "!=":
                            $value = "'" . mysql_real_escape_string($value) . "'";
                            $operador = "!=";
                            break;
                        default:
                            $value = "'" . mysql_real_escape_string($value) . "'";
                            if (in_array(strtoupper($w["operator"]), ['<', '>', '<=', '>=', '='])) {
                                $operador = $w["operator"];
                            } else {
                                $operador = "=";
                            }
                    }

                    if (@$builtWhere) $builtWhere .= " $enlace ";
                    if(!@$w["raw_key"]) {
                        $builtWhere .= "$key $not$operador $value";
                    } else {
                        $builtWhere .= "`$key` $not$operador $value";
                    }
                } else {
                    if (@$builtWhere) $builtWhere .= " AND ";
                    $builtWhere .= "`$key`='" . mysql_real_escape_string($w) . "'";
                }
            endforeach;
        } else {
            $builtWhere = $where;
        }
        if ($add_parenthesis) return "(" . $builtWhere . ")";
        return $builtWhere;
    }

    /**
     * Elimina del primer array las claves pasadas en el segundo parámetro
     * @param array: Array del que vamos a eliminar las keys
     * @param keys: Array que contiene las keys que queremos eliminar
     * @return nuevo array
     */
    public static function unsetKeys($array, $keys)
    {
        $c = $array;
        foreach ($keys as $removeKey) {
            unset($c[$removeKey]);
        }
        return $c;
    }

    /**
     * Setea un punto de BackTrace
     * @param string: string informativo
     */
    static function setBacktracePoint($string)
    {
        self::$backTracePoint = $string;
    }
    /**
     * Devuelve la información de TrackData ( uso en acai code )
     * @return trackData
     */
    static function getTrackData()
    {
        return self::$trackData;
    }
    /**
     * Setea información de track para saber por donde va toda la web ( uso en acai code )
     * @return pushed data
     */
    static function setTrackData($init = false, $type = null, $id = null, $data = [])
    {
        if ($init) {
            

            $pushedData = [
                "ip"    => $_SERVER["REMOTE_ADDR"],
                "timestamp" => round(floatval(microtime(true) * 1000), 4),
                "totalTime" => 0,
                "host" => $_SERVER["HTTP_HOST"],
                "url" => $_SERVER["REQUEST_URI"],
                "trackData" => []
            ];
            self::$trackData[] = $pushedData;
        } else {
            //            if (count(self::$trackData[count(self::$trackData)-1]["trackData"])){
            //                $prevTimestamp = self::$trackData[count(self::$trackData)-1]["trackData"][count(self::$trackData[count(self::$trackData)-1]["trackData"])-1]["timestamp"];
            //            }else{
            //                
            //            }
            $prevTimestamp = self::$trackData[count(self::$trackData) - 1]["timestamp"];
            $pushedData = [
                "timestamp" => round((floatval(microtime(true) * 1000) - $prevTimestamp), 4),
                "type" => $type,
                "id" => $id,
                "transferKeys" => !isset($data[0]) ? array_keys($data) : (is_array($data[0]) ? array_keys($data[0]) : ["undefined" => $data[0]])
                //                "data" => $data
            ];
            self::$trackData[count(self::$trackData) - 1]["trackData"][] = $pushedData;
            self::$trackData[count(self::$trackData) - 1]["totalTime"] = $pushedData["timestamp"];
            $prevPercent = 0;
            foreach (self::$trackData[count(self::$trackData) - 1]["trackData"] as $cont => $trackData) {
                $percent = ($trackData["timestamp"] * 100) / self::$trackData[count(self::$trackData) - 1]["totalTime"];
                self::$trackData[count(self::$trackData) - 1]["trackData"][$cont]["percent"] = round($percent, 2);
                self::$trackData[count(self::$trackData) - 1]["trackData"][$cont]["initPercent"] = $prevPercent;
                self::$trackData[count(self::$trackData) - 1]["trackData"][$cont]["widthPercent"] = $percent - $prevPercent;
                $prevPercent = $percent;
            }

            /*self::$trackData[count(self::$trackData)-1]["totalTime"] += $pushedData["timestamp"];
            self::$trackData[count(self::$trackData)-1]["totalTime"] = round(self::$trackData[count(self::$trackData)-1]["totalTime"],4);
                        
            self::$trackData[count(self::$trackData)-1]["trackData"][] = $pushedData;
            
            $sum = 0;
            foreach( self::$trackData[count(self::$trackData)-1]["trackData"] as $cont => $trackData){
                $percent = ($trackData["timestamp"] * 100)/self::$trackData[count(self::$trackData)-1]["totalTime"];
                
                self::$trackData[count(self::$trackData)-1]["trackData"][$cont]["percent"] = $sum;
                $sum+=$percent;
                $sum = round($sum,2);
            }*/
        }
        return $pushedData;
    }

    /**
     * Muestra la variable debug
     * @return html content
     */
    static function showDebug($formated = false, $index = -1)
    {
        if (!$formated) return json_encode(self::$debugData, JSON_PRETTY_PRINT);
        if (!self::$debugData) return "";
        $result = '
            <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
            <div class="container mx-auto border p-4 flex flex-col pb-2">
        ';
        foreach (self::$debugData as $cont => $debugQuery) {
            if ($index > -1 && $cont != $index) continue;
            $result .= '<div class="w-full ">';
            $result .= '<div class="border p-2 bg-green-200 text-green-800 text-xs border-green-400 mb-2 break-word block overflow-auto whitespace-normal">';
            $result .= $debugQuery["hora"] . "<br>";
            if (self::$backTracePoint) $result .= self::$backTracePoint . "<br>";
            $result .= $debugQuery["query"] . "<br>" . $debugQuery["time"] . " microsegundos<br>";
            $result .= "<button onclick='this.parentNode.parentNode.querySelector(\".info\").classList.toggle(\"hidden\")' class='text-white py-1 px-2 mt-2 mr-1 bg-green-500 hover:bg-green-400 focus:outline-none inline-block'>Ver resultado</button>";
            $result .= "<button onclick='this.parentNode.parentNode.querySelector(\".back\").classList.toggle(\"hidden\")' class='text-white py-1 px-2 mt-2 bg-blue-500 hover:bg-blue-400 focus:outline-none inline-block'>Ver backtrace</button>";
            $result .= '</div>';
            $result .= '<pre class="info hidden bg-gray-200 p-4 text-gray-600 whitespace-normal text-xs">';
            $result .= str_replace("  ", "&nbsp;&nbsp;", nl2br(json_encode($debugQuery["records"], JSON_PRETTY_PRINT)));
            $result .= '</pre>';
            $result .= '<pre class="back hidden bg-blue-200 p-4 text-blue-800 whitespace-normal text-xs">';
            $result .= str_replace("  ", "&nbsp;&nbsp;", nl2br(json_encode(debug_backtrace(), JSON_PRETTY_PRINT)));
            $result .= '</pre>';
            $result .= '</div>';
        }
        $result .= '</div>';
        return $result;
    }
    /**
     * Parsea un record dado de un resultado de busqueda en la base de datos añadiendo información extra de valor
     */
    static function parseGetRecord(&$record, $firstTable, $schema, $options = [], $uploadsResult = [])
    {
        global $TABLE_PREFIX;
        $record["tableName"] = $firstTable;

        if (@$uploadsResult) {
            foreach ($schema as $fieldName => $fieldValue) {
                if (!is_array($fieldValue)) continue;
                if (@$fieldValue["type"] != "upload") continue;

                if (@$uploadsResult[$firstTable][$fieldName][$record['num']]) {
                    $record[$fieldName] = $uploadsResult[$firstTable][$fieldName][$record['num']];
                }
            }
        }

        foreach ($record as $recordKey => $recordValue) {
            $schemaField = @$schema[$recordKey];
            if (!@$schemaField || !is_array($schemaField)) continue;
            if (@$options["relations"] && is_array($options["relations"]) && !in_array($recordKey, $options["relations"])) continue;
            switch (@$schemaField["type"]) {
                case "list":
                    switch ($schemaField["optionsType"]) {
                        case "query":
                            $query = getEvalOutput($schemaField['optionsQuery']);
                            if (!isset(self::$queryCaches[md5($query)])) {
                                self::$queryCaches[md5($query)] = mysql_query_fetch_all_assoc($query);
                            }
                            preg_match('/FROM\s+(.*)\s+/', $query . " ", $matches);
                            $tableQuery = null;
                            if (@$matches[1]) $tableQuery = str_replace($TABLE_PREFIX, "", trim($matches[1]));

                            $result = array_filter(self::$queryCaches[md5($query)], function ($rec) use ($recordValue) {
                                return $recordValue == $rec[array_keys($rec)[0]];
                            });
                            $result = array_merge(array_map(function ($rec) use ($tableQuery) {
                                if ($tableQuery) $rec["tableName"] = $tableQuery;
                                return $rec;
                            }, $result));
                            $record[$recordKey . "_bd"] = $result;
                            break;
                        case "text":
                            $optionsText = array_filter(explode("\n", $schemaField["optionsText"]));
                            $optionsList = [];
                            foreach ($optionsText as $option) {
                                $sepOption = explode("|", $option);
                                if (!isset($sepOption[1])) $sepOption[1] = $sepOption[0];
                                $optionsList[$sepOption[0]] = $sepOption[1];
                            }
                            // Anael: evitamos que se haga un explode de los valores null (casos extraños como el blog).
                            $resultDatas = [];
                            if(@$recordValue) $resultDatas = explode("\t", $recordValue);
                            $record[$recordKey . "_bd"] = [];
                            foreach ($resultDatas as $resultData) {
                                if (isset($optionsList[$resultData])) {
                                    $record[$recordKey . "_bd"][] = ["key" => $resultData, "value" => t_var($optionsList[$resultData])];
                                } else {
                                    $record[$recordKey . "_bd"][] = ["key" => $resultData, "value" => t_var($resultData)];
                                }
                            }
                            break;
                        case "table":

                            $nums = array_filter(explode("\t", mysql_real_escape_string($recordValue ?? '')));
                            if (@$nums && @$options["relationsDepth"]) {
                                /*$hash_query_caches = 'query_all_'.$schemaField["optionsTablename"] . '_' . $options["relationsDepth"];
                                if(!isset(self::$queryCaches[$hash_query_caches])) {
                                    self::$queryCaches[$hash_query_caches] = self::get($schemaField["optionsTablename"], null, null, null, [
                                        "relationsDepth" => intval(@$options["relationsDepth"])-1,
                                        "debug" => @$options["debug"],
                                        "translates" => @$options["translates"],
                                        "uploads" => @$options["uploads"]
                                    ]);
                                }
                                $cache_filter_by_key = $schemaField["optionsValueField"];
                                $cache_filter_by_values = $nums;
                                $record[$recordKey."_bd"] = array_values(array_filter(self::$queryCaches[$hash_query_caches], function($each) use($cache_filter_by_key, $cache_filter_by_values) {
                                    return in_array($each[$cache_filter_by_key], $cache_filter_by_values);
                                }));*/
                                // SE HA ELIMINADO ESTE SCRIPT PORQUE DUPLICABA EL TIEMPO DE RESPUESTA EN BANANA
                                //$record[$recordKey."_bd"] = self::get($schemaField["optionsTablename"], $schemaField["optionsValueField"]." IN ('".join("','", $nums)."')", null, null, ["relationsDepth" => intval(@$options["relationsDepth"])-1,"debug" => @$options["debug"]]);
                                $record[$recordKey . "_bd"] = self::get($schemaField["optionsTablename"], $schemaField["optionsValueField"] . " IN ('" . join("','", $nums) . "')", null, null, [
                                    "relationsDepth" => intval(@$options["relationsDepth"]) - 1,
                                    "debug" => @$options["debug"],
                                    "translates" => @$options["translates"],
                                    "uploads" => @$options["uploads"]
                                ]);
                            }
                            break;
                        default:
                    }
                    break;
                case "multitext":
                    $record[$recordKey . "_bd"] = json_decode($recordValue ?? '', true);
                    break;
                default:
            }
        }
        // Translates
        if (@$options["translates"]) {
            $idiomaActual = @$_REQUEST["idioma"];
            $_REQUEST["idioma"] = @$options["translates"];
            if (@$_REQUEST["idioma"]) {
                $record = self::t_recursivo($record, null);
            }
            $_REQUEST["idioma"] = $idiomaActual;
        }

        $record["breadcrumbField"] = @$schema["breadcrumbField"];
        // Si es parentNum ponemos valores por defecto
        if (@$record["breadcrumbField"] == "parentNum") {
            $record["optionsTablename"] = $record["tableName"];
            $record["optionsValueField"] = "num";
        } else if (@$record["breadcrumbField"]) {
            // Si no es parentNum, ponemos los que dicte el schema
            $record["optionsTablename"] = @$schema[$schema["breadcrumbField"]]["optionsTablename"];
            $record["optionsValueField"] =  @$schema[$schema["breadcrumbField"]]["optionsValueField"];
        }

        // Para el campo principal (para la generación de enlaces y el breadcrumb)
        if (@$record["name"]) {
            $record["mainFieldBreadcrumb"] = $record["name"];
        } else if (@$record["title"]) {
            $record["mainFieldBreadcrumb"] = $record["title"];
        } else if (@$record["titulo"]) {
            $record["mainFieldBreadcrumb"] = $record["titulo"];
        } else if (@$record["nombre"]) {
            $record["mainFieldBreadcrumb"] = $record["nombre"];
        } else {
            foreach ($schema as $key => $value) :
                if (!is_array($value)) continue;
                if (@$value["type"] == "textfield" && $key != "enlace") {
                    $record["mainFieldBreadcrumb"] = $record[$key];
                    break;
                }
            endforeach;
        }
    }
    /**
     * Obtiene los uploads de una tabla y los cachea
     * @return un array con todos los uploads
     */
    static function getUploadsResults($options)
    {
        global $TABLE_PREFIX;
        $pathCacheUploads = __DIR__ . "/../../../cache/";
        if (!file_exists($pathCacheUploads)) {
            mkdir($pathCacheUploads);
        }
        $fileName = $pathCacheUploads . "uploads-" . date("Y-m-d", time()) . "-" . $_SERVER['HTTP_HOST'] . ".json";
        if (!file_exists($fileName)) {
            $fields_to_select = "num, `order`, tableName, fieldName, recordNum, filePath, urlPath, info1, info2, info3, info4, info5, alt";
            $uploads = mysql_query_fetch_all_assoc("SELECT $fields_to_select FROM " . $TABLE_PREFIX . "uploads ORDER BY `order` ASC");
            $uploadsResult = [];
            foreach ($uploads as $upload) {
                $table = $upload['tableName'];
                $field = $upload['fieldName'];
                $num = $upload['recordNum'];
                if (!isset($uploadsResult[$table])) $uploadsResult[$table] = [];
                if (!isset($uploadsResult[$table][$field])) $uploadsResult[$table][$field] = [];
                if (!isset($uploadsResult[$table][$field][$num])) $uploadsResult[$table][$field][$num] = [];
                $uploadsResult[$table][$field][$num][] = $upload;
            }
            file_put_contents($fileName, json_encode($uploadsResult));
        } else {
            if ($options['useAbsoluteUrls']) {
                $uploadsResult = json_decode(str_replace('"urlPath":"\/cms', '"urlPath":"https:\/\/' . $_SERVER['HTTP_HOST'] . '\/cms', file_get_contents($fileName)), true);
            } else {
                $uploadsResult = json_decode(file_get_contents($fileName), true);
            }
            // $uploadsResult = isset($uploadsResult[$tableName]) ? $uploadsResult[$tableName] : [];
        }
        return $uploadsResult;
    }
    /**
     * Obtiene los uploads consultándolos a base de datos registro por registro
     * @return void
     */
    static function getUploadsResultsFromRecord(&$record, $firstTable, $schema, $options)
    {
        global $TABLE_PREFIX;
        if (!is_array($schema)) return;
        $record["tableName"] = $firstTable;
        $fields_to_select = "num, `order`, tableName, fieldName, recordNum, filePath, urlPath, info1, info2, info3, info4, info5, alt";
        foreach ($schema as $schemaKey => $schemaField) {
            if (!is_array($schemaField)) continue;
            switch (@$schemaField["type"]) {
                case "upload":
                    $uploads = mysql_query_fetch_all_assoc("SELECT " . $fields_to_select . " FROM " . $TABLE_PREFIX . "uploads WHERE tableName = '" . $firstTable . "' and fieldName = '" . $schemaKey . "' and recordNum = " . $record["num"] . " ORDER BY `order` ASC");
                    if (@$options['useAbsoluteUrls'] && is_array($uploads)) {
                        $uploads = array_map(function ($each) {
                            if (strpos($each['urlPath'], '/cms') === 0) {
                                $each['urlPath'] = str_replace('/cms', 'https://' . $_SERVER['HTTP_HOST'] . '/cms', $each['urlPath']);
                            }
                            return $each;
                        }, $uploads);
                    }
                    $record[$schemaKey] = @$uploads;
                    break;
                default:
            }
        }
    }

    /**
     * Recupera todas las configuraciones de los plugins en una sola consulta y devuelve el resultado si existe el plugin buscado en un array general
     */
    
    static function getPluginsConfig($table,$where){
        if (!self::$pluginsConfig){
            $pluginsConfig = mysql_query_fetch_all_assoc("SELECT * FROM aux_plg_config ORDER BY num DESC");
            
            self::$pluginsConfig = [];
            foreach($pluginsConfig as $pluginConfig){
                if (!@self::$pluginsConfig[$pluginConfig["plugin"]]) self::$pluginsConfig[$pluginConfig["plugin"]] = $pluginConfig;
            }
        }
        
        $auxWhere = str_replace(" ","",strtolower(trim($where)));
        $auxWhere = str_replace("plugin=","",$auxWhere);
        $auxWhere = str_replace("'","",$auxWhere);
        $auxWhere = str_replace('"','',$auxWhere);

        return @self::$pluginsConfig[$auxWhere] ?: [];
    } 

    /**
     * Obtiene registros de una tabla
     * @param table: Tabla de la que vamos a eliminar registros
     * @param where: string con el where o array de condiciones ( condicion ["field" => num,"operator" => "=","value" => 1] )
     * @param order: string
     * @param limit: string con el limit o array de limite y offset ( ["limit" => 10,"offset" => 20] )
     * @param options: Array asociativo que recoge opciones
          @option : debug => Boolean
          @option : translates => string con el idioma
          @option : uploads => Boolean
          @option : groupBy => string
          @option : aggregates => array de aggregates
          @option : relations => Boolean o array de campos en los que emitir las relaciones ( ['islas'] )
          @option : relationsDepth => Int

     * @return todos los registros
     */
    static function get($table, $where, $order = null, $limit = null, $options = [])
    {
        global $TABLE_PREFIX;

        /*if ($table == "aux_plg_config"){
            // Evitar demasiadas consultas a aux_plg_config
            $resultPlugin = self::getPluginsConfig($table,$where);        
            return $resultPlugin;
            Desactivado temporalmente porque se pierden los márgenes
        }*/
        
        $microtime = microtime(true);
        $optionsDefault = [
            "debug" => false,
            "translates" => @$_REQUEST["idioma"],
            "uploads" => true,
            "useAbsoluteUrls" => false,
            "groupBy" => null,
            "ignoreSchema" => false,
            "aggregates" => [],
            "relations" => true,
            "redis" => null,
            "onlyFields" => null,
            "redis_expire" => 60,
            "relationsDepth" => self::$defaultRelationsDepth,
            "dieBeforeQuery" => false,
            "prefix" => $TABLE_PREFIX
        ];

        // ALIAS PARA JORDAN
        if (@$options["ignoreSchemas"]) $options["ignoreSchema"] = $options["ignoreSchemas"];

        foreach ($optionsDefault as $key => $value) {
            if (!isset($options[$key])) $options[$key] = $value;
        }

        
        if (self::$force_redis) {
            $options["redis"] = is_null($options["redis"]) ? true : $options["redis"];
            if (self::$redis_expireTime) $options["redis_expire"] = self::$redis_expireTime;
        }


        // EN CASO DE PEDIR CACHE REDIS INSTANCIAMOS Y CONECTAMOS
        if (@$options["redis"]) {
            self::initCache();
        }

        if (intval(@$options["relationsDepth"]) < 0) return [];
        // Definición de tablas y schemas
        $tables = array_filter(explode(",", $table));
        $tables = array_map("trim", $tables);
        $schemas = [];
        $fullSchemas = [];

        if (!@$options["ignoreSchema"]) {
            foreach ($tables as $index => $table) {
                $tableName = explode(" ", $table)[0];
                $schemaLoaded = loadSchema($tableName);
                if (empty($schemaLoaded)) {
                    unset($tables[$index]);
                    continue;
                }
                if (!$order) $order = $schemaLoaded["listPageOrder"];
                $fullSchemas[$tableName] = $schemaLoaded;

                $schemas[$tableName] = array_filter($schemaLoaded, function ($rec) use ($options) {
                    // Creo que esto es innecesario pero por si acaso se necesite el squema sin uploads si no se pide
                    if (@$options["uploads"]) {
                        return !empty($rec) && is_array($rec) && isset($rec["type"]) && $rec["type"] != "separator";
                    } else {
                        return !empty($rec) && is_array($rec) && isset($rec["type"]) && $rec["type"] != "separator" && $rec["type"] != "upload";
                    }
                });
            }
        }

        $select = @$options["onlyFields"] ?: ["*"];
        
        if (count($tables) > 1) {
            $select = [];
            foreach ($tables as $index => $table) {
                $referencia = @explode(" ", $table)[1];
                $tableName = @explode(" ", $table)[0];
                if (!isset($schemas[$tableName])) continue;

                if (!isset($referencia)) $referencia = $options["prefix"] . $tableName;
                if ($index === 0) {
                    $select[] = $referencia . ".*";
                } else {
                    $selectResult = $referencia . "." . join("," . $referencia . ".", array_map(function ($key) use ($referencia) {
                        return $key . " AS '" . $referencia . "." . $key . "'";
                    }, array_keys($schemas[$tableName])));
                    $select[] = $selectResult;
                }
            }
        }
        if (isset($options["aggregates"]) && is_array($options["aggregates"])) {
            foreach ($options["aggregates"] as $aggregate) {
                $select[] = $aggregate;
            }
        }

        // Definición de los FROM
        $from = join(",", array_map(function ($table) use ($options) {
            return $options["prefix"] . $table;
        }, $tables));

        // Definición del Where
        $where = self::parse_where($where, null);
        $meta_limit = 1000000;

        // Definición del Limit
        if ($limit && is_array($limit)) {
            if (isset($limit["perPage"])) $limit["limit"] = $limit["perPage"];
            if (!isset($limit["limit"])) self::error(["error" => "No se puede poner limit sin limit"]);
            if (isset($limit["page"]) && !isset($limit["offset"])) $limit["offset"] = (max(1, intval($limit["page"])) - 1) * (intval($limit["limit"]));
            $meta_limit = intval($limit["limit"]);
            if (isset($limit["offset"])) {
                $limit = intval($limit["offset"]) . "," . intval($limit["limit"]);
            } else {
                $limit = intval($limit["limit"]);
            }
        } else if ($limit) {
            if(strpos($limit, ',') !== false) {
                $meta_limit = intval(explode(',', $limit)[1]);
            } else {
                $meta_limit = intval($limit);
            }
        }


        $select = join(', ', $select);
        $sql = "SELECT " . $select;
        $sql .= " FROM " . $from;
        $sql .= $where ? " WHERE " . $where : "";
        $sql .= @$options["groupBy"] ? " GROUP BY " . $options["groupBy"] : "";
        $sql .= $order ? " ORDER BY " . $order : "";

        $query_without_limit = $sql;

        $sql .= $limit ? " LIMIT " . $limit : "";

        if (@$options['dieBeforeQuery']) {
            self::error(["info" => $sql]);
        }

        $num_rows = mysql_num_rows(mysql_query($query_without_limit));

        $hashSql = self::cacheGenerateHash(md5($sql . json_encode($options)));

        if (!self::$noCacheTABLES || (self::$noCacheTABLES && !in_array($TABLE_PREFIX . str_replace($TABLE_PREFIX, "", $table), self::$noCacheTABLES))) {
            if (@$options["redis"]) {
                $resultQueryRedis = self::cacheGet($hashSql);
                if (@$resultQueryRedis) {
                    return json_decode($resultQueryRedis, true);
                }
            } else if (self::$force_load_cache) {
                if (!empty(self::$getCaches[$hashSql])) {
                    return json_decode(self::$getCaches[$hashSql], true);
                }
            }
        }

        $resultQuery = mysql_query($sql) or self::error(["error" => "Error en la consulta SQL " . mysql_error()]);
        $records = [];

        $firstTable = explode(" ", $tables[0])[0];

        // Uploads cacheados
        if (@$options["uploads"] && self::$force_json_cache_uploads) {
            $uploadsResult = self::getUploadsResults($options);
            $uploadsResult[$firstTable] = isset($uploadsResult[$firstTable]) ? $uploadsResult[$firstTable] : [];
            $possible_keys_of_uploads = array_filter(array_keys(@$uploadsResult[$firstTable]));
        }

        // Records
        while ($record = mysql_fetch_assoc($resultQuery)) {
            // Uploads sin cache
            if (@$options["uploads"] && !self::$force_json_cache_uploads) {
                self::getUploadsResultsFromRecord($record, $firstTable, @$fullSchemas[$firstTable], $options);
            }
            if (!@$options["ignoreSchema"]) self::parseGetRecord($record, $firstTable, @$fullSchemas[$firstTable], $options, @$uploadsResult);


            if (@$options["returnDataByKey"]) {

                $records[$record[$options["returnDataByKey"]]] = $record;
            } else {

                $records[] = $record;
            }
        }

        if (self::$storeDebugData) {
            $debugData = [
                "hora" => date("Y-m-d H:i:s", time()) . " " . microtime(),
                "query" => $sql,
                "records" => $records,
                "time" => microtime(true) - $microtime
            ];

            if (self::$backTracePoint) {
                $debugData["backTracePoint"] = self::$backTracePoint;
            }

            self::$debugData[] = $debugData;
        }

        if (@$options["debug"]) {
            echo self::showDebug(true, count(self::$debugData) - 1);
        }

        if (@$options["withMetas"]) {
            $listDetails =  [
                "totalRecords" => $num_rows,
                //"totalMatches" => count($records),
                "perPage"      => $meta_limit,
                //"keyword"      => "",
                "totalPages"   => ceil($num_rows / max(1, $meta_limit)),
                //"page"         => 1,
                //"prevPage"     => 1,
                //"nextPage"     => 1
            ];
            if (@$options["redis"] && @$hashSql) {
                if (!self::$noCacheTABLES || (self::$noCacheTABLES && !in_array($TABLE_PREFIX . str_replace($TABLE_PREFIX, "", $table), self::$noCacheTABLES))) {
                    self::cacheSet($hashSql, json_encode([$listDetails, $records]), $options["redis_expire"]);
                }
            } else if (self::$force_load_cache) {
                self::$getCaches[$hashSql] = json_encode([$listDetails, $records]);
            }
            return [$listDetails, $records];
        }
        if (@$options["redis"] && @$hashSql) {
            if (!self::$noCacheTABLES || (self::$noCacheTABLES && !in_array($TABLE_PREFIX . str_replace($TABLE_PREFIX, "", $table), self::$noCacheTABLES))) {
                self::cacheSet($hashSql, json_encode($records), $options["redis_expire"]);
            }
        } else if (self::$force_load_cache) {
            self::$getCaches[$hashSql] = json_encode($records);
        }
        return $records;
    }

    /**
     * Fuerza que todas las consulta se ejecuten con caché redis
     */
    static function localCache()
    {
        self::$force_load_cache = true;
    }

    /**
     * Inicializa la caché
     * TO DO : Falta comprobar si no se puede conectar
     */
    static function initCache()
    {
        $redisHost = '127.0.0.1';
        $redisPort = 6379;

        if (!self::$redis) {
            self::$redis = new Redis();
            self::$redis->connect($redisHost, $redisPort);
        } else if (!self::$redis->isConnected()) {
            self::$redis->connect($redisHost, $redisPort);
        }
    }

    /**
     * Fuerza que todas las consulta se ejecuten con caché redis
     */
    static function fullCache($expireTime = 60)
    {
        self::$force_redis = true;
        self::$redis_expireTime = $expireTime;

        self::initCache();
    }
    /**
     * Genera un hash para esta web
     */
    static function cacheGenerateHash($string)
    {
        return $_SERVER["HTTP_HOST"] . "_" . $string . "_" . self::$redis_expireTime;
    }

    /**
     * Setea datos en el caché a través de un hash
     */
    static function cacheSet($hash, $data, $expireTime = null)
    {
        if (!self::$redis) return;
        if (!self::$redis->isConnected()) return;
        if (!$expireTime && self::$redis_expireTime) $expireTime = self::$redis_expireTime;
        if (!$expireTime) $expireTime = 60;
        self::$redis->set($hash, $data);
        self::$redis->expire($hash, $expireTime);
    }

    /**
     * Obtiene datos de el caché a través de un hash
     */
    static function cacheGet($hash)
    {
        if (!self::$redis) return;
        if (!self::$redis->isConnected()) return;
        // SE HA MODIFICADO ESTE SCRIPT PORQUE CONSUMIA MAS RECURSOS EL GET
        return self::$redis->exists($hash) ? self::$redis->get($hash) : null;
    }

    /**
     * Reajusta las variables de activación de cache según la url
     */
    static function bloquedCacheByURL($url)
    {
        if (!self::$noCacheURIS) return false;
        if (in_array($url, self::$noCacheURIS)) return true;
        foreach (self::$noCacheURIS as $noCacheURI) {
            if (preg_match('/' . $noCacheURI . '/i', $url, $matches, PREG_OFFSET_CAPTURE)) return true;
        }
        return false;
    }

    /**
     * Reemplaza el hook token en el html resultante para que la seguridad no bloquee las peticiones
     */
    static function replaceHooksToken($html)
    {
        session_start();
        $html = preg_replace_callback(
            "/var hooksToken(\s)?=(\s)?[\'\"]([a-zA-Z0-9]+)[\'\"]\;/i",
            function ($matches) {
                $token = sha1(session_id() . $_SERVER["HTTP_HOST"]);
                return "var hooksToken = '" . $token . "'; console.log('⚡️⚡️⚡️ Render cached HTML ⚡️⚡️⚡️');";
            },
            $html
        );
        return $html;
    }
    /**
     * Realiza una traducción recursiva de un array de valores
     */
    static function t_recursivo($record, $idx = null)
    {
        global $TABLE_PREFIX;
        if (is_null(self::$allowedTranslateFields)) {
            self::$allowedTranslateFields = array_flip(array_map(function ($field) {
                return $field['fieldName'];
            }, mysql_query_fetch_all_assoc("SELECT DISTINCT fieldName FROM {$TABLE_PREFIX}traducciones")));
        }
        $it = $idx ? $record[$idx] : $record;
        if (is_array($it)) {
            foreach ($it as $key => $value) {
                if (is_array($it[$key])) {
                    $it[$key] = self::t_recursivo($it[$key], null);
                } else {
                    if (isset(self::$allowedTranslateFields[$key])) {
                        $it[$key] = t($it, $key);
                        if (isset($it[$key . '_bd']) && strpos($it[$key], '[') === 0 && strpos($it[$key], ']') === (strlen($it[$key]) - 1)) {
                            $it[$key . '_bd'] = json_decode($it[$key], true);
                        }
                    }
                }
            }
            if ($idx) {
                $record[$idx] = $it;
            } else {
                $record = $it;
            }
        } else {
            if ($idx && isset(self::$allowedTranslateFields[$idx])) {
                $record[$idx] = t($record, $idx);
                if (isset($record[$idx . '_bd']) && strpos($record[$idx], '[') === 0 && strpos($record[$idx], ']') === (strlen($record[$idx]) - 1)) {
                    $record[$idx . '_bd'] = json_decode($record[$idx], true);
                }
            }
        }
        return $record;
    }
    /*
     * Función traida del CMS lib/menus/default/common.php
     */
    static function updateCategoryMetadata($tableName = null, $where = '')
    {
        global $escapedTableName, $schema, $TABLE_PREFIX;

        if (!$tableName) {
            $newEscapedTableName = $escapedTableName;
            $newSchema = $schema;
        } else {
            $newEscapedTableName = $TABLE_PREFIX . str_replace($TABLE_PREFIX, "", $tableName);
            $newSchema = loadSchema($newEscapedTableName);
        }
        if ($newSchema['menuType'] != 'category') {
            return;
        }

        // load categoriesByNum
        $categoriesByNum = array();

        if ($where) $where = " WHERE $where";

        $query = "SELECT * FROM $newEscapedTableName $where ORDER BY globalOrder";
        $result = mysql_query($query) or die("MySQL Error: " . mysql_error() . "\n");
        while ($row = mysql_fetch_assoc($result)) {
            $categoriesByNum[$row['num']] = $row;
        }
        if (is_resource($result)) {
            mysql_free_result($result);
        }

        // get childNums for each parentNum
        $childNumsOfParentNum = array();
        foreach (array_keys($categoriesByNum) as $num) {
            $parentNum = (int) $categoriesByNum[$num]['parentNum'];
            $childNumsOfParentNum[$parentNum][] = $num;
        }

        // reset order
        self::_updateCategoryBranch(array(
            'branchParent' => 0,
            'records'      => &$categoriesByNum,
            'childNodes'   => $childNumsOfParentNum,
        ));


        // save new order
        foreach ($categoriesByNum as $num => $category) {
            $query = "UPDATE `$newEscapedTableName` SET ";
            $query .= "`globalOrder`  = '" . mysql_real_escape_string($category['globalOrder']) . "', ";
            $query .= "`siblingOrder` = '" . mysql_real_escape_string($category['siblingOrder']) . "', ";
            $query .= "`depth`        = '" . mysql_real_escape_string($category['depth']) . "', ";
            $query .= "`lineage`      = '" . mysql_real_escape_string($category['lineage']) . "', ";
            $query .= "`breadcrumb`   = '" . mysql_real_escape_string($category['breadcrumb']) . "' ";
            $query .= "WHERE num = '{$category['num']}'";

            mysql_query($query) or die("There was an error updating the category metadata:\n\n" . htmlspecialchars(mysql_error()) . "\n");
        }
    }
    /*
     * Función traida del CMS lib/menus/default/common.php
     */
    static function _updateCategoryBranch($args)
    {

        ## set defaults
        if (!@$args['globalOrder']) {
            $args['globalOrder'] = 0;
        }
        if (!@$args['depth']) {
            $args['depth']       = 0;
        }
        if (!@$args['lineage']) {
            $args['lineage']     = ":";
        }

        # sort branch children
        $sortedChildren = array();
        foreach ($args['childNodes'][$args['branchParent']] as $childNum) {
            $sortedChildren[$childNum] = &$args['records'][$childNum];
        }
        uasort($sortedChildren, 'self::_sortCategoriesBySiblingOrder');

        # loop over branch children
        $siblingOrder = 0;
        foreach (array_keys($sortedChildren) as $childNum) {
            $childRecord = &$args['records'][$childNum];

            $childRecord['globalOrder']  = ++$args['globalOrder'];
            $childRecord['siblingOrder'] = ++$siblingOrder;
            $childRecord['depth']        = $args['depth'];
            $childRecord['lineage']      = $args['lineage'] . "$childNum:";
            $childRecord['breadcrumb']   = @$args['breadcrumb'] ? "{$args['breadcrumb']} : {$childRecord['name']}" : $childRecord['name'];

            # if child has children, loop over them
            if (@$args['childNodes'][$childNum]) {
                self::_updateCategoryBranch(array(
                    'branchParent' => $childNum,
                    'globalOrder'  => &$args['globalOrder'],
                    'records'      => &$args['records'],
                    'childNodes'   => $args['childNodes'],
                    'depth'        => ($args['depth'] + 1),
                    'lineage'      => $childRecord['lineage'],
                    'breadcrumb'   => $childRecord['breadcrumb'],
                ));
            }
        }
    }
    /*
     * Función traida del CMS lib/menus/default/common.php
     */
    static function _sortCategoriesBySiblingOrder($arrayA, $arrayB)
    {
        if ($arrayA['siblingOrder'] < $arrayB['siblingOrder']) {
            return -1;
        }
        if ($arrayA['siblingOrder'] > $arrayB['siblingOrder']) {
            return 1;
        }
        return 0;
    }
}
