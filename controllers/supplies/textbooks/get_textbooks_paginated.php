<?php
require_once dirname(__DIR__, 3) . '/controllers/auth/auth.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $schoolType = 'Elementary';
    try {
        $schoolTypeValue = $pdo->query('SELECT type FROM school_details ORDER BY id ASC LIMIT 1')->fetchColumn();
        if (is_string($schoolTypeValue)) {
            $t = trim($schoolTypeValue);
            if (strcasecmp($t, 'Secondary Level') === 0 || strcasecmp($t, 'Secondary') === 0) {
                $schoolType = 'Secondary';
            } elseif (strcasecmp($t, 'Regional Office') === 0 || strcasecmp($t, 'Division Office') === 0) {
                $schoolType = 'All';
            }
        }
    } catch (PDOException $e) {
        // Keep default Elementary
    }

    $elemGrades = [
        'Kinder', 'Kindergarten',
        'Grade I', 'Grade II', 'Grade III', 'Grade IV', 'Grade V', 'Grade VI',
        'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
        'grade_1', 'grade_2', 'grade_3', 'grade_4', 'grade_5', 'grade_6'
    ];

    $secGrades = [
        'Grade VII', 'Grade VIII', 'Grade IX', 'Grade X', 'Grade XI', 'Grade XII',
        'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12',
        'grade_7', 'grade_8', 'grade_9', 'grade_10', 'grade_11', 'grade_12'
    ];

    $gradeAliases = [
        'kindergarten' => 'Kinder',
        'kinder'       => 'Kinder',
        'grade 1'      => 'Grade I',
        'grade_1'      => 'Grade I',
        'grade i'      => 'Grade I',
        'grade 2'      => 'Grade II',
        'grade_2'      => 'Grade II',
        'grade ii'     => 'Grade II',
        'grade 3'      => 'Grade III',
        'grade_3'      => 'Grade III',
        'grade iii'    => 'Grade III',
        'grade 4'      => 'Grade IV',
        'grade_4'      => 'Grade IV',
        'grade iv'     => 'Grade IV',
        'grade 5'      => 'Grade V',
        'grade_5'      => 'Grade V',
        'grade v'      => 'Grade V',
        'grade 6'      => 'Grade VI',
        'grade_6'      => 'Grade VI',
        'grade vi'     => 'Grade VI',
        'grade 7'      => 'Grade VII',
        'grade_7'      => 'Grade VII',
        'grade vii'    => 'Grade VII',
        'grade 8'      => 'Grade VIII',
        'grade_8'      => 'Grade VIII',
        'grade viii'   => 'Grade VIII',
        'grade 9'      => 'Grade IX',
        'grade_9'      => 'Grade IX',
        'grade ix'     => 'Grade IX',
        'grade 10'     => 'Grade X',
        'grade_10'     => 'Grade X',
        'grade x'      => 'Grade X',
        'grade 11'     => 'Grade XI',
        'grade_11'     => 'Grade XI',
        'grade xi'     => 'Grade XI',
        'grade 12'     => 'Grade XII',
        'grade_12'     => 'Grade XII',
        'grade xii'    => 'Grade XII',
    ];

    $typeCondition = '';
    if ($schoolType === 'Elementary') {
        $quoted = "'" . implode("','", $elemGrades) . "'";
        $typeCondition = "grade_level IN ($quoted)";
    } elseif ($schoolType === 'Secondary') {
        $quoted = "'" . implode("','", $secGrades) . "'";
        $typeCondition = "grade_level IN ($quoted)";
    }

    $draw = max(0, (int) ($_GET['draw'] ?? 1));
    $start = max(0, (int) ($_GET['start'] ?? 0));
    $length = (int) ($_GET['length'] ?? 10);
    $length = ($length > 0 && $length <= 100) ? $length : 10;
    $search = trim((string) ($_GET['search']['value'] ?? ''));

    $whereConditions = [];
    if ($typeCondition !== '') {
        $whereConditions[] = $typeCondition;
    }

    $bindings = [];
    if ($search !== '') {
        $whereConditions[] = '(lr_item LIKE :item OR grade_level LIKE :grade OR lr_subject LIKE :subject OR lr_qty LIKE :qty OR lr_unit LIKE :unit OR recipient LIKE :recipient OR `condition` LIKE :condition OR created_at LIKE :created)';
        $term = '%' . $search . '%';
        $bindings = [':item' => $term, ':grade' => $term, ':subject' => $term, ':qty' => $term, ':unit' => $term, ':recipient' => $term, ':condition' => $term, ':created' => $term];
    }

    $where = !empty($whereConditions) ? ' WHERE ' . implode(' AND ', $whereConditions) : '';

    $baseWhere = $typeCondition !== '' ? ' WHERE ' . $typeCondition : '';
    $total = (int) $pdo->query('SELECT COUNT(*) FROM lr_textbooks' . $baseWhere)->fetchColumn();

    if ($search === '') {
        $filtered = $total;
    } else {
        $count = $pdo->prepare('SELECT COUNT(*) FROM lr_textbooks' . $where);
        $count->execute($bindings);
        $filtered = (int) $count->fetchColumn();
    }

    $columns = [0 => 'lr_item', 1 => 'grade_level', 2 => 'lr_subject', 3 => 'lr_qty', 4 => 'lr_unit', 5 => 'recipient', 6 => '`condition`', 7 => 'created_at'];
    $hasOrder = isset($_GET['order'][0]['column']);
    $orderIdx = $hasOrder ? (int) $_GET['order'][0]['column'] : -1;
    $orderColumn = $hasOrder ? ($columns[$orderIdx] ?? 'id') : 'id';
    $direction = strtolower((string) ($_GET['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
    if ($hasOrder && !isset($columns[$orderIdx])) $orderColumn = 'id';

    $sql = 'SELECT id, lr_item, grade_level, lr_subject, lr_qty, lr_unit, recipient, `condition`, created_at FROM lr_textbooks'
        . $where . ' ORDER BY ' . $orderColumn . ' ' . $direction . ', id DESC LIMIT :offset, :limit';
    $stmt = $pdo->prepare($sql);
    foreach ($bindings as $key => $value) $stmt->bindValue($key, $value, PDO::PARAM_STR);
    $stmt->bindValue(':offset', $start, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $length, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $g = strtolower(trim((string)($row['grade_level'] ?? '')));
        if (isset($gradeAliases[$g])) {
            $row['grade_level'] = $gradeAliases[$g];
        }
    }
    unset($row);

    echo json_encode(['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $rows], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    // Keep compatibility with databases predating the optional condition column.
    if (stripos($e->getMessage(), 'condition') !== false) {
        try {
            $baseWhere = (!empty($typeCondition)) ? ' WHERE ' . $typeCondition : '';
            $total = (int) $pdo->query('SELECT COUNT(*) FROM lr_textbooks' . $baseWhere)->fetchColumn();
            $fallback = $pdo->prepare("SELECT id, lr_item, grade_level, lr_subject, lr_qty, lr_unit, recipient, 'Good' AS `condition`, created_at FROM lr_textbooks" . $where . " ORDER BY id DESC LIMIT :offset, :limit");
            foreach ($bindings as $key => $value) $fallback->bindValue($key, $value, PDO::PARAM_STR);
            $fallback->bindValue(':offset', max(0, (int) ($_GET['start'] ?? 0)), PDO::PARAM_INT);
            $fallback->bindValue(':limit', min(100, max(1, (int) ($_GET['length'] ?? 10))), PDO::PARAM_INT);
            $fallback->execute();
            $rows = $fallback->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$row) {
                $g = strtolower(trim((string)($row['grade_level'] ?? '')));
                if (isset($gradeAliases[$g])) {
                    $row['grade_level'] = $gradeAliases[$g];
                }
            }
            unset($row);
            echo json_encode(['draw' => (int) ($_GET['draw'] ?? 1), 'recordsTotal' => $total, 'recordsFiltered' => ($search === '' ? $total : count($rows)), 'data' => $rows], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $fallbackError) {
            error_log('get_textbooks_paginated.php fallback error: ' . $fallbackError->getMessage());
        }
    }
    error_log('get_textbooks_paginated.php error: ' . $e->getMessage());
    echo json_encode(['draw' => (int) ($_GET['draw'] ?? 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => 'Unable to load textbooks.']);
}
