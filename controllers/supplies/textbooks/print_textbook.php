<?php
require_once dirname(__DIR__, 2) . '/auth/auth.php';

require_once dirname(__DIR__, 3) . '/config/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die('Invalid item ID provided.');
}

try {
    // Query lr_textbooks and join with employee table to get recipient's position
    $stmt = $pdo->prepare("
        SELECT t.*, e.emp_position AS recipient_position 
        FROM lr_textbooks t 
        LEFT JOIN employee e ON t.recipient = e.emp_name 
        WHERE t.id = ?
    ");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        die('Error: Textbook with ID ' . $id . ' not found in the database.');
    }

    $recipientName = $item['recipient'] ?? 'N/A';
    $recipientPosition = $item['recipient_position'] ?? 'Teacher I';

    $currentDate = date('F d, Y');
    $schoolDetails = $pdo->query(
        'SELECT school_name, school_address, custodian_name, custodian_position FROM school_details ORDER BY id ASC LIMIT 1'
    )->fetch(PDO::FETCH_ASSOC) ?: [];
    $schoolName = trim((string) ($schoolDetails['school_name'] ?? ''));
    $schoolAddress = trim((string) ($schoolDetails['school_address'] ?? ''));
    $receivedFromAdmin = $schoolDetails['custodian_name'] ?? '';
    $receivedFromPosition = $schoolDetails['custodian_position'] ?? '';

} catch (PDOException $e) {
    error_log('controllers/supplies/textbooks/print_textbook.php DB error: ' . $e->getMessage());
    die('A database error occurred while generating this document. Please try again or contact the administrator.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Textbook Property Slip - <?php echo htmlspecialchars($item['lr_item']); ?></title>
    <link href="../../../assets/vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
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
            font-family: "Old English Text MT", "Engravers Old English", "Times New Roman", serif;
            letter-spacing: 0.5px;
        }
        .deped-header .department {
            font-size: 15px;
            font-weight: bold;
            font-family: "Old English Text MT", "Engravers Old English", "Times New Roman", serif;
            margin: 2px 0;
        }
        .deped-header .region, .deped-header .division, .deped-header .school, .deped-header .address {
            font-size: 11px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .deped-header .address {
            font-size: 10px;
            font-weight: normal;
            text-transform: none;
            margin-top: 2px;
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
        <button onclick="window.print()" class="btn btn-sm btn-primary">Print Slip</button>
        <button onclick="window.close()" class="btn btn-sm btn-secondary">Close Tab</button>
    </div>

    <div class="deped-header">
        <img src="../../../assets/img/deped.png" alt="DepEd Logo">
        <div class="republic">Republika ng Pilipinas</div>
        <div class="department">Department of Education</div>
        <div class="region">Region VIII — Eastern Visayas</div>
        <div class="division">Schools Division of Catbalogan City</div>
        <?php if ($schoolName !== ''): ?>
        <div class="school"><?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <div class="address"><?= htmlspecialchars($schoolAddress, ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <div class="header-title">
        TEXTBOOK ISSUANCE SLIP
    </div>

    <div class="mb-3 fw-semibold" style="font-size: 12px;">
        <div>Entity Name: <?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8') ?></div>
    </div>

    <table class="table table-bordered text-center align-middle">
        <thead class="table-light">
            <tr>
                <th>Quantity</th>
                <th>Unit</th>
                <th>Description</th>
                <th>Grade Level</th>
                <th>Subject</th>
                <th>Condition</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php echo htmlspecialchars($item['lr_qty'] ?? 1); ?></td>
                <td><?php echo htmlspecialchars($item['lr_unit'] ?? 'pc'); ?></td>
                <td class="text-start"><?php echo htmlspecialchars($item['lr_item']); ?></td>
                <td><?php echo htmlspecialchars($item['grade_level'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($item['lr_subject'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($item['condition'] ?? 'Good'); ?></td>
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
                    <span class="text-muted" style="font-size: 0.85rem;"><?php echo htmlspecialchars($receivedFromPosition); ?></span><br>
                    <span style="font-size: 0.8rem;">Date: <?php echo htmlspecialchars($currentDate); ?></span>
                </div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
