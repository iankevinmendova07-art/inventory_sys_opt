<?php
// controllers/supplies/consumable/print_rsmi_month.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__, 3) . '/controllers/auth/auth.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

// Get the selected month and year from GET (e.g., '2026-08')
$selectedMonthYear = isset($_GET['month_year']) ? $_GET['month_year'] : '';

try {
    // 1. Fetch issuance records filtered by selected month/year if provided
    if (!empty($selectedMonthYear)) {
        $stmt = $pdo->prepare("SELECT * FROM transaction_log WHERE DATE_FORMAT(created_at, '%Y-%m') = ? ORDER BY created_at ASC, id ASC");
        $stmt->execute([$selectedMonthYear]);
    } else {
        $stmt = $pdo->query("SELECT * FROM transaction_log ORDER BY created_at ASC, id ASC");
    }
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Determine Property Custodian / Supply Officer Name dynamically
    $custodian_name = "IAN KEVIN MENDOVA";
    
    $empStmt = $pdo->query("SELECT emp_name, emp_position FROM employee");
    $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($employees as $emp) {
        $pos = trim($emp['emp_position'] ?? '');
        if (stripos($pos, 'Administrative Officer') !== false || stripos($pos, 'Custodian') !== false || stripos($pos, 'Property') !== false) {
            $custodian_name = strtoupper(trim($emp['emp_name']));
            break;
        }
    }

    if (!empty($items[0]['release_by'])) {
        $custodian_name = strtoupper(trim($items[0]['release_by']));
    }

    // Determine default date or serial based on selected filter
    if (!empty($selectedMonthYear)) {
        $formattedDate = date('F Y', strtotime($selectedMonthYear . '-01'));
        $serialNo = $selectedMonthYear . '-001';
    } else {
        $latestDate = !empty($items) ? end($items)['created_at'] : date('Y-m-d');
        $formattedDate = date('F d, Y', strtotime($latestDate));
        $serialNo = date('Y-m', strtotime($latestDate)) . '-001';
    }

} catch (Exception $e) {
    error_log('controllers/supplies/consumable/print_rsmi_month.php DB error: ' . $e->getMessage());
    die('A database error occurred while generating this document. Please try again or contact the administrator.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report of Supplies and Materials Issued (RSMI) - Appendix 64</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 12mm 10mm 12mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: "Arial", sans-serif;
            font-size: 10pt;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
        }
        .page-container {
            width: 100%;
            max-width: 190mm;
            margin: 0 auto;
            position: relative;
        }
        .appendix-tag {
            text-align: right;
            font-style: italic;
            font-size: 11pt;
            margin-bottom: 8px;
        }
        .report-title {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            letter-spacing: 0.5px;
            margin-bottom: 20px;
        }
        .header-meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .header-meta td {
            padding: 3px 0;
            font-size: 9.5pt;
            vertical-align: bottom;
        }
        .meta-line {
            display: inline-block;
            border-bottom: 1px solid #000;
            min-width: 160px;
            padding-left: 5px;
            font-weight: 500;
        }
        .main-table {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
            table-layout: fixed;
        }
        .main-table th, .main-table td {
            border: 1px solid #000;
            padding: 4px 5px;
            font-size: 9pt;
            vertical-align: middle;
            word-wrap: break-word;
        }
        .main-table thead th {
            font-weight: bold;
            text-align: center;
        }
        .super-header th {
            font-weight: normal;
            font-style: italic;
            font-size: 8.5pt;
            padding: 3px 5px;
            background-color: #fcfcfc;
        }
        .col-ris { width: 13%; }
        .col-rcc { width: 17%; }
        .col-stock { width: 10%; }
        .col-item { width: 26%; }
        .col-unit { width: 9%; }
        .col-qty { width: 9%; }
        .col-cost { width: 8%; }
        .col-amt { width: 8%; }

        .text-center { text-align: center; }
        .text-start { text-align: left; }
        .text-end { text-align: right; }

        .empty-row td {
            height: 22px;
        }

        .cert-section {
            border: 1.5px solid #000;
            border-top: none;
            padding: 10px 14px 14px 14px;
            width: 100%;
            box-sizing: border-box;
        }
        .cert-text {
            font-size: 9pt;
            margin-bottom: 26px;
        }
        .signature-box {
            text-align: center;
            display: inline-block;
            margin-left: 20px;
        }
        .custodian-name {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9.5pt;
            display: inline-block;
            border-bottom: 1px solid #000;
            min-width: 280px;
            padding-bottom: 2px;
        }
        .custodian-title {
            font-size: 8.5pt;
            line-height: 1.25;
            margin-top: 3px;
        }
        .page-footer-num {
            text-align: center;
            margin-top: 35px;
            font-size: 10pt;
        }

        .action-toolbar {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 10px 16px;
            border-radius: 8px;
            margin-bottom: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .action-btn {
            background-color: #0D3B66;
            color: #fff;
            border: none;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .action-btn:hover {
            background-color: #082642;
        }
        .close-btn {
            background-color: #64748b;
            color: #fff;
            border: none;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
        }
        .close-btn:hover {
            background-color: #475569;
        }

        @media print {
            .action-toolbar {
                display: none !important;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .page-container {
                max-width: 100%;
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="page-container">
    <!-- Non-printable actions bar -->
    <div class="action-toolbar">
        <div style="font-weight: bold; color: #0D3B66;">
            Appendix 64: Report of Supplies and Materials Issued
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="button" class="action-btn" onclick="window.print()">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                    <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                </svg>
                Print RSMI
            </button>
            <button type="button" class="close-btn" onclick="window.close()">Close</button>
        </div>
    </div>

    <!-- Appendix Label -->
    <div class="appendix-tag">Appendix 64</div>

    <!-- Report Title -->
    <div class="report-title">REPORT OF SUPPLIES AND MATERIALS ISSUED</div>

    <!-- Header Metadata -->
    <table class="header-meta">
        <tr>
            <td style="width: 58%;">
                <strong>Entity Name:</strong> <span class="meta-line" style="min-width: 220px; font-weight: bold;">San Roque Elementary School</span>
            </td>
            <td style="width: 42%; text-align: right;">
                <strong>Serial No. :</strong> <span class="meta-line" style="min-width: 170px;"><?php echo htmlspecialchars($serialNo); ?></span>
            </td>
        </tr>
        <tr>
            <td>
                <strong>Fund Cluster:</strong> <span class="meta-line" style="min-width: 220px;"></span>
            </td>
            <td style="text-align: right;">
                <strong>Date :</strong> <span class="meta-line" style="min-width: 170px;"><?php echo htmlspecialchars($formattedDate); ?></span>
            </td>
        </tr>
    </table>

    <!-- Main Table -->
    <table class="main-table">
        <thead>
            <tr class="super-header">
                <th colspan="6">To be filled up by the Property Custodian</th>
                <th colspan="2">To be filled up by the Accounting Division/Unit</th>
            </tr>
            <tr>
                <th class="col-ris">RIS No.</th>
                <th class="col-rcc">Responsibility<br>Center Code</th>
                <th class="col-stock">Stock No.</th>
                <th class="col-item">Item</th>
                <th class="col-unit">Unit</th>
                <th class="col-qty">Quantity<br>Issued</th>
                <th class="col-cost">Unit Cost</th>
                <th class="col-amt">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $rowCount = 0;
            if (!empty($items)): 
                foreach ($items as $row): 
                    $rowCount++;
            ?>
                <tr>
                    <td class="text-center"><?php echo htmlspecialchars($row['trans_code']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['emp_name']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['supply_code']); ?></td>
                    <td class="text-start"><?php echo htmlspecialchars($row['supply_name']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['supply_unit']); ?></td>
                    <td class="text-center"><?php echo htmlspecialchars($row['supply_qty']); ?></td>
                    <td class="text-center"></td>
                    <td class="text-center"></td>
                </tr>
            <?php 
                endforeach; 
            endif; 
            
            // Pad empty rows to maintain full page appearance matching official template
            $minRows = 16;
            for ($i = $rowCount; $i < $minRows; $i++):
            ?>
                <tr class="empty-row">
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

    <!-- Bottom Certification Box -->
    <div class="cert-section">
        <div class="cert-text">
            I hereby certify to the correctness of the above information.
        </div>
        <div style="margin-top: 15px;">
            <div class="signature-box">
                <div class="custodian-name"><?php echo htmlspecialchars($custodian_name); ?></div>
                <div class="custodian-title">
                    Signature over Printed Name of Supply and/or<br>Property Custodian
                </div>
            </div>
        </div>
    </div>

    <!-- Page Number -->
    <div class="page-footer-num">
        159
    </div>
</div>

</body>
</html>