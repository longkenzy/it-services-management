<?php
/**
 * Script kiểm tra các bảng cần thiết
 */

require_once '../config/db.php';

header('Content-Type: application/json');

try {
    $tables = ['leave_requests', 'staffs', 'notifications', 'activity_logs'];
    $results = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        
        if ($exists) {
            // Đếm số bản ghi
            $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $results[$table] = ['exists' => true, 'count' => $count];
        } else {
            $results[$table] = ['exists' => false, 'count' => 0];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $results
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?> 