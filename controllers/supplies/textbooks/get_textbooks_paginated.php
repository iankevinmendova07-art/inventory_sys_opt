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
        $where = ' WHERE lr_item LIKE :item OR grade_level LIKE :grade OR lr_subject LIKE :subject OR lr_qty LIKE :qty OR lr_unit LIKE :unit OR recipient LIKE :recipient OR `condition` LIKE :condition OR created_at LIKE :created';
        $term = '%' . $search . '%';
        $bindings = [':item' => $term, ':grade' => $term, ':subject' => $term, ':qty' => $term, ':unit' => $term, ':recipient' => $term, ':condition' => $term, ':created' => $term];
    }

    $total = (int) $pdo->query('SELECT COUNT(*) FROM lr_textbooks')->fetchColumn();
    if ($where === '') {
        $filtered = $total;
    } else {
        $count = $pdo->prepare('SELECT COUNT(*) FROM lr_textbooks' . $where);
        $count->execute($bindings);
        $filtered = (int) $count->fetchColumn();
    }

    $columns = [0 => 'lr_item', 1 => 'grade_level', 2 => 'lr_subject', 3 => 'lr_qty', 4 => 'lr_unit', 5 => 'recipient', 6 => '`condition`', 7 => 'created_at'];
    $hasOrder = isset($_GET['order'][0]['column']);
    $orderIdx = $hasOrder ? (int) $_GET['order'][0]['column'] : -1;
    $orderColumn = $hasOrder ? ($columns[$orderIdx] ?? 'id') : 'id';
    $direction = strtolower((string) ($_GET['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
    if ($hasOrder && !isset($columns[$orderIdx])) $orderColumn = 'id';

    $sql = 'SELECT id, lr_item, grade_level, lr_subject, lr_qty, lr_unit, recipient, `condition`, created_at FROM lr_textbooks'
        . $where . ' ORDER BY ' . $orderColumn . ' ' . $direction . ', id DESC LIMIT :offset, :limit';
    $stmt = $pdo->prepare($sql);
    foreach ($bindings as $key => $value) $stmt->bindValue($key, $value, PDO::PARAM_STR);
    $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
    $stmt->execute();
    echo json_encode(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    // Keep compatibility with databases predating the optional condition column.
    if (stripos($e->getMessage(), 'condition') !== false) {
        try {
            $total = (int) $pdo->query('SELECT COUNT(*) FROM lr_textbooks')->fetchColumn();
            $fallback = $pdo->prepare("SELECT id, lr_item, grade_level, lr_subject, lr_qty, lr_unit, recipient, 'Good' AS `condition`, created_at FROM lr_textbooks ORDER BY id DESC LIMIT :offset, :limit");
            $fallback->bindValue(':offset', max(0, (int) ($_GET['start'] ?? 0)), PDO::PARAM_INT);
            $fallback->bindValue(':limit', min(100, max(1, (int) ($_GET['length'] ?? 10))), PDO::PARAM_INT);
            $fallback->execute();
            echo json_encode(['draw' => (int) ($_GET['draw'] ?? 1), 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $fallback->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $fallbackError) {
            error_log('get_textbooks_paginated.php fallback error: ' . $fallbackError->getMessage());
        }
    }
    error_log('get_textbooks_paginated.php error: ' . $e->getMessage());
    echo json_encode(['draw' => (int) ($_GET['draw'] ?? 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Unable to load textbooks.']);
}
