<?php
require_once dirname(__DIR__, 3) . '/includes/session_security.php';
require_once dirname(__DIR__, 3) . '/controllers/auth/auth.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

$schoolDetails = [];
try {
    $schoolDetails = $pdo->query(
        'SELECT Region, Division, type, school_name, school_address, custodian_name, custodian_position, head_name, head_position FROM school_details ORDER BY id ASC LIMIT 1'
    )->fetch(PDO::FETCH_ASSOC) ?: [];
    $schoolRegion = trim((string) ($schoolDetails['Region'] ?? ''));
    $schoolDivision = trim((string) ($schoolDetails['Division'] ?? ''));
    $schoolName = trim((string) ($schoolDetails['school_name'] ?? ''));
    $schoolAddress = trim((string) ($schoolDetails['school_address'] ?? ''));

    $stmt = $pdo->query("SELECT lr_item, grade_level, lr_subject, lr_qty, lr_unit, recipient, `condition`, created_at FROM lr_textbooks ORDER BY grade_level ASC, lr_item ASC");
} catch (PDOException $e) {
    // If condition column was somehow missing, fallback to standard query
    try {
        $stmt = $pdo->query("SELECT lr_item, grade_level, lr_subject, lr_qty, lr_unit, recipient, 'Good' AS `condition`, created_at FROM lr_textbooks ORDER BY grade_level ASC, lr_item ASC");
    } catch (PDOException $ex) {
        error_log('controllers/supplies/textbooks/print_textbook_report.php DB error: ' . $ex->getMessage());
        die('A database error occurred while generating this report. Please try again or contact the administrator.');
    }
}

$custodianName = $schoolDetails['custodian_name'] ?? '';
$custodianPosition = $schoolDetails['custodian_position'] ?? '';
$headName = $schoolDetails['head_name'] ?? '';
$headPosition = $schoolDetails['head_position'] ?? '';

$isSecondary = strcasecmp(trim((string) ($schoolDetails['type'] ?? '')), 'Secondary') === 0;
if ($isSecondary) {
    $gradeLevels = [
        'grade_7' => 'Grade 7',
        'grade_8' => 'Grade 8',
        'grade_9' => 'Grade 9',
        'grade_10' => 'Grade 10',
        'grade_11' => 'Grade 11',
        'grade_12' => 'Grade 12',
    ];
} else {
    $gradeLevels = [
        'Kinder' => 'Kinder',
        'Grade I' => 'Grade I',
        'Grade II' => 'Grade II',
        'Grade III' => 'Grade III',
        'Grade IV' => 'Grade IV',
        'Grade V' => 'Grade V',
        'Grade VI' => 'Grade VI',
    ];
}
$textbooksByGrade = array_fill_keys(array_keys($gradeLevels), []);
if (!$isSecondary) {
    $textbooksByGrade['Other'] = [];
}
$textbookCount = 0;

$secondaryGradeAliases = [
    'Grade 7' => 'grade_7',
    'Grade 8' => 'grade_8',
    'Grade 9' => 'grade_9',
    'Grade 10' => 'grade_10',
    'Grade 11' => 'grade_11',
    'Grade 12' => 'grade_12',
];

