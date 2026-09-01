<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/inventory_sys_opt/config/db.php';

try {
    $stmt = $pdo->query("SELECT * FROM lr_textbooks ORDER BY id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['lr_item']) . "</td>";
        echo "<td>" . htmlspecialchars($row['grade_level']) . "</td>";
        echo "<td>" . htmlspecialchars($row['lr_subject']) . "</td>";
        echo "<td>" . htmlspecialchars($row['lr_qty']) . "</td>";
        echo "<td>" . htmlspecialchars($row['lr_unit']) . "</td>";
        echo "<td>" . htmlspecialchars($row['recipient']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
        echo "<td>
            <button class='btn btn-sm btn-primary edit-btn' 
                data-id='" . $row['id'] . "'
                data-item='" . htmlspecialchars($row['lr_item']) . "'
                data-grade='" . htmlspecialchars($row['grade_level']) . "'
                data-subject='" . htmlspecialchars($row['lr_subject']) . "'
                data-qty='" . htmlspecialchars($row['lr_qty']) . "'
                data-unit='" . htmlspecialchars($row['lr_unit']) . "'
                data-recipient='" . htmlspecialchars($row['recipient']) . "'>
                <i class='bi bi-pencil-square'></i>
            </button>         
            <button class='btn btn-sm btn-danger delete-btn' data-id='" . $row['id'] . "'>
                <i class='bi bi-trash'></i>
            </button>
               <button class='btn btn-sm btn-secondary print-btn' data-id='" . $row['id'] . "' title='Print'>
                <i class='bi bi-printer'></i>
            </button>
        </td>";
        echo "</tr>";
    }
} catch (PDOException $e) {
    error_log('controllers/supplies/textbooks/display_textbook.php error: ' . $e->getMessage());
    echo "<tr><td colspan='8' class='text-center text-danger'>Unable to load data. Please try again later.</td></tr>";
}
?>