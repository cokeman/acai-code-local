<?php
/**
 * Script para consolidar chats duplicados por UUID
 *
 * Problema: Varios workers crean chats simultáneamente para el mismo UUID
 * Solución: Mover todos los mensajes al chat principal y eliminar los duplicados
 *
 * Uso:
 *   php consolidate_duplicate_chats.php                    # Modo dry-run (solo muestra qué haría)
 *   php consolidate_duplicate_chats.php --execute          # Ejecuta los cambios
 *   php consolidate_duplicate_chats.php --execute --verbose # Ejecuta con detalle
 */

// Cargar configuración directamente desde settings.dat.php
$settingsFile = __DIR__ . '/../cms/data/settings.dat.php';
if (!file_exists($settingsFile)) {
    die("Error: No se encuentra el archivo de configuración\n");
}
$settingsContent = file_get_contents($settingsFile);
$settingsContent = preg_replace('/^<\?php\s*exit;\s*\?>\s*/s', '', $settingsContent);
$SETTINGS = parse_ini_string($settingsContent, true);

$DB_HOST = $SETTINGS['mysql']['hostname'];
$DB_NAME = $SETTINGS['mysql']['database'];
$DB_USER = $SETTINGS['mysql']['username'];
$DB_PASS = $SETTINGS['mysql']['password'];
$DB_CHARSET = 'utf8mb4';

$DRY_RUN = !in_array('--execute', $argv);
$VERBOSE = in_array('--verbose', $argv);

echo "=== Consolidación de Chats Duplicados ===\n";
echo "Modo: " . ($DRY_RUN ? "DRY-RUN (simulación)" : "EJECUCIÓN REAL") . "\n\n";

try {
    $dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=$DB_CHARSET";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage() . "\n");
}

// Verificar qué tablas existen
$existingTables = [];
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach (['notas_internas', 'cocochats_ratings', 'etiquetas_chats'] as $table) {
    $existingTables[$table] = in_array($table, $tables);
}

// 1. Encontrar UUIDs duplicados
$sql = "
    SELECT uuid, COUNT(*) as total_chats
    FROM cocochats
    GROUP BY uuid
    HAVING COUNT(*) > 1
    ORDER BY total_chats DESC
";
$duplicates = $pdo->query($sql)->fetchAll();

echo "UUIDs duplicados encontrados: " . count($duplicates) . "\n\n";

$totalChatsProcessed = 0;
$totalChatsDeleted = 0;
$totalMessagesMoved = 0;

