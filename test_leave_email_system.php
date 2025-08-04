<?php
/**
 * Test hệ thống Email đơn nghỉ phép
 * File: test_leave_email_system.php
 * Mục đích: Kiểm tra các chức năng của hệ thống
 */

require_once 'config/db.php';
require_once 'config/email.php';

echo "<h1>🧪 Test Hệ thống Email Đơn nghỉ phép</h1>";

// Test 1: Kiểm tra kết nối database
echo "<h2>1. Kiểm tra kết nối Database</h2>";
try {
    $sql = "SELECT COUNT(*) as count FROM leave_requests";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    echo "✅ Kết nối database thành công. Có {$result['count']} đơn nghỉ phép trong hệ thống.<br>";
} catch (Exception $e) {
    echo "❌ Lỗi kết nối database: " . $e->getMessage() . "<br>";
}

// Test 2: Kiểm tra cột approve_token
echo "<h2>2. Kiểm tra cột approve_token</h2>";
try {
    $sql = "SHOW COLUMNS FROM leave_requests LIKE 'approve_token'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✅ Cột approve_token đã tồn tại.<br>";
    } else {
        echo "❌ Cột approve_token chưa tồn tại. Vui lòng chạy file SQL để thêm cột.<br>";
    }
} catch (Exception $e) {
    echo "❌ Lỗi kiểm tra cột: " . $e->getMessage() . "<br>";
}

// Test 3: Test tạo token
echo "<h2>3. Test tạo token</h2>";
$token1 = generateApproveToken(16);
$token2 = generateApproveToken(16);
echo "✅ Token 1: " . $token1 . "<br>";
echo "✅ Token 2: " . $token2 . "<br>";
echo "✅ Độ dài token: " . strlen($token1) . " ký tự<br>";

// Test 4: Test tạo URL duyệt
echo "<h2>4. Test tạo URL duyệt</h2>";
$approve_url = generateApproveUrl(1, $token1);
echo "✅ URL duyệt: <a href='{$approve_url}' target='_blank'>{$approve_url}</a><br>";

// Test 5: Kiểm tra cấu hình email
echo "<h2>5. Kiểm tra cấu hình Email</h2>";
echo "📧 SMTP Host: " . $email_config['smtp_host'] . "<br>";
echo "📧 SMTP Port: " . $email_config['smtp_port'] . "<br>";
echo "📧 From Email: " . $email_config['from_email'] . "<br>";
echo "📧 Admin Email: " . $email_config['admin_email'] . "<br>";
echo "🌐 Base URL: " . $website_config['base_url'] . "<br>";

// Test 6: Test gửi email (nếu có đơn nghỉ phép)
echo "<h2>6. Test gửi Email</h2>";
try {
    $sql = "SELECT id, request_code, approve_token FROM leave_requests WHERE status = 'Chờ phê duyệt' LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $leave_request = $stmt->fetch();
    
    if ($leave_request) {
        echo "📋 Tìm thấy đơn nghỉ phép: {$leave_request['request_code']}<br>";
        
        // Test gửi email
        $test_url = generateApproveUrl($leave_request['id'], $leave_request['approve_token']);
        echo "🔗 URL test: <a href='{$test_url}' target='_blank'>{$test_url}</a><br>";
        
        echo "⚠️ Để test gửi email thực tế, vui lòng cấu hình email trong config/email.php<br>";
    } else {
        echo "📋 Không có đơn nghỉ phép nào đang chờ duyệt.<br>";
    }
} catch (Exception $e) {
    echo "❌ Lỗi test email: " . $e->getMessage() . "<br>";
}

// Test 7: Test validation token
echo "<h2>7. Test Validation Token</h2>";
if (isset($leave_request)) {
    $is_valid = validateApproveToken($leave_request['id'], $leave_request['approve_token']);
    echo "✅ Token hợp lệ: " . ($is_valid ? 'Có' : 'Không') . "<br>";
    
    $is_invalid = validateApproveToken($leave_request['id'], 'invalid_token');
    echo "✅ Token không hợp lệ: " . ($is_invalid ? 'Có' : 'Không') . "<br>";
} else {
    echo "⚠️ Không có đơn nghỉ phép để test validation.<br>";
}

