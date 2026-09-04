<?php
// controllers/supplies/consumable/get_supplies_paginated.php
session_start();
require_once dirname(__DIR__, 3) . '/controllers/auth/auth.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // 1. Read DataTables parameters
    $draw   = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;
    $start  = isset($_GET['start']) ? max(0, (int)$_GET['start']) : 0;
    $length = isset($_GET['length']) ? (int)$_GET['length'] : 10;
    $search = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';

    // Safety guard against unbounded length
    if ($length < 1 || $length > 100) {
        $length = 10;
    }

    // 2. Count total un-filtered records
    $totalRecords = (int)$pdo->query("SELECT COUNT(*) FROM supplies")->fetchColumn();

    // 3. Prepare filtered query
    $whereClauses = [];
    $bindings = [];

    if ($search !== '') {
        $whereClauses[] = "(supply_code LIKE :search OR supply_name LIKE :search OR reference LIKE :search)";
        $bindings[':search'] = '%' . $search . '%';
    }

    $whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

    // 4. Count filtered records
    if (!empty($whereClauses)) {
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM supplies {$whereSql}");
        $stmtCount->execute($bindings);
        $recordsFiltered = (int)$stmtCount->fetchColumn();
    } else {
        $recordsFiltered = $totalRecords;
    }

    // 5. Order Mapping (whitelist columns to prevent SQL injection)
    $columnsMap = [
        0 => 'supply_code',
        1 => 'supply_name',
        2 => 'supply_unit',
        3 => 'reference',
        4 => 'supply_qty'
    ];

    $orderColumnIdx = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 4;
    $orderDir = (isset($_GET['order'][0]['dir']) && strtolower($_GET['order'][0]['dir']) === 'asc') ? 'ASC' : 'DESC';
    $orderColumn = $columnsMap[$orderColumnIdx] ?? 'supply_qty';

    // 6. Fetch paginated subset
    $sql = "SELECT id, supply_code, supply_name, supply_unit, reference, supply_qty 
            FROM supplies 
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

    // 7. Format clean JSON response
    $data = [];
    foreach ($rows as $row) {
        $qty = (int)$row['supply_qty'];
        $rowStatus = $qty === 0 ? 'out-of-stock' : ($qty <= 5 ? 'low-stock' : 'in-stock');

        $data[] = [
            'id'          => (int)$row['id'],
            'supply_code' => htmlspecialchars($row['supply_code'] ?? '', ENT_QUOTES, 'UTF-8'),
            'supply_name' => htmlspecialchars($row['supply_name'] ?? '', ENT_QUOTES, 'UTF-8'),
            'supply_unit' => htmlspecialchars($row['supply_unit'] ?? '', ENT_QUOTES, 'UTF-8'),
            'reference'   => htmlspecialchars($row['reference'] ?? '', ENT_QUOTES, 'UTF-8'),
            'supply_qty'  => $qty,
            'status'      => $rowStatus
        ];
    }

    echo json_encode([
        'draw'            => $draw,
        'recordsTotal'    => $totalRecords,
        'recordsFiltered' => $recordsFiltered,
        'data'            => $data
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log('Error in get_supplies_paginated.php: ' . $e->getMessage());
    echo json_encode([
        'draw'            => (int)($_GET['draw'] ?? 1),
        'recordsTotal'    => 0,
        'recordsFiltered' => 0,
        'data'            => [],
        'error'           => 'A server-side database error occurred while fetching supplies.'
    ]);
}

