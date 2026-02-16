<?php

# load modules
$noSessionStart = true; // don't call session start (not needed for viewers)
$libDir         = pathinfo(__FILE__, PATHINFO_DIRNAME);
require_once "$libDir/init.php";

// error checking
if (!$SETTINGS['isInstalled']) { die("Error: You must install the program before you can use the viewers."); }

// globals
global $VIEWER_NAME;
global $FORM;
$FORM = $_REQUEST; // eventually we'll migrate from FORM to _REQUEST

global $hashTraducciones;
$hashTraducciones = array();
global $__allowedTranslateFields;
$__allowedTranslateFields = null;

/*
list($records, $details) = getRecords(array(
    'tableName'     => 'listings', // REQUIRED, error if not specified, tableName is prefixed with $TABLE_PREFIX
    'where'         => '',         // optional, defaults to blank
    'orderBy'       => '',         // optional, defaults to table sort order if undefined
    'limit'         => '',         // optional, defaults to blank
    'offset'        => '',         // optional, defaults to blank (if set but no limit then limit is set to high number as per mysql docs)
    'perPage'       => '',         // optional, number of records to show per page - loads page number from $_REQUEST['page']
    'allowSearch'   => '',         // optional, defaults to yes, adds search info from query string
    'requireSearchMatch' => '',    // optional, don't show any results unless search keyword submitted and matched
    'loadUploads'   => '',         // optional, defaults to yes, loads upload array into upload field
    'loadCreatedBy' => '',         // optional, defaults to yes, adds createdBy. fields for created user
    'loadListDetails' => '',       // optional, defaults to yes, adds $details with prev/next page, etc info
    'orWhere'       => '',         // optional, adding " OR ... " to end of where clause
    'useSeoUrls'    => false,      // optional, use SEO urls, defaults to no

    'leftJoin'      => array(        // Note: leftJoins require you to use fully qualified fieldnames in WHERE and ORDER BY, such as tablename.fieldname
        'grocery_aisle' => 'aisleNum', // foreign table => local field (that matches num in foreign field)
        'brands'        => 'brandNum',
    ),

    'debugSql'      => false,      // optional, display SQL query, defaults to no
));
*/

require_once dirname(__FILE__)."/classes/CocoParser.php";
require_once dirname(__FILE__)."/classes/CocoDB.php";
require_once dirname(__FILE__)."/classes/CocoEmail.php";
require_once dirname(__FILE__)."/vendor/minifier/autoload.php";

require_once dirname(__FILE__)."/handlers/handler_ws.php";

$configuracionRecord = isset($configuracionRecord) ? $configuracionRecord : CocoDB::get("configuracion",null,null,null,["relationsDepth" => 0])[0];
//Se cambia linea de abajo por llamada a cocodb por no tener datos schema y no poder realizar traducciones en algunos casos - REVISAR
// $configuracionRecord = Db::getInstance()->fetch("SELECT * FROM {$TABLE_PREFIX}configuracion LIMIT 1");

function h($file, $plantilla = true, &$version = null) {
    $absolute = strpos($file, 'http') === 0 || strpos($file, '//') === 0;
    if ($absolute) return $file;
    if ($plantilla) {
        $file = substr(RUTA_PLANTILLA_FROM_INIT.$file, 1); // Para quitar el '/' del principio
    }
    $root = realpath(__DIR__.'/../../');
    if (file_exists($root.'/'.$file)) {
        $timestamp = filemtime($root.'/'.$file);
    }
    if (@$timestamp) { // Archivo encontrado y todo ok
        if ($version !== null) {
            $version += $timestamp;
        }
        $timestamp = base_convert($timestamp, 10, 36);

        // Añadimos -timestamp al final del nombre del archivo
        $parts = pathinfo($file);
        $carpeta = str_replace($parts['basename'], '', $file);
        $path = $carpeta.$parts['filename'].'-hsh'.$timestamp.'.'.$parts['extension'];
        return minify_file(@$plantilla ? '/'.$path : $path, $timestamp);
    }
    return @$plantilla ? '/'.$file : $file;
}

