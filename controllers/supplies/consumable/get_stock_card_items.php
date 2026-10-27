<?php
require_once dirname(__DIR__, 2) . '/auth/auth.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['data' => [], 'error' => 'Unauthorized.']);
    exit;
}

try {
    $draw = max(0, (int)($_GET['draw'] ?? 1));
    $start = max(0, (int)($_GET['start'] ?? 0));
    $length = (int)($_GET['length'] ?? 10);
    $length = ($length >= 1 && $length <= 100) ? $length : 10;
    $search = trim((string)($_GET['search']['value'] ?? ''));
    $where = $search === '' ? '' : ' WHERE supply_name LIKE :name';
    $total = (int)$pdo->query('SELECT COUNT(*) FROM supplies')->fetchColumn();
    if ($search === '') {
        $filtered = $total;
    } else {
        $count = $pdo->prepare('SELECT COUNT(*) FROM supplies' . $where);
        $count->execute([':name' => '%' . $search . '%']);
        $filtered = (int)$count->fetchColumn();
    }
    $stmt = $pdo->prepare('SELECT id, supply_name FROM supplies' . $where . ' ORDER BY supply_name ASC, id ASC LIMIT :offset, :limit');
    if ($search !== '') $stmt->bindValue(':name', '%' . $search . '%', PDO::PARAM_STR);
    $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
    $stmt->execute();
    $data = [];
    while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[] = ['id' => (int)$item['id'], 'item_name' => htmlspecialchars($item['supply_name'], ENT_QUOTES, 'UTF-8')];
    }
    echo json_encode(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
} catch (PDOException $e) {
    echo json_encode(['draw' => max(0, (int)($_GET['draw'] ?? 1)), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Unable to load stock card items.']);
}
?>
