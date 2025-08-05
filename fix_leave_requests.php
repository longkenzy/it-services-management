<?php
/**
 * Script sửa chữa bảng leave_requests
 * Chạy script này để khắc phục vấn đề id = 0 và created_at = 0000-00-00
 */

require_once 'config/db.php';

echo "<h2>Sửa chữa bảng leave_requests</h2>";

try {
    // 1. Kiểm tra trạng thái hiện tại
    echo "<h3>1. Kiểm tra trạng thái hiện tại</h3>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total, MIN(id) as min_id, MAX(id) as max_id FROM leave_requests");
    $status = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Tổng số records: " . $status['total'] . "<br>";
    echo "ID nhỏ nhất: " . $status['min_id'] . "<br>";
    echo "ID lớn nhất: " . $status['max_id'] . "<br>";
    
    // Kiểm tra records có created_at = 0000-00-00
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM leave_requests WHERE created_at = '0000-00-00 00:00:00' OR created_at IS NULL");
        $invalid_dates = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Records có created_at không hợp lệ: " . $invalid_dates['count'] . "<br><br>";
    } catch (Exception $e) {
        echo "⚠ Không thể kiểm tra records có created_at không hợp lệ do strict mode<br>";
        echo "Sẽ sửa chữa trong bước tiếp theo<br><br>";
    }
    
    // 2. Sửa chữa cấu trúc bảng
    echo "<h3>2. Sửa chữa cấu trúc bảng</h3>";
    
    // Sửa cột id
    $pdo->exec("ALTER TABLE `leave_requests` MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT");
    echo "✓ Đã sửa cột id<br>";
    
    // Sửa cột created_at
    $pdo->exec("ALTER TABLE `leave_requests` MODIFY COLUMN `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo'");
    echo "✓ Đã sửa cột created_at<br>";
    
    // Sửa cột updated_at
    $pdo->exec("ALTER TABLE `leave_requests` MODIFY COLUMN `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật'");
    echo "✓ Đã sửa cột updated_at<br>";
    
    // 3. Đảm bảo auto increment
    echo "<h3>3. Đảm bảo auto increment</h3>";
    $pdo->exec("ALTER TABLE `leave_requests` AUTO_INCREMENT = 1");
    echo "✓ Đã reset auto increment<br>";
    
    // 4. Sửa chữa dữ liệu bị lỗi
    echo "<h3>4. Sửa chữa dữ liệu bị lỗi</h3>";
    
    // Tạm thời tắt strict mode để sửa chữa dữ liệu
    $pdo->exec("SET sql_mode = ''");
    echo "✓ Đã tạm thời tắt strict mode để sửa chữa dữ liệu<br>";
    
    try {
        $stmt = $pdo->prepare("UPDATE `leave_requests` SET `created_at` = NOW() WHERE `created_at` = '0000-00-00 00:00:00' OR `created_at` IS NULL");
        $stmt->execute();
        echo "✓ Đã sửa " . $stmt->rowCount() . " records có created_at không hợp lệ<br>";
    } catch (Exception $e) {
        echo "⚠ Lỗi khi sửa chữa dữ liệu: " . $e->getMessage() . "<br>";
    }
    
    // Bật lại strict mode
    $pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
    echo "✓ Đã bật lại strict mode<br>";
    
    // 5. Kiểm tra và sửa chữa foreign keys
    echo "<h3>5. Kiểm tra và sửa chữa foreign keys</h3>";
    
    // Kiểm tra foreign key requester_id
    try {
        $pdo->exec("ALTER TABLE `leave_requests` ADD CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `staffs` (`id`) ON DELETE CASCADE");
        echo "✓ Đã thêm foreign key requester_id<br>";
    } catch (Exception $e) {
        echo "ℹ Foreign key requester_id đã tồn tại hoặc có lỗi: " . $e->getMessage() . "<br>";
    }
    
    // Kiểm tra foreign key handover_to
    try {
        $pdo->exec("ALTER TABLE `leave_requests` ADD CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`handover_to`) REFERENCES `staffs` (`id`) ON DELETE SET NULL");
        echo "✓ Đã thêm foreign key handover_to<br>";
    } catch (Exception $e) {
        echo "ℹ Foreign key handover_to đã tồn tại hoặc có lỗi: " . $e->getMessage() . "<br>";
    }
    
    // 6. Tạo lại indexes
    echo "<h3>6. Tạo lại indexes</h3>";
    
    $indexes = [
        'idx_leave_requests_requester_id' => '(`requester_id`)',
        'idx_leave_requests_status' => '(`status`)',
        'idx_leave_requests_created_at' => '(`created_at`)'
    ];
    
    foreach ($indexes as $index_name => $index_columns) {
        try {
            $pdo->exec("CREATE INDEX `$index_name` ON `leave_requests` $index_columns");
            echo "✓ Đã tạo index $index_name<br>";
        } catch (Exception $e) {
            echo "ℹ Index $index_name đã tồn tại hoặc có lỗi: " . $e->getMessage() . "<br>";
        }
    }
    
    // 7. Thiết lập MySQL settings
    echo "<h3>7. Thiết lập MySQL settings</h3>";
    
    $pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
    echo "✓ Đã thiết lập strict mode<br>";
    
    $pdo->exec("SET time_zone = '+07:00'");
    echo "✓ Đã thiết lập timezone<br>";
    
    // 8. Kiểm tra kết quả
    echo "<h3>8. Kiểm tra kết quả</h3>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total, MIN(id) as min_id, MAX(id) as max_id, MIN(created_at) as earliest_created, MAX(created_at) as latest_created FROM leave_requests");
    $final_status = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Tổng số records: " . $final_status['total'] . "<br>";
    echo "ID nhỏ nhất: " . $final_status['min_id'] . "<br>";
    echo "ID lớn nhất: " . $final_status['max_id'] . "<br>";
    echo "Ngày tạo sớm nhất: " . $final_status['earliest_created'] . "<br>";
    echo "Ngày tạo muộn nhất: " . $final_status['latest_created'] . "<br>";
    
    // Kiểm tra lại records có created_at = 0000-00-00
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM leave_requests WHERE created_at = '0000-00-00 00:00:00' OR created_at IS NULL");
        $final_invalid_dates = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Records có created_at không hợp lệ: " . $final_invalid_dates['count'] . "<br>";
    } catch (Exception $e) {
        echo "Records có created_at không hợp lệ: Không thể kiểm tra do strict mode<br>";
    }
    
    if ($final_status['min_id'] > 0) {
        echo "<h3 style='color: green;'>✓ Sửa chữa thành công!</h3>";
        echo "<p>Bảng leave_requests đã được sửa chữa và sẵn sàng sử dụng.</p>";
    } else {
        echo "<h3 style='color: orange;'>⚠ Vẫn còn một số vấn đề cần kiểm tra thêm</h3>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>✗ Lỗi:</h3>";
    echo "Message: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?> 