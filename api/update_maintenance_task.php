<?php
require_once '../config/db.php';
require_once '../includes/session.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

try {
    // Validate required fields
    $data = [
        'id' => $input['id'] ?? '',
        'task_description' => $input['task_description'] ?? '',
        'task_type' => $input['task_type'] ?? '',
        'template_name' => $input['task_template'] ?? '',
        'assigned_to' => $input['assigned_to'] ?? '',
        'start_date' => $input['start_date'] ?? '',
        'end_date' => $input['end_date'] ?? '',
        'status' => $input['status'] ?? ''
    ];

    // Validate required fields
    if (empty($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID task là bắt buộc']);
        exit;
    }

    if (empty($data['task_description'])) {
        echo json_encode(['success' => false, 'message' => 'Tên task là bắt buộc']);
        exit;
    }

    if (empty($data['task_type'])) {
        echo json_encode(['success' => false, 'message' => 'Loại task là bắt buộc']);
        exit;
    }

    // Check if task exists
    $stmt = $pdo->prepare("SELECT id FROM maintenance_tasks WHERE id = ?");
    $stmt->execute([$data['id']]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Task không tồn tại']);
        exit;
    }

    // Update task
    $sql = "UPDATE maintenance_tasks SET 
        task_description = ?, task_type = ?, template_name = ?, assigned_to = ?, 
        start_date = ?, end_date = ?, status = ?
        WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['task_description'],
        $data['task_type'],
        $data['template_name'],
        $data['assigned_to'] ?: null, // Convert empty string to null for int column
        $data['start_date'] ?: null,
        $data['end_date'] ?: null,
        $data['status'],
        $data['id']
    ]);

    // Log activity
    $activitySql = "INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())";
    $activityStmt = $pdo->prepare($activitySql);
    $activityStmt->execute([
        $_SESSION['user_id'],
        'UPDATE_MAINTENANCE_TASK',
        json_encode([
            'task_id' => $data['id'],
            'task_name' => $data['task_description'],
            'status' => $data['status']
        ]),
        $_SERVER['REMOTE_ADDR']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật task bảo trì thành công'
    ]);

} catch (PDOException $e) {
    error_log("Database error in update_maintenance_task.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu']);
} catch (Exception $e) {
    error_log("Error in update_maintenance_task.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
?>
