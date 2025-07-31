<?php
header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../config/db.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

try {
    $maintenance_request_id = $_GET['maintenance_request_id'] ?? null;
    
    $sql = "SELECT 
                mc.*,
                s.fullname as assigned_to_name,
                (
                    SELECT COUNT(*) FROM maintenance_tasks mt WHERE mt.maintenance_case_id = mc.id
                ) as total_tasks,
                (
                    SELECT COUNT(*) FROM maintenance_tasks mt WHERE mt.maintenance_case_id = mc.id AND mt.status = 'Hoàn thành'
                ) as completed_tasks
            FROM maintenance_cases mc
            LEFT JOIN staffs s ON mc.assigned_to = s.id";
    
    $params = [];
    
    if ($maintenance_request_id) {
        $sql .= " WHERE mc.maintenance_request_id = ?";
        $params[] = $maintenance_request_id;
    }
    
    $sql .= " ORDER BY mc.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $cases
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_maintenance_cases.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi lấy danh sách cases bảo trì'
    ]);
}
?> 