<?php
/**
 * Fix Auto Increment cho bảng maintenance_cases
 * File: fix_maintenance_cases_auto_increment.php
 * Mục đích: Sửa lỗi auto-increment bị reset về 0
 * Tác giả: IT Support Team
 * Ngày tạo: 2024-12-19
 */

require_once 'config/db.php';

echo "<h2>Fixing Auto Increment cho bảng maintenance_cases</h2>";

try {
    // Bước 1: Kiểm tra cấu trúc bảng hiện tại
    echo "<p>1. Kiểm tra cấu trúc bảng maintenance_cases...</p>";
    
    $stmt = $pdo->query("DESCRIBE maintenance_cases");
    $columns = $stmt->fetchAll();
    
    $idColumn = null;
    foreach ($columns as $column) {
        if ($column['Field'] === 'id') {
            $idColumn = $column;
            break;
        }
    }
    
    if (!$idColumn) {
        throw new Exception("Không tìm thấy cột 'id' trong bảng maintenance_cases");
    }
    
    echo "<p>Cột 'id' hiện tại: " . $idColumn['Type'] . " | Extra: " . $idColumn['Extra'] . "</p>";
    
    // Bước 2: Kiểm tra giá trị auto_increment hiện tại
    echo "<p>2. Kiểm tra giá trị AUTO_INCREMENT hiện tại...</p>";
    
    $stmt = $pdo->query("SHOW TABLE STATUS LIKE 'maintenance_cases'");
    $tableStatus = $stmt->fetch();
    
    $currentAutoIncrement = $tableStatus['Auto_increment'];
    echo "<p>Giá trị AUTO_INCREMENT hiện tại: " . ($currentAutoIncrement ?: 'NULL') . "</p>";
    
    // Bước 3: Tìm ID cao nhất trong bảng
    echo "<p>3. Tìm ID cao nhất trong bảng...</p>";
    
    $stmt = $pdo->query("SELECT MAX(id) as max_id FROM maintenance_cases");
    $result = $stmt->fetch();
    $maxId = $result['max_id'];
    
    echo "<p>ID cao nhất hiện tại: " . ($maxId ?: 'NULL (bảng trống)') . "</p>";
    
    // Bước 4: Tính toán giá trị AUTO_INCREMENT mới
    $nextId = $maxId ? $maxId + 1 : 1;
    echo "<p>4. Giá trị AUTO_INCREMENT mới sẽ là: " . $nextId . "</p>";
    
    // Bước 5: Sửa cấu trúc cột id nếu cần
    echo "<p>5. Sửa cấu trúc cột 'id'...</p>";
    
    if (strpos($idColumn['Extra'], 'auto_increment') === false) {
        echo "<p>Cột 'id' không có auto_increment, đang sửa...</p>";
        $alterSql = "ALTER TABLE maintenance_cases MODIFY COLUMN id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
        $pdo->exec($alterSql);
        echo "<p>✓ Đã sửa cấu trúc cột 'id'</p>";
    } else {
        echo "<p>✓ Cột 'id' đã có auto_increment</p>";
    }
    
    // Bước 6: Reset AUTO_INCREMENT về giá trị đúng
    echo "<p>6. Reset AUTO_INCREMENT về giá trị đúng...</p>";
    
    $sql5 = "ALTER TABLE maintenance_cases AUTO_INCREMENT = $nextId";
    $pdo->exec($sql5);
    echo "<p>✓ Đã reset AUTO_INCREMENT về " . $nextId . "</p>";
    
    // Bước 7: Kiểm tra lại
    echo "<p>7. Kiểm tra lại sau khi sửa...</p>";
    
    $stmt = $pdo->query("SHOW TABLE STATUS LIKE 'maintenance_cases'");
    $tableStatusAfter = $stmt->fetch();
    $newAutoIncrement = $tableStatusAfter['Auto_increment'];
    
    echo "<p>Giá trị AUTO_INCREMENT sau khi sửa: " . $newAutoIncrement . "</p>";
    
    // Bước 8: Test insert để kiểm tra
    echo "<p>8. Test insert để kiểm tra...</p>";
    
    // Tạo một record test tạm thời
    $testCode = 'TEST_' . date('YmdHis');
    $stmt = $pdo->prepare("INSERT INTO maintenance_cases (case_code, maintenance_request_id, request_type, assigned_to, status, created_by) VALUES (?, 1, 'Test Case', 1, 'Tiếp nhận', 1)");
    $stmt->execute([$testCode]);
    
    $testId = $pdo->lastInsertId();
    echo "<p>✓ Test insert thành công với ID: " . $testId . "</p>";
    
    // Xóa record test
    $stmt = $pdo->prepare("DELETE FROM maintenance_cases WHERE case_code = ?");
    $stmt->execute([$testCode]);
    echo "<p>✓ Đã xóa record test</p>";
    
    echo "<h3 style='color: green;'>✓ Hoàn thành! Auto increment đã được sửa thành công.</h3>";
    echo "<p>Bảng maintenance_cases hiện tại có thể tạo ID mới bắt đầu từ: " . $nextId . "</p>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>✗ Lỗi: " . $e->getMessage() . "</h3>";
    error_log("Error in fix_maintenance_cases_auto_increment.php: " . $e->getMessage());
}

echo "<p><a href='maintenance_requests.php'>← Quay lại trang Yêu cầu bảo trì</a></p>";
?>
