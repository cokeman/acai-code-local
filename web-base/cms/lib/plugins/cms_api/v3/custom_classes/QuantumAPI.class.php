<?
if (!defined('QUANTUM_PLAN_GRATUITO')) {
    define('QUANTUM_PLAN_GRATUITO', 4);
}

class QuantumAPI {
    static function getChat($request){
        global $TABLE_PREFIX;
        $originalRequest=$request;
        
        if (!@$request["userHash"] || !@$request["domain"]){
            throw new ApiError("Domain and userHash needed");
        }
        
        // Construir condiciones de búsqueda base
        if (@$request["chatNum"]){
            $baseConditions = "num = '".mysql_real_escape_string($request["chatNum"])."' and uuid = '".mysql_real_escape_string($request["userHash"])."' and website = '".mysql_real_escape_string($request["domain"])."' and trashed = 0";
        } else {
            $baseConditions = "uuid = '".mysql_real_escape_string($request["userHash"])."' and website = '".mysql_real_escape_string($request["domain"])."' and trashed = 0";
        }

        // Si viene channelNum, agregarlo a las condiciones
        if (@$request["channelNum"] && @$request["realChannelNum"]){
            $baseConditions .= " and (channel_num = '".mysql_real_escape_string($request["channelNum"])."' or real_channel_num = '".mysql_real_escape_string($request["realChannelNum"])."')";
        } else if (@$request["realChannelNum"]){
            $baseConditions .= " and real_channel_num = '".mysql_real_escape_string($request["realChannelNum"])."'";
        } else if (@$request["channelNum"]){
            $baseConditions .= " and channel_num = '".mysql_real_escape_string($request["channelNum"])."'";
        }
        
        // Buscar chat existente con las condiciones base
        $chatResult = CocoDB::get("cocochats", $baseConditions, "num desc", 1, ["prefix" => "","ignoreSchemas" => true]);
        
        if (!@$chatResult && !@$request["createIfNotExists"]){
            API::success([]);
        }
        
        //file_put_contents(__DIR__."/../chatResult.txt","[".date("Y-m-d H:i:s")."] ".json_encode($request)."\n",FILE_APPEND);
        
        if (!$chatResult){
            /*$data = [
                "createdDate" => date("Y-m-d H:i:s"),
                "uuid" => $request["userHash"],
                "lastConnectionDate" => date("Y-m-d H:i:s"),
                "website" => $request["domain"],
                "channel" => @$request["channel"] ?: "web",
                "channel_num" => @$request["channelNum"] ?: NULL,
                "real_channel_num" => @$request["realChannelNum"] ?: NULL,
                "status" => "Resuelta",
                "valoracion" => rand(1,5)
            ];
            
            if (@$request["chatType"]){
                $data["chatType"] = $request["chatType"];
            }
            
            $chatResultCreation = CocoDB::insertRecords("cocochats",$data,null,["return_last_id" => 1,"prefix" => "","ignoreSchema" => true]);
            
            if (@$chatResultCreation){
                $chatResult = CocoDB::get("cocochats","num=".$chatResultCreation,"num desc",1,["prefix" => "","ignoreSchemas" => true]);
            }else{
                throw new ApiError("Error on chat creation");
            }*/


            // Usar INSERT ... ON DUPLICATE KEY UPDATE para evitar race conditions
            $createdDate = date("Y-m-d H:i:s");
            $uuid = mysql_real_escape_string($request["userHash"]);
            $website = mysql_real_escape_string($request["domain"]);
            $channel = mysql_real_escape_string(@$request["channel"] ?: "web");
            $channelNum = @$request["channelNum"] ? "'".mysql_real_escape_string($request["channelNum"])."'" : "NULL";
            $realChannelNum = @$request["realChannelNum"] ? "'".mysql_real_escape_string($request["realChannelNum"])."'" : "NULL";
            $chatType = @$request["chatType"] ? "'".mysql_real_escape_string($request["chatType"])."'" : "'default'";
            $valoracion = rand(1,5);
            
            $sql = "INSERT INTO cocochats (createdDate, uuid, lastConnectionDate, website, channel, channel_num, real_channel_num, status, valoracion, chatType, trashed) 
                    VALUES ('$createdDate', '$uuid', '$createdDate', '$website', '$channel', $channelNum, $realChannelNum, 'Resuelta', $valoracion, $chatType, 0)
                    ON DUPLICATE KEY UPDATE 
                        lastConnectionDate = VALUES(lastConnectionDate),
                        trashed = 0,
                        real_channel_num = COALESCE(VALUES(real_channel_num), real_channel_num)";
            
            $ok = mysql_query($sql);
            $insertId = intval(mysql_insert_id());

            if (!$ok) { throw new ApiError("Error on chat creation SQL: ".mysql_error()); }
            
            // Buscar el chat (ya sea recién creado o existente)
            $chatResult = CocoDB::get("cocochats", $baseConditions, "num desc", 1, ["prefix" => "", "ignoreSchemas" => true]);

            if (!$chatResult && $insertId > 0) {
                $chatResult = CocoDB::get("cocochats", "num=".$insertId, "num desc", 1, ["prefix" => "","ignoreSchemas" => true]);
            }
            
            if (!$chatResult) {
                //throw new ApiError("Error on chat creation.");
                throw new ApiError("Error on chat creation. baseConditions=".$baseConditions." | sql_error=".mysql_error());
            }
        }else{
            $dataToUpdate = [
                "lastConnectionDate" => date("Y-m-d H:i:s")
            ];
            // Ajuste por la nemiedad de que antes no se guardaba el channel_num y real_channel_num y ahora si
            if (@$request["realChannelNum"]){
                $dataToUpdate["real_channel_num"] = $request["realChannelNum"];
            }

            $chatInfoUpdate = CocoDB::updateRecords("cocochats",$dataToUpdate,"num=".$chatResult[0]["num"],null,["prefix" => "","ignoreSchema" => true]);
        }

        
        
        if (@$chatResult[0]["num"]){
            self::markAsRead(["chat_num" => $chatResult[0]["num"],"userHash" => @$request["userAgent"] ?: @$request["userHash"]]);
        }
        
        if (@$chatResult[0]["chatInfo"]){
            $chatResult[0]["chatInfo"] = json_decode($chatResult[0]["chatInfo"],true);
        }
        
        if (!@$chatResult[0]["chatInfo"]){
            $chatResult[0]["chatInfo"] = [];
        }
        
        //Cambio la consulta para traer solo notas cuyo campo deleted_at sea null o '' es decir sin eliminar(Adriel)
        //$chatResult[0]["chatInfo"]["internalNotes"] = CocoDB::get("notas_internas","chat_num=".$chatResult[0]["num"],"createdDate desc",null,["ignoreSchemas" => true]);
        $chatResult[0]["chatInfo"]["internalNotes"] = CocoDB::get("notas_internas","chat_num=".$chatResult[0]["num"]." AND (deleted_at IS NULL OR deleted_at = '')","createdDate desc",null,["ignoreSchemas" => true]);
        
        if (!@$chatResult[0]["chatInfo"]["internalNotes"]){
            $chatResult[0]["chatInfo"]["internalNotes"] = [];
        }
        
        if (@$request["withMessages"]){
            if (@$request["prevMessagesFrom"]){
                $chatResult[0]["messages"] = array_reverse(CocoDB::get("cocochats_mensajes","chat_id=".$chatResult[0]["num"],"num desc",["offset" => @$request["prevMessagesFrom"],"limit" => 50],["prefix" => "","ignoreSchemas" => true]));
            } else {
                // Otherwise, fetch the latest messages
                $chatResult[0]["messages"] = array_reverse(CocoDB::get("cocochats_mensajes","chat_id=".$chatResult[0]["num"],"num desc",50,["prefix" => "","ignoreSchemas" => true]));
            }
        }
        
        if (@$request["withContactDetails"]){
            $chatResult[0]["contact"] = @CocoDB::get("contactos","num=".intval($chatResult[0]["contactNum"]),"num desc",1,["ignoreSchemas" => true])[0];
            if (@$chatResult[0]["contact"]){
                $chatResult[0]["chats"] = @CocoDB::get("cocochats u","u.contactNum=".intval($chatResult[0]["contact"]["num"]),"u.lastConnectionDate desc",null,[
                    "prefix" => "",
                    "ignoreSchemas" => true,
                    "aggregates" => [
                        "(SELECT ch.createdDate FROM cocochats_mensajes ch WHERE ch.chat_id = u.num ORDER BY ch.num desc LIMIT 1) as lastMessageDate"
                    ]
                ]);
            }
        }
        
        if (@$request["chatInfo"]){
            $chatInfoUpdate = CocoDB::updateRecords("cocochats",["chatInfo" => $request["chatInfo"]],"num=".$chatResult[0]["num"],null,["prefix" => "","ignoreSchema" => true]);
        }
        
        $chatResult[0]["_debug_request"] = $originalRequest;
        API::success($chatResult);
    }

    static function getChartsData($request){
        if (!@$request["domain"]){
            throw new ApiError("domain needed");
        } 
        if (!@$request["type"] || !@$request["dates"] || !@$request["chat_conf_num"]){
            throw new ApiError("type,dates,char_conf_num needed");
        } 
        
        if($request["language"]){
            $_REQUEST["idioma"] = $request["language"];
        };

        switch(@$request["type"]){
            case "chartCategory":
                // Validar que las fechas estén presentes
                if (!isset($request["dates"]) || !is_array($request["dates"]) || count($request["dates"]) < 2) {
                    $result = [ "success" => false, "error" => "Fechas no proporcionadas correctamente", "series" => [], "labels" => [] ];
                    break;
                }
                
                $startDate = $request["dates"][0];
                $endDate = $request["dates"][1];
                
                // Validar formato de fechas
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                    $result = [ "success" => false, "error" => "Formato de fecha inválido. Use YYYY-MM-DD", "series" => [], "labels" => [] ];
                    break;
                }
                
                try {
                    $chatbot = CocoDB::get("chat_configuraciones", "num=" . @$request["chat_conf_num"], "num desc", 1)[0];
                    $categories = CocoDB::get("categorizacion_conv", "chat_conf_num=" . $request["chat_conf_num"], "title asc", null, ["ignoreSchemas" => true]);

                    $labels = array_map(function($r) {
                        return $r["title"];
                    }, $categories);

                    // Optimizado: 1 query con GROUP BY en lugar de N+1 queries
                    $countsResult = mysql_query_fetch_all_assoc("
                        SELECT category, COUNT(*) as total
                        FROM cocochats
                        WHERE website = '" . mysql_real_escape_string($chatbot["dominio"]) . "'
                        AND lastConnectionDate >= '" . mysql_real_escape_string($startDate . " 00:00:00") . "'
                        AND lastConnectionDate <= '" . mysql_real_escape_string($endDate . " 23:59:59") . "'
                        GROUP BY category
                    ");

                    // Mapear resultados por category
                    $countsMap = [];
                    $othersCount = 0;
                    foreach ($countsResult as $row) {
                        if ($row["category"] === null || $row["category"] === '' || $row["category"] === '0') {
                            $othersCount += intval($row["total"]);
                        } else {
                            $countsMap[intval($row["category"])] = intval($row["total"]);
                        }
                    }

                    // Construir valores usando el mapa
                    $values = array_map(function($r) use ($countsMap) {
                        return $countsMap[intval($r["num"])] ?? 0;
                    }, $categories);

                    // Agregar categoría "Otros"
                    $labels[] = "Otros";
                    $values[] = $othersCount;

                    $result = [
                        "success" => true,
                        "series" => array_map(function($r) { return intval($r); }, $values),
                        "labels" => $labels
                    ];

                } catch (Exception $e) {
                    $result = [ "success" => false, "error" => "Error en la consulta: " . $e->getMessage(), "series" => [], "labels" => [] ];
                }
                break;

           case "chartIA":
                // Validar que las fechas estén presentes
                if (!isset($request["dates"]) || !is_array($request["dates"]) || count($request["dates"]) < 2) {
                    $result = [ "success" => false, "error" => "Fechas no proporcionadas correctamente", "series" => [], "labels" => [] ];
                    break;
                }
                
                $startDate = $request["dates"][0];
                $endDate = $request["dates"][1];
                
                // Validar formato de fechas
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                    $result = [ "success" => false, "error" => "Formato de fecha inválido. Use YYYY-MM-DD", "series" => [], "labels" => [] ];
                    break;
                }
                
                try {
                    $chatbot = CocoDB::get("chat_configuraciones", "num=" . @$request["chat_conf_num"], "num desc", 1)[0];
                    
                    if (@$chatbot) {
                        // CORRECCIÓN AQUI: Agregadas las horas de inicio y fin para capturar todo el día
                        $chats = mysql_query_fetch_all_assoc("
                            SELECT num 
                            FROM cocochats 
                            WHERE website = '" . mysql_real_escape_string($chatbot["dominio"]) . "' 
                            AND lastConnectionDate >= '" . mysql_real_escape_string($startDate . " 00:00:00") . "' 
                            AND lastConnectionDate <= '" . mysql_real_escape_string($endDate . " 23:59:59") . "'
                        ");
                        
                        // Si no hay chats creados en ese rango, devolvemos vacío (esto es correcto)
                        if (!@$chats || empty($chats)) {
                            $result = [
                                "success" => true,
                                "series" => [
                                    [ "name" => t_var('Agentes'), "data" => [] ],
                                    [ "name" => t_var('IA'), "data" => [] ]
                                ],
                                "labels" => []
                            ];
                            break;
                        }
                        
                        $chatIds = array_map(function($r) { return intval($r["num"]); }, $chats);
                        
                        // Consulta principal de mensajes (Esta ya tenía las horas, pero depende de $chatIds)
                        $res = mysql_query_fetch_all_assoc("
                            SELECT 
                                DATE_FORMAT(createdDate, '%Y-%m-%d') AS day,
                                SUM(CASE WHEN user = 'IA' THEN 1 ELSE 0 END) AS ia_messages,
                                SUM(CASE WHEN user != 'IA' THEN 1 ELSE 0 END) AS human_messages
                            FROM 
                                cocochats_mensajes
                            WHERE 
                                origin = 'assistant' AND
                                chat_id IN (" . implode(",", $chatIds) . ") AND
                                createdDate >= '" . mysql_real_escape_string($startDate . " 00:00:00") . "' AND
                                createdDate <= '" . mysql_real_escape_string($endDate . " 23:59:59") . "'
                            GROUP BY 
                                day
                            ORDER BY 
                                day ASC
                        ");
                        
                        if (empty($res)) {
                            $result = [
                                "success" => true,
                                "series" => [
                                    [ "name" => t_var('Agentes'), "data" => [] ],
                                    [ "name" => t_var('IA'), "data" => [] ]
                                ],
                                "labels" => []
                            ];
                        } else {
                            $result = [
                                "success" => true,
                                "series" => [
                                    [
                                        "name" => t_var('Agentes'),
                                        "data" => array_map(function($r) { return intval(@$r["human_messages"]); }, $res)
                                    ],
                                    [
                                        "name" => t_var('IA'),
                                        "data" => array_map(function($r) { return intval(@$r["ia_messages"]); }, $res)
                                    ]
                                ],
                                "labels" => array_map(function($r) { return $r["day"]; }, $res)
                            ];
                        }
                        
                    } else {
                        $result = [ "success" => false, "error" => "Chatbot no encontrado", "series" => [], "labels" => [] ];
                    }
                    
                } catch (Exception $e) {
                    $result = [ "success" => false, "error" => "Error en la consulta: " . $e->getMessage(), "series" => [], "labels" => [] ];
                }
                break;

            case "chartAgents":
                // Validar que las fechas estén presentes
                if (!isset($request["dates"]) || !is_array($request["dates"]) || count($request["dates"]) < 2) {
                    $result = [ "success" => false, "error" => "Fechas no proporcionadas correctamente", "series" => [], "labels" => [], "nums" => [] ];
                    break;
                }
                
                $startDate = $request["dates"][0];
                $endDate = $request["dates"][1];
                
                // Validar formato de fechas
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                    $result = [ "success" => false, "error" => "Formato de fecha inválido. Use YYYY-MM-DD", "series" => [], "labels" => [], "nums" => [] ];
                    break;
                }
                
                try {
                    $chatbot = CocoDB::get("chat_configuraciones", "num=" . @$request["chat_conf_num"], "num desc", 1)[0];
                    
                    if (!@$chatbot) {
                        $result = [ "success" => false, "error" => "Chatbot no encontrado", "series" => [], "labels" => [], "nums" => [] ];
                        break;
                    }
                    
                    $agents = CocoDB::get("chatbots", "chat_conf_num=" . $request["chat_conf_num"], "num desc", null, ["ignoreSchemas" => true]);
                    $realAgents = [];
                    
                    // Obtener todos los nombres de usuarios de una vez (evita N+1)
                    $agentUserIds = array_filter(array_map(function($a) { return intval($a["usuario"]); }, $agents));
                    $nombresMap = [];
                    if (!empty($agentUserIds)) {
                        $nombresResult = mysql_query_fetch_all_assoc("SELECT num, nombre FROM cms_usuarios WHERE num IN (" . implode(',', $agentUserIds) . ")");
                        foreach ($nombresResult as $row) {
                            $nombresMap[intval($row["num"])] = $row["nombre"];
                        }
                    }

                    // Obtener totales de todos los agentes en UNA sola consulta
                    $totalesMap = [];
                    if (!empty($agentUserIds)) {
                        // Construir CASE para extraer el usuario_id del patrón 'agent-XXX_...'
                        $totalesResult = mysql_query_fetch_all_assoc("
                            SELECT 
                                CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(m.user, 'agent-', -1), '_', 1) AS UNSIGNED) as agent_user_id,
                                COUNT(DISTINCT c.num) as total 
                            FROM cocochats c
                            INNER JOIN cocochats_mensajes m ON c.num = m.chat_id
                            WHERE m.user REGEXP '^agent-[0-9]+_'
                            AND c.website = '" . mysql_real_escape_string($chatbot["dominio"]) . "'
                            AND m.createdDate >= '" . mysql_real_escape_string($startDate . " 00:00:00") . "'
                            AND m.createdDate <= '" . mysql_real_escape_string($endDate . " 23:59:59") . "'
                            GROUP BY agent_user_id
                        ");
                        foreach ($totalesResult as $row) {
                            $totalesMap[intval($row["agent_user_id"])] = intval($row["total"]);
                        }
                    }

                    $index = 0;
                    foreach($agents as $agent) {
                        if(empty($agent["num"])) continue;
                        
                        $usuarioId = intval($agent["usuario"]);
                        $totalMessages = isset($totalesMap[$usuarioId]) ? $totalesMap[$usuarioId] : 0;
                        $nombre = isset($nombresMap[$usuarioId]) ? $nombresMap[$usuarioId] : "Usuario eliminado (#" . $agent["num"] . ")";
                        
                        $realAgents[$index] = [
                            "total" => $totalMessages, 
                            "nombre" => $nombre,
                            "num" => intval($agent["num"])
                        ];
                        $index++;
                    }
                    
                    $result = [
                        "success" => true,
                        "series" => array_map(function($r) { return intval($r["total"]); }, $realAgents),
                        "labels" => array_map(function($r) { return $r["nombre"]; }, $realAgents),
                        "nums" => array_map(function($r) { return intval($r["num"]); }, $realAgents),
                    ];
                    
                } catch (Exception $e) {
                    $result = [ "success" => false, "error" => "Error en la consulta: " . $e->getMessage(), "series" => [], "labels" => [], "nums" => [] ];
                }
                break;

            case "chartRatings":
                if (!isset($request["dates"]) || !is_array($request["dates"]) || count($request["dates"]) < 2) {
                    $result = [ "success" => false, "error" => "Fechas no proporcionadas correctamente", "series" => [], "series_auto" => [], "series_manual" => [], "labels" => [], "nums" => [], "total_chats_auto" => [], "total_chats_manual" => [], "total_chats_combined" => [], "total_stars_auto" => [], "total_stars_manual" => [], "total_stars_combined" => [] ];
                    break;
                }
                
                $startDate = $request["dates"][0];
                $endDate = $request["dates"][1];
                
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                    $result = [ "success" => false, "error" => "Formato de fecha inválido. Use YYYY-MM-DD", "series" => [], "series_auto" => [], "series_manual" => [], "labels" => [], "nums" => [], "total_chats_auto" => [], "total_chats_manual" => [], "total_chats_combined" => [], "total_stars_auto" => [], "total_stars_manual" => [], "total_stars_combined" => [] ];
                    break;
                }
                
                try {
                    if($request["language"]){
                        $_REQUEST["idioma"] = $request["language"];
                    }
                    
                    $agents = CocoDB::get("chatbots", "chat_conf_num=" . $request["chat_conf_num"], "num desc", null, ["ignoreSchemas" => true]);
                    $realAgents = [];
                    
                    $index = 0;
                    foreach($agents as $agent) {
                        // Obtener todos los nombres de usuarios de una vez
                        $agentUserIds = array_filter(array_map(function($a) { return intval($a["usuario"]); }, $agents));
                        $nombresMap = [];
                        if (!empty($agentUserIds)) {
                            $nombresResult = mysql_query_fetch_all_assoc("SELECT num, nombre FROM cms_usuarios WHERE num IN (" . implode(',', $agentUserIds) . ")");
                            foreach ($nombresResult as $row) {
                                $nombresMap[intval($row["num"])] = $row["nombre"];
                            }
                        }

                        // Obtener todas las estadísticas de ratings en UNA consulta
                        $ratingsMap = [];
                        if (!empty($agentUserIds)) {
                            $ratingsResult = mysql_query_fetch_all_assoc("
                                SELECT 
                                    CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(cm.user, 'agent-', -1), '_', 1) AS UNSIGNED) as agent_user_id,
                                    cr.type,
                                    AVG(cr.rating) as media,
                                    COUNT(*) as total_chats,
                                    SUM(cr.rating) as total_stars
                                FROM cocochats_ratings cr
                                INNER JOIN cocochats_mensajes cm ON cr.chat_num = cm.chat_id
                                WHERE cm.user REGEXP '^agent-[0-9]+_'
                                AND cr.createdDate >= '" . mysql_real_escape_string($startDate . " 00:00:00") . "' 
                                AND cr.createdDate <= '" . mysql_real_escape_string($endDate . " 23:59:59") . "'
                                GROUP BY agent_user_id, cr.type
                            ");
                            foreach ($ratingsResult as $row) {
                                $agentId = intval($row["agent_user_id"]);
                                $type = $row["type"];
                                if (!isset($ratingsMap[$agentId])) {
                                    $ratingsMap[$agentId] = ["auto" => null, "manual" => null];
                                }
                                $ratingsMap[$agentId][$type] = [
                                    "media" => floatval($row["media"]),
                                    "total_chats" => intval($row["total_chats"]),
                                    "total_stars" => intval($row["total_stars"])
                                ];
                            }
                        }

                        $index = 0;
                        foreach($agents as $agent) {
                            if(empty($agent["num"]) || empty($agent["usuario"])) continue;
                            
                            $usuarioId = intval($agent["usuario"]);
                            
                            // Obtener datos del mapa (o valores por defecto)
                            $autoData = isset($ratingsMap[$usuarioId]["auto"]) ? $ratingsMap[$usuarioId]["auto"] : null;
                            $manualData = isset($ratingsMap[$usuarioId]["manual"]) ? $ratingsMap[$usuarioId]["manual"] : null;
                            
                            $mediaAuto = $autoData ? $autoData["media"] : 0;
                            $totalChatsAuto = $autoData ? $autoData["total_chats"] : 0;
                            $totalStarsAuto = $autoData ? $autoData["total_stars"] : 0;
                            
                            $mediaManual = $manualData ? $manualData["media"] : 0;
                            $totalChatsManual = $manualData ? $manualData["total_chats"] : 0;
                            $totalStarsManual = $manualData ? $manualData["total_stars"] : 0;
                            
                            // Calcular estadísticas combinadas
                            $totalChatsCombinados = $totalChatsAuto + $totalChatsManual;
                            $totalStarsCombinados = $totalStarsAuto + $totalStarsManual;
                            
                            // Calcular media combinada
                            $mediaCombinada = 0;
                            if($mediaAuto > 0 && $mediaManual > 0) {
                                $mediaCombinada = ($mediaAuto + $mediaManual) / 2;
                            } elseif($mediaAuto > 0) {
                                $mediaCombinada = $mediaAuto;
                            } elseif($mediaManual > 0) {
                                $mediaCombinada = $mediaManual;
                            }
                            
                            // Nombre del mapa
                            $nombre = isset($nombresMap[$usuarioId]) ? $nombresMap[$usuarioId] : "Usuario eliminado (#" . $agent["usuario"] . ")";
                            
                            $realAgents[$index] = [
                                "media" => $mediaCombinada,
                                "media_auto" => $mediaAuto,
                                "media_manual" => $mediaManual,
                                "total_chats_auto" => $totalChatsAuto,
                                "total_chats_manual" => $totalChatsManual,
                                "total_chats_combined" => $totalChatsCombinados,
                                "total_stars_auto" => $totalStarsAuto,
                                "total_stars_manual" => $totalStarsManual,
                                "total_stars_combined" => $totalStarsCombinados,
                                "nombre" => $nombre,
                                "num" => intval($agent["usuario"])
                            ];
                            $index++;
                        }
                    }
                    
