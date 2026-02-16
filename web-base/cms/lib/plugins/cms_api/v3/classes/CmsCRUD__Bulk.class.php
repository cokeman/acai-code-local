<?
class Bulk extends CmsCRUD {
    static $defaultMethod = "get";
    static function get($request){
        return self::listRecordsBulk($request);
    }
    static function bulk_sync($request){
        global $TABLE_PREFIX;
        $result = [];
        foreach($request as $tableName => $values){
            if (!@$values["records"]) continue;
            
            $records = $values["records"];
            
            if (!isset($records[0])) {
                $records = [$records];
            }

            list($ignoreFields, $ignoreSchema, $prefix) = CocoDB::parse_options(@$values["options"]);

            if (!$ignoreSchema) {
                $schema = @loadSchema($tableName);
                if (!@$schema) die('Error. Tabla no encontrada');
            }
            
            $result[$tableName] = [];
            
            $key = @$values["key"] ?: "num";
            foreach ($records as $record):
                $isUpdate = isset($record[$key]) && CocoDB::get($tableName,$key."=".intval($record[$key]),null,1,["ignoreSchema" => true]);

                

                if ($isUpdate){
                    
                    CocoDB::updateRecords($tableName,$record,$key."=".intval($record[$key]), [], @$record["options"] ?: @$values["options"]);
                }else{
                    CocoDB::insertRecords($tableName,$record,[],@$record["options"] ?: @$values["options"]);
                }
                $preValue = $isUpdate ? $record[$key] : null;
                /*
                $record = CocoDB::unsetKeys($record, $ignoreFields,);

                $where = $isUpdate ? "`".$key."`='".$preValue."'" : "";
                $sqlBase = CocoDB::prepareBaseSQL($prefix, $tableName, @$schema, $isUpdate,[], $record);
                $contResult = 0;
                CocoDB::insertOrUpdate($record, $sqlBase, $contResult, $where, $prefix.$tableName, [], $ignoreSchema, @$schema, @$record["options"] ?: @$values["options"]);*/
                $result[$tableName][] = $isUpdate ? [$key => $preValue,"success" => mysql_affected_rows() ? true : false]: ["num" => mysql_insert_id(),"success" => mysql_affected_rows() ? true : false];
            endforeach;

            if (@$values['options']['generate_category_metadata']) {
                CocoDB::updateCategoryMetadata($tableName);
            }

        }
        
        return $result;
    }
}