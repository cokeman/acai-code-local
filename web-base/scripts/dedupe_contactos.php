<?php
/**
 * Limpieza de contactos duplicados y reasignación en cocochats.contactNum
 */

//////////////////// CONFIG ////////////////////
require_once __DIR__ . '/config.php';

$BATCH_LIMIT = 500;
////////////////////////////////////////////////

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=$DB_CHARSET";
define('DRY_RUN', @$DRY_RUN ?? true);

$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (Throwable $e) {
  die("Error de conexión: " . $e->getMessage() . PHP_EOL);
}

// Verificar índices necesarios
echo "Verificando índices...\n";
$indexes = $pdo->query("SHOW INDEX FROM cms_contactos WHERE Key_name LIKE '%chat_conf%' OR Key_name LIKE '%telefono%' OR Key_name LIKE '%email%'")->fetchAll();
if (empty($indexes)) {
  echo "⚠️  ADVERTENCIA: No se encontraron índices en cms_contactos. El proceso será MUY LENTO.\n";
  echo "   Considera ejecutar:\n";
  echo "   CREATE INDEX idx_chat_tel ON cms_contactos(chat_conf_num, telefono);\n";
  echo "   CREATE INDEX idx_chat_email ON cms_contactos(chat_conf_num, email);\n\n";
}

// Helpers
function pickTitular(array $rows): array {
  usort($rows, function ($a, $b) {
    $cmp = strcmp($a['createdDate'], $b['createdDate']);
    if ($cmp !== 0) return $cmp;
    return (int)$a['num'] <=> (int)$b['num'];
  });
  return $rows[0];
}

function mergeCamposTitular(PDO $pdo, int $titularNum, array $titular, array $candidatos) {
  $nuevoNombre = $titular['nombre'];
  $nuevoExternal = $titular['external_id'];

  if (empty($nuevoNombre)) {
    foreach ($candidatos as $r) {
      if (!empty($r['nombre'])) { $nuevoNombre = $r['nombre']; break; }
    }
  }
  if (empty($nuevoExternal)) {
    foreach ($candidatos as $r) {
      if (!empty($r['external_id'])) { $nuevoExternal = $r['external_id']; break; }
    }
  }

  if ($nuevoNombre !== $titular['nombre'] || $nuevoExternal !== $titular['external_id']) {
    if (DRY_RUN) {
      echo "[DRY] UPDATE cms_contactos SET nombre='{$nuevoNombre}', external_id='{$nuevoExternal}' WHERE num={$titularNum}\n";
    } else {
      $upd = $pdo->prepare("UPDATE cms_contactos SET nombre = ?, external_id = ? WHERE num = ?");
      $upd->execute([$nuevoNombre, $nuevoExternal, $titularNum]);
    }
  }
}

function procesarGrupo(PDO $pdo, array $ids, int $titularNum) {
  $idsSinTitular = array_values(array_diff($ids, [$titularNum]));
  if (empty($idsSinTitular)) return;

  $placeholders = implode(',', array_fill(0, count($idsSinTitular), '?'));
  
  // Reasignar cocochats
  if (DRY_RUN) {
    echo "[DRY] UPDATE cocochats SET contactNum={$titularNum} WHERE contactNum IN (" . implode(',', $idsSinTitular) . ")\n";
  } else {
    $stmt = $pdo->prepare("UPDATE cocochats SET contactNum = ? WHERE contactNum IN ($placeholders)");
    $stmt->execute(array_merge([$titularNum], $idsSinTitular));
  }

  // Reasignar cocochats_autolearn_embeddings
  if (DRY_RUN) {
    echo "[DRY] UPDATE cocochats_autolearn_embeddings SET contact_num={$titularNum} WHERE contact_num IN (" . implode(',', $idsSinTitular) . ")\n";
  } else {
    $stmt = $pdo->prepare("UPDATE cocochats_autolearn_embeddings SET contact_num = ? WHERE contact_num IN ($placeholders)");
    $stmt->execute(array_merge([$titularNum], $idsSinTitular));
  }

  // Borrar duplicados
  if (DRY_RUN) {
    echo "[DRY] DELETE FROM cms_contactos WHERE num IN (" . implode(',', $idsSinTitular) . ")\n";
  } else {
    $del = $pdo->prepare("DELETE FROM cms_contactos WHERE num IN ($placeholders)");
    $del->execute($idsSinTitular);
  }
}

$yaMapeados = [];
$stats = ['tel_grupos' => 0, 'tel_eliminados' => 0, 'email_grupos' => 0, 'email_eliminados' => 0];

// ---------- PASADA 1: DUPLICADOS POR TELÉFONO ----------
echo "\n=== DUPLICADOS POR (chat_conf_num, telefono) ===\n";

$sqlDupTel = "
  SELECT chat_conf_num, telefono, COUNT(*) as total
  FROM cms_contactos
  WHERE telefono IS NOT NULL AND telefono <> ''
  GROUP BY chat_conf_num, telefono
  HAVING COUNT(*) > 1
