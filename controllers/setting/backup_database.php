<?php
// controllers/setting/backup_database.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__, 2) . '/controllers/auth/auth.php';
require_once dirname(__DIR__, 2) . '/config/db.php';

// Set timezone to Asia/Manila for accurate timestamp in filename and header
date_default_timezone_set('Asia/Manila');

// Generate the filename: date+time_inventory_sys_opt.sql
$filename = date('Y-m-d_H-i-s') . '_inventory_sys_opt.sql';

// Set headers for direct file download
header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

try {
    $out = fopen('php://output', 'w');

    // Header metadata comments
    fwrite($out, "-- ========================================================\n");
    fwrite($out, "-- San Roque Elementary School - Inventory System Backup\n");
    fwrite($out, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
    fwrite($out, "-- Database: " . ($db_name ?? 'inventory_sys_db') . "\n");
    fwrite($out, "-- ========================================================\n\n");
    fwrite($out, "SET FOREIGN_KEY_CHECKS=0;\n");
    fwrite($out, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
    fwrite($out, "SET time_zone = \"+00:00\";\n\n");

    // Fetch all database tables
    $tables = [];
    $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    foreach ($tables as $table) {
        fwrite($out, "-- --------------------------------------------------------\n");
        fwrite($out, "-- Table structure for table `{$table}`\n");
        fwrite($out, "-- --------------------------------------------------------\n\n");
        fwrite($out, "DROP TABLE IF EXISTS `{$table}`;\n");

        // Fetch CREATE TABLE schema
        $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
        $createRow = $createStmt->fetch(PDO::FETCH_NUM);
        if ($createRow && isset($createRow[1])) {
            fwrite($out, $createRow[1] . ";\n\n");
        }

        // Fetch and export rows
        $dataStmt = $pdo->query("SELECT * FROM `{$table}`");
        $insertBuffer = [];
        $columns = [];

        while ($dataRow = $dataStmt->fetch(PDO::FETCH_ASSOC)) {
            if (empty($columns)) {
                $columns = array_map(function($col) {
                    return "`" . str_replace("`", "``", $col) . "`";
                }, array_keys($dataRow));
            }

            $values = [];
            foreach ($dataRow as $val) {
                if ($val === null) {
                    $values[] = 'NULL';
                } else {
                    $values[] = $pdo->quote($val);
                }
            }
            $insertBuffer[] = "(" . implode(", ", $values) . ")";

            // Output in batches of 50 rows
            if (count($insertBuffer) >= 50) {
                fwrite($out, "INSERT INTO `{$table}` (" . implode(", ", $columns) . ") VALUES\n" . implode(",\n", $insertBuffer) . ";\n\n");
                $insertBuffer = [];
            }
        }

        if (!empty($insertBuffer) && !empty($columns)) {
            fwrite($out, "INSERT INTO `{$table}` (" . implode(", ", $columns) . ") VALUES\n" . implode(",\n", $insertBuffer) . ";\n\n");
        }

        fwrite($out, "\n");
    }

    fwrite($out, "SET FOREIGN_KEY_CHECKS=1;\n");
    fwrite($out, "-- Backup completed successfully.\n");

    fclose($out);
    exit();

} catch (Exception $e) {
    error_log("Database backup error: " . $e->getMessage());
    http_response_code(500);
    die("Error generating database backup. Please try again or contact administrator.");
}

