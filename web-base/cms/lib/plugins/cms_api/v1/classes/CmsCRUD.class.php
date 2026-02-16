<?
class CmsCRUD {
    static function listRecordsBulk($request,$throwError = true){
        global $TABLE_PREFIX;
        if (!@$request) throw new ApiError('No tableName specified');
        $data = [];
        foreach($request as $key => $value){
            $data[$key] = self::listRecords(@$value["where"],$key,$throwError,@$value["order"],@$value["limit"]);
        }
        return $data;
    }
    static function listRecords($whereArray = null,$tableName = null,$throwError = true,$order = null,$limit = null){
        global $TABLE_PREFIX;
        if(!$tableName) throw new ApiError("No tableName specified");
        $where = "num!=0";        
        
        if ($whereArray && is_array($whereArray)){
            //$whereArray = $whereArray["where"];
            
            if (!@$whereArray[0]) $whereArray = [$whereArray];
            foreach($whereArray as $value){
                if(!isset($value["field"]) || !isset($value["operator"]) || !isset($value["value"])) {
                    throw new ApiError('Missing parameters');
                }
                if(trim($value["field"]) === '') {
                    throw new ApiError('Field parameter cannot be empty');
                }
                $where.=" AND `".mysql_real_escape_string($value["field"])."` ";
                if(!in_array(strtoupper($value["operator"]), ["<",">","=","<=",">=","<=>","LIKE","!=","<>","IN"])) {
                    throw new ApiError('Operator not supported');
                }
                $where.=" ".$value["operator"]." ";
                if($value["operator"] === "IN" && is_array($value["value"])) {
                    $where.= "(".implode(',',array_map(function($each) { return "'".mysql_real_escape_string($each)."'"; }, $value["value"])).")";
                } else {
                    $where.= is_string($value["value"]) ? "'".mysql_real_escape_string($value["value"])."'" : mysql_real_escape_string($value["value"]);
                }
            }
        }
        
        $orderString = $order ? " ORDER BY ".$order : ''; 
        $limitString = $limit ? " LIMIT ".$limit : ''; 
        
        $listRecords_query = mysql_query("SELECT * FROM ".$TABLE_PREFIX.$tableName." WHERE ".$where.$orderString.$limitString);
        if(!$listRecords_query) throw new ApiError(mysql_error());

        $listRecords = [];
        while ($row = mysql_fetch_assoc($listRecords_query)) {
            if (@$row["num"]){
                $resultUploads = @mysql_query_fetch_all_assoc("SELECT * FROM ".$TABLE_PREFIX."uploads WHERE tableName = '".$tableName."' AND recordNum=".intval($row["num"]));
                foreach($resultUploads as $upload){
                    if (!@$row[$upload["fieldName"]]) $row[$upload["fieldName"]] = [];
                    $row[$upload["fieldName"]][] = $upload;
                }
            }
            $listRecords[] = $row;
        }

        $listDetails =  [
            "totalRecords" => count($listRecords),
            "totalMatches" => count($listRecords),
            "perPage"      => 250,
            "keyword"      => "",
            "totalPages"   => 1,
            "page"         => 1,
            "prevPage"     => 1,
            "nextPage"     => 1
        ];
        
        if (!@$listRecords && $throwError){
            throw new ApiError('No '.$tableName.' were found');
        }
        
        foreach($listRecords as $cont => $record){
            $listRecords[$cont]["datos"] = @$listRecords[$cont]["datos"] ? json_decode($listRecords[$cont]["datos"],true) : [];
        }
        
        $data=[$listDetails,$listRecords];
        return $data;
        
    }
    
    static function removeRecord($id = null,$tableName = null){
        global $TABLE_PREFIX;
        if(!$tableName) throw new ApiError("No tableName specified");
        if (!@$id){
            throw new ApiError('No '.$tableName.' id was sent');
        }
        API::activity($id,$tableName,null,"DELETE");
        // $record = mysql_query_fetch_all_assoc("SELECT * FROM ".$TABLE_PREFIX."usuarios WHERE num = ".intval($id));
        $record = mysql_query("SELECT * FROM ".$TABLE_PREFIX.$tableName." WHERE num = ".intval($id));
        if(!$record) throw new ApiError(mysql_error());
        $record = mysql_fetch_assoc($record);

        if (!@$record){
            throw new ApiError('No '.$tableName.' was found');
        }else{
            mysql_query("DELETE FROM ".$TABLE_PREFIX.$tableName." WHERE num=".intval($record["num"])." LIMIT 1");
        }

        return ["success" => true];
    }
    
    static function getRecord($id = null,$tableName = null){
        global $TABLE_PREFIX;
        if(!$tableName) throw new ApiError("No tableName specified");
        if (!@$id){
            throw new ApiError('No '.$tableName.' id was sent');
        }
        
        // $record = mysql_query_fetch_all_assoc("SELECT * FROM ".$TABLE_PREFIX."usuarios WHERE num = ".intval($id)." LIMIT 1");
        $record = mysql_query("SELECT * FROM ".$TABLE_PREFIX.$tableName." WHERE num = ".intval($id));
        if(!$record) throw new ApiError(mysql_error());
        $record = mysql_fetch_assoc($record);

        if (!@$record){
            throw new ApiError('No '.$tableName.' was found');
        }
        if (@$record["num"]){
            $resultUploads = @mysql_query_fetch_all_assoc("SELECT * FROM ".$TABLE_PREFIX."uploads WHERE tableName = '".$tableName."' AND recordNum=".intval($record["num"]));
            foreach($resultUploads as $upload){
                if (!@$record[$upload["fieldName"]]) $record[$upload["fieldName"]] = [];
                $record[$upload["fieldName"]][] = $upload;
            }
        }

        return [$record];
    }
    static function tVar($identificador = null,$valor = null){
        if(!$identificador) throw new ApiError("No key specified");
        if(!$valor) throw new ApiError("No value specified");
        
        global $TABLE_PREFIX;
        $identifier = $identificador;
        if (defined($identifier)) return $valor;
        
        $recordtr = mysql_query_fetch_all_assoc("SELECT * FROM {$TABLE_PREFIX}textos_generales WHERE identificador='".$identifier."' LIMIT 1");

        if (@$recordtr){
            return ["text" => $recordtr[0]["texto"]];
        }else{
            CocoDB::insertRecords('textos_generales', ['identificador' => $identifier, 'texto' => $valor]);
            return ["text" => $valor];
        } 

    }
    
}