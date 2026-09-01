<?php
session_start();
require_once '../../../controllers/auth/auth.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

$supplyId = intval($_GET['id'] ?? 0);

if ($supplyId <= 0) {
    die('Invalid stock card item.');
}

try {
    $supplyStmt = $pdo->prepare(
        'SELECT id, supply_code, supply_name, supply_unit, reference FROM supplies WHERE id = ? LIMIT 1'
    );
    $supplyStmt->execute([$supplyId]);
    $supply = $supplyStmt->fetch(PDO::FETCH_ASSOC);

    if (!$supply) {
        die('Supply item not found.');
    }

    $historyStmt = $pdo->prepare(
        "SELECT id, transaction_date, transaction_type, qty, reference, recepient, item_unit
         FROM stock_card
         WHERE supply_code = ?
         ORDER BY transaction_date ASC, id ASC"
    );
    $historyStmt->execute([$supply['supply_code']]);
    $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Unable to load the stock card.');
}

$balance = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stock Card - <?php echo htmlspecialchars($supply['supply_name']); ?></title>
    <style>
        @page { size: A4 landscape; margin: 9mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #000; font-family: Arial, Helvetica, sans-serif; font-size: 10px; }
        .print-actions { text-align: right; margin-bottom: 8px; }
        .print-actions button { padding: 6px 12px; border: 0; border-radius: 4px; background: #0D3B66; color: #fff; cursor: pointer; }
        .annex { text-align: right; font-size: 9px; }
        .title { margin: 8px 0 14px; text-align: center; font-size: 14px; font-weight: 700; }
        .details { width: 100%; margin-bottom: 9px; border-collapse: collapse; }
        .details td { padding: 2px 0; vertical-align: top; }
        .details .right { text-align: right; }
        .history { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .history th, .history td { border: 1px solid #000; padding: 4px 3px; vertical-align: middle; }
        .history th { font-weight: 700; text-align: center; }
        .history .center { text-align: center; }
        .history .right { text-align: right; }
        .empty-row td { height: 26px; }
        .note { margin-top: 8px; font-size: 8.5px; }
        @media print {
            .print-actions { display: none; }
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
    <div class="print-actions"><button type="button" onclick="window.print()">Save as PDF / Print</button></div>
    <div class="deped-header">
        <img src="/inventory_sys/assets/img/deped.png" alt="DepEd Logo">
        <div class="republic">Republic of the Philippines</div>
        <div class="department">Department of Education</div>
        <div class="region">Region VIII — Eastern Visayas</div>
        <div class="division">Schools Division of Catbalogan City</div>
        <div class="school">San Roque Elementary School</div>
        <div class="address" style="font-size: 10px; font-weight: normal; margin-top: 2px;">Brgy. San Roque, Catbalogan City</div>
    </div>
    <div class="annex">Annex A.1</div>
    <div class="title">STOCK CARD</div>

    <table class="details">
        <tr>
            <td><strong>Entity Name:</strong> San Roque Elementary School</td>
            <td class="right"><strong>Fund Cluster:</strong> ____________________</td>
        </tr>
        <tr>
            <td><strong>Stock Card Item:</strong> <?php echo htmlspecialchars($supply['supply_name']); ?></td>
            <td class="right"><strong>Stock Card No.:</strong> <?php echo htmlspecialchars($supply['supply_code']); ?></td>
        </tr>
        <tr>
            <td><strong>Description:</strong> <?php echo htmlspecialchars($supply['supply_name']); ?> (<?php echo htmlspecialchars($supply['supply_unit']); ?>)</td>
            <td class="right"><strong>Reference:</strong>MOOE/DONATION</td>
        </tr>
    </table>

    <table class="history">
        <colgroup>
            <col style="width: 8%;">
            <col style="width: 14%;">
            <col style="width: 7%;">
            <col style="width: 8%;">
            <col style="width: 9%;">
            <col style="width: 8%;">
            <col style="width: 7%;">
            <col style="width: 17%;">
            <col style="width: 7%;">
            <col style="width: 15%;">
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2">Date</th>
                <th rowspan="2">Reference</th>
                <th colspan="3">Receipt</th>
                <th colspan="3">Issue / Transfer / Disposal</th>
                <th colspan="1">Balance</th>
                <th rowspan="2">Remarks</th>
            </tr>
            <tr>
                <th>Qty.</th>
                <th>Unit Cost</th>
                <th>Total Cost</th>
                <th>Item No.</th>
                <th>Qty.</th>
                <th>Office / Officer</th>
                <th>Qty.</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($history as $entry): ?>
                <?php
                    $quantity = (int) $entry['qty'];
                    $isIn = strtoupper($entry['transaction_type']) === 'IN';
                    $balance += $isIn ? $quantity : -$quantity;
                ?>
                <tr>
                    <td class="center"><?php echo htmlspecialchars(date('m/d/Y', strtotime($entry['transaction_date']))); ?></td>
                    <td><?php echo htmlspecialchars($entry['reference']); ?></td>
                    <td class="center"><?php echo $isIn ? $quantity : ''; ?></td>
                    <td></td>
                    <td></td>
                    <td class="center"><?php echo $isIn ? '' : htmlspecialchars($supply['supply_code']); ?></td>
                    <td class="center"><?php echo $isIn ? '' : $quantity; ?></td>
                    <td><?php echo $isIn ? '' : htmlspecialchars($entry['recepient'] ?? ''); ?></td>
                    <td class="center"><?php echo $balance; ?></td>
                    <td><?php echo $isIn ? 'IN - ' . htmlspecialchars($entry['recepient'] ?? '') : 'OUT'; ?></td>
                </tr>
            <?php endforeach; ?>
            <?php for ($row = count($history); $row < 12; $row++): ?>
                <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
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
        </tr>
    </table>
    <div class="note">This stock card is generated from the inventory system. Choose “Save as PDF” in the print dialog to download it as a PDF.</div>
</body>
</html>
