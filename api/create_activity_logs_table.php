<?php
/**
 * Script tạo bảng activity_logs
 */

require_once '../config/db.php';

try {
    // Kiểm tra xem bảng đã tồn tại chưa
    $stmt = $pdo->query("SHOW TABLES LIKE 'activity_logs'");
    if ($stmt->rowCount() > 0) {
        echo "Bảng activity_logs đã tồn tại.\n";
        exit;
    }
    
    // Tạo bảng activity_logs
    $sql = "CREATE TABLE IF NOT EXISTS `activity_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL COMMENT 'ID người dùng thực hiện hành động',
        `action` varchar(100) NOT NULL COMMENT 'Hành động được thực hiện',
        `details` text DEFAULT NULL COMMENT 'Chi tiết hành động',
        `ip_address` varchar(45) DEFAULT NULL COMMENT 'Địa chỉ IP',
        `user_agent` text DEFAULT NULL COMMENT 'User agent',
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `action` (`action`),
        KEY `created_at` (`created_at`),
        CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `staffs` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng log hoạt động của người dùng'";
    
    $pdo->exec($sql);
    echo "Đã tạo bảng activity_logs thành công.\n";
    
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
}
?> 