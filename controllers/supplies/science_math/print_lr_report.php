<?php
session_start();
require_once dirname(__DIR__, 3) . '/config/db.php';

// Accepted types
$allowed = ['Science', 'Math', 'All'];
$type    = isset($_GET['type']) && in_array($_GET['type'], $allowed) ? $_GET['type'] : 'All';

$scienceItems = [];
$mathItems    = [];

try {
    if ($type === 'Science' || $type === 'All') {
        $stmt = $pdo->prepare("SELECT * FROM lr_sme WHERE lr_type = 'Science' ORDER BY CAST(lr_code AS UNSIGNED) ASC");
        $stmt->execute();
        $scienceItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    if ($type === 'Math' || $type === 'All') {
        $stmt = $pdo->prepare("SELECT * FROM lr_sme WHERE lr_type = 'Math' ORDER BY CAST(lr_code AS UNSIGNED) ASC");
        $stmt->execute();
        $mathItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die('Unable to load equipment data.');
}

$reportDate = date('F d, Y');
$titleMap   = ['Science' => 'Science Equipment', 'Math' => 'Math Equipment', 'All' => 'Science &amp; Math Equipment'];
$reportTitle = $titleMap[$type];

function renderSection(array $items, string $label, string $color): void {
    if (empty($items)) return;
    echo '<div class="section-header" style="background:' . $color . ';">' . $label . ' Equipment</div>';
    echo '<table class="report-table">
        <thead>
            <tr>
                <th style="width:7%">Code</th>
                <th style="width:47%">Item / Description</th>
                <th style="width:10%">Quantity</th>
                <th style="width:10%">Unit</th>
                <th style="width:26%">Remarks</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($items as $i => $row) {
        $bg = ($i % 2 === 0) ? '#fff' : '#f8f9fa';
        echo '<tr style="background:' . $bg . ';">
            <td class="center">' . htmlspecialchars($row['lr_code']) . '</td>
            <td>' . htmlspecialchars($row['lr_item']) . '</td>
            <td class="center">' . htmlspecialchars($row['lr_qty']) . '</td>
            <td class="center">' . htmlspecialchars($row['lr_unit'] ?? '') . '</td>
            <td></td>
        </tr>';
    }

    echo '<tr class="total-row">
            <td colspan="2" style="text-align:right; padding-right:8px;"><strong>Total Items:</strong></td>
            <td class="center"><strong>' . count($items) . '</strong></td>
            <td colspan="2"></td>
        </tr>';

    echo '</tbody></table>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LR Inventory Report – <?php echo strip_tags($reportTitle); ?></title>
    <style>
        @page { size: A4 portrait; margin: 12mm 10mm; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
        }

        /* Print button */
        .print-actions {
            text-align: right;
            margin-bottom: 10px;
        }
        .print-actions button {
            padding: 6px 16px;
            border: none;
            border-radius: 4px;
            background: #0D3B66;
            color: #fff;
            font-size: 12px;
            cursor: pointer;
        }
        @media print { .print-actions { display: none; } }

        /* DepEd header */
        .deped-header {
            text-align: center;
            margin-bottom: 14px;
            line-height: 1.3;
        }
        .deped-header img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-bottom: 4px;
        }
        .deped-header .republic  { font-size: 10px; font-family: "Times New Roman", serif; }
        .deped-header .department{ font-size: 13px; font-weight: bold; font-family: "Times New Roman", serif; margin: 2px 0; }
        .deped-header .sub       { font-size: 10px; font-weight: bold; text-transform: uppercase; }

        /* Report title */
        .report-title {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            margin: 10px 0 2px;
        }
        .report-subtitle {
            text-align: center;
            font-size: 10px;
            color: #444;
            margin-bottom: 12px;
        }

        /* Meta row */
        .meta-row {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
            font-size: 10px;
        }
        .meta-row td { padding: 1px 0; }
        .meta-row .right { text-align: right; }

        /* Section header */
        .section-header {
            font-size: 11px;
            font-weight: bold;
            color: #fff;
            padding: 5px 8px;
            margin-top: 14px;
            margin-bottom: 0;
            border-radius: 3px 3px 0 0;
        }

        /* Report table */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-bottom: 6px;
        }
        .report-table th {
            border: 1px solid #000;
            background: #e9ecef;
            text-align: center;
            font-weight: bold;
            padding: 4px 3px;
            font-size: 9.5px;
        }
        .report-table td {
            border: 1px solid #ccc;
            padding: 4px 4px;
            vertical-align: middle;
            font-size: 9.5px;
        }
        .center { text-align: center; }
        .total-row td {
            border-top: 2px solid #000;
            background: #f0f0f0;
            font-size: 9.5px;
        }

        /* Spacer between sections */
        .section-gap { height: 10px; }

        /* Signature area */
        .signature-area {
            margin-top: 20px;
            width: 100%;
            border-collapse: collapse;
        }
        .signature-area td { padding: 3px 0; vertical-align: top; font-size: 10px; }
        .sig-line {
            display: inline-block;
            width: 200px;
            border-top: 1px solid #000;
            margin-top: 30px;
            text-align: center;
        }

        /* Footer note */
        .footer-note {
            margin-top: 14px;
            font-size: 8px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body onload="window.print()">

    <div class="print-actions">
        <button onclick="window.print()">🖨 Save as PDF / Print</button>
    </div>

    <!-- DepEd Header -->
    <div class="deped-header">
        <img src="/inventory_sys/assets/img/deped.png" alt="DepEd Logo">
        <div class="republic">Republic of the Philippines</div>
        <div class="department">Department of Education</div>
        <div class="sub">Region VIII — Eastern Visayas</div>
        <div class="sub">Schools Division of Catbalogan City</div>
        <div class="sub">San Roque Elementary School</div>
        <div style="font-size:9px; margin-top:2px;">Brgy. San Roque, Catbalogan City</div>
    </div>

    <!-- Report Title -->
    <div class="report-title">Learning Resources Inventory Report</div>
    <div class="report-subtitle"><?php echo $reportTitle; ?></div>

    <!-- Meta Information -->
    <table class="meta-row">
        <tr>
            <td><strong>Entity:</strong> San Roque Elementary School</td>
            <td class="right"><strong>Date Generated:</strong> <?php echo $reportDate; ?></td>
        </tr>
        <tr>
            <td><strong>Report Type:</strong> <?php echo htmlspecialchars($type === 'All' ? 'Science & Math Equipment' : $type . ' Equipment'); ?></td>
            <td class="right"><strong>School Year:</strong> <?php echo date('Y') . '–' . (date('Y') + 1); ?></td>
        </tr>
    </table>

    <?php if (!empty($scienceItems)): ?>
        <?php renderSection($scienceItems, 'Science', '#c0392b'); ?>
    <?php endif; ?>

    <?php if (!empty($scienceItems) && !empty($mathItems)): ?>
        <div class="section-gap"></div>
    <?php endif; ?>

    <?php if (!empty($mathItems)): ?>
        <?php renderSection($mathItems, 'Math', '#0D3B66'); ?>
    <?php endif; ?>

    <?php if (empty($scienceItems) && empty($mathItems)): ?>
        <p style="text-align:center; color:#888; margin-top:30px;">No equipment data found.</p>
    <?php endif; ?>

    <!-- Signature Area -->
    <table class="signature-area">
        <tr>
            <td style="width:50%;">
                <strong>Prepared by:</strong><br><br><br>
                <div class="sig-line"></div><br>
                <strong>IAN KEVIN T. MENDOVA</strong><br>
                Administrative Officer II
            </td>
            <td style="width:50%; text-align:right;">
                <strong>Noted by:</strong><br><br><br>
                <div class="sig-line"></div><br>
                <strong>ROSELLE U. GAYAMAT</strong><br>
                School Head
            </td>
        </tr>
    </table>

    <div class="footer-note">
        This report is system-generated from Project IAN – Inventory and Asset Navigator. Choose "Save as PDF" in the print dialog to download.
    </div>

</body>
</html>

