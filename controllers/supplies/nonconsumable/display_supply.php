<?php
require_once dirname(__DIR__, 3) . '/config/db.php';
try {
    $stmt = $pdo->query("SELECT * FROM nonconsumable ORDER BY qty_property_card DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<tr>
                <td>' . htmlspecialchars($row['property_number']) . '</td>
                <td>' . htmlspecialchars($row['description']) . '</td>
                <td>' . htmlspecialchars($row['unit_of_measure']) . '</td>
                <td>' . htmlspecialchars($row['item_type']) . '</td>
                <td>' . number_format((float)$row['unit_cost'], 2) . '</td>
                <td>' . htmlspecialchars($row['qty_property_card']) . '</td>
                <td>' . htmlspecialchars($row['created_at']) . '</td>
                <td>' . htmlspecialchars($row['recepient']) . '</td>
                <td>' . htmlspecialchars($row['remarks']) . '</td>
                <td>
                    <button class="btn btn-sm btn-primary edit-btn me-1" 
                        data-id="' . $row['id'] . '"
                        data-property_number="' . htmlspecialchars($row['property_number'], ENT_QUOTES) . '"
                        data-description="' . htmlspecialchars($row['description'], ENT_QUOTES) . '"
                        data-unit="' . htmlspecialchars($row['unit_of_measure'], ENT_QUOTES) . '"
                        data-category="' . htmlspecialchars($row['item_type'], ENT_QUOTES) . '"
                        data-unit_cost="' . htmlspecialchars($row['unit_cost'], ENT_QUOTES) . '"
                        data-total_cost="' . htmlspecialchars($row['total_cost'] ?? '', ENT_QUOTES) . '"
                        data-qty_property_card="' . htmlspecialchars($row['qty_property_card'], ENT_QUOTES) . '"
                        data-qty_physical_count="' . htmlspecialchars($row['qty_physical_count'] ?? '', ENT_QUOTES) . '"
                        data-shortage_overage_qty="' . htmlspecialchars($row['shortage_overage_qty'] ?? '', ENT_QUOTES) . '"
                        data-shortage_overage_value="' . htmlspecialchars($row['shortage_overage_value'] ?? '', ENT_QUOTES) . '"
                        data-remarks="' . htmlspecialchars($row['remarks'], ENT_QUOTES) . '"
                        data-recipient="' . htmlspecialchars($row['recepient'], ENT_QUOTES) . '"
                        title="Edit">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button class="btn btn-sm btn-danger delete-btn me-1" data-id="' . $row['id'] . '" title="Delete"><i class="bi bi-trash"></i></button>
                    <button class="btn btn-sm btn-secondary print-btn" 
                        data-id="' . $row['id'] . '"
                        data-category="' . htmlspecialchars($row['item_type'], ENT_QUOTES) . '"
                        data-unit_cost="' . htmlspecialchars($row['unit_cost'], ENT_QUOTES) . '"
                        data-total_cost="' . htmlspecialchars($row['total_cost'] ?? '', ENT_QUOTES) . '"
                        title="Print Item"><i class="bi bi-printer"></i></button>
                </td>
            </tr>';
    }
} catch (PDOException $e) {
    // Handle error silently or log if needed
}
?>