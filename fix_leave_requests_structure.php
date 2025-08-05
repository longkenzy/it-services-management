<?php
/**
 * Script sửa cấu trúc bảng leave_requests
 * Thêm PRIMARY KEY và AUTO_INCREMENT cho cột id
 */

// Bật hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Sửa cấu trúc bảng leave_requests</h2>";

try {
    require_once 'config/db.php';
    
    echo "<h3>1. Kiểm tra cấu trúc hiện tại</h3>";
    
    // Kiểm tra cấu trúc cột id
    $stmt = $pdo->query("DESCRIBE leave_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $id_column = null;
    foreach ($columns as $column) {
        if ($column['Field'] === 'id') {
            $id_column = $column;
            break;
        }
    }
    
    if ($id_column) {
        echo "Cột id hiện tại:<br>";
        echo "- Field: " . $id_column['Field'] . "<br>";
        echo "- Type: " . $id_column['Type'] . "<br>";
        echo "- Null: " . $id_column['Null'] . "<br>";
        echo "- Key: " . $id_column['Key'] . "<br>";
        echo "- Default: " . $id_column['Default'] . "<br>";
        echo "- Extra: " . $id_column['Extra'] . "<br><br>";
        
        if ($id_column['Key'] === '' && $id_column['Extra'] === '') {
            echo "⚠ Cột id chưa có PRIMARY KEY và AUTO_INCREMENT<br>";
            echo "Cần sửa chữa...<br><br>";
        } else {
            echo "✅ Cột id đã có PRIMARY KEY và AUTO_INCREMENT<br>";
            return;
        }
    }
    
    // 2. Kiểm tra dữ liệu hiện tại
    echo "<h3>2. Kiểm tra dữ liệu hiện tại</h3>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM leave_requests");
    $total = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Tổng số records: " . $total['total'] . "<br>";
    
    if ($total['total'] > 0) {
        $stmt = $pdo->query("SELECT MIN(id) as min_id, MAX(id) as max_id FROM leave_requests");
        $id_range = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "ID nhỏ nhất: " . $id_range['min_id'] . "<br>";
        echo "ID lớn nhất: " . $id_range['max_id'] . "<br>";
        
        // Kiểm tra xem có ID = 0 không
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM leave_requests WHERE id = 0");
        $zero_count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Records có ID = 0: " . $zero_count['count'] . "<br><br>";
    }
    
    // 3. Sửa chữa cấu trúc bảng
    echo "<h3>3. Sửa chữa cấu trúc bảng</h3>";
    
    // Bước 1: Thêm PRIMARY KEY cho cột id
    echo "Bước 1: Thêm PRIMARY KEY cho cột id...<br>";
    try {
        $sql = "ALTER TABLE leave_requests MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
        $pdo->exec($sql);
        echo "✅ Đã thêm PRIMARY KEY và AUTO_INCREMENT cho cột id<br>";
    } catch (Exception $e) {
        echo "❌ Lỗi khi thêm PRIMARY KEY: " . $e->getMessage() . "<br>";
        
        // Thử cách khác nếu có dữ liệu
        if ($total['total'] > 0) {
            echo "Thử cách khác...<br>";
            
            // Tạo bảng tạm
            $temp_table = "leave_requests_temp_" . time();
            $sql = "CREATE TABLE $temp_table LIKE leave_requests";
            $pdo->exec($sql);
            echo "✓ Đã tạo bảng tạm: $temp_table<br>";
            
            // Sửa cấu trúc bảng tạm
            $sql = "ALTER TABLE $temp_table MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
            $pdo->exec($sql);
            echo "✓ Đã sửa cấu trúc bảng tạm<br>";
            
            // Copy dữ liệu
            $sql = "INSERT INTO $temp_table SELECT * FROM leave_requests";
            $pdo->exec($sql);
            echo "✓ Đã copy dữ liệu sang bảng tạm<br>";
            
            // Xóa bảng cũ và đổi tên
            $pdo->exec("DROP TABLE leave_requests");
            $pdo->exec("RENAME TABLE $temp_table TO leave_requests");
            echo "✓ Đã thay thế bảng cũ bằng bảng mới<br>";
        }
    }
    
    // 4. Kiểm tra lại cấu trúc
    echo "<h3>4. Kiểm tra lại cấu trúc</h3>";
    
    $stmt = $pdo->query("DESCRIBE leave_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $id_column = null;
    foreach ($columns as $column) {
        if ($column['Field'] === 'id') {
            $id_column = $column;
            break;
        }
    }
    
    if ($id_column) {
        echo "Cột id sau khi sửa:<br>";
        echo "- Field: " . $id_column['Field'] . "<br>";
        echo "- Type: " . $id_column['Type'] . "<br>";
        echo "- Null: " . $id_column['Null'] . "<br>";
        echo "- Key: " . $id_column['Key'] . "<br>";
        echo "- Default: " . $id_column['Default'] . "<br>";
        echo "- Extra: " . $id_column['Extra'] . "<br><br>";
        
        if ($id_column['Key'] === 'PRI' && strpos($id_column['Extra'], 'auto_increment') !== false) {
            echo "✅ Cột id đã có PRIMARY KEY và AUTO_INCREMENT<br>";
        } else {
            echo "❌ Vẫn chưa sửa được cấu trúc<br>";
        }
    }
    
    // 5. Test insert và lastInsertId
    echo "<h3>5. Test insert và lastInsertId</h3>";
    
    // Tạo request code test
    $current_year = date('y');
    $current_month = date('m');
    $test_code = "TEST" . $current_year . $current_month . "002";
    
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
            
            echo "<h3 style='color: green;'>🎉 SỬA CHỮA THÀNH CÔNG!</h3>";
            echo "<p>Bảng leave_requests đã được sửa chữa và sẵn sàng sử dụng.</p>";
        } else {
            echo "❌ lastInsertId() vẫn trả về 0<br>";
        }
    } else {
        echo "❌ Insert thất bại<br>";
    }
    
    // 6. Kiểm tra AUTO_INCREMENT
    echo "<h3>6. Kiểm tra AUTO_INCREMENT</h3>";
    
    $stmt = $pdo->query("SHOW TABLE STATUS LIKE 'leave_requests'");
    $table_status = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "AUTO_INCREMENT hiện tại: " . $table_status['Auto_increment'] . "<br>";
    echo "Engine: " . $table_status['Engine'] . "<br>";
    echo "Row format: " . $table_status['Row_format'] . "<br>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ Lỗi:</h3>";
    echo "Message: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?> 