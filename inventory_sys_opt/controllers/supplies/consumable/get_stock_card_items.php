<?php
session_start();
require_once dirname(__DIR__, 3) . '/config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['data' => [], 'error' => 'Unauthorized.']);
    exit;
}

try {
    $stmt = $pdo->query("SELECT id, supply_name FROM supplies ORDER BY supply_name ASC");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = array_map(function ($item) {
        return [
            'id' => (int) $item['id'],
            'item_name' => htmlspecialchars($item['supply_name'])
        ];
    }, $items);

    echo json_encode(['data' => $data]);
} catch (PDOException $e) {
    echo json_encode(['data' => [], 'error' => 'Unable to load stock card items.']);
}
?>
