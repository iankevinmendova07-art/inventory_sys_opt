<?php
require_once dirname(__DIR__, 3) . '/controllers/auth/auth.php';
require_once dirname(__DIR__, 3) . '/config/db.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $draw = max(0, (int) ($_GET['draw'] ?? 1));
    $start = max(0, (int) ($_GET['start'] ?? 0));
    $length = (int) ($_GET['length'] ?? 10);
    $length = ($length > 0 && $length <= 100) ? $length : 10;
    $search = trim((string) ($_GET['search']['value'] ?? ''));

    $where = '';
    $bindings = [];
    if ($search !== '') {
        $where = " WHERE trans_code LIKE :code OR emp_name LIKE :employee OR supply_code LIKE :supply_code OR supply_name LIKE :supply_name OR supply_unit LIKE :unit OR supply_qty LIKE :qty OR DATE_FORMAT(created_at, '%b %d, %Y') LIKE :display_date";
        $term = '%' . $search . '%';
        $bindings = [':code' => $term, ':employee' => $term, ':supply_code' => $term, ':supply_name' => $term, ':unit' => $term, ':qty' => $term, ':display_date' => $term];
    }

    $total = (int) $pdo->query('SELECT COUNT(*) FROM transaction_log')->fetchColumn();
    if ($where === '') {
        $filtered = $total;
    } else {
        $count = $pdo->prepare('SELECT COUNT(*) FROM transaction_log' . $where);
        $count->execute($bindings);
        $filtered = (int) $count->fetchColumn();
    }

    $columns = [0 => 'trans_code', 1 => 'emp_name', 2 => 'supply_code', 3 => 'supply_name', 4 => 'supply_unit', 5 => 'supply_qty', 6 => 'created_at'];
    $hasOrder = isset($_GET['order'][0]['column']);
    $orderIdx = $hasOrder ? (int) $_GET['order'][0]['column'] : -1;
    $orderColumn = $hasOrder ? ($columns[$orderIdx] ?? 'id') : 'id';
    $direction = strtolower((string) ($_GET['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';

    $sql = 'SELECT id, trans_code, emp_name, supply_code, supply_name, supply_unit, supply_qty, created_at FROM transaction_log'
        . $where . ' ORDER BY ' . $orderColumn . ' ' . $direction . ', id DESC LIMIT :offset, :limit';
    $stmt = $pdo->prepare($sql);
    foreach ($bindings as $key => $value) $stmt->bindValue($key, $value, PDO::PARAM_STR);
    $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
    $stmt->execute();

    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[] = [
            'trans_code' => htmlspecialchars((string) $row['trans_code'], ENT_QUOTES, 'UTF-8'),
            'emp_name' => htmlspecialchars((string) $row['emp_name'], ENT_QUOTES, 'UTF-8'),
            'supply_code' => htmlspecialchars((string) $row['supply_code'], ENT_QUOTES, 'UTF-8'),
            'supply_name' => htmlspecialchars((string) $row['supply_name'], ENT_QUOTES, 'UTF-8'),
            'supply_unit' => htmlspecialchars((string) $row['supply_unit'], ENT_QUOTES, 'UTF-8'),
            'supply_qty' => htmlspecialchars((string) $row['supply_qty'], ENT_QUOTES, 'UTF-8'),
            'date' => $row['created_at'] ? date('M d, Y', strtotime($row['created_at'])) : ''
        ];
    }
    echo json_encode(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('get_rsmi_logs_paginated.php error: ' . $e->getMessage());
    echo json_encode(['draw' => (int) ($_GET['draw'] ?? 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Unable to load RSMI records.']);
}