                    // Agregar "Sin agente asignado" al final
                    $sinAgenteResult = mysql_query_fetch_all_assoc("
                        SELECT AVG(valoracion) as media 
                        FROM cocochats 
                        WHERE (assignedToAgent IS NULL or assignedToAgent = '' or assignedToAgent = 0) 
                        AND lastConnectionDate >= '" . mysql_real_escape_string($startDate . " 00:00:00") . "' 
                        AND lastConnectionDate <= '" . mysql_real_escape_string($endDate . " 23:59:59") . "'
                    ");
                    
                    $mediaSinAgente = 0;
                    if(!empty($sinAgenteResult) && isset($sinAgenteResult[0]["media"]) && $sinAgenteResult[0]["media"] !== null) {
                        $mediaSinAgente = floatval($sinAgenteResult[0]["media"]);
                    }
                    
                    $realAgents[] = [
                        "num" => 0,
                        "nombre" => t_var("Sin agente asignado"),
                        "media" => $mediaSinAgente,
                        "media_auto" => 0,
                        "media_manual" => 0,
                        "total_chats_auto" => 0,
                        "total_chats_manual" => 0,
                        "total_chats_combined" => 0,
                        "total_stars_auto" => 0,
                        "total_stars_manual" => 0,
                        "total_stars_combined" => 0
                    ];
                    
                    $result = [
                        "success" => true,
                        "series" => array_map(function($r) { return round($r["media"], 1); }, $realAgents),
                        "series_auto" => array_map(function($r) { return round($r["media_auto"], 1); }, $realAgents),
                        "series_manual" => array_map(function($r) { return round($r["media_manual"], 1); }, $realAgents),
                        "labels" => array_map(function($r) { return $r["nombre"]; }, $realAgents),
                        "nums" => array_map(function($r) { return intval($r["num"]); }, $realAgents),
                        "total_chats_auto" => array_map(function($r) { return intval($r["total_chats_auto"]); }, $realAgents),
                        "total_chats_manual" => array_map(function($r) { return intval($r["total_chats_manual"]); }, $realAgents),
                        "total_chats_combined" => array_map(function($r) { return intval($r["total_chats_combined"]); }, $realAgents),
                        "total_stars_auto" => array_map(function($r) { return intval($r["total_stars_auto"]); }, $realAgents),
                        "total_stars_manual" => array_map(function($r) { return intval($r["total_stars_manual"]); }, $realAgents),
                        "total_stars_combined" => array_map(function($r) { return intval($r["total_stars_combined"]); }, $realAgents)
                    ];
                    
                } catch (Exception $e) {
                    $result = [ "success" => false, "error" => "Error en la consulta: " . $e->getMessage(), "series" => [], "series_auto" => [], "series_manual" => [], "labels" => [], "nums" => [], "total_chats_auto" => [], "total_chats_manual" => [], "total_chats_combined" => [], "total_stars_auto" => [], "total_stars_manual" => [], "total_stars_combined" => [] ];
                }
                break;

            case "chartChannels":
                // -------------------------------------------------------------
                //  LOGICA ACTUALIZADA: Chats Activos Reales
                // -------------------------------------------------------------
                if (!isset($request["dates"]) || !is_array($request["dates"]) || count($request["dates"]) < 2) {
                    $result = ["success" => false, "error" => "Fechas no proporcionadas correctamente", "series" => [], "labels" => [], "total" => 0]; break;
                }
                
