<?php
/**
 * Script cập nhật cột role trong bảng staffs
 */

header('Content-Type: application/json');

require_once 'config/db.php';

try {
    // Cập nhật cột role để hỗ trợ các vai trò mới
    $sql = "ALTER TABLE staffs MODIFY COLUMN role ENUM('user', 'admin', 'hr', 'sale', 'it', 'leader') NOT NULL DEFAULT 'user' COMMENT 'Vai trò trong hệ thống'";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute();
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Cột role đã được cập nhật thành công!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể cập nhật cột role'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?> 