<?php
/**
 * Script test nhanh để kiểm tra thông báo sau khi cập nhật database
 */

require_once 'config/db.php';

echo "<h2>⚡ Quick Test Notification System</h2>";

try {
    // Test 1: Kiểm tra ENUM đã được cập nhật chưa
    echo "<h3>1. 🔍 Kiểm tra ENUM</h3>";
    
    $check_type = $pdo->prepare("
        SELECT COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'notifications' 
        AND COLUMN_NAME = 'type'
    ");
    $check_type->execute();
    $type_info = $check_type->fetch(PDO::FETCH_ASSOC);
    
    if ($type_info) {
        echo "📋 Cấu trúc ENUM: " . $type_info['COLUMN_TYPE'] . "<br>";
        
        if (str_contains($type_info['COLUMN_TYPE'], 'internal_case')) {
            echo "✅ ENUM đã có 'internal_case' - Có thể test tiếp!<br>";
            
            // Test 2: Tạo thông báo test
            echo "<h3>2. 🧪 Test tạo thông báo</h3>";
            
            // Lấy staff đầu tiên làm handler
            $staff = $pdo->query("SELECT id, fullname FROM staffs LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            
            if ($staff) {
                $handler_id = $staff['id'];
                $handler_name = $staff['fullname'];
                
                // Tạo thông báo test
                $test_sql = "INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, 'internal_case', ?)";
                $test_stmt = $pdo->prepare($test_sql);
                $test_result = $test_stmt->execute([
                    $handler_id,
                    "Test Case nội bộ",
                    "Test message cho case nội bộ - " . date('Y-m-d H:i:s'),
                    999
                ]);
                
                if ($test_result) {
                    echo "✅ Tạo thông báo thành công!<br>";
                    echo "📧 Gửi cho: $handler_name (ID: $handler_id)<br>";
                    
                    // Kiểm tra thông báo vừa tạo
                    $notification = $pdo->query("SELECT * FROM notifications WHERE type = 'internal_case' AND related_id = 999 ORDER BY created_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                    
                    if ($notification) {
                        echo "📋 Chi tiết thông báo:<br>";
                        echo "- ID: {$notification['id']}<br>";
                        echo "- User ID: {$notification['user_id']}<br>";
                        echo "- Title: {$notification['title']}<br>";
                        echo "- Message: {$notification['message']}<br>";
                        echo "- Type: {$notification['type']}<br>";
                        echo "- Created: {$notification['created_at']}<br>";
                    }
                    
                    // Xóa thông báo test
                    $pdo->exec("DELETE FROM notifications WHERE type = 'internal_case' AND related_id = 999");
                    echo "🧹 Đã xóa thông báo test<br>";
                    
                    echo "<h3>🎉 KẾT QUẢ:</h3>";
                    echo "✅ Database đã sẵn sàng!<br>";
                    echo "✅ Có thể tạo thông báo internal_case!<br>";
                    echo "✅ Hệ thống thông báo hoạt động bình thường!<br>";
                    echo "<br>";
                    echo "🚀 Bây giờ bạn có thể:<br>";
                    echo "1. Đăng nhập vào hệ thống<br>";
                    echo "2. Vào trang Case Nội Bộ<br>";
                    echo "3. Tạo case mới với người xử lý<br>";
                    echo "4. Kiểm tra thông báo trong dropdown<br>";
                    
                } else {
                    echo "❌ Tạo thông báo thất bại<br>";
                    $error_info = $test_stmt->errorInfo();
                    echo "🔍 Lỗi: " . $error_info[2] . "<br>";
                }
            } else {
                echo "❌ Không có dữ liệu staffs<br>";
            }
            
        } else {
            echo "❌ ENUM chưa có 'internal_case'<br>";
            echo "⚠️ Cần chạy lệnh SQL trong phpMyAdmin:<br>";
            echo "<code>ALTER TABLE notifications MODIFY COLUMN type ENUM('leave_request', 'leave_approved', 'leave_rejected', 'internal_case', 'system') DEFAULT 'system';</code><br>";
        }
    } else {
        echo "❌ Không tìm thấy cột type<br>";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Lỗi:</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>📋 Hướng dẫn:</h3>";
echo "1. Chạy lệnh SQL trong phpMyAdmin<br>";
echo "2. Chạy lại script này để kiểm tra<br>";
echo "3. Nếu thành công, test tính năng tạo case nội bộ<br>";
?>
