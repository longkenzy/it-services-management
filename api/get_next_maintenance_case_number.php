<?php
header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../config/db.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

try {
    $current_year = date('y');
    $current_month = date('m');
    
    // Tìm mã case cuối cùng trong tháng hiện tại
    $sql = "SELECT case_code FROM maintenance_cases 
            WHERE case_code LIKE ? 
            ORDER BY case_code DESC 
            LIMIT 1";
    
    $pattern = "BT{$current_year}{$current_month}%";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$pattern]);
    $result = $stmt->fetch();
    
    if ($result) {
        // Lấy số thứ tự từ mã cuối cùng
        $last_code = $result['case_code'];
        $last_number = intval(substr($last_code, -3));
        $next_number = $last_number + 1;
    } else {
        // Nếu chưa có mã nào trong tháng này
        $next_number = 1;
    }
    
    // Tạo mã mới
    $case_code = sprintf("BT%s%s%03d", $current_year, $current_month, $next_number);
    
    echo json_encode([
        'success' => true,
        'case_code' => $case_code
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_next_maintenance_case_number.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi tạo mã case'
    ]);
}
?> 