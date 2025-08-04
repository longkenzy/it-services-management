<?php
/**
 * File sửa lỗi bảng deployment_requests trên hosting
 * Chạy file này một lần trên hosting để sửa cấu trúc bảng
 */

require_once 'config/db.php';

echo "<h2>Sửa lỗi bảng deployment_requests</h2>";

try {
    // 1. Thêm AUTO_INCREMENT cho cột id
    echo "<p>1. Đang thêm AUTO_INCREMENT cho cột id...</p>";
    $sql1 = "ALTER TABLE deployment_requests MODIFY COLUMN id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute();
    echo "<p style='color: green;'>✓ Thành công: Đã thêm AUTO_INCREMENT cho cột id</p>";
    
    // 2. Sửa lại cột created_at
    echo "<p>2. Đang sửa cột created_at...</p>";
    $sql2 = "ALTER TABLE deployment_requests MODIFY COLUMN created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute();
    echo "<p style='color: green;'>✓ Thành công: Đã sửa cột created_at</p>";
    
    // 3. Sửa lại cột updated_at
    echo "<p>3. Đang sửa cột updated_at...</p>";
    $sql3 = "ALTER TABLE deployment_requests MODIFY COLUMN updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
    $stmt3 = $pdo->prepare($sql3);
    $stmt3->execute();
    echo "<p style='color: green;'>✓ Thành công: Đã sửa cột updated_at</p>";
    
    // 4. Kiểm tra cấu trúc bảng
    echo "<p>4. Kiểm tra cấu trúc bảng sau khi sửa:</p>";
    $sql4 = "DESCRIBE deployment_requests";
    $stmt4 = $pdo->prepare($sql4);
    $stmt4->execute();
    $columns = $stmt4->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 5. Kiểm tra AUTO_INCREMENT
    echo "<p>5. Kiểm tra AUTO_INCREMENT:</p>";
    $sql5 = "SHOW TABLE STATUS LIKE 'deployment_requests'";
    $stmt5 = $pdo->prepare($sql5);
    $stmt5->execute();
    $tableStatus = $stmt5->fetch(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Auto_increment hiện tại:</strong> " . $tableStatus['Auto_increment'] . "</p>";
    
    echo "<h3 style='color: green;'>✓ Hoàn thành! Bảng deployment_requests đã được sửa thành công.</h3>";
    echo "<p>Bây giờ bạn có thể tạo yêu cầu triển khai mới và id sẽ tự động tăng.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<p><strong>Lưu ý:</strong> Sau khi chạy xong, hãy xóa file này để bảo mật.</p> 