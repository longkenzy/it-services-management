<?php
/**
 * Script cập nhật database ENUM để thêm 'internal_case'
 * Chạy trực tiếp từ terminal
 */

require_once 'config/db.php';

echo "<h2>🔧 Cập Nhật Database ENUM</h2>";

try {
    // Kiểm tra cấu trúc hiện tại
    echo "<h3>1. 📊 Kiểm tra cấu trúc hiện tại</h3>";
    
    $check_type = $pdo->prepare("
        SELECT COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'notifications' 
        AND COLUMN_NAME = 'type'
    ");
    $check_type->execute();
    $type_info = $check_type->fetch(PDO::FETCH_ASSOC);
    
    if ($type_info) {
        echo "📋 Cấu trúc ENUM hiện tại: " . $type_info['COLUMN_TYPE'] . "<br>";
        
        if (str_contains($type_info['COLUMN_TYPE'], 'internal_case')) {
            echo "✅ ENUM đã có 'internal_case' - Không cần cập nhật!<br>";
        } else {
            echo "❌ ENUM chưa có 'internal_case' - Tiến hành cập nhật...<br>";
            
            // Cập nhật ENUM
            echo "<h3>2. 🔄 Cập nhật ENUM</h3>";
            
            $alter_sql = "ALTER TABLE notifications MODIFY COLUMN type ENUM('leave_request', 'leave_approved', 'leave_rejected', 'internal_case', 'system') DEFAULT 'system'";
            
            echo "📝 Thực thi lệnh SQL: $alter_sql<br>";
            
            $result = $pdo->exec($alter_sql);
            
            if ($result !== false) {
                echo "✅ Cập nhật ENUM thành công!<br>";
                
                // Kiểm tra lại sau khi cập nhật
                echo "<h3>3. ✅ Kiểm tra sau khi cập nhật</h3>";
                
                $check_type->execute();
                $new_type_info = $check_type->fetch(PDO::FETCH_ASSOC);
                
                if ($new_type_info) {
                    echo "📋 Cấu trúc ENUM mới: " . $new_type_info['COLUMN_TYPE'] . "<br>";
                    
                    if (str_contains($new_type_info['COLUMN_TYPE'], 'internal_case')) {
                        echo "🎉 Cập nhật thành công! ENUM đã có 'internal_case'<br>";
                        
                        // Test tạo thông báo
                        echo "<h3>4. 🧪 Test tạo thông báo</h3>";
                        
                        $staff = $pdo->query("SELECT id, fullname FROM staffs LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                        
                        if ($staff) {
                            $handler_id = $staff['id'];
                            $handler_name = $staff['fullname'];
                            
                            $test_sql = "INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, 'internal_case', ?)";
                            $test_stmt = $pdo->prepare($test_sql);
                            $test_result = $test_stmt->execute([
                                $handler_id,
                                "Test Case nội bộ",
                                "Test message cho case nội bộ - " . date('Y-m-d H:i:s'),
                                999
                            ]);
                            
                            if ($test_result) {
                                echo "✅ Test tạo thông báo thành công!<br>";
                                echo "📧 Gửi cho: $handler_name (ID: $handler_id)<br>";
                                
                                // Xóa thông báo test
                                $pdo->exec("DELETE FROM notifications WHERE type = 'internal_case' AND related_id = 999");
                                echo "🧹 Đã xóa thông báo test<br>";
                                
                                echo "<h3>🎉 HOÀN THÀNH!</h3>";
                                echo "✅ Database đã được cập nhật thành công!<br>";
                                echo "✅ Có thể tạo thông báo internal_case!<br>";
                                echo "✅ Hệ thống thông báo sẵn sàng hoạt động!<br>";
                                echo "<br>";
                                echo "🚀 Bây giờ bạn có thể:<br>";
                                echo "1. Đăng nhập vào hệ thống<br>";
                                echo "2. Vào trang Case Nội Bộ<br>";
                                echo "3. Tạo case mới với người xử lý<br>";
                                echo "4. Kiểm tra thông báo trong dropdown<br>";
                                
                            } else {
                                echo "❌ Test tạo thông báo thất bại<br>";
                                $error_info = $test_stmt->errorInfo();
                                echo "🔍 Lỗi: " . $error_info[2] . "<br>";
                            }
                        } else {
                            echo "❌ Không có dữ liệu staffs<br>";
                        }
                        
                    } else {
                        echo "❌ Cập nhật thất bại - ENUM vẫn chưa có 'internal_case'<br>";
                    }
                }
                
            } else {
                echo "❌ Cập nhật ENUM thất bại!<br>";
                $error_info = $pdo->errorInfo();
                echo "🔍 Lỗi: " . $error_info[2] . "<br>";
            }
        }
    } else {
        echo "❌ Không tìm thấy cột type trong bảng notifications<br>";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Lỗi:</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
}

echo "<hr>";
echo "<h3>📋 Tóm tắt:</h3>";
echo "Script này sẽ tự động cập nhật database ENUM<br>";
echo "Sau khi chạy xong, hệ thống thông báo sẽ hoạt động bình thường<br>";
?>