";

$gruposTel = $pdo->query($sqlDupTel)->fetchAll();
echo "Encontrados " . count($gruposTel) . " grupos con duplicados por teléfono\n\n";

foreach ($gruposTel as $k) {
  $chatConf = $k['chat_conf_num'];
  $tel = $k['telefono'];

  // Traer todas las filas del grupo
  $stmt = $pdo->prepare("
    SELECT num, createdDate, nombre, email, telefono, external_id, chat_conf_num
    FROM cms_contactos
    WHERE chat_conf_num = ? AND telefono = ?
    ORDER BY createdDate ASC, num ASC
  ");
  $stmt->execute([$chatConf, $tel]);
  $rows = $stmt->fetchAll();
  
  if (count($rows) < 2) continue;

  $titular = pickTitular($rows);
  $titularNum = (int)$titular['num'];
  $ids = array_map(fn($r) => (int)$r['num'], $rows);

  echo sprintf("Grupo: chat=%s, tel=%s → titular=%d (eliminando %d)\n", 
    $chatConf, $tel, $titularNum, count($ids)-1);

  try {
    if (!DRY_RUN) $pdo->beginTransaction();

    mergeCamposTitular($pdo, $titularNum, $titular, $rows);
    procesarGrupo($pdo, $ids, $titularNum);

    if (!DRY_RUN) $pdo->commit();

    foreach ($ids as $id) {
      if ($id !== $titularNum) {
        $yaMapeados[$id] = $titularNum;
        $stats['tel_eliminados']++;
      }
    }
    $stats['tel_grupos']++;

  } catch (Throwable $e) {
    if (!DRY_RUN && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "❌ Error: " . $e->getMessage() . PHP_EOL);
  }
}

// ---------- PASADA 2: DUPLICADOS POR EMAIL ----------
echo "\n=== DUPLICADOS POR (chat_conf_num, email) ===\n";

$sqlDupEmail = "
  SELECT chat_conf_num, email, COUNT(*) as total
  FROM cms_contactos
  WHERE email IS NOT NULL AND email <> ''
  GROUP BY chat_conf_num, email
  HAVING COUNT(*) > 1
";

$gruposEmail = $pdo->query($sqlDupEmail)->fetchAll();
echo "Encontrados " . count($gruposEmail) . " grupos con duplicados por email\n\n";

foreach ($gruposEmail as $k) {
  $chatConf = $k['chat_conf_num'];
  $email = $k['email'];

  $stmt = $pdo->prepare("
    SELECT num, createdDate, nombre, email, telefono, external_id, chat_conf_num
    FROM cms_contactos
    WHERE chat_conf_num = ? AND email = ?
    ORDER BY createdDate ASC, num ASC
  ");
  $stmt->execute([$chatConf, $email]);
  $rows = $stmt->fetchAll();
  
  if (count($rows) < 2) continue;

  // Filtrar ya procesados
  $rowsActivos = array_filter($rows, fn($r) => !isset($yaMapeados[(int)$r['num']]));
  $rowsActivos = array_values($rowsActivos);

  if (count($rowsActivos) < 2) continue;

  $titular = pickTitular($rowsActivos);
  $titularNum = (int)$titular['num'];
  $ids = array_map(fn($r) => (int)$r['num'], $rowsActivos);

  echo sprintf("Grupo: chat=%s, email=%s → titular=%d (eliminando %d)\n", 
    $chatConf, $email, $titularNum, count($ids)-1);

  try {
    if (!DRY_RUN) $pdo->beginTransaction();

    mergeCamposTitular($pdo, $titularNum, $titular, $rowsActivos);
    procesarGrupo($pdo, $ids, $titularNum);

    if (!DRY_RUN) $pdo->commit();

    foreach ($ids as $id) {
      if ($id !== $titularNum) {
        $yaMapeados[$id] = $titularNum;
        $stats['email_eliminados']++;
      }
    }
    $stats['email_grupos']++;

  } catch (Throwable $e) {
    if (!DRY_RUN && $pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "❌ Error: " . $e->getMessage() . PHP_EOL);
  }
}

// Resumen final
echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RESUMEN FINAL\n";
echo str_repeat("=", 60) . "\n";
echo "Grupos TEL procesados:     {$stats['tel_grupos']}\n";
echo "Contactos TEL eliminados:  {$stats['tel_eliminados']}\n";
echo "Grupos EMAIL procesados:   {$stats['email_grupos']}\n";
echo "Contactos EMAIL eliminados: {$stats['email_eliminados']}\n";
echo "Total contactos eliminados: " . ($stats['tel_eliminados'] + $stats['email_eliminados']) . "\n";
echo "\nModo: " . (DRY_RUN ? "🔍 DRY-RUN (SIN CAMBIOS)" : "✅ EJECUCIÓN REAL") . "\n";