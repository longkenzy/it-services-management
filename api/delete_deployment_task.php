<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $pdo = getConnection();
    
    // Lấy dữ liệu từ request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    $task_id = $input['id'] ?? null;
    
    // Validate dữ liệu
    if (!$task_id) {
        throw new Exception('Thiếu ID task');
    }
    
    // Kiểm tra task có tồn tại không
    $stmt = $pdo->prepare("SELECT id, task_number FROM deployment_tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();
    
    if (!$task) {
        throw new Exception('Task không tồn tại');
    }
    
    // Xóa task
    $stmt = $pdo->prepare("DELETE FROM deployment_tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Xóa task triển khai thành công',
            'deleted_task_number' => $task['task_number']
        ]);
    } else {
        throw new Exception('Không thể xóa task');
    }
    
} catch (Exception $e) {
    error_log("Error in delete_deployment_task.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi xóa task triển khai: ' . $e->getMessage()
    ]);
}
?> 