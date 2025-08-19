<?php
/**
 * Script sửa lỗi auto increment cho tất cả các bảng
 * Chạy file này để sửa lỗi ID = 0 cho deployment_cases và deployment_tasks
 */

require_once 'config/db.php';

echo "<h2>Sửa lỗi Auto Increment cho tất cả các bảng</h2>";

$tables = [
    'deployment_cases' => 'Case triển khai',
    'deployment_tasks' => 'Task triển khai'
];

$results = [];

foreach ($tables as $table => $tableName) {
    echo "<h3>Sửa lỗi cho bảng: $tableName ($table)</h3>";
    
    try {
        // 1. Kiểm tra cấu trúc bảng
        echo "<p>1. Kiểm tra cấu trúc bảng $table...</p>";
        $sql1 = "DESCRIBE $table";
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->execute();
        $columns = $stmt1->fetchAll(PDO::FETCH_ASSOC);
        
        $idColumn = null;
        foreach ($columns as $column) {
            if ($column['Field'] === 'id') {
                $idColumn = $column;
                break;
            }
        }
        
        if (!$idColumn) {
            echo "<p style='color: red;'>✗ Không tìm thấy cột id trong bảng $table</p>";
            continue;
        }
        
        echo "<p>Cột id: {$idColumn['Type']} | Key: {$idColumn['Key']} | Extra: {$idColumn['Extra']}</p>";
        
        // 2. Kiểm tra dữ liệu hiện có
        echo "<p>2. Kiểm tra dữ liệu hiện có...</p>";
        $sql2 = "SELECT COUNT(*) as total, MAX(id) as max_id FROM $table";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute();
        $data = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        $totalRecords = $data['total'] ?? 0;
        $maxId = $data['max_id'] ?? 0;
        
        echo "<p>Tổng số record: $totalRecords | ID cao nhất: $maxId</p>";
        
        // 3. Xóa dữ liệu lỗi (ID = 0)
        echo "<p>3. Xóa dữ liệu lỗi (ID = 0)...</p>";
        $deleteSql = "DELETE FROM $table WHERE id = 0";
        $deleteStmt = $pdo->prepare($deleteSql);
        $deleteStmt->execute();
        $deletedCount = $deleteStmt->rowCount();
        echo "<p style='color: orange;'>✓ Đã xóa $deletedCount record có ID = 0</p>";
        
        // 4. Thêm PRIMARY KEY và AUTO_INCREMENT cho cột id nếu cần
        echo "<p>4. Kiểm tra và sửa cấu trúc cột id...</p>";
        
        $checkPrimarySql = "SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'";
        $checkPrimaryStmt = $pdo->prepare($checkPrimarySql);
        $checkPrimaryStmt->execute();
        $hasPrimary = $checkPrimaryStmt->rowCount() > 0;
        
        if (!$hasPrimary) {
            $alterSql = "ALTER TABLE $table MODIFY COLUMN id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
            $alterStmt = $pdo->prepare($alterSql);
            $alterStmt->execute();
            echo "<p style='color: green;'>✓ Thành công: Đã thêm PRIMARY KEY và AUTO_INCREMENT cho cột id</p>";
        } else {
            echo "<p style='color: blue;'>✓ Cột id đã có PRIMARY KEY</p>";
        }
        
        // 5. Kiểm tra auto increment hiện tại
        echo "<p>5. Kiểm tra auto increment hiện tại...</p>";
        $sql3 = "SHOW TABLE STATUS LIKE '$table'";
        $stmt3 = $pdo->prepare($sql3);
        $stmt3->execute();
        $tableStatus = $stmt3->fetch(PDO::FETCH_ASSOC);
        
        $currentAutoIncrement = $tableStatus['Auto_increment'] ?? 1;
        echo "<p>Auto Increment hiện tại: <strong>$currentAutoIncrement</strong></p>";
        
        // 6. Sửa auto increment nếu cần
        if ($currentAutoIncrement <= $maxId) {
            echo "<p>6. Đang sửa auto increment...</p>";
            $nextId = $maxId + 1;
            $sql5 = "ALTER TABLE $table AUTO_INCREMENT = $nextId";
            $stmt5 = $pdo->prepare($sql5);
            $stmt5->execute();
            echo "<p style='color: green;'>✓ Thành công: Đã set auto increment = $nextId</p>";
        } else {
            echo "<p style='color: blue;'>✓ Auto increment đã đúng, không cần sửa</p>";
        }
        
        // 7. Sửa dữ liệu timestamp lỗi
        echo "<p>7. Sửa dữ liệu timestamp lỗi...</p>";
        $updateTimestampSql = "UPDATE $table SET created_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE created_at = '0000-00-00 00:00:00' OR updated_at = '0000-00-00 00:00:00'";
        $updateTimestampStmt = $pdo->prepare($updateTimestampSql);
        $updateTimestampStmt->execute();
        $updatedCount = $updateTimestampStmt->rowCount();
        echo "<p style='color: green;'>✓ Đã sửa $updatedCount record có timestamp lỗi</p>";
        
        // 8. Kiểm tra lại sau khi sửa
        echo "<p>8. Kiểm tra lại sau khi sửa...</p>";
        $sql8 = "SHOW TABLE STATUS LIKE '$table'";
        $stmt8 = $pdo->prepare($sql8);
        $stmt8->execute();
        $tableStatusAfter = $stmt8->fetch(PDO::FETCH_ASSOC);
        
        $newAutoIncrement = $tableStatusAfter['Auto_increment'] ?? 1;
        echo "<p>Auto Increment sau khi sửa: <strong>$newAutoIncrement</strong></p>";
        
        // Lưu kết quả
        $results[$table] = [
            'success' => true,
            'deleted_count' => $deletedCount,
            'updated_count' => $updatedCount,
            'old_auto_increment' => $currentAutoIncrement,
            'new_auto_increment' => $newAutoIncrement,
            'max_id' => $maxId
        ];
        
        echo "<p style='color: green;'>✓ Hoàn thành sửa lỗi cho bảng $table</p>";
        echo "<hr>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Lỗi khi sửa bảng $table: " . $e->getMessage() . "</p>";
        $results[$table] = [
            'success' => false,
            'error' => $e->getMessage()
        ];
        echo "<hr>";
    }
}

