<?php
// controllers/supplies/consumable/get_quantity_list.php
session_start();
require_once dirname(__DIR__, 3) . '/config/db.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, supply_code, supply_name, supply_unit, supply_qty FROM supplies ORDER BY id DESC");
    $supplies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($supplies as $row) {
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

    echo json_encode(['data' => $data]);
} catch (Exception $e) {
    error_log('controllers/supplies/consumable/get_quantity_list.php error: ' . $e->getMessage());
    echo json_encode(['data' => [], 'error' => 'A server error occurred. Please try again.']);
}
?>
