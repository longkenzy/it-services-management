<?php
/**
 * Script sửa lỗi lastInsertId() và kiểm tra cấu trúc bảng leave_requests
 */

// Bật hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Sửa lỗi lastInsertId() - leave_requests</h2>";

try {
    require_once 'config/db.php';
    
    echo "<h3>1. Kiểm tra cấu trúc bảng leave_requests</h3>";
    
    // Kiểm tra cấu trúc bảng
    $stmt = $pdo->query("DESCRIBE leave_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . $column['Field'] . "</td>";
        echo "<td>" . $column['Type'] . "</td>";
        echo "<td>" . $column['Null'] . "</td>";
        echo "<td>" . $column['Key'] . "</td>";
        echo "<td>" . $column['Default'] . "</td>";
        echo "<td>" . $column['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Kiểm tra AUTO_INCREMENT
    $stmt = $pdo->query("SHOW TABLE STATUS LIKE 'leave_requests'");
    $table_status = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "AUTO_INCREMENT hiện tại: " . $table_status['Auto_increment'] . "<br>";
    echo "Engine: " . $table_status['Engine'] . "<br>";
    echo "Row format: " . $table_status['Row_format'] . "<br><br>";
    
    // 2. Kiểm tra dữ liệu hiện tại
    echo "<h3>2. Kiểm tra dữ liệu hiện tại</h3>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM leave_requests");
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Tổng số records: " . $total['total'] . "<br>";
    
    $stmt = $pdo->query("SELECT MIN(id) as min_id, MAX(id) as max_id FROM leave_requests");
    $id_range = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "ID nhỏ nhất: " . $id_range['min_id'] . "<br>";
    echo "ID lớn nhất: " . $id_range['max_id'] . "<br><br>";
    
    // 3. Sửa chữa AUTO_INCREMENT nếu cần
    echo "<h3>3. Sửa chữa AUTO_INCREMENT</h3>";
    
    if ($id_range['max_id'] > 0) {
        $next_auto_increment = $id_range['max_id'] + 1;
        $sql = "ALTER TABLE leave_requests AUTO_INCREMENT = $next_auto_increment";
        $pdo->exec($sql);
        echo "✓ Đã cập nhật AUTO_INCREMENT thành: $next_auto_increment<br>";
    } else {
        echo "⚠ Không có dữ liệu để tính AUTO_INCREMENT<br>";
    }
    
    // 4. Test insert và lastInsertId
    echo "<h3>4. Test insert và lastInsertId</h3>";
    
    // Tạo request code test
    $current_year = date('y');
    $current_month = date('m');
    $test_code = "TEST" . $current_year . $current_month . "001";
    
    // Test insert
    $sql = "INSERT INTO leave_requests (
        request_code, requester_id, requester_position, requester_department, requester_office,
        start_date, end_date, return_date, leave_days, leave_type, reason, handover_to,
        attachment, status, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $test_code,
        1, // requester_id
        'Test Position',
        'Test Department', 
        'Test Office',
        '2025-08-06 08:00:00',
        '2025-08-06 17:00:00',
        '2025-08-07 08:00:00',
        1.0,
        'Test Leave Type',
        'Test reason for debugging',
        1,
        null,
        'Chờ phê duyệt'
    ]);
    
    if ($result) {
        $inserted_id = $pdo->lastInsertId();
        echo "✓ Insert thành công!<br>";
        echo "lastInsertId(): " . $inserted_id . "<br>";
        
        if ($inserted_id > 0) {
            echo "✅ lastInsertId() hoạt động bình thường<br>";
            
            // Xóa record test
            $stmt = $pdo->prepare("DELETE FROM leave_requests WHERE id = ?");
            $stmt->execute([$inserted_id]);
            echo "✓ Đã xóa record test<br>";
        } else {
            echo "❌ lastInsertId() trả về 0 hoặc null<br>";
            
            // Thử cách khác để lấy ID
            $stmt = $pdo->query("SELECT LAST_INSERT_ID() as last_id");
            $last_id_result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "LAST_INSERT_ID(): " . $last_id_result['last_id'] . "<br>";
            
            // Kiểm tra record vừa insert
            $stmt = $pdo->query("SELECT id, request_code FROM leave_requests WHERE request_code = '$test_code' ORDER BY id DESC LIMIT 1");
            $recent_record = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($recent_record) {
                echo "Record vừa tạo có ID: " . $recent_record['id'] . "<br>";
            }
        }
    } else {
        echo "❌ Insert thất bại<br>";
    }
    
    // 5. Kiểm tra MySQL settings
    echo "<h3>5. Kiểm tra MySQL settings</h3>";
    
    $settings = [
        'sql_mode' => $pdo->query("SELECT @@sql_mode")->fetchColumn(),
        'auto_increment_increment' => $pdo->query("SELECT @@auto_increment_increment")->fetchColumn(),
        'auto_increment_offset' => $pdo->query("SELECT @@auto_increment_offset")->fetchColumn(),
        'innodb_autoinc_lock_mode' => $pdo->query("SELECT @@innodb_autoinc_lock_mode")->fetchColumn()
    ];
    
    foreach ($settings as $setting => $value) {
        echo "$setting: $value<br>";
    }
    
    // 6. Sửa chữa nếu cần
    echo "<h3>6. Sửa chữa nếu cần</h3>";
    
    if ($inserted_id <= 0) {
        echo "⚠ Phát hiện lỗi lastInsertId()<br>";
        echo "Có thể do:<br>";
        echo "- Bảng không có AUTO_INCREMENT<br>";
        echo "- MySQL strict mode<br>";
        echo "- Transaction issues<br>";
        echo "- Connection issues<br>";
        
        // Thử sửa chữa
        echo "<br>Thử sửa chữa...<br>";
        
        // Kiểm tra và sửa AUTO_INCREMENT
        $stmt = $pdo->query("SHOW CREATE TABLE leave_requests");
        $create_table = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "CREATE TABLE statement:<br>";
        echo "<pre>" . htmlspecialchars($create_table['Create Table']) . "</pre>";
        
        // Nếu không có AUTO_INCREMENT, thêm vào
        if (strpos($create_table['Create Table'], 'AUTO_INCREMENT') === false) {
            echo "⚠ Cột id không có AUTO_INCREMENT<br>";
            echo "Cần sửa cấu trúc bảng...<br>";
        }
    } else {
        echo "✅ lastInsertId() hoạt động bình thường<br>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Lỗi:</h3>";
    echo "Message: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?> 