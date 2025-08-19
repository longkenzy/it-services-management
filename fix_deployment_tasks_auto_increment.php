<?php
/**
 * Script sửa lỗi auto increment cho bảng deployment_tasks
 * Chạy file này một lần trên hosting để khắc phục lỗi id = 0
 */

require_once 'config/db.php';

echo "<h2>Sửa lỗi Auto Increment cho bảng deployment_tasks</h2>";

try {
    // 1. Kiểm tra cấu trúc bảng hiện tại
    echo "<p>1. Kiểm tra cấu trúc bảng deployment_tasks...</p>";
    $sql1 = "DESCRIBE deployment_tasks";
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute();
    $columns = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
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
    echo "</table>";
    
    // 2. Kiểm tra dữ liệu hiện có
    echo "<p>2. Kiểm tra dữ liệu hiện có...</p>";
    $sql2 = "SELECT id, task_number, created_at FROM deployment_tasks ORDER BY id ASC LIMIT 10";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute();
    $records = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
    echo "<tr><th>ID</th><th>Task Number</th><th>Created At</th></tr>";
    foreach ($records as $record) {
        echo "<tr>";
        echo "<td>{$record['id']}</td>";
        echo "<td>{$record['task_number']}</td>";
        echo "<td>{$record['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Xóa dữ liệu lỗi (ID = 0)
    echo "<p>3. Xóa dữ liệu lỗi (ID = 0)...</p>";
    $deleteSql = "DELETE FROM deployment_tasks WHERE id = 0";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute();
    $deletedCount = $deleteStmt->rowCount();
    echo "<p style='color: orange;'>✓ Đã xóa $deletedCount record có ID = 0</p>";
    
    // 4. Thêm PRIMARY KEY và AUTO_INCREMENT cho cột id
    echo "<p>4. Thêm PRIMARY KEY và AUTO_INCREMENT cho cột id...</p>";
    
    // Kiểm tra xem có PRIMARY KEY chưa
    $checkPrimarySql = "SHOW KEYS FROM deployment_tasks WHERE Key_name = 'PRIMARY'";
    $checkPrimaryStmt = $pdo->prepare($checkPrimarySql);
    $checkPrimaryStmt->execute();
    $hasPrimary = $checkPrimaryStmt->rowCount() > 0;
    
    if (!$hasPrimary) {
        // Thêm PRIMARY KEY và AUTO_INCREMENT
        $alterSql = "ALTER TABLE deployment_tasks MODIFY COLUMN id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
        $alterStmt = $pdo->prepare($alterSql);
        $alterStmt->execute();
        echo "<p style='color: green;'>✓ Thành công: Đã thêm PRIMARY KEY và AUTO_INCREMENT cho cột id</p>";
    } else {
        echo "<p style='color: blue;'>✓ Cột id đã có PRIMARY KEY</p>";
    }
    
    // 5. Kiểm tra auto increment hiện tại
    echo "<p>5. Kiểm tra auto increment hiện tại...</p>";
    $sql3 = "SHOW TABLE STATUS LIKE 'deployment_tasks'";
    $stmt3 = $pdo->prepare($sql3);
    $stmt3->execute();
    $tableStatus = $stmt3->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Auto Increment hiện tại: <strong>{$tableStatus['Auto_increment']}</strong></p>";
    
    // 6. Tìm ID cao nhất
    $sql4 = "SELECT MAX(id) as max_id FROM deployment_tasks";
    $stmt4 = $pdo->prepare($sql4);
    $stmt4->execute();
    $maxResult = $stmt4->fetch(PDO::FETCH_ASSOC);
    $maxId = $maxResult['max_id'] ?? 0;
    
    echo "<p>ID cao nhất hiện có: <strong>$maxId</strong></p>";
    
    // 7. Sửa auto increment nếu cần
    if ($tableStatus['Auto_increment'] <= $maxId) {
        echo "<p>6. Đang sửa auto increment...</p>";
        $nextId = $maxId + 1;
        $sql5 = "ALTER TABLE deployment_tasks AUTO_INCREMENT = $nextId";
        $stmt5 = $pdo->prepare($sql5);
        $stmt5->execute();
        echo "<p style='color: green;'>✓ Thành công: Đã set auto increment = $nextId</p>";
    } else {
        echo "<p style='color: blue;'>✓ Auto increment đã đúng, không cần sửa</p>";
    }
    
    // 8. Sửa dữ liệu timestamp lỗi
    echo "<p>7. Sửa dữ liệu timestamp lỗi...</p>";
    $updateTimestampSql = "UPDATE deployment_tasks SET created_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE created_at = '0000-00-00 00:00:00' OR updated_at = '0000-00-00 00:00:00'";
    $updateTimestampStmt = $pdo->prepare($updateTimestampSql);
    $updateTimestampStmt->execute();
    $updatedCount = $updateTimestampStmt->rowCount();
    echo "<p style='color: green;'>✓ Đã sửa $updatedCount record có timestamp lỗi</p>";
    
    // 9. Kiểm tra lại auto increment sau khi sửa
    echo "<p>8. Kiểm tra lại auto increment sau khi sửa...</p>";
    $sql8 = "SHOW TABLE STATUS LIKE 'deployment_tasks'";
    $stmt8 = $pdo->prepare($sql8);
    $stmt8->execute();
    $tableStatusAfter = $stmt8->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Auto Increment sau khi sửa: <strong>{$tableStatusAfter['Auto_increment']}</strong></p>";
    
    // 10. Test insert một record để kiểm tra
    echo "<p>9. Test insert để kiểm tra auto increment...</p>";
    try {
        // Kiểm tra xem có deployment_case_id và staff_id hợp lệ không
        $checkCaseSql = "SELECT id FROM deployment_cases LIMIT 1";
        $checkCaseStmt = $pdo->prepare($checkCaseSql);
        $checkCaseStmt->execute();
        $caseResult = $checkCaseStmt->fetch(PDO::FETCH_ASSOC);
        
        $checkStaffSql = "SELECT id FROM staffs LIMIT 1";
        $checkStaffStmt = $pdo->prepare($checkStaffSql);
        $checkStaffStmt->execute();
        $staffResult = $checkStaffStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($caseResult && $staffResult) {
            $testSql = "INSERT INTO deployment_tasks (
                task_number, deployment_case_id, task_type, task_description, assignee_id, status
            ) VALUES (?, ?, ?, ?, ?, ?)";
            $testStmt = $pdo->prepare($testSql);
            $testStmt->execute([
                'TEST_' . date('YmdHis'),
                $caseResult['id'], // deployment_case_id
                'Test Task Type',
                'Test task description',
                $staffResult['id'], // assignee_id
                'Tiếp nhận'
            ]);
            
            $testId = $pdo->lastInsertId();
            echo "<p style='color: green;'>✓ Test insert thành công! ID mới: <strong>$testId</strong></p>";
            
            // Xóa record test
            $deleteSql = "DELETE FROM deployment_tasks WHERE id = ?";
            $deleteStmt = $pdo->prepare($deleteSql);
            $deleteStmt->execute([$testId]);
            echo "<p style='color: blue;'>✓ Đã xóa record test</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Không thể test insert vì thiếu dữ liệu trong bảng deployment_cases hoặc staffs</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Test insert thất bại: " . $e->getMessage() . "</p>";
    }
    
    // 11. Hiển thị kết quả cuối cùng
    echo "<p>10. Kiểm tra kết quả cuối cùng...</p>";
    $finalSql = "SELECT id, task_number, created_at FROM deployment_tasks ORDER BY id ASC LIMIT 5";
    $finalStmt = $pdo->prepare($finalSql);
    $finalStmt->execute();
    $finalRecords = $finalStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($finalRecords)) {
        echo "<p style='color: blue;'>Bảng deployment_tasks hiện tại trống</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
        echo "<tr><th>ID</th><th>Task Number</th><th>Created At</th></tr>";
        foreach ($finalRecords as $record) {
            echo "<tr>";
            echo "<td>{$record['id']}</td>";
            echo "<td>{$record['task_number']}</td>";
            echo "<td>{$record['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3 style='color: green;'>Hoàn thành sửa lỗi!</h3>";
    echo "<p>Bây giờ bạn có thể tạo deployment task mới và ID sẽ được tự động tăng đúng cách.</p>";
    echo "<p><strong>Lưu ý:</strong> Đã xóa $deletedCount record có ID = 0 và sửa $updatedCount record có timestamp lỗi.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
    echo "<p>Chi tiết lỗi: " . print_r($e->getTrace(), true) . "</p>";
}
?>