                $startDate = $request["dates"][0];
                $endDate = $request["dates"][1];
                
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                    $result = ["success" => false, "error" => "Formato de fecha inválido. Use YYYY-MM-DD", "series" => [], "labels" => [], "total" => 0]; break;
                }
                
                try {
                    $chatbot = CocoDB::get("chat_configuraciones", "num=" . @$request["chat_conf_num"], "num desc", 1)[0];
                    if (!@$chatbot) { $result = ["success" => false, "error" => "Chatbot no encontrado"]; break; }
                    
                    // --- FILTROS COMUNES (Mensajes válidos, dominio y fecha en mensajes) ---
                    $msgTypeFilter = "AND m.message_type IN ('text', 'text_reopened', 'file', 'error')";
                    $domainFilter = "AND (c.website = '" . mysql_real_escape_string($chatbot["dominio"]) . "' OR c.website IS NULL)";
                    $dateFilter = "AND m.createdDate >= '" . mysql_real_escape_string($startDate . " 00:00:00") . "' 
                                   AND m.createdDate <= '" . mysql_real_escape_string($endDate . " 23:59:59") . "'";

                    // 1. Obtener lista completa de canales desde la tabla principal (para las series)
                    $channels = mysql_query_fetch_all_assoc("
                        SELECT DISTINCT channel FROM cocochats c
                        WHERE c.website = '" . mysql_real_escape_string($chatbot["dominio"]) . "' 
                    ");
                    $channels = array_map(function($r) { return $r["channel"]; }, $channels);
                    $channels = array_filter($channels, function($r) { return $r != ""; });
                    $channels[] = "otros";
                    
                    $resultFinal = ["series" => []];
                    
                    // 2. Calcular TOTAL de chats únicos activos (usando LEFT JOIN)
                    $totalQuery = "
                        SELECT COUNT(DISTINCT m.chat_id) as total 
                        FROM cocochats_mensajes m
                        LEFT JOIN cocochats c ON m.chat_id = c.num
                        WHERE 1=1 
                        $dateFilter
                        $domainFilter
                        $msgTypeFilter
                    ";
                    $totalResult = mysql_query_fetch_all_assoc($totalQuery);
                    $total = (!empty($totalResult) && isset($totalResult[0]["total"])) ? intval($totalResult[0]["total"]) : 0;
                    
                    // 3. Procesar por Canal
                    foreach($channels as $channel) {
                        if ($channel == "otros") {
                            $whereChannel = "AND (c.channel IS NULL OR c.channel = '')";
                        } else {
                            $whereChannel = "AND c.channel = '" . mysql_real_escape_string($channel) . "'";
                        }
                        
                        // Contar chats únicos activos por día y canal
                        $query = "
                            SELECT COUNT(DISTINCT m.chat_id) as total, DATE_FORMAT(m.createdDate, '%Y-%m-%d') as day
                            FROM cocochats_mensajes m
                            LEFT JOIN cocochats c ON m.chat_id = c.num
                            WHERE 1=1
                            $whereChannel
                            $dateFilter
                            $domainFilter
                            $msgTypeFilter
                            GROUP BY day
                            ORDER BY day ASC
                        ";
                        $result = mysql_query_fetch_all_assoc($query);
                        
                        // Rellenar días vacíos con 0
                        $result_a = [];
                        $currentDate = $startDate;
                        while ($currentDate <= $endDate) {
                            $filteredResult = array_filter($result, function ($r) use ($currentDate) { return $r["day"] == $currentDate; });
                            $filteredResult = array_values($filteredResult);
                            
                            $val = !empty($filteredResult) ? intval($filteredResult[0]["total"]) : 0;
                            $result_a[] = ["total" => $val, "day" => $currentDate];
                            
                            $currentDate = date("Y-m-d", strtotime($currentDate . " +1 day"));
                        }
                        
                        $resultFinal["labels"] = array_map(function($r) { return $r["day"]; }, $result_a);
                        $resultFinal["series"][] = ["name" => $channel, "data" => array_map(function($r) { return $r["total"]; }, $result_a)];
                    }
                    
                    $result = ["success" => true, "series" => $resultFinal["series"], "labels" => $resultFinal["labels"], "total" => $total];
                    
                } catch (Exception $e) {
                    $result = ["success" => false, "error" => "Error: " . $e->getMessage(), "series" => [], "labels" => [], "total" => 0];
                }
                break;

            case "chartContacts":
                // Validar que las fechas estén presentes
                if (!isset($request["dates"]) || !is_array($request["dates"]) || count($request["dates"]) < 2) {
                    $result = [ "success" => false, "error" => "Fechas no proporcionadas correctamente", "series" => [], "labels" => [] ];
                    break;
                }
                
                $startDate = $request["dates"][0];
                $endDate = $request["dates"][1];
                
                // Validar formato de fechas
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                    $result = [ "success" => false, "error" => "Formato de fecha inválido. Use YYYY-MM-DD", "series" => [], "labels" => [] ];
                    break;
                }
                
                try {
                    // Consulta principal con las fechas proporcionadas
                    $result = mysql_query_fetch_all_assoc("
                        SELECT 
                            COUNT(*) as total, 
                            DATE_FORMAT(createdDate, '%Y-%m-%d') as day
                        FROM cms_contactos 
                        WHERE createdDate >= '" . mysql_real_escape_string($startDate . " 00:00:00") . "' 
                        AND createdDate <= '" . mysql_real_escape_string($endDate . " 23:59:59") . "'
                        AND chat_conf_num = " . intval($request["chat_conf_num"]) . "
                        AND email != 'upanel@quantumasis.com'
                        GROUP BY day
                        ORDER BY day ASC
                    ");
                    
                    // Crear array con todos los días del rango
                    $result_a = [];
                    $currentDate = $startDate;
                    
                    while ($currentDate <= $endDate) {
                        $filteredResult = array_filter($result, function ($r) use ($currentDate) {
                            return $r["day"] == $currentDate;
                        });
                        
                        $filteredResult = array_values($filteredResult);
                        
                        if (!empty($filteredResult)) {
                            $result_a[] = [
                                "total" => intval($filteredResult[0]["total"]),
                                "day" => $filteredResult[0]["day"]
                            ];
                        } else {
                            $result_a[] = [
                                "total" => 0,
                                "day" => $currentDate
                            ];
                        }
                        
                        $currentDate = date("Y-m-d", strtotime($currentDate . " +1 day"));
                    }
                    
                    $values = array_map(function($r) { return $r["total"]; }, $result_a);
                    $days = array_map(function($r) { return $r["day"]; }, $result_a);
                    
                    $result = [
                        "success" => true,
                        "series" => [
                            [
                                "name" => 'Contactos',
                                "data" => $values,
                            ]
                        ],
                        "labels" => $days
                    ];
                    
                } catch (Exception $e) {
                    $result = [ "success" => false, "error" => "Error en la consulta: " . $e->getMessage(), "series" => [], "labels" => [] ];
                }
                break;

            case "chartMessages":
                // -------------------------------------------------------------
                //  LOGICA ACTUALIZADA: Volumen de Mensajes Reales
                // -------------------------------------------------------------
                if (!isset($request["dates"]) || !is_array($request["dates"]) || count($request["dates"]) < 2) {
                    $result = ["success" => false, "error" => "Fechas no proporcionadas correctamente", "series" => [], "labels" => [], "total" => 0]; break;
                }
                
                $startDate = $request["dates"][0];
                $endDate = $request["dates"][1];
                
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                    $result = ["success" => false, "error" => "Formato de fecha inválido. Use YYYY-MM-DD", "series" => [], "labels" => [], "total" => 0]; break;
                }
                
                try {
                    $chatbot = CocoDB::get("chat_configuraciones", "num=" . @$request["chat_conf_num"], "num desc", 1)[0];
                    if (!@$chatbot) { $result = ["success" => false, "error" => "Chatbot no encontrado"]; break; }
                    
                    // --- FILTROS COMUNES (Mismos que chartChannels para coherencia) ---
                    $msgTypeFilter = "AND m.message_type IN ('text', 'text_reopened', 'file', 'error')";
                    $domainFilter = "AND (c.website = '" . mysql_real_escape_string($chatbot["dominio"]) . "' OR c.website IS NULL)";
                    $dateFilter = "AND m.createdDate >= '" . mysql_real_escape_string($startDate . " 00:00:00") . "' 
                                   AND m.createdDate <= '" . mysql_real_escape_string($endDate . " 23:59:59") . "'";

                    // 1. Obtener canales (para series)
                    $channels = mysql_query_fetch_all_assoc("
                        SELECT DISTINCT channel FROM cocochats c WHERE c.website = '" . mysql_real_escape_string($chatbot["dominio"]) . "'
                    ");
                    $channels = array_map(function($r) { return $r["channel"]; }, $channels);
                    $channels = array_filter($channels, function($r) { return $r != ""; });
                    $channels[] = "otros";
                    
                    $resultFinal = ["series" => []];
                    
                    // 2. Calcular TOTAL de mensajes (m.num)
                    $totalQuery = "
                        SELECT COUNT(m.num) as total
                        FROM cocochats_mensajes m
                        LEFT JOIN cocochats c ON m.chat_id = c.num
                        WHERE 1=1
                        $dateFilter
                        $domainFilter
                        $msgTypeFilter
                    ";
                    $totalResult = mysql_query_fetch_all_assoc($totalQuery);
                    $total = (!empty($totalResult) && isset($totalResult[0]["total"])) ? intval($totalResult[0]["total"]) : 0;
                    
                    // 3. Procesar por Canal
                    foreach($channels as $channel) {
                        if ($channel == "otros") {
                            $whereChannel = "AND (c.channel IS NULL OR c.channel = '')";
                        } else {
                            $whereChannel = "AND c.channel = '" . mysql_real_escape_string($channel) . "'";
                        }
                        
                        // Contar mensajes totales (m.num) por día y canal
                        $query = "
                            SELECT COUNT(m.num) as total, DATE_FORMAT(m.createdDate, '%Y-%m-%d') as day
                            FROM cocochats_mensajes m
                            LEFT JOIN cocochats c ON m.chat_id = c.num
                            WHERE 1=1
                            $whereChannel
                            $dateFilter
                            $domainFilter
                            $msgTypeFilter
                            GROUP BY day
                            ORDER BY day ASC
                        ";
                        $result = mysql_query_fetch_all_assoc($query);
                        
                        // Rellenar días vacíos
                        $result_a = [];
                        $currentDate = $startDate;
                        while ($currentDate <= $endDate) {
                            $filteredResult = array_filter($result, function ($r) use ($currentDate) { return $r["day"] == $currentDate; });
                            $filteredResult = array_values($filteredResult);
                            
                            $val = !empty($filteredResult) ? intval($filteredResult[0]["total"]) : 0;
                            $result_a[] = ["total" => $val, "day" => $currentDate];
                            
                            $currentDate = date("Y-m-d", strtotime($currentDate . " +1 day"));
                        }
                        
                        $resultFinal["labels"] = array_map(function($r) { return $r["day"]; }, $result_a);
                        $resultFinal["series"][] = ["name" => $channel, "data" => array_map(function($r) { return $r["total"]; }, $result_a)];
                    }
                    
                    $result = ["success" => true, "series" => $resultFinal["series"], "labels" => $resultFinal["labels"], "total" => $total];
                    
                } catch (Exception $e) {
                    $result = ["success" => false, "error" => "Error: " . $e->getMessage(), "series" => [], "labels" => [], "total" => 0];
                }
                break;
                
            case "chartFirstResponse":
                // Validar fechas
                if (!isset($request["dates"]) || !is_array($request["dates"]) || count($request["dates"]) < 2) {
                    $result = [ "success" => true, "debug" => ["error" => "Fechas no válidas"], "series" => [], "labels" => [], "total" => 0, "avgMinutes" => 0 ];
                    break;
                }
            
                $startDate = $request["dates"][0];
                $endDate   = $request["dates"][1];
            
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                    $result = [ "success" => true, "debug" => ["error" => "Formato de fecha inválido"], "series" => [], "labels" => [], "total" => 0, "avgMinutes" => 0 ];
                    break;
                }
            
                try {
                    $chatbot = CocoDB::get("chat_configuraciones", "num=" . intval(@$request["chat_conf_num"]), "num desc", 1)[0];
                    if (!@$chatbot) {
                        $result = [ "success" => true, "debug" => ["error" => "Chatbot no encontrado"], "series" => [], "labels" => [], "total" => 0, "avgMinutes" => 0 ];
                        break;
                    }
            
                    // Obtener chats válidos con primera respuesta de agente, incluyendo canal
                    $validChats = mysql_query_fetch_all_assoc("
                        SELECT DISTINCT c.num, c.uuid, c.lastConnectionDate AS chat_created, c.channel, m_reply.createdDate AS first_reply_date, m_user.createdDate AS user_message_date
                        FROM cocochats c
                        JOIN cocochats_mensajes m_user ON m_user.chat_id = c.num
                        JOIN cocochats_mensajes m_reply ON m_reply.chat_id = c.num
                        WHERE c.uuid LIKE ('%_". @$request["chat_conf_num"] ."')
                          AND c.chatType = 'default'
                          AND c.trashed = 0
                          AND m_user.createdDate >= '" . mysql_real_escape_string($startDate . " 00:00:00") . "'
                          AND m_user.createdDate <= '" . mysql_real_escape_string($endDate . " 23:59:59") . "'
                          AND m_user.origin = 'user'
                          AND (m_user.user <> 'IA' AND m_user.user NOT LIKE 'agent-%')
                          AND (
                            -- Caso 1: Primer mensaje del chat (no es reapertura)
                            (m_user.message_type IS NULL OR m_user.message_type != 'text_reopened')
                            AND m_user.createdDate = (
                              SELECT MIN(mu.createdDate) FROM cocochats_mensajes mu
                              WHERE mu.chat_id = c.num AND mu.origin = 'user' AND (mu.user <> 'IA' AND mu.user NOT LIKE 'agent-%')
                            )
                            -- Caso 2: Mensaje de reapertura (text_reopened)
                            OR m_user.message_type = 'text_reopened'
                          )
                          AND m_reply.createdDate = (
                            SELECT MIN(mr.createdDate) FROM cocochats_mensajes mr
                            WHERE mr.chat_id = c.num AND mr.createdDate > m_user.createdDate
                          )
                          AND m_reply.user LIKE 'agent-%'
                        ORDER BY c.createdDate
                    ");
            
                    // Función para calcular solo tiempo laboral entre dos fechas
                    if (!function_exists('calculateWorkingMinutes')) {
                        function calculateWorkingMinutes($startDateTime, $endDateTime, $workingHours) {
                            $start = new DateTime($startDateTime);
                            $end = new DateTime($endDateTime);
                            
                            $totalMinutes = 0;
                            $current = clone $start;
                            
                            while ($current < $end) {
                                $dayOfWeek = strtolower($current->format('l')); // monday, tuesday, etc.
                                $dayConfig = null;
                                
                                // Buscar configuración del día
                                foreach ($workingHours['schedule'] as $day) {
                                    if ($day['internationalDay'] === $dayOfWeek && $day['active']) {
                                        $dayConfig = $day;
                                        break;
                                    }
                                }
                                
                                if ($dayConfig) {
                                    $dayStart = new DateTime($current->format('Y-m-d') . ' ' . $dayConfig['start']);
                                    $dayEnd = new DateTime($current->format('Y-m-d') . ' ' . $dayConfig['end']);
                                    
                                    // Solo contar tiempo dentro del horario laboral del día
                                    $dayStart = max($dayStart, $start);
                                    $dayEnd = min($dayEnd, $end);
                                    
                                    if ($dayStart < $dayEnd) {
                                        $totalMinutes += ($dayEnd->getTimestamp() - $dayStart->getTimestamp()) / 60;
                                    }
                                }
                                
                                $current->add(new DateInterval('P1D'));
                                $current->setTime(0, 0, 0);
                            }
                            
                            return $totalMinutes;
                        }
                    }
            
                    // Agrupar por canal y día
                    $channelData = [];
                    $debugInfo = [
                        "startDate" => $startDate,
                        "endDate" => $endDate,
                        "chatbot_found" => true,
                        "chatbot_dominio" => $chatbot["dominio"],
                        "total_chats_in_range" => count($validChats),
                        "valid_chats_sample" => array_slice($validChats, 0, 5),
                        "processing_details" => []
                    ];
                    
                    foreach ($validChats as $index => $chat) {
                        // Extraer chat_conf_num del uuid
                        $uuid = $chat['uuid'];
                        $chatConfNum = null;
                        if (strpos($uuid, '_') !== false) {
                            $parts = explode('_', $uuid);
                            $chatConfNum = end($parts);
                        }
                        
                        if (!$chatConfNum) {
                            $debugInfo["processing_details"][] = "Chat {$chat['num']}: No se pudo extraer chat_conf_num del uuid: $uuid";
                            continue;
                        }
                        
                        // Obtener configuración de horarios
                        $hoursConfigQuery = "
                            SELECT value FROM cocouser_configs 
                            WHERE chat_conf_num = " . intval($chatConfNum) . " 
                            AND `key` = 'info_hours'
                            LIMIT 1
                        ";
                        
                        $hoursConfig = mysql_query_fetch_all_assoc($hoursConfigQuery);
                        
                        if (empty($hoursConfig)) {
                            // Si no hay configuración, usar tiempo total
                            $workingMinutes = (strtotime($chat['first_reply_date']) - strtotime($chat['user_message_date'])) / 60;
                        } else {
                            $workingHours = json_decode($hoursConfig[0]['value'], true);
                            if (!$workingHours || !isset($workingHours['schedule'])) {
                                // Si configuración inválida, usar tiempo total
                                $workingMinutes = (strtotime($chat['first_reply_date']) - strtotime($chat['user_message_date'])) / 60;
                            } else {
                                // Calcular solo tiempo laboral desde el mensaje del usuario hasta la respuesta
                                $workingMinutes = calculateWorkingMinutes($chat['user_message_date'], $chat['first_reply_date'], $workingHours);
                            }
                        }
                        
                        if ($workingMinutes <= 0) {
                            $debugInfo["processing_details"][] = "Chat {$chat['num']}: Tiempo laboral <= 0, saltando";
                            continue;
                        }
                        
                        $channel = $chat['channel'] ?: 'web'; // Si no hay canal, asumir 'web'
                        $day = date('Y-m-d', strtotime($chat['user_message_date']));
                        
                        if (!isset($channelData[$channel])) {
                            $channelData[$channel] = [];
                        }
                        
                        if (!isset($channelData[$channel][$day])) {
                            $channelData[$channel][$day] = ['total' => 0, 'sum' => 0];
                        }
                        
                        $channelData[$channel][$day]['total']++;
                        $channelData[$channel][$day]['sum'] += $workingMinutes;
                    }
            
                    // Crear array con todos los días del rango
                    $allDays = [];
                    $currentDate = $startDate;
                    while ($currentDate <= $endDate) {
                        $allDays[] = $currentDate;
                        $currentDate = date("Y-m-d", strtotime($currentDate . " +1 day"));
                    }
            
                    // Definir todos los canales posibles para asegurar que aparezcan todos
                    $allChannels = ['web', 'whatsapp', 'instagram', 'messenger', 'telegram', 'custom'];
                    
                    // Crear series para cada canal (incluyendo los que tienen 0 chats)
                    $series = [];
                    $totalChats = 0;
                    $totalMinutes = 0;
                    
                    foreach ($allChannels as $channel) {
                        $channelValues = [];
                        $channelTotal = 0;
                        $channelSumMinutes = 0;
                        
                        foreach ($allDays as $day) {
                            if (isset($channelData[$channel][$day])) {
                                $avgMinutes = $channelData[$channel][$day]['sum'] / $channelData[$channel][$day]['total'];
                                $channelValues[] = round($avgMinutes, 2);
                                $channelTotal += $channelData[$channel][$day]['total'];
                                $channelSumMinutes += $channelData[$channel][$day]['sum'];
                            } else {
                                $channelValues[] = 0;
                            }
                        }
                        
                        // Incluir el canal aunque tenga 0 chats para mostrar estadísticas completas
                        $series[] = [
                            "name" => ucfirst($channel),
                            "data" => $channelValues,
                            "totalChats" => $channelTotal,
                            "avgMinutes" => $channelTotal > 0 ? round($channelSumMinutes / $channelTotal, 2) : 0
                        ];
                        
                        $totalChats += $channelTotal;
                        $totalMinutes += $channelSumMinutes;
                    }
            
                    $overallAvgMinutes = $totalChats > 0 ? round($totalMinutes / $totalChats, 2) : 0;
            
                    $result = [
                        "success" => true,
                        "debug" => $debugInfo,
                        "series" => $series,
                        "labels" => $allDays,
                        "total" => $totalChats,
                        "avgMinutes" => $overallAvgMinutes
                    ];
            
                } catch (Exception $e) {
                    $result = [
                        "success" => true,
                        "debug" => ["exception" => $e->getMessage()],
                        "series" => [],
                        "labels" => [],
                        "total" => 0,
                        "avgMinutes" => 0
                    ];
                }
                break;
        }

        API::success($result);
    }
    static function assignToAgent($request){
        global $TABLE_PREFIX;

        if (!@$request["domain"] || !@$request["chat_num"] || !@$request["userHash"]){
            throw new ApiError("chat_num and domain and userHash needed");
        } 

        $chatNum = @$request["chat_num"];
        $userHash = @$request["userHash"];
        $agentNum = @$request["agent_num"] ?: 0;

        $resultUpdate = CocoDB::updateRecords("cocochats",["assignedToAgent" => $agentNum],"num=".$chatNum,null,["prefix" => "","ignoreSchema" => true]);
        
        return $resultUpdate;

    }

    static function setErrorMessage($request){
        if (!@$request["mid"] || !@$request["error_message"]){
            throw new ApiError("mid and error_message and domain are needed");
        }

        $data = [
            "message_type" => "error",
            "error_message" => $request["error_message"],
            "updatedDate" => date("Y-m-d H:i:s")
        ];

        $updateSuccess = CocoDB::updateRecords("cocochats_mensajes",$data,"channel_id='".mysql_real_escape_string($request["mid"])."'",null,["prefix" => "","ignoreSchema" => true]);
        API::success(["result" => $updateSuccess]);
    }


    static function setErrorLastMessage($request){
        global $TABLE_PREFIX;

        if (!@$request["domain"] || !@$request["userHash"]){
            throw new ApiError("domain and userHash needed");
        } 

        $messageId = @$request["message_id"];

        if ($messageId){
            $data = [
                "message_type" => "error",
                "error_message" => $request["errorMessage"],
                "updatedDate" => date("Y-m-d H:i:s")
            ];

            $resultMessage = CocoDB::get("cocochats_mensajes","channel_id='".$messageId."'","num desc",1,["prefix" => "","ignoreSchemas" => true]);
            if (!@$resultMessage){
                throw new ApiError("message not found. Where channel_id='".$messageId."'");
            }
            $resultUpdateMessage = CocoDB::updateRecords("cocochats_mensajes",$data,"channel_id='".mysql_real_escape_string($messageId)."'",null,["prefix" => "","ignoreSchema" => true]);
            
        }else{
            $userHash = @$request["userHash"];

            $resultChat = CocoDB::get("cocochats","uuid = '".mysql_real_escape_string($userHash)."' and website = '".mysql_real_escape_string(@$request["domain"])."' and trashed = 0","num desc",1,["prefix" => "","ignoreSchemas" => true]);

            if (!@$resultChat){
                throw new ApiError("chat not found");
            }

            $chatNum = $resultChat[0]["num"];
            $resultMessage = CocoDB::get("cocochats_mensajes","chat_id = ".$chatNum." and origin = 'assistant'","num desc",1,["prefix" => "","ignoreSchemas" => true]);
            if (!@$resultMessage){
                throw new ApiError("message not found");
            }
            $resultUpdateMessage = CocoDB::updateRecords("cocochats_mensajes",["message_type" => "error"],"num=".$resultMessage[0]["num"],null,["prefix" => "","ignoreSchema" => true]);
            
        }

        
        return $resultUpdateMessage;
    }

    static function waitChat($request){
        global $TABLE_PREFIX;

        if (!@$request["domain"] || !@$request["chat_num"] || !@$request["userHash"]){
            throw new ApiError("chat_num and domain and userHash needed");
        } 

        $chatNum = @$request["chat_num"];
        $userHash = @$request["userHash"];

        $resultUpdate = CocoDB::updateRecords("cocochats",["status" => "En espera"],"num=".$chatNum,null,["prefix" => "","ignoreSchema" => true]);
        
        return $resultUpdate;

    }

    static function resolveChat($request){
        global $TABLE_PREFIX;

        if (!@$request["domain"] || !@$request["chat_num"] || !@$request["userHash"]){
            throw new ApiError("chat_num and domain and userHash needed");
        } 

        $chatNum = @$request["chat_num"];
        $userHash = @$request["userHash"];
        $category = @$request["category"] ?: 0;

        $resultUpdate = CocoDB::updateRecords("cocochats",["status" => "Resuelta","category" => $category],"num=".$chatNum,null,["prefix" => "","ignoreSchema" => true]);
        
        return $resultUpdate;

    }

    static function changeCategory($request){
        global $TABLE_PREFIX;

        if (!@$request["domain"] || !@$request["chat_num"] || !isset($request["category"])){
            throw new ApiError("chat_num and domain and userHash and category needed");
        } 

        $chatNum = @$request["chat_num"];
        $category = @$request["category"];
        $domain = @$request["domain"];

        $resultUpdate = CocoDB::updateRecords("cocochats",["category" => $category],"num=".$chatNum." and website = '".mysql_real_escape_string($domain)."'",null,["prefix" => "","ignoreSchema" => true]);
        
        return $resultUpdate;

    }

    static function getChatbotFromHash($request){
        if (!@$request["hash"]){
            throw new ApiError("hash needed".$request);
        }
        $chatbot = @CocoDB::get("chat_configuraciones","hash='".mysql_real_escape_string($request["hash"])."'","num desc",1)[0];

        if (@$chatbot){
            $requestedBotId = intval(@$request["bot_id"]);
            $bot = $requestedBotId > 0 ? @self::getBot(["botId" => $requestedBotId, "chat_conf_num" => $chatbot["num"]], true)["bot"] : null;
            if ($requestedBotId > 0 && !$bot) throw new ApiError("Bot not found: " . $requestedBotId);
            if ($bot) {
                if (@$bot["data"]) $chatbot["data"] = $bot["data"];
                $chatbot["bot_id"] = $bot["num"];
                $chatbot["bot_name"] = $bot["name"];
            }

            // GENERALES
            $neededKeys = ["widget_design","widget_settings","widget_forms","custom_domain","info_hours","info_general"];
            $chatConfNum = intval($chatbot["num"]);
            $keysSql = "'" . join("','", $neededKeys) . "'";
            $where = "chat_conf_num=" . $chatConfNum . " AND `key` IN (" . $keysSql . ")";
            $order = "num desc";
            
            $rawAdditionalData = @CocoDB::get("cocouser_configs", $where, $order, null, ["prefix" => "", "ignoreSchemas" => true]) ?: [];
            
            
            // BOT
            $neededKeys = ["start_messages","welcome_type","flow_data","help_messages"];
            $chatConfNum = intval($chatbot["num"]);
            $keysSql = "'" . join("','", $neededKeys) . "'";
            $where = "chat_conf_num=" . $chatConfNum . " AND `key` IN (" . $keysSql . ")";
            $order = "num desc";
            if ($bot) {
                $where .= " AND (bot_id=" . intval($bot["num"]) . " OR bot_id IS NULL OR bot_id = 0)";
                $order = "bot_id DESC, num desc";
            }
            $rawAdditionalData_bot = @CocoDB::get("cocouser_configs", $where . (!$bot ? " AND (bot_id IS NULL OR bot_id = 0)" : ""), $order, null, ["prefix" => "", "ignoreSchemas" => true]) ?: [];
            if ($bot) {
                $seen = []; $dedup = [];
                foreach($rawAdditionalData_bot as $row){ if (!isset($seen[$row["key"]])) { $dedup[] = $row; $seen[$row["key"]] = true; } }
                $rawAdditionalData_bot = $dedup;
            }

            $rawAdditionalDataResult = array_merge($rawAdditionalData,$rawAdditionalData_bot);
            
            $chatbot["aditional_data"] = array_map(function($r){ return ["key" => $r["key"],"value" => $r["value"]];},$rawAdditionalDataResult);
        }
        if (@$chatbot && @$request["userHash"]){
            
            $chatResult = CocoDB::get("cocochats","uuid = '".mysql_real_escape_string($request["userHash"])."' and website = '".$chatbot["dominio"]."' and trashed = 0","num desc",1,["prefix" => "","ignoreSchemas" => true]);
            if (@$request["withMessages"]){
                $chatbot["messages"] = array_reverse(CocoDB::get("cocochats_mensajes","chat_id=".$chatResult[0]["num"],"num desc",50,["prefix" => "","ignoreSchemas" => true]));
            }
            if (@$request["withContactDetails"]){
                $chatbot["contact"] = @CocoDB::get("contactos","num=".intval($chatResult[0]["contactNum"]),"num desc",1,["ignoreSchemas" => true])[0];                
            }
        } 
        if (@$chatbot){
            if (@$chatbot["data"]){
                $data = json_decode($chatbot["data"],true);
                $data = array_values(array_filter($data,function($r){ return $r["campo"] != "openai_token";}));
                $chatbot["data"] = json_encode($data);
                //die(var_dump($data));
            }
            unset($chatbot["hash"]);
            unset($chatbot["tableName"]);
            unset($chatbot["tokens"]);
            //unset($chatbot["num"]);
            unset($chatbot["plan"]);
            unset($chatbot["breadcrumbField"]);
            unset($chatbot["createdByUserNum"]);
            unset($chatbot["createdDate"]);
            unset($chatbot["dragSortOrder"]);
            unset($chatbot["instagram_id"]);
            unset($chatbot["mainFieldBreadcrumb"]);
            unset($chatbot["messenger_id"]);
            unset($chatbot["stripe_customer_id"]);
            unset($chatbot["updatedByUserNum"]);
            unset($chatbot["updatedDate"]);
            return $chatbot;
        }else{
            throw new ApiError("no encuentro el chatbot del dominio");
        }
    }

    static function isUserBlacklisted($request){
        if (!@$request["chat_conf_num"] || !@$request["userhash"]){
            throw new ApiError("chat_conf_num and userhash needed");
        }

        $blacklistEntry = CocoDB::get("bloqueos","chat_conf_num = ".intval($request["chat_conf_num"])." and userhash = '".mysql_real_escape_string($request["userhash"])."'",null,1,["ignoreSchemas" => true]);
        return !empty($blacklistEntry);
    }

    static function getChatbotFromDomain($domain){
        if (is_array($domain)){
            $domain = $domain['domain'];
        }
    
        if ($domain == intval($domain)){
            $chatbot = CocoDB::get("chat_configuraciones",[
                ["column" => "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telefono,')',''),'(',''),'+',''),' ',''),'-','')","value" => $domain,"operator" => "="],
            ],"num desc",1);
        }else{
            $chatbot = CocoDB::get("chat_configuraciones","REPLACE(dominio,' ','') = '".$domain."' OR instagram_id = '".$domain."' OR messenger_id = '".$domain."'","num desc",1);
        }
		
        if (!@$chatbot){
            throw new ApiError("no encuentro el chatbot del dominio");
        }
        return $chatbot;
    }

    static function toggleAutolearnVisibility($request){
        if (!@$request["chat_conf_num"] || !@$request["autolearn_num"] || !isset($request["is_visible"])){
            throw new ApiError("chat_conf_num, autolearn_num and is_visible needed");
        }
        $chatConfNum = intval($request["chat_conf_num"]);
        $requestedBotId = intval(@$request["bot_id"]);
        $bot = $requestedBotId > 0 ? @self::getBot(["botId" => $requestedBotId, "chat_conf_num" => $chatConfNum], true)["bot"] : null;
        $botWhere = ($requestedBotId > 0 && !empty($bot["num"])) ? " AND bot_id=" . intval($bot["num"]) : ($requestedBotId > 0 ? " AND 1=0" : " AND (bot_id IS NULL OR bot_id = 0)");
        
        $botWhere = ""; // Desactivamos el bott en autolearn por ahora
        $resultUpdate = CocoDB::updateRecords("cocochats_autolearn_embeddings",["is_visible" => intval($request["is_visible"])],"num=".intval($request["autolearn_num"])." and chat_conf_num=".$chatConfNum.$botWhere,null,["prefix" => "","ignoreSchema" => true]);

        $result = @CocoDB::get("cocochats_autolearn_embeddings","num=".intval($request["autolearn_num"])." and chat_conf_num=".$chatConfNum.$botWhere,null,1,["prefix" => "","ignoreSchemas" => true])[0];
        return $result;
    }
    static function getAutolearnByChatConfNum($request){
        if (!@$request["chat_conf_num"]){
            throw new ApiError("chat_conf_num needed");
        }
        $perPage = 10;
        $page = 1;
        $limit = null;

        if (@$request["perPage"]){
            $perPage = intval($request["perPage"]);
        }
        if (@$request["page"]){
            $page = intval($request["page"]);
        }
        if (@$perPage){
            $limit = ["page" => $page,"limit" => $perPage];
        }
        $chatConfNum = intval($request["chat_conf_num"]);
        $requestedBotId = intval(@$request["bot_id"]);
        $bot = $requestedBotId > 0 ? @self::getBot(["botId" => $requestedBotId, "chat_conf_num" => $chatConfNum], true)["bot"] : null;
        $botWhere = ($requestedBotId > 0 && !empty($bot["num"])) ? " AND bot_id=" . intval($bot["num"]) : ($requestedBotId > 0 ? " AND 1=0" : " AND (bot_id IS NULL OR bot_id = 0)");

        //$where = "chat_conf_num=".$chatConfNum." and type = 'QA_PAIR'".$botWhere;
        //desactivamos el filtro de bot hasta que lo hagamos individual
        $where = "chat_conf_num=".$chatConfNum." and type = 'QA_PAIR'";
        if (@$request["query"]){
            $where .= " and text LIKE '%".mysql_real_escape_string($request["query"])."%'";
        }
        if (@$request["is_visible"] !== null){
            $where .= " and is_visible = ".intval($request["is_visible"]);
        }
        $totalRecords = @mysql_query_fetch_all_assoc("select count(*) as total from cocochats_autolearn_embeddings where ".$where)[0]["total"];
        $embeddingResult = CocoDB::get("cocochats_autolearn_embeddings",$where,"num desc",$limit,["prefix" => "","ignoreSchemas" => true]);

        API::success([
            "totalRecords" => $totalRecords,
            "page" => $page,
            "perPage" => $perPage,
            "records" => $embeddingResult
        ]);

    }

    static function getEmbeddingsCSV($request){
        if (!@$request["domain"]){
            throw new ApiError("domain needed");
        }

        $chatbot = self::getChatbotFromDomain($request["domain"])[0];
        $chatConfNum = intval($chatbot["num"]);
        $requestedBotId = intval(@$request["bot_id"]);
        $bot = $requestedBotId > 0 ? @self::getBot(["botId" => $requestedBotId, "chat_conf_num" => $chatConfNum], true)["bot"] : null;
        $botWhere = ($requestedBotId > 0 && !empty($bot["num"])) ? " AND bot_id=" . intval($bot["num"]) : ($requestedBotId > 0 ? " AND 1=0" : " AND (bot_id IS NULL OR bot_id = 0)");

        $trainingData = CocoDB::get("cocochats_training_data","chat_conf_id = ".$chatConfNum." and enabled = 1".$botWhere,"num asc",null,["prefix" => "","ignoreSchemas" => true]);

        $numsTraining = array_map(function($r){
            return $r["num"];
        },$trainingData);

        // Multi-bot: no salir inmediatamente si no hay training, puede haber autolearn
        $trainingRecords = [];
        if (!empty($numsTraining)){
            $trainingRecords = CocoDB::get("cocochats_training_embeddings","training_data_num IN (".join(",",$numsTraining).")","num asc",null,["prefix" => "","ignoreSchemas" => true]);

            // Añadir source_type a training
            $trainingRecords = array_map(function($record) {
                $record['source_type'] = 'training';
                return array_intersect_key($record, array_flip(["num","text", "n_tokens", "embeddings", "source_type"]));
            }, $trainingRecords);
        }

        $ia_config_checks = CocoDB::get("cocouser_configs","chat_conf_num = ".$chatConfNum." and `key` = 'ia_config_checks'".$botWhere,"num desc",1,["prefix" => "","ignoreSchemas" => true]);
        $ia_config_checks = @json_decode($ia_config_checks[0]["value"],true);

        // 2. Obtener embeddings de autolearn (SOLO visible = 1)
        $autolearnRecords = CocoDB::get(
            "cocochats_autolearn_embeddings",
            //"chat_conf_num = ".$chatConfNum." AND is_visible = 1".$botWhere, <-- Desactivado temporalmente
            "chat_conf_num = ".$chatConfNum." AND is_visible = 1",
            "num asc",
            null,
            ["prefix" => "","ignoreSchemas" => true]
        );

        // Añadir source_type a autolearn
        if (@$autolearnRecords && @$ia_config_checks["autoLearnEnabled"]){
            $autolearnRecords = array_map(function($record) {
                $record['source_type'] = 'autolearn';
                return array_intersect_key($record, array_flip(["num","text", "n_tokens", "embeddings", "source_type"]));
            }, $autolearnRecords);
        }else{
            $autolearnRecords = [];
        }

        // 3. Combinar ambos resultados
        $records = array_merge($trainingRecords, $autolearnRecords);

        if (@$records){
            // Crear un buffer de salida para generar el CSV
            $outputBuffer = fopen('php://output', 'w');
            if (!$outputBuffer) {
                throw new ApiError("Unable to create CSV output buffer");
            }

            // Activar el buffer de salida
            ob_start();

            // Agregar encabezados del CSV si es necesario
            if (!empty($records)) {
                // Usar las claves del primer registro como encabezados
                fputcsv($outputBuffer, array_keys($records[0]));
            }

            // Recorrer los registros y agregarlos al CSV
            foreach ($records as $record) {
                fputcsv($outputBuffer, $record);
            }

            // Capturar el contenido del buffer
            fclose($outputBuffer);
            $csvData = ob_get_clean();

            // Devolver el string CSV generado
            API::success($csvData);
        }else{
            throw new ApiError("Error getting embeddings csv");
        }
    }

    static function getScrapedCSVToTrain($request){
        if (!@$request["chat_conf_id"]){
            throw new ApiError("chat_conf_id needed");
        } 
        $where = "chat_conf_id = ".intval(@$request["chat_conf_id"]);

        if (@$request["training_data_num"]){
            $where .= " and num=".intval(@$request["training_data_num"]);
        }
        $records = CocoDB::get("cocochats_training_data",$where,"num asc",null,["prefix" => "","ignoreSchemas" => true]);

        $records = array_map(function($record) {
            return array_intersect_key($record, array_flip(["num","title", "text"]));
        }, $records);

        if (@$records){
            // Crear un buffer de salida para generar el CSV
            $outputBuffer = fopen('php://output', 'w');
            if (!$outputBuffer) {
                throw new ApiError("Unable to create CSV output buffer");
            }

            // Activar el buffer de salida
            ob_start();

            // Agregar encabezados del CSV si es necesario
            if (!empty($records)) {
                // Usar las claves del primer registro como encabezados
                fputcsv($outputBuffer, array_keys($records[0]));
            }

            // Recorrer los registros y agregarlos al CSV
            foreach ($records as $record) {
                fputcsv($outputBuffer, $record);
            }

            // Capturar el contenido del buffer
            fclose($outputBuffer);
            $csvData = ob_get_clean();

            // Devolver el string CSV generado
            API::success($csvData);
        }else{
            throw new ApiError("Error on chat update");
        }
    }

    static function createEmbeddingsTrainData($request){
        if (!@$request["training_data_num"] || !@$request["data"]){
            throw new ApiError("domain and training_data_num and data needed");
        }

        $data = array_map(function($r) use($request){
            $r["training_data_num"] = intval($request["training_data_num"]);
            return $r;
        },$request["data"]);

        $embeddingResult = CocoDB::get("cocochats_training_embeddings","training_data_num=".intval($request["training_data_num"]),"num desc",1,["prefix" => "","ignoreSchemas" => true]);
        
        if ((!@$embeddingResult) || (@$embeddingResult && @$request["force"])){
            if (@$request["force"]){
                CocoDB::deleteRecords("cocochats_training_embeddings","training_data_num=".intval($request["training_data_num"]),["prefix" => "","ignoreSchema" => true]);
            }
            $embeddingTextCreation = CocoDB::insertRecords("cocochats_training_embeddings",$data,null,["return_last_id" => 1,"prefix" => "","ignoreSchema" => true]);
            if (@$embeddingTextCreation){
                $embeddingResult = CocoDB::get("cocochats_training_embeddings","training_data_num=".intval($request["training_data_num"]),"num desc",1,["prefix" => "","ignoreSchemas" => true]);
            }
        }

        API::success($embeddingResult);

    }

    static function getEmbeddingsAutoLearnByFirstMessageNum($request){
        if (!@$request["first_message_num"]){
            throw new ApiError("first_message_num needed");
        }

        $embeddingResult = CocoDB::get("cocochats_autolearn_embeddings","first_message_num=".intval($request["first_message_num"]),"num desc",null,["prefix" => "","ignoreSchemas" => true]);

        API::success($embeddingResult);

    }

    static function createEmbeddingsAutoLearn($request){
        if (!@$request["data"] || !@$request["domain"] || !@$request["chat_conf_num"]){
            throw new ApiError("domain and and data and chat_conf_num needed");
        }
        $data = $request["data"];
        $data = array_map(function($r) use($request){
            $r["is_visible"] = 0;
            $r["bot_id"] = @$request["bot_id"] ? intval($request["bot_id"]) : NULL;
            return $r;
        },$data);

        $first_message_num = @$data[0]["first_message_num"] ?: 0;

        $embeddingResult = CocoDB::get("cocochats_autolearn_embeddings","chat_conf_num=".intval($request["chat_conf_num"])." and chat_id=".intval($request["chat_id"])." and first_message_num=".intval($first_message_num),"num desc",1,["prefix" => "","ignoreSchemas" => true]);

        if ((!@$embeddingResult) || (@$embeddingResult && @$request["force"])){
            if (@$request["force"]){
                CocoDB::deleteRecords("cocochats_autolearn_embeddings","chat_conf_num=".intval($request["chat_conf_num"])." and chat_id=".intval($request["chat_id"]),["prefix" => "","ignoreSchema" => true]);
            }

            $embeddingTextCreation = CocoDB::insertRecords("cocochats_autolearn_embeddings",$data,null,["return_last_id" => 1,"prefix" => "","ignoreSchema" => true]);
            if (@$embeddingTextCreation){
                $embeddingResult = CocoDB::get("cocochats_autolearn_embeddings","chat_conf_num=".intval($request["chat_conf_num"])." and chat_id=".intval($request["chat_id"]),"num desc",1,["prefix" => "","ignoreSchemas" => true]);
            }
        }

        API::success($embeddingResult);

    }

    static function getTrainedData($request){
        if (!@$request["domain"]){
            throw new ApiError("domain needed");
        } 

        $chatbot = self::getChatbotFromDomain($request["domain"]);
                
        if (!@$chatbot){
            throw new ApiError("chatbot not found");
        }

        $where = "chat_conf_id=".intval($chatbot[0]["num"]);

        if (@$request["type"]){
            $where.=" and type = '".$request["type"]."'";
        }

        $requestedBotId = intval(@$request["bot_id"]);
        $bot = $requestedBotId > 0 ? @self::getBot(["botId" => $requestedBotId, "chat_conf_num" => $chatbot[0]["num"]], true)["bot"] : null;
        $where .= ($requestedBotId > 0 && !empty($bot["num"])) ? " AND bot_id=" . intval($bot["num"]) : ($requestedBotId > 0 ? " AND 1=0" : " AND (bot_id IS NULL OR bot_id = 0)");

        $trainResult = CocoDB::get("cocochats_training_data",$where,"num desc",null,["prefix" => "","ignoreSchemas" => true]);

        $trainResult = array_map(function($rec){
            $rec["state"] = @CocoDB::get("cocochats_training_embeddings","training_data_num = ".$rec["num"],"num desc",1,["prefix" => "","ignoreSchemas" => true]) ? true : false;
            $rec["enabled"] = intval($rec["enabled"]);
            return $rec;
        },$trainResult);

        API::success($trainResult);
    }

    static function cleanEmbeddings($request){
        if (!@$request["domain"]){
            throw new ApiError("domain needed");
        } 

        $chatbot = self::getChatbotFromDomain($request["domain"]);
                
        if (!@$chatbot){
            throw new ApiError("chatbot not found");
        }

        $trainResult = CocoDB::deleteRecords("cocochats_training_embeddings","training_data_num=".intval($request["num"]),["prefix" => "","ignoreSchemas" => true]);

        API::success(["result" => $trainResult]);
    }

    static function removeTrainedData($request){
        if (!@$request["domain"]){
            throw new ApiError("domain needed");
        } 

        $chatbot = self::getChatbotFromDomain($request["domain"]);
                
        if (!@$chatbot){
            throw new ApiError("chatbot not found");
        }

        $trainResult = CocoDB::deleteRecords("cocochats_training_data","num=".intval($request["num"]),["prefix" => "","ignoreSchemas" => true,"dieBeforeQuery" => true]);

        API::success(intval($request["num"]));
    }

    static function addOrUpdateTextToTrain($request){
        if (!@$request["domain"] || !@$request["chat_conf_id"]){
            throw new ApiError("domain or chat_conf_id needed");
        } 
		
        $data = [
            "chat_conf_id" => intval(@$request["chat_conf_id"]),
            "url" => @$request["url"],
            "title" => @$request["title"],
            "text" => @$request["text"],
            "enabled" => intval(@$request["enabled"]),
            "extraData" => @$request["extraData"],
            "type" => @$request["type"],
            "n_tokens" => @$request["n_tokens"],
            "embeddings" => @$request["embeddings"],
            "updatedDate" => date("Y-m-d H:i:s"),
            "bot_id" => @$request["bot_id"] ? intval($request["bot_id"]) : NULL
        ];
        if (@$request["num"]){
            $trainTextUpdating = CocoDB::updateRecords("cocochats_training_data",$data,"num=".intval($request["num"]),null,["prefix" => "","ignoreSchema" => true]);
            if (@$trainTextUpdating){
                $trainResult = CocoDB::get("cocochats_training_data","num=".intval($request["num"]),"num desc",1,["prefix" => "","ignoreSchemas" => true]);
            }else{
                throw new ApiError("Error on chat update");
            }

        }else{
            if($data["type"] == "ical" || $data["type"] == "feed"){
                CocoDB::deleteRecords("cocochats_training_data","chat_conf_id=".intval($data["chat_conf_id"])." and type='".$data["type"]."'",["prefix" => "","ignoreSchemas" => true]);
            }
            $trainTextCreation = CocoDB::insertRecords("cocochats_training_data",$data,null,["return_last_id" => 1,"prefix" => "","ignoreSchema" => true]);
            if (@$trainTextCreation){
                $trainResult = CocoDB::get("cocochats_training_data","num=".$trainTextCreation,"num desc",1,["prefix" => "","ignoreSchemas" => true]);
            }else{
                throw new ApiError("Error on chat creation");
            } 

        }
        
        API::success($trainResult);
        
    }

    static function changeChatIaMode($request){
        if (!@$request["chat_num"] || !@$request["mode"]){
            throw new ApiError("chat_num and mode needed");
        } 

        $chatNum = @$request["chat_num"];
        $mode = @$request["mode"];

        $chatResult = CocoDB::get("cocochats","num=".$chatNum,"num desc",1,["prefix" => "","ignoreSchemas" => true])[0];
        if (@$chatResult){
            $updateSuccess = CocoDB::updateRecords("cocochats",["iaMode" => $mode],"num=".$chatNum,null,["prefix" => "","ignoreSchema" => true]);
            API::success(["result" => $updateSuccess]);
        }else{
            throw new ApiError("Chat not found");
        }
        
    }

    static function markAsRead($request){
        if (!@$request["userHash"] || !@$request["chat_num"]){
            throw new ApiError("chat_num and userHash needed");
        } 

        $chatNum = @$request["chat_num"];
        $userHash = @$request["userHash"];


        /*$chatMessages = CocoDB::get("cocochats_mensajes","chat_id=".$chatNum,"num desc",null,["prefix" => "","ignoreSchemas" => true]);
        if (@$chatMessages){
            foreach($chatMessages as $chatMessage):
                
                $usersReaded = @array_filter(array_values(explode("\t",$chatMessage["leido_por"]))) ?: [];
                if (!in_array($userHash,$usersReaded)) $usersReaded[] = $userHash;
                sort($usersReaded, SORT_STRING);
                $string = "\t".join("\t",$usersReaded)."\t";
                $updateSuccess = CocoDB::updateRecords("cocochats_mensajes",["leido_por" => $string],"num=".$chatMessage["num"],null,["prefix" => "","ignoreSchema" => true]);
            endforeach;
            
            
        }*/
        // Optimizado: 1 sola query en lugar de N+1
        $userHashEscaped = mysql_real_escape_string($userHash);
        $sql = "UPDATE cocochats_mensajes
                SET leido_por = CONCAT(
                    COALESCE(leido_por, '\t'),
                    IF(leido_por NOT LIKE '%\t".$userHashEscaped."\t%', '".$userHashEscaped."\t', '')
                )
                WHERE chat_id = ".intval($chatNum)."
                AND (leido_por IS NULL OR leido_por NOT LIKE '%\t".$userHashEscaped."\t%')";
        mysql_query($sql);
    }

    static function getChatsNumsBySearchTerm($searchTerm, $status, $domain){
        global $TABLE_PREFIX;

        $searchTermEsc = mysql_real_escape_string($searchTerm);
        $statusEsc = mysql_real_escape_string($status);
        $domainEsc = mysql_real_escape_string($domain);

        // Optimizado: 1 query unificada en lugar de 5 queries separadas
        $sql = "
            SELECT DISTINCT c.num FROM cocochats c
            LEFT JOIN {$TABLE_PREFIX}contactos co ON co.num = c.contactNum
            WHERE c.website = '$domainEsc'
            AND c.status = '$statusEsc'
            AND c.uuid NOT LIKE 'test-widget%'
            AND c.trashed = 0
            AND (
                co.nombre LIKE '%$searchTermEsc%'
                OR co.telefono LIKE '%$searchTermEsc%'
                OR c.uuid LIKE '%$searchTermEsc%'
            )

            UNION

            SELECT DISTINCT c.num FROM cocochats c
            INNER JOIN cocochats_mensajes m ON m.chat_id = c.num
            WHERE c.website = '$domainEsc'
            AND c.status = '$statusEsc'
            AND c.uuid NOT LIKE 'test-widget%'
            AND c.trashed = 0
            AND m.mensaje LIKE '%$searchTermEsc%'
        ";

        $result = mysql_query_fetch_all_assoc($sql);
        return $result ? array_column($result, 'num') : [];
    }

    static function getChats($request){
        global $TABLE_PREFIX;

        if (!@$request["domain"]){
            throw new ApiError("Domain needed");
        }
		
		$where = "website = '".mysql_real_escape_string($request["domain"])."' and uuid not like 'test-widget%' and trashed = 0";

        if (@$request["chatType"]){

            $where .= " and chatType = '".mysql_real_escape_string($request["chatType"])."'";

        }else{
            if(@$request["notReaded"]){
                $where .= " and (chatType = 'default' or chatType = 'internal')";
            }else{
                $where .= " and chatType = 'default'";
            }
        }

        if (@$request["contactNum"]){
        	$where .= " and contactNum = '".mysql_real_escape_string($request["contactNum"])."'";
		}
        if (@$request["status"]){
            $where .= " and status = '".mysql_real_escape_string($request["status"])."'";
        }else if(@$request["notReaded"]){
            $where .= " and status = 'Abierta'";
        }

        if (@$request["perPage"]){
            $perPage = intval($request["perPage"]);
        }
		
        if (@$request["page"]){
            $page = intval($request["page"]);
        } else if (@$request["perPage"]) {
			$page = 1;
		}

		
        if (@$request["orderDir"]){
            $orderDir = "lastMessageDate ".$request["orderDir"];
        }else{
			$orderDir = "num desc";
		}
		
        if (@$request["dates"] && @$request["chatType"] != "internal"){
            $dates = $request["dates"];
            if (@$dates[0] && @$dates[1]){
                $where .= " and lastMessageDate >= '".mysql_real_escape_string(explode("T",$dates[0])[0])." 00:00:00'";
                $where .= " and lastMessageDate <= '".mysql_real_escape_string(explode("T",$dates[1])[0])." 23:59:59'";
            }
            
        }

        if (@$request["dateAgent"]){
            $where .= " and assignedToAgent = '".intval(@$request["dateAgent"])."'";
        }

        if (@$request["notReaded"] && @$request["userHash"]){
            $userHashEscaped = mysql_real_escape_string($request["userHash"]);
            $where .= " AND EXISTS (
                SELECT 1 FROM cocochats_mensajes ch 
                WHERE ch.chat_id = cocochats.num 
                AND (ch.leido_por IS NULL OR ch.leido_por NOT LIKE '%\t" . $userHashEscaped . "\t%')
                LIMIT 1
            )";
        }

        if (@$request["nums"]){
            $where .= " and num IN (".join(",",$request["nums"]).")";
        }

        if (@$request["searchTerm"] && @$request["status"]){
            $searchTerm = mysql_real_escape_string($request["searchTerm"]);
            $nums = self::getChatsNumsBySearchTerm($searchTerm, $request["status"], $request["domain"]);
            if (@$nums){
                $where .= " and num IN (".join(",",$nums).")";
            }else{
                $where .= " and num IN (0)";
            }
        }

        $options = ["prefix" => "","ignoreSchemas" => true];

        $limit = 500;

        if (@$perPage){
            $limit = ["page" => $page,"limit" => $perPage];
        }

		$chatResult = CocoDB::get("cocochats",$where,$orderDir,$limit,$options);
        
        // VERSIÓN OPTIMIZADA
        $chatResultNums = $chatResult ? array_filter(array_map(function($r){ return intval($r["num"]); }, $chatResult)) : [];
        $chatResultContactNums = $chatResult ? array_filter(array_map(function($r){ return intval($r["contactNum"]); }, $chatResult)) : [];

        // Consulta más simple usando una subquery correlacionada optimizada
        $messagesByChatNums = $chatResultNums ? mysql_query_fetch_all_assoc("
            SELECT m.* 
            FROM cocochats_mensajes m
            WHERE m.num IN (
                SELECT MAX(num) 
                FROM cocochats_mensajes 
                WHERE chat_id IN (" . join(",", $chatResultNums) . ") 
                GROUP BY chat_id
            )
        ") : [];

        $messagesByChatAssoc = array_column($messagesByChatNums, null, 'chat_id');

        $contactsByNums = $chatResultContactNums ? mysql_query_fetch_all_assoc("
            SELECT * FROM cms_contactos WHERE num IN (" . join(",", $chatResultContactNums) . ")
        ") : [];

        $contactsAssoc = array_column($contactsByNums, null, 'num');
        // FIN NUEVO SISTEMA PARA OPTIMIZAR LAS SUBCONSULTAS
        
        //Jordán se cagó encima, puso resultchats, se lo cambio por resultChats
        $resultChats = [];

        if (@$chatResult){
            foreach ($chatResult as $rec){
                //$message = @CocoDB::get("cocochats_mensajes","chat_id = ".$rec["num"],"num desc",1,["prefix" => "","ignoreSchemas" => true])[0];
                $message = @$messagesByChatAssoc[$rec["num"]];

                if($rec["contactNum"]!=null){
                    //$contactoBBDD = @CocoDB::get("contactos","num=".intval($rec["contactNum"]),"num desc",1,["ignoreSchemas" => true])[0];
                    $contactoBBDD = @$contactsAssoc[$rec["contactNum"]];
                }else if($rec["chatInfo"]){
                    $chatInfo = @json_decode($rec["chatInfo"],true);
                    if(@$chatInfo["contactInfo"]){
                        $contactoBBDD = $chatInfo["contactInfo"];
                    }else{
                        $contactoBBDD = null;
                    }
                }
                $resultChats[] = [
                    "chat_num" => intval($rec["num"]),
                    "uuid" => $rec["uuid"],
                    "lastConnectionDate" => $rec["lastConnectionDate"],
                    "lastMessageDate" => $rec["lastMessageDate"],
                    "description" => @$rec["description"] ?: "",
                    "channel" => @$rec["channel"],
                    "realChannelNum" => @$rec["real_channel_num"],
                    "lastMessage" => $message ? ["role" => $message["origin"],"content" => $message["mensaje"],"timestamp" => $message["createdDate"],"read_by" => $message["leido_por"],"message_type" => $message["message_type"]] : null,
                    "contact" => (isset($contactoBBDD))?$contactoBBDD:["nombre"=>"Desconocido"],
                    "rating" => @$rec["valoracion"],
                    "chatIaMode" => @$rec["iaMode"],
                    "status" => @$rec["status"],
                    "chatType" => @$rec["chatType"],
                    "where" => $where,
                ];
            }
        }

        

        if (@$request["chatType"] == "internal" && !@$request["notReaded"]){
            self::createEmptyAgentsChats($resultChats,$request["domain"]);
        }

        /*$resultChats = array_map(function($rec) use($where){
            $rec["where"] = $where;
            return $rec;
        },$resultChats);*/
        
        if (@$request["returnResult"]){
            return $resultChats;
        }
        
        API::success($resultChats);
    }

    static function createEmptyAgentsChats(&$resultChats, $domain){
        $chatbot = CocoDB::get("chat_configuraciones","dominio='".mysql_real_escape_string($domain)."'","num desc",1)[0];
        if (!@$chatbot) return [];

        $categorias = CocoDB::get("categorizacion_conv","chat_conf_num=".$chatbot["num"],"num desc",null,["ignoreSchemas" => true]);
        $categorias[] = [
            "num" => 0,
            "title" => "General"
        ];

        foreach($categorias as $cat){
            $chatExists = false;
            foreach($resultChats as $contChat => $chat){
                if ($chat["uuid"] == md5($cat["num"]."".$domain)."_".$chatbot["num"]){
                    
                    $resultChats[$contChat]["contact"] = ["nombre" => $cat["title"]];
                    
                    $chatExists = true;
                    break;
                }
            }
            if ($chatExists){
                continue;
            }

            $data = [
                "createdDate" => date("Y-m-d H:i:s"),
                "uuid" => md5($cat["num"]."".$domain)."_".$chatbot["num"],
                "lastConnectionDate" => date("Y-m-d H:i:s"),
                "lastMessageDate" => date("Y-m-d H:i:s"),
                "website" => $domain,
                "description" => $cat["title"],
                "channel" => "web",
                "status" => "Abierta",
                "valoracion" => 0,
                "chatType" => "internal"
            ];
            $chatExists = CocoDB::get("cocochats","uuid = '".mysql_real_escape_string($data["uuid"])."' and website = '".mysql_real_escape_string($domain)."' and trashed = 0","num desc",1,["prefix" => "","ignoreSchemas" => true]);
            if (!@$chatExists){    
                $chatResultCreation = CocoDB::insertRecords("cocochats",$data,null,["return_last_id" => 1,"prefix" => "","ignoreSchema" => true]);

                $newChatAgent = [
                    "chat_num" => $chatResultCreation,
                    "uuid" => md5($cat["num"]."".$domain)."_".$chatbot["num"],
                    "lastConnectionDate" => date("Y-m-d H:i:s"),
                    "lastMessageDate" => date("Y-m-d H:i:s"),
                    "description" => $cat["title"],
                    "channel" => 'web',
                    "lastMessage" => date("Y-m-d H:i:s"),
                    "contact" => ["nombre" => $cat["title"]],
                    "rating" => 0,
                    "chatIaMode" => 'default',
                    "status" => 'Abierta',
                ];
            }else{
                $chatExists[0]["chat_num"] = $chatExists[0]["num"];
                $chatExists[0]["contact"] = ["nombre" => $cat["title"]];
                $chatExists[0]["chatIaMode"] = $chatExists[0]["iaMode"];
                unset($chatExists[0]["num"]);

                $newChatAgent = $chatExists[0];
            }
            
            $resultChats[] = $newChatAgent;
        }
        //die(var_dump($resultChats));
        // ordenar resultchats por la clave num
        $resultChats = array_filter($resultChats,function($rec){
            if (!@$rec["chat_num"]){
                return false;
            }
            return true;
        });
        usort($resultChats, function($a, $b) {
            return $a['chat_num'] <=> $b['chat_num'];
        });
        return $resultChats;
    }

    static function deleteIntegrationConfig($request){
        if (!@$request["num"]){
            throw new ApiError("num are needed");
        }
        $channel = CocoDB::get("canales", [["column" => "num", "value" => intval($request["num"]), "operator" => "="]], "num desc", 1)[0];
        if (@$channel){
            $resultDelete = CocoDB::deleteRecords("canales","num=".intval($request["num"]),[]);
            if (@$channel["name"] == "whatsapp") {
                $updateSuccess_conf = CocoDB::updateRecords("chat_configuraciones",["telefono" => ""],"num=".$channel["chat_conf_num"],null);    
            }
            if (@$channel["name"] == "instagram") {
                $updateSuccess_conf = CocoDB::updateRecords("chat_configuraciones",["instagram_id" => ""],"num=".$channel["chat_conf_num"],null);    
            }

            if (@$channel["name"] == "messenger") {
                $updateSuccess_conf = CocoDB::updateRecords("chat_configuraciones",["messenger_id" => ""],"num=".$channel["chat_conf_num"],null);    
            }

            if (@$channel["identify_value"]){
                $chatResult = @CocoDB::get("chat_configuraciones","num=".intval(@$channel["chat_conf_num"]),"num desc",1,["ignoreSchemas" => true])[0];
                if (@$chatResult){
                    CocoDB::updateRecords("cocochats",["trashed" => 1],"website = '".mysql_real_escape_string($chatResult["dominio"])."' and channel_num = '".mysql_real_escape_string($channel["identify_value"])."'",null,["prefix" => "","ignoreSchema" => true]);
                }
            }
        }else{
            throw new ApiError("Channel no encontrado");
        }
        
        API::success(["result" => $resultDelete,"num" => $request["num"]]);
    }

    static function toggleTrainedData($request){
        if (!@$request["num"]){
            throw new ApiError("num are needed");
        }
        $trainedData = CocoDB::get("cocochats_training_data",["num" => intval($request["num"])],"num desc",1,["prefix" => "","ignoreSchemas" => true])[0];
        if (@$trainedData){
            $newStatus = $trainedData["enabled"] ? 0 : 1;
            $updateSuccess = CocoDB::updateRecords("cocochats_training_data",["enabled" => $newStatus],"num=".intval($request["num"]),null,["prefix" => "","ignoreSchema" => true]);
        }else{
            throw new ApiError("Trained data not found");
        }
        API::success(["result" => $updateSuccess,"num" => $request["num"],"enabled" => $newStatus]);
    }

	static function createOrUpdateIntegrationConfig($request) {
        if (!@$request["network"] || !@$request["network_config"] || !@$request["chat_conf_num"]){
            throw new ApiError("domain and jsonData are needed");
        }
		
		$network = $request['network'];
		$newConfig = $request['network_config'];
        $chat_conf_num = $request['chat_conf_num'];
        $channel_num = @$request['channel_num'] ?: NULL;
        $identify_value = @$newConfig["identify_value"] ?: "";
        $identify_value_extra = @$newConfig["phone_registered_number"] ?: NULL;
		
		// Validar que el network es "whatsapp"
		if ($network !== 'whatsapp' && $network !== 'instagram' && $network !== 'messenger' && $network !== 'custom' && $network !== 'telegram' && $network !== 'twilio' && $network !== 'quantumvoice') {
			throw new ApiError("Now only 'whatsapp' and 'instagram' and 'messenger' and 'custom' and 'telegram' and 'twilio' and 'quantumvoice' networks are supported");
		}

		$data = [
			"createdByUserNum" => API::$user['num'],
			"updatedByUserNum" => API::$user['num'],
			"name" => $network,
			"channel_config" => $newConfig,
            "chat_conf_num" => $chat_conf_num,
			"updatedDate" => date("Y-m-d H:i:s"),
            "identify_value" => $identify_value,
            "identify_value_extra" => $identify_value_extra
		];

        if (@$network == "whatsapp"){
            
            $phone = @$newConfig["user_number_list"][0]["display_phone_number"];
            if (@$phone){
                $updateSuccess_conf = CocoDB::updateRecords("chat_configuraciones",["telefono" => $phone],"num=".intval(@$chat_conf_num),null);
            }else{
                throw new ApiError("Phone not found in whatsapp config");
            }
        }

        if (@$network == "instagram"){
            
            $instagram_page_id = @$newConfig["instagram_account_data_object"]["id"];

            if (@$instagram_page_id){
                $updateSuccess_conf = CocoDB::updateRecords("chat_configuraciones",["instagram_id" => "IG".$instagram_page_id],"num=".intval(@$chat_conf_num),null);
            }else{
                throw new ApiError("ID Not found in instagram config".json_encode($newConfig));
            }
        }

        if (@$network == "messenger"){
            
            $messenger_page_id = @$newConfig["page_selected"];

            if (@$messenger_page_id){
                $updateSuccess_conf = CocoDB::updateRecords("chat_configuraciones",["messenger_id" => "MS".$messenger_page_id],"num=".intval(@$chat_conf_num),null);
            }else{
                throw new ApiError("ID Not found in messenger config");
            }
        }

		// Obtener el registro existente
		if (!@$request["multiple"] || @$channel_num){
            if (@$channel_num){
                $chatbot = CocoDB::get("canales", [["column" => "chat_conf_num", "value" => $chat_conf_num, "operator" => "="],["column" => "name", "value" => $network, "operator" => "="],["column" => "num", "value" => $channel_num, "operator" => "="]], "num desc", 1);
            }else{
                $chatbot = CocoDB::get("canales", [["column" => "chat_conf_num", "value" => $chat_conf_num, "operator" => "="],["column" => "name", "value" => $network, "operator" => "="]], "num desc", 1);
            }
        }else{
            $chatbot = null;
        }
		// Si no existe el registro, crear uno nuevo
		if (!$chatbot || (is_array($chatbot) && count($chatbot)<=0)) {
			$data["createdDate"] = date("Y-m-d H:i:s");
			$id = CocoDB::insertRecords("cms_canales",$data,null,["return_last_id" => 1,"prefix" => "","ignoreSchema" => true]);
			API::success(["result" => "New record created successfully (".$id.")"]);
			return;
		}
		$updateSuccess = CocoDB::updateRecords("cms_canales",$data,"num=".$chatbot[0]["num"],null,["prefix" => "","ignoreSchema" => true]);

        
		API::success(["result" => "Record updated successfully","resultUpdate" => $updateSuccess,"num" => $chatbot[0]["num"]]);
	}

    static function scheduleMessageSended($request){
        if (!@$request["chat_num"]){
            throw new ApiError("chat_num needed");
        } 

        $chatNum = intval(@$request["chat_num"]);
        $hace30min = date("Y-m-d H:i:s",strtotime("-4 hours"));
        $exists = @CocoDB::get("cocochats_mensajes","chat_id=".$chatNum." and message_type='schedule' and createdDate >= '".$hace30min."'","num desc",1,["prefix" => "","ignoreSchemas" => true])[0];

        return $exists ? true : false;

    }

    static function getAgentNameByNum($request){
        if (!@$request["agent_num"]){
            throw new ApiError("agent_num needed");
        } 

        $agentNum = intval(@$request["agent_num"]);
        $agent = @CocoDB::get("usuarios","num=".$agentNum,"num desc",1,["ignoreSchemas" => true])[0];

        return @$agent["nombre"] ? $agent["nombre"] : "Desconocido";

    }

    static function welcomeMessageSended($request){
        //return false;
        if (!@$request["chat_num"]){
            throw new ApiError("chat_num needed");
        } 

        $chatNum = intval(@$request["chat_num"]);
        $hace30min = date("Y-m-d H:i:s",strtotime("-4 hours"));
        $exists = @CocoDB::get("cocochats_mensajes","chat_id=".$chatNum." and message_type LIKE 'welcome_%' and createdDate >= '".$hace30min."'","num desc",1,["prefix" => "","ignoreSchemas" => true])[0];

        return $exists ? true : false;

    }    
	
    static function getIntegrationConfig($request) {
        if (!@$request["chat_conf_num"]){
            throw new ApiError("chat_conf_num and jsonData are needed");
        }
		$chat_conf_num = $request['chat_conf_num'];
        
        $where = [["column" => "chat_conf_num", "value" => $chat_conf_num, "operator" => "="]];
        if (@$request["network"]){
            $where[] = ["column" => "name", "value" => $request['network'], "operator" => "="];
        }

		$canales = CocoDB::get("canales", $where, "num desc", 10,["ignoreSchema" => true]);
		
        if (@$canales && !@$canales[0]["channel_config"]){
            throw new ApiError("Channel configuration not found [" . $request["chat_conf_num"] . "]");
        }
        API::success($canales);
	}
	
    static function getJsonData($request){
        if (!@$request["domain"]){
            throw new ApiError("Domain or number are needed in domain Field");
        }
        $request["domain"] = preg_replace("/[^a-zA-Z0-9.]/", "", $request["domain"]);

        if ($request["domain"] == intval($request["domain"])){
            $chatbot = CocoDB::get("chat_configuraciones",[
                ["column" => "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telefono,')',''),'(',''),'+',''),' ',''),'-','')","value" => $request["domain"],"operator" => "="],
            ],"num desc",1);
        }else{
            $canal = @CocoDB::get("canales",[
                ["column" => "identify_value","value" => $request["domain"],"operator" => "="]
            ],"num desc",1)[0];
            if (@$canal){
                $chatbot = CocoDB::get("chat_configuraciones","num=".$canal["chat_conf_num"],"num desc",1);
            }else{
                $chatbot = CocoDB::get("chat_configuraciones","REPLACE(dominio,' ','') = '".$request["domain"]."'","num desc",1);
            }
        }
        if (!@$chatbot[0]["dominio"]){
            throw new ApiError("Chatbot not found [" . intval($request["domain"] . "][" . $request["domain"] . "]"));
        }
        if (@$chatbot){
            $requestedBotId = intval(@$request["bot_id"]);
            $bot = $requestedBotId > 0 ? @self::getBot(["botId" => $requestedBotId, "chat_conf_num" => $chatbot[0]["num"]], true)["bot"] : null;
            if ($requestedBotId > 0 && !$bot) throw new ApiError("Bot not found: " . $requestedBotId);
            if ($bot) {
                if (@$bot["data"]) $chatbot[0]["data"] = $bot["data"];
                $chatbot[0]["bot_id"] = $bot["num"];
                $chatbot[0]["bot_name"] = $bot["name"];
            }

            // GENERAL
            $filtered_keys = ["info_hours","info_general"];
            $chatbot[0]["additional_data"] = [];
            $chatConfNum = intval($chatbot[0]["num"]);
            $keysSql = "'" . join("','", $filtered_keys) . "'";
            $where = "chat_conf_num=" . $chatConfNum . " AND `key` IN (" . $keysSql . ")";
            $order = "num desc";
            
            $additional_data = @CocoDB::get("cocouser_configs", $where . (!$bot ? " AND (bot_id IS NULL OR bot_id = 0)" : ""), $order, null, ["prefix" => "", "ignoreSchemas" => true]) ?: [];
            

            // BOT
            $filtered_keys = ["help_messages","start_messages","welcome_type"];
            $chatbot[0]["additional_data"] = [];
            $chatConfNum = intval($chatbot[0]["num"]);
            $keysSql = "'" . join("','", $filtered_keys) . "'";
            $where = "chat_conf_num=" . $chatConfNum . " AND `key` IN (" . $keysSql . ")";
            $order = "num desc";
            if ($bot) {
                $where .= " AND (bot_id=" . intval($bot["num"]) . " OR bot_id IS NULL OR bot_id = 0)";
                $order = "bot_id DESC, num desc";
            }
            $additional_data_bot = @CocoDB::get("cocouser_configs", $where . (!$bot ? " AND (bot_id IS NULL OR bot_id = 0)" : ""), $order, null, ["prefix" => "", "ignoreSchemas" => true]) ?: [];
            if ($bot) {
                $seen = []; $dedup = [];
                foreach($additional_data_bot as $row){ if (!isset($seen[$row["key"]])) { $dedup[] = $row; $seen[$row["key"]] = true; } }
                $additional_data_bot = $dedup;
            }

            $additional_data_result = array_merge($additional_data,$additional_data_bot);
            
            foreach($additional_data_result as $ad){
                $chatbot[0]["additional_data"][$ad["key"]] = @json_decode($ad["value"],true) ?: $ad["value"];
            }

            if (!@$chatbot[0]["data"]){
                $chatbot[0]["data"] = '[{"campo":"initial_config","valor":"{}"}]';
            }
            $resultPlanData = ["dominio" => $request["domain"],"chatbot_conf" => $chatbot[0]["num"],"num" => null];
            self::getQuantumPlanData($resultPlanData);
            $chatbot[0]["planData"] = $resultPlanData;

            $webhooks = CocoDB::get("webhooks","chat_conf_num=".intval($chatbot[0]["num"]),"num desc",null,["ignoreSchemas" => true]);
            $chatbot[0]["webhooks"] = @$webhooks ? array_map(function($rec){ 
                return [
                    "url" => $rec["url"],
                    "title" => $rec["title"],
                    "channel" => $rec["channel"],
                    "chat_conf_num" => $rec["chat_conf_num"]
                ];
            }, $webhooks): [];
        }
        if (@$request["returnResult"]){
            return $chatbot;
        }
        API::success($chatbot);
    }

    static function saveRating($request){
        if (!@$request["chat_num"] || !@$request["rating"]){
            throw new ApiError("chat_num and rating needed");
        } 

        $chatNum = intval(@$request["chat_num"]);
        $rating = @$request["rating"];

        $updateSuccess = CocoDB::updateRecords("cocochats",["valoracion" => $rating],"num=".$chatNum,null,["prefix" => "","ignoreSchema" => true]);
        
        $chatResult = CocoDB::get("cocochats","num=".$chatNum,"num desc",1,["prefix" => "","ignoreSchemas" => true])[0];
        if (!@$chatResult){
            throw new ApiError("Chat not found");
        }

        $chatConfiguraciones = CocoDB::get("chat_configuraciones","dominio='".$chatResult["website"]."'","num desc",1,["ignoreSchemas" => true])[0];
        if (!@$chatConfiguraciones){
            throw new ApiError("Chat configuration not found");
        }
        
        $lastMessage = CocoDB::get("cocochats_mensajes","chat_id=".$chatNum,"num desc",1,["prefix" => "","ignoreSchemas" => true])[0];

        $insertSuccess = CocoDB::insertRecords("cocochats_ratings",[
            "createdDate" => date("Y-m-d H:i:s"),
            "chat_conf_num" => @$chatConfiguraciones["num"] ?: '',
            "chat_num" => $chatResult["num"],
            "agent_num" => @$chatResult["assignedToAgent"] ?: NULL,
            "message_num" => @$lastMessage["num"] ?: '',
            "message_date" => @$lastMessage["createdDate"] ?: '',
            "rating" => $rating,
            "type" => @$request["type"] ?: 'auto',
        ],null,["return_last_id" => 1,"prefix" => "","ignoreSchema" => true]);

        return $updateSuccess;

    }

    static function saveJsonData($request){
        if (!@$request["domain"] || !@$request["jsonData"]){
            throw new ApiError("domain and jsonData are needed");
        }

        $request["domain"] = preg_replace("/[^a-zA-Z0-9.]/", "", $request["domain"]);

        $chatbot = @CocoDB::get("chat_configuraciones",[
            ["column" => "REPLACE(dominio,' ','')","value" => $request["domain"],"operator" => "="]
        ],"num desc",1)[0];

        if (@$chatbot){
            $requestedBotId = intval(@$request["bot_id"]);
            $bot = $requestedBotId > 0 ? @self::getBot(["botId" => $requestedBotId, "chat_conf_num" => $chatbot["num"]], true)["bot"] : null;
            if ($requestedBotId > 0 && !$bot) throw new ApiError("Bot not found: " . $requestedBotId);

            if ($bot) {
                $updateSuccess = CocoDB::updateRecords(
                    "ia_bots",
                    [
                        "data" => json_encode($request["jsonData"]),
                        "updatedDate" => date("Y-m-d H:i:s")
                    ],
                    "num=" . intval($bot["num"]),
                    null,
                    ["prefix" => "", "ignoreSchema" => true]
                );

                API::success([
                    "result" => $updateSuccess ? 1 : 0,
                    "bot_id" => intval($bot["num"])
                ]);
                return;
            }

            $updateSuccess = CocoDB::updateRecords(
                "chat_configuraciones",
                ["data" => json_encode($request["jsonData"])],
                "num=" . intval($chatbot["num"]),
                null
            );

            API::success(["result" => $updateSuccess ? 1 : 0]);
            return;
        }

        // Fallback: si no existe dominio pero llega bot_id, guardar en ese bot.
        if (@$request["bot_id"]) {
            $botId = intval($request["bot_id"]);
            $bot = @CocoDB::get(
                "ia_bots",
                "num=" . $botId . " AND activo=1",
                "num desc",
                1,
                ["prefix" => "", "ignoreSchemas" => true]
            )[0];

            if (!$bot) {
                throw new ApiError("Bot not found: " . $botId);
            }

            $updateSuccess = CocoDB::updateRecords(
                "ia_bots",
                [
                    "data" => json_encode($request["jsonData"]),
                    "updatedDate" => date("Y-m-d H:i:s")
                ],
                "num=" . $botId,
                null,
                ["prefix" => "", "ignoreSchema" => true]
            );

            API::success(["result" => $updateSuccess ? 1 : 0, "bot_id" => $botId]);
            return;
        }

        $data = [
            "dominio" => $request["domain"],
            "data" => json_encode($request["jsonData"])
        ];
        $chatResultCreation = CocoDB::insertRecords("chat_configuraciones",$data,null,["return_last_id" => 1]);

        API::success(["result" => @$chatResultCreation ? 1 : 0]);
    }

    static function getConfig($request){

        if (!@$request["chat_conf_num"]){
            throw new ApiError("dominio are needed");
        }
        $where = "chat_conf_num=".intval($request["chat_conf_num"]);

        if (@$request["key"]){
            $where .= " and `key`='".$request["key"]."'";
        }

        if (@$request["user_num"]){
            $where .= " and user_num=".intval($request["user_num"]);
        }

        $chatConfNum = intval($request["chat_conf_num"]);
        $requestedBotId = intval(@$request["bot_id"]);
        $bot = $requestedBotId > 0 ? @self::getBot(["botId" => $requestedBotId, "chat_conf_num" => $chatConfNum], true)["bot"] : null;
        $where .= ($requestedBotId > 0 && !empty($bot["num"])) ? " AND bot_id=" . intval($bot["num"]) : ($requestedBotId > 0 ? " AND 1=0" : " AND (bot_id IS NULL OR bot_id = 0)");

        $record = CocoDB::get("cocouser_configs",$where,"num desc",100,["prefix" => "","ignoreSchema" => true]);
        API::success($record);
    }

    static function countRegisters($request){
        $table = "cms_contactos";
        if (@$request["table"]){
            $table = $request["table"];
        }
		$where = "(email != 'upanel@quantumasis.com' and (external_id = '' or external_id is null) AND email NOT LIKE '@%')";
        if (@$request["where"]){
            $where.= ' and (' . $request["where"] . ')';
        }
        
		$query = mysql_query("select count(*) as totalRegisters from ". $table . " where ". $where);
        API::success(mysql_fetch_assoc($query));
    }

    static function saveConfig($request){
        if (!@$request["chat_conf_num"] && !@$request["user_num"]){
            throw new ApiError("chat_conf_num or user_num are needed");
        }
        if (!@$request["key"]){
            throw new ApiError("key are needed");
        }
        if (!@$request["value"]){
            throw new ApiError("value are needed");
        }
        $multiple = @$request["multiple"] ?: false; 
        //¨Si no se define multiple, el valor actual que tenga el usuario se sustituye por el nuevo

        $data = [
            "key" => $request["key"],
            "value" => is_array($request["value"]) ? json_encode($request["value"]) : $request["value"]
        ];
        
        //Petición para actualizar el idioma y el dominio (Adriel)
        if ($request["key"] === "info_general" && @$request["chat_conf_num"]) {
            $valueData = is_array($request["value"]) ? $request["value"] : json_decode($request["value"], true);
        
            $updateFields = []; // acumulamos cambios
        
            // Si viene lang
            if (isset($valueData["lang"])) {
                $updateFields["lang"] = $valueData["lang"];
                unset($valueData["lang"]);
            }
        
            // Si viene domain
            if (isset($valueData["domain"])) {
                $updateFields["dominio"] = $valueData["domain"];
                unset($valueData["domain"]);
            }
        
            // Si hay cambios en la tabla
            if (!empty($updateFields)) {
                $chatConfigWhere = "num=" . intval($request["chat_conf_num"]);
                $updateSuccess = CocoDB::updateRecords(
                    "chat_configuraciones",
                    $updateFields,
                    $chatConfigWhere,
                    ["prefix" => "", "ignoreSchema" => true]
                );
        
                if (!$updateSuccess) {
                    throw new ApiError("Error al actualizar campos en cms_chat_configuraciones");
                }
        
                // Actualizamos value sin lang ni domain
                $data["value"] = json_encode($valueData);
            }
        }
        //---------------------------------
        
        $where = "`key`='".$request["key"]."' ";
        if (@$request["user_num"]){
            $where .= " and user_num=".intval($request["user_num"]);
            $data["user_num"] = intval($request["user_num"]);
        }

        if (@$request["chat_conf_num"]){
            $where .= " and chat_conf_num=".intval($request["chat_conf_num"]);
            $data["chat_conf_num"] = intval($request["chat_conf_num"]);
        }

        if (@$request["chat_conf_num"]) {
            $chatConfNum = intval($request["chat_conf_num"]);
            $requestedBotId = intval(@$request["bot_id"]);
            $bot = $requestedBotId > 0 ? @self::getBot(["botId" => $requestedBotId, "chat_conf_num" => $chatConfNum], true)["bot"] : null;
            if ($requestedBotId > 0 && !$bot) throw new ApiError("Bot not found: " . $requestedBotId);
            if ($requestedBotId > 0 && !empty($bot["num"])) {
                $where .= " AND bot_id=" . intval($bot["num"]);
                $data["bot_id"] = intval($bot["num"]);
            } else {
                $where .= " AND (bot_id IS NULL OR bot_id = 0)";
                unset($data["bot_id"]);
            }
        } else if (@$request["bot_id"]) {
            $where .= " AND bot_id=" . intval($request["bot_id"]);
            $data["bot_id"] = intval($request["bot_id"]);
        } else {
            $where .= " AND (bot_id IS NULL OR bot_id = 0)";
        }

        if (!@$multiple || $request["key"] == "custom_profile"){
            $removeSuccess = CocoDB::deleteRecords("cocouser_configs",$where, ["prefix" => "","ignoreSchemas" => true]);
        }

        $insertSuccess = CocoDB::insertRecords("cocouser_configs",$data,null,["return_last_id" => 1,"prefix" => "","ignoreSchema" => true]);
        
        if (@$insertSuccess){
            $record = CocoDB::get("cocouser_configs","num=".intval($insertSuccess),"num DESC",1,["prefix" => "","ignoreSchema" => true]);
            API::success($record);
        }else{
            throw new ApiError("Error al crear el registro");
        }

    }

    static function saveSource($request){
        if (!@$request["url"]){
            throw new ApiError("url are needed");
        }
        if (!@$request["title"]){
            throw new ApiError("title are needed");
        }
        if (!@$request["dominio"]){
            throw new ApiError("dominio are needed");
        }
        if (!@$request["type"]){
            throw new ApiError("type are needed");
        }

        $request["dominio"] = preg_replace("/[^a-zA-Z0-9.]/", "", $request["dominio"]);
            
        $chatbot = @CocoDB::get("chat_configuraciones",[
            ["column" => "REPLACE(dominio,' ','')","value" => $request["dominio"],"operator" => "="]
        ],"num desc",1)[0];
    
        if (@$chatbot){
            $updateSuccess = CocoDB::updateRecords("chat_configuraciones",["data" => json_encode($request["jsonData"])],"num=".intval(@$chatbot["num"]),null);
        }else{
            $data = [
                "dominio" => $request["dominio"],
                "data" => json_encode($request["jsonData"])
            ];
            $chatResultCreation = CocoDB::insertRecords("chat_configuraciones",$data,null,["return_last_id" => 1]);
        }

        API::success(["result" => 1]);
    }

    static function tokensUsed($request){
        if (!@$request["domain"] || !@$request["tokens"]){
            throw new ApiError("Domain and tokens are needed");
        }

        if ($request["domain"] == intval($request["domain"])){
            $chatbot = CocoDB::get("chat_configuraciones",[
                ["column" => "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telefono,')',''),'(',''),'+',''),' ',''),'-','')","value" => $request["domain"],"operator" => "="],
            ],"num desc",1);
        }else{
            $chatbot = CocoDB::get("chat_configuraciones","REPLACE(dominio,' ','') = '".$request["domain"]."' OR instagram_id = '".$request["domain"]."' OR messenger_id = '".$request["domain"]."'","num desc",1);
        }

        $request["domain"] = $chatbot[0]["dominio"];

        $data = [
            "createdDate" => date("Y-m-d H:i:s"),
            "website" => $request["domain"],
            "tokens" => intval(@$request["tokens"])
        ];

        $tokensResultCreation = CocoDB::insertRecords("cocotokens",$data,null,["return_last_id" => 1,"prefix" => "","ignoreSchema" => true]);
        if (@$tokensResultCreation){
            API::success(["record_num" => $tokensResultCreation]);
        }else{
            throw new ApiError("Error on chat creation");
        }  

    }

    static function addInternalNote($request){
        if (!@$request["chat_num"] || !@$request["noteData"] || !@$request["domain"]){
            throw new ApiError("chat_num and note and domain are needed");
        }

        $data = [
            "chat_num" => intval($request["chat_num"]),
            "text" => $request["noteData"]["text"],
            "agent" => @$request["noteData"]["agent"]
        ];

        $noteResultCreation = CocoDB::insertRecords("notas_internas",$data,null,["return_last_id" => 1]);
        if (@$noteResultCreation){
            API::success(["record_num" => $noteResultCreation]);
        }else{
            throw new ApiError("Error on chat creation");
        }  
    }
    
    //función para eliminar nota (Adriel)
    static function deleteInternalNote($request){
        if (!@$request["chat_num"] || !@$request["noteId"] || !@$request["domain"]){
            throw new ApiError("chat_num, noteId and domain are needed");
        }
    
        $chatbot = self::getChatbotFromDomain($request["domain"]);
                
        if (!@$chatbot){
            throw new ApiError("chatbot not found");
        }
    
        // Soft delete: marcar como eliminada
        $updateData = [
            "deleted_at" => date('Y-m-d H:i:s'),
            "deleted_by" => @$request["noteData"]["agent"] ?? null
        ];
    
        $updateResult = CocoDB::updateRecords(
            "notas_internas", 
            $updateData, 
            "num=" . intval($request["noteId"])
        );
    
        if ($updateResult){
            API::success(["soft_deleted_note_id" => intval($request["noteId"])]);
        } else {
            throw new ApiError("Error marking internal note as deleted");
        }
    }

    static function addMessage($request){

        if (!@$request["userHash"] || !@$request["domain"] || !@$request["chat_id"] || !@$request["message"] || !@$request["origin"]){
            throw new ApiError("Domain and userHash and chat_id and message needed");
        }

        $chatResult = CocoDB::get("cocochats","num=".intval($request["chat_id"]),"num desc",1,["prefix" => "","ignoreSchemas" => true,"aggregates" => [
            "(SELECT cf.num FROM cms_chat_configuraciones cf WHERE cf.dominio = website LIMIT 1) as chat_conf_num"]]);

        if (!$chatResult){
            throw new ApiError("Chat no encontrado");
        }

        // Crear o actualizar contacto
        $userData = @$request["userData"];
        $contactExists = self::getOrCreateContact(
            $userData,
            $chatResult[0]["chat_conf_num"]
        );
        

        $user = @$request["origin"] == "assistant" ? @$request["userAgent"] : @$request["userHash"];
        $user = @$user ?: "IA";

        $data = [
            "mensaje" => $request["message"],
            "origin" => $request["origin"],
            "user" => $user,
            "chat_id" => $request["chat_id"],
            "createdDate" => date("Y-m-d H:i:s"),
            "updatedDate" => date("Y-m-d H:i:s"),
            "message_type" => @$request["messageType"] ?: "text",
            "leido_por" => @$request["userAgent"] ? "\t".join("\t",[$request["userAgent"],$request["userHash"]])."\t" : "\t".$request["userHash"]."\t",
            "channel_id" => @$request["channelId"],
            "reply_to_id" => @$request["reply_to_id"] ?: null
        ];

        if (@$request["messageChannel"]){
            $data["message_channel"] = $request["messageChannel"];
        }

        if (@$request["messageChannelNum"]){
            $data["message_channel_num"] = $request["messageChannelNum"];
        }

        $messageExists = false;
        // JHON: esto hace que cuando entran mensajes de whatsapp se dupliquen if (@$request["isEdited"]){
            $messageExistsDB = @CocoDB::get("cocochats_mensajes","channel_id = '".$request["channelId"]."' AND chat_id='".$request["chat_id"]."'","num desc",1,["prefix" => "","ignoreSchema" => true])[0];
            if (@$messageExistsDB){
                $messageExists = true;
            }
        //Fin modificacion John}
           
        $dataToChat = [
            "lastConnectionDate" => date("Y-m-d H:i:s"),
        ];

        if (@$data["message_type"] != "report" && @$data["message_type"] != "calification" && @$data["message_type"] != "calification_response" && @$data["message_type"] != "schedule" && @$data["user"] != "IA"){
            // Si la conversación estaba cerrada, se cambia el estado a abierta y se marca el mensaje como reopened
            // Sólo válido para mensajes de texto, es un simple contador, tampoco afecta a lo demás
            if (@$chatResult[0]["status"] == "Resuelta" && $data["message_type"] == "text"){
                if (@$data["message_type"] == "text"){
                    $data["message_type"] = "text_reopened";
                }
            }
            
            if (@$chatResult[0]["status"] == "Resuelta" && @$request["sendNewToInbox"]){
                // Si es Nueva conversación ya resuelta y se fuerza otra bandeja de entrada, se envía a esa bandeja
                $dataToChat["status"] = $request["sendNewToInbox"];
            }else {
                // Por defecto, se marca como abierta en los demás casos
                $dataToChat["status"] = "Abierta";
            }
            
        }

        if ($messageExists && $messageExistsDB){
            $chatUpdateResult = CocoDB::updateRecords("cocochats_mensajes",["mensaje" => $request["message"],"updatedDate" => date("Y-m-d H:i:s")],"num=".$messageExistsDB["num"],null,["prefix" => "","ignoreSchema" => true]);
            $chatResultCreation = $messageExistsDB["num"];
        }else{
            $chatResultCreation = CocoDB::insertRecords("cocochats_mensajes",$data,null,["return_last_id" => 1,"prefix" => "","ignoreSchema" => true]);            
        }
        

        if (isset($request["forceStatus"])){
            $dataToChat["status"] = $request["forceStatus"];
        }

        if (@$contactExists){
            $dataToChat["contactNum"] = $contactExists["num"];
        }

        if ($chatResult[0]["status"] == "Resuelta" && @$request["origin"] != "assistant"){
            $info_general = @CocoDB::get("cocouser_configs","chat_conf_num=".intval($chatResult[0]["chat_conf_num"])." and `key`='info_general'","num desc",1,["prefix" => "","ignoreSchemas" => true])[0];
            if (@$info_general){
                $info_general = json_decode($info_general["value"],true);
                
                if (isset($info_general["removeAssignedOnReopen"]) && $info_general["removeAssignedOnReopen"] == true){
                    $dataToChat["assignedToAgent"] = 0;
                    $chatResult[0]["assignedToAgent"] = 0;
                }
            }
            
        }

        $updateSuccessChat = CocoDB::updateRecords("cocochats",$dataToChat,"num=".intval(@$request["chat_id"]),null,["prefix" => "","ignoreSchema" => true]);

        if (@$chatResultCreation){
            if (!@$request["channelId"]){
                $chatUpdateResult = CocoDB::updateRecords("cocochats_mensajes",["channel_id" => $chatResultCreation],"num=".$chatResultCreation,null,["prefix" => "","ignoreSchema" => true]);
            }
            $chatResult = CocoDB::get("cocochats_mensajes","num=".$chatResultCreation,null,null,["prefix" => "","ignoreSchemas" => true]);
        }else{
            throw new ApiError("Error on chat message creation");
        }   
        
        // Reutilizar datos de contacto si existen, sino query al contacto del chat
        if ($contactExists) {
            $chatResult[0]["userData"] = [
                "nombre" => $contactExists["nombre"],
                "email" => $contactExists["email"],
                "telefono" => $contactExists["telefono"]
            ];
        } else {
            $chatResult[0]["userData"] = @mysql_query_fetch_all_assoc("
                SELECT co.nombre, co.email, co.telefono FROM cocochats ch
                LEFT JOIN cms_contactos co ON co.num = ch.contactNum
                WHERE ch.num = ".intval($chatResult[0]["chat_id"])." LIMIT 1
            ")[0];
        }

        API::success($chatResult);
    }

    static function normalizeContactData($userData) {
        $normalized = [
            'nombre' => trim(@$userData["name"] ?: @$userData["nombre"] ?: 'Desconocido'),
            'email' => '',
            'telefono' => '',
            'external_id' => '',
        ];
        
        // Teléfono: solo números
        if (@$userData["telefono"]) {
            $normalized['telefono'] = preg_replace("/[^0-9]/", "", $userData["telefono"]);
        }
        
        // Email: minúsculas y sin espacios
        if (@$userData["email"]) {
            $normalized['email'] = strtolower(trim($userData["email"]));
        }
        
        // External ID: para redes sociales
        if (@$userData["id"]) {
            $normalized['external_id'] = trim($userData["id"]);
        } elseif (@$userData["username"]) {
            $normalized['external_id'] = trim($userData["username"]);
        }
        
        return $normalized;
    }

    static function getOrCreateContact($userData, $chatConfNum) {
        $normalized = self::normalizeContactData($userData);
        $chat_conf_num = intval($chatConfNum);
        
        // Buscar en orden de prioridad
        $searchField = null;
        $searchValue = null;
        
        if ($normalized['telefono']) {
            $searchField = 'telefono';
            $searchValue = $normalized['telefono'];
        } elseif ($normalized['email']) {
            $searchField = 'email';
            $searchValue = $normalized['email'];
        } elseif ($normalized['external_id']) {
            $searchField = 'external_id';
            $searchValue = $normalized['external_id'];
        }
        
        if (!$searchField) return null; // Sin identificador
        
        // Buscar
        $contactExists = @CocoDB::get(
            "contactos",
            "chat_conf_num = $chat_conf_num AND $searchField = '" . mysql_real_escape_string($searchValue) . "'",
            "updatedDate desc", 1, ["ignoreSchemas" => true]
        )[0];
        
        // Actualizar o crear
        if ($contactExists) {
            if ($normalized['nombre'] == 'Desconocido' && $contactExists['nombre'] != '') {
                $normalized['nombre'] = $contactExists['nombre'];
            }
            $updateData = ['updatedDate' => date('Y-m-d H:i:s')];
            if ($normalized['nombre']) $updateData['nombre'] = $normalized['nombre'];
            if ($normalized['email']) $updateData['email'] = $normalized['email'];
            if ($normalized['telefono']) $updateData['telefono'] = $normalized['telefono'];
            if ($normalized['external_id']) $updateData['external_id'] = $normalized['external_id'];
            
            CocoDB::updateRecords("contactos", $updateData, "num=" . $contactExists["num"], null, ["ignoreSchemas" => true]);
            return $contactExists;
        }
        
        // Crear
        $contactId = CocoDB::insertRecords("contactos", [
            'chat_conf_num' => $chat_conf_num,
            'nombre' => $normalized['nombre'],
            'email' => $normalized['email'],
            'telefono' => $normalized['telefono'],
            'external_id' => $normalized['external_id']
        ], null, ["return_last_id" => 1]);
        
        return $contactId ? CocoDB::get("contactos", "num=$contactId", "num desc", 1, ["ignoreSchemas" => true])[0] : null;
    }

    static function resetTestChat($request){
        if (!@$request["hash"]){
            throw new ApiError("hash needed".$request);
        }
        $chatbot = @CocoDB::get("chat_configuraciones","hash='".mysql_real_escape_string($request["hash"])."'","num desc",1)[0];

        if (!@$chatbot){
            throw new ApiError("Chatbot not found");
        }

        $chatResult = CocoDB::get("cocochats","website='".$chatbot["dominio"]."' and uuid like 'test-widget%' and trashed = 0","num desc",1,["prefix" => "","ignoreSchemas" => true]);

        if (@$chatResult){
            foreach($chatResult as $chRes){
                $deleteSuccessMessages = CocoDB::deleteRecords("cocochats_mensajes","chat_id=".intval($chRes["num"]),["prefix" => "","ignoreSchema" => true]);
            }
        }
            
    }

    static function updateChatTopic($request){
        
        // Sólo actualiza el chat topic si: 1-> no tiene descripcion, 2-> tiene descripción y tiene más de 5 mensajes en la ultima hora
        $oneHourAgo = date("Y-m-d H:i:s", strtotime("-5 minutes"));
        $chat = CocoDB::get("cocochats","num=".intval(@$request["chat_id"])." and (description IS NULL OR description = '' or lastUpdatedTopic IS NULL or lastUpdatedTopic <= '".$oneHourAgo."')","num desc",1,["prefix" => "","ignoreSchemas" => true]);
        if (!@$chat){
            API::success(["warning" => "El chat topic ya ha sido actualizado en los ultimos 5 minutos"]);
        }

        $messages = CocoDB::get("cocochats_mensajes","chat_id=".intval(@$request["chat_id"]),null,null,["prefix" => "","ignoreSchemas" => true]);
        $mensajes = "";
        if (count($messages) > 0) {
            foreach ($messages as $message) {
                switch ($message['origin']) {
                    case 'user':
                        $mensajes .= '-Usuario: ' . $message['mensaje'] . ' ';
                        break;
                    case 'assistant':
                        $mensajes .= '-Asistente: ' . $message['mensaje'] . ' ';
                        break;
                    default:
                        break;
                }
            }
        }

        $mensajes = "A continuación se presenta una conversación entre un usuario (-Usuario) y un asistente de chatbot (-Asistente): " . $mensajes . ". Debes devolver una frase corta de menos de 5 palabras ( sin contar las preposiciones ) describiendo la temática de la conversación.";

        $data = [
            "rol" => "user",
            "action" => "text/generateResume",
            "app" => "CHATBOT",
            "message" => $mensajes
        ];
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://ws.cocosolution.com/api/ia/?noAuth=1',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($data) // Convert data array to JSON
        ]);
        $response = curl_exec($curl);

        if ($response === false) {
            $error = curl_error($curl);
            curl_close($curl);
            die("Error en la llamada a la IA");
            
        } else {

            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            if ($httpCode != 200) {
                echo "HTTP Error: $httpCode";
                curl_close($curl);
                die("Error HTTP");
            }

            curl_close($curl);

            $response_decoded = json_decode($response, true);
            if (isset($response_decoded['success']) && $response_decoded['success'] == true) {
                $description = $response_decoded['data'];
                if ($description != '') {
                    $updateSuccess = CocoDB::updateRecords("cocochats",["description" => $description,"lastUpdatedTopic" => date("Y-m-d H:i:s")],"num=".intval(@$request["chat_id"]),null,["prefix" => "","ignoreSchema" => true]);
                }
            }
        }
    }
	static function slugify($string) {
		// Convierte la cadena en minúsculas, reemplaza caracteres no permitidos y recorta a 30 caracteres
		return substr(strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-')), 0, 30);
	}

	static function sendEmail($params) {
		require_once __DIR__."/../../../../../../sesion.php";
        require_once __DIR__."/../../../../../../funciones.php";
        require_once __DIR__."/../../../builder_saas/replace_code.php";
        require_once __DIR__."/../../../builder_saas/builder_functions.php";
        if(!isset($params["dominio"])){
            throw new ApiError("domain are needed");
        }

        $chatbot = CocoDB::get("chat_configuraciones","REPLACE(dominio,' ','') = '".$params["dominio"]."'","num desc",1);

        if (!@$chatbot){
            throw new ApiError("Chatbot not found");
        }

        $chatbot = $chatbot[0];

        $config = CocoDB::get("cocouser_configs","chat_conf_num=".$chatbot["num"]." and `key`='info_email'","num desc",1,["prefix" => "","ignoreSchema" => true]);
        if (@$config[0]){
            $config = @json_decode($config[0]["value"],true);
        }
        if (@$config){
            CocoEmail::$smtp = true;
            CocoEmail::$smtp_data["host"] = @$config["host"];
            CocoEmail::$smtp_data["port"] = @$config["puerto"];
            CocoEmail::$smtp_data["secure"] = @$config["encriptacion"];
            CocoEmail::$smtp_data["username"] = @$config["usuario"];
            CocoEmail::$smtp_data["password"] = @$config["contraseña"];
            CocoEmail::$smtp_data["from"] = @$config["correo"];
            CocoEmail::$smtp_data["from_name"] = @$config["nombre"];
        }else{
            CocoEmail::$smtp = true;
            CocoEmail::$smtp_data["host"] = "mail.quantumasis.com";
            CocoEmail::$smtp_data["port"] = "587";
            CocoEmail::$smtp_data["secure"] = "STARTTLS";
            CocoEmail::$smtp_data["username"] = "noreply@quantumasis.com";
            CocoEmail::$smtp_data["password"] = "uK5DDSa7sjPm7YtBbkRR";
            CocoEmail::$smtp_data["from"] = "noreply@quantumasis.com";
            CocoEmail::$smtp_data["from_name"] = "QuantumASIS";
		}

        $logo = CocoDB::get("cocouser_configs","chat_conf_num=".$chatbot["num"]." and `key`='custom_domain'","num desc",1,["prefix" => "","ignoreSchema" => true]);

        if (@$logo){
            $logo = @$logo[0]["value"];
        }

		if(!isset($params["cuerpo"])){
			$params["cuerpo"] = "INVITE_USER";
		}
		if(isset($params["email"])){
			hook("/hooks/customEmailHeader/",["logo" => @$logo]);
			$result3 = CocoEmail::send($params["cuerpo"],$params,[$params["email"]], @$params["subject"], @$params["content"], @$params["returnHTML"] ?: false, @$params["options"] ?: []);
			return ['success' => true];
		}
		return ['success' => false, 'message' => "Error: email or body are required" ];
	}


	static function uploadFile($request) {
		// Configuración inicial
		$date = new DateTime();
		$timestamp = $date->getTimestamp();
		$max_size = 10 * 1024 * 1024; // Tamaño máximo: 2 MB, Cambiado a 10 (Adriel)
		$target_dir = "/var/www/quantum/cms/uploads/documentsTraining/";
		//Agregado idioma y traducciones con t_var (Adriel)
		if($request["language"]){
            $_REQUEST["idioma"] = $request["language"];
        };

		// Verifica si el directorio no existe
		if (!is_dir($target_dir)) {
			// Intenta crear el directorio con permisos recursivos
			if (!mkdir($target_dir, 0755, true)) {
				throw new ApiError(t_var('Error, hubo un problema al intentar crear el directorio. Verifica si seleccionaste un archivo válido.'));
			}
		} 

		// Obtiene el esquema (http o https)
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		// Obtiene el nombre del host (dominio o IP)
		$host = $_SERVER['HTTP_HOST'];

		// Variables para manejar el archivo
		$file = null;
		$originalName = '';
		$extension = '';
		// Verificar si se recibió un archivo base64
		if (isset($_REQUEST['file']) && strpos($_REQUEST['file'], 'data:') === 0) {
			// Manejar archivo base64
			$base64Data = $_REQUEST['file'];

			// Extraer el tipo MIME y los datos
			preg_match('/^data:(\w+\/\w+);base64,(.*)$/', $base64Data, $matches);

			if (count($matches) !== 3) {
				throw new ApiError(t_var('Error en el formato del archivo base64.'));
			}

			$mimeType = $matches[1];
			$base64Content = $matches[2];

			// Mapeo de tipos MIME a extensiones
			$mimeToExtension = [
				'image/jpeg' => 'jpg',
				'image/jpg' => 'jpg',
                'image/png' => 'png',
				'application/pdf' => 'pdf',
				'application/msword' => 'doc',
				'text/plain' => 'txt',
				'application/vnd.ms-excel' => 'xls',
				'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document'=>'docx',
				// ---- Audio ---- (Adriel)
    			'audio/mpeg' => 'mp3',
    			'audio/mp3' => 'mp3',
    			'audio/wav' => 'wav',
    			'audio/x-wav' => 'wav',
    			'audio/ogg' => 'ogg',
    			'audio/webm' => 'webm',
    			'audio/aac' => 'aac',
    			'audio/flac' => 'flac'
			];

			$extension = $mimeToExtension[$mimeType] ?? null;

			if (!$extension) {
				throw new ApiError(t_var('Tipo de archivo no permitido.'));
			}

			// Decodificar contenido base64
			$fileContent = base64_decode($base64Content);

			// Validar tamaño
			if (strlen($fileContent) > $max_size) {
				throw new ApiError(t_var('Error, el archivo es demasiado pesado. Tamaño máximo permitido: 10 MB.'));
			}

			// Nombre de archivo
			$originalName = 'base64_file';

		} elseif (isset($_FILES["file"]) && $_FILES["file"]["error"] == UPLOAD_ERR_OK) {
			// Manejar archivo tradicional de $_FILES
			$fileInfo = pathinfo($_FILES["file"]["name"]);
			$originalName = $fileInfo['filename'];
			$extension = strtolower($fileInfo['extension']);

			// Validación de tamaño
			if ($_FILES["file"]["size"] > $max_size) {
				throw new ApiError(t_var('Error, el archivo es demasiado pesado. Tamaño máximo permitido: 10 MB.'));
			}

		} else {
			throw new ApiError(t_var('Error, no se pudo subir el archivo. Verifica si seleccionaste un archivo válido.'));
		}

		// Extensiones permitidas
		$allowedExtensions = ['jpg','jpeg', 'png', 'pdf', 'doc','docx', 'xls', 'xlsx', 'txt',
		                        'mp3','wav','ogg','webm','aac','flac']; //Añadidos audios (Adriel)
		if (!in_array($extension, $allowedExtensions)) {
			throw new ApiError(t_var('Error, solo se admiten archivos con las siguientes extensiones: ') . implode(', ', $allowedExtensions) . ".");
		}

		// Genera el nombre del archivo destino
		$slugifiedName = QuantumApi::slugify($originalName);
		$target_name = (isset($_POST['title']) && !empty($_POST['title']) 
						? QuantumApi::slugify($_POST['title']) 
						: $slugifiedName) 
			. "-" . $timestamp . "." . $extension;
		$target_file = $target_dir . $target_name;

		// Guardar archivo
		if (isset($fileContent)) {
			// Para base64
			if (file_put_contents($target_file, $fileContent) === false) {
				throw new ApiError(t_var('Error, no se pudo guardar el archivo en el servidor.'));
			}
		} else {
			// Para $_FILES
			if (!move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
				throw new ApiError(t_var('Error, no se pudo guardar el archivo en el servidor.'));
			}
		}

		// Respuesta exitosa
		API::success([
			"path" => $protocol.$host."/cms/uploads/documentsTraining/".$target_name, 
			"message" => t_var('Archivo subido correctamente.')
		]);
		
	}

    static function removeFile($request){
		// Asegurarse de que el directorio termine con una barra
		$directory = "/var/www/quantum/cms/uploads/documentsTraining/";

		// Ruta completa del archivo
		$filepath = $directory . $_REQUEST["filename"];

		// Verificar si el archivo existe
		if (!file_exists($filepath)) {
			throw new ApiError("Error, el archivo no existe.");
		}

		// Verificar si es un archivo normal (no un directorio)
		if (!is_file($filepath)) {
			throw new ApiError("Error, No es un archivo.");
		}

		// Intentar eliminar el archivo
		try {
			if (unlink($filepath)) {
				API::success([
					"message" => "Archivo borrado correctamente."
				]);
			} else {
				throw new ApiError("Error, el archivo no se puede borrar.");
			}
		} catch (Exception $e) {
			// Manejar cualquier error de eliminación
			error_log('Error al eliminar archivo: ' . $e->getMessage());
			
			throw new ApiError('Error al eliminar archivo: ' . $e->getMessage());
    	}
	}
	
	//funcion para descargar audio (Adriel)
	static function downloadAudio($request) {
        try {
            // Obtener los datos del POST
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['file_url']) || !isset($input['filename'])) {
                API::error('Datos incompletos: se requiere file_url y filename', 400);
            }
            
            $fileUrl = $input['file_url'];
            $filename = $input['filename'];
            
            // Validar que la URL sea del dominio permitido
            $allowedDomain = 'quantumasis.com';
            if (parse_url($fileUrl, PHP_URL_HOST) !== $allowedDomain) {
                API::error('URL no permitida', 403);
            }
            
            // Construir la ruta local del archivo
            $directory = "/var/www/quantum/cms/uploads/documentsTraining/";
            $filepath = $directory . $filename;
            
            // Verificar que el archivo existe
            if (!file_exists($filepath)) {
                API::error("Error, el archivo no existe en: $filepath", 404);
            }
            
            // Verificar que es un archivo válido
            if (!is_file($filepath)) {
                API::error("Error, No es un archivo válido: $filepath", 400);
            }
            
            // Obtener información del archivo
            $fileSize = filesize($filepath);
            $mimeType = mime_content_type($filepath);
            
            // Configurar headers para descarga
            header('Content-Type: ' . $mimeType);
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . $fileSize);
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: 0');
            
            // Limpiar buffer de salida
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Leer y enviar el archivo
            readfile($filepath);
            exit;
            
        } catch (Exception $e) {
            API::error('Error en downloadAudio: ' . $e->getMessage(), 500);
        }
    }

    static function getPlans($request){
        $idioma = @$request["idioma"] == "en" ? "en": "es";
        $moneda = @$request["currency"] == "en" ? "en" : "es";

        // OBTENEMOS LOS PLANES SEGUN LA MONEDA
        $_REQUEST["idioma"] = $moneda == "en" ? "en" : "";

        $plans_by_currency = CocoDB::get("planes","visible=1","dragSortOrder desc",100);
        $plans_by_currency = array_map(function($plan){
            $plan_aux = [
                "num" => intval(@$plan["num"]),
                "title" => @$plan["title"],
                "description" => @$plan["description"],
                "price" => @$plan["price"],
                "price_ano" => @$plan["price_ano"], 
                "features" => $plan["features_bd"],
                "texto" => @$plan["texto"],
                "maxChannels" => intval(@$plan["canales"]),
                "maxUsers" => intval(@$plan["agentes"]),
            ];
            
            return $plan_aux;
        },$plans_by_currency);


        // OBTENEMOS LOS PLANES SEGUN EL IDIOMA
        $_REQUEST["idioma"] = $idioma == "en" ? "en" : "";

        $plans = CocoDB::get("planes","visible=1","dragSortOrder desc",100);
        $plans = array_map(function($plan){
            $plan_aux = [
                "num" => intval(@$plan["num"]),
                "title" => @$plan["title"],
                "description" => @$plan["description"],
                "price" => @$plan["price"],
                "price_ano" => @$plan["price_ano"], 
                "features" => $plan["features_bd"],
                "texto" => @$plan["texto"],
                "maxChannels" => intval(@$plan["canales"]),
                "maxUsers" => intval(@$plan["agentes"]),
            ];
            
            return $plan_aux;
        },$plans);

        // UNIFICAMOS TODO
        foreach($plans as $index => $plan){
            $plan_currency = @array_values(array_filter($plans_by_currency, function($p) use ($plan){
                return $p["num"] == $plan["num"];
            }))[0];
            if (@$plan_currency){
                $plans[$index]["price"] = $plan_currency["price"];
                $plans[$index]["price_ano"] = $plan_currency["price_ano"];
            }

            $plans[$index]["currency_lang"] = $moneda;
            $plans[$index]["lang"] = $idioma;

        }



        API::success($plans);
    }

    static function getSubproductos($request){
        if (@$request["currency"]){
            $_REQUEST["idioma"] = @$request["currency"];
        }
        $subproductos = CocoDB::get("subproductos","visible=1","dragSortOrder desc",100);
        API::success($subproductos);
    }

    static function getPurchases($request){
        if (!@$request["userNum"] || !@$request["chat_conf_num"]){
            throw new ApiError("userNum and chat_conf_num needed");
        }
        if (@$request["currency"]){
            $_REQUEST["idioma"] = @$request["currency"];
        }
        $purchases = CocoDB::get("contrataciones","chat_conf_num='".$request["chat_conf_num"]."'",null,null,[]);
        API::success($purchases);
    }

    static function getPurchasesSubsWithStripeData($request, $returnData = false){
        if (!@$request["chat_conf_num"]){
            throw new ApiError("chat_conf_num needed");
        }
        $resultContrataciones = mysql_query_fetch_all_assoc(
            "
                SELECT 
                    co.*,su.producto_type as producto_type, 
                    pl.stripe_price_id as plan_stripe_price_id, 
                    pl.stripe_price_id_ano as plan_stripe_price_id_ano,
                    su.stripe_price_id as subproducto_stripe_price_id,
                    su.stripe_price_id_ano as subproducto_stripe_price_id_ano
                from cms_contrataciones co
                LEFT JOIN cms_planes pl on pl.num = co.plan
                LEFT JOIN cms_subproductos su on su.num = co.subproducto
                WHERE co.chat_conf_num = ".intval($request["chat_conf_num"])." and co.suscripcion = 'activa'
            "
        );

        if ($returnData){
            return $resultContrataciones;
        }else{
            API::success($resultContrataciones);
        }
    }

    static function markAllNotificationsAsReaded($request){
        if (!@$request["userNum"] || !@$request["chat_conf_num"]){
            throw new ApiError("userNum and chat_conf_num needed");
        }

        $userNum = intval($request['userNum']);
        $chat_conf_num = intval($request['chat_conf_num']);

        $notificaciones_leidas = CocoDB::get("notificaciones_leidas","user_num=".$userNum,"num desc",100,["prefix" => "","ignoreSchema" => true]);
        $notificaciones_leidas = array_map(function($notificacion){
            return $notificacion["notificacion_num"];
        },$notificaciones_leidas);

        $where = "(chat_conf_num=".$chat_conf_num." or chat_conf_num = '')";

        if (@$notificaciones_leidas && count($notificaciones_leidas) > 0){
            $where .= " and num not in (".join(",",$notificaciones_leidas).")";
        }

        $result = CocoDB::get("notificaciones",$where,"createdDate desc",100,["ignoreSchema" => true]);

        $resultInsert = array_map(function($notificacion) use ($userNum){
            return [
                "user_num" => $userNum,
                "notificacion_num" => $notificacion["num"]
            ];
        },$result);
        $insertSuccess = CocoDB::insertRecords("notificaciones_leidas",$resultInsert,null,["return_last_id" => 1,"prefix" => "","ignoreSchema" => true]);
        API::success($insertSuccess);
    }

    static function markNotificationAsReaded($request){
        if (!@$request["userNum"] || !@$request["notificationNum"]){
            throw new ApiError("userNum and notificationNum needed");
        }

        $userNum = intval($request['userNum']);
        $notificationNum = intval($request['notificationNum']);

        $data = [
            "user_num" => $userNum,
            "notificacion_num" => $notificationNum
        ];

        $insertSuccess = CocoDB::insertRecords("notificaciones_leidas",$data,null,["return_last_id" => 1,"prefix" => "","ignoreSchema" => true]);
        
        API::success($insertSuccess);
    }

    static function getNotifications($request){
        if (!@$request["userNum"] || !@$request["chat_conf_num"]){
            throw new ApiError("userNum and chat_conf_num needed");
        }

        $userNum = intval($request['userNum']);
        $chat_conf_num = intval($request['chat_conf_num']);

        $notificaciones_leidas = CocoDB::get("notificaciones_leidas","user_num=".$userNum,"num desc",100,["prefix" => "","ignoreSchema" => true]);
        $notificaciones_leidas = array_map(function($notificacion){
            return $notificacion["notificacion_num"];
        },$notificaciones_leidas);

        $where = "(chat_conf_num=".$chat_conf_num." or chat_conf_num = '')";

        if (@$notificaciones_leidas && count($notificaciones_leidas) > 0){
            $where .= " and num not in (".join(",",$notificaciones_leidas).")";
        }

        $result = CocoDB::get("notificaciones",$where,"createdDate desc",100,["ignoreSchema" => true]);
        
        API::success($result);
    }    

    static function getActiveChannels($request){
        if (!@$request["chat_conf_num"]){
            throw new ApiError("chat_conf_num needed");
        }

        $chat_conf_num = intval($request['chat_conf_num']);
        $channels = CocoDB::get("canales","chat_conf_num=".$chat_conf_num,"num desc",100,["ignoreSchema" => true]);
        
        API::success($channels);
    }

    static function toggleChannel($request){
        if (!@$request["chat_conf_num"] || !@$request["channelNum"]){
            throw new ApiError("chat_conf_num and channelNum needed");
        }

        $chat_conf_num = intval($request['chat_conf_num']);
        $channelNum = intval($request['channelNum']);
        $channel = CocoDB::get("canales","num=".$channelNum." and chat_conf_num=".$chat_conf_num,"num desc",1,["ignoreSchema" => true]);
        if (!$channel){
            throw new ApiError("Channel not found");
        }
        $channel = $channel[0];
        $data = [
            "activo" => @$request["active"] ? 1 : 0
        ];
        $updateSuccess = CocoDB::updateRecords("canales",$data,"num=".$channelNum,null,["ignoreSchema" => true]);
        if (!$updateSuccess){
            throw new ApiError("Error updating channel");
        }
        API::success([
            "message" => "Channel updated successfully",
            "active" => $data["activo"]
        ]);
    }
	
	
	static function createTicket($request){
		
		if (!@$request["title"] || !@$request["body"] || !@$request["user"] || !@$request["priority_status"]) {
			throw new ApiError("title, body, user, and priority_status are required");
		}

		$title = trim($request["title"]);
		$body = trim($request["body"]);
		$img_uri = isset($request["archivoUrl"]) ? trim($request["archivoUrl"]) : "";
		$userNum = intval($request["user"]);
		$priority = trim($request["priority_status"]);

		$prioridades_permitidas = ['Urgente', 'Normal', 'Critica', 'Baja'];
		if (!in_array($priority, $prioridades_permitidas)) {
			throw new ApiError("priority must be one of: " . implode(', ', $prioridades_permitidas));
		}
		
        // Obtener el chat_conf_num desde la base de datos
        $chatConfNumResult = CocoDB::get("chatbots", "usuario = ".$userNum);
        if (!$chatConfNumResult) {
            throw new ApiError("No se pudo obtener el chat_conf_num para el usuario.");
        }
        $chatConfNum = $chatConfNumResult[0]['chat_conf_num'];
        
		$data = [
			
				"agente" => $userNum,
				"titulo" => $title,
				"prioridad" => $priority,
				"estado" => "Abierta",
				"chat_conf_num" => $chatConfNum,
				"dragSortOrder" => date("mdHis"),
				"createdDate" => date("Y-m-d H:i:s"),
				"updatedDate" => date("Y-m-d H:i:s"),
				"createdByUserNum" => $userNum,
				"updatedByUserNum" => $userNum
			
		];
		$ticket_num = CocoDB::insertRecords("tickets", $data, null, [
			"return_last_id" => 1,
			"prefix" => "cms_",
			"ignoreSchema" => true
		]);

		if (!$ticket_num) {
			throw new ApiError("Error al crear el ticket");
		}
		
		
		$data = [
			
				"ticket_num" => $ticket_num,
				"user_num" => $userNum,
				"mensaje" => $body,
				"archivo_url" => $img_uri,
				"dragSortOrder" => date("mdHis"),
				"createdDate" => date("Y-m-d H:i:s"),
				"updatedDate" => date("Y-m-d H:i:s"),
				"createdByUserNum" => $userNum,
				"updatedByUserNum" => $userNum
			
		];
		$ticket_message_num = CocoDB::insertRecords("tickets_mensajes", $data, null, [
			"return_last_id" => 1,
			"prefix" => "cms_",
			"ignoreSchema" => true
		]);

		if (!$ticket_message_num) {
			throw new ApiError("Error al crear el ticket");
		}
		
        self::notifyAdminTicket($ticket_num,$title,$body,$userNum);
		
		API::success([
			"message" => "Ticket creado correctamente",
			"ticket_num" => $ticket_num,
			"ticket_message_num" => $ticket_message_num
		]);


    }
    
	static function updateTicket($request){
		
        if (!isset($request["user"]) || !isset($request["ticketNum"])) {
			throw new ApiError("user and ticketNum are required");
		}

		$userNum = intval($request["user"]);
		$ticketNum = trim($request["ticketNum"]);
		$data = [
			
				"agente" => $userNum,
				"updatedDate" => date("Y-m-d H:i:s"),
				"updatedByUserNum" => $userNum
			
		];
		
		if(isset($request["title"])){
		    $title = trim($request["title"]);
            $data["titulo"] = trim($title);
		}


        // Validación y asignación de prioridad
        if (isset($request["priority_status"])) {
            $priority = trim($request["priority_status"]);
            $prioridades_permitidas = ['Urgente', 'Normal', 'Critica', 'Baja'];
            if (!in_array($priority, $prioridades_permitidas)) {
                throw new ApiError("priority must be one of: " . implode(', ', $prioridades_permitidas));
            }
            $data["prioridad"] = $priority;
        }
    
        // Validación y asignación de estado
        if (isset($request["state"])) {
            $state = trim($request["state"]);
            $estados_permitidos = ['Abierta','Resuelta','En espera'];
            if (!in_array($state, $estados_permitidos)) {
                throw new ApiError("state must be one of: " . implode(', ', $estados_permitidos));
            }
            $data["estado"] = $state;
        }
	    $where = "num = ".$ticketNum;

		$ticket_num = CocoDB::updateRecords("tickets", $data, $where,null, ["ignoreSchema" => true]);

		API::success([
			"message" => "Ticket actualizado correctamente",
			"ticket_num" => $ticketNum
		]);


    }    
    
    static function notifyAdminTicket(string $ticketNum, string $title, string $body, int $userNum): void
    {
        try {
            // Obtener configuración de emails de administradores
            $adminConfig = CocoDB::get("configuracion", "", "num desc", 1);
            
            if (empty($adminConfig) || !is_array($adminConfig)) {
                error_log("Error: No se pudo obtener la configuración de emails de administradores");
                return;
            }
            
            $emailsString = $adminConfig[0]['correo_dev'] ?? '';
            
            if (empty($emailsString)) {
                error_log("Warning: No hay emails de administradores configurados");
                return;
            }
            
            // Limpiar y validar emails
            $emails = self::parseAndValidateEmails($emailsString);
            
            if (empty($emails)) {
                error_log("Warning: No se encontraron emails válidos de administradores");
                return;
            }
            
            // Preparar datos del email una sola vez
            $emailData = [
                "cuerpo" => "NEW_TICKET",
                "titulo" => " | #{$ticketNum} | {$title}",
                "mensaje" => $body,
                "uid" => $userNum,
                "dominio" => "quantumasis.com"
            ];
            
            // Enviar emails
            self::sendBulkEmails($emails, $emailData);
            
        } catch (Exception $e) {
            error_log("Error en notifyAdminTicket: " . $e->getMessage());
        }
    }
    
    /**
     * Parsea y valida una lista de emails separados por comas
     */
    private static function parseAndValidateEmails(string $emailsString): array
    {
        $emails = array_map('trim', explode(',', $emailsString));
        
        return array_filter($emails, function($email) {
            return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
        });
    }
    
    /**
     * Envía emails en lote a múltiples destinatarios
     */
    private static function sendBulkEmails(array $emails, array $baseEmailData): void
    {
        foreach ($emails as $email) {
            try {
                $emailData = array_merge($baseEmailData, ["email" => $email]);
                self::sendEmail($emailData);
            } catch (Exception $e) {
                error_log("Error enviando email a {$email}: " . $e->getMessage());
                // Continúa con los demás emails
            }
        }
    }
    // Endpoint para listar los tickets de un usuario
    public static function getTickets($request) {
        // Verificamos que los parámetros obligatorios estén presentes en la solicitud
        if (!@$request["user"]) {
            throw new ApiError("user es obligatorio");
        }
    
        // Variables a partir de los parámetros de la solicitud
        $userNum = intval($request["user"]);
        $order = @$request["order"] ?: 'desc';  // Por defecto 'desc'
        if (isset($request["order"]) && !isset($request["order_field"])) {
            $orderField = 'createdDate';
        } else {
            $orderField = @$request["order_field"] ?: 'num';
        }
        $filterTerm = @$request["filterTerm"] ?: '';  // Por defecto ''
        $filterDomain = @$request["filterDomain"] ?: '';  // Por defecto ''
        //$range = @$request["range"] ?: [date("d-m-Y"), date("d-m-Y")];
        $limit = @$request["limit"] ?: 50;
        $offset = @$request["offset"] ?: 0;
        // Filtro por estado
        $state = @$request["state"];  // Estado que se pasa en la solicitud
        // Obtener el chat_conf_num desde la base de datos
        $chatConfNumResult = CocoDB::get("chatbots", "usuario = ".$userNum);
        if (!$chatConfNumResult) {
            throw new ApiError("No se pudo obtener el chat_conf_num para el usuario.");
        }
    

        // Construir el WHERE correctamente como string
        $where = [];
        // Filtro por número de usuario
        if ($userNum) {
            $chatConfNum = $chatConfNumResult[0]['chat_conf_num'];
            if ($chatConfNum != 87) {//solo para que quantum lea todas
                $where[] = "chat_conf_num = $chatConfNum";
            }
        }
        
        // Filtro por filtro de término en el título (con LIKE)
        if ($filterTerm) {
            $where[] = "titulo LIKE '%" . addslashes($filterTerm) . "%'";
        }
        
        // Filtro por filtro de término en el dominio (con LIKE)
        if ($filterDomain) {
            $chatConfNumResult = CocoDB::get("chat_configuraciones", "dominio LIKE '%" . addslashes($filterDomain) . "%'");
            if ($chatConfNumResult) {
                $nums = array_column($chatConfNumResult, 'num');
                if (!empty($nums)) {
                    $where[] = "chat_conf_num IN (" . implode(',', $nums) . ")";
                }
            }
        }

        
        // Filtro por rango de fechas
        /*if ($range) {
            $startDate = date("Y-m-d", strtotime($range[0]));  // Fecha de inicio
            $endDate = isset($range[1]) ? date("Y-m-d", strtotime($range[1])) : $startDate;
            $where[] = "createdDate >= '$startDate 00:00:00' AND createdDate <= '$endDate 23:59:59'";
        }*/
        
        // Filtro por estado si se pasa en la solicitud
        if ($state) {
            $where[] = "estado = '$state'";
        }
        
        // Si el array `$where` tiene elementos, concatenarlos con 'AND'
        $whereStr = count($where) > 0 ? implode(" AND ", $where) : "1=1";
        $orderClause = "$orderField $order";

        // Si el ordenamiento es por 'prioridad', hacemos un ORDER BY especial
        if ($orderField == 'prioridad') {
            if (strtolower($order) == 'asc') {
                $orderClause = "FIELD(prioridad, 'Critica', 'Urgente', 'Normal', 'Baja') ASC";
            } else {
                $orderClause = "FIELD(prioridad, 'Baja', 'Normal', 'Urgente', 'Critica') ASC"; // Nota: sigue siendo ASC porque invertimos la lista
            }
        } elseif ($orderField == 'createdDate') {
            // Ordenar por el último mensaje del ticket
            $orderClause = "(SELECT MAX(tm.createdDate) FROM cms_tickets_mensajes tm WHERE tm.ticket_num = cms_tickets.num) $order";
        } 
        
        // Llamar a la función get() de CocoDB para obtener los tickets
        //throw new ApiError(json_encode($whereStr));
        $tickets = CocoDB::get("tickets", $whereStr, "$orderClause", [ "limit" => $limit,
            "offset" => $offset], [
            "ignoreSchema" => true
        ]);
        
        // Llamar a la función get() de CocoDB para obtener los tickets
        $ticketsCount =  mysql_query_fetch_all_assoc("SELECT COUNT(*) as total FROM cms_tickets WHERE " . $whereStr);
        
        // devolver los tickets
        if ($tickets) {

            foreach ($tickets as &$ticket) {
                $ticketNum = intval($ticket['num']);
                $mensaje = CocoDB::get("tickets_mensajes", "ticket_num = $ticketNum", "createdDate DESC", [
                    "limit" => 1
                ], [
                    "ignoreSchema" => true
                ]);
                // Si hay mensaje, comparamos el user_num
                if ($mensaje && count($mensaje) > 0) {
                    $ticket['ultima_respuesta_es_mia'] = ($mensaje[0]['user_num'] == $userNum);
                } else {
                    $ticket['ultima_respuesta_es_mia'] = false;
                }
                if (@$mensaje[0]){
                    $ticket['mensaje'] = $mensaje[0]['mensaje'];
                    $ticket["mensaje_date"] = $mensaje[0]['createdDate'];
                }
                
                // Consultar el dominio para el `userNum` del ticket actual
                $chatConfNumResult = CocoDB::get("chatbots", "usuario = ".$ticket['agente']);
                if ($chatConfNumResult) {
                    $chatConfNum = $chatConfNumResult[0]['chat_conf_num'];
    
                    // Obtener el dominio en base al chatConfNum de cada ticket
                    $chatConfiguraciones = CocoDB::get("chat_configuraciones", "num = ".$chatConfNum);
                    if ($chatConfiguraciones) {
                        $ticket['dominio'] = $chatConfiguraciones[0]['dominio'];  // Añadir dominio a cada ticket
                    }
                }
            }
            API::success(["tickets" => $tickets,"ticketsCount" => $ticketsCount[0]]);
        } else {
            throw new ApiError("No se encontraron tickets para el usuario.");
        }
    }

    static function addVideoCall($request){
        if (!@$request["domain"] || !@$request["chat_id"]) {
            throw new ApiError("domain and chat_id are needed. ".json_encode($request));
        }

        $response = CocoDB::insertRecords("cocovideochats", [
            "chat_id" => intval($request["chat_id"]),
            "website" => $request["domain"],
            "createdDate" => date("Y-m-d H:i:s")
        ], null, ["return_last_id" => 1, "prefix" => "", "ignoreSchema" => true]);
        if (@$response["error"]) {
            throw new ApiError($response["message"]);
        }

        return $response;
    }

    static function traspaseChats($request){
        if (!@$request["domain"] || !@$request["from_inbox"] || !@$request["to_inbox"] || !isset($request["type"])){
            throw new ApiError("domain, from_inbox, to_inbox and type are needed. ".json_encode($request));
        }

        // type puede ser: "0" fuera de horario, "1" todas las conversaciones
        
        $chats = self::getChats([
            "domain" => $request["domain"],
            "status" => $request["from_inbox"],
            "type" => $request["type"],
            "dates" => @$request["dates"],
            "dateAgent" => @$request["dateAgent"],
            "returnResult" => true
        ]);

        if (!@$chats || count($chats) == 0){
            API::success(["success" => 0,"total_chats" => 0,"message" => "No se encontraron chats para traspasar."]);
        }
        $chatsNums_all = array_map(function($chat){
            return $chat["chat_num"];
        },$chats);

        // Filtrar fuera de horario
        if ($request["type"] == "0") {
            $filteredChats = CocoDB::get("cocochats u", "u.num IN (".join(",", $chatsNums_all).")", null, null, [
                "prefix" => "", 
                "ignoreSchema" => true,
                "aggregates" => [
                    "(SELECT ch.message_type FROM cocochats_mensajes ch WHERE ch.chat_id = u.num and ch.origin = 'assistant' ORDER BY ch.num desc LIMIT 1) as lastMessageAssistant"
                ]
            ]);
            // Filtramos los chats que tienen el último mensaje del asistente como "schedule"
            $filteredChats = array_filter($filteredChats, function($chat) {
                return $chat["lastMessageAssistant"] == "schedule"; // Solo chats con mensajes fuera de horario
            });
            $chats = @$filteredChats ?: [];

            $chatsNums_aux = array_map(function($chat){
                return $chat["num"];
            },$chats);

            if (@$chatsNums_aux){
                $resultUpdate = CocoDB::updateRecords("cocochats", [
                    "status" => $request["to_inbox"],
                    "updatedDate" => date("Y-m-d H:i:s")
                ], "num IN (".join(",",$chatsNums_aux).")", null, ["prefix" => "","ignoreSchema" => true]);
            }else{
                $resultUpdate = 0;
            }
        }else{
            if (@$chatsNums_all){
                $resultUpdate = CocoDB::updateRecords("cocochats", [
                        "status" => $request["to_inbox"],
                    "updatedDate" => date("Y-m-d H:i:s")
                ], "num IN (".join(",",$chatsNums_all).")", null, ["prefix" => "","ignoreSchema" => true]);
            }else{
                $resultUpdate = 0;
            }
        } 

        API::success(["success" => $resultUpdate,"total_chats" => count($chats),"message" => count($chats) ? count($chats)." Chats traspasados correctamente." : "No se encontraron chats para traspasar."]);

    }


	static function createTicketMessage($request){
		
		if ( !@$request["body"] || !@$request["user"] || !@$request["ticketNum"] ) {
			throw new ApiError("body, ticketNum and user are required");
		}

		$body = trim($request["body"]);
		$userNum = intval($request["user"]);
		$ticketNum = intval($request["ticketNum"]);
		$img_uri = isset($request["archivoUrl"]) ? trim($request["archivoUrl"]) : "";

        $ticket = CocoDB::get("tickets", "num = $ticketNum", null, null, [
            "ignoreSchema" => true
        ]);
        if (!$ticket) {
            throw new ApiError("Ticket no encontrado");
        }
    
        $estadoActual = $ticket[0]['estado'];
    
        if (in_array($estadoActual, ['Resuelta', 'En espera'])) {
            CocoDB::updateRecords("tickets", [
                "estado" => "Abierta",
                "updatedDate" => date("Y-m-d H:i:s"),
                "updatedByUserNum" => $userNum
            ], "num = $ticketNum", null, ["ignoreSchema" => true]);
        }

		$data = [
			
				"ticket_num" => $ticketNum,
				"user_num" => $userNum,
				"mensaje" => $body,
				"archivo_url" => $img_uri,
				"dragSortOrder" => date("mdHis"),
				"createdDate" => date("Y-m-d H:i:s"),
				"updatedDate" => date("Y-m-d H:i:s"),
				"createdByUserNum" => $userNum,
				"updatedByUserNum" => $userNum
			
		];
		$ticket_message_num = CocoDB::insertRecords("tickets_mensajes", $data, null, [
			"return_last_id" => 1,
			"prefix" => "cms_",
			"ignoreSchema" => true
		]);
		if (!$ticket_message_num) {
			throw new ApiError("Error Actualizar la incidencia");
		}
		
        self::notifyAdminTicket($ticketNum,$ticket[0]['titulo'],$body,$userNum);
		
		API::success([
			"message" => "Mensaje creado correctamente",
			"ticket_num" => $ticketNum,
			"ticket_message_num" => $ticket_message_num
		]);


    }    
	
	
    // Endpoint para listar los mensajes de los tickets de unon pasado por param
    public static function getTicketsMessages($request) {
        
        // Verificamos que los parámetros obligatorios estén presentes en la solicitud
        if (!@$request["ticket_num"]) {
            throw new ApiError("ticket_num es obligatorio");
        }
    
        // Variables a partir de los parámetros de la solicitud
        $userNum = intval($request["user"]);
        $ticketNum = intval($request["ticket_num"]);
        $order = @$request["order"] ?: 'desc';  // Por defecto 'desc'
        $orderField = @$request["order_field"] ?: 'num';  // Por defecto 'createdDate'
    

        // Filtro por número de usuario
        if ($ticketNum) {
            $whereStr = "ticket_num = $ticketNum";
        }
        
        try{
            // Llamar a la función get() de CocoDB para obtener los tickets
            $mensajes = CocoDB::get("tickets_mensajes", $whereStr, "$orderField $order", 0, ["ignoreSchema" => true]);
		} catch (Exception $e) {
            throw new ApiError("Ha ocurrido un error al obtener los datos, intentelo de nuevo.");
        }
        // devolver las incidencias
        if ($mensajes) {
            API::success($mensajes);
        } else {
            throw new ApiError("No se encontraron mensajes para la incidencia.");
        }
    }

    static function resetAccount($request, $returnResult = false){
        if (!@$request["chat_conf_num"]){
            throw new ApiError("chat_conf_num needed");
        }

        $chat_conf_num = intval($request['chat_conf_num']);

        CocoDB::updateRecords("canales", ["activo" => 0], "chat_conf_num = " . $chat_conf_num, null, []);
        CocoDB::updateRecords("chatbots", ["activo" => 0], "chat_conf_num = " . $chat_conf_num . " and rol = 2", null, []);
        if ($returnResult){
            return true;
        }else{
            API::success(["message" => "Channels and agents disabled successfully."]);
        }
        
    }

    static function getQuantumPlanData(&$filteredUser){
        $filteredUser["appConfig"] = [
            "showChatTabs" => ["info","ia"]
        ];

        $filteredUser["userPayConfig"] = [
            "maxAgentes" => 0,
            "maxCanales" => 0,
            "maxTokens" => 0,
            "maxVideochat" => 0,
            "maxConversaciones" => 0,
            "maxCampanas" => 0,
            "iaLevel" => 0,
            "supportLevel" => 0,
            "freeDays" => 0,
            "productos" => []
        ];
        $filteredUser["userCount"] = [
            "agentesRestantes" => 0,
            "canalesRestantes" => 0,
            "videochatRestantes" => 0,
            "tokensRestantes" => 0,
            "conversacionesRestantes" => 0,
            "diasRestantes" => 0
        ];
        $contrataciones = CocoDB::get("contrataciones","chat_conf_num='".$filteredUser["chatbot_conf"]."' and suscripcion='activa'",null,null,[]);
        $contratacionPlan = null;
        if (@$contrataciones){
            foreach($contrataciones as $key => $contratacion){
                if (@$contratacion["plan_bd"]){
                    $filteredUser["userPayConfig"]["maxAgentes"] += intval(@$contratacion["plan_bd"][0]["agentes"]);
                    $filteredUser["userPayConfig"]["maxCanales"] += intval(@$contratacion["plan_bd"][0]["canales"]);
                    $filteredUser["userPayConfig"]["maxVideochat"] += intval(@$contratacion["plan_bd"][0]["videochat"]);
                    $filteredUser["userPayConfig"]["maxTokens"] += (intval(@$contratacion["plan_bd"][0]["tokens"]) * 1000000); // Asumimos que el límite es en millones de tokens
                    $filteredUser["userPayConfig"]["maxConversaciones"] += intval(@$contratacion["plan_bd"][0]["conversaciones"]);
                    $filteredUser["userPayConfig"]["maxCampanas"] += intval(@$contratacion["plan_bd"][0]["campanas"]);
                }
                if (@$contratacion["subproducto_bd"]){
                    $filteredUser["userPayConfig"]["productos"][] = [
                        "producto_id" => @$contratacion["subproducto_bd"][0]["producto_id"],
                        "limite" => intval(@$contratacion["subproducto_bd"][0]["producto_limit"]),
                        "tipo" => @$contratacion["subproducto_bd"][0]["producto_type"],
                    ];
                    switch($contratacion["subproducto_bd"][0]["producto_id"]){
                        case "agent":
                            $filteredUser["userPayConfig"]["maxAgentes"] += intval(@$contratacion["subproducto_bd"][0]["producto_limit"]);
                            break;
                        case "channel":
                            $filteredUser["userPayConfig"]["maxCanales"] += intval(@$contratacion["subproducto_bd"][0]["producto_limit"]);
                            break;
                        case "videochat":
                            $filteredUser["userPayConfig"]["maxVideochat"] += intval(@$contratacion["subproducto_bd"][0]["producto_limit"]);
                            break;
                        case "tokens":
                            $filteredUser["userPayConfig"]["maxTokens"] += (intval(@$contratacion["subproducto_bd"][0]["producto_limit"]) * 1000000); // Asumimos que el límite es en millones de tokens
                            break;
                        case "chats":
                            $filteredUser["userPayConfig"]["maxConversaciones"] += intval(@$contratacion["subproducto_bd"][0]["producto_limit"]);
                            break;
                    } 
                }
                if (@$contratacion["plan_bd"][0]["ia"]){
                    $filteredUser["userPayConfig"]["iaLevel"] = max($filteredUser["userPayConfig"]["iaLevel"], intval(@$contratacion["plan_bd"][0]["ia"]));
                }
                if (@$contratacion["plan_bd"][0]["soporte"]){
                    $filteredUser["userPayConfig"]["supportLevel"] = max($filteredUser["userPayConfig"]["supportLevel"], intval(@$contratacion["plan_bd"][0]["soporte"]));
                }
                if (@$contratacion["plan_bd"][0]){
                    $contratacionPlan = $contratacion;
                }
                if (@$contratacion["plan_bd"][0]["dias_gratis"]){
                    $filteredUser["userPayConfig"]["freeDays"] += intval(@$contratacion["plan_bd"][0]["dias_gratis"]);
                }
            }
        }

        if (!$contratacionPlan){
            // Si no hay contrataciones activas, asignamos un plan gratuito por defecto
            $contratacion = [
                "usuario" => $filteredUser["num"],
                "chat_conf_num" => $filteredUser["chatbot_conf"],
                "plan" => QUANTUM_PLAN_GRATUITO, // Plan gratuito por defecto
                "suscripcion" => "activa",
            ];
            
            CocoDB::insertRecords("contrataciones", $contratacion, null);
            $contratacionPlan = CocoDB::get("contrataciones","chat_conf_num='".$filteredUser["chatbot_conf"]."' and suscripcion='activa'",null,null,[])[0];
        }
        
        // Asignamos los valores del plan contratado
        $fechaContratacion = @$contratacionPlan["createdDate"];
        $fechaActual = date("Y-m-d H:i:s");
        $fechaContratacion = strtotime($fechaContratacion);
        $fechaActual = strtotime($fechaActual);
        
        if ($contratacionPlan["plan"] == QUANTUM_PLAN_GRATUITO){
            $diferenciaDias = ceil(($fechaActual - $fechaContratacion) / (60 * 60 * 24)) - 1; // Restamos 1 día para no contar el día de la contratación
            $filteredUser["userCount"]["diasRestantes"] = max(0, $filteredUser["userPayConfig"]["freeDays"] - $diferenciaDias);
        }else{
            // calculamos la fecha del dia 1 del mes siguiente al mes actual
            $fechaRenovacion = date("Y-m-d H:i:s", strtotime("first day of next month", $fechaActual));
            $fechaRenovacion = strtotime($fechaRenovacion);
            $diferenciaDias = ceil(($fechaRenovacion - $fechaActual) / (60 * 60 * 24)) +1; // +1 para dar margen de 2 días 
            $filteredUser["userCount"]["diasRestantes"] = max(0, $diferenciaDias);
        }

        $filteredUser["userCount"]["agentesRestantes"] = $filteredUser["userPayConfig"]["maxAgentes"] - count(
            CocoDB::get("chatbots","chat_conf_num='".$filteredUser["chatbot_conf"]."' and activo = 1",null,null,["ignoreSchema" => true]));
        $filteredUser["userCount"]["canalesRestantes"] = $filteredUser["userPayConfig"]["maxCanales"] - count(
            CocoDB::get("canales","chat_conf_num='".$filteredUser["chatbot_conf"]."' and activo = 1",null,null,["ignoreSchema" => true]));

        $fechaInicioMes = date("Y-m-01 00:00:00");
        $fechaFinMes = date("Y-m-t 23:59:59");

        // Calculamos los tokens restantes del mes en cuestion
        $tokensUsados = CocoDB::get("cocotokens","website='".$filteredUser["dominio"]."' and createdDate >= '$fechaInicioMes' and createdDate <= '$fechaFinMes'",null,null,["prefix" => "","ignoreSchema" => true]);
        $tokensUsados = @$tokensUsados ? array_sum(array_column($tokensUsados, 'tokens')) : 0;
        $filteredUser["userCount"]["tokensRestantes"] = $filteredUser["userPayConfig"]["maxTokens"] + $tokensUsados;
    
        // Calculamos los videochats restantes del mes en cuestion
        $filteredUser["userCount"]["videochatRestantes"] = $filteredUser["userPayConfig"]["maxVideochat"] - count(
            CocoDB::get("cocovideochats","website='".$filteredUser["dominio"]."' and createdDate >= '$fechaInicioMes' and createdDate <= '$fechaFinMes'",null,null,["prefix" => "","ignoreSchema" => true]));

        // Calculamos las conversaciones restantes del mes en cuestion
        $totalConversaciones = 0;
        $sql = "
            SELECT 
                SUM(c.startMessages) AS Total
            FROM cocochats c
            WHERE 
                c.website = '".$filteredUser["dominio"]."'
            AND
                (SELECT count(cm.num) FROM cocochats_mensajes cm WHERE cm.chat_id = c.num AND cm.createdDate >= '$fechaInicioMes' AND cm.createdDate <= '$fechaFinMes') > 0
        ";
        
        $conversaciones = @mysql_query_fetch_all_assoc($sql)[0]["Total"];
        $totalConversaciones = @$conversaciones ? $conversaciones : 0;
        $filteredUser["userCount"]["conversacionesRestantes"] = $filteredUser["userPayConfig"]["maxConversaciones"] - $totalConversaciones;

        // Calculamos las campañas restantes del mes en cuestion
        $totalCampanas = 0;
        $sql = "
            SELECT 
                COUNT(c.num) AS Total
            FROM campaigns c
            WHERE 
                c.chat_conf_num = '".$filteredUser["chatbot_conf"]."'
            AND
                c.createdDate >= '$fechaInicioMes' AND c.createdDate <= '$fechaFinMes'
        ";

        $campanas = @mysql_query_fetch_all_assoc($sql)[0]["Total"];
        $totalCampanas = @$campanas ? $campanas : 0;
        $filteredUser["userCount"]["campanasRestantes"] = $filteredUser["userPayConfig"]["maxCampanas"] - $totalCampanas;

        // Definimos si el usuario tiene un plan activo para limitar el acceso a los chats si no lo tiene
        $filteredUser["plan"] = intval(@$contratacionPlan["plan"]);
        $filteredUser["userActivePlanAccount"] = $filteredUser["userCount"]["diasRestantes"] > 0;

        if (@$filteredUser["userCount"]["agentesRestantes"] < 0 || @$filteredUser["userCount"]["canalesRestantes"] < 0){
           // self::resetAccount(["chat_conf_num" => $filteredUser["chatbot_conf"]], true);
            //self::getQuantumPlanData($filteredUser);
           // $filteredUser["accountRestored"] = true;
        }

        // Añadimos el total de conversaciones al usuario
        $sql = "
            SELECT COUNT(*) as Total
            FROM cocochats ch
            WHERE ch.website = '".$filteredUser["dominio"]."' and trashed = 0 and chatType = 'default'
        ";
        
        $chatsCount = @mysql_query_fetch_all_assoc($sql)[0]["Total"];
        $filteredUser["chats_count"] = $chatsCount ? intval($chatsCount) : 0;
    }
    
    static function changeSatisfactionSurvey($request){
		global $TABLE_PREFIX;
		if (!@$request["domain"] || !@$request["chat_num"] || !isset($request["sendSatisfactionSurvey"])){
			throw new ApiError("chat_num and domain and sendSatisfactionSurvey needed");
		} 

		$chatNum = @$request["chat_num"];
		$sendSatisfactionSurvey = @$request["sendSatisfactionSurvey"] ? 1 : 0; // Convertir a entero
		$domain = @$request["domain"];

		$resultUpdate = CocoDB::updateRecords("cocochats",["sendSatisfactionSurvey" => $sendSatisfactionSurvey],"num=".$chatNum." and website = '".mysql_real_escape_string($domain)."'",null,["prefix" => "","ignoreSchema" => true]);

		API::success(["result" => $resultUpdate, "chat_num" => $chatNum, "sendSatisfactionSurvey" => $sendSatisfactionSurvey]);
	}

    static function getCampaignsLogs($request){
        $limit = isset($request['limit']) ? intval($request['limit']) : 100;
        $offset = isset($request['offset']) ? intval($request['offset']) : 0;
    
        $where = "1=1";

        if (isset($request["pending_chat_conf"])){
            $result = mysql_query_fetch_all_assoc("SELECT DISTINCT chat_conf_num FROM campaigns_send_log WHERE status = 'esperando'");
            API::success($result);
        }
        
        if (isset($request["chat_conf_num"])) {
            $chat_conf_num = intval($request['chat_conf_num']);
            $where .= " AND chat_conf_num = " . $chat_conf_num;
        }
        
        if (isset($request['campaign_num'])) {
            $campaign_num = intval($request['campaign_num']);
            $where .= " AND campaign_num = " . $campaign_num;
        }
        
        if (isset($request['status'])) {
            $status = $request['status'];
            $valid_statuses = ['esperando', 'enviado', 'error'];
            if (in_array($status, $valid_statuses)) {
                $where .= " AND status = '" . $status . "'";
            }
        }
    
        $logs = CocoDB::get("campaigns_send_log", $where, "sendDate ASC", ["limit" => $limit, "offset" => $offset], ["prefix" => "", "ignoreSchema" => true]);
        $totalLogs = mysql_query_fetch_all_assoc("SELECT COUNT(*) as total FROM campaigns_send_log WHERE " . $where);
    
        API::success([
            "logs" => $logs,
            "totalLogs" => isset($totalLogs[0]['total']) ? intval($totalLogs[0]['total']) : 0
        ]);
    }

    static function getCampaigns($request){
        if (!@$request["chat_conf_num"]){
            throw new ApiError("chat_conf_num needed");
        }

        $chat_conf_num = intval($request['chat_conf_num']);
        $limit = isset($request['limit']) ? intval($request['limit']) : 100;
        $offset = isset($request['offset']) ? intval($request['offset']) : 0;

        $where = "chat_conf_num = " . $chat_conf_num;    
        if (isset($request['campaign_num'])) {
            $campaign_num = intval($request['campaign_num']);
            $where .= " AND num = " . $campaign_num;
        }

        $campaigns = CocoDB::get("campaigns", $where, "createdDate DESC", ["limit" => $limit, "offset" => $offset], ["prefix" => "", "ignoreSchema" => true]);
        foreach($campaigns as $key => $campaign){
            $totalLogs = mysql_query_fetch_all_assoc("SELECT COUNT(*) as total FROM campaigns_send_log WHERE campaign_num = " . intval($campaign['num']));
            $totalSendedLogs = mysql_query_fetch_all_assoc("SELECT COUNT(*) as total FROM campaigns_send_log WHERE campaign_num = " . intval($campaign['num']) . " AND status = 'enviado'");
            $totalErrorLogs = mysql_query_fetch_all_assoc("SELECT COUNT(*) as total FROM campaigns_send_log WHERE campaign_num = " . intval($campaign['num']) . " AND status = 'error'");
            $campaigns[$key]['totalLogs'] = isset($totalLogs[0]['total']) ? intval($totalLogs[0]['total']) : 0;
            $campaigns[$key]['totalSended'] = isset($totalSendedLogs[0]['total']) ? intval($totalSendedLogs[0]['total']) : 0;
            $campaigns[$key]['totalErrors'] = isset($totalErrorLogs[0]['total']) ? intval($totalErrorLogs[0]['total']) : 0;
        }
        API::success($campaigns);
    }
    
    static function createCampaign($request){
        if (!@$request["chat_conf_num"]){
            throw new ApiError("chat_conf_num needed");
        }
        if (!@$request["title"]){
            throw new ApiError("title needed");
        }
        if (!@$request["channel_num"]){
            throw new ApiError("channel_num needed");
        }
    
        $chat_conf_num = intval($request['chat_conf_num']);
        $channel_num = intval($request['channel_num']);
        $title = $request['title'];
        $message = $request['message'] ?? '';
        $sendDate = $request['sendDate'] ?? date('Y-m-d H:i:s');
        $recipients = $request['recipients'] ?? [];
        $vars = $request['vars'] ?? null;
        
        if (empty($recipients)) {
            throw new ApiError("recipients needed (array of phones)");
        }
    
        $chatConfData = CocoDB::get(
            "chat_configuraciones",
            "num = " . $chat_conf_num,
            null,
            null,
            ["prefix" => "cms_", "ignoreSchema" => true]
        );
        
        if (empty($chatConfData)) {
            throw new ApiError("Chat configuration not found for chat_conf_num: " . $chat_conf_num);
        }
        
        $domain = $chatConfData[0]['dominio'];
    
        $campaignData = [
            'chat_conf_num' => $chat_conf_num,
            'channel_num' => $channel_num,
            'title' => $title,
            'message' => $message,
            'sendDate' => $sendDate,
            'domain' => $domain,
            'status' => 'esperando',
            'createdDate' => date('Y-m-d H:i:s'),
            'updatedDate' => date('Y-m-d H:i:s'),
            'createdByUserNum' => $request['user_num'] ?? null
        ];
    
        $campaign_num = CocoDB::insertRecords("campaigns", $campaignData, null, [
            "return_last_id" => 1,
            "prefix" => "",
            "ignoreSchema" => true
        ]);
    
        if (!$campaign_num) {
            throw new ApiError("Error creating campaign");
        }
    
        $logsCreated = 0;
        foreach ($recipients as $recipient) {
            if (is_array($recipient)) {
                $phone = $recipient['phone'] ?? '';
                $name = $recipient['name'] ?? null;
                $processedTemplate = $recipient['processedTemplate'] ?? null;
            } else {
                $phone = $recipient;
                $name = null;
                $processedTemplate = null;
            }
            
            $finalMessage = $processedTemplate ? json_encode($processedTemplate) : $message;
            
            $logData = [
                'campaign_num' => $campaign_num,
                'chat_conf_num' => $chat_conf_num,
                'channel_num' => $channel_num,
                'domain' => $domain,
                'phone' => $phone,
                'name' => $name,
                'value' => $finalMessage,
                'status' => 'esperando',
                'sendDate' => $sendDate,
                'createdDate' => date('Y-m-d H:i:s'),
                'lang' => $request['lang'] ?? 'es',
                'vars' => @$recipient["vars"] ?: $vars
            ];
            
            $logInserted = CocoDB::insertRecords("campaigns_send_log", $logData, null, [
                "return_last_id" => 1,
                "prefix" => "",
                "ignoreSchema" => true
            ]);
            if ($logInserted) {
                $logsCreated++;
            }
        }
    
        API::success([
            'campaign_num' => $campaign_num,
            'logs_created' => $logsCreated,
            'message' => 'Campaign created successfully'
        ]);
    }
    
    static function processMessageWithVariables($message, $csvData, $selectedVariables) {
        $messageObj = json_decode($message, true);
        if (!$messageObj) {
            return $message;
        }
        
        if (isset($messageObj['components'])) {
            foreach ($messageObj['components'] as &$component) {
                if ($component['type'] === 'BODY' && isset($component['text'])) {
                    $component['text'] = self::replaceVariablesInText($component['text'], $csvData, $selectedVariables);
                }
            }
        }
        
        if (isset($messageObj['text'])) {
            $messageObj['text'] = self::replaceVariablesInText($messageObj['text'], $csvData, $selectedVariables);
        }
        
        return json_encode($messageObj);
    }
    
    static function processMessageWithManualParams($message, $templateParams) {
        $messageObj = json_decode($message, true);
        if (!$messageObj) {
            return $message;
        }
        
        if (isset($messageObj['components'])) {
            foreach ($messageObj['components'] as &$component) {
                if ($component['type'] === 'BODY' && isset($component['text'])) {
                    $component['text'] = self::replaceManualParamsInText($component['text'], $templateParams);
                }
            }
        }
        
        if (isset($messageObj['text'])) {
            $messageObj['text'] = self::replaceManualParamsInText($messageObj['text'], $templateParams);
        }
        
        return json_encode($messageObj);
    }
    
    static function replaceVariablesInText($text, $csvData, $selectedVariables) {
        foreach ($selectedVariables as $variable) {
            $column = $variable['column'];
            $variableNum = $variable['variable'];
            $value = isset($csvData[$column]) ? $csvData[$column] : '';
            $text = str_replace($variableNum, $value, $text);
        }
        return $text;
    }
    
    static function replaceManualParamsInText($text, $templateParams) {
        foreach ($templateParams as $paramNum => $value) {
            $placeholder = '{{' . $paramNum . '}}';
            $text = str_replace($placeholder, $value, $text);
        }
        return $text;
    }

    static function createOrUpdateContact($request) {
        if (!@$request["chat_conf_num"] || (!@$request["telefono"] && !@$request["email"] && !@$request["id"])) {
            throw new ApiError("chat_conf_num and (telefono or email or id) are required");
        }
        
        // Usar getOrCreateContact internamente
        $contact = self::getOrCreateContact(
            $request,
            intval($request['chat_conf_num'])
        );
        
        if (!$contact) {
            throw new ApiError("Error creating or updating contact");
        }
        
        API::success([
            'message' => 'Contact created or updated',
            'num' => $contact['num']
        ]);
    }
    
    static function updateCampaignLog($request) {
        if (!@$request["log_num"] || !@$request["status"]) {
            throw new ApiError("log_num and status are required");
        }
        
        $log_num = intval($request['log_num']);
        $status = $request['status'];
        $error_message = $request['error_message'] ?? null;
        $resultData = @$request['resultData'] ?? null;
        $errorData = @$request['errorData'] ?? null;

        $campaignLog = @CocoDB::get(
            "campaigns_send_log",
            "num = " . $log_num,
            null,
            null,
            ["prefix" => "", "ignoreSchema" => true]
        )[0];

        if (empty($campaignLog)) {
            throw new ApiError("Campaign log not found for log_num: " . $log_num);
        }
        
        $updateData = [
            'status' => $status,
            'updatedDate' => date('Y-m-d H:i:s'),
            'intentos' => $campaignLog['intentos'] + 1,
            'resultData' => $resultData ?? null,
            'errorData' => $errorData ?? null
        ];
        
        if ($error_message) {
            $updateData['error_message'] = $error_message;
        }
        
        CocoDB::updateRecords(
            "campaigns_send_log",
            $updateData,
            "num=".$log_num,
            null,
            ["prefix" => "", "ignoreSchema" => true]
        );
        
        API::success(['message' => 'Log updated']);
    }
    
    static function deleteCampaign($request){
        if (ob_get_level()) {
            ob_clean();
        }
        
        $old_error_reporting = error_reporting();
        error_reporting(E_ERROR | E_PARSE);
        
        try {
            if (!@$request["campaign_num"]){
                API::error("campaign_num needed");
                return;
            }
            if (!@$request["chat_conf_num"]){
                API::error("chat_conf_num needed");
                return;
            }
        
            $campaign_num = intval($request['campaign_num']);
            $chat_conf_num = intval($request['chat_conf_num']);
        
            $campaign = CocoDB::get(
                "campaigns",
                "num = " . $campaign_num . " AND chat_conf_num = " . $chat_conf_num,
                null,
                null,
                ["prefix" => "", "ignoreSchema" => true]
            );
        
            if (empty($campaign)) {
                API::error("Campaign not found or access denied");
                return;
            }
        
            $campaign_data = $campaign[0];
            $campaign_status = $campaign_data['status'] ?? 'pending';
        
            if ($campaign_status === 'enviado' || $campaign_status === 'sent') {
                API::error("Cannot delete campaigns that have already been sent");
                return;
            }
        
            $sent_logs = mysql_query_fetch_all_assoc(
                "SELECT COUNT(*) as total FROM campaigns_send_log 
                 WHERE campaign_num = " . $campaign_num . " 
                 AND status = 'enviado'"
            );
        
            if (isset($sent_logs[0]['total']) && intval($sent_logs[0]['total']) > 0) {
                API::error("Cannot delete campaign: some messages have already been sent");
                return;
            }
        
            $delete_logs = CocoDB::deleteRecords(
                "campaigns_send_log",
                "campaign_num = " . $campaign_num,
                ["prefix" => "", "ignoreSchema" => true]
            );
        
            if (!$delete_logs) {
                API::error("Error deleting campaign logs");
                return;
            }
        
            $delete_campaign = CocoDB::deleteRecords(
                "campaigns",
                "num = " . $campaign_num,
                ["prefix" => "", "ignoreSchema" => true]
            );
        
            if (!$delete_campaign) {
                API::error("Error deleting campaign");
                return;
            }
        
            API::success([
                'message' => 'Campaign deleted successfully',
                'campaign_num' => $campaign_num,
                'deleted_logs' => true
            ]);
        
        } catch (Exception $e) {
            API::error("Error deleting campaign: " . $e->getMessage());
        } finally {
            error_reporting($old_error_reporting);
        }
    }

    static function getChatAnalyticsIA($request){
        if (!@$request["chat_id"]){
            throw new ApiError("chat_id needed");
        }

        $chat_id = intval($request['chat_id']);

        $analytics = CocoDB::get(
            "cocochats_analisis",
            "chat_id = " . $chat_id,
            "num DESC",
            1,
            ["prefix" => "", "ignoreSchema" => true]
        );
        $analytics = @$analytics[0] ? $analytics[0] : null;
        if (@$analytics["data"]){
            $analytics["data"] = json_decode($analytics["data"], true);
            if (is_array($analytics["data"])){
                $analytics["data"]["createdDate"] = $analytics["createdDate"];
            }
        }
        API::success($analytics);
    }

    static function insertOrUpdateChatAnalyticsIA($request){
        if (!@$request["chat_id"]){
            throw new ApiError("chat_id needed");
        }
        if (!@$request["data"]){
            throw new ApiError("data needed");
        }

        $chat_id = intval($request['chat_id']);
        $data = mysql_real_escape_string(json_encode($request['data']));
        $createdDate = date("Y-m-d H:i:s");

        $sql = "INSERT INTO cocochats_analisis (chat_id, data, createdDate)
                VALUES ($chat_id, '$data', '$createdDate')
                ON DUPLICATE KEY UPDATE
                    data = VALUES(data),
                    createdDate = NOW()";

        $result = mysql_query($sql);

        if ($result) {
            API::success(["message" => "Analytics saved successfully", "chat_id" => $chat_id]);
        } else {
            throw new ApiError("Error saving analytics");
        }
    }

    static function searchSimilarEmbeddings($request){
        /**
         * Busca embeddings similares por cosine similarity threshold
         * 
         * Parámetros:
         * - domain: dominio del chatbot
         * - embedding: array de números (vector embedding)
         * - threshold: valor entre 0 y 1 (ej: 0.85 para 85% similar)
         * - type: tipo de embedding a buscar ("QA_PAIR" o "SUMMARY")
         * - limit: máximo de resultados (default: 5)
         */
        
        if (!@$request["domain"] || !@$request["embedding"] || !@$request["threshold"]){
            throw new ApiError("domain, embedding, and threshold needed");
        }
        
        $domain = $request["domain"];
        $currentEmbedding = $request["embedding"]; // Array de números
        $threshold = floatval($request["threshold"]); // Ej: 0.85
        $type = @$request["type"] ?: "QA_PAIR"; // Por defecto buscar Q&A
        $limit = intval(@$request["limit"] ?: 5);
        
        // 1. Obtener chatbot por dominio
        $chatbot = CocoDB::get(
            "chat_configuraciones",
            "dominio = '".mysql_real_escape_string($domain)."'",
            "num desc",
            1,
            ["ignoreSchemas" => true]
        )[0];
        
        if (!@$chatbot){
            throw new ApiError("Chatbot not found for domain: ".$domain);
        }
        
        $chat_conf_num = $chatbot["num"];
        
        // 2. Obtener TODOS los embeddings (training + autolearn) de este chatbot
        
        $trainingData = CocoDB::get("cocochats_training_data","chat_conf_id = ".$chat_conf_num." and enabled = 1","num asc",null,["prefix" => "","ignoreSchemas" => true]);

        $numsTraining = array_map(function($r){
            return $r["num"];
        },$trainingData);

        if (@$numsTraining && count($numsTraining) > 0){
            $whereClause = "training_data_num in (".implode(",",$numsTraining).")";

            // Obtener embeddings de training_data
            $trainingEmbeddings = CocoDB::get(
                "cocochats_training_embeddings",
                $whereClause,
                "num desc",
                null,
                ["prefix" => "", "ignoreSchemas" => true]
            );
        }

        $whereClause = "chat_conf_num = ".intval($chat_conf_num);
        if ($type){
            $whereClause .= " and type = '".mysql_real_escape_string($type)."'";
        }

        // Obtener embeddings de autolearn
        $autolearnEmbeddings = CocoDB::get(
            "cocochats_autolearn_embeddings",
            $whereClause,
            "num desc",
            null,
            ["prefix" => "", "ignoreSchemas" => true]
        );

        // Combinar ambos resultados
        $existingEmbeddings = [];
        if (@$trainingEmbeddings){
            $existingEmbeddings = array_merge($existingEmbeddings, $trainingEmbeddings);
        }
        if (@$autolearnEmbeddings){
            $existingEmbeddings = array_merge($existingEmbeddings, $autolearnEmbeddings);
        }

        if (!@$existingEmbeddings){
            API::success([]);
            return;
        }
        
        // 3. Calcular cosine similarity con cada embedding existente
        $similarResults = [];
        
        foreach($existingEmbeddings as $existing){
            $existingVector = @json_decode($existing["embeddings"], true);
            
            if (!is_array($existingVector) || empty($existingVector)){
                continue; // Saltar si el vector no es válido
            }
            
            // Calcular cosine similarity
            $similarity = self::cosineSimilarity($currentEmbedding, $existingVector);
            
            // Si supera el threshold, añadir a resultados
            if ($similarity >= $threshold){
                $existing["similarity_score"] = round($similarity, 4);
                $similarResults[] = $existing;
            }
        }
        
        // 4. Ordenar por similarity descendente y limitar
        usort($similarResults, function($a, $b){
            return $b["similarity_score"] <=> $a["similarity_score"];
        });
        
        $similarResults = array_slice($similarResults, 0, $limit);
        
        API::success($similarResults);
    }

    /**
     * Calcula cosine similarity entre dos vectores
     * Fórmula: (A · B) / (||A|| * ||B||)
     */
    static function cosineSimilarity($vectorA, $vectorB){
        
        if (!is_array($vectorA) || !is_array($vectorB)){
            return 0;
        }
        
        // Asegurar que tienen la misma longitud
        if (count($vectorA) !== count($vectorB)){
            return 0;
        }
        
        // Calcular producto escalar (A · B)
        $dotProduct = 0;
        foreach($vectorA as $i => $valueA){
            $dotProduct += $valueA * $vectorB[$i];
        }
        
        // Calcular magnitudes ||A|| y ||B||
        $magnitudeA = 0;
        $magnitudeB = 0;
        
        foreach($vectorA as $value){
            $magnitudeA += $value * $value;
        }
        
        foreach($vectorB as $value){
            $magnitudeB += $value * $value;
        }
        
        $magnitudeA = sqrt($magnitudeA);
        $magnitudeB = sqrt($magnitudeB);
        
        // Evitar división por cero
        if ($magnitudeA == 0 || $magnitudeB == 0){
            return 0;
        }
        
        // Retornar cosine similarity
        return $dotProduct / ($magnitudeA * $magnitudeB);
    }


    // Lista bots del chatbot.
    static function getBots($request) {
        $chatConfNum = intval(@$request["chat_conf_num"]);
        if ($chatConfNum <= 0) throw new ApiError("chat_conf_num needed");
        API::success(@CocoDB::get("ia_bots", "chat_conf_num=" . $chatConfNum, "is_default DESC, num DESC", null, ["prefix" => "", "ignoreSchemas" => true]) ?: []);
    }

    //
    static function getBot($request, $returnData = false) {
        $botId = intval(@$request["botId"]);
        if ($botId <= 0) throw new ApiError("botId needed");
        $chatConfNum = intval(@$request["chat_conf_num"]);
        $where = "num=" . $botId . " AND activo=1" . ($chatConfNum > 0 ? " AND chat_conf_num=" . $chatConfNum : "");
        $result = ["bot" => @CocoDB::get("ia_bots", $where, "num desc", 1, ["prefix" => "", "ignoreSchemas" => true])[0] ?: null];
        if ($returnData) return $result;
        API::success($result);
    }

    // Crea un bot nuevo.
    static function createBot($request) {
        $chatConfNum = intval(@$request["chat_conf_num"]);
        if ($chatConfNum <= 0 || !@$request["name"]) throw new ApiError("chat_conf_num and name needed");
        $id = CocoDB::insertRecords("ia_bots", ["chat_conf_num" => $chatConfNum, "name" => $request["name"], "is_default" => 0, "data" => json_encode([["campo" => "initial_config", "valor" => json_encode(["role_message" => "", "rating_prompt" => "", "avatar_selected" => "", "function_endpoint" => "", "functions" => [], "context_filter_endpoint" => "", "context_filter" => []])]]), "activo" => 1, "createdDate" => date("Y-m-d H:i:s"), "updatedDate" => date("Y-m-d H:i:s")], null, ["return_last_id" => 1, "prefix" => "", "ignoreSchema" => true]);
        if (!$id) throw new ApiError("Error creating bot");
        API::success(@CocoDB::get("ia_bots", "num=" . intval($id), "num desc", 1, ["prefix" => "", "ignoreSchemas" => true]) ?: []);
    }

    // Desactiva un bot no-default.
    static function deleteBot($request) {
        $botId = intval(@$request["bot_id"]);
        if ($botId <= 0) throw new ApiError("bot_id needed");
        if (intval(@CocoDB::get("ia_bots", "num=" . $botId, "num desc", 1, ["prefix" => "", "ignoreSchemas" => true])[0]["is_default"]) === 1) throw new ApiError("Cannot delete the default bot");
        API::success(["result" => CocoDB::updateRecords("ia_bots", ["activo" => 0, "updatedDate" => date("Y-m-d H:i:s")], "num=" . $botId . " AND is_default=0", null, ["prefix" => "", "ignoreSchema" => true]) ? 1 : 0]);
    }

    //
    static function getChannelBot($request, $returnData = false) {
        $channelNum = intval(@$request["channelNum"]);
        if ($channelNum <= 0) throw new ApiError("channelNum needed");
        $chatConfNum = intval(@$request["chat_conf_num"]);
        $where = "num=" . $channelNum . ($chatConfNum > 0 ? " AND chat_conf_num=" . $chatConfNum : "");
        $result = ["bot_id" => intval(@CocoDB::get("canales", $where, "num desc", 1, ["ignoreSchema" => true])[0]["bot_id"]) ?: null];
        if ($returnData) return $result;
        API::success($result);
    }

    // Actualiza bot_id del canal.
    static function setChannelBot($request) {
        $channelNum = intval(@$request["channelNum"]); if ($channelNum <= 0 || !array_key_exists("botId", $request)) throw new ApiError("channelNum and botId needed");
        $botId = ($request["botId"] !== null && $request["botId"] !== "" && strtolower(strval($request["botId"])) !== "null") ? intval($request["botId"]) : null; if ($botId !== null && $botId <= 0) $botId = null;
        API::success(["result" => CocoDB::updateRecords("canales", ["bot_id" => $botId, "updatedDate" => date("Y-m-d H:i:s")], "num=" . $channelNum, null, ["ignoreSchema" => true]) ? 1 : 0] + self::getChannelBot(["channelNum" => $channelNum], true));
    }

    //
    static function getDefaultBot($request, $returnData = false) {
        $chatConfNum = intval(@$request["chat_conf_num"]);
        if ($chatConfNum <= 0) throw new ApiError("chat_conf_num needed");
        $result = ["bot" => @CocoDB::get("ia_bots", "chat_conf_num=" . $chatConfNum . " AND activo=1 AND is_default=1", "num desc", 1, ["prefix" => "", "ignoreSchemas" => true])[0] ?: null];
        if ($returnData) return $result;
        API::success($result);
    }

    // Marca un bot como default.
    static function setDefaultBot($request) {
        $botId = intval(@$request["bot_id"]); $chatConfNum = intval(@$request["chat_conf_num"]);
        if ($botId <= 0 || $chatConfNum <= 0) throw new ApiError("bot_id and chat_conf_num needed");
        if (!@self::getBot(["botId" => $botId, "chat_conf_num" => $chatConfNum], true)["bot"]) throw new ApiError("Bot not found");
        CocoDB::updateRecords("ia_bots", ["is_default" => 0, "updatedDate" => date("Y-m-d H:i:s")], "chat_conf_num=" . $chatConfNum, null, ["prefix" => "", "ignoreSchema" => true]);
        CocoDB::updateRecords("ia_bots", ["is_default" => 1, "updatedDate" => date("Y-m-d H:i:s")], "num=" . $botId, null, ["prefix" => "", "ignoreSchema" => true]);
        API::success(["result" => 1, "bot_id" => $botId]);
    }

}