// Hiển thị tổng kết
echo "<h3>Tổng kết kết quả</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
echo "<tr><th>Bảng</th><th>Trạng thái</th><th>Đã xóa</th><th>Đã sửa timestamp</th><th>Auto Increment cũ</th><th>Auto Increment mới</th></tr>";

foreach ($results as $table => $result) {
    echo "<tr>";
    echo "<td>$table</td>";
    
    if ($result['success']) {
        echo "<td style='color: green;'>✓ Thành công</td>";
        echo "<td>{$result['deleted_count']}</td>";
        echo "<td>{$result['updated_count']}</td>";
        echo "<td>{$result['old_auto_increment']}</td>";
        echo "<td>{$result['new_auto_increment']}</td>";
    } else {
        echo "<td style='color: red;'>✗ Thất bại</td>";
        echo "<td colspan='4'>{$result['error']}</td>";
    }
    
    echo "</tr>";
}
echo "</table>";

echo "<h3 style='color: green;'>Hoàn thành sửa lỗi!</h3>";
echo "<p>Bây giờ bạn có thể tạo deployment cases và tasks mới và ID sẽ được tự động tăng đúng cách.</p>";

$totalDeleted = array_sum(array_column(array_filter($results, function($r) { return $r['success']; }), 'deleted_count'));
$totalUpdated = array_sum(array_column(array_filter($results, function($r) { return $r['success']; }), 'updated_count'));

echo "<p><strong>Lưu ý:</strong> Đã xóa tổng cộng $totalDeleted record có ID = 0 và sửa $totalUpdated record có timestamp lỗi.</p>";
?>