while ($textbook = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $gradeLevel = trim($textbook['grade_level'] ?? '');
    if ($isSecondary && isset($secondaryGradeAliases[$gradeLevel])) {
        $gradeLevel = $secondaryGradeAliases[$gradeLevel];
    }
    if ($isSecondary && !array_key_exists($gradeLevel, $gradeLevels)) {
        continue;
    }
    $group = array_key_exists($gradeLevel, $gradeLevels) ? $gradeLevel : 'Other';
    $textbooksByGrade[$group][] = $textbook;
    $textbookCount++;
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
            font-family: "Old English Text MT", "Engravers Old English", "Times New Roman", serif;
            letter-spacing: 0.5px;
        }
        .deped-header .department {
            font-size: 14px;
            font-weight: bold;
            font-family: "Old English Text MT", "Engravers Old English", "Times New Roman", serif;
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
        .deped-header .address {
            font-size: 10px;
            font-weight: normal;
            text-transform: none;
            margin-top: 2px;
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
        .print-btn-container { position: fixed; top: 16px; right: 16px; z-index: 1000; text-align: right; margin: 0 !important; }
        .print-btn-container button { padding: 6px 10px; border: 0; border-radius: 4px; color: #fff; font-size: 14px; line-height: 1.5; cursor: pointer; }
        .print-btn-container button:first-child { background: #0d6efd; }
        .print-btn-container button:last-child { background: #6c757d; }
    </style>
</head>
<body>
<div class="print-btn-container text-end" style="margin-bottom: 8px;">
    <button onclick="window.print()">Print</button>
    <button onclick="window.close()">Close</button>
</div>

<div class="report-sheet">
    <div class="deped-header">
        <img src="../../../assets/img/deped.png" alt="DepEd Logo">
        <div class="republic">Republika ng Pilipinas</div>
        <div class="department">Department of Education</div>
        <div class="region"><?= htmlspecialchars($schoolRegion ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <div class="division"><?= htmlspecialchars($schoolDivision ?? '', ENT_QUOTES, 'UTF-8') ?></div>
        <?php if ($schoolName !== ''): ?>
        <div class="school"><?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <div class="address"><?= htmlspecialchars($schoolAddress, ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="report-title">Textbooks Inventory Report</div>

    <table class="no-border" style="font-size: 9px; margin-bottom: 6px;">
        <tr>
            <td><strong>Entity Name :</strong> <?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8') ?></td>
            <td class="text-end"><strong>Date Generated :</strong> <?php echo htmlspecialchars($reportDate); ?></td>
        </tr>
        <tr>
            <td><strong>Division :</strong> <?= htmlspecialchars($schoolDivision ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td class="text-end"><strong>Total Records :</strong> <?php echo $textbookCount; ?></td>
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
            <?php if ($textbookCount === 0): ?>
                <tr><td colspan="7" class="empty-row">No textbook records found.</td></tr>
            <?php else: ?>
                <?php $grandTotalQuantity = 0; ?>
                <?php foreach ($textbooksByGrade as $gradeLevel => $gradeTextbooks): ?>
                    <?php if ($gradeLevel === 'Other' && empty($gradeTextbooks)) continue; ?>
                    <?php $gradeLabel = $gradeLevel === 'Other' ? 'Other' : $gradeLevels[$gradeLevel]; ?>
                    <tr>
                        <td colspan="7" class="category-yellow">GRADE LEVEL: <?php echo htmlspecialchars(strtoupper($gradeLabel)); ?></td>
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
                                <td><?php echo htmlspecialchars($textbook['condition'] ?? 'Good'); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars($textbook['created_at'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr class="total-row">
                        <td colspan="2" class="text-end">TOTAL FOR <?php echo htmlspecialchars(strtoupper($gradeLabel)); ?></td>
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
       <table class="signatures">
        <tr>
            <td style="width: 33%;">
                Prepared by:<br><br><br>
                <strong><?php echo htmlspecialchars($custodianName, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                <div class="line"></div>
                <span style="font-size: 8.5pt; font-weight: normal;"><?php echo htmlspecialchars($custodianPosition, ENT_QUOTES, 'UTF-8'); ?></span>
            </td>
            <td style="width: 33%;">
                Noted by:<br><br><br>                
                <strong><?php echo htmlspecialchars($headName, ENT_QUOTES, 'UTF-8'); ?></strong><br>
                <div class="line"></div>
                <span style="font-size: 8.5pt; font-weight: normal;"><?php echo htmlspecialchars($headPosition, ENT_QUOTES, 'UTF-8'); ?></span>
            </td>
        </tr>
    </table>
</div>
</body>
</html>
