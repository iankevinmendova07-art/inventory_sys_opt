<?php
// controllers/supplies/consumable/print_ris.php
session_start();
require_once '../../../controllers/auth/auth.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

$trans_code = $_GET['trans_code'] ?? '';

if (empty($trans_code)) {
    die("Invalid Transaction Code.");
}

try {
    // 1. Fetch all items under this transaction code from transaction_log
    $stmt = $pdo->prepare("SELECT * FROM transaction_log WHERE trans_code = ?");
    $stmt->execute([$trans_code]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$items) {
        die("Transaction not found.");
    }

    $recipient_name = $items[0]['emp_name'] ?? '';
    $transaction_date = $items[0]['created_at'] ?? date('Y-m-d H:i:s');

    // 2. Fetch all employees to find exact designation from emp_position
    $empQuery = $pdo->prepare("SELECT emp_name, emp_position FROM employee");
    $empQuery->execute();
    $employees = $empQuery->fetchAll(PDO::FETCH_ASSOC);

    $recipient_position = "Teacher I"; // Default fallback
    $school_head_name = "SCHOOL HEAD";
    $admin_officer_name = "ADMINISTRATIVE OFFICER II";

    foreach ($employees as $emp) {
        $dbEmpName = trim($emp['emp_name']);
        $position = trim($emp['emp_position']);

        // Check if this employee is the transaction recipient
        if (strcasecmp($dbEmpName, trim($recipient_name)) === 0) {
            $recipient_position = $position;
        }

        // Dynamically find School Head
        if (stripos($position, 'School Head') !== false || stripos($position, 'Principal') !== false) {
            $school_head_name = strtoupper($dbEmpName);
        }

        // Dynamically find Administrative Officer II
        if (stripos($position, 'Administrative Officer II') !== false || stripos($position, 'Admin. Officer II') !== false) {
            $admin_officer_name = strtoupper($dbEmpName);
        }
    }

} catch (Exception $e) {
    error_log('controllers/supplies/consumable/print_ris.php DB error: ' . $e->getMessage());
    die('A database error occurred while generating this document. Please try again or contact the administrator.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Requisition and Issue Slip - <?php echo htmlspecialchars($trans_code); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page {
            size: portrait;
            margin: 4mm;
        }
        body { 
            font-family: Arial, sans-serif; 
            font-size: 9.5px; 
            color: #000; 
            background: #fff; 
            margin: 0; 
            padding: 2px; 
        }
        .ris-half { 
            height: auto; 
            min-height: 48.5vh; 
            box-sizing: border-box; 
            border: 1.5px solid #000; 
            padding: 5px; 
            overflow: visible;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 2px 4px; vertical-align: middle; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .no-border td { border: none; padding: 1px 2px; }
        .page-divider { 
            border-top: 1.5px dashed #444; 
            margin: 3px 0; 
            text-align: center; 
            position: relative; 
            height: 2px;
        }
        .page-divider span { 
            background: #fff; 
            padding: 0 5px; 
            font-size: 7.5px; 
            color: #444; 
            position: relative; 
            top: -7px; 
        }
        @media print {
            .print-btn-container { display: none; }
            body { padding: 0; margin: 0; }
            .ris-half { border: 1.5px solid #000; }
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
    </style>
</head>
<body onload="window.print()">

<div class="print-btn-container text-end mb-2">
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> Print RIS</button>
    <button onclick="window.close()" class="btn btn-secondary btn-sm">Close</button>
</div>

<?php 
$render_ris_form = function() use ($trans_code, $transaction_date, $items, $recipient_name, $school_head_name, $admin_officer_name, $recipient_position) {
?>
    <div class="ris-half">
       <div class="deped-header">
        <img src="/inventory_sys/assets/img/deped.png" alt="DepEd Logo">
        <div class="republic">Republic of the Philippines</div>
        <div class="department">Department of Education</div>
        <div class="region">Region VIII — Eastern Visayas</div>
        <div class="division">Schools Division of Catbalogan City</div>
        <div class="school">San Roque Elementary School</div>
        <div class="address" style="font-size: 10px; font-weight: normal; margin-top: 2px;">Brgy. San Roque, Catbalogan City</div>
       </div>

        <!-- Details Meta -->
        <table class="no-border mb-1" style="font-size: 8.5px;">
            <tr>
                <td><strong>Entity Name :</strong> San Roque Elementary School</td>
                <td><strong>Fund Cluster :</strong> ___________________</td>
            </tr>
            <tr>
                <td><strong>Division :</strong> Catbalogan City</td>
                <td><strong>RIS No :</strong> <?php echo htmlspecialchars($trans_code); ?></td>
            </tr>
            <tr>
                <td><strong>Responsibility Center Code :</strong> _______________</td>
                <td><strong>Date :</strong> <?php echo htmlspecialchars(date('F d, Y', strtotime($transaction_date))); ?></td>
            </tr>
        </table>

        <!-- Main Items Table -->
        <table style="font-size: 8.5px;">
            <thead>
                <tr class="text-center">
                    <th colspan="2">Requisition</th>
                    <th rowspan="2">Stock No.</th>
                    <th rowspan="2">Item Description</th>
                    <th rowspan="2">Unit</th>
                    <th rowspan="2">Quantity</th>
                    <th colspan="2">Stock Available?</th>
                    <th rowspan="2">Issue Qty</th>
                    <th rowspan="2">Remarks</th>
                </tr>
                <tr class="text-center">
                    <th>Stock No.</th>
                    <th>Quantity</th>
                    <th>Yes</th>
                    <th>No</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): 
                    $qty = $item['supply_qty'] ?? 0;
                    $supplyCode = $item['supply_code'] ?? 'N/A';
                    $supplyName = $item['supply_name'] ?? '';
                    $supplyUnit = $item['supply_unit'] ?? 'PIECE'; 
                ?>
                <tr>
                    <td class="text-center">-</td>
                    <td class="text-center"><?php echo htmlspecialchars($qty); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($supplyCode); ?></td>
                    <td><?php echo htmlspecialchars($supplyName); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($supplyUnit); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($qty); ?></td>
                    <td class="text-center">✓</td>
                    <td class="text-center"></td>
                    <td class="text-center"><?php echo htmlspecialchars($qty); ?></td>
                    <td></td>
                </tr>
                <?php endforeach; ?>
                <?php for($i = count($items); $i < 2; $i++): ?>
                <tr>
                    <td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>

        <!-- Purpose -->
        <table class="no-border mt-1 mb-1" style="font-size: 8.5px;">
            <tr>
                <td><strong>Purpose:</strong> For official school use / Office supplies release.</td>
            </tr>
        </table>

        <!-- Signatures Table -->
        <table class="text-center mt-1" style="font-size: 8px; table-layout: fixed;">
            <colgroup>
                <col style="width: 16%;">
                <col style="width: 21%;">
                <col style="width: 21%;">
                <col style="width: 21%;">
                <col style="width: 21%;">
            </colgroup>
            <tr>
                <td class="text-start"></td>
                <td><strong>Requested by:</strong></td>
                <td><strong>Approved by:</strong></td>
                <td><strong>Issued by:</strong></td>
                <td><strong>Received by:</strong></td>
            </tr>
            <tr style="height: 18px;">
                <td class="text-start">Signature:</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="text-start">Printed Name:</td>
                <td class="fw-bold"><?php echo htmlspecialchars(strtoupper($recipient_name)); ?></td>
                <td class="fw-bold"><?php echo htmlspecialchars($school_head_name); ?></td>
                <td class="fw-bold"><?php echo htmlspecialchars($admin_officer_name); ?></td>
                <td class="fw-bold"><?php echo htmlspecialchars(strtoupper($recipient_name)); ?></td>
            </tr>
            <tr>
                <td class="text-start">Designation:</td>
                <td><?php echo htmlspecialchars($recipient_position); ?></td>
                <td>School Head</td>
                <td>Admin. Officer II</td>
                <td><?php echo htmlspecialchars($recipient_position); ?></td>
            </tr>
            <tr>
                <td class="text-start">Date:</td>
                <td><?php echo htmlspecialchars(date('m/d/Y', strtotime($transaction_date))); ?></td>
                <td><?php echo htmlspecialchars(date('m/d/Y', strtotime($transaction_date))); ?></td>
                <td><?php echo htmlspecialchars(date('m/d/Y', strtotime($transaction_date))); ?></td>
                <td><?php echo htmlspecialchars(date('m/d/Y', strtotime($transaction_date))); ?></td>
            </tr>
        </table>
    </div>
<?php
};
?>

<?php
$singlePageMode = count($items) > 5;

if ($singlePageMode) {
    $render_ris_form();
} else {
    // Render Upper Copy
    $render_ris_form();
    ?>

    <!-- Divider / Cut Line -->
    <div class="page-divider"><span>✂ - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - ✂</span></div>

    <?php
    // Render Lower Copy
    $render_ris_form();
}
?>

</body>
</html>