<?php
require_once __DIR__ . '/../../config/db.php'; // includes/partials/ -> project root/config/db.php
try {
    $unitStmt = $pdo->query("SELECT * FROM unit_measure ORDER BY unit_name ASC");
    while ($uRow = $unitStmt->fetch(PDO::FETCH_ASSOC)) {
        $unitName = htmlspecialchars($uRow['unit_name']);
        echo "<option value=\"{$unitName}\">{$unitName}</option>";
    }
} catch (PDOException $e) {
    echo "<option value=\"Pieces\">Pieces</option>";
    echo "<option value=\"Reams\">Reams</option>";
    echo "<option value=\"Boxes\">Boxes</option>";
}
?>