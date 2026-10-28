<?php
/**
 * Apply additive schema fixes for existing inventory databases.
 * Run from the project root with PHP CLI. No rows are deleted or rewritten.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__, 2) . '/config/db.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
    version INT NOT NULL PRIMARY KEY,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS school_details (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    Region VARCHAR(255) NOT NULL DEFAULT '',
    Division VARCHAR(255) NOT NULL DEFAULT '',
    school_name VARCHAR(255) NOT NULL,
    head_name VARCHAR(255) NOT NULL,
    head_position VARCHAR(255) NOT NULL,
    custodian_name VARCHAR(255) NOT NULL,
    custodian_position VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS checkout_requests (
    admin_id INT NOT NULL,
    request_key CHAR(32) NOT NULL,
    request_fingerprint CHAR(64) NOT NULL,
    response_json JSON DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (admin_id, request_key),
    KEY idx_checkout_requests_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$indexes = [
    'employee' => [
        'idx_employee_name'     => 'emp_name',
        'idx_employee_position' => 'emp_position',
    ],
    'lr_sme' => [
        'idx_lr_sme_type_code' => 'lr_type, lr_code',
        'idx_lr_sme_unit'      => 'lr_unit',
    ],
    'lr_textbooks' => [
        'idx_lr_textbooks_grade_item' => 'grade_level, lr_item',
        'idx_lr_textbooks_grade_qty'  => 'grade_level, lr_qty',
        'idx_lr_textbooks_subject_qty' => 'lr_subject, lr_qty',
        'idx_lr_textbooks_recipient'  => 'recipient',
    ],
    'nonconsumable' => [
        'idx_nonconsumable_type'      => 'item_type',
        'idx_nonconsumable_recipient' => 'recepient',
        'idx_nonconsumable_unit'      => 'unit_of_measure',
        'idx_nonconsumable_qty_id'    => 'qty_property_card, id',
    ],
    'stock_card' => [
        'idx_stock_card_supply_date' => 'supply_code, transaction_date, id',
        'idx_stock_card_recipient'   => 'recepient',
        'idx_stock_card_unit'        => 'item_unit',
    ],
    'supplies' => [
        'idx_supplies_unit' => 'supply_unit',
        'idx_supplies_name_id' => 'supply_name, id',
        'idx_supplies_qty_id' => 'supply_qty, id',
    ],
    'transaction_log' => [
        'idx_transaction_log_code_id'    => 'trans_code, id',
        'idx_transaction_log_code_employee' => 'trans_code, emp_name, id',
        'idx_transaction_log_created_id' => 'created_at, id',
        'idx_transaction_log_employee'   => 'emp_name',
    ],
];

foreach ($indexes as $table => $tableIndexes) {
    foreach ($tableIndexes as $indexName => $columns) {
        $exists = $pdo->prepare(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1'
        );
        $exists->execute([$table, $indexName]);
        if ($exists->fetchColumn()) {
            continue;
        }

        $quotedColumns = implode(', ', array_map(
            static fn(string $column): string => '`' . str_replace('`', '``', trim($column)) . '`',
            explode(',', $columns)
        ));
        $pdo->exec('ALTER TABLE `' . $table . '` ADD INDEX `' . $indexName . '` (' . $quotedColumns . ')');
        echo "Added {$indexName} on {$table}." . PHP_EOL;
    }
}

$pdo->exec('INSERT IGNORE INTO schema_migrations (version) VALUES (1)');

// ── Migration version 2 (v1.1 upgrade) ───────────────────────────────────────
// Adds columns and tables introduced in v1.1. All statements are idempotent
// (CREATE TABLE IF NOT EXISTS / column existence check before ALTER TABLE).
$v2Applied = (int) $pdo->query('SELECT COUNT(*) FROM schema_migrations WHERE version = 2')->fetchColumn();
if ($v2Applied === 0) {

    // 2a. Add school_address to school_details if it doesn't already exist
    $hasSchoolAddress = (int) $pdo->query(
        "SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name   = 'school_details'
           AND column_name  = 'school_address'"
    )->fetchColumn();
    if ($hasSchoolAddress === 0) {
        $pdo->exec(
            "ALTER TABLE school_details
             ADD COLUMN school_address VARCHAR(255) NOT NULL DEFAULT '' AFTER school_name,
             MODIFY COLUMN school_name  VARCHAR(255) NOT NULL DEFAULT ''"
        );
        echo 'Added school_address column to school_details.' . PHP_EOL;
    }

    // 2b. Create system_settings table if missing for future app flags
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
        setting_key   VARCHAR(100) NOT NULL,
        setting_value VARCHAR(255) NOT NULL,
        updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 2c. Create unit_measure table if missing
    $pdo->exec("CREATE TABLE IF NOT EXISTS unit_measure (
        id        INT NOT NULL AUTO_INCREMENT,
        unit_name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec('INSERT IGNORE INTO schema_migrations (version) VALUES (2)');
    echo 'Migration version 2 applied.' . PHP_EOL;
}

// Migration version 3 adds school jurisdiction fields used by Settings and reports.
$v3Applied = (int) $pdo->query('SELECT COUNT(*) FROM schema_migrations WHERE version = 3')->fetchColumn();
if ($v3Applied === 0) {
    $schoolDetailColumns = [
        'Region' => 'AFTER id',
        'Division' => 'AFTER Region',
    ];
    foreach ($schoolDetailColumns as $columnName => $position) {
        $columnCheck = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );
        $columnCheck->execute(['school_details', $columnName]);
        if ((int) $columnCheck->fetchColumn() === 0) {
            $quotedColumn = '`' . str_replace('`', '``', $columnName) . '`';
            $pdo->exec("ALTER TABLE school_details ADD COLUMN {$quotedColumn} VARCHAR(255) NOT NULL DEFAULT '' {$position}");
            echo "Added {$columnName} column to school_details." . PHP_EOL;
        }
    }

    $pdo->exec('INSERT IGNORE INTO schema_migrations (version) VALUES (3)');
    echo 'Migration version 3 applied.' . PHP_EOL;
}

// Migration version 4 adds the school type field used by setup and Settings.
$v4Applied = (int) $pdo->query('SELECT COUNT(*) FROM schema_migrations WHERE version = 4')->fetchColumn();
if ($v4Applied === 0) {
    $hasSchoolType = (int) $pdo->query(
        "SELECT COUNT(*) FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = 'school_details'
           AND column_name = 'type'"
    )->fetchColumn();
    if ($hasSchoolType === 0) {
        $pdo->exec("ALTER TABLE school_details ADD COLUMN type VARCHAR(255) NOT NULL DEFAULT '' AFTER Division");
        echo 'Added type column to school_details.' . PHP_EOL;
    }
    $pdo->exec('INSERT IGNORE INTO schema_migrations (version) VALUES (4)');
    echo 'Migration version 4 applied.' . PHP_EOL;
}

echo 'Additive inventory schema migration completed.' . PHP_EOL;
