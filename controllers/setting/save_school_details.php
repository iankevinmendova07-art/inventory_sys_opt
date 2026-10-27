<?php
require_once dirname(__DIR__, 2) . '/includes/session_security.php';

require_once dirname(__DIR__, 2) . '/controllers/auth/auth.php';
require_once dirname(__DIR__, 2) . '/config/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: ../../setting.php');
    exit;
}

$fields = ['Region', 'Division', 'school_name', 'school_address', 'head_name', 'head_position', 'custodian_name', 'custodian_position'];
$philippineRegions = [
    'NCR - National Capital Region',
    'CAR - Cordillera Administrative Region',
    'Region I - Ilocos Region',
    'Region II - Cagayan Valley',
    'Region III - Central Luzon',
    'Region IV-A - CALABARZON',
    'MIMAROPA Region',
    'Region V - Bicol Region',
    'Region VI - Western Visayas',
    'Region VII - Central Visayas',
    'Region VIII - Eastern Visayas',
    'Region IX - Zamboanga Peninsula',
    'Region X - Northern Mindanao',
    'Region XI - Davao Region',
    'Region XII - SOCCSKSARGEN',
    'Region XIII - Caraga',
    'BARMM - Bangsamoro Autonomous Region in Muslim Mindanao'
];
$values = [];
foreach ($fields as $field) {
    $value = trim((string)($_POST[$field] ?? ''));
    $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
    if ((!in_array($field, ['school_name', 'school_address'], true) && $value === '') || $length > 255) {
        $_SESSION['school_details_status'] = 'invalid';
        header('Location: ../../setting.php');
        exit;
    }
    $values[$field] = $value;
}

if (!in_array($values['Region'], $philippineRegions, true)) {
    $_SESSION['school_details_status'] = 'invalid';
    header('Location: ../../setting.php');
    exit;
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS school_details (
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        Region VARCHAR(255) NOT NULL DEFAULT '',
        Division VARCHAR(255) NOT NULL DEFAULT '',
        school_name VARCHAR(255) NOT NULL DEFAULT '',
        school_address VARCHAR(255) NOT NULL DEFAULT '',
        head_name VARCHAR(255) NOT NULL,
        head_position VARCHAR(255) NOT NULL,
        custodian_name VARCHAR(255) NOT NULL,
        custodian_position VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $employeeCheck = $pdo->prepare('SELECT COUNT(*) FROM employee WHERE emp_name = :employee_name');
    foreach (['head_name', 'custodian_name'] as $employeeField) {
        $employeeCheck->execute(['employee_name' => $values[$employeeField]]);
        if ((int)$employeeCheck->fetchColumn() === 0) {
            $_SESSION['school_details_status'] = 'invalid_employee';
            header('Location: ../../setting.php');
            exit;
        }
    }
    $positionCheck = $pdo->prepare('SELECT COUNT(*) FROM position WHERE position_name = :position_name');
    foreach (['head_position', 'custodian_position'] as $positionField) {
        $positionCheck->execute(['position_name' => $values[$positionField]]);
        if ((int)$positionCheck->fetchColumn() === 0) {
            $_SESSION['school_details_status'] = 'invalid_position';
            header('Location: ../../setting.php');
            exit;
        }
    }

    $existingId = $pdo->query('SELECT id FROM school_details ORDER BY id ASC LIMIT 1')->fetchColumn();
    if ($existingId !== false) {
        $stmt = $pdo->prepare('UPDATE school_details SET Region = :Region, Division = :Division, school_name = :school_name, school_address = :school_address, head_name = :head_name, head_position = :head_position, custodian_name = :custodian_name, custodian_position = :custodian_position WHERE id = :id');
        $values['id'] = (int)$existingId;
        $stmt->execute($values);
    } else {
        $stmt = $pdo->prepare('INSERT INTO school_details (Region, Division, school_name, school_address, head_name, head_position, custodian_name, custodian_position) VALUES (:Region, :Division, :school_name, :school_address, :head_name, :head_position, :custodian_name, :custodian_position)');
        $stmt->execute($values);
    }
    $_SESSION['school_details_status'] = 'saved';
} catch (PDOException $e) {
    error_log('save_school_details.php error: ' . $e->getMessage());
    $_SESSION['school_details_status'] = 'error';
}

header('Location: ../../setting.php');
exit;