foreach ($duplicates as $dup) {
    $uuid = $dup['uuid'];
    $totalChats = $dup['total_chats'];

    // 2. Obtener todos los chats de este UUID con su conteo de mensajes
    $sql = "
        SELECT
            c.num as chat_num,
            c.createdDate,
            c.status,
            c.contactNum,
            c.assignedToAgent,
            c.category,
            (SELECT COUNT(*) FROM cocochats_mensajes m WHERE m.chat_id = c.num) as total_mensajes
        FROM cocochats c
        WHERE c.uuid = :uuid
        ORDER BY total_mensajes DESC, c.createdDate ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['uuid' => $uuid]);
    $chats = $stmt->fetchAll();

    if (count($chats) < 2) continue;

    // El chat principal es el que tiene más mensajes
    $mainChat = $chats[0];
    $mainChatNum = $mainChat['chat_num'];
    $duplicateChats = array_slice($chats, 1);

    // Verificar si alguno de los chats tiene status "Resuelta"
    $anyResolved = false;
    foreach ($chats as $chat) {
        if ($chat['status'] === 'Resuelta') {
            $anyResolved = true;
            break;
        }
    }

    echo "--- UUID: $uuid ---\n";
    echo "  Chat principal: #{$mainChatNum} ({$mainChat['total_mensajes']} mensajes, {$mainChat['status']})\n";
    if ($anyResolved && $mainChat['status'] !== 'Resuelta') {
        echo "  -> Se marcará como Resuelta (algún duplicado estaba resuelto)\n";
    }

    foreach ($duplicateChats as $dupChat) {
        $dupChatNum = $dupChat['chat_num'];
        $dupMessages = $dupChat['total_mensajes'];

        echo "  Duplicado: #{$dupChatNum} ({$dupMessages} mensajes)\n";

        if (!$DRY_RUN) {
            $pdo->beginTransaction();

            try {
                // Mover mensajes al chat principal
                if ($dupMessages > 0) {
                    $sql = "UPDATE cocochats_mensajes SET chat_id = :main_id WHERE chat_id = :dup_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['main_id' => $mainChatNum, 'dup_id' => $dupChatNum]);
                    $movedCount = $stmt->rowCount();
                    $totalMessagesMoved += $movedCount;

                    if ($VERBOSE) {
                        echo "    -> Movidos $movedCount mensajes\n";
                    }
                }

                // Mover ratings al chat principal (si la tabla existe)
                if ($existingTables['cocochats_ratings']) {
                    $sql = "UPDATE cocochats_ratings SET chat_num = :main_id WHERE chat_num = :dup_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['main_id' => $mainChatNum, 'dup_id' => $dupChatNum]);
                }

                // Mover notas internas al chat principal (si la tabla existe)
                if ($existingTables['notas_internas']) {
                    $sql = "UPDATE notas_internas SET chat_num = :main_id WHERE chat_num = :dup_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['main_id' => $mainChatNum, 'dup_id' => $dupChatNum]);
                }

                // Mover etiquetas (si la tabla existe)
                if ($existingTables['etiquetas_chats']) {
                    $sql = "
                        UPDATE IGNORE etiquetas_chats
                        SET chat_num = :main_id
                        WHERE chat_num = :dup_id
                    ";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['main_id' => $mainChatNum, 'dup_id' => $dupChatNum]);

                    // Eliminar etiquetas duplicadas que no se pudieron mover
                    $sql = "DELETE FROM etiquetas_chats WHERE chat_num = :dup_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['dup_id' => $dupChatNum]);
                }

                // Actualizar el chat principal con datos del duplicado si están vacíos
                if (empty($mainChat['contactNum']) && !empty($dupChat['contactNum'])) {
                    $sql = "UPDATE cocochats SET contactNum = :contact WHERE num = :main_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['contact' => $dupChat['contactNum'], 'main_id' => $mainChatNum]);
                }

                if (empty($mainChat['assignedToAgent']) && !empty($dupChat['assignedToAgent'])) {
                    $sql = "UPDATE cocochats SET assignedToAgent = :agent WHERE num = :main_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['agent' => $dupChat['assignedToAgent'], 'main_id' => $mainChatNum]);
                }

                if (empty($mainChat['category']) && !empty($dupChat['category'])) {
                    $sql = "UPDATE cocochats SET category = :cat WHERE num = :main_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['cat' => $dupChat['category'], 'main_id' => $mainChatNum]);
                }

                // Si algún duplicado estaba resuelto, marcar el principal como resuelto
                if ($anyResolved && $mainChat['status'] !== 'Resuelta') {
                    $sql = "UPDATE cocochats SET status = 'Resuelta' WHERE num = :main_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(['main_id' => $mainChatNum]);
                    $mainChat['status'] = 'Resuelta'; // Actualizar para no repetir
                    if ($VERBOSE) {
                        echo "    -> Status actualizado a Resuelta\n";
                    }
                }

                // Eliminar el chat duplicado
                $sql = "DELETE FROM cocochats WHERE num = :dup_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['dup_id' => $dupChatNum]);

                $pdo->commit();
                $totalChatsDeleted++;

                if ($VERBOSE) {
                    echo "    -> Chat #{$dupChatNum} eliminado\n";
                }

            } catch (Exception $e) {
                $pdo->rollBack();
                echo "    ERROR: " . $e->getMessage() . "\n";
            }
        } else {
            $totalMessagesMoved += $dupMessages;
            $totalChatsDeleted++;
        }

        $totalChatsProcessed++;
    }

    echo "\n";
}

echo "=== Resumen ===\n";
echo "UUIDs procesados: " . count($duplicates) . "\n";
echo "Chats duplicados " . ($DRY_RUN ? "a eliminar" : "eliminados") . ": $totalChatsDeleted\n";
echo "Mensajes " . ($DRY_RUN ? "a mover" : "movidos") . ": $totalMessagesMoved\n";

if ($DRY_RUN) {
    echo "\n*** Esto fue una simulación. Usa --execute para aplicar los cambios ***\n";
}
