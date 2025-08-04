<!DOCTYPE html>
<html>
<head>
    <title>Test Database</title>
</head>
<body>
    <h1>Test Database Connection</h1>
    <?php
    try {
        require_once 'config/db.php';
        echo "<p style='color: green;'>✅ Kết nối database thành công!</p>";
        
        // Test query staffs
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM staffs");
        $result = $stmt->fetch();
        echo "<p>Số lượng staff: " . $result['count'] . "</p>";
        
        // Kiểm tra bảng notifications
        $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color: green;'>✅ Bảng notifications đã tồn tại!</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Bảng notifications chưa tồn tại!</p>";
            
            // Tạo bảng notifications
            echo "<p>Đang tạo bảng notifications...</p>";
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
            echo "<p style='color: green;'>✅ Đã tạo bảng notifications!</p>";
            
            // Tạo index
            $pdo->exec("CREATE INDEX idx_notifications_user_id ON notifications(user_id)");
            $pdo->exec("CREATE INDEX idx_notifications_type ON notifications(type)");
            $pdo->exec("CREATE INDEX idx_notifications_is_read ON notifications(is_read)");
            $pdo->exec("CREATE INDEX idx_notifications_created_at ON notifications(created_at)");
            echo "<p style='color: green;'>✅ Đã tạo các index!</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Lỗi: " . $e->getMessage() . "</p>";
    }
    ?>
</body>
</html> 