function minify_file($path, $timestamp) {
    if (strpos($path, '/') !== 0) return $path;
    $root = realpath(__DIR__.'/../../');
    $fileType = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $originalFilePath = preg_replace('/-hsh([\w]+)/', '', $path);
    $originalFileName = preg_replace('/-hsh([\w]+)/', '', basename($path));
    $baseFolder = str_replace($root, '', pathinfo($path, PATHINFO_DIRNAME));
    $minifiedDir = $root.$baseFolder.'/minified';

    if (!file_exists($minifiedDir)) {
        mkdir($minifiedDir);
    }
    switch ($fileType) {
        case 'css':
            $minifier = new MatthiasMullie\Minify\CSS($root.$originalFilePath);
        break;
        case 'js':
            $minifier = new MatthiasMullie\Minify\JS($root.$originalFilePath);
        break;
        default:
            return $path;
    }

    $parts = pathinfo($originalFileName);
    $minifiedFileName = $parts['filename'].'-hsh'.$timestamp.'.'.$parts['extension'];
    $urlPath = str_replace($root, '', $minifiedDir).'/'.$minifiedFileName;
    if (file_exists($minifiedDir.'/'.$originalFileName)) {
        // Si el archivo no ha cambiado, lo devolvemos
        if (filemtime($minifiedDir.'/'.$originalFileName) >= filemtime($root.$originalFilePath)) {
            return $urlPath;
        }
    }
    $minifier->minify($minifiedDir.'/'.$originalFileName);
    return $urlPath;
}

function t_var($identificador){
    global $TABLE_PREFIX;
//    $identifier = strtoupper(CocoParser::parsea_campo2(utf8_encode($identificador))); // SE QUITO PARA PONER LAS TILDES EN ACAI
    $identifier = strtoupper(CocoParser::parsea_campo2($identificador)); // ANAEL SE EQUIVOCO
    if (defined($identifier)) return constant($identifier);
    $DB = Db::getInstance();
    $recordtr = $DB->fetch("SELECT * FROM {$TABLE_PREFIX}textos_generales WHERE identificador=? LIMIT 1", [$identifier]);

    if (@$recordtr){
        $recordtr["tableName"] = "textos_generales";
        return t($recordtr, "texto");
    }else{
        CocoDB::insertRecords('textos_generales', ['identificador' => $identifier, 'texto' => $identificador]);
        return t_var($identificador);
    }
}

function parsea_texto2($data){
    if(!$data) return '';
    if (base64_encode(base64_decode($data)) === $data) {
        if (mb_detect_encoding(base64_decode($data), 'UTF-8', true)){
            return base64_decode($data);
        }else{
            return $data;
        }
    }else {
        return $data;
    }
}


function t($record, $campo = "title", $options = array(), $idioma=null){

    global $TABLE_PREFIX, $hashTraducciones,$__allowedTranslateFields;

    if (isset($record[$campo]) === false) return null;
    if (is_array(@$record[$campo])) return @$record[$campo];

    if (!$idioma) $idioma = @$_REQUEST["idioma"];
    if(!@$record['num'] || !@$record['tableName'] || !@$idioma) return CocoParser::parsea_codigos_en_linea(@$record[$campo], $options);

    if (@$idioma){
        if(is_null($__allowedTranslateFields)) {
            $__allowedTranslateFields = array_flip(array_map(function($field) {
                return $field['fieldName'];
            }, mysql_query_fetch_all_assoc("SELECT DISTINCT fieldName FROM {$TABLE_PREFIX}traducciones")));
        }
        if (!isset($__allowedTranslateFields[$campo]) && isset($record[$campo])) return CocoParser::parsea_codigos_en_linea(parsea_texto2($record[$campo]),$options);
    }

    $hash = md5(@$record["num"].$campo.@$record["tableName"].$idioma.json_encode(@$_REQUEST));
    // Comprobamos si el hash pedido ya está en la tabla
    if (@$hashTraducciones[$hash] && @$record['num']) return $hashTraducciones[$hash];

    if (@CocoDB::$redis) {
        $redisHash = CocoDB::cacheGenerateHash("TRANSLATE-".@$_REQUEST["idioma"].$hash);
        if (CocoDB::cacheGet($redisHash)) {
            return CocoDB::cacheGet($redisHash);
        }
    }

    $DB = Db::getInstance();

    if (@$idioma){
        if (@$record["urlPath"] || @$record["info1"]){
            $recordtr = $DB->fetch("SELECT fieldValue FROM {$TABLE_PREFIX}traducciones WHERE tableName='uploads' AND recordNum=? AND fieldName=? AND prefix=? AND uploadNum=? LIMIT 1", [$record['recordNum'], $campo, @$idioma, $record['num']]);
        }else{
            $recordtr = $DB->fetch("SELECT fieldValue FROM {$TABLE_PREFIX}traducciones WHERE tableName=? AND recordNum=? AND fieldName=? AND prefix=? LIMIT 1", [@$record['tableName'], @$record['num'], $campo, @$idioma]);
        }
        if ($recordtr) {
            $trad = CocoParser::parsea_codigos_en_linea(parsea_texto2($recordtr["fieldValue"]),$options);
            $hashTraducciones[$hash] = $trad;
            if (@CocoDB::$redis) CocoDB::cacheSet($redisHash,$trad);
            return $trad;
        }
        else {
            $trad = CocoParser::parsea_codigos_en_linea(@$record[$campo],$options);
            $hashTraducciones[$hash] = $trad;
            if (@CocoDB::$redis) CocoDB::cacheSet($redisHash,$trad);
            return $trad;
        }

    }else{
        $trad = CocoParser::parsea_codigos_en_linea(@$record[$campo],$options);
        $hashTraducciones[$hash] = $trad;
        if (@CocoDB::$redis) CocoDB::cacheSet($redisHash,$trad);
        return $trad;
    }
}


