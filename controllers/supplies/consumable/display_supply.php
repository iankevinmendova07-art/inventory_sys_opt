<?php
// Include your database configuration file
require_once $_SERVER['DOCUMENT_ROOT'] . '/inventory_sys_opt/config/db.php';

// Fallback safeguard if $conn isn't set globally but another variable is used
if (!isset($conn) && isset($pdo)) {
    // If your db.php uses PDO ($pdo), handle it gracefully
    $stmt = $pdo->query("SELECT * FROM supplies ORDER BY supply_qty DESC");
    $supplies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($supplies as $row) {
        renderSupplyRow($row);
    }
} elseif (isset($conn)) {
    // Standard MySQLi connection ($conn)
    $sql = "SELECT * FROM supplies ORDER BY supply_qty DESC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            renderSupplyRow($row);
        }
    }
} else {
    echo "<tr><td colspan='6' class='text-center text-danger'>Database connection error: \$conn is not initialized.</td></tr>";
}

// Helper function to keep row rendering clean
function renderSupplyRow($row) {
    $qty = (int)$row['supply_qty'];
    
    $rowClass = '';
    if ($qty === 0) {
        $rowClass = 'table-danger'; // Light red background for 0 quantity
    } elseif ($qty <= 5) {
        $rowClass = 'table-warning'; // Light yellow background for 5 or below
    }

    $code = htmlspecialchars($row['supply_code'] ?? '', ENT_QUOTES);
    $name = htmlspecialchars($row['supply_name'] ?? '', ENT_QUOTES);
    $unit = htmlspecialchars($row['supply_unit'] ?? '', ENT_QUOTES);
    $category = htmlspecialchars($row['supply_category'] ?? 'Consumable Supply', ENT_QUOTES);

    echo "<tr class='{$rowClass}'>";
    echo "<td>" . htmlspecialchars($row['supply_code'] ?? '') . "</td>";
    echo "<td>" . htmlspecialchars($row['supply_name'] ?? '') . "</td>";
    echo "<td>" . htmlspecialchars($row['supply_unit'] ?? '') . "</td>";
    echo "<td>" . htmlspecialchars($row['reference'] ?? '') . "</td>";
    echo "<td>" . htmlspecialchars($row['supply_qty'] ?? '0') . "</td>";
    echo "<td>";
    echo "<button class='btn btn-sm btn-primary edit-btn me-1' data-id='{$row['id']}' title='Edit'><i class='bi bi-pencil-square'></i></button>";
    echo "<button class='btn btn-sm btn-danger delete-btn me-1' data-id='{$row['id']}' title='Delete'><i class='bi bi-trash'></i></button>";
    echo "<button class='btn btn-sm btn-warning add-to-cart-btn text-dark' data-id='{$row['id']}' data-code='{$code}' data-name='{$name}' data-unit='{$unit}' data-category='{$category}' data-qty='{$qty}' title='Add to Cart'><i class='bi bi-cart-plus'></i></button>";
    echo "</td>";
    echo "</tr>";
}
?>
