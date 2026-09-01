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

    $propertyNo = !empty($item['property_number']) ? $item['property_number'] : 'PROP-' . sprintf('%04d', $id);
    $transCode = !empty($item['trans_code']) ? $item['trans_code'] : $propertyNo;
    
    // Pulling category from database
    $categoryVal = !empty($item['category']) ? $item['category'] : ($item['item_type'] ?? 'N/A');

} catch (PDOException $e) {
    error_log('controllers/supplies/nonconsumable/print_property_card.php DB error: ' . $e->getMessage());
    die('A database error occurred while generating this document. Please try again or contact the administrator.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Property Card - <?php echo htmlspecialchars($propertyNo); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
            margin: 6mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .property-card-container {
            width: 100%;
            max-width: 198mm;
            margin: auto;
            border: 2px solid #000;
            padding: 10px;
            background: #fff;
            box-sizing: border-box;
            position: relative;
        }
        .appendix-number {
            position: absolute;
            top: 8px;
            right: 12px;
            font-size: 10px;
            font-weight: bold;
        }
        .deped-header {
            text-align: center;
            margin-bottom: 10px;
            line-height: 1.15;
        }
        .deped-header img {
            width: 55px;
            height: 55px;
            object-fit: contain;
            margin-bottom: 3px;
        }
        .deped-header .republic {
            font-size: 10px;
            font-family: "Times New Roman", Times, serif;
            letter-spacing: 0.5px;
        }
        .deped-header .department {
            font-size: 13px;
            font-weight: bold;
            font-family: "Times New Roman", Times, serif;
            margin: 1px 0;
        }
        .deped-header .region, .deped-header .division, .deped-header .school, .deped-header .address {
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .header-title {
            text-align: center;
            font-weight: bold;
            font-size: 13px;
            margin: 8px 0 12px 0;
            letter-spacing: 0.5px;
            text-decoration: underline;
        }
        .info-table {
            width: 100%;
            margin-bottom: 8px;
            font-size: 10px;
        }
        .info-table td {
            padding: 2px 4px;
            vertical-align: top;
        }
        .table-bordered, .table-bordered th, .table-bordered td {
            border: 1px solid #000 !important;
        }
        .ledger-table {
            margin-top: 8px;
            font-size: 9.5px;
            margin-bottom: 15px;
        }
        .ledger-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            padding: 4px 2px;
            font-size: 9px;
        }
        .ledger-table td {
            padding: 3px 2px;
            vertical-align: middle;
            height: 20px;
        }
        .signature-table {
            width: 100%;
            margin-top: 10px;
            font-size: 10px;
        }
        .signature-table td {
            padding: 4px;
            vertical-align: top;
        }
        @media print {
            body {
                background: none;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .property-card-container {
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

<div class="property-card-container">
    <div class="appendix-number">Appendix 69</div>
    
    <div class="text-end mb-2 no-print">
        <button onclick="window.print()" class="btn btn-sm btn-primary">Print Property Card</button>
        <button onclick="window.close()" class="btn btn-sm btn-secondary">Close Tab</button>
    </div>
    
    <div class="deped-header">
        <img src="/inventory_sys/assets/img/deped.png" alt="DepEd Logo">
        <div class="republic">Republic of the Philippines</div>
        <div class="department">Department of Education</div>
        <div class="region">Region VIII — Eastern Visayas</div>
        <div class="division">Schools Division of Catbalogan City</div>
        <div class="school">San Roque Elementary School</div>
        <div class="address" style="font-size: 9.5px; font-weight: normal; margin-top: 1px;">Brgy. San Roque, Catbalogan City</div>
    </div>

    <div class="header-title">
        PROPERTY CARD
    </div>

    <table class="info-table">
        <tr>
            <td style="width: 55%;"><strong>Entity Name :</strong> San Roque Elementary School</td>
            <td style="width: 45%;"><strong>Fund Cluster:</strong> ______________________</td>
        </tr>
        <tr>
            <td colspan="2"><strong>Property, Plant and Equipment :</strong> <?php echo htmlspecialchars($categoryVal); ?></td>
        </tr>
        <tr>
            <td colspan="2"><strong>Property Number:</strong> <?php echo htmlspecialchars($propertyNo); ?></td>
        </tr>
        <tr>
            <td colspan="2"><strong>Description :</strong> <?php echo htmlspecialchars($item['description'] ?? 'N/A'); ?></td>
        </tr>
    </table>

    <table class="table table-bordered ledger-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 65px;">Date</th>
                <th rowspan="2" style="width: 85px;">Reference/<br>PAR No.</th>
                <th rowspan="2" style="width: 38px;">Receipt<br>Qty.</th>
                <th colspan="2" style="width: 110px;">Issue/Transfer/Disposal</th>
                <th rowspan="2" style="width: 38px;">Balance<br>Qty.</th>
                <th rowspan="2" style="width: 70px;">Amount</th>
                <th rowspan="2" style="width: 75px;">Remarks</th>
            </tr>
            <tr>
                <th style="width: 38px;">Qty.</th>
                <th style="width: 72px;">Office/Officer</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center"><?php echo date('M. d, Y', strtotime($item['created_at'] ?? 'now')); ?></td>
                <td><?php echo htmlspecialchars($transCode); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($item['qty_property_card'] ?? '0'); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($item['qty_property_card'] ?? '0'); ?></td>
                <td><?php echo htmlspecialchars($recipientName); ?></td>
                <td class="text-center"><?php echo htmlspecialchars($item['qty_property_card'] ?? '0'); ?></td>
                <td class="text-end"><?php echo number_format(floatval($item['total_cost'] ?? 0), 2); ?></td>
                <td><?php echo htmlspecialchars($item['remarks'] ?? 'New'); ?></td>
            </tr>
            <?php for($i = 0; $i < 8; $i++): ?>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <?php endfor; ?>
        </tbody>
    </table>
    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <strong>Prepared by:</strong><br><br><br>
                <div class="text-center" style="width: 200px;">
                    <strong class="text-uppercase">IAN KEVIN T. MENDOVA</strong><br>
                    <span>Admin. Officer II</span>
                </div>
            </td>
            <td style="width: 50%;">
                <strong>Received by:</strong><br><br><br>
                <div class="text-center" style="width: 200px;">
                    <strong class="text-uppercase"><?php echo htmlspecialchars($recipientName); ?></strong><br>
                    <span><?php echo htmlspecialchars($recipientPosition); ?></span>
                </div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>