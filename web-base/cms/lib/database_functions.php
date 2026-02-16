<?php
if (!extension_loaded("mysql")) {
	require_once "mysql.php";
}

function escapeMysqlWildcards($string) {
	return addcslashes($string, '%_');
}
function connectToMySQL($returnErrors = false) {
	global $SETTINGS, $DBH;
	addPlugins("pre_db", $SETTINGS);
	### Connect to database
	try {
		Db::getInstance($SETTINGS['mysql']['hostname'], $SETTINGS['mysql']['username'], $SETTINGS['mysql']['password'], $SETTINGS['mysql']['database']);
		$DBH = @mysql_connect($SETTINGS['mysql']['hostname'], $SETTINGS['mysql']['username'], $SETTINGS['mysql']['password']);
		@mysql_select_db($SETTINGS['mysql']['database']);
	}
	catch (Exception $e) {
		$connectionError = $e->getMessage();
		$libDir = pathinfo(__FILE__, PATHINFO_DIRNAME);
		
		ob_start();
		$doc = new domdocument();
		require(__DIR__."/../../template/estandar/mantenimiento.tpl");
		$resultado = ob_get_clean();
		echo $resultado;
		
		exit();
	}


	### check for required mysql version
	$currentVersion  = preg_replace("/[^0-9\.]/", "", mysql_get_server_info());
	if (version_compare(REQUIRED_MYSQL_VERSION, $currentVersion, '>')) {
		$error  = "This program requires MySQL v" .REQUIRED_MYSQL_VERSION. " or newer. This server has v$currentVersion installed.<br/>\n";
		$error .= "Please ask your server administrator to install MySQL v" .REQUIRED_MYSQL_VERSION. " or newer.<br/>\n";
		if ($returnErrors) { return $error; }
		die($error);
	}

	mysql_set_charset("utf8mb4") or die("Error loading character set utf8mb4: " .mysql_error(). "");
	mysqlStrictMode(true);
	return '';
}

function mysqlStrictMode($strictMode) {
	$currentVersion  = preg_replace("/[^0-9\.]/", "", mysql_get_server_info());
	$isMySql5        = version_compare($currentVersion, '5.0.0', '>=');

	# only for Mysql 5
	if (!$isMySql5) { return; }

	# set MySQL strict mode - https://dev.mysql.com/doc/refman/5.0/en/server-sql-mode.html
	if ($strictMode) {
		$query = "SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER'";
		mysql_query($query) or die("MySQL Error: " .mysql_error(). "\n");
	}
	else {
		$query = "SET SESSION sql_mode = 'NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER'";
		mysql_query($query) or die("MySQL Error: " .mysql_error(). "\n");
	}
}

