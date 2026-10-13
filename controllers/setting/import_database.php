<?php
require_once dirname(__DIR__, 2) . '/includes/session_security.php';

require_once dirname(__DIR__, 2) . '/controllers/auth/auth.php';
require_once dirname(__DIR__, 2) . '/config/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: ../../setting.php');
    exit;
}

header('Content-Type: application/json; charset=utf-8');

function import_response(int $status, string $message): never
{
    http_response_code($status);
    echo json_encode(['status' => $status < 400 ? 'success' : 'error', 'message' => $message]);
    exit;
}

try {
    require_csrf();
} catch (Throwable $e) {
    import_response(403, 'Your session expired. Refresh the page and try again.');
}

if (!isset($_FILES['database_file']) || !is_array($_FILES['database_file'])) {
    import_response(400, 'Choose a .sql file to import.');
}

$upload = $_FILES['database_file'];
if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($upload['tmp_name'] ?? '')) {
    import_response(400, 'The upload failed. Check the file size and try again.');
}
if (strtolower(pathinfo((string)$upload['name'], PATHINFO_EXTENSION)) !== 'sql') {
    import_response(415, 'Only .sql files are accepted.');
}
$sql = file_get_contents($upload['tmp_name']);
if ($sql === false || trim($sql) === '') {
    import_response(400, 'The SQL file is empty or could not be read.');
}
$sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);

// Split ordinary SQL dumps on semicolons while preserving semicolons inside strings
// and ignoring semicolons inside SQL comments.
function split_sql_statements(string $sql): Generator
{
    $buffer = '';
    $quote = null;
    $lineComment = false;
    $blockComment = false;
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($lineComment) {
            if ($char === "\n") {
                $lineComment = false;
                $buffer .= $char;
            }
            continue;
        }
        if ($blockComment) {
            if ($char === '*' && $next === '/') {
                $blockComment = false;
                $i++;
            }
            continue;
        }
        if ($quote !== null) {
            $buffer .= $char;
            if ($char === '\\' && $quote !== '`' && $i + 1 < $length) {
                $buffer .= $sql[++$i];
                continue;
            }
            if ($char === $quote) {
                if ($next === $quote) {
                    $buffer .= $sql[++$i];
                } else {
                    $quote = null;
                }
            }
            continue;
        }
        if (($char === '-' && $next === '-' && ($i + 2 >= $length || ctype_space($sql[$i + 2]))) || $char === '#') {
            $lineComment = true;
            if ($char === '-') $i++;
            continue;
        }
        if ($char === '/' && $next === '*') {
            $blockComment = true;
            $i++;
            continue;
        }
        if ($char === "'" || $char === '"' || $char === '`') {
            $quote = $char;
            $buffer .= $char;
            continue;
        }
        if ($char === ';') {
            if (trim($buffer) !== '') yield trim($buffer);
            $buffer = '';
            continue;
        }
        $buffer .= $char;
    }

    if ($quote !== null || $blockComment) {
        throw new RuntimeException('The SQL file contains an unterminated string or comment.');
    }
    if (trim($buffer) !== '') yield trim($buffer);
}

try {
    $statements = iterator_to_array(split_sql_statements($sql), false);
    if ($statements === []) {
        import_response(400, 'The SQL file does not contain any statements.');
    }

    // Schema-only exports often contain CREATE TABLE but no DROP TABLE. Since
    // this action is explicitly a database replacement, remove the old tables
    // first in that case so those exports can be restored over an existing DB.
    $hasCreateTable = false;
    $hasDropTable = false;
    foreach ($statements as $statement) {
        $hasCreateTable = $hasCreateTable || (bool)preg_match('/^CREATE\s+TABLE\b/i', ltrim($statement));
        $hasDropTable = $hasDropTable || (bool)preg_match('/^DROP\s+TABLE\b/i', ltrim($statement));
    }
    if ($hasCreateTable && !$hasDropTable) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        $existingTables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
        foreach ($existingTables as $tableRow) {
            $tableName = (string)$tableRow[0];
            $pdo->exec('DROP TABLE IF EXISTS `' . str_replace('`', '``', $tableName) . '`');
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    $count = 0;
    foreach ($statements as $statement) {
        $pdo->exec($statement);
        $count++;
    }
    import_response(200, 'The SQL file was imported successfully.');
} catch (Throwable $e) {
    error_log('Database import error: ' . $e->getMessage());
    import_response(400, 'Import failed: ' . $e->getMessage() . ' The database may be partially updated.');
}
