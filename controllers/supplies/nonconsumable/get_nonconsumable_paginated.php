<?php
// controllers/supplies/nonconsumable/get_nonconsumable_paginated.php
session_start();
require_once dirname(__DIR__, 3) . '/controllers/auth/auth.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $draw   = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;
    $start  = isset($_GET['start']) ? max(0, (int)$_GET['start']) : 0;
    $length = isset($_GET['length']) ? (int)$_GET['length'] : 10;
    $search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

    if ($length < 1 || $length > 100) {
        $length = 10;
    }

    $totalRecords = (int)$pdo->query("SELECT COUNT(*) FROM nonconsumable")->fetchColumn();

    $whereClauses = [];
    $bindings = [];

    if ($search !== '') {
        $whereClauses[] = "(property_number LIKE :search OR description LIKE :search OR item_type LIKE :search OR recepient LIKE :search OR remarks LIKE :search)";
        $bindings[':search'] = '%' . $search . '%';
    }

    $whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

    if (!empty($whereClauses)) {
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM nonconsumable {$whereSql}");
        $stmtCount->execute($bindings);
        $recordsFiltered = (int)$stmtCount->fetchColumn();
    } else {
        $recordsFiltered = $totalRecords;
    }

    $columnsMap = [
        0 => 'property_number',
        1 => 'description',
        2 => 'unit_of_measure',
        3 => 'item_type',
        4 => 'unit_cost',
        5 => 'qty_property_card',
        6 => 'created_at',
        7 => 'recepient',
        8 => 'remarks'
    ];

    $orderColumnIdx = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 5;
    $orderDir = (isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'asc') ? 'ASC' : 'DESC';
    $orderColumn = $columnsMap[$orderColumnIdx] ?? 'qty_property_card';

    $sql = "SELECT id, property_number, description, unit_of_measure, item_type, unit_cost, total_cost, 
                   qty_property_card, qty_physical_count, shortage_overage_qty, shortage_overage_value, 
                   recepient, remarks, created_at 
            FROM nonconsumable 
            {$whereSql} 
            ORDER BY {$orderColumn} {$orderDir} 
            LIMIT :offset, :limit";

    $stmt = $pdo->prepare($sql);
    foreach ($bindings as $key => $val) {
        $stmt->bindValue($key, $val, PDO::PARAM_STR);
    }
    $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $row) {
        $data[] = [
            'id'                     => (int)$row['id'],
            'property_number'        => htmlspecialchars($row['property_number'] ?? '', ENT_QUOTES, 'UTF-8'),
            'description'            => htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8'),
            'unit_of_measure'        => htmlspecialchars($row['unit_of_measure'] ?? '', ENT_QUOTES, 'UTF-8'),
            'item_type'              => htmlspecialchars($row['item_type'] ?? '', ENT_QUOTES, 'UTF-8'),
            'unit_cost'              => (float)($row['unit_cost'] ?? 0),
            'formatted_unit_cost'    => number_format((float)($row['unit_cost'] ?? 0), 2),
            'total_cost'             => (float)($row['total_cost'] ?? 0),
            'formatted_total_cost'   => number_format((float)($row['total_cost'] ?? 0), 2),
            'qty_property_card'      => (int)($row['qty_property_card'] ?? 0),
            'qty_physical_count'     => (int)($row['qty_physical_count'] ?? 0),
            'shortage_overage_qty'   => (int)($row['shortage_overage_qty'] ?? 0),
            'shortage_overage_value' => (float)($row['shortage_overage_value'] ?? 0),
            'recepient'              => htmlspecialchars($row['recepient'] ?? '', ENT_QUOTES, 'UTF-8'),
            'remarks'                => htmlspecialchars($row['remarks'] ?? '', ENT_QUOTES, 'UTF-8'),
            'created_at'             => htmlspecialchars($row['created_at'] ?? '', ENT_QUOTES, 'UTF-8')
        ];
    }

    echo json_encode([
        'draw'            => $draw,
        'recordsTotal'    => $totalRecords,
        'recordsFiltered' => $recordsFiltered,
        'data'            => $data
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('Error in get_nonconsumable_paginated.php: ' . $e->getMessage());
    echo json_encode([
        'draw'            => (int)($_GET['draw'] ?? 1),
        'recordsTotal'    => 0,
        'recordsFiltered' => 0,
        'data'            => [],
        'error'           => 'A server-side database error occurred while fetching non-consumable items.'
    ]);
}