function getRecords($options) {
    global $VIEWER_NAME, $TABLE_PREFIX;
    $VIEWER_NAME = "getRecords(" . @$options['tableName'] . ")";

    // error checking
    _getRecords_errorChecking($options);

    // load schema
    $schema = loadSchema($options['tableName']);
    if (!$schema) { die("$VIEWER_NAME: Couldn't load schema for '" .htmlspecialchars($options['tableName']). "'!"); }


    // set defaults
    if (!array_key_exists('orderBy', $options))         { $options['orderBy'] = $schema['listPageOrder']; } // default orderBy to section editor OrderBy value
    if (!array_key_exists('loadUploads', $options))     { $options['loadUploads']     = true; }
    if (!array_key_exists('allowSearch', $options))     { $options['allowSearch']     = true; }
    if (!array_key_exists('requireSearchMatch', $options)) { $options['requireSearchMatch']   = false; }
    if (!array_key_exists('loadCreatedBy', $options))   { $options['loadCreatedBy']   = true; }
    if (!array_key_exists('loadListDetails', $options)) { $options['loadListDetails'] = true; }

    $options['pageNum'] = @$options['pageNum'] ? @$options['pageNum'] : max(@$_REQUEST['page'], 1);
    $options['limit']   = @$options['perPage'] ? $options['perPage'] : @$options['limit'];
    $options['offset']  = @$options['perPage'] ? (($options['pageNum']-1) * $options['perPage']) + @$options['offset'] : @$options['offset'];
    if ($options['offset'] && !$options['limit']) { $options['limit'] = 1000000; } // if offset and no limit set limit to high number as per MySQL docs


    // Get records
    list($rows, $totalRecords) = _getRecords_loadResults($options, $schema);

    // Add uploads
    if (@$options['loadUploads'])   { _getRecords_addUploadFields($rows, $options, $schema); }

    // Add createdBy.fields to records
    if (@$options['loadCreatedBy'] && @$schema['createdByUserNum']) { _getRecords_joinTable($rows, $options, 'accounts'); }

    // Add joinTable fields
    if (@$options['joinTable']) { _getRecords_joinTable($rows, $options); }

    // get List Details
    $listDetails = array();
    if ($options['loadListDetails']) {
        $listDetails = _getRecords_getListDetails($options, count($rows), $totalRecords, $schema);
    }

    //
    return array($rows, $listDetails, $schema);
}




