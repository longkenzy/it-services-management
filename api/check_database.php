<?php
/**
 * API kiểm tra database
 */

header('Content-Type: application/json');

try {
    require_once '../config/db.php';
    
    $response = [
        'success' => true,
        'database_status' => 'unknown',
        'connection_info' => [],
        'tables' => [],
        'leave_requests_info' => []
    ];
    
    // Kiểm tra kết nối
    $pdo->query("SELECT 1");
    $response['database_status'] = 'connected';
    
    // Thông tin kết nối
    $response['connection_info'] = [
        'database' => $pdo->query("SELECT DATABASE()")->fetchColumn(),
        'version' => $pdo->query("SELECT VERSION()")->fetchColumn(),
        'timezone' => $pdo->query("SELECT @@time_zone")->fetchColumn(),
        'sql_mode' => $pdo->query("SELECT @@sql_mode")->fetchColumn()
    ];
    
    // Kiểm tra các bảng quan trọng
    $tables = ['staffs', 'leave_requests', 'notifications', 'activity_logs'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        
        if ($exists) {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            $response['tables'][$table] = [
                'exists' => true,
                'count' => $count
            ];
        } else {
            $response['tables'][$table] = [
                'exists' => false,
                'count' => 0
            ];
        }
    }
    
    // Thông tin chi tiết về bảng leave_requests
    if ($response['tables']['leave_requests']['exists']) {
        $stmt = $pdo->query("DESCRIBE leave_requests");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['leave_requests_info'] = [
            'columns' => $columns,
            'recent_records' => []
        ];
        
        // Lấy 3 records gần nhất
        $stmt = $pdo->query("SELECT id, request_code, created_at FROM leave_requests ORDER BY id DESC LIMIT 3");
        $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response['leave_requests_info']['recent_records'] = $recent;
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?> 