<?php
// Include your database configuration file
require_once $_SERVER['DOCUMENT_ROOT'] . '/inventory_sys_opt/config/db.php';

// Fallback safeguard if $conn isn't set globally but another variable is used
if (!isset($conn) && isset($pdo)) {
    // If your db.php uses PDO ($pdo), handle it gracefully
    $stmt = $pdo->query("SELECT * FROM supplies ORDER BY id DESC");
    $supplies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($supplies as $row) {
        renderSupplyRow($row);
    }
} elseif (isset($conn)) {
    // Standard MySQLi connection ($conn)
    $sql = "SELECT * FROM supplies ORDER BY id DESC";
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

    echo "<tr class='{$rowClass}'>";
    echo "<td>" . htmlspecialchars($row['supply_code']) . "</td>";
    echo "<td>" . htmlspecialchars($row['supply_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['supply_unit']) . "</td>";
    echo "<td>" . htmlspecialchars($row['reference'] ?? '') . "</td>";
    echo "<td>" . htmlspecialchars($row['supply_qty']) . "</td>";
    echo "<td>";
    echo "<button class='btn btn-sm btn-primary edit-btn me-1' data-id='{$row['id']}'>Edit</button>";
    echo "<button class='btn btn-sm btn-danger delete-btn' data-id='{$row['id']}'>Delete</button>";
    echo "</td>";
    echo "</tr>";
}
?>