function createMissingSchemaTablesAndFields() {
	global $APP, $TABLE_PREFIX;

	$schemaTables = getSchemaTables();
	$mysqlTables  = getMysqlTablesWithPrefix();

	// create missing schema tables in mysql
	foreach ($schemaTables as $tableName) {

		// create mysql table
		$mysqlTableName = $TABLE_PREFIX . $tableName;
		if (!in_array($mysqlTableName, $mysqlTables)) {
			notice("Creating MySQL table for schema table: $tableName<br/>\n");
			$result = mysql_query("CREATE TABLE `".mysql_real_escape_string($mysqlTableName)."` (num int(10) unsigned NOT NULL auto_increment, PRIMARY KEY (num)) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;");
			if (!$result) { alert("Error creating MySQL table: $mysqlTableName<br/>\MySQL error was: ". htmlspecialchars(mysql_error()) . "\n"); }
		}

		// get schema fieldnames
		$schemaFieldnames = array();
		$tableSchema = loadSchema($tableName);
		foreach ($tableSchema as $name => $valueOrArray) {
			if (is_array($valueOrArray)) { array_push($schemaFieldnames, $name); } // only fields has arrays as values
		}

		// get mysql fieldnames
		$mysqlFieldnames = array();
		$result = mysql_query("SHOW COLUMNS FROM `".mysql_real_escape_string($mysqlTableName)."`") or die("MySQL Error: ". htmlspecialchars(mysql_error()) . "\n");
		while ($row = mysql_fetch_assoc($result)) { array_push($mysqlFieldnames, strtolower($row['Field'])); }

		// add missing fieldnames to mysql
		$addFieldSQL = '';
		foreach ($schemaFieldnames as $fieldname) {
			if (!in_array(strtolower($fieldname), $mysqlFieldnames)) {
				$columnType = getColumnTypeFor($fieldname, @$tableSchema[$fieldname]['type'], @$tableSchema[$fieldname]['customColumnType']);
				if (!$columnType) { continue; }
				if ($addFieldSQL) { $addFieldSQL .= ", "; }
				$addFieldSQL .= " ADD COLUMN `".mysql_real_escape_string($fieldname)."` $columnType";
			}
		}
		if ($addFieldSQL) {
			mysql_query("ALTER TABLE `".mysql_real_escape_string($mysqlTableName)."` $addFieldSQL") or die("Error adding fields to '$mysqlTableName', the error was:\n\n". htmlspecialchars(mysql_error()));
			notice("Adding MySQL fields for schema table: $tableName<br/>\n");
		}
	}
}
function getSchemaTables($dir = '') {

	if (!$dir) { $dir = realpath("{$GLOBALS['APP']['datadir']}/schema/"); }

	// get schema files
	$schemaTables = array();
	foreach (scandir($dir) as $file) {
		if (!preg_match("/([^.]+)\.ini\.php$/", $file, $matches)) { continue; } // skip non-schema files
		$tableName = $matches[1];
		$schemaTables[] = $tableName;
	}

	return $schemaTables;
}
function &getSchemaFields($tableNameOrSchema) {

	// load schema
	$schema = $tableNameOrSchema;
	if (!is_array($schema)) { $schema =  loadSchema($tableNameOrSchema); }

	// load fields
	$fieldList = array();
	foreach ($schema as $name => $valueOrArray) {
		if (is_array($valueOrArray)) {  // only fields have arrays as values, other values are table metadata
			$fieldList[$name]         = $valueOrArray;
			$fieldList[$name]['name'] = $name; // add pseudo-field for fieldname
		}
	}

	//
	return $fieldList;
}
function loadSchema($tableName, $schemaDir = '') {
	global $APP;

	// error checking
	if (!$tableName) { die(__FUNCTION__ . ": no tableName specified!"); }

	// get schemapath
	$tableNameWithoutPrefix = getTableNameWithoutPrefix($tableName);
	if (!$schemaDir) { $schemaFilepath = "{$APP['datadir']}/schema/$tableNameWithoutPrefix.ini.php"; }
	else             { $schemaFilepath = "$schemaDir/". getTableNameWithoutPrefix($tableName) . ".ini.php"; }

	// load schema
	$schema = array();
	if (file_exists($schemaFilepath)) {
		$schema = loadINI($schemaFilepath);
	}

	//
	return $schema;
}

