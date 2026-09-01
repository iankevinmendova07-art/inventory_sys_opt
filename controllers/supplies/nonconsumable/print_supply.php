<?php
session_start();

$dbPath = $_SERVER['DOCUMENT_ROOT'] . '/inventory_sys/config/db.php';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/../../../../config/db.php';
}
require_once $dbPath;

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die('Invalid item ID provided.');
}
try {
    // Fixed table and column names to match your database schema
    $stmt = $pdo->prepare("
        SELECT n.*, e.emp_position AS recipient_position 
        FROM nonconsumable n 
        LEFT JOIN employee e ON n.recepient = e.emp_name 
        WHERE n.id = ?
    ");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        die('Error: Item with ID ' . $id . ' not found in the database.');
    }

    $recipientName = $item['recepient'] ?? ($item['emp_name'] ?? 'N/A');
    $recipientPosition = $item['recipient_position'] ?? 'Teacher I';

    $icsNo = !empty($item['trans_code']) ? $item['trans_code'] : (!empty($item['property_number']) ? $item['property_number'] : 'ICS-' . sprintf('%04d', $id));
    $currentDate = date('F d, Y');
    $receivedFromAdmin = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'ADMIN';

} catch (PDOException $e) {
    error_log('controllers/supplies/nonconsumable/print_supply.php DB error: ' . $e->getMessage());
    die('A database error occurred while generating this document. Please try again or contact the administrator.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Custodian Slip - <?php echo htmlspecialchars($icsNo); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .ics-container {
            width: 100%;
            max-width: 190mm;
            margin: auto;
            border: 2px solid #000;
            padding: 20px;
            background: #fff;
            box-sizing: border-box;
        }
        .deped-header {
            text-align: center;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        .deped-header img {
            width: 70px;
            height: 70px;
            object-fit: contain;
            margin-bottom: 5px;
        }
        .deped-header .republic {
            font-size: 11px;
            font-family: "Times New Roman", Times, serif;
            letter-spacing: 0.5px;
        }
        .deped-header .department {
            font-size: 15px;
            font-weight: bold;
            font-family: "Times New Roman", Times, serif;
            margin: 2px 0;
        }
        .deped-header .region, .deped-header .division, .deped-header .school, .deped-header .address {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .header-title {
            text-align: center;
            font-weight: bold;
            font-size: 15px;
            margin: 15px 0 20px 0;
            letter-spacing: 0.5px;
            text-decoration: underline;
        }
        .table-bordered, .table-bordered th, .table-bordered td {
            border: 1px solid #000 !important;
        }
        .signature-section td {
            height: 75px;
            vertical-align: bottom;
        }
        @media print {
            body {
                background: none;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .ics-container {
                border: 2px solid #000;
                box-shadow: none;
                margin: 0;
                width: 100%;
                max-width: none;
            }
        }
    </style>
</head>
<body onload="window.print()">

<div class="ics-container">
    <div class="text-end mb-3 no-print">
        <button onclick="window.print()" class="btn btn-sm btn-primary">Print ICS</button>
        <button onclick="window.close()" class="btn btn-sm btn-secondary">Close Tab</button>
    </div>

    <div class="deped-header">
        <img src="/inventory_sys/assets/img/deped.png" alt="DepEd Logo">
        <div class="republic">Republic of the Philippines</div>
        <div class="department">Department of Education</div>
        <div class="region">Region VIII — Eastern Visayas</div>
        <div class="division">Schools Division of Catbalogan City</div>
        <div class="school">San Roque Elementary School</div>
        <div class="address" style="font-size: 10px; font-weight: normal; margin-top: 2px;">Brgy. San Roque, Catbalogan City</div>
    </div>

    <div class="header-title">
        INVENTORY CUSTODIAN SLIP
    </div>

    <div class="d-flex justify-content-between mb-3 fw-semibold" style="font-size: 12px;">
        <div>Entity Name: San Roque Elementary School</div>
        <div>ICS No.: <?php echo htmlspecialchars($icsNo); ?></div>
    </div>

    <table class="table table-bordered text-center align-middle">
        <thead class="table-light">
            <tr>
                <th>Quantity</th>
                <th>Unit</th>
                <th>Description</th>
                <th>Unit Cost</th>
                <th>Total Cost</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php echo htmlspecialchars($item['qty_property_card'] ?? 1); ?></td>
                <td><?php echo htmlspecialchars($item['unit_of_measure'] ?? 'pc'); ?></td>
                <td class="text-start"><?php echo htmlspecialchars($item['description']); ?></td>
                <td><?php echo number_format(floatval($item['unit_cost'] ?? 0), 2); ?></td>
                <td><?php echo number_format(floatval($item['total_cost'] ?? 0), 2); ?></td>
            </tr>
        </tbody>
    </table>

    <table class="table table-bordered mt-4 signature-section">
        <tr>
            <td width="55%">
                <strong>Received By:</strong><br><br>
                <div class="text-center">
                    <span class="fw-bold text-uppercase"><?php echo htmlspecialchars($recipientName); ?></span><br>
                    <span class="text-muted" style="font-size: 0.85rem;"><?php echo htmlspecialchars($recipientPosition); ?></span><br>
                    <span style="font-size: 0.8rem;">Date: <?php echo htmlspecialchars($currentDate); ?></span>
                </div>
            </td>
            <td width="45%">
                <strong>Received From:</strong><br><br>
                <div class="text-center">
                    <span class="fw-bold text-uppercase"><?php echo htmlspecialchars($receivedFromAdmin); ?></span><br>
                    <span class="text-muted" style="font-size: 0.85rem;">Administrative Officer II</span><br>
                    <span style="font-size: 0.8rem;">Date: <?php echo htmlspecialchars($currentDate); ?></span>
                </div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>