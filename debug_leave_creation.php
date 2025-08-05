<?php
/**
 * Debug script để kiểm tra vấn đề tạo đơn nghỉ phép
 * Vấn đề: id = 0 và created_at = 0000-00-00
 */

require_once 'config/db.php';
require_once 'includes/session.php';

echo "<h2>Debug Leave Request Creation</h2>";

try {
    // 1. Kiểm tra cấu hình database
    echo "<h3>1. Kiểm tra cấu hình database</h3>";
    
    // Kiểm tra timezone
    $stmt = $pdo->query("SELECT @@global.time_zone, @@session.time_zone, NOW() as current_datetime");
    $timezone_info = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Global timezone: " . $timezone_info['@@global.time_zone'] . "<br>";
    echo "Session timezone: " . $timezone_info['@@session.time_zone'] . "<br>";
    echo "Current time: " . $timezone_info['current_datetime'] . "<br><br>";
    
    // 2. Kiểm tra cấu trúc bảng leave_requests
    echo "<h3>2. Kiểm tra cấu trúc bảng leave_requests</h3>";
    $stmt = $pdo->query("DESCRIBE leave_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // 3. Kiểm tra dữ liệu hiện tại
    echo "<h3>3. Kiểm tra dữ liệu hiện tại</h3>";
    $stmt = $pdo->query("SELECT id, request_code, created_at, updated_at FROM leave_requests ORDER BY id DESC LIMIT 5");
    $recent_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Request Code</th><th>Created At</th><th>Updated At</th></tr>";
    foreach ($recent_requests as $request) {
        echo "<tr>";
        echo "<td>{$request['id']}</td>";
        echo "<td>{$request['request_code']}</td>";
        echo "<td>{$request['created_at']}</td>";
        echo "<td>{$request['updated_at']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // 4. Thử tạo một đơn nghỉ phép test
    echo "<h3>4. Thử tạo đơn nghỉ phép test</h3>";
    
    // Lấy user hiện tại
    if (isLoggedIn()) {
        $current_user = getCurrentUser();
        echo "Current user: " . $current_user['fullname'] . " (ID: " . $current_user['id'] . ")<br>";
        
        // Tạo request code
        $current_year = date('y');
        $current_month = date('m');
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM leave_requests WHERE request_code LIKE ?");
        $month_pattern = "NP{$current_year}{$current_month}%";
        $stmt->execute([$month_pattern]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $next_number = $result['count'] + 1;
        $request_code = "NP{$current_year}{$current_month}" . str_pad($next_number, 3, '0', STR_PAD_LEFT);
        
        echo "Generated request code: " . $request_code . "<br>";
        
        // Thử insert với explicit created_at
        $sql = "INSERT INTO leave_requests (
            request_code, requester_id, requester_position, requester_department, requester_office,
            start_date, end_date, return_date, leave_days, leave_type, reason, handover_to,
            attachment, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $request_code,
            $current_user['id'],
            $current_user['position'] ?? 'Test Position',
            $current_user['department'] ?? 'Test Department',
            $current_user['office'] ?? 'Test Office',
            '2024-12-20 08:00:00',
            '2024-12-20 17:00:00',
            '2024-12-21 08:00:00',
            1.0,
            'Nghỉ phép năm',
            'Test reason for debugging',
            1,
            null,
            'Chờ phê duyệt'
        ]);
        
        if ($result) {
            $inserted_id = $pdo->lastInsertId();
            echo "✓ Insert thành công! ID: " . $inserted_id . "<br>";
            
            // Kiểm tra dữ liệu vừa insert
            $stmt = $pdo->prepare("SELECT id, request_code, created_at FROM leave_requests WHERE id = ?");
            $stmt->execute([$inserted_id]);
            $inserted_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "Dữ liệu vừa insert:<br>";
            echo "- ID: " . $inserted_data['id'] . "<br>";
            echo "- Request Code: " . $inserted_data['request_code'] . "<br>";
            echo "- Created At: " . $inserted_data['created_at'] . "<br>";
            
            // Xóa record test
            $stmt = $pdo->prepare("DELETE FROM leave_requests WHERE id = ?");
            $stmt->execute([$inserted_id]);
            echo "✓ Đã xóa record test<br>";
        } else {
            echo "✗ Insert thất bại<br>";
        }
    } else {
        echo "✗ Chưa đăng nhập<br>";
    }
    
    // 5. Kiểm tra cài đặt MySQL
    echo "<h3>5. Kiểm tra cài đặt MySQL</h3>";
    
    // Kiểm tra sql_mode
    $stmt = $pdo->query("SELECT @@sql_mode");
    $sql_mode = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "SQL Mode: " . $sql_mode['@@sql_mode'] . "<br>";
    
    // Kiểm tra strict mode
    $stmt = $pdo->query("SELECT @@global.sql_mode, @@session.sql_mode");
    $strict_mode = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Global SQL Mode: " . $strict_mode['@@global.sql_mode'] . "<br>";
    echo "Session SQL Mode: " . $strict_mode['@@session.sql_mode'] . "<br>";
    
    // Kiểm tra auto_increment
    $stmt = $pdo->query("SHOW TABLE STATUS LIKE 'leave_requests'");
    $table_status = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Auto Increment: " . $table_status['Auto_increment'] . "<br>";
    echo "Engine: " . $table_status['Engine'] . "<br>";
    
} catch (Exception $e) {
    echo "<h3>Lỗi:</h3>";
    echo "Message: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?> 