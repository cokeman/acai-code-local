<?
$schemaPlugins = array();

function getSchemaPlugins($force = false){
    global $schemaPlugins; 
    $pluginsDir = $GLOBALS["APP"]["pluginsdir"];

    if (!$force && @$schemaPlugins) return $schemaPlugins;

    foreach (scandir($pluginsDir) as $dirname) {
        $fullPath = $pluginsDir."/".$dirname; 
        if (is_dir($fullPath)){
            $schemaFile = $fullPath."/schema.ini.php";
            if (file_exists($schemaFile)){
                $schemaPlugins[$dirname] = loadINI($schemaFile);
            }
			
            $customSchemaFile = $fullPath."/custom-schema.ini.php";
            if (file_exists($customSchemaFile)){
                $customSchema = loadINI($customSchemaFile);
				$exclude = array("version", "name", "description");
				foreach ($exclude as $e):
					unset($customSchema[$e]);
				endforeach;
                foreach($schemaPlugins[$dirname] as $key => $value):
                    if (is_array($value)){
                        foreach($value as $kv => $vv):
                            if (isset($customSchema[$key][$kv]) && @$customSchema[$key][$kv] != "" && @$customSchema[$key][$kv] != $vv) $schemaPlugins[$dirname][$key][$kv] = $customSchema[$key][$kv];
                        endforeach;
                    }else{
                        if (isset($customSchema[$key]) && @$customSchema[$key] != "" && @$customSchema[$key] != $value) $schemaPlugins[$dirname][$key] = $customSchema[$key];
                    }
                endforeach;
                
            }
        }
    }
    return $schemaPlugins;
}
function installSQL($path){
    if (file_exists($path."/install.sql")){
        try{
            sqlImport($path."/install.sql");
            return 1;
        }catch(Exception $e){
            return 0;
        }
    }else{
        return 1;
    }

}
function installSchemas($path){
    if (file_exists($path."/schemaPresets")){
        foreach (scandir($path."/schemaPresets") as $dirname) {
            if (strpos($dirname,".ini")){
                addTablePlugin($path."/schemaPresets",$dirname);
            }
        }
        rename($path."/schemaPresets/",$path."/schemaPresets.bck/");
        return 0;
    }else{
        return 1;
    }
}
function showInstall($path){
    global $TABLE_PREFIX,$menu,$action,$schema;
    //
    $result = 0;
    showHeader();
?>
<header class="navbar navbar-default navbar-fixed-top">
    <h4 class="titular"><?php echo $schema['menuName'] ?> - <span class="balance">0</span></h4>
</header>

<div id="page-content">
    <h3>Instalando el plugin</h3>
    <? $borro = true; ?> 
    <? if (installSQL($path)){ if ($borro) rename($path."/install.sql",$path."/install.sql.bck"); ?><p>Instalando tablas necesarias</p><?}else{?><p class="text-danger">Error instalando la base de datos</p><?}?>
    <? if (installSchemas($path)){ if ($borro) rename($path."/schemaPresets",$path."/schemaPresets.bck"); ?><p>Instalando Schemas</p><?}else{?><p class="text-danger">Error instalando los esquemas</p><?}?>
    <? if ($result==2){?>
    <p>&nbsp;</p>
    <a href="">Recarga para cargar el plugin</a>
    <? }?>
</div>
<?
    showFooter();
    exit;    
}

