<?php
// controllers/supplies/consumable/get_quantity_list.php
require_once dirname(__DIR__, 2) . '/auth/auth.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

header('Content-Type: application/json');

try {
    $draw = max(0, (int)($_GET['draw'] ?? 1));
    $start = max(0, (int)($_GET['start'] ?? 0));
    $length = (int)($_GET['length'] ?? 10);
    $length = ($length >= 1 && $length <= 100) ? $length : 10;
    $search = trim((string)($_GET['search']['value'] ?? ''));
    $where = '';
    $bindings = [];
    if ($search !== '') {
        $where = ' WHERE supply_code LIKE :code OR supply_name LIKE :name OR supply_unit LIKE :unit';
        $term = '%' . $search . '%';
        $bindings = [':code' => $term, ':name' => $term, ':unit' => $term];
    }
    $total = (int)$pdo->query('SELECT COUNT(*) FROM supplies')->fetchColumn();
    if ($where === '') {
        $filtered = $total;
    } else {
        $count = $pdo->prepare('SELECT COUNT(*) FROM supplies' . $where);
        $count->execute($bindings);
        $filtered = (int)$count->fetchColumn();
    }
    $stmt = $pdo->prepare('SELECT id, supply_code, supply_name, supply_unit, supply_qty FROM supplies' . $where . ' ORDER BY id DESC LIMIT :offset, :limit');
    foreach ($bindings as $key => $value) $stmt->bindValue($key, $value, PDO::PARAM_STR);
    $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
    $stmt->execute();

    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $qty = (int)$row['supply_qty'];
        $badgeClass = 'bg-success';
        $stockStatus = 'In Stock';
        
        if ($qty === 0) {
            $badgeClass = 'bg-danger';
            $stockStatus = 'Out of Stock';
        } elseif ($qty <= 5) {
            $badgeClass = 'bg-warning text-dark';
            $stockStatus = 'Low Stock';
        }

        $qtyBadge = '<span class="badge ' . $badgeClass . ' fs-6 me-1">' . $qty . '</span> <small class="text-muted">(' . $stockStatus . ')</small>';
        
        $inputAction = '<div class="d-flex align-items-center justify-content-center gap-2 flex-nowrap">' .
            '<input type="number" class="form-control form-control-sm inline-qty-input text-center fw-semibold" style="width: 78px;" ' .
            'id="qty_input_' . $row['id'] . '" ' .
            'placeholder="Qty" ' .
            'min="0" ' .
            'data-id="' . $row['id'] . '" ' .
            'data-code="' . htmlspecialchars($row['supply_code'], ENT_QUOTES) . '" ' .
            'data-name="' . htmlspecialchars($row['supply_name'], ENT_QUOTES) . '" ' .
            'data-current-qty="' . $qty . '">' .
            '<input type="text" class="form-control form-control-sm inline-reference-input" style="width: 150px;" ' .
            'id="reference_input_' . $row['id'] . '" ' .
            'placeholder="Reference" maxlength="100" required>' .
            '<button type="button" class="btn btn-sm btn-primary save-inline-qty-btn" ' .
            'data-id="' . $row['id'] . '" ' .
            'data-code="' . htmlspecialchars($row['supply_code'], ENT_QUOTES) . '" ' .
            'data-name="' . htmlspecialchars($row['supply_name'], ENT_QUOTES) . '">' .
            '<i class="bi bi-check-lg me-1"></i> Save</button>' .
            '</div>';

        $data[] = [
            'code' => htmlspecialchars($row['supply_code']),
            'name' => htmlspecialchars($row['supply_name']),
            'unit' => htmlspecialchars($row['supply_unit']),
            'qty' => $qtyBadge,
            'action' => $inputAction
        ];
    }

    echo json_encode(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $data]);
} catch (Exception $e) {
    error_log('controllers/supplies/consumable/get_quantity_list.php error: ' . $e->getMessage());
    echo json_encode(['draw' => max(0, (int)($_GET['draw'] ?? 1)), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'A server error occurred. Please try again.']);
}
?>
