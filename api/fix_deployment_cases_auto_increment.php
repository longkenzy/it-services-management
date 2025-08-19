<?php
/**
 * API sửa lỗi auto increment cho bảng deployment_cases
 * Endpoint: POST /api/fix_deployment_cases_auto_increment.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/session.php';
require_once '../config/db.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

function log_fix($msg) {
    file_put_contents(__DIR__ . '/fix_auto_increment.log', date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}

try {
    log_fix("Starting auto increment fix for deployment_cases table");
    
    // 1. Kiểm tra cấu trúc bảng
    $describeSql = "DESCRIBE deployment_cases";
    $describeStmt = $pdo->prepare($describeSql);
    $describeStmt->execute();
    $columns = $describeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $idColumn = null;
    foreach ($columns as $column) {
        if ($column['Field'] === 'id') {
            $idColumn = $column;
            break;
        }
    }
    
    if (!$idColumn) {
        throw new Exception('Column id not found in deployment_cases table');
    }
    
    log_fix("Current id column structure: " . json_encode($idColumn));
    
    // 2. Kiểm tra auto increment hiện tại
    $statusSql = "SHOW TABLE STATUS LIKE 'deployment_cases'";
    $statusStmt = $pdo->prepare($statusSql);
    $statusStmt->execute();
    $tableStatus = $statusStmt->fetch(PDO::FETCH_ASSOC);
    
    $currentAutoIncrement = $tableStatus['Auto_increment'] ?? 1;
    log_fix("Current auto increment: $currentAutoIncrement");
    
    // 3. Tìm ID cao nhất
    $maxIdSql = "SELECT MAX(id) as max_id FROM deployment_cases";
    $maxIdStmt = $pdo->prepare($maxIdSql);
    $maxIdStmt->execute();
    $maxResult = $maxIdStmt->fetch(PDO::FETCH_ASSOC);
    $maxId = $maxResult['max_id'] ?? 0;
    
    log_fix("Max ID in table: $maxId");
    
    // 4. Kiểm tra xem có cần sửa không
    $needsFix = false;
    $fixes = [];
    
    // Kiểm tra cấu trúc cột id
    if (strpos($idColumn['Extra'], 'auto_increment') === false) {
        $needsFix = true;
        $fixes[] = 'id_column_structure';
    }
    
    // Kiểm tra auto increment value
    if ($currentAutoIncrement <= $maxId) {
        $needsFix = true;
        $fixes[] = 'auto_increment_value';
    }
    
    if (!$needsFix) {
        log_fix("No fixes needed - auto increment is working correctly");
        echo json_encode([
            'success' => true,
            'message' => 'Auto increment is working correctly',
            'current_auto_increment' => $currentAutoIncrement,
            'max_id' => $maxId
        ]);
        exit;
    }
    
    log_fix("Fixes needed: " . implode(', ', $fixes));
    
    // 5. Thực hiện các sửa chữa
    $results = [];
    
    // Sửa cấu trúc cột id nếu cần
    if (in_array('id_column_structure', $fixes)) {
        log_fix("Fixing id column structure...");
        $fixIdSql = "ALTER TABLE deployment_cases MODIFY COLUMN id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
        $fixIdStmt = $pdo->prepare($fixIdSql);
        $fixIdStmt->execute();
        $results['id_column_structure'] = 'fixed';
        log_fix("Id column structure fixed");
    }
    
    // Sửa auto increment value nếu cần
    if (in_array('auto_increment_value', $fixes)) {
        log_fix("Fixing auto increment value...");
        $nextId = $maxId + 1;
        $fixAutoIncrementSql = "ALTER TABLE deployment_cases AUTO_INCREMENT = ?";
        $fixAutoIncrementStmt = $pdo->prepare($fixAutoIncrementSql);
        $fixAutoIncrementStmt->execute([$nextId]);
        $results['auto_increment_value'] = "set to $nextId";
        log_fix("Auto increment value set to $nextId");
    }
    
    // 6. Kiểm tra lại sau khi sửa
    $statusSql2 = "SHOW TABLE STATUS LIKE 'deployment_cases'";
    $statusStmt2 = $pdo->prepare($statusSql2);
    $statusStmt2->execute();
    $tableStatusAfter = $statusStmt2->fetch(PDO::FETCH_ASSOC);
    
    $newAutoIncrement = $tableStatusAfter['Auto_increment'] ?? 1;
    log_fix("New auto increment after fix: $newAutoIncrement");
    
    // 7. Test insert để kiểm tra
    $testCaseCode = 'TEST_' . date('YmdHis');
    $testSql = "INSERT INTO deployment_cases (
        case_code, deployment_request_id, request_type, assigned_to, status, created_by
    ) VALUES (?, ?, ?, ?, ?, ?)";
    $testStmt = $pdo->prepare($testSql);
    $testStmt->execute([
        $testCaseCode,
        1, // deployment_request_id
        'Test Request Type',
        1, // assigned_to
        'Tiếp nhận',
        1  // created_by
    ]);
    
    $testId = $pdo->lastInsertId();
    log_fix("Test insert result - ID: $testId");
    
    // Xóa record test
    $deleteSql = "DELETE FROM deployment_cases WHERE case_code = ?";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute([$testCaseCode]);
    log_fix("Test record deleted");
    
    $results['test_insert'] = $testId > 0 ? "successful (ID: $testId)" : "failed";
    
    echo json_encode([
        'success' => true,
        'message' => 'Auto increment fixed successfully',
        'fixes_applied' => $results,
        'old_auto_increment' => $currentAutoIncrement,
        'new_auto_increment' => $newAutoIncrement,
        'max_id' => $maxId,
        'test_result' => $testId > 0 ? "successful" : "failed"
    ]);
    
} catch (Exception $e) {
    log_fix("Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
