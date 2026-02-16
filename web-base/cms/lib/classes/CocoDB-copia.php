<?php
class CocoDB {
    private static $tableCache = [];
    private static $uploadColumns = [];
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
    static function insertRecords($table, $records, $functions = [], $options = []) {
        
        if (!isset($records[0])) {
            $records = [$records];
        }
        if (@$options["preSaveTempId"]) {
            $preSave = true;
        }

        list($ignoreFields, $ignoreSchema, $prefix) = self::parse_options($options);

        if (!$ignoreSchema) {
            $schema = @loadSchema($table);
            if (!@$schema) die('Error. Tabla no encontrada');
        }
        $sqlBase = self::prepareBaseSQL($prefix, $table, @$schema);

        $result = 0;
        $lastSaved = 0;
        foreach ($records as $record):
            $record = self::unsetKeys($record, $ignoreFields);
            self::insertOrUpdate($record, $sqlBase, $result, null, $prefix.$table, $functions, $ignoreSchema, @$schema);
            $lastSaved = mysql_insert_id();
            foreach(self::$uploadColumns as $keyColumn => $uploadColumn){
                foreach($uploadColumn as $keyCol => $urlPath){
                    self::insertRecords("uploads",[
                        "urlPath" => $urlPath,
                        "filePath" => realpath(__DIR__."/../../../".$urlPath),
                        "fieldName" => $keyColumn,
                        "recordNum" => $lastSaved,
                        "tableName" => $table,
                        "createdTime" => date("Y-m-d H:i:s"),
                        "order" => time() + $keyCol,
                        "width" => 640,
                        "height" => 480
                    ],[],["ignoreSchema" => true]);
                }
            }
        endforeach;
        
        
        if (@$preSave) {
            $query = "UPDATE {$prefix}uploads "
                . "   SET recordNum = LAST_INSERT_ID(), preSaveTempId = '' "
                . " WHERE tableName     = '".mysql_real_escape_string($table)."' AND "
                . "       preSaveTempId = '".mysql_real_escape_string($options["preSaveTempId"])."'";
            
            mysql_query($query) or die("MySQL Error: ". htmlspecialchars(mysql_error()) . "\n");
            
            $query = "UPDATE {$prefix}traducciones "
                . "   SET recordNum = LAST_INSERT_ID(), preSaveTempId = '' "
                . " WHERE tableName     = '".mysql_real_escape_string($table)."' AND "
                . "       preSaveTempId = '".mysql_real_escape_string($options["preSaveTempId"])."'";
            mysql_query($query) or die("MySQL Error: ". htmlspecialchars(mysql_error()) . "\n");
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
    static function deleteRecords($table, $where, $options = []) {
        list($_, $_, $prefix) = self::parse_options($options);
        
        $where = self::parse_where($where, $table);
        if (!@$where) return false;

        $q = mysql_query("DELETE FROM $prefix$table WHERE $where");
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
    static function updateRecords($table, $records, $where, $functions = [], $options = []) {
        if (!isset($records[0])) {
            $records = [$records];
        }

        list($ignoreFields, $ignoreSchema, $prefix) = self::parse_options($options);

        if (!$ignoreSchema) {
            $schema = @loadSchema($table);
            if (!@$schema) die('Error. Tabla no encontrada');
        }
        $sqlBase = self::prepareBaseSQL($prefix, $table, @$schema, true);
        $result = 0;
        foreach ($records as $record):
            $record = self::unsetKeys($record, $ignoreFields);
            // Está comentado, pero no se si hace falta, si se descomenta se rompe el guardar del Builder (Plugin)
            // if (@$schema['menuType'] == 'category' && !isset($record['parentNum'])) {
            //     continue;
            // }
            self::insertOrUpdate($record, $sqlBase, $result, $where, $prefix.$table, $functions, $ignoreSchema, @$schema);
        endforeach;
        
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
    private static function insertOrUpdate($record, $sqlBase, &$result, $where = null, $table = null, $functions = null, $ignoreSchema = false, $schema = null) {
        $sql = $sqlBase;
        self::$uploadColumns = [];
        foreach ($record as $key => $value):
            $column_exists = self::column_exists($key, @$schema, $table);
            if (!$column_exists) continue;
            if (is_array($column_exists) && $column_exists["type"] === "upload"){
                if (!is_array($value)) $value = [$value];
                if (!isset(self::$uploadColumns[$key])) self::$uploadColumns[$key] = [];
                foreach($value as $val){
                    self::$uploadColumns[$key][] = $val;
                }
                continue;
            }

            if (is_array($functions) && isset($functions[$key]) && is_callable($functions[$key])) {
                $value = $functions[$key]($value);
            }
            else if (!$ignoreSchema) {
                $value = self::parse_value_schema($value, $schema, $key);
            }

            if ($value === null) {
                continue;
                // return false;
            }

            if (is_array($value)) {
                $value = json_encode($value);
            }

            $sql .= ", `$key`='".mysql_real_escape_string($value)."'";
        endforeach;

        if (@$where) {
            $where = self::parse_where($where, $table);
            $sql .= " WHERE ".$where;
        }

        if (mysql_query($sql)) {
            $result++;
        }else{
            if(class_exists('API') && class_exists('ApiError'))
                API::error(new ApiError(json_encode(["error" => mysql_error(),"sql" => $sql])));
            else
                die(json_encode(["error" => mysql_error(),"sql" => $sql]));
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
    private static function prepareBaseSQL($prefix, $table, $schema = null, $update = false) {
        $operation = $update ? "UPDATE" : "INSERT INTO";
        if (@$schema) {
            $d = date('Y-m-d H:i:s');
            $t = time();
            $sqlBase = "$operation $prefix$table SET updatedDate='$d'";
            if (!$update) {
                $sqlBase .= ", num=NULL, createdDate='$d', createdByUserNum=1, updatedByUserNum=1";
                switch ($schema['menuType']) {
                    case 'category':
                    $sqlBase .= ", globalOrder=0, siblingOrder=0, lineage='', depth=0, breadcrumb=''";
                    break;
                    case 'multi':
                    $sqlBase .= ", dragSortOrder='$t'";
                    break;
                    default:
                    break;
                }
            }
        }
        else {
            $sqlBase = "$operation $prefix$table SET num=".($update ? "num" : "NULL");
        }
        return $sqlBase;
    }

    /**
     * Parsea las opciones de los métodos
     * @return lista con las opciones ignoreFields, ignoreSchema y prefix
     */
    private static function parse_options($options) {
        global $TABLE_PREFIX;

        $ignoreFields = ['num'];
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
    private static function column_exists($key, $schema, $table) {
        if (isset(self::$tableCache[$table]) && isset(self::$tableCache[$table][$key])) {
            return self::$tableCache[$table][$key];
        }
        if ($schema && isset($schema[$key])) {
            return $schema[$key];
        }
        
        $result = mysql_query("SHOW COLUMNS FROM `$table` LIKE '$key'");
        $exists = mysql_num_rows($result) > 0;
        self::cache_column($table, $key, $exists);

        return $exists;
    }

    /**
     * Cachea la comprobación de una columna en una tabla
     */
    private static function cache_column($table, $column, $exists) {
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
    private static function parse_value_schema($value, $schema, $key) {
        switch($schema[$key]['type']) {
            case 'list':
                switch ($schema[$key]['listType']) {
                    case 'pulldownMulti':
                        if (is_array($value)) {
                            $value = "\t".join("\t", $value)."\t";
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
        return $value;
    }

    /**
     * Parsea el where pasado por parámetro
     * - or: Si se envía a true usa OR como enlace en lugar de AND
     * - not: Si se envía a true se usa
     * - operador: LIKE, IN, != o =
     * 
     * @param where: String o array
     * @return where
     */
    private static function parse_where($where, $table) {
        $builtWhere = "";
        if (is_array($where)) {
            foreach ($where as $key => $w):
                if (is_array($w)) {
                    if (!isset($w["value"]) || !isset($w["column"])) return false;
                    $key = $w["column"];
                    if (!self::column_exists($key, null, $table)) return false;
                    $value = $w["value"];
                    $enlace = @$w["or"] ? "OR" : "AND";
                    $not = @$w["not"] ? "NOT " : "";
                    switch (strtoupper(@$w["operator"])) {
                        case "LIKE":
                            $value = "'".mysql_real_escape_string($value)."'";
                            $operador = "LIKE";
                        break;
                        case "IN":
                            if (is_array($value)) {
                                $value = join(", ", array_map(function($a) {
                                    if (is_int($a)) return intval($a);
                                    if (is_numeric($a)) return floatval($a);
                                    return "'".mysql_real_escape_string($a)."'";
                                }, $value));
                            }
                            $value = "(".$value.")";
                            $operador = "IN";
                        break;
                        case "!=":
                            $value = "'".mysql_real_escape_string($value)."'";
                            $operador = "!=";
                        break;
                        default:
                            $value = "'".mysql_real_escape_string($value)."'";
                            $operador = "=";
                    }
                    
                    if (@$builtWhere) $builtWhere .= " $enlace ";
                    $builtWhere .= "`$key` $not$operador $value";
                }
                else {
                    if (@$builtWhere) $builtWhere .= " AND ";
                    $builtWhere .= "`$key`='".mysql_real_escape_string($w)."'";
                }
            endforeach;
        }
        else {
            $builtWhere = $where;
        }
        return $builtWhere;
    }

    /**
     * Elimina del primer array las claves pasadas en el segundo parámetro
     * @param array: Array del que vamos a eliminar las keys
     * @param keys: Array que contiene las keys que queremos eliminar
     * @return nuevo array 
     */
    private static function unsetKeys($array, $keys) {
        $c = $array;
        foreach ($keys as $removeKey) {
            unset($c[$removeKey]);
        }
        return $c;
    }
}