//
function _createDefaultWhereWithFormInput($schema, $where) {
    global $VIEWER_NAME;
    if ($where != "") { return $where; } // don't set default where if it's already been specified
    $seenQueries     = array();

    //
    $andConditions = array();
    foreach ($_REQUEST as $name => $value) {
        if ($value == '') { continue; } // skip fields with empty values
        if (!preg_match("/^(.+?)(_min|_max|_match|_keyword|_prefix|_query|)$/", $name, $matches)) { continue; } // skip fields without search suffixes
        $fieldnamesAsCSV = $matches[1];
        $searchType      = $matches[2];
        $fieldnames      = explode(',',$fieldnamesAsCSV);
        $orConditions    = array();


        foreach ($fieldnames as $fieldnameString) {

            // get field value for date searches
            if (preg_match("/^(.+?)_(year|month|day)$/", $fieldnameString, $matches)) {
                $fieldname = $matches[1];
                $dateValue = $matches[2];
                if (!is_array(@$schema[$fieldname])) { continue; } // skip invalid fieldnames
                if     ($dateValue == 'year')        { $fieldValue = "YEAR(`$fieldname`)"; }
                elseif ($dateValue == 'month')       { $fieldValue = "MONTH(`$fieldname`)"; }
                elseif ($dateValue == 'day')         { $fieldValue = "DAYOFMONTH(`$fieldname`)"; }
                else                                 { die("unknown date value '$dateValue'!"); }
            }

            // get field value for everything else
            else {
                if (!is_array(@$schema[$fieldnameString])) { continue; } // skip invalid fieldnames
                $fieldValue  = '`' .str_replace('.', '`.`', $fieldnameString). '`'; // quote bare fields and qualified fieldnames (table.field)
            }

            // add conditions
            $fieldSchema = @$schema[$fieldnameString];
            $isMultiList = @$fieldSchema['type'] == 'list' && (@$fieldSchema['listType'] == 'pulldownMulti' || @$fieldSchema['listType'] == 'checkboxes');
            $valueAsNumberOnly = preg_replace('/[^\d\.]/', '', $value);

            if (!$fieldValue) { die("No fieldValue defined!"); }
            if      ($searchType == '_min')     { $orConditions[] = "$fieldValue+0 >= $valueAsNumberOnly"; }
            else if ($searchType == '_max')     { $orConditions[] = "$fieldValue+0 <= $valueAsNumberOnly"; }
            else if ($searchType == '_match' || $searchType == '') {
                if ($isMultiList) { $orConditions[] = "$fieldValue LIKE '%\\t" .mysql_real_escape_string($value). "\\t%'"; }
                else              { $orConditions[] = "$fieldValue = '"    .mysql_real_escape_string($value). "'"; }
            }
            else if ($searchType == '_keyword') { $orConditions[] = "$fieldValue LIKE '%" .escapeMysqlWildcards(mysql_real_escape_string($value)). "%'"; }
            else if ($searchType == '_prefix')  { $orConditions[] = "$fieldValue LIKE '" .escapeMysqlWildcards(mysql_real_escape_string($value)). "%'"; }
            else if ($searchType == '_query')   {
                if (@$seenQueries["$fieldnamesAsCSV=$searchType"]++) { continue; } // only add each query once since we're add all fields at once
                $orConditions[] = _getWhereForSearchQuery($value, $fieldnames, $schema);
            }
            else { die($VIEWER_NAME . ": Unknown search type '$searchType'!"); }
        }

        $condition = join(' OR ', $orConditions);
        if ($condition) { $andConditions[] = "($condition)"; }

    }

    $where = join(" AND ", $andConditions);

    return $where;
}




//
function _addWhereConditionsForSpecialFields($schema, $extraConditions) {
    $where            = "";
    $conditions       = array();

    if ($extraConditions)        { array_push($conditions, "($extraConditions)"); }
    if (@$schema['hidden'])      { array_push($conditions, "hidden = 0"); }
    if (@$schema['publishDate']) { array_push($conditions, "publishDate <= NOW()"); }
    if (@$schema['removeDate'])  {
        $thisCondition  = "removeDate >= NOW()";  // NULL end date or future end date
        if (@$schema['neverRemove']) { $thisCondition .= " OR neverRemove = 1"; } // never expires checked
        array_push($conditions, "($thisCondition)");
    }
    if ($conditions) {
        $where = " WHERE " . implode(" AND ", $conditions) . " ";
    }

    return $where;
}


// return MySQL WHERE clause for google style query: +word -word "multi word phrase"
function _getWhereForSearchQuery($query, $fieldnames, $schema = null) {

    // error checking
    if (!is_array($fieldnames)) { die(__FUNCTION__ . ": fieldnames must be an array!"); }

    // parse out "quoted strings"
    $searchTerms = array();
    $quotedStringRegexp = "/([+-]?)(['\"])(.*?)\\2/";
    preg_match_all($quotedStringRegexp, $query, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        list(,$plusOrMinus,,$phrase) = $match;
        $phrase = trim($phrase);
        $searchTerms[$phrase] = $plusOrMinus;
    }
    $query = preg_replace($quotedStringRegexp, "", $query); // remove quoted strings

    // parse out keywords
    $keywords = preg_split('/[\\s,;]+/', $query);
    foreach ($keywords as $keyword) {
        $plusOrMinus = "";
        if (preg_match("/^([+-])/", $keyword, $matches)) {
            $keyword = preg_replace("/^([+-])/", "", $keyword, 1);
            $plusOrMinus = $matches[1];
        }

        $searchTerms[$keyword] = $plusOrMinus;
    }

    // create query
    $where = "";
    $conditions = array();
    foreach ($searchTerms as $term => $plusOrMinus) {
        if ($term == '') { continue; }
        $likeOrNotLike  = ($plusOrMinus == '-') ? "NOT LIKE" : "LIKE";
        $andOrOr        = ($plusOrMinus == '-') ? " AND " : " OR ";
        $termConditions = array();

        foreach ($fieldnames as $fieldname) {
            if ($schema && !is_array(@$schema[$fieldname])) { continue; } // fields are stored as arrays, other entries are table metadata
            $fieldname = trim($fieldname);
            $escapedKeyword = escapeMysqlWildcards( mysql_real_escape_string($term) );
            $quotedFieldname  = '`' . str_replace('.', '`.`', $fieldname) . '`';
            $termConditions[] = "$quotedFieldname $likeOrNotLike '%$escapedKeyword%'";
        }

        if ($termConditions) {
            $conditions[] = "(" . join($andOrOr, $termConditions) . ")\n";
        }

    }

    //
    $where = join(" AND ", $conditions);
    return $where;
}



