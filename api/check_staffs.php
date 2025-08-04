<?php
/**
 * Script kiểm tra dữ liệu staffs
 */

header('Content-Type: application/json');

require_once '../config/db.php';

try {
    // Kiểm tra bảng staffs
    $stmt = $pdo->query("SHOW TABLES LIKE 'staffs'");
    if ($stmt->rowCount() == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Bảng staffs không tồn tại'
        ]);
        exit;
    }
    
    // Lấy tất cả staffs
    $stmt = $pdo->query("SELECT id, fullname, position, department, office, resigned FROM staffs ORDER BY id");
    $staffs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy staffs không nghỉ việc
    $stmt = $pdo->query("SELECT id, fullname, position, department, office FROM staffs WHERE resigned = 0 OR resigned IS NULL ORDER BY id");
    $active_staffs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'total_staffs' => count($staffs),
        'active_staffs' => count($active_staffs),
        'all_staffs' => $staffs,
        'active_staffs_list' => $active_staffs
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?> 