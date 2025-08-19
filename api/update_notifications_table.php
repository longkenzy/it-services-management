<?php
/**
 * API cập nhật bảng notifications để hỗ trợ loại thông báo internal_case
 */

header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../config/db.php';

// Chỉ admin mới có thể chạy script này
if (!isLoggedIn() || !hasRole('admin')) {
    echo json_encode([
        'success' => false,
        'message' => 'Không có quyền truy cập'
    ]);
    exit;
}

try {
    // Kiểm tra xem bảng notifications có tồn tại không
    $check_table = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($check_table->rowCount() == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Bảng notifications chưa tồn tại'
        ]);
        exit;
    }
    
    // Kiểm tra cấu trúc hiện tại của cột type
    $check_type = $pdo->prepare("
        SELECT COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'notifications' 
        AND COLUMN_NAME = 'type'
    ");
    $check_type->execute();
    $type_info = $check_type->fetch(PDO::FETCH_ASSOC);
    
    if (!$type_info) {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy cột type trong bảng notifications'
        ]);
        exit;
    }
    
    $current_type = $type_info['COLUMN_TYPE'];
    
    // Kiểm tra xem đã có 'internal_case' chưa
    if (str_contains($current_type, 'internal_case')) {
        echo json_encode([
            'success' => true,
            'message' => 'Loại thông báo internal_case đã tồn tại',
            'current_type' => $current_type
        ]);
        exit;
    }
    
    // Cập nhật ENUM để thêm 'internal_case'
    $alter_sql = "ALTER TABLE notifications MODIFY COLUMN type ENUM('leave_request', 'leave_approved', 'leave_rejected', 'internal_case', 'system') DEFAULT 'system'";
    $pdo->exec($alter_sql);
    
    // Kiểm tra lại sau khi cập nhật
    $check_type->execute();
    $new_type_info = $check_type->fetch(PDO::FETCH_ASSOC);
    $new_type = $new_type_info['COLUMN_TYPE'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật bảng notifications thành công',
        'old_type' => $current_type,
        'new_type' => $new_type
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
