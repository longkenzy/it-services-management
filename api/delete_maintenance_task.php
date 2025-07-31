<?php
header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../config/db.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $task_id = $input['id'] ?? null;

    if (!$task_id) {
        echo json_encode(['success' => false, 'message' => 'ID task không hợp lệ']);
        exit;
    }

    // Lấy thông tin task trước khi xóa để log
    $stmt = $pdo->prepare("SELECT task_code FROM maintenance_tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();

    if (!$task) {
        echo json_encode(['success' => false, 'message' => 'Task bảo trì không tồn tại']);
        exit;
    }

    // Xóa task
    $stmt = $pdo->prepare("DELETE FROM maintenance_tasks WHERE id = ?");
    $stmt->execute([$task_id]);

    // Log hoạt động
    $log_message = "Xóa task bảo trì: {$task['task_code']}";
    $log_sql = "INSERT INTO user_activity_logs (user_id, activity, details, ip_address) VALUES (?, ?, ?, ?)";
    $log_stmt = $pdo->prepare($log_sql);
    $log_stmt->execute([
        getCurrentUserId(),
        'DELETE_MAINTENANCE_TASK',
        $log_message,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Xóa task bảo trì thành công'
    ]);

} catch (Exception $e) {
    error_log("Error in delete_maintenance_task.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
?> 