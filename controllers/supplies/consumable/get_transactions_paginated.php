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

    $from = ' FROM transaction_log';
    $where = '';
    $bindings = [];
    if ($search !== '') {
        $where = ' WHERE trans_code LIKE :code OR emp_name LIKE :name OR created_at LIKE :created';
        $term = '%' . $search . '%';
        $bindings = [':code' => $term, ':name' => $term, ':created' => $term];
    }

    $total = (int) $pdo->query('SELECT COUNT(*) FROM (SELECT trans_code, emp_name' . $from . ' GROUP BY trans_code, emp_name) grouped_transactions')->fetchColumn();
    if ($where === '') {
        $filtered = $total;
    } else {
        $count = $pdo->prepare('SELECT COUNT(*) FROM (SELECT trans_code, emp_name' . $from . $where . ' GROUP BY trans_code, emp_name) grouped_transactions');
        $count->execute($bindings);
        $filtered = (int) $count->fetchColumn();
    }

    $columns = [0 => 'trans_code', 1 => 'emp_name', 2 => 'transaction_date'];
    $hasOrder = isset($_GET['order'][0]['column']);
    $orderIdx = $hasOrder ? (int) $_GET['order'][0]['column'] : -1;
    $orderColumn = $hasOrder ? ($columns[$orderIdx] ?? 'latest_id') : 'latest_id';
    $direction = strtolower((string) ($_GET['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';

    $sql = 'SELECT trans_code, emp_name AS name, MAX(created_at) AS transaction_date, MAX(id) AS latest_id'
        . $from . $where . ' GROUP BY trans_code, emp_name ORDER BY ' . $orderColumn . ' ' . $direction . ' LIMIT :offset, :limit';
    $stmt = $pdo->prepare($sql);
    foreach ($bindings as $key => $value) $stmt->bindValue($key, $value, PDO::PARAM_STR);
    $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
    $stmt->execute();

    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $code = (string) $row['trans_code'];
        $data[] = [
            'trans_code' => htmlspecialchars($code, ENT_QUOTES, 'UTF-8'),
            'name' => htmlspecialchars((string) $row['name'], ENT_QUOTES, 'UTF-8'),
            'date' => htmlspecialchars((string) $row['transaction_date'], ENT_QUOTES, 'UTF-8'),
            'action' => '<div class="text-center"><button type="button" class="btn btn-sm btn-outline-primary print-report-btn" data-trans-code="' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '" title="Print Report"><i class="bi bi-printer"></i></button></div>'
        ];
    }
    echo json_encode(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('get_transactions_paginated.php error: ' . $e->getMessage());
    echo json_encode(['draw' => (int) ($_GET['draw'] ?? 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Unable to load transaction logs.']);
}
