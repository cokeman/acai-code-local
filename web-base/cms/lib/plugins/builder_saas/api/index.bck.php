<?
$domains_allowed = ['https://cms.cocosolution.com'];
$tokens_allowed = ['0d775395420d7f6a3f231a86a00e998c'];

require_once __DIR__."/../../../init.php";
require_once __DIR__."/../../../database_functions.php";
require_once __DIR__."/../../../admin_functions.php";
$current_user = getCurrentUserAndLogin();
$GLOBALS["CURRENT_USER"] = $current_user;
connectToMySQL();


function handleError($code = 400, $userMessage = 'Error.', $adminMessage = 'Error.', $response) {
    http_response_code($code);
    $response['error']['userMessage'] = $userMessage;
    $response['error']['adminMessage'] = $adminMessage;
    die(json_encode($response));
}

// Allow from any origin
$allow_all = false;
if (isset($_SERVER['HTTP_ORIGIN'])) {
    // should do a check here to match $_SERVER['HTTP_ORIGIN'] to a
    if (in_array($_SERVER['HTTP_ORIGIN'], $domains_allowed) || $allow_all) {
        // whitelist of safe domains
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    } else {
        handleError(400, 'No permitido.', 'No está autorizado.'.$_SERVER['HTTP_ORIGIN'], $response);
        die();
    }
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS");
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    http_response_code(200);
    die();
}

$response = [
    'data' => ''
];

$request = json_decode(file_get_contents("php://input"), true)?:[];
$request = array_merge($request, $_REQUEST);

if(!isset($request['token']) || !in_array($request['token'], $tokens_allowed)) {
    handleError(403, 'Acceso no autorizado.', 'Token inválido o no presente.', $response);
}

// Extructura de API:
// GET /api/tableName
// GET /api/tableName/num
// POST /api/tableName
// PATCH /api/tableName/num
// DELETE /api/tableName/num
require_once __DIR__ . '/../../../viewer_functions.php';

//require_once __DIR__ . '/../../../sesion.php';
//require_once __DIR__ . '/../../../funciones.php';

if(isset($request['tableName']))
    $tableName = $request['tableName'];

if(isset($request['num']))
    $num = $request['num'];

if (!isset($tableName) || $tableName === '') { handleError(404, 'La tabla solicitada no existe.', 'No existe la tabla: ' . $tableName, $response); }

try {
    
    $method = isset($request['method'])?$request['method']:$_SERVER['REQUEST_METHOD']?:'';
    $method = strtoupper($method);
    if (!in_array($method, ['GET', 'POST', 'PATCH', 'DELETE'])) { handleError(400, 'La acción solicitada no existe.', 'No existe el método.', $response); }

    // $tableName
    $where = isset($request['where'])?$request['where']:[];
    $options = isset($request['options'])?$request['options']:[];
    $order = isset($request['order'])?$request['order']:'';
    $offset = isset($request['offset'])?$request['offset']:0;
    $limit = isset($request['limit'])?$request['limit']:50;

    $records = isset($request['records'])?$request['records']:[];

    if(isset($num) && !isset($where['num'])) { $where['num'] = $num; }
    $functions = [];

    switch ($method) {
        case 'GET':
            
            // $response['data']['records'] = CocoDB::get($tableName, $where, $order, $limit);
            if(isset($num)) $where = 'num='.intval($num);
            if (!@$_REQUEST["ignoreSchema"]){
                
                $response['data'] = array_slice(dame_registros($tableName, $where, $order), $offset, $limit);
            }else{
                
                $whereAux = @$where ? " WHERE ".$where : ""; 
                $orderAux = @$order ? " ORDER BY ".$order : ""; 
                $limitAux = @$limit ? " LIMIT ".$limit : ""; 
                
                $response['data'] = mysql_query_fetch_all_assoc("SELECT * FROM ".$TABLE_PREFIX.$tableName.$whereAux.$orderAux.$limitAux);
                
            }
            break;
        case 'POST':
            $response['data'] = CocoDB::insertRecords($tableName, $records, $functions, $options);
            
            break;
        case 'PATCH':
            $response['data'] = CocoDB::updateRecords($tableName, $records, $where, $functions, $options);
            break;
        case 'DELETE':
            $response['data'] = CocoDB::deleteRecords($tableName, $where, $options);
            break;
    }
    if($response['data'] !== 0 && $response['data'] == $limit) {
        $response['nextPage'] = 'https://' . $_SERVER['HTTP_HOST'] . preg_replace('/&?offset=[0-9]+/', '', $_SERVER['REQUEST_URI']);
        if(strpos($response['nextPage'], '?') !== false)
            $response['nextPage'] .= '&';
        else
            $response['nextPage'] .= '?';
        $response['nextPage'] .= 'offset=' . ($offset + $limit);
    }
    if($offset - $limit >= 0) {
        $response['prevPage'] = 'https://' . $_SERVER['HTTP_HOST'] . preg_replace('/&?offset=[0-9]+/', '', $_SERVER['REQUEST_URI']);
        if(strpos($response['prevPage'], '?') !== false)
            $response['prevPage'] .= '&';
        else
            $response['prevPage'] .= '?';
        $response['prevPage'] .= 'offset=' . ($offset - $limit);
    }
} catch (Exception $e) {
    handleError(400, 'Lo sentimos, ha habido un problema con su petición, inténtelo de nuevo o contacto con un administrador.', 'Hubo una excepción:' . $e->getMessage(), $response);
}

