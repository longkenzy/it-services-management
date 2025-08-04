<?php
header('Content-Type: application/json');

// Kiểm tra xem có phải chạy từ web không
if (php_sapi_name() === 'cli') {
    // Nếu chạy từ command line, tạo mã test
    $current_year = date('y');
    $current_month = date('m');
    $task_code = sprintf("TBT%s%s%03d", $current_year, $current_month, 1);
    
    echo json_encode([
        'success' => true,
        'task_code' => $task_code
    ]);
    exit;
}

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../config/db.php';

// Debug: Kiểm tra session
error_log("Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'active' : 'not active'));
error_log("User logged in: " . (isLoggedIn() ? 'yes' : 'no'));

// Khởi tạo session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

try {
    $current_year = date('y');
    $current_month = date('m');
    
    // Tìm mã task cuối cùng trong tháng hiện tại
    $sql = "SELECT task_code FROM maintenance_tasks 
            WHERE task_code LIKE ? 
            ORDER BY task_code DESC 
            LIMIT 1";
    
    $pattern = "TBT{$current_year}{$current_month}%";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$pattern]);
    $result = $stmt->fetch();
    
    if ($result) {
        // Lấy số thứ tự từ mã cuối cùng
        $last_code = $result['task_code'];
        $last_number = intval(substr($last_code, -3));
        $next_number = $last_number + 1;
    } else {
        // Nếu chưa có mã nào trong tháng này
        $next_number = 1;
    }
    
    // Tạo mã mới
    $task_code = sprintf("TBT%s%s%03d", $current_year, $current_month, $next_number);
    
    echo json_encode([
        'success' => true,
        'task_code' => $task_code
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_next_maintenance_task_number.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi tạo mã task'
    ]);
}
?> 