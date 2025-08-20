<?php
/**
 * Test Auto Increment cho bảng maintenance_tasks
 * File: test_maintenance_tasks_auto_increment.php
 * Mục đích: Kiểm tra xem auto-increment có hoạt động đúng không
 * Tác giả: IT Support Team
 * Ngày tạo: 2024-12-19
 */

require_once 'config/db.php';

echo "<h2>Test Auto Increment cho bảng maintenance_tasks</h2>";

try {
    // Bước 1: Kiểm tra cấu trúc bảng
    echo "<h3>1. Kiểm tra cấu trúc bảng</h3>";
    $stmt = $pdo->query("DESCRIBE maintenance_tasks");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
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
    echo "</table>";
    
    // Bước 2: Kiểm tra giá trị AUTO_INCREMENT hiện tại
    echo "<h3>2. Kiểm tra giá trị AUTO_INCREMENT hiện tại</h3>";
    $stmt = $pdo->query("SHOW TABLE STATUS LIKE 'maintenance_tasks'");
    $tableStatus = $stmt->fetch();
    
    echo "<p><strong>AUTO_INCREMENT hiện tại:</strong> " . ($tableStatus['Auto_increment'] ?: 'NULL') . "</p>";
    echo "<p><strong>Engine:</strong> " . $tableStatus['Engine'] . "</p>";
    echo "<p><strong>Rows:</strong> " . $tableStatus['Rows'] . "</p>";
    
    // Bước 3: Tìm ID cao nhất
    echo "<h3>3. Tìm ID cao nhất trong bảng</h3>";
    $stmt = $pdo->query("SELECT MAX(id) as max_id, MIN(id) as min_id, COUNT(*) as total_records FROM maintenance_tasks");
    $result = $stmt->fetch();
    
    echo "<p><strong>ID cao nhất:</strong> " . ($result['max_id'] ?: 'NULL (bảng trống)') . "</p>";
    echo "<p><strong>ID thấp nhất:</strong> " . ($result['min_id'] ?: 'NULL (bảng trống)') . "</p>";
    echo "<p><strong>Tổng số records:</strong> " . $result['total_records'] . "</p>";
    
    // Bước 4: Kiểm tra có record nào có ID = 0 không
    echo "<h3>4. Kiểm tra records có ID = 0</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM maintenance_tasks WHERE id = 0");
    $result = $stmt->fetch();
    
    echo "<p><strong>Số records có ID = 0:</strong> " . $result['count'] . "</p>";
    
    if ($result['count'] > 0) {
        echo "<p style='color: red;'><strong>⚠️ CẢNH BÁO:</strong> Có " . $result['count'] . " record(s) có ID = 0. Điều này có thể gây ra lỗi auto-increment.</p>";
    }
    
    // Bước 5: Test insert
    echo "<h3>5. Test insert để kiểm tra auto-increment</h3>";
    
    $testCode = 'TEST_' . date('YmdHis');
    echo "<p>Đang tạo record test với mã: " . $testCode . "</p>";
    
    $stmt = $pdo->prepare("INSERT INTO maintenance_tasks (task_number, maintenance_case_id, task_name, status, created_by) VALUES (?, 1, 'Test Task', 'Chờ xử lý', 1)");
    $stmt->execute([$testCode]);
    
    $testId = $pdo->lastInsertId();
    echo "<p style='color: green;'><strong>✓ Test insert thành công!</strong></p>";
    echo "<p><strong>ID được tạo:</strong> " . $testId . "</p>";
    
    // Bước 6: Kiểm tra record vừa tạo
    $stmt = $pdo->prepare("SELECT id, task_number, created_at FROM maintenance_tasks WHERE task_number = ?");
    $stmt->execute([$testCode]);
    $testRecord = $stmt->fetch();
    
    if ($testRecord) {
        echo "<p><strong>Record test:</strong></p>";
        echo "<ul>";
        echo "<li>ID: " . $testRecord['id'] . "</li>";
        echo "<li>Mã: " . $testRecord['task_number'] . "</li>";
        echo "<li>Thời gian tạo: " . $testRecord['created_at'] . "</li>";
        echo "</ul>";
    }
    
    // Bước 7: Xóa record test
    echo "<h3>6. Xóa record test</h3>";
    $stmt = $pdo->prepare("DELETE FROM maintenance_tasks WHERE task_number = ?");
    $stmt->execute([$testCode]);
    
    echo "<p style='color: blue;'><strong>✓ Đã xóa record test</strong></p>";
    
    // Bước 8: Kiểm tra AUTO_INCREMENT sau khi test
    $stmt = $pdo->query("SHOW TABLE STATUS LIKE 'maintenance_tasks'");
    $tableStatusAfter = $stmt->fetch();
    
    echo "<h3>7. Kiểm tra AUTO_INCREMENT sau khi test</h3>";
    echo "<p><strong>AUTO_INCREMENT sau test:</strong> " . ($tableStatusAfter['Auto_increment'] ?: 'NULL') . "</p>";
    
    // Bước 9: Đánh giá kết quả
    echo "<h3>8. Đánh giá kết quả</h3>";
    
    $maxId = $result['max_id'] ?? 0;
    $currentAutoIncrement = $tableStatusAfter['Auto_increment'] ?? 0;
    $expectedNextId = $maxId + 1;
    
    if ($currentAutoIncrement == $expectedNextId) {
        echo "<p style='color: green;'><strong>✅ AUTO_INCREMENT hoạt động bình thường!</strong></p>";
        echo "<p>Giá trị AUTO_INCREMENT (" . $currentAutoIncrement . ") đúng bằng ID cao nhất + 1 (" . $expectedNextId . ")</p>";
    } else {
        echo "<p style='color: red;'><strong>❌ AUTO_INCREMENT có vấn đề!</strong></p>";
        echo "<p>Giá trị AUTO_INCREMENT (" . $currentAutoIncrement . ") khác với ID cao nhất + 1 (" . $expectedNextId . ")</p>";
        echo "<p>Bạn cần chạy file fix để sửa lỗi này.</p>";
    }
    
    if ($testId == $expectedNextId) {
        echo "<p style='color: green;'><strong>✅ Test insert thành công với ID đúng!</strong></p>";
    } else {
        echo "<p style='color: red;'><strong>❌ Test insert có vấn đề!</strong></p>";
        echo "<p>ID được tạo (" . $testId . ") khác với ID mong đợi (" . $expectedNextId . ")</p>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>✗ Lỗi: " . $e->getMessage() . "</h3>";
    error_log("Error in test_maintenance_tasks_auto_increment.php: " . $e->getMessage());
}

echo "<hr>";
echo "<p><a href='maintenance_requests.php'>← Quay lại trang Yêu cầu bảo trì</a></p>";
echo "<p><a href='fix_maintenance_cases_auto_increment.php'>🔧 Chạy fix auto-increment cho Cases</a></p>";
echo "<p><a href='fix_maintenance_tasks_auto_increment.php'>🔧 Chạy fix auto-increment cho Tasks</a></p>";
?>
