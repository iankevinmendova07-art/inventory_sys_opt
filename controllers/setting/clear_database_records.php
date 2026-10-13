<?php
require_once dirname(__DIR__, 2) . '/includes/session_security.php';

require_once dirname(__DIR__, 2) . '/controllers/auth/auth.php';
require_once dirname(__DIR__, 2) . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

function clear_database_response(int $status, string $message): never
{
    http_response_code($status);
    echo json_encode(['status' => $status < 400 ? 'success' : 'error', 'message' => $message]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    clear_database_response(405, 'Method not allowed.');
}

$tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
$preservedTables = ['admin', 'school_details', 'employee', 'position', 'unit_measure'];
$tables = array_values(array_filter(
    $tables,
    static fn(array $row): bool => !in_array(strtolower((string)$row[0]), $preservedTables, true)
));

try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach ($tables as $row) {
        $tableName = str_replace('`', '``', (string)$row[0]);
        $pdo->exec('TRUNCATE TABLE `' . $tableName . '`');
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    clear_database_response(200, 'Records were deleted from all tables except admin, school_details, employee, position, and unit_measure. Table structures were kept.');
} catch (Throwable $e) {
    try {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    } catch (Throwable $ignored) {
        // Preserve the original database error for logging.
    }
    error_log('Database record deletion error: ' . $e->getMessage());
    clear_database_response(500, 'Some records may have been deleted, but the operation could not finish. Check the database permissions and error log.');
}