function getFilenameFieldValue($record, $filenameFields) {
    global $VIEWER_NAME;
    $filenameValue = "";

    // convert string to array
    if (!is_array($filenameFields)) {
        $filenameFields = preg_split("/\s*,\s*/", $filenameFields);
    }

    // error checking
    foreach ($filenameFields as $fieldname) {
        if ($fieldname == "") { continue; }
        if (!array_key_exists($fieldname, $record)) { die("$VIEWER_NAME: Unknown field '" .htmlspecialchars($fieldname). "' in filenameFields or titleField options!"); }
    }

    // get first defined field value
    foreach ($filenameFields as $fieldname) {
        if (@$record[$fieldname] == "") { continue; }
        $filenameValue = @$record[$fieldname];
        $filenameValue = preg_replace('/[^a-z0-9\.\-\_]+/i', '-', $filenameValue);
        $filenameValue = preg_replace("/(^-+|-+$)/", '', $filenameValue); # remove leading and trailing underscores
        if ($filenameValue) { $filenameValue .= "-"; }
        break;
    }

    //
    return $filenameValue;

}


// return an array of list values
function getListValues($tableName, $fieldName, $fieldValue) {
    $array = explode("\t", $fieldValue);
    $array = array_slice($array, 1, -1); // remove blanks from leading/trailing tabs
    return $array;
}

// return an array of list labels
function getListLabels($tableName, $fieldName, $fieldValue) {
    $values = getListValues($tableName, $fieldName, $fieldValue);

    // load values to labels
    static $valuesToLabels;
    if (!@$valuesToLabels[$tableName][$fieldName]) {
        $valuesToLabels[$tableName][$fieldName] = getListOptions($tableName, $fieldName);
    }

    //
    $labels = array();
    foreach ($values as $value) {
        if (@$valuesToLabels[$tableName][$fieldName][$value]) {
            array_push($labels, $valuesToLabels[$tableName][$fieldName][$value]);
        }
        else {
            array_push($labels, $value);
        }
    }

    return $labels;
}


//
function _getRecords_errorChecking($options) {
    global $VIEWER_NAME;

    ### error checking
    $errors = "";
    if     (!is_array($options))     { $errors .= "First argument for getRecords() must be an array!<br/>\n"; }
    elseif (!@$options['tableName']) { $errors .= "No 'tableName' value specified in options!<br/>\n"; }

    // check for unknown options!
    $validOptions = array('tableName', 'where', 'orWhere', 'orderBy', 'limit', 'offset', 'perPage', 'loadUploads', 'allowSearch', 'requireSearchMatch', 'loadCreatedBy', 'useSeoUrls', 'loadListDetails', 'joinTable', 'debugSql', 'leftJoin');
    foreach (array_keys($options) as $optionName) {
        if (!in_array($optionName, $validOptions)) {
            $validOptionsAsCSV = implode(', ', $validOptions);
            $errors .= "Unknown option '$optionName' specified.  Valid option names are: ($validOptionsAsCSV)<br/>\n";
        }
    }


    //
    if (@$options['perPage'] && (@$options['limit'] || @$options['offset'])) {
        $errors .= "Can't set both 'perPage' and 'limit' or 'offset' options at the same time, choose one!<br/>\n";
    }

    //
    if ($errors) { die("$VIEWER_NAME errors<br/>\n$errors"); }

}

