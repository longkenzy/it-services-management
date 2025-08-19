<?php
/**
 * Script test toàn diện hệ thống thông báo internal case
 * Kiểm tra từng bước để tìm lỗi
 */

require_once 'config/db.php';

echo "<h2>🔍 Test Hệ Thống Thông Báo Internal Case</h2>";

try {
    // Test 1: Kiểm tra database structure
    echo "<h3>1. 📊 Kiểm tra cấu trúc Database</h3>";
    
    $check_table = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($check_table->rowCount() > 0) {
        echo "✅ Bảng notifications tồn tại<br>";
    } else {
        echo "❌ Bảng notifications không tồn tại<br>";
        exit;
    }
    
    // Kiểm tra cấu trúc cột type
    $check_type = $pdo->prepare("
        SELECT COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'notifications' 
        AND COLUMN_NAME = 'type'
    ");
    $check_type->execute();
    $type_info = $check_type->fetch(PDO::FETCH_ASSOC);
    
    if ($type_info) {
        echo "✅ Cột type tồn tại<br>";
        echo "📋 Cấu trúc hiện tại: " . $type_info['COLUMN_TYPE'] . "<br>";
        
        if (str_contains($type_info['COLUMN_TYPE'], 'internal_case')) {
            echo "✅ Loại 'internal_case' đã có trong ENUM<br>";
        } else {
            echo "❌ Loại 'internal_case' chưa có trong ENUM<br>";
            echo "⚠️ Cần chạy lệnh SQL: ALTER TABLE notifications MODIFY COLUMN type ENUM('leave_request', 'leave_approved', 'leave_rejected', 'internal_case', 'system') DEFAULT 'system';<br>";
        }
    }
    
    // Test 2: Kiểm tra cấu trúc bảng staffs
    echo "<h3>2. 👥 Kiểm tra cấu trúc bảng Staffs</h3>";
    $check_staffs_columns = $pdo->query("DESCRIBE staffs");
    $staffs_columns = $check_staffs_columns->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Các cột trong bảng staffs:<br>";
    foreach ($staffs_columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})<br>";
    }
    
    // Test 3: Kiểm tra dữ liệu staffs
    echo "<h3>3. 👥 Kiểm tra dữ liệu Staffs</h3>";
    $check_staffs = $pdo->query("SELECT id, fullname FROM staffs LIMIT 5");
    $staffs = $check_staffs->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($staffs) > 0) {
        echo "✅ Có dữ liệu staffs (" . count($staffs) . " nhân viên)<br>";
        echo "📋 Danh sách staffs mẫu:<br>";
        foreach ($staffs as $staff) {
            echo "- ID: {$staff['id']}, Tên: {$staff['fullname']}<br>";
        }
    } else {
        echo "❌ Không có dữ liệu staffs<br>";
        exit;
    }
    
    // Test 4: Kiểm tra file API tồn tại
    echo "<h3>4. 📁 Kiểm tra file API</h3>";
    $api_files = [
        'api/create_internal_case_notification.php',
        'api/create_case.php',
        'includes/notifications.php'
    ];
    
    foreach ($api_files as $file) {
        if (file_exists($file)) {
            echo "✅ File tồn tại: $file<br>";
        } else {
            echo "❌ File không tồn tại: $file<br>";
        }
    }
    
    // Test 5: Test tạo thông báo trực tiếp (nếu ENUM đã được cập nhật)
    if (str_contains($type_info['COLUMN_TYPE'], 'internal_case')) {
        echo "<h3>5. 🧪 Test tạo thông báo trực tiếp</h3>";
        
        $handler_id = $staffs[0]['id'];
        $handler_name = $staffs[0]['fullname'];
        
        // Thử tạo thông báo trực tiếp
        $test_sql = "INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, 'internal_case', ?)";
        $test_stmt = $pdo->prepare($test_sql);
        $test_result = $test_stmt->execute([
            $handler_id,
            "Test Case nội bộ",
            "Test message cho case nội bộ - " . date('Y-m-d H:i:s'),
            999
        ]);
        
        if ($test_result) {
            echo "✅ Test tạo thông báo trực tiếp thành công!<br>";
            echo "📧 Thông báo đã gửi cho: $handler_name (ID: $handler_id)<br>";
            
            // Kiểm tra thông báo vừa tạo
            $check_notification = $pdo->prepare("SELECT * FROM notifications WHERE type = 'internal_case' AND related_id = 999 ORDER BY created_at DESC LIMIT 1");
            $check_notification->execute();
            $notification = $check_notification->fetch(PDO::FETCH_ASSOC);
            
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
        } else {
            echo "❌ Test tạo thông báo trực tiếp thất bại<br>";
            $error_info = $test_stmt->errorInfo();
            echo "🔍 Lỗi: " . $error_info[2] . "<br>";
        }
    } else {
        echo "<h3>5. ⚠️ Bỏ qua test tạo thông báo trực tiếp</h3>";
        echo "❌ ENUM chưa được cập nhật, không thể test tạo thông báo<br>";
    }
    
    // Test 6: Kiểm tra cURL có hoạt động không
    echo "<h3>6. 🔧 Kiểm tra CURL</h3>";
    if (function_exists('curl_init')) {
        echo "✅ CURL extension đã được cài đặt<br>";
    } else {
        echo "❌ CURL extension chưa được cài đặt<br>";
    }
    
    // Test 7: Kiểm tra session
    echo "<h3>7. 🔐 Kiểm tra Session</h3>";
    session_start();
    echo "Session ID: " . session_id() . "<br>";
    echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
    echo "Role: " . ($_SESSION['role'] ?? 'Not set') . "<br>";
    
    // Test 8: Kiểm tra thông báo trong database
    echo "<h3>8. 📊 Kiểm tra thông báo trong Database</h3>";
    $check_notifications = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");
    $notifications = $check_notifications->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($notifications) > 0) {
        echo "✅ Tìm thấy " . count($notifications) . " thông báo trong database:<br>";
        foreach ($notifications as $notif) {
            echo "- ID: {$notif['id']}, User: {$notif['user_id']}, Type: {$notif['type']}, Title: {$notif['title']}, Created: {$notif['created_at']}<br>";
        }
    } else {
        echo "❌ Không tìm thấy thông báo nào trong database<br>";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ Lỗi:</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
}

echo "<hr>";
echo "<h3>📋 Tóm tắt:</h3>";
echo "1. Kiểm tra database structure<br>";
echo "2. Kiểm tra cấu trúc bảng staffs<br>";
echo "3. Kiểm tra dữ liệu staffs<br>";
echo "4. Kiểm tra file API<br>";
echo "5. Test tạo thông báo trực tiếp<br>";
echo "6. Kiểm tra CURL extension<br>";
echo "7. Kiểm tra session<br>";
echo "8. Kiểm tra thông báo trong database<br>";
echo "<br>";
echo "<h3>🚨 VẤN ĐỀ CHÍNH:</h3>";
echo "❌ Database ENUM chưa được cập nhật để thêm 'internal_case'<br>";
echo "🔧 Cần chạy lệnh SQL trong phpMyAdmin trước khi test tiếp<br>";
?>
