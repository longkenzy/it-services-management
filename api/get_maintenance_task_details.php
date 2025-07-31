<?php
header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../config/db.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$task_id = $_GET['id'] ?? null;

if (!$task_id) {
    echo json_encode(['success' => false, 'message' => 'ID task không hợp lệ']);
    exit;
}

try {
    $sql = "SELECT 
                mt.*,
                s.fullname as assigned_to_name
            FROM maintenance_tasks mt
            LEFT JOIN staffs s ON mt.assigned_to = s.id
            WHERE mt.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$task_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$task) {
        echo json_encode(['success' => false, 'message' => 'Task bảo trì không tồn tại']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $task
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_maintenance_task_details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi lấy thông tin task bảo trì'
    ]);
}
?> 