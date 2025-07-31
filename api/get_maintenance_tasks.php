<?php
require_once '../config/db.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

try {
    // Lấy tham số filter
    $maintenance_case_id = isset($_GET['maintenance_case_id']) ? $_GET['maintenance_case_id'] : null;
    $maintenance_request_id = isset($_GET['maintenance_request_id']) ? $_GET['maintenance_request_id'] : null;
    
    // Xây dựng câu query
    $sql = "SELECT 
                mt.*,
                s.fullname as assignee_name
            FROM maintenance_tasks mt
            LEFT JOIN staffs s ON mt.assignee_id = s.id
            WHERE 1=1";
    
    $params = [];
    
    // Thêm điều kiện filter
    if ($maintenance_case_id) {
        $sql .= " AND mt.maintenance_case_id = ?";
        $params[] = $maintenance_case_id;
    }
    
    if ($maintenance_request_id) {
        $sql .= " AND mt.maintenance_request_id = ?";
        $params[] = $maintenance_request_id;
    }
    
    $sql .= " ORDER BY mt.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $tasks
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_maintenance_tasks.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi lấy danh sách tasks bảo trì'
    ]);
}
?> 