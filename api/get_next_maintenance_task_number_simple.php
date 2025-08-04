<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/db.php';

try {
    $current_year = date('y');
    $current_month = date('m');
    
    // Tìm mã task cuối cùng trong tháng hiện tại
    $sql = "SELECT task_number FROM maintenance_tasks 
            WHERE task_number LIKE ? 
            ORDER BY task_number DESC 
            LIMIT 1";
    
    $pattern = "TBT{$current_year}{$current_month}%";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$pattern]);
    $result = $stmt->fetch();
    
    if ($result) {
        // Lấy số thứ tự từ mã cuối cùng
        $last_code = $result['task_number'];
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
    error_log("Error in get_next_maintenance_task_number_simple.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi tạo mã task: ' . $e->getMessage()
    ]);
}
?> 