function dame_registros($tabla,$where="",$order="",$limit=1000){

	list($configuracionRecords, $configuracionMetaData,$schema) = getRecords(array(
		'tableName'   =>  $tabla,
		'where'       =>  $where,
		'allowSearch' =>  0,
		'orderBy'     =>  $order,
		'limit'       =>  $limit,
	));

	/*
    EXPERIMENTAL - EN DESARROLLO
    */
	foreach ($configuracionRecords as $index => $record) {
		foreach ($record as $key => $value) {
			switch (@$schema[$key]["type"]) {
				case "list":
					if (@$schema[$key]["optionsType"] == "table") {
						$nums = array_filter(explode("\t", $value));

						if (@$nums) {
							$newSchema = loadSchema($schema[$key]["optionsTablename"]);
							if (@$newSchema["dragSortOrder"]) $order = "dragSortOrder DESC";
							if (@$newSchema["siblingOrder"]) $order = "siblingOrder ASC";
							if (!@$order) $order = "num DESC";
							$newRecord = dame_registros($schema[$key]["optionsTablename"], $schema[$key]["optionsValueField"]." IN (".join(",", $nums).")", $order);
							$configuracionRecords[$index][$key."_bd"] = $newRecord;
						}else {
							$configuracionRecords[$index][$key."_bd"] = array();
						}
					}else{
						$configuracionRecords[$index][$key."_bd"] = array();
					}
					break;
				case "multitext":
					$result = (@$value) ? json_decode(t($record, $key), true) : array();
					$configuracionRecords[$index][$key."_bd"] = $result;
					break;
				case "textfield":
					if (@$schema[$key]["tipoTags"]) {
						$tags = array_filter(explode(",", $value));
						$configuracionRecords[$index][$key."_bd"] = $tags;
					}
					break;
				default:
					break;
			}
		}
		$configuracionRecords[$index]["breadcrumbField"] = @$schema["breadcrumbField"];
		// Si es parentNum ponemos valores por defecto
		if (@$configuracionRecords[$index]["breadcrumbField"] == "parentNum") {
			$configuracionRecords[$index]["optionsTablename"] = $configuracionRecords[$index]["tableName"];
			$configuracionRecords[$index]["optionsValueField"] = "num";
		} else if (@$configuracionRecords[$index]["breadcrumbField"]) {
			// Si no es parentNum, ponemos los que dicte el schema
			$configuracionRecords[$index]["optionsTablename"] = @$schema[$schema["breadcrumbField"]]["optionsTablename"];
			$configuracionRecords[$index]["optionsValueField"] =  @$schema[$schema["breadcrumbField"]]["optionsValueField"];
		}

		// Para el campo principal (para la generación de enlaces y el breadcrumb)
		if (@$record["name"]) {
			$configuracionRecords[$index]["mainFieldBreadcrumb"] = t($record, "name");
		}
		else if (@$record["title"]) {
			$configuracionRecords[$index]["mainFieldBreadcrumb"] = t($record, "title");
		}
		else if (@$record["titulo"]) {
			$configuracionRecords[$index]["mainFieldBreadcrumb"] = t($record, "titulo");
		}
		else if (@$record["nombre"]) {
			$configuracionRecords[$index]["mainFieldBreadcrumb"] = t($record, "nombre");
		}
		else {
			foreach ($schema as $key => $value):
			if (@$value["type"] == "textfield" && $key != "enlace") {
				$configuracionRecords[$index]["mainFieldBreadcrumb"] = t($record, $key);
				break;
			}
			endforeach;
		}
	}
	if ($configuracionRecords) return $configuracionRecords; else return array();
}
die(json_encode($response));