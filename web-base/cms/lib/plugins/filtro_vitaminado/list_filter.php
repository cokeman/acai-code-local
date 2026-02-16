<?
if (!@$_SESSION) session_start();
global $schema, $tableName;
$advanced_filter = [[]];

if(@$_REQUEST['advanced_filter']) {
    $advanced_filter = $_REQUEST['advanced_filter'];
    $_SESSION['__advanced_filter'][$tableName] = $advanced_filter;
}

if(@$_REQUEST['resetSearch'] == 'Reiniciar') {
    if(@$_SESSION['__advanced_filter'][$tableName])
        unset($_SESSION['__advanced_filter'][$tableName]);
    $advanced_filter = [[]];
}

if(@$_SESSION['__advanced_filter'][$tableName]) {
    $advanced_filter = $_SESSION['__advanced_filter'][$tableName];
}


$campos_a_saltar = ['dragSortOrder'];
$where_chains = ['1'];

foreach ($advanced_filter as $key => $filter): 
    foreach (explode(",", $schema['listPageFields']) as $campo): 
        $campo = trim($campo);
        if(in_array($campo, $campos_a_saltar)) continue;
        
        // $valor_a_evaluar = str_replace("Seleccionar...", "", @$filter[$campo]['value']);
        $valor_a_evaluar = isset($filter[$campo])?$filter[$campo]['value']:'';
        if(isset($filter[$campo]) && $valor_a_evaluar !== '-_@ESTO_ES_UN_VALOR_IMPOSIBLE@_-') {
            if(!@$where_chains[$key]) $where_chains[$key] = '1';
            $prefix = '';
            $sufix = '';
            if($schema[$campo]['type'] == 'list') {
                if($filter[$campo]['operator'] == '~' && $schema[$campo]['listType'] !== 'pulldownMulti') {
                    $filter[$campo]['operator'] = '=';
                }
                if($schema[$campo]['listType'] == 'pulldownMulti') {
                    $prefix = '\t';
                    $sufix = '\t';
                }
            } else {
                if($valor_a_evaluar == '') continue;
            }
            switch ($filter[$campo]['operator']) {
                case '=': 
                    if($schema[$campo]['type'] == 'list' && $valor_a_evaluar == '') {
                        $where_chains[$key] .= " AND (`" . $campo . "` = '" . $prefix . $valor_a_evaluar . $sufix . "' OR `" . $campo . "` IS NULL)";
                    } else {
                        $where_chains[$key] .= " AND `" . $campo . "` = '" . $prefix . $valor_a_evaluar . $sufix . "'";
                    }
                break;
                case '!=': 
                    if($schema[$campo]['type'] == 'list' && $valor_a_evaluar == '') {
                        $where_chains[$key] .= " AND (`" . $campo . "` != '" . $prefix . $valor_a_evaluar . $sufix . "' AND `" . $campo . "` IS NOT NULL)";
                    } else {
                        $where_chains[$key] .= " AND `" . $campo . "` != '" . $prefix . $valor_a_evaluar . $sufix . "'";
                    }
                break;
                case '&gt;': 
                    $where_chains[$key] .= " AND `" . $campo . "` > '" . $prefix . $valor_a_evaluar . $sufix . "'";
                break;
                case '&lt;': 
                    $where_chains[$key] .= " AND `" . $campo . "` < '" . $prefix . $valor_a_evaluar . $sufix . "'";
                break;
                case '&gt;=': 
                    $where_chains[$key] .= " AND `" . $campo . "` >= '" . $prefix . $valor_a_evaluar . $sufix . "'";
                break;
                case '&lt;=': 
                    $where_chains[$key] .= " AND `" . $campo . "` <= '" . $prefix . $valor_a_evaluar . $sufix . "'";
                break;
                case '~':
                default:
                    $where_chains[$key] .= " AND (`" . $campo . "` LIKE '%" . $prefix . $valor_a_evaluar . $sufix . "%' || `" . $campo . "` LIKE '%" . strtoupper($prefix) . strtoupper($valor_a_evaluar) . strtoupper($sufix) . "%' || `" . $campo . "` LIKE '%" . strtolower($prefix) . strtolower($valor_a_evaluar) . strtolower($sufix) . "%')";
                    break;
            }
        }
    endforeach;
endforeach;

$final_where = '';
foreach ($where_chains as $key => $where) {
    if($key == 0)
        $final_where = '(' . $where . ')';
    else
        $final_where .= ' OR (' . $where . ')';
}

$GLOBALS['advanced_filter'] = $advanced_filter;

if($final_where !== '' && $final_where !== '(1)'){
    $var['whereKeywordAndUser'] = @$var['whereKeywordAndUser'] ? "(".$var['whereKeywordAndUser'].") AND (".$final_where.")" : $final_where;
}
// echo "<pre style='display: block; position: absolute; z-index: 100000; bottom: 0px;'>"; var_dump($var['whereKeywordAndUser']); echo "</pre>";
// echo "<pre style='display: block;position: absolute;z-index: 100000;bottom: 0px;max-height: 100%;overflow: scroll;'>"; var_dump($final_where); echo "</pre>";
// echo "<pre style='display: block;position: absolute;z-index: 100000;bottom: 0px;max-height: 100%;overflow: scroll;resize:both;'>"; var_dump($schema); echo "</pre>";
// echo "<pre style='display: block;position: absolute;z-index: 100000;bottom: 0px;max-height: 100%;overflow: scroll;resize:both;'>"; var_dump($advanced_filter); echo "</pre>";