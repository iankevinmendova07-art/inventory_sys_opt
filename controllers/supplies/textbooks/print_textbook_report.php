<?php
session_start();
require_once '../../../controllers/auth/auth.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

try {
    $stmt = $pdo->query("SELECT lr_item, grade_level, lr_subject, lr_qty, lr_unit, recipient, `condition`, created_at FROM lr_textbooks ORDER BY grade_level ASC, lr_item ASC");
    $textbooks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('controllers/supplies/textbooks/print_textbook_report.php DB error: ' . $e->getMessage());
    die('A database error occurred while generating this report. Please try again or contact the administrator.');
}

$gradeLevels = ['Kinder', 'Grade I', 'Grade II', 'Grade III', 'Grade IV', 'Grade V', 'Grade VI'];
$textbooksByGrade = array_fill_keys($gradeLevels, []);
$textbooksByGrade['Other'] = [];

foreach ($textbooks as $textbook) {
    $gradeLevel = trim($textbook['grade_level'] ?? '');
    $group = in_array($gradeLevel, $gradeLevels, true) ? $gradeLevel : 'Other';
    $textbooksByGrade[$group][] = $textbook;
}

$reportDate = date('F d, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Textbooks Inventory Report</title>
    <style>
        @page {
            size: landscape;
            margin: 10mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 2px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px 4px;
            vertical-align: middle;
        }
        th {
            background: #f0f0f0;
            text-align: center;
            text-transform: uppercase;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .no-border td {
            border: none;
            padding: 1px 2px;
        }
        .deped-header {
            text-align: center;
            margin-bottom: 12px;
            line-height: 1.2;
        }
        .deped-header img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            margin-bottom: 4px;
        }
        .deped-header .republic {
            font-size: 11px;
            font-family: "Times New Roman", Times, serif;
            letter-spacing: 0.5px;
        }
        .deped-header .department {
            font-size: 14px;
            font-weight: bold;
            font-family: "Times New Roman", Times, serif;
            margin: 2px 0;
        }
        .deped-header .region,
        .deped-header .division,
        .deped-header .school,
        .deped-header .address {
            font-size: 10.5px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .report-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin: 8px 0 10px;
            text-transform: uppercase;
        }
        .empty-row {
            text-align: center;
            padding: 14px;
        }
        .category-yellow {
            background-color: #FFFF00 !important;
            text-align: left !important;
            font-weight: bold;
            padding-left: 8px !important;
        }
        .total-row {
            background-color: #fafafa;
            font-weight: bold;
        }
        .grand-total-row {
            background-color: #eaeaea;
            font-weight: bold;
        }
        @media screen {
            body {
                background: #eef2f6;
                padding: 15px;
            }
            .report-sheet {
                background: #fff;
                padding: 15px;
                margin: 0 auto;
                max-width: 297mm;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
            }
        }
        @media print {
            .print-btn-container { display: none; }
            body { padding: 0; margin: 0; background: none; }
            .report-sheet {
                padding: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body onload="window.print()">
<div class="print-btn-container text-end" style="margin-bottom: 8px;">
    <button onclick="window.print()">Print / Save as PDF</button>
    <button onclick="window.close()">Close</button>
</div>

<div class="report-sheet">
    <div class="deped-header">
        <img src="/inventory_sys/assets/img/deped.png" onerror="this.src='../../../assets/img/deped.png'" alt="DepEd Logo">
        <div class="republic">Republic of the Philippines</div>
        <div class="department">Department of Education</div>
        <div class="region">Region VIII — Eastern Visayas</div>
        <div class="division">Schools Division of Catbalogan City</div>
        <div class="school">San Roque Elementary School</div>
        <div class="address" style="font-size: 10px; font-weight: normal; margin-top: 2px;">Brgy. San Roque, Catbalogan City</div>
    </div>

    <div class="report-title">Textbooks Inventory Report</div>

    <table class="no-border" style="font-size: 9px; margin-bottom: 6px;">
        <tr>
            <td><strong>Entity Name :</strong> San Roque Elementary School</td>
            <td class="text-end"><strong>Date Generated :</strong> <?php echo htmlspecialchars($reportDate); ?></td>
        </tr>
        <tr>
            <td><strong>Division :</strong> Catbalogan City</td>
            <td class="text-end"><strong>Total Records :</strong> <?php echo count($textbooks); ?></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Item Name</th>
                <th>Subject</th>
                <th>Quantity</th>
                <th>Unit</th>
                <th>Recipient</th>
                <th>Condition</th>
                <th>Date Added</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($textbooks)): ?>
                <tr><td colspan="7" class="empty-row">No textbook records found.</td></tr>
            <?php else: ?>
                <?php $grandTotalQuantity = 0; ?>
                <?php foreach ($textbooksByGrade as $gradeLevel => $gradeTextbooks): ?>
                    <tr>
                        <td colspan="7" class="category-yellow">GRADE LEVEL: <?php echo htmlspecialchars(strtoupper($gradeLevel)); ?></td>
                    </tr>
                    <?php $gradeTotalQuantity = 0; ?>
                    <?php if (empty($gradeTextbooks)): ?>
                        <tr>
                            <td colspan="7" class="empty-row">No textbooks recorded for this grade level.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($gradeTextbooks as $textbook): ?>
                            <?php
                            $quantity = (int) ($textbook['lr_qty'] ?? 0);
                            $gradeTotalQuantity += $quantity;
                            $grandTotalQuantity += $quantity;
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($textbook['lr_item'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($textbook['lr_subject'] ?? ''); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($textbook['lr_qty'] ?? ''); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($textbook['lr_unit'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($textbook['recipient'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($textbook['condition'] ?? ''); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($textbook['created_at'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td colspan="2" class="text-end">TOTAL FOR <?php echo htmlspecialchars(strtoupper($gradeLevel)); ?></td>
                        <td class="text-center"><?php echo $gradeTotalQuantity > 0 ? $gradeTotalQuantity : ''; ?></td>
                        <td colspan="4"></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="grand-total-row">
                    <td colspan="2" class="text-end">GRAND TOTAL</td>
                    <td class="text-center"><?php echo $grandTotalQuantity > 0 ? $grandTotalQuantity : ''; ?></td>
                    <td colspan="4"></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
