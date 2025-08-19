<?php
/**
 * Script test tính năng thông báo internal case
 * Chạy script này để test API tạo thông báo
 */

require_once 'config/db.php';

echo "<h2>Test Internal Case Notification</h2>";

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
            echo "Cần chạy script SQL để cập nhật<br>";
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
        
        // Test 5: Test tạo thông báo mẫu
        echo "<h3>5. Test tạo thông báo mẫu</h3>";
        $handler_id = $staffs[0]['id'];
        $handler_name = $staffs[0]['fullname'];
        
        $test_data = [
            'case_id' => 999,
            'case_number' => 'CNB.2501001',
            'handler_id' => $handler_id,
            'issue_title' => 'Test Case - Hỗ trợ kỹ thuật',
            'requester_name' => 'Test User'
        ];
        
        // Gọi API tạo thông báo
        $notification_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/api/create_internal_case_notification.php';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $notification_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Requested-With: XMLHttpRequest'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "HTTP Code: $http_code<br>";
        echo "Response: $response<br>";
        
        if ($http_code === 200) {
            $result = json_decode($response, true);
            if ($result && $result['success']) {
                echo "✓ Test tạo thông báo thành công!<br>";
                echo "Thông báo đã gửi cho: $handler_name<br>";
            } else {
                echo "✗ Test tạo thông báo thất bại: " . ($result['message'] ?? 'Unknown error') . "<br>";
            }
        } else {
            echo "✗ HTTP error: $http_code<br>";
        }
        
    } else {
        echo "✗ Không có dữ liệu staffs<br>";
    }
    
    // Test 6: Kiểm tra thông báo vừa tạo
    echo "<h3>6. Kiểm tra thông báo vừa tạo</h3>";
    $check_new = $pdo->query("SELECT * FROM notifications WHERE type = 'internal_case' ORDER BY created_at DESC LIMIT 1");
    $new_notification = $check_new->fetch(PDO::FETCH_ASSOC);
    
    if ($new_notification) {
        echo "✓ Tìm thấy thông báo internal_case mới nhất:<br>";
        echo "- ID: {$new_notification['id']}<br>";
        echo "- User ID: {$new_notification['user_id']}<br>";
        echo "- Title: {$new_notification['title']}<br>";
        echo "- Message: {$new_notification['message']}<br>";
        echo "- Type: {$new_notification['type']}<br>";
        echo "- Created: {$new_notification['created_at']}<br>";
    } else {
        echo "✗ Không tìm thấy thông báo internal_case nào<br>";
    }
    
} catch (Exception $e) {
    echo "<h3>Lỗi:</h3>";
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h3>Hướng dẫn sử dụng:</h3>";
echo "1. Chạy script SQL để cập nhật bảng notifications<br>";
echo "2. Đăng nhập vào hệ thống<br>";
echo "3. Vào trang Case Nội Bộ<br>";
echo "4. Tạo case mới với người xử lý được chỉ định<br>";
echo "5. Kiểm tra thông báo trong dropdown notifications<br>";
?>