function addPlugins($type,&$var, $fieldtype = null){  
    global $CURRENT_USER,$menu,$action;
    $schemaPlugins = getSchemaPlugins();
    if (!@$schemaPlugins) return;
    $pluginsInserted = array();
    
    $allowedFields = array("db_type", "db_type_config");
    
    foreach($schemaPlugins as $key => $value):
    if (!@$CURRENT_USER["isAdmin"] && $value["adminOnly"]) continue;
    if (!@$value["enabled"]) continue;
    if (@$value["checkPoints"]){
        foreach($allowedFields as $allowedField):
            $value["checkPoints"][$allowedField] = "";
        endforeach;
        
        foreach($value["checkPoints"] as $checkPoint => $controller):
        if ($checkPoint==$type && isset($value["checkPoints"][$type])){
            
            $enabled = true;
            $pluginsInserted[] = $key;
            // DEFINIMOS LAS VARIABLES DE OPCIONES PARA EL PLUGIN 
            $options = array();
            $options["templatePath"] = str_replace($GLOBALS["PROGRAM_DIR"]."/","",$GLOBALS["APP"]["pluginsdir"])."/".$key;
            $options["pluginName"] = $key;
            if (@$value["newFieldType"]){
                $options["fieldType"] = $value["newFieldType"];
                if (@$fieldtype){
                    foreach($options["fieldType"] as $kvar => $vvar):
                        if (isset($var["field"][$kvar])) $options["fieldType"][$kvar] = $var["field"][$kvar];
                    endforeach;
                }
            }

            // SI EXISTEN FILTROS DE MENU
            if (@$value["menuFilter"][$type]){
                $sepMenu = array_filter(explode(",",$value["menuFilter"][$type]));
                if (@$sepMenu && !in_array(@$menu,@$sepMenu)){
                    $enabled = false;
                }
            }
            // SI EXISTEN FILTROS DE ACTION
            if (@$value["actionFilter"][$type]){
                $sepAction = array_filter(explode(",",$value["actionFilter"][$type]));
                if (@$sepAction && !in_array(@$action,@$sepAction)){
                    $enabled = false;
                }
            }
            
            // SI EXISTEN FILTROS DE TIPO DE CAMPO
            if (@$value["newFieldType"]["type"] && @$fieldtype && @$value["newFieldType"]["type"] != $fieldtype) {
                $enabled = false;
            }
            
            // SI EXISTE UN ARCHIVO DE INSTALACION LO EJECUTAMOS
            if (file_exists($GLOBALS["APP"]["pluginsdir"]."/".$key."/install.sql") || file_exists($GLOBALS["APP"]["pluginsdir"]."/".$key."/schemaPresets")){
                $enabled = false;
                showInstall($GLOBALS["APP"]["pluginsdir"]."/".$key);
            }
            // SI NOS PIDE EL TYPE DE CAMPO Y EXISTE EN EL SCHEMA DEL PLUGIN LO AÑADIMOS
            if ($enabled && @$type=="db_type" && @$value["newFieldType"]["type"]){
                array_push($var, array("value" => $value["newFieldType"]["type"], "name" => $value["newFieldType"]["label"]));
                continue;
            }
            
            if ($enabled && @$type=="db_type_config" && @$value["newFieldType"]){
                ?>                
                <fieldset data-plugin="<?=$options["pluginName"];?>" data-fieldtype="<?=$value["newFieldType"]["type"];?>" class="fieldPluginContainer">
                    <legend><b>Plugin Options</b></legend>
                    
                    <? foreach ($value["newFieldType"] as $keyFT => $vFT):?>
                        <? if (@$keyFT=="label" || @$keyFT=="type") continue;?>
                        <? if (@$var[$keyFT]) $vFT = @$var[$keyFT];?>
                        <div class="<?=$keyFT;?>">
                            <div class="label"><?=$keyFT;?></div>
                            <input type="text" name="plugin[<?=$options["pluginName"];?>][<?=$keyFT;?>]" value="<?=$vFT;?>">
                        </div>
                        
                    <? endforeach;?>
                </fieldset>
                <?
                continue;
            }
            // SI SE PERMITE EJECUTAMOS EL PLUGIN
            if ($enabled && @$value["checkPoints"][$type]) {
                include $GLOBALS["APP"]["pluginsdir"]."/".$key."/".@$value["checkPoints"][$type];
            }
        }
        endforeach;
    }

    endforeach;
    return $pluginsInserted;
}

