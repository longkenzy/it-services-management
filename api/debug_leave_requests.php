<?php
/**
 * Debug script để kiểm tra lỗi trong create_leave_request.php
 */

header('Content-Type: application/json');

require_once '../includes/session.php';
require_once '../config/db.php';

try {
    // Kiểm tra kết nối database
    echo "=== Kiểm tra kết nối database ===\n";
    $pdo->query("SELECT 1");
    echo "✓ Kết nối database thành công\n\n";
    
    // Kiểm tra bảng leave_requests
    echo "=== Kiểm tra bảng leave_requests ===\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'leave_requests'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Bảng leave_requests tồn tại\n";
        
        // Kiểm tra cấu trúc bảng
        $stmt = $pdo->query("DESCRIBE leave_requests");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Cấu trúc bảng:\n";
        foreach ($columns as $column) {
            echo "- {$column['Field']}: {$column['Type']} {$column['Null']} {$column['Key']}\n";
        }
    } else {
        echo "✗ Bảng leave_requests không tồn tại\n";
    }
    echo "\n";
    
    // Kiểm tra bảng staffs
    echo "=== Kiểm tra bảng staffs ===\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'staffs'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Bảng staffs tồn tại\n";
        
        // Đếm số nhân viên
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM staffs");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Số nhân viên: {$result['count']}\n";
    } else {
        echo "✗ Bảng staffs không tồn tại\n";
    }
    echo "\n";
    
    // Kiểm tra bảng notifications
    echo "=== Kiểm tra bảng notifications ===\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Bảng notifications tồn tại\n";
    } else {
        echo "✗ Bảng notifications không tồn tại\n";
    }
    echo "\n";
    
    // Kiểm tra bảng activity_logs
    echo "=== Kiểm tra bảng activity_logs ===\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'activity_logs'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Bảng activity_logs tồn tại\n";
    } else {
        echo "✗ Bảng activity_logs không tồn tại\n";
    }
    echo "\n";
    
    // Kiểm tra quyền ghi file
    echo "=== Kiểm tra quyền ghi file ===\n";
    $upload_dir = '../assets/uploads/leave_attachments/';
    if (is_dir($upload_dir)) {
        echo "✓ Thư mục upload tồn tại\n";
    } else {
        echo "✗ Thư mục upload không tồn tại\n";
        if (mkdir($upload_dir, 0777, true)) {
            echo "✓ Đã tạo thư mục upload\n";
        } else {
            echo "✗ Không thể tạo thư mục upload\n";
        }
    }
    
    if (is_writable($upload_dir)) {
        echo "✓ Có quyền ghi vào thư mục upload\n";
    } else {
        echo "✗ Không có quyền ghi vào thư mục upload\n";
    }
    echo "\n";
    
    // Kiểm tra session
    echo "=== Kiểm tra session ===\n";
    if (isLoggedIn()) {
        $current_user = getCurrentUser();
        echo "✓ Đã đăng nhập\n";
        echo "User ID: {$current_user['id']}\n";
        echo "Username: {$current_user['username']}\n";
        echo "Fullname: {$current_user['fullname']}\n";
    } else {
        echo "✗ Chưa đăng nhập\n";
    }
    
} catch (Exception $e) {
    echo "✗ Lỗi: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?> 