//
function _getRecords_loadResults($options, $schema) {
    global $VIEWER_NAME, $TABLE_PREFIX;

    // create fieldlist
    $selectFields = "`{$options['tableName']}`.*";

    // add left joins
    $LEFT_JOIN = "";
    if (@$options['leftJoin']) {

        // Fix $_REQUEST keys containing tablename
        __replaceUnderscoresInRequest($options['tableName']);

        // add qualified fieldsnames to schema
        foreach (array_keys(getSchemaFields($schema)) as $fieldname) {
            $schema["{$options['tableName']}.$fieldname"]         = $schema[$fieldname];
            $schema["{$options['tableName']}.$fieldname"]['name'] = $fieldname;
        }

        //
        foreach ($options['leftJoin'] as $foreignTable => $foreignKey) {

            // get ON condition
            if (preg_match("/\s*ON\b/i", $foreignKey)) { $ON_CONDITION = $foreignKey; }
            else {
                $ON_CONDITION = "ON {$options['tableName']}.`$foreignKey` = $foreignTable.num\n";
            }

            // add left join
            $LEFT_JOIN .= "LEFT JOIN `{$TABLE_PREFIX}{$foreignTable}` AS `$foreignTable` $ON_CONDITION";

            // add fieldnames to SELECT
            $foreignSchemaFields = getSchemaFields($foreignTable);
            $validFieldTypes = array('textfield','textbox','wysiwyg','date','list','checkbox');
            foreach (array_keys($foreignSchemaFields) as $fieldname) {
                if (in_array(@$foreignSchemaFields[$fieldname]['type'], $validFieldTypes)) {
                    $selectFields .= ",\n                           $foreignTable.`$fieldname` as `$foreignTable.$fieldname`";
                }

                // Fix $_REQUEST keys containing tablename
                __replaceUnderscoresInRequest($foreignTable);

                // add fieldnames to schema
                $schema["{$foreignTable}.$fieldname"]         = $foreignSchemaFields[$fieldname];
                $schema["{$foreignTable}.$fieldname"]['name'] = $fieldname;
            }
        }
    }

    // create where
    $where = @$options['where'];
    if ($options['allowSearch']) {
        $defaultWhere = _createDefaultWhereWithFormInput($schema, '');
        if ($options['requireSearchMatch'] && !$defaultWhere) { $defaultWhere = "0 = 1"; } // always false

        if     (!$where)                 { $where = $defaultWhere; }
        elseif ($where && $defaultWhere) { $where = "$where AND $defaultWhere"; }
    }
    if (@$schema['createdByUserNum'] && @$schema['_hideRecordsFromDisabledAccounts']) {
        if ($where) { $where .= " AND "; }
        $subquery = "SELECT num FROM `{$TABLE_PREFIX}accounts` WHERE disabled != 1 AND (expiresDate > NOW() OR neverExpires = 1)";
        $where   .= "{$options['tableName']}.createdByUserNum IN ($subquery)";
    }
    $where = _addWhereConditionsForSpecialFields($schema, $where); // adds WHERE to beginning of string, do this LAST
    if (@$options['orWhere']) {
        $where = preg_replace("/^\s*WHERE\s*/i", "", $where); // remove WHERE keyword
        if ($where) { $where = "($where) OR {$options['orWhere']}"; }
        else        { $where = $options['orWhere']; }
        if ($where) { $where = "\nWHERE $where"; }
    }

    // create query
    $query  = "SELECT SQL_CALC_FOUND_ROWS $selectFields\n";
    $query .= "FROM `$TABLE_PREFIX{$options['tableName']}` as `{$options['tableName']}`\n";
    $query .= $LEFT_JOIN;
    $query .= "$where\n";
    $query .= (@$options['orderBy']) ? " ORDER BY {$options['orderBy']}" : "";
    if (@$options['limit'])  { $query .= "\n LIMIT "  . (int) $options['limit']; }
    if (@$options['offset']) { $query .= "\nOFFSET " . (int) $options['offset']; }
    if (@$options['debugSql']) { print "<xmp>$query</xmp>"; }

    // execute query
    $result = mysql_query($query) or die("$VIEWER_NAME MySQL Error: ". htmlspecialchars(mysql_error()) . "\n");
    $records   = array();
    while ($record = mysql_fetch_assoc($result)) {
        $filenameValue       = getFilenameFieldValue($record, @$schema['_filenameFields']);
        $record['_filename'] = rtrim($filenameValue, '-');
        $record['tableName'] = @$options['tableName'];

        if    (@!$schema['_detailPage']) { $record['_link'] = "javascript:alert('Set Detail Page Url for this section in: Admin > Section Editors > Viewer Urls')"; }
        elseif(@$options['useSeoUrls'])  { $record['_link'] = @$schema['_detailPage'] . '/' . $filenameValue . $record['num'] . "/"; }
        else                             { $record['_link'] = @$schema['_detailPage'] . '?' . $filenameValue . $record['num']; }

        array_push($records, $record);
    }
    if (is_resource($result)) { mysql_free_result($result); }



    // modify field values
    foreach ($schema as $fieldname => $fieldSchema) {
        if (!is_array($fieldSchema)) { continue; } // fields are stored as arrays, other entries are table metadata

        // assign checkbox values
        if (@$fieldSchema['type'] != 'checkbox') {
            if (array_key_exists('checkedValue', $fieldSchema)) { // skip checkbox fields without checked/unchecked values
                foreach (array_keys($records) as $index) {
                    $record = &$records[$index];
                    $record[$fieldname] = $record[$fieldname] ? $fieldSchema['checkedValue'] : $fieldSchema['uncheckedValue'];
                }
            }
        }

    }



    // get record count
    $totalRecords = (int) mysql_select_found_rows();

    //
    return array($records, $totalRecords);
}

