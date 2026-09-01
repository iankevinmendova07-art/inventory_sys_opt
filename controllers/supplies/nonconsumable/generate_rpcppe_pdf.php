<?php
session_start();

// Reliable database connection path
$dbPath = $_SERVER['DOCUMENT_ROOT'] . '/inventory_sys/config/db.php';
if (!file_exists($dbPath)) {
    $dbPath = __DIR__ . '/../../../config/db.php';
}
require_once $dbPath;

if (!isset($pdo)) {
    die("Database connection failed: \$pdo is not defined in config/db.php.");
}

try {
    // 1. Fetch non-consumable items ordered by item_type and description
    $stmt = $pdo->query("SELECT * FROM nonconsumable ORDER BY item_type ASC, description ASC");
    $itemsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Define the exact standard categories in order (matching your official template)
    $categories = [
        "BUILDINGS" => [],
        "SCHOOL BUILDINGS" => [],
        "PARK, PLAZAS AND MONUMENTS" => [],
        "POWER SUPPLY SYSTEMS" => [],
        "COMPUTER SOFTWARE" => [],
        "OFFICE EQUIPMENT" => [],
        "FURNITURE & FIXTURES" => [],
        "COMM. EQUIPMENT" => [],
        "ICT EQUIPMENT" => [],
        "MOTOR VEHICLES" => [],
        "MEDICAL EQUIPMENT" => []
    ];

    foreach ($itemsRaw as $row) {
        $itemType = strtoupper(trim($row['item_type'] ?? ($row['category'] ?? '')));
        if (isset($categories[$itemType])) {
            $categories[$itemType][] = $row;
        } else {
            $categories[$itemType][] = $row;
        }
    }

    // 3. Fetch Signatories dynamically using emp_position
    $adminOfficerName = "___________________________";
    $adminOfficerPos = "Administrative Officer II";
    
    $schoolHeadName = "___________________________";
    $schoolHeadPos = "Principal II / School Head";

    $empQuery = $pdo->query("SHOW TABLES LIKE 'employee'");
    if ($empQuery->rowCount() > 0) {
        $stmtAdmin = $pdo->prepare("SELECT * FROM employee WHERE emp_position LIKE ? OR emp_position LIKE ? LIMIT 1");
        $stmtAdmin->execute(['%Administrative Officer%', '%Custodian%']);
        $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
        if ($admin) {
            $adminOfficerName = $admin['name'] ?? ($admin['emp_name'] ?? $adminOfficerName);
            $adminOfficerPos = $admin['emp_position'] ?? $adminOfficerPos;
        }

        $stmtHead = $pdo->prepare("SELECT * FROM employee WHERE emp_position LIKE ? OR emp_position LIKE ? LIMIT 1");
        $stmtHead->execute(['%Principal%', '%School Head%']);
        $head = $stmtHead->fetch(PDO::FETCH_ASSOC);
        if ($head) {
            $schoolHeadName = $head['name'] ?? ($head['emp_name'] ?? $schoolHeadName);
            $schoolHeadPos = $head['emp_position'] ?? $schoolHeadPos;
        }
    }

} catch (PDOException $e) {
    error_log('controllers/supplies/nonconsumable/generate_rpcppe_pdf.php DB error: ' . $e->getMessage());
    die('A database error occurred while generating this document. Please try again or contact the administrator.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report on the Physical Count of Property, Plant and Equipment (RPCPPE)</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            color: #000;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .page-container {
            width: 100%;
            max-width: 277mm;
            margin: 0 auto;
            box-sizing: border-box;
        }
        .header-title {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 4px;
        }
        .sub-header {
            margin-bottom: 15px;
            font-size: 9.5pt;
            line-height: 1.4;
        }
        table.rpcppe-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            table-layout: fixed;
        }
        table.rpcppe-table th, table.rpcppe-table td {
            border: 1px solid #000;
            padding: 5px 4px;
            text-align: center;
            font-size: 8.5pt;
            word-wrap: break-word;
            overflow: hidden;
            height: 20px;
        }
        table.rpcppe-table th {
            background-color: #f2f2f2;
            vertical-align: middle;
            font-weight: bold;
        }
        /* Optimized Column Widths for A4 Landscape (Total ~277mm available) */
        table.rpcppe-table th:nth-child(1), table.rpcppe-table td:nth-child(1) { width: 12%; } /* Article */
        table.rpcppe-table th:nth-child(2), table.rpcppe-table td:nth-child(2) { width: 22%; } /* Description */
        table.rpcppe-table th:nth-child(3), table.rpcppe-table td:nth-child(3) { width: 10%; } /* Property Number */
        table.rpcppe-table th:nth-child(4), table.rpcppe-table td:nth-child(4) { width: 7%;  } /* Unit of Measure */
        table.rpcppe-table th:nth-child(5), table.rpcppe-table td:nth-child(5) { width: 8%;  } /* Unit Cost */
        table.rpcppe-table th:nth-child(6), table.rpcppe-table td:nth-child(6) { width: 9%;  } /* Total Cost */
        table.rpcppe-table th:nth-child(7), table.rpcppe-table td:nth-child(7) { width: 7%;  } /* Qty Property Card */
        table.rpcppe-table th:nth-child(8), table.rpcppe-table td:nth-child(8) { width: 7%;  } /* Qty Physical Count */
        table.rpcppe-table th:nth-child(9), table.rpcppe-table td:nth-child(9) { width: 5%;  } /* Shortage Qty */
        table.rpcppe-table th:nth-child(10), table.rpcppe-table td:nth-child(10) { width: 7%; } /* Shortage Value */
        table.rpcppe-table th:nth-child(11), table.rpcppe-table td:nth-child(11) { width: 6%; } /* Remarks */

        /* Yellow Category Header */
        .category-yellow {
            background-color: #FFFF00 !important;
            text-align: left !important;
            font-weight: bold;
            padding-left: 8px !important;
        }
        .text-left { text-align: left !important; }
        .text-right { text-align: right !important; }
        
        .signatures {
            margin-top: 40px;
            width: 100%;
            page-break-inside: avoid;
        }
        .signatures td {
            border: none;
            text-align: center;
            vertical-align: top;
            padding-top: 30px;
            font-size: 9.5pt;
        }
        .line {
            border-bottom: 1px solid #000;
            width: 70%;
            margin: 0 auto 5px auto;
        }
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
            .page-container { width: 100%; max-width: none; border: none; }
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
<body>

