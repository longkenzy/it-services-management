<?php
// Test kết nối database
require_once 'config/db.php';

echo "Kết nối database thành công!\n";

// Test query đơn giản
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM staffs");
    $result = $stmt->fetch();
    echo "Số lượng staff: " . $result['count'] . "\n";
    
    // Kiểm tra bảng notifications
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        echo "Bảng notifications đã tồn tại!\n";
    } else {
        echo "Bảng notifications chưa tồn tại!\n";
    }
    
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
}
?> 