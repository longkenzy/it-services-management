<?php
require_once '../config/db.php';
require_once '../includes/session.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get task ID from query parameter
$taskId = $_GET['id'] ?? '';

if (empty($taskId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Task ID is required']);
    exit;
}

try {
    // Get task details with related information
    $sql = "SELECT 
        mt.*,
        s.fullname as assigned_to_name,
        mc.case_code,
        mr.request_code
        FROM maintenance_tasks mt
        LEFT JOIN staffs s ON mt.assigned_to = s.id
        LEFT JOIN maintenance_cases mc ON mt.maintenance_case_id = mc.id
        LEFT JOIN maintenance_requests mr ON mt.maintenance_request_id = mr.id
        WHERE mt.id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$taskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        echo json_encode(['success' => false, 'message' => 'Task không tồn tại']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'data' => $task
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_maintenance_task_details.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu']);
} catch (Exception $e) {
    error_log("Error in get_maintenance_task_details.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
?>
