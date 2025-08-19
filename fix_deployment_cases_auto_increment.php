<?php
/**
 * Script sửa lỗi auto increment cho bảng deployment_cases
 * Chạy file này một lần trên hosting để khắc phục lỗi id = 0
 */

require_once 'config/db.php';

echo "<h2>Sửa lỗi Auto Increment cho bảng deployment_cases</h2>";

try {
    // 1. Kiểm tra cấu trúc bảng hiện tại
    echo "<p>1. Kiểm tra cấu trúc bảng deployment_cases...</p>";
    $sql1 = "DESCRIBE deployment_cases";
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
    $sql2 = "SELECT id, case_code, created_at FROM deployment_cases ORDER BY id ASC LIMIT 10";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute();
    $records = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
    echo "<tr><th>ID</th><th>Case Code</th><th>Created At</th></tr>";
    foreach ($records as $record) {
        echo "<tr>";
        echo "<td>{$record['id']}</td>";
        echo "<td>{$record['case_code']}</td>";
        echo "<td>{$record['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 3. Kiểm tra auto increment hiện tại
    echo "<p>3. Kiểm tra auto increment hiện tại...</p>";
    $sql3 = "SHOW TABLE STATUS LIKE 'deployment_cases'";
    $stmt3 = $pdo->prepare($sql3);
    $stmt3->execute();
    $tableStatus = $stmt3->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Auto Increment hiện tại: <strong>{$tableStatus['Auto_increment']}</strong></p>";
    
    // 4. Tìm ID cao nhất
    $sql4 = "SELECT MAX(id) as max_id FROM deployment_cases";
    $stmt4 = $pdo->prepare($sql4);
    $stmt4->execute();
    $maxResult = $stmt4->fetch(PDO::FETCH_ASSOC);
    $maxId = $maxResult['max_id'] ?? 0;
    
    echo "<p>ID cao nhất hiện có: <strong>$maxId</strong></p>";
    
    // 5. Sửa auto increment nếu cần
    if ($tableStatus['Auto_increment'] <= $maxId) {
        echo "<p>4. Đang sửa auto increment...</p>";
        $nextId = $maxId + 1;
        $sql5 = "ALTER TABLE deployment_cases AUTO_INCREMENT = $nextId";
        $stmt5 = $pdo->prepare($sql5);
        $stmt5->execute();
        echo "<p style='color: green;'>✓ Thành công: Đã set auto increment = $nextId</p>";
    } else {
        echo "<p style='color: blue;'>✓ Auto increment đã đúng, không cần sửa</p>";
    }
    
    // 6. Kiểm tra lại cấu trúc cột id
    echo "<p>5. Kiểm tra cấu trúc cột id...</p>";
    $sql6 = "SHOW CREATE TABLE deployment_cases";
    $stmt6 = $pdo->prepare($sql6);
    $stmt6->execute();
    $createTable = $stmt6->fetch(PDO::FETCH_ASSOC);
    
    if (strpos($createTable['Create Table'], '`id` int(11) NOT NULL AUTO_INCREMENT') === false) {
        echo "<p>6. Đang sửa cấu trúc cột id...</p>";
        $sql7 = "ALTER TABLE deployment_cases MODIFY COLUMN id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
        $stmt7 = $pdo->prepare($sql7);
        $stmt7->execute();
        echo "<p style='color: green;'>✓ Thành công: Đã sửa cấu trúc cột id</p>";
    } else {
        echo "<p style='color: blue;'>✓ Cấu trúc cột id đã đúng</p>";
    }
    
    // 7. Kiểm tra lại auto increment sau khi sửa
    echo "<p>7. Kiểm tra lại auto increment sau khi sửa...</p>";
    $sql8 = "SHOW TABLE STATUS LIKE 'deployment_cases'";
    $stmt8 = $pdo->prepare($sql8);
    $stmt8->execute();
    $tableStatusAfter = $stmt8->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Auto Increment sau khi sửa: <strong>{$tableStatusAfter['Auto_increment']}</strong></p>";
    
    // 8. Test insert một record để kiểm tra
    echo "<p>8. Test insert để kiểm tra auto increment...</p>";
    try {
        $testSql = "INSERT INTO deployment_cases (
            case_code, deployment_request_id, request_type, assigned_to, status, created_by
        ) VALUES (?, ?, ?, ?, ?, ?)";
        $testStmt = $pdo->prepare($testSql);
        $testStmt->execute([
            'TEST_' . date('YmdHis'),
            1, // deployment_request_id
            'Test Request Type',
            1, // assigned_to
            'Tiếp nhận',
            1  // created_by
        ]);
        
        $testId = $pdo->lastInsertId();
        echo "<p style='color: green;'>✓ Test insert thành công! ID mới: <strong>$testId</strong></p>";
        
        // Xóa record test
        $deleteSql = "DELETE FROM deployment_cases WHERE id = ?";
        $deleteStmt = $pdo->prepare($deleteSql);
        $deleteStmt->execute([$testId]);
        echo "<p style='color: blue;'>✓ Đã xóa record test</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Test insert thất bại: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3 style='color: green;'>Hoàn thành kiểm tra và sửa lỗi!</h3>";
    echo "<p>Bây giờ bạn có thể tạo deployment case mới và ID sẽ được tự động tăng đúng cách.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
}
?>