function getTableNameWithoutPrefix($tableName) {  // add $TABLE_PREFIX to table if it isn't there already
	return  preg_replace("/^{$GLOBALS['TABLE_PREFIX']}/", '', $tableName); // remove prefix
}
function getColumnTypeFor($fieldName, $fieldType, $customColumnType = "") {
	$columnType = "";

	// special case: default column type specified
	if      ($customColumnType)        { $columnType = $customColumnType; }

	// Special Fieldnames
	elseif  ($fieldName == 'num')              { $columnType = 'int(10) unsigned NOT NULL auto_increment'; }
	elseif  ($fieldName == 'createdDate')      { $columnType = 'datetime NOT NULL'; }
	elseif  ($fieldName == 'createdByUserNum') { $columnType = 'int(10) unsigned NOT NULL'; }
	elseif  ($fieldName == 'updatedDate')      { $columnType = 'datetime NOT NULL'; }
	elseif  ($fieldName == 'updatedByUserNum') { $columnType = 'int(10) unsigned NOT NULL'; }
	elseif  ($fieldName == 'dragSortOrder')    { $columnType = 'int(10) unsigned NOT NULL'; }
	// NOTE:  Other special field types don't need to be specified here because they have required
	//        ... field types in /lib/menus/default/editField_functions.php that map to the column
	//        ... types below.  We only need to specify the column types above because they are
	//        ... not available with any predefined field type.

	// otherwise return columnType for fieldType
	elseif ($fieldType == '')          { $columnType = ''; }
	elseif ($fieldType == 'none')      { $columnType = ''; }
	elseif ($fieldType == 'textfield') { $columnType = 'mediumtext'; }
	elseif ($fieldType == 'multitext') { $columnType = 'mediumtext'; }
	elseif ($fieldType == 'textbox')   { $columnType = 'mediumtext'; }
	elseif ($fieldType == 'codigo')    { $columnType = 'mediumtext'; }
	elseif ($fieldType == 'wysiwyg')   { $columnType = 'mediumtext'; }
	elseif ($fieldType == 'date')      { $columnType = 'datetime NOT NULL'; }
	elseif ($fieldType == 'list')      { $columnType = 'mediumtext'; }
	elseif ($fieldType == 'checkbox')  { $columnType = 'tinyint(1) unsigned NOT NULL'; }
	elseif ($fieldType == 'upload')    { $columnType = ''; }
	elseif ($fieldType == 'separator') { $columnType = ''; }

	// special fields types
	elseif ($fieldType == 'accessList') { $columnType = ''; }
	elseif ($fieldType == 'dateCalendar')  { $columnType = ''; }

	else {
		$columnType = 'mediumtext';
		//die(__FUNCTION__ . ": Field '" .htmlspecialchars($fieldName). "' has unknown fieldType '" .htmlspecialchars($fieldType). "'.");
	}

	return $columnType;
}
function getMysqlTablesWithPrefix() {
	global $TABLE_PREFIX;

	$tableNames = array();
	$escapedTablePrefix = mysql_real_escape_string($TABLE_PREFIX);
	$escapedTablePrefix = preg_replace("/([_%])/", '\\\$1', $escapedTablePrefix);  // escape mysql wildcard chars
	$result    = mysql_query("SHOW TABLES LIKE '$escapedTablePrefix%'") or die("MySQL Error: ". htmlspecialchars(mysql_error()) . "\n");
	while ($row = mysql_fetch_row($result)) {
		array_push($tableNames, $row[0]);
	}
	return $tableNames;
}
function getTablenameErrors($tablename) {

	// get used tablenames
	static $usedTableNamesLc = array();
	static $loadedTables;
	if (!$loadedTables++) {
		foreach (getMysqlTablesWithPrefix() as $usedTablename) {
			$withoutPrefixLc = strtolower(getTablenameWithoutPrefix($usedTablename));
			array_push($usedTableNamesLc, $withoutPrefixLc);
		}
		foreach (getSchemaTables() as $usedTableName) {
			$withoutPrefixLc = strtolower($usedTablename);
			array_push($usedTableNamesLc, $withoutPrefixLc);
		}
	}


	// get reserved tablenames
	$reservedTableNamesLc = array();
	array_push($reservedTableNamesLc, 'home', 'admin', 'database', 'accounts', 'license'); // the are hard coded menu names
	array_push($reservedTableNamesLc, 'default');  // can't be used because menu folder exists with default menu files
	array_push($reservedTableNamesLc, 'all');      // can't be used because the "all" keyword gives access to all menus in user accounts

	// get error
	$error       = null;
	$tablenameLc = strtolower(getTableNameWithoutPrefix($tablename));
	if      ($tablenameLc == "")                          { $error = "No table name specified!\n"; }
	else if (preg_match("/^_/", $tablenameLc))            { $error = "Table name cannot start with an underscore!\n"; }
	else if (preg_match("/[A-Z]/", $tablename))           { $error = "Table name must be lowercase!\n"; }
	else if (preg_match("/[^a-z0-9\_]/", $tablename))     { $error = "Table name can only contain these characters (\"a-z, 0-9, and _\")!\n"; }
	if (in_array($tablenameLc, $usedTableNamesLc))        { $error = "That table name is already in use, please choose another.\n"; }
	if (in_array($tablenameLc, $reservedTableNamesLc))    { $error = "That table name is not allowed, please choose another.\n"; }
	//
	return $error;
}
function getListOptions($tablename, $fieldname) {

	$valuesToLabels = array();

	$schema       = loadSchema($tablename);
	$fieldSchema  = $schema[$fieldname];
	$fieldOptions = getListOptionsFromSchema($fieldSchema);
	foreach ($fieldOptions as $valueAndLabel) {
		list($value, $label) = $valueAndLabel;
		$valuesToLabels[$value] = $label;
	}

	return $valuesToLabels;
}
function getListOptionsFromSchema($fieldSchema, $record = null) {
	global $TABLE_PREFIX;

	$listOptions = array();
	$optionsType = @$fieldSchema['optionsType'];

	### parse text options
	if ($optionsType == 'text') { // parse
		$optionText = explode("\n", @$fieldSchema['optionsText']);

		foreach ($optionText as $optionString) {
			if (preg_match("/(^|[^\|])(\|\|)*(\|)(?!\|)/", $optionString, $match, PREG_OFFSET_CAPTURE)) {
				$delimiterOffset = $match[3][1];
				$value = substr($optionString, 0, $delimiterOffset);
				$label = substr($optionString, $delimiterOffset+1);
			}
			else {
				$value = $optionString;
				$label = $optionString;
			}

			$value = str_replace("||", "|", $value);
			$label = str_replace("||", "|", $label);

			// remove trailing whitespace
			$value = rtrim($value);
			$label = rtrim($label);

			$listOptions[] = array($value, $label);
		}
	}

	### lookup table values
	else {

		// create query
		if ($optionsType == 'table') {
			$selectFields = "`" . @$fieldSchema['optionsValueField'] ."`, `". @$fieldSchema['optionsLabelField'] . "`";
			$selectTable  = "`" . $TABLE_PREFIX . $fieldSchema['optionsTablename'] . "`";
			$tableSchema  = loadSchema($fieldSchema['optionsTablename']);
			$orderBy      = @$tableSchema['listPageOrder'] ? "ORDER BY {$tableSchema['listPageOrder']}" : "";
			$query        = "SELECT $selectFields FROM $selectTable $orderBy LIMIT 0, 999";
		}
		else if ($optionsType == 'query') {
			$filterFieldValue                = @$record[ @$fieldSchema['filterField'] ];
			$GLOBALS['ESCAPED_FILTER_VALUE'] = mysql_real_escape_string($filterFieldValue);
			$query                           = getEvalOutput($fieldSchema['optionsQuery']);
		}
		else { die("Unknown optionsType '$optionsType'!"); }

		// execute query
		$result = @mysql_query($query);
		if (!$result) {
			$error  = "There was an error creating the list field.\n\n";
			$error .= "MySQL Error: " .mysql_error(). "\n\n";
			header("Content-type: text/plain");
			die($error);
		}
		while ($row = mysql_fetch_row($result)) {

			$listOptions[] = array($row[0],$row[1]);
		}
		if (is_resource($result)) { mysql_free_result($result); }
	}

	//
	return $listOptions;
}
function mysql_select_found_rows() {
	$result = mysql_query('SELECT FOUND_ROWS()') or die("MySQL Error: ". htmlspecialchars(mysql_error()) . "\n");
	$count  = mysql_result($result, 0);
	if (is_resource($result)) { mysql_free_result($result); }

	return $count;
}
function &mysql_query_fetch_all_assoc($query) {
	$microtime = microtime(true);
    if (class_exists("CocoDB") && isset(CocoDB::$force_redis) && CocoDB::$force_redis){
        $hash = CocoDB::cacheGenerateHash("DB_".md5($query));
        $data = CocoDB::cacheGet($hash);
       	if (@$data) {
			$table = explode(" ",strtolower($query));
			$table = join("",array_splice($table,array_search("from",$table)+1,1));
			if (!@CocoDB::$noCacheTABLES || (CocoDB::$noCacheTABLES && !in_array($table,CocoDB::$noCacheTABLES))){
				$result = json_decode($data,true);
       			return $result;	
			}
       	}
    }
	$records = array();
	$result  = mysql_query($query) or die("MySQL Error: ". htmlspecialchars(mysql_error()) . "\n");
	while ($record = mysql_fetch_assoc($result)) {
		$records[] = $record;
	}

	if (class_exists("CocoDB") && isset(CocoDB::$storeDebugData) && CocoDB::$storeDebugData) {
        $debugData = [
            "hora" => date("Y-m-d H:i:s", time()) . " " . microtime(),
            "query" => $query,
            "records" => $records,
            "time" => microtime(true) - $microtime
        ];

        CocoDB::$debugData[] = $debugData;
    }
    
	if (is_resource($result)) { mysql_free_result($result); }
    
    if (class_exists("CocoDB") && isset(CocoDB::$force_redis) && CocoDB::$force_redis){
		$table = explode(" ",strtolower($query));
		$table = join("",array_splice($table,array_search("from",$table)+1,1));
		if (!@CocoDB::$noCacheTABLES || (CocoDB::$noCacheTABLES && !in_array($table,CocoDB::$noCacheTABLES))){
        	CocoDB::cacheSet($hash,json_encode($records));
		}
    }
    
	return $records;
}
