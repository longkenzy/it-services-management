<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/session.php';

if (null === getCurrentUserId()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$maintenance_case_id = isset($_GET['maintenance_case_id']) ? intval($_GET['maintenance_case_id']) : 0;
if ($maintenance_case_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid maintenance case ID']);
    exit;
}

try {
    error_log("=== GET MAINTENANCE TASKS DEBUG ===");
    error_log("Requested case_id: " . $maintenance_case_id);
    
    $sql = "SELECT 
                dt.id,
                dt.maintenance_case_id,
                dt.task_number,
                dt.task_type,
                dt.template_name,
                dt.task_description,
                dt.start_date,
                dt.end_date,
                dt.assigned_to as assignee_id,
                dt.status,
                dt.created_at,
                dt.updated_at,
                s.fullname as assignee_name
            FROM maintenance_tasks dt
            LEFT JOIN staffs s ON dt.assigned_to = s.id
            WHERE dt.maintenance_case_id = ?
            ORDER BY dt.created_at ASC";

    error_log("SQL: " . $sql);
    error_log("Parameter: " . $maintenance_case_id);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$maintenance_case_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Found " . count($tasks) . " tasks");
    if (count($tasks) > 0) {
        error_log("First task: " . json_encode($tasks[0]));
    }
    
    echo json_encode(['success' => true, 'data' => $tasks]);
    
} catch (Exception $e) {
    error_log("Error in get_maintenance_tasks.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi lấy danh sách tasks'
    ]);
}
?> 
