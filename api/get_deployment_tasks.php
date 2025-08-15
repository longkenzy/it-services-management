<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../includes/session.php';

if (null === getCurrentUserId()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$deployment_case_id = isset($_GET['deployment_case_id']) ? intval($_GET['deployment_case_id']) : 0;
if ($deployment_case_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid deployment case ID']);
    exit;
}

try {
    $sql = "SELECT 
                dt.id,
                dt.deployment_case_id,
                dt.task_number,
                dt.task_type,
                dt.template_name,
                dt.task_description,
                dt.start_date,
                dt.end_date,
                dt.assignee_id,
                dt.status,
                dt.created_at,
                dt.updated_at,
                dt.created_by,
                s.fullname as assignee_name
            FROM deployment_tasks dt
            LEFT JOIN staffs s ON dt.assignee_id = s.id
            WHERE dt.deployment_case_id = ?
            ORDER BY dt.created_at ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$deployment_case_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $tasks]);
    
} catch (Exception $e) {
    error_log("Error in get_deployment_tasks.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi lấy danh sách tasks'
    ]);
}
?> 