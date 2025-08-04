<?php
/**
 * Script kiểm tra cấu trúc bảng leave_requests
 */

require_once '../config/db.php';

header('Content-Type: application/json');

try {
    // Kiểm tra cấu trúc bảng leave_requests
    $stmt = $pdo->query("DESCRIBE leave_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $structure = [];
    foreach ($columns as $column) {
        $structure[] = [
            'field' => $column['Field'],
            'type' => $column['Type'],
            'null' => $column['Null'],
            'key' => $column['Key'],
            'default' => $column['Default'],
            'extra' => $column['Extra']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $structure
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?> 