<div class="page-container">
    <div class="no-print" style="margin-bottom: 15px; text-align: right;">
        <button onclick="window.print();" style="padding: 8px 16px; background: #0D3B66; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Print / Save A4 Landscape PDF</button>
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

    <div class="header-title">REPORT ON THE PHYSICAL COUNT OF PROPERTY, PLANT AND EQUIPMENT</div>

    <div class="sub-header">
        <strong>As of:</strong> ___________________________ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>Fund Cluster:</strong> _________________________________<br><br>
        For which ___________________________________, ________________________ is accountable, having assumed such accountability on ________________________.
    </div>

    <table class="rpcppe-table">
        <thead>
            <tr>
                <th rowspan="2">ARTICLE</th>
                <th rowspan="2">DESCRIPTION</th>
                <th rowspan="2">PROPERTY NUMBER</th>
                <th rowspan="2">UNIT OF MEASURE</th>
                <th rowspan="2">UNIT COST</th>
                <th rowspan="2">TOTAL COST</th>
                <th rowspan="2">QUANTITY per PROPERTY CARD</th>
                <th rowspan="2">QUANTITY per PHYSICAL COUNT</th>
                <th colspan="2">SHORTAGE/OVERAGE</th>
                <th rowspan="2">REMARKS</th>
            </tr>
            <tr>
                <th>Quantity</th>
                <th>Value</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $grandTotalCost = 0;
            $grandTotalPropertyCard = 0;
            $grandTotalPhysicalCount = 0;
            $grandTotalShortageQty = 0;
            $grandTotalShortageValue = 0;

            foreach ($categories as $catName => $items): 
                $subTotalCost = 0;
                $subTotalPropertyCard = 0;
                $subTotalPhysicalCount = 0;
                $subTotalShortageQty = 0;
                $subTotalShortageValue = 0;
            ?>
                <!-- Yellow Item Type Header -->
                <tr>
                    <td colspan="11" class="category-yellow"><?php echo htmlspecialchars($catName); ?></td>
                </tr>

                <?php if (empty($items)): ?>
                    <tr>
                        <td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): 
                        $unitCost = floatval($item['unit_cost'] ?? 0);
                        $totalCost = floatval($item['total_cost'] ?? 0);
                        $qtyCard = intval($item['qty_property_card'] ?? 0);
                        $qtyCount = intval($item['qty_physical_count'] ?? 0);
                        $shortageQty = intval($item['shortage_overage_qty'] ?? 0);
                        $shortageVal = floatval($item['shortage_overage_value'] ?? 0);

                        $subTotalCost += $totalCost;
                        $subTotalPropertyCard += $qtyCard;
                        $subTotalPhysicalCount += $qtyCount;
                        $subTotalShortageQty += $shortageQty;
                        $subTotalShortageValue += $shortageVal;
                    ?>
                        <tr>
                            <td class="text-left"></td>
                            <td class="text-left"><?php echo htmlspecialchars($item['description'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($item['property_number'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($item['unit_of_measure'] ?? ''); ?></td>
                            <td class="text-right"><?php echo $unitCost > 0 ? number_format($unitCost, 2) : ''; ?></td>
                            <td class="text-right"><?php echo $totalCost > 0 ? number_format($totalCost, 2) : ''; ?></td>
                            <td><?php echo $qtyCard > 0 ? $qtyCard : ''; ?></td>
                            <td><?php echo $qtyCount > 0 ? $qtyCount : ''; ?></td>
                            <td><?php echo $shortageQty != 0 ? $shortageQty : ''; ?></td>
                            <td class="text-right"><?php echo $shortageVal != 0 ? number_format($shortageVal, 2) : ''; ?></td>
                            <td class="text-left"><?php echo htmlspecialchars($item['remarks'] ?? ''); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Category Total Row -->
                <tr style="font-weight: bold; background-color: #fafafa;">
                    <td></td>
                    <td class="text-left">TOTAL</td>
                    <td></td><td></td><td></td>
                    <td class="text-right"><?php echo $subTotalCost > 0 ? number_format($subTotalCost, 2) : ''; ?></td>
                    <td><?php echo $subTotalPropertyCard > 0 ? $subTotalPropertyCard : ''; ?></td>
                    <td><?php echo $subTotalPhysicalCount > 0 ? $subTotalPhysicalCount : ''; ?></td>
                    <td><?php echo $subTotalShortageQty != 0 ? $subTotalShortageQty : ''; ?></td>
                    <td class="text-right"><?php echo $subTotalShortageValue != 0 ? number_format($subTotalShortageValue, 2) : ''; ?></td>
                    <td></td>
                </tr>

                <?php 
                $grandTotalCost += $subTotalCost;
                $grandTotalPropertyCard += $subTotalPropertyCard;
                $grandTotalPhysicalCount += $subTotalPhysicalCount;
                $grandTotalShortageQty += $subTotalShortageQty;
                $grandTotalShortageValue += $subTotalShortageValue;
                endforeach; 
                ?>

                <!-- Grand Total Row -->
                <tr style="font-weight: bold; background-color: #eaeaea; font-size: 9pt;">
                    <td colspan="5" class="text-right" style="text-align: right; padding-right: 10px; font-style: italic;">GRAND TOTAL</td>
                    <td class="text-right"><?php echo $grandTotalCost > 0 ? number_format($grandTotalCost, 2) : ''; ?></td>
                    <td><?php echo $grandTotalPropertyCard > 0 ? $grandTotalPropertyCard : ''; ?></td>
                    <td><?php echo $grandTotalPhysicalCount > 0 ? $grandTotalPhysicalCount : ''; ?></td>
                    <td><?php echo $grandTotalShortageQty != 0 ? $grandTotalShortageQty : ''; ?></td>
                    <td class="text-right"><?php echo $grandTotalShortageValue != 0 ? number_format($grandTotalShortageValue, 2) : ''; ?></td>
                    <td></td>
                </tr>
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td style="width: 33%;">
                Certified Correct:<br><br><br>
                <strong><?php echo htmlspecialchars($adminOfficerName); ?></strong><br>
                <div class="line"></div>
                <span style="font-size: 8.5pt; font-weight: normal;"><?php echo htmlspecialchars($adminOfficerPos); ?></span>
            </td>
            <td style="width: 33%;">
                Approved by:<br><br><br>                
                <strong><?php echo htmlspecialchars($schoolHeadName); ?></strong><br>
                <div class="line"></div>
                <span style="font-size: 8.5pt; font-weight: normal;"><?php echo htmlspecialchars($schoolHeadPos); ?></span>
            </td>
            <td style="width: 33%;">
                Verified by:<br><br><br>
                <div class="line"></div>
                <strong>COA Representative</strong><br>
                <span style="font-size: 8.5pt; font-weight: normal;">Auditing Unit</span>
            </td>
        </tr>
    </table>
</div>

</body>
</html>