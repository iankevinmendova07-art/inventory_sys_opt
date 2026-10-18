<?php
require_once dirname(__DIR__, 3) . '/includes/session_security.php';
require_once '../../../controllers/auth/auth.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

$supplyId = intval($_GET['id'] ?? 0);
$isAll = isset($_GET['all']) || ($_GET['id'] ?? '') === 'all' || ($supplyId <= 0);

try {
    $schoolDetails = $pdo->query(
        'SELECT school_name, school_address, custodian_name, custodian_position FROM school_details ORDER BY id ASC LIMIT 1'
    )->fetch(PDO::FETCH_ASSOC) ?: [];
    $schoolName = trim((string) ($schoolDetails['school_name'] ?? ''));
    $schoolAddress = trim((string) ($schoolDetails['school_address'] ?? ''));
    $custodianName = $schoolDetails['custodian_name'] ?? '';
    $custodianPosition = $schoolDetails['custodian_position'] ?? '';

    if ($isAll) {
        $supplyStmt = $pdo->query(
            'SELECT id, supply_code, supply_name, supply_unit, reference FROM supplies ORDER BY supply_name ASC'
        );
        $supplies = $supplyStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($supplies)) {
            die('No supply items found.');
        }

    } else {
        $supplyStmt = $pdo->prepare(
            'SELECT id, supply_code, supply_name, supply_unit, reference FROM supplies WHERE id = ? LIMIT 1'
        );
        $supplyStmt->execute([$supplyId]);
        $supply = $supplyStmt->fetch(PDO::FETCH_ASSOC);

        if (!$supply) {
            die('Supply item not found.');
        }
        $supplies = [$supply];

    }

    // Keep the full history for every selected stock card without retaining
    // 100,000+ rows as nested PHP arrays. A temporary file is scoped to this request.
    $historyFile = tmpfile();
    if ($historyFile === false) {
        throw new RuntimeException('Unable to create temporary stock-card storage.');
    }
    $historyRanges = [];
    $historyCounts = [];
    $historySql = 'SELECT id, supply_code, transaction_date, transaction_type, qty, reference, recepient, item_unit FROM stock_card';
    $historyCodes = array_column($supplies, 'supply_code');
    $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
    if (!$isAll) {
        $historyStmt = $pdo->prepare($historySql . ' WHERE supply_code = ? ORDER BY transaction_date ASC, id ASC');
        $historyStmt->execute([$historyCodes[0]]);
    } else {
        $historyStmt = $pdo->query($historySql . ' ORDER BY BINARY supply_code ASC, transaction_date ASC, id ASC');
    }

    $activeKey = null;
    while ($row = $historyStmt->fetch(PDO::FETCH_ASSOC)) {
        $key = 'code:' . (string) $row['supply_code'];
        if ($key !== $activeKey) {
            if ($activeKey !== null) {
                $historyRanges[$activeKey]['end'] = ftell($historyFile);
            }
            $activeKey = $key;
            $historyRanges[$activeKey] = ['start' => ftell($historyFile), 'end' => null];
            $historyCounts[$activeKey] = 0;
        }
        $line = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) . "\n";
        if (fwrite($historyFile, $line) !== strlen($line)) {
            throw new RuntimeException('Unable to write temporary stock-card history.');
        }
        $historyCounts[$activeKey]++;
    }
    if ($activeKey !== null) {
        $historyRanges[$activeKey]['end'] = ftell($historyFile);
    }
} catch (PDOException $e) {
    error_log('controllers/supplies/consumable/print_stock_card.php DB error: ' . $e->getMessage());
    die('Unable to load stock card data.');
} catch (Throwable $e) {
    error_log('controllers/supplies/consumable/print_stock_card.php history error: ' . $e->getMessage());
    die('Unable to load stock card data.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $isAll ? 'Stock Cards - All Items' : 'Stock Card - ' . htmlspecialchars($supplies[0]['supply_name']); ?></title>
    <style>
        @page { size: A4 landscape; margin: 9mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #000; font-family: Arial, Helvetica, sans-serif; font-size: 10px; }
        .print-actions { text-align: right; margin-bottom: 12px; }
        .print-actions button { padding: 6px 14px; border: 0; border-radius: 4px; background: #0D3B66; color: #fff; cursor: pointer; font-size: 12px; font-weight: 600; }
        .print-actions button:hover { background: #092744; }
        .stock-card-sheet {
            page-break-after: always;
            break-after: page;
        }
        .stock-card-sheet:last-child {
            page-break-after: auto;
            break-after: auto;
        }
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
        .signature-table {
            width: 100%;
            margin-top: 10px;
        }
        @media screen {
            body {
                background: #f4f6f9;
                padding: 20px;
            }
            .stock-card-sheet {
                background: #fff;
                padding: 24px;
                margin: 0 auto 30px auto;
                max-width: 280mm;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .stock-card-sheet:last-child {
                margin-bottom: 0;
            }
        }
        @media print {
            body {
                background: none;
                padding: 0;
            }
            .stock-card-sheet {
                background: none;
                padding: 0;
                margin: 0;
                box-shadow: none;
                page-break-after: always;
                break-after: page;
            }
            .stock-card-sheet:last-child {
                page-break-after: auto;
                break-after: auto;
            }
            .print-actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button type="button" onclick="window.print()">Save as PDF / Print <?php echo $isAll ? 'All Stock Cards' : 'Stock Card'; ?></button>
    </div>

    <?php foreach ($supplies as $supply):
        $historyKey = 'code:' . (string) $supply['supply_code'];
        $historyRange = $historyRanges[$historyKey] ?? null;
        $historyCount = $historyCounts[$historyKey] ?? 0;
        if ($historyRange !== null) {
            fseek($historyFile, $historyRange['start']);
        }
        $balance = 0;
    ?>
    <div class="stock-card-sheet">
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
        <div class="annex">Annex A.1</div>
        <div class="title">STOCK CARD</div>

        <table class="details">
            <tr>
                <td><strong>Entity Name:</strong> <?= htmlspecialchars($schoolName, ENT_QUOTES, 'UTF-8') ?></td>
                <td class="right"><strong>Fund Cluster:</strong> ____________________</td>
            </tr>
            <tr>
                <td><strong>Stock Card Item:</strong> <?php echo htmlspecialchars($supply['supply_name']); ?></td>
                <td class="right"><strong>Stock Card No.:</strong> <?php echo htmlspecialchars($supply['supply_code']); ?></td>
            </tr>
            <tr>
                <td><strong>Description:</strong> <?php echo htmlspecialchars($supply['supply_name']); ?> (<?php echo htmlspecialchars($supply['supply_unit']); ?>)</td>
                <td class="right"><strong>Reference:</strong> <?php echo htmlspecialchars(!empty($supply['reference']) ? $supply['reference'] : 'MOOE/DONATION'); ?></td>
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
                <?php while ($historyRange !== null && ftell($historyFile) < $historyRange['end'] && ($historyLine = fgets($historyFile)) !== false): ?>
                    <?php
                        $entry = json_decode($historyLine, true) ?: [];
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
                <?php endwhile; ?>
                <?php for ($row = $historyCount; $row < 12; $row++): ?>
                    <tr class="empty-row"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                <?php endfor; ?>
            </tbody>
        </table>
        <table class="signature-table">
            <tr>
                <td style="width: 50%;">
                    <strong>Prepared by:</strong><br><br><br>
                    <div class="text-center" style="width: 200px;">
                        <strong class="text-uppercase"><?php echo htmlspecialchars(mb_strtoupper($custodianName, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?></strong><br>
                        <span><?php echo htmlspecialchars(mb_strtoupper($custodianPosition, 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </td>
            </tr>
        </table>
        <div class="note">This stock card is generated from the inventory system. Choose “Save as PDF” in the print dialog to download it as a PDF.</div>
    </div>
    <?php endforeach; ?>
</body>
</html>
