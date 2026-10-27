<?php
require_once dirname(__DIR__, 3) . '/controllers/auth/auth.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $draw = max(0, (int)($_GET['draw'] ?? 1));
    $start = max(0, (int)($_GET['start'] ?? 0));
    $length = (int)($_GET['length'] ?? 10);
    $length = ($length >= 1 && $length <= 100) ? $length : 10;
    $type = (string)($_GET['type'] ?? '');
    if (!in_array($type, ['Science', 'Math'], true)) {
        http_response_code(400);
        echo json_encode(['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
        exit;
    }

    $search = trim((string)($_GET['search']['value'] ?? ''));
    $where = ' WHERE lr_type = :type';
    $bindings = [':type' => $type];
    if ($search !== '') {
        $where .= ' AND (lr_code LIKE :code OR lr_item LIKE :item OR lr_qty LIKE :qty OR lr_unit LIKE :unit)';
        $term = '%' . $search . '%';
        $bindings += [':code' => $term, ':item' => $term, ':qty' => $term, ':unit' => $term];
    }

    $totalStmt = $pdo->prepare('SELECT COUNT(*) FROM lr_sme WHERE lr_type = :type');
    $totalStmt->execute([':type' => $type]);
    $total = (int)$totalStmt->fetchColumn();
    if ($search === '') {
        $filtered = $total;
    } else {
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM lr_sme' . $where);
        $countStmt->execute($bindings);
        $filtered = (int)$countStmt->fetchColumn();
    }

    $columns = [0 => 'lr_code', 1 => 'lr_item', 2 => 'lr_qty', 3 => 'lr_unit'];
    $orderIndex = (int)($_GET['order'][0]['column'] ?? 0);
    $orderColumn = $columns[$orderIndex] ?? 'lr_code';
    if ($orderColumn === 'lr_code') $orderColumn = 'CAST(lr_code AS UNSIGNED)';
    $direction = strtolower((string)($_GET['order'][0]['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
    $sql = 'SELECT id, lr_code, lr_item, lr_qty, lr_unit, lr_type FROM lr_sme' . $where .
        ' ORDER BY ' . $orderColumn . ' ' . $direction . ', id ASC LIMIT :offset, :limit';
    $stmt = $pdo->prepare($sql);
    foreach ($bindings as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
    $stmt->execute();
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total,
        'recordsFiltered' => $filtered,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('get_sme_paginated.php error: ' . $e->getMessage());
    echo json_encode([
        'draw' => max(0, (int)($_GET['draw'] ?? 1)),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Unable to load Science and Math records.'
    ]);
}