// Fix $_REQUEST keys.  Reference: PHP replaces dots with underscores: https://ca.php.net/variables.external#Dots_in_incoming_variable_names
// Note: We're doing this here because we have the foreign table names, it's used in _createDefaultWhereWithFormInput()
function __replaceUnderscoresInRequest($tablename) {
    foreach ($_REQUEST as $key => $value) {
        $newKey = preg_replace("/^($tablename)_/", '\1.', $key);
        if ($newKey != $key) {
            $_REQUEST[$newKey] = $_REQUEST[$key];
            # unset($_REQUEST[$key]); // unset original
        }
    }
}


//
function _getRecords_joinTable(&$rows, $options, $joinTable = '') {
    global $VIEWER_NAME, $TABLE_PREFIX;

    $joinTable  = $joinTable ? $joinTable : $options['joinTable'];
    $isAccounts = ($joinTable == 'accounts');
    $joinFieldA = 'createdByUserNum';
    $joinFieldB = $isAccounts ? 'num' : 'createdByUserNum';


    // get fieldA values as CSV
    $fieldAValues = array();
    foreach ($rows as $row) {
        if ($row[$joinFieldA]) { $fieldAValues[] = $row[$joinFieldA]; }
    }
    $fieldAValues = array_unique($fieldAValues);
    if (!$fieldAValues) { return; }
    $fieldAValuesAsCSV = implode(',', $fieldAValues);

    // load rows
    list($joinrows,,$schema) = getRecords(array(
        'tableName'       => $joinTable,
        'where'           => "`$joinFieldB` IN ($fieldAValuesAsCSV)",
        'loadUploads'     => $options['loadUploads'],
        'allowSearch'     => false,
        'loadCreatedBy'   => false,
        'loadListDetails' => false,
        'useSeoUrls'      => @$options['useSeoUrls'],
        'debugSql'        => @$options['debugSql'],
    ));

    // get join rows by num
    $joinRowsByNum = array();
    foreach ($joinrows as $record) {
        $joinRowsByNum[ $record[$joinFieldB] ] = $record;
    }

    // get tableBfields
    $joinTableFields = array();
    foreach ($schema as $fieldname => $fieldSchema) {
        if (!is_array($fieldSchema)) { continue; }
        if (@$fieldSchema['type'] == 'separator') { continue; }
        $joinTableFields[] = $fieldname;
    }
    $joinTableFields[] = "_filename";
    $joinTableFields[] = "_link";

    // add tableB rows
    $fieldnamePrefix = $isAccounts ? 'createdBy' : $joinTable;
    foreach (array_keys($rows) as $index) {
        $record     = &$rows[$index];
        $joinRecord = @$joinRowsByNum[$record[$joinFieldA]];

        foreach ($joinTableFields as $fieldname) {
            if ($isAccounts && $fieldname == 'password') { continue; }
            $record["$fieldnamePrefix.$fieldname"] = @$joinRecord[$fieldname];
        }
    }
}