// Test 8: Kiểm tra PHPMailer
echo "<h2>8. Kiểm tra PHPMailer</h2>";
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "✅ PHPMailer đã được cài đặt.<br>";
} else {
    echo "❌ PHPMailer chưa được cài đặt. Vui lòng chạy: composer require phpmailer/phpmailer<br>";
}

// Test 9: Test các file cần thiết
echo "<h2>9. Kiểm tra Files</h2>";
$required_files = [
    'config/email.php',
    'api/submit_leave.php',
    'api/send_leave_approval_email.php',
    'approve_leave.php',
    'assets/js/leave_form.js'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "✅ {$file}<br>";
    } else {
        echo "❌ {$file} - File không tồn tại<br>";
    }
}

// Test 10: Tạo đơn test
echo "<h2>10. Tạo đơn test</h2>";
echo "<form method='post' action=''>";
echo "<input type='hidden' name='action' value='create_test_leave'>";
echo "<button type='submit' class='btn btn-primary'>Tạo đơn nghỉ phép test</button>";
echo "</form>";

// Xử lý tạo đơn test
if (isset($_POST['action']) && $_POST['action'] === 'create_test_leave') {
    try {
        // Tạo mã đơn test
        $test_code = 'TEST' . date('YmdHis');
        $test_token = generateApproveToken(16);
        
        $sql = "INSERT INTO leave_requests (
                    request_code, requester_id, requester_position, requester_department,
                    requester_office, start_date, end_date, return_date, leave_days,
                    leave_type, reason, approve_token, status, created_at
                ) VALUES (
                    ?, 1, 'Nhân viên test', 'IT', 'Hà Nội',
                    NOW(), DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL 3 DAY),
                    2.0, 'Nghỉ phép năm', 'Đơn test hệ thống email', ?, 'Chờ phê duyệt', NOW()
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$test_code, $test_token]);
        $leave_id = $pdo->lastInsertId();
        
        echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "✅ Đã tạo đơn test thành công!<br>";
        echo "📋 Mã đơn: {$test_code}<br>";
        echo "🔗 URL duyệt: <a href='approve_leave.php?id={$leave_id}&token={$test_token}' target='_blank'>Duyệt đơn test</a><br>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
        echo "❌ Lỗi tạo đơn test: " . $e->getMessage();
        echo "</div>";
    }
}

// Hiển thị danh sách đơn nghỉ phép gần đây
echo "<h2>11. Danh sách đơn nghỉ phép gần đây</h2>";
try {
    $sql = "SELECT id, request_code, requester_id, leave_type, status, created_at, approve_token 
            FROM leave_requests 
            ORDER BY created_at DESC 
            LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $recent_requests = $stmt->fetchAll();
    
    if ($recent_requests) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th>ID</th><th>Mã đơn</th><th>Loại nghỉ</th><th>Trạng thái</th><th>Ngày tạo</th><th>Token</th><th>Thao tác</th>";
        echo "</tr>";
        
        foreach ($recent_requests as $request) {
            $approve_url = $request['approve_token'] ? 
                "approve_leave.php?id={$request['id']}&token={$request['approve_token']}" : 
                "#";
            
            echo "<tr>";
            echo "<td>{$request['id']}</td>";
            echo "<td>{$request['request_code']}</td>";
            echo "<td>{$request['leave_type']}</td>";
            echo "<td>{$request['status']}</td>";
            echo "<td>{$request['created_at']}</td>";
            echo "<td>" . substr($request['approve_token'], 0, 8) . "...</td>";
            echo "<td>";
            if ($request['approve_token']) {
                echo "<a href='{$approve_url}' target='_blank'>Duyệt</a>";
            } else {
                echo "Đã duyệt";
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "📋 Không có đơn nghỉ phép nào.";
    }
} catch (Exception $e) {
    echo "❌ Lỗi lấy danh sách: " . $e->getMessage();
}

?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #007bff; }
h2 { color: #6c757d; margin-top: 30px; }
.btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
.btn:hover { background: #0056b3; }
table { margin-top: 10px; }
th, td { padding: 8px; text-align: left; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style> 