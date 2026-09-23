<?php
session_start();
require_once dirname(__DIR__, 3) . '/controllers/auth/auth.php';
require_once dirname(__DIR__, 3) . '/config/db.php';
require_once dirname(__DIR__, 3) . '/includes/sms_gateway.php';

if (isset($_POST['save_textbook'])) {
    $lr_item     = trim($_POST['lr_item'] ?? '');
    $grade_level = trim($_POST['grade_level'] ?? '');
    $lr_subject  = trim($_POST['lr_subject'] ?? '');
    $lr_qty      = intval($_POST['lr_qty'] ?? 0);
    $lr_unit     = trim($_POST['lr_unit'] ?? 'pc');
    $recipient   = trim($_POST['recipient'] ?? '');
    $condition   = trim($_POST['condition'] ?? 'Good');

    try {
        $stmt = $pdo->prepare("INSERT INTO lr_textbooks (lr_item, grade_level, lr_subject, lr_qty, lr_unit, recipient, `condition`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$lr_item, $grade_level, $lr_subject, $lr_qty, $lr_unit, $recipient, $condition]);

        // The textbook is saved before notification. SMS is best-effort and
        // cannot turn a successful insert into a failed add operation.
        try {
            $recipientStmt = $pdo->prepare('SELECT emp_phone FROM employee WHERE emp_name = ? LIMIT 1');
            $recipientStmt->execute([$recipient]);
            $recipientPhone = $recipientStmt->fetchColumn();

            if (!is_string($recipientPhone) || trim($recipientPhone) === '') {
                $smsResult = [
                    'enabled' => true,
                    'sent' => false,
                    'message' => 'No mobile number on file for ' . $recipient . '.'
                ];
            } else {
                $smsResult = send_release_sms([$recipientPhone], implode("\n", [
                    'Mam/Sir (' . $recipient . '),',
                    '',
                    'A textbook has been added to your assigned learning resources:',
                    $lr_item . ' - ' . $lr_subject . ' - ' . $grade_level,
                    'Quantity: ' . $lr_qty . ' ' . $lr_unit,
                    'Condition: ' . $condition,
                    '',
                    'Please contact the supplies office if you have any questions.',
                    'Thank you',
                    '',
                    'IAN KEVIN T. MENDOVA',
                    'Admin. Officer II'
                ]));
            }
        } catch (Throwable $smsError) {
            error_log('Textbook SMS notification error: ' . $smsError->getMessage());
            $smsResult = [
                'enabled' => true,
                'sent' => false,
                'message' => 'SMS notification could not be sent.'
            ];
        }

        $smsStatus = empty($smsResult['enabled'])
            ? 'disabled'
            : (!empty($smsResult['sent']) ? 'sent' : 'failed');

        header('Location: ../../../textbooks.php?success=added&sms=' . $smsStatus);
        exit();
    } catch (PDOException $e) {
        error_log('controllers/supplies/textbooks/insert_textbook.php error: ' . $e->getMessage());
        echo 'A server error occurred while saving this record. Please try again.';
    }
} else {
    header("Location: ../../../textbooks.php");
    exit();
}
?>
