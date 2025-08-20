<?php
/**
 * Script test đơn giản để kiểm tra cấu trúc bảng notifications
 */

require_once 'config/db.php';

echo "<h2>Test Database Notifications Structure</h2>";

try {
    // Test 1: Kiểm tra bảng notifications
    echo "<h3>1. Kiểm tra bảng notifications</h3>";
    $check_table = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($check_table->rowCount() > 0) {
        echo "✓ Bảng notifications tồn tại<br>";
    } else {
        echo "✗ Bảng notifications không tồn tại<br>";
        exit;
    }
    
    // Test 2: Kiểm tra cấu trúc cột type
    echo "<h3>2. Kiểm tra cấu trúc cột type</h3>";
    $check_type = $pdo->prepare("
        SELECT COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'notifications' 
        AND COLUMN_NAME = 'type'
    ");
    $check_type->execute();
    $type_info = $check_type->fetch(PDO::FETCH_ASSOC);
    
    if ($type_info) {
        echo "✓ Cột type tồn tại<br>";
        echo "Cấu trúc hiện tại: " . $type_info['COLUMN_TYPE'] . "<br>";
        
        if (str_contains($type_info['COLUMN_TYPE'], 'internal_case')) {
            echo "✓ Loại 'internal_case' đã có trong ENUM<br>";
        } else {
            echo "✗ Loại 'internal_case' chưa có trong ENUM<br>";
            echo "<strong>Cần chạy lệnh SQL sau trong phpMyAdmin:</strong><br>";
            echo "<code>ALTER TABLE notifications MODIFY COLUMN type ENUM('leave_request', 'leave_approved', 'leave_rejected', 'internal_case', 'system') DEFAULT 'system';</code><br>";
        }
    } else {
        echo "✗ Không tìm thấy cột type<br>";
    }
    
    // Test 3: Kiểm tra dữ liệu mẫu
    echo "<h3>3. Kiểm tra dữ liệu mẫu</h3>";
    $check_data = $pdo->query("SELECT COUNT(*) as total FROM notifications");
    $total = $check_data->fetch(PDO::FETCH_ASSOC)['total'];
    echo "Tổng số thông báo hiện có: $total<br>";
    
    // Test 4: Kiểm tra staffs để lấy handler_id mẫu
    echo "<h3>4. Kiểm tra dữ liệu staffs</h3>";
    $check_staffs = $pdo->query("SELECT id, fullname FROM staffs LIMIT 5");
    $staffs = $check_staffs->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($staffs) > 0) {
        echo "✓ Có dữ liệu staffs<br>";
        echo "Danh sách staffs mẫu:<br>";
        foreach ($staffs as $staff) {
            echo "- ID: {$staff['id']}, Tên: {$staff['fullname']}<br>";
        }
    } else {
        echo "✗ Không có dữ liệu staffs<br>";
    }
    
    // Test 5: Thử tạo thông báo test trực tiếp (nếu ENUM đã được cập nhật)
    if (str_contains($type_info['COLUMN_TYPE'], 'internal_case')) {
        echo "<h3>5. Test tạo thông báo trực tiếp</h3>";
        
        $handler_id = $staffs[0]['id'];
        $handler_name = $staffs[0]['fullname'];
        
        $test_sql = "INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, 'internal_case', ?)";
        $test_stmt = $pdo->prepare($test_sql);
        $test_result = $test_stmt->execute([
            $handler_id,
            "Test Case nội bộ",
            "Test message cho case nội bộ",
            999
        ]);
        
        if ($test_result) {
            echo "✓ Test tạo thông báo thành công!<br>";
            echo "Thông báo đã gửi cho: $handler_name<br>";
            
            // Xóa thông báo test
            $pdo->exec("DELETE FROM notifications WHERE type = 'internal_case' AND related_id = 999");
            echo "✓ Đã xóa thông báo test<br>";
        } else {
            echo "✗ Test tạo thông báo thất bại<br>";
        }
    }
    
} catch (Exception $e) {
    echo "<h3>Lỗi:</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Hướng dẫn:</h3>";
echo "1. <strong>Chạy lệnh SQL trong phpMyAdmin</strong> để cập nhật ENUM<br>";
echo "2. Chạy lại script này để kiểm tra<br>";
echo "3. Đăng nhập vào hệ thống và test tính năng tạo case nội bộ<br>";
?>



