<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $pdo = getConnection();
    
    $task_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($task_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid task ID']);
        exit;
    }
    
    // Lấy thông tin task
    $sql = "SELECT 
                dt.id, dt.deployment_case_id, dt.task_number, dt.task_type, dt.template_name, 
                dt.task_description, dt.start_date, dt.end_date, dt.assignee_id, dt.status, 
                dt.created_at, dt.updated_at,
                s.fullname as assignee_name
            FROM deployment_tasks dt
            LEFT JOIN staffs s ON dt.assignee_id = s.id
            WHERE dt.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$task_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$task) {
        echo json_encode(['success' => false, 'message' => 'Task không tồn tại']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $task
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_task_details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi lấy thông tin task: ' . $e->getMessage()
    ]);
}
?> 