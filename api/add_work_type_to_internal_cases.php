<?php
/**
 * Add work_type column to internal_cases table
 * File: api/add_work_type_to_internal_cases.php
 * Purpose: Add work_type column for remote, onsite, offsite classification
 */

// Include necessary files
require_once 'includes/session.php';
require_once 'config/db.php';

// Bảo vệ API - yêu cầu đăng nhập
requireLogin();

try {
    // Kiểm tra xem cột work_type đã tồn tại chưa
    $checkColumn = $pdo->query("SHOW COLUMNS FROM internal_cases LIKE 'work_type'");
    
    if ($checkColumn->rowCount() == 0) {
        // Thêm cột work_type
        $sql = "ALTER TABLE internal_cases ADD COLUMN work_type VARCHAR(20) DEFAULT 'onsite' COMMENT 'Hình thức: remote, onsite, offsite'";
        $pdo->exec($sql);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm cột work_type vào bảng internal_cases'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Cột work_type đã tồn tại trong bảng internal_cases'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>