//
function _getRecords_addUploadFields(&$rows, $options, $schema) {
    global $VIEWER_NAME, $TABLE_PREFIX;
    if (@!$options['loadUploads']) { return; }

    // get recordNums
    $recordNums = array();
    foreach ($rows as $record) {
        if (@$record['num']) { $recordNums[] = $record['num']; }
    }
    if (!$recordNums) { return; }

    // get upload fields
    $uploadFields = array();
    $uploadFieldsAsCSV = "";
    foreach ($schema as $fieldname => $fieldSchema) {
        if (!is_array($fieldSchema))           { continue; }  // fields are stored as arrays, other entries are table metadata, skip metadata
        if (@$fieldSchema['type'] != 'upload') { continue; }  // skip all but upload fields
        if ($uploadFieldsAsCSV) { $uploadFieldsAsCSV .= ","; }
        $uploadFields[]     = $fieldname;
        $uploadFieldsAsCSV .= "'$fieldname'";
    }

    // load uploads
    $uploadsByNumAndField = array();
    $recordNumsAsCSV   = implode(',', $recordNums);
    if ($recordNumsAsCSV && $uploadFieldsAsCSV) {
        $query   = "SELECT * FROM `{$TABLE_PREFIX}uploads`\n";
        $query  .= " WHERE tableName = '" .mysql_real_escape_string($options['tableName']). "' AND\n";
        $query  .= "       fieldName IN ($uploadFieldsAsCSV) AND\n";
        $query  .= "       recordNum IN ($recordNumsAsCSV)\n";
        $query  .= " ORDER BY `order`, num";
        if (@$options['debugSql']) { print "<xmp>$query</xmp>"; }

        $result  = mysql_query($query) or die("MySQL Error: ". htmlspecialchars(mysql_error()) . "\n");
        while ($record = mysql_fetch_assoc($result)) {
            $record['filename']     = pathinfo($record['filePath'], PATHINFO_BASENAME);
            $record['extension']    = pathinfo($record['filePath'], PATHINFO_EXTENSION);
            $record['isImage']      = preg_match("/\.(gif|jpg|jpeg|png)$/i", $record['filePath']);
            $record['hasThumbnail'] = $record['isImage'] && $record['thumbUrlPath'];
            $uploadsByNumAndField[$record['recordNum']][$record['fieldName']][] = $record;
        }
        if (is_resource($result)) { mysql_free_result($result); }
    }

    // add uploads to records
    foreach (array_keys($rows) as $index) {
        $record = &$rows[$index];

        foreach ($uploadFields as $fieldname) {
            $record[$fieldname] = array();
            $uploadsArray    = @$uploadsByNumAndField[$record['num']][$fieldname];
            if ($uploadsArray) { $record[$fieldname] = $uploadsArray; }
        }
    }
}


//
function _getRecords_getListDetails($options, $rowCount, $totalRecords, $schema) {
    global $VIEWER_NAME;
    $details = array();

    ### get list details
    $details = array();
    $details['invalidPageNum']   = !$rowCount && $options['pageNum'] > 1;
    $details['noRecordsFound']   = !$rowCount && $options['pageNum'] == 1;
    $details['page']             = $options['pageNum'];
    $details['perPage']          = @$options['perPage'];

    $details['totalPages']       = 1;
    if (@$options['perPage'] && $totalRecords > $options['perPage']) {
        $details['totalPages'] = ceil($totalRecords / $options['perPage']);
    }

    $details['totalRecords']     = $totalRecords;
    $details['pageResultsStart'] = min($totalRecords, $options['offset'] + 1);
    $details['pageResultsEnd']   = min($totalRecords, $options['offset'] + $options['limit']);

    # get page nums
    $_minOfPageNumAndTotalPages    = min($options['pageNum'], $details['totalPages']);
    $details['prevPage']       = ($_minOfPageNumAndTotalPages > 1) ? $_minOfPageNumAndTotalPages-1 : '';
    $details['nextPage']       = ($_minOfPageNumAndTotalPages < $details['totalPages']) ? $_minOfPageNumAndTotalPages+1 : '';
    if ($details['invalidPageNum']) {
        $details['prevPage'] = $details['totalPages'];
    }

    # pass query arguments forward in page links
    $extraQueryArgs    = "";
    $extraPathInfoArgs = "";
    foreach ($_REQUEST as $key => $value) {
        if (!is_array($key) && !is_array($value) && !is_null($value)){
            if ($key == 'page')  { continue; } // skip page value, we set it below
            $extraQueryArgs    .= urlencode($key) .'='. urlencode($value) . '&';
            $extraPathInfoArgs .= urlencode($key) .'-'. urlencode($value) . '/';
        }
    }

    # get page links
    $listViewer = $_SERVER['SCRIPT_NAME'];
    if (@$options['useSeoUrls']) {
        $details['prevPageLink']   = "$listViewer/{$extraPathInfoArgs}page-{$details['prevPage']}/";
        $details['nextPageLink']   = "$listViewer/{$extraPathInfoArgs}page-{$details['nextPage']}/";
        $details['firstPageLink']  = "$listViewer/{$extraPathInfoArgs}page-1/";
        $details['lastPageLink']   = "$listViewer/{$extraPathInfoArgs}page-{$details['totalPages']}/";
    }
    else {
        $details['prevPageLink']  = "$listViewer?{$extraQueryArgs}page={$details['prevPage']}";
        $details['nextPageLink']  = "$listViewer?{$extraQueryArgs}page={$details['nextPage']}";
        $details['firstPageLink'] = "$listViewer?{$extraQueryArgs}page=1";
        $details['lastPageLink']  = "$listViewer?{$extraQueryArgs}page=" . $details['totalPages'];
    }

    //
    $details['_detailPage'] = @$schema['_detailPage'];
    $details['_listPage']   = @$schema['_listPage'] ? $schema['_listPage'] : "javascript:alert('Set List Page Url for this section in: Admin > Section Editors > Viewer Urls')"; ;

    return $details;
}
