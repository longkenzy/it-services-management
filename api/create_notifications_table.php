<?php
/**
 * Script tạo bảng notifications
 */

header('Content-Type: application/json');
require_once 'config/db.php';

try {
    echo "Bắt đầu tạo bảng notifications...\n";
    // Tạo bảng notifications
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('leave_request', 'leave_approved', 'leave_rejected', 'system') DEFAULT 'system',
        related_id INT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES staffs(id) ON DELETE CASCADE
    )";
    
    $pdo->exec($sql);
    
    // Tạo index
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_notifications_type ON notifications(type)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_notifications_is_read ON notifications(is_read)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_notifications_created_at ON notifications(created_at)");
    
    echo "Bảng notifications đã được tạo thành công!\n";
    echo json_encode([
        'success' => true,
        'message' => 'Bảng notifications đã được tạo thành công'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?> 