function addTablePlugin($path,$file) {
    global $TABLE_PREFIX, $APP;
    //rename($path."/".$file,$GLOBALS["APP"]["datadir"]."schemaPresets/".$file);
    
    $presetTableName = str_replace(".ini.php","",$file);

    // error checking
    $errors = "";
    $tableNameErrors  = getTablenameErrors($presetTableName);

    // create new schema data
    $schemaPresetDir       = $path."/";
    $newSchema             = loadSchema($presetTableName, $schemaPresetDir) or die("Couldn't load preset schema");

    $newSchema['menuName']  = $presetTableName; // change menu name
    $newSchema['menuOrder'] = time(); // use time to sort to bottom


    // create mysql table
    // (this isn't required but done here so we catch get mysql errors creating the table)
    // createMissingSchemaTablesAndFields() creates if this doesn't.
    $tableNameWithPrefix = $TABLE_PREFIX . $presetTableName;
    $result = mysql_query("CREATE TABLE `".mysql_real_escape_string($tableNameWithPrefix)."` (
                                          num int(10) unsigned NOT NULL auto_increment,
                                          PRIMARY KEY (num)
                                        ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
    if (!$result) {
        print "Error creating MySQL table.\n\nMySQL error was: ". htmlspecialchars(mysql_error()) . "\n";
        exit;
    }


    // save new schema
    saveSchema($presetTableName, $newSchema);

    // Create schema table and fields in MySQL
    createMissingSchemaTablesAndFields();
    clearAlertsAndNotices(); // don't display alerts about adding new fields

}
/************************/

function sqlImport($file)
{

    $delimiter = ';';
    $file = fopen($file, 'r');
    $isFirstRow = true;
    $isMultiLineComment = false;
    $sql = '';

    while (!feof($file)) {

        $row = fgets($file);

        // remove BOM for utf-8 encoded file
        if ($isFirstRow) {
            $row = preg_replace('/^\x{EF}\x{BB}\x{BF}/', '', $row);
            $isFirstRow = false;
        }

        // 1. ignore empty string and comment row
        if (trim($row) == '' || preg_match('/^\s*(#|--\s)/sUi', $row)) {
            continue;
        }

        // 2. clear comments
        $row = trim(clearSQL($row, $isMultiLineComment));

        // 3. parse delimiter row
        if (preg_match('/^DELIMITER\s+[^ ]+/sUi', $row)) {
            $delimiter = preg_replace('/^DELIMITER\s+([^ ]+)$/sUi', '$1', $row);
            continue;
        }

        // 4. separate sql queries by delimiter
        $offset = 0;
        while (strpos($row, $delimiter, $offset) !== false) {
            $delimiterOffset = strpos($row, $delimiter, $offset);
            if (isQuoted($delimiterOffset, $row)) {
                $offset = $delimiterOffset + strlen($delimiter);
            } else {
                $sql = trim($sql . ' ' . trim(substr($row, 0, $delimiterOffset)));
                query($sql);

                $row = substr($row, $delimiterOffset + strlen($delimiter));
                $offset = 0;
                $sql = '';
            }
        }
        $sql = trim($sql . ' ' . $row);
    }
    if (strlen($sql) > 0) {
        query($row);
    }

    fclose($file);
}

/**
 * Remove comments from sql
 *
 * @param string sql
 * @param boolean is multicomment line
 * @return string
 */
function clearSQL($sql, &$isMultiComment)
{
    if ($isMultiComment) {
        if (preg_match('#\*/#sUi', $sql)) {
            $sql = preg_replace('#^.*\*/\s*#sUi', '', $sql);
            $isMultiComment = false;
        } else {
            $sql = '';
        }
        if(trim($sql) == ''){
            return $sql;
        }
    }

    $offset = 0;
    while (preg_match('{--\s|#|/\*[^!]}sUi', $sql, $matched, PREG_OFFSET_CAPTURE, $offset)) {
        list($comment, $foundOn) = $matched[0];
        if (isQuoted($foundOn, $sql)) {
            $offset = $foundOn + strlen($comment);
        } else {
            if (substr($comment, 0, 2) == '/*') {
                $closedOn = strpos($sql, '*/', $foundOn);
                if ($closedOn !== false) {
                    $sql = substr($sql, 0, $foundOn) . substr($sql, $closedOn + 2);
                } else {
                    $sql = substr($sql, 0, $foundOn);
                    $isMultiComment = true;
                }
            } else {
                $sql = substr($sql, 0, $foundOn);
                break;
            }
        }
    }
    return $sql;
}

/**
 * Check if "offset" position is quoted
 *
 * @param int $offset
 * @param string $text
 * @return boolean
 */
function isQuoted($offset, $text)
{
    if ($offset > strlen($text))
        $offset = strlen($text);

    $isQuoted = false;
    for ($i = 0; $i < $offset; $i++) {
        if ($text[$i] == "'")
            $isQuoted = !$isQuoted;
        if ($text[$i] == "\\" && $isQuoted)
            $i++;
    }
    return $isQuoted;
}

function query($sql)
{
    //echo '#<strong>SQL CODE TO RUN:</strong><br>' . htmlspecialchars($sql) . ';<br><br>';
    if (!$query = mysql_query($sql)) {
        throw new Exception("Cannot execute request to the database {$sql}: " . mysql_error());
    }
}

?>