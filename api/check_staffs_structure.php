<?php
/**
 * Script kiểm tra cấu trúc bảng staffs
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
    
    // Lấy cấu trúc bảng
    $stmt = $pdo->query("DESCRIBE staffs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy dữ liệu mẫu
    $stmt = $pdo->query("SELECT * FROM staffs LIMIT 3");
    $sample_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Đếm tổng số
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM staffs");
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo json_encode([
        'success' => true,
        'table_exists' => true,
        'columns' => $columns,
        'sample_data' => $sample_data,
        'total_records' => $total
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?> 