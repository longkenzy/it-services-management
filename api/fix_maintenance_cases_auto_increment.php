<?php
/**
 * API Fix Auto Increment cho bảng maintenance_cases
 * File: api/fix_maintenance_cases_auto_increment.php
 * Mục đích: Sửa lỗi auto-increment bị reset về 0
 * Tác giả: IT Support Team
 * Ngày tạo: 2024-12-19
 */

header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/session.php';

// Kiểm tra đăng nhập và quyền admin
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Kiểm tra quyền admin (tùy chọn)
$current_role = getCurrentUserRole();
if ($current_role !== 'admin' && $current_role !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Insufficient permissions']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $response = [
        'success' => true,
        'steps' => [],
        'final_status' => null
    ];

    // Bước 1: Kiểm tra cấu trúc bảng
    $stmt = $pdo->query("DESCRIBE maintenance_cases");
    $columns = $stmt->fetchAll();
    
    $idColumn = null;
    foreach ($columns as $column) {
        if ($column['Field'] === 'id') {
            $idColumn = $column;
            break;
        }
    }
    
    if (!$idColumn) {
        throw new Exception("Không tìm thấy cột 'id' trong bảng maintenance_cases");
    }
    
    $response['steps'][] = [
        'step' => 1,
        'action' => 'Kiểm tra cấu trúc bảng',
        'status' => 'success',
        'details' => "Cột 'id': " . $idColumn['Type'] . " | Extra: " . $idColumn['Extra']
    ];

    // Bước 2: Kiểm tra giá trị auto_increment hiện tại
    $stmt = $pdo->query("SHOW TABLE STATUS LIKE 'maintenance_cases'");
    $tableStatus = $stmt->fetch();
    $currentAutoIncrement = $tableStatus['Auto_increment'];
    
    $response['steps'][] = [
        'step' => 2,
        'action' => 'Kiểm tra AUTO_INCREMENT hiện tại',
        'status' => 'success',
        'details' => "Giá trị hiện tại: " . ($currentAutoIncrement ?: 'NULL')
    ];

    // Bước 3: Tìm ID cao nhất
    $stmt = $pdo->query("SELECT MAX(id) as max_id FROM maintenance_cases");
    $result = $stmt->fetch();
    $maxId = $result['max_id'];
    
    $response['steps'][] = [
        'step' => 3,
        'action' => 'Tìm ID cao nhất',
        'status' => 'success',
        'details' => "ID cao nhất: " . ($maxId ?: 'NULL (bảng trống)')
    ];

    // Bước 4: Tính toán giá trị AUTO_INCREMENT mới
    $nextId = $maxId ? $maxId + 1 : 1;
    
    $response['steps'][] = [
        'step' => 4,
        'action' => 'Tính toán AUTO_INCREMENT mới',
        'status' => 'success',
        'details' => "Giá trị mới sẽ là: " . $nextId
    ];

    // Bước 5: Sửa cấu trúc cột id nếu cần
    if (strpos($idColumn['Extra'], 'auto_increment') === false) {
        $alterSql = "ALTER TABLE maintenance_cases MODIFY COLUMN id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
        $pdo->exec($alterSql);
        
        $response['steps'][] = [
            'step' => 5,
            'action' => 'Sửa cấu trúc cột id',
            'status' => 'success',
            'details' => 'Đã thêm auto_increment cho cột id'
        ];
    } else {
        $response['steps'][] = [
            'step' => 5,
            'action' => 'Kiểm tra cấu trúc cột id',
            'status' => 'success',
            'details' => 'Cột id đã có auto_increment'
        ];
    }

    // Bước 6: Reset AUTO_INCREMENT
    $resetSql = "ALTER TABLE maintenance_cases AUTO_INCREMENT = $nextId";
    $pdo->exec($resetSql);
    
    $response['steps'][] = [
        'step' => 6,
        'action' => 'Reset AUTO_INCREMENT',
        'status' => 'success',
        'details' => "Đã reset về " . $nextId
    ];

    // Bước 7: Kiểm tra lại
    $stmt = $pdo->query("SHOW TABLE STATUS LIKE 'maintenance_cases'");
    $tableStatusAfter = $stmt->fetch();
    $newAutoIncrement = $tableStatusAfter['Auto_increment'];
    
    $response['steps'][] = [
        'step' => 7,
        'action' => 'Kiểm tra sau khi sửa',
        'status' => 'success',
        'details' => "Giá trị mới: " . $newAutoIncrement
    ];

    // Bước 8: Test insert
    $testCode = 'TEST_' . date('YmdHis');
    $stmt = $pdo->prepare("INSERT INTO maintenance_cases (case_code, maintenance_request_id, request_type, assigned_to, status, created_by) VALUES (?, 1, 'Test Case', 1, 'Tiếp nhận', 1)");
    $stmt->execute([$testCode]);
    
    $testId = $pdo->lastInsertId();
    
    // Xóa record test
    $stmt = $pdo->prepare("DELETE FROM maintenance_cases WHERE case_code = ?");
    $stmt->execute([$testCode]);
    
    $response['steps'][] = [
        'step' => 8,
        'action' => 'Test insert',
        'status' => 'success',
        'details' => "Test thành công với ID: " . $testId
    ];

    // Thông tin cuối cùng
    $response['final_status'] = [
        'auto_increment_value' => $newAutoIncrement,
        'next_id_will_be' => $nextId,
        'message' => 'Auto increment đã được sửa thành công'
    ];

    // Log hoạt động
    $current_user_id = getCurrentUserId();
    if ($current_user_id) {
        $log_message = "Fix auto increment cho bảng maintenance_cases - Từ: " . ($currentAutoIncrement ?: 'NULL') . " -> " . $newAutoIncrement;
        $log_sql = "INSERT INTO user_activity_logs (user_id, activity, details, ip_address) VALUES (?, ?, ?, ?)";
        $log_stmt = $pdo->prepare($log_sql);
        $log_stmt->execute([
            $current_user_id,
            'FIX_MAINTENANCE_CASES_AUTO_INCREMENT',
            $log_message,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Database error in fix_maintenance_cases_auto_increment.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Database error: ' . $e->getMessage(),
        'steps' => $response['steps'] ?? []
    ]);
} catch (Exception $e) {
    error_log("Error in fix_maintenance_cases_auto_increment.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Error: ' . $e->getMessage(),
        'steps' => $response['steps'] ?? []
    ]);
}
?>
