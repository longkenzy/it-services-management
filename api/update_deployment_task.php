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
    $task_type = $input['task_type'] ?? null;
    $template_name = $input['template_name'] ?? null;
    $task_description = $input['task_description'] ?? null;
    $start_date = $input['start_date'] ?? null;
    $end_date = $input['end_date'] ?? null;
    $assignee_id = $input['assignee_id'] ?? null;
    $status = $input['status'] ?? null;
    
    // Validate dữ liệu
    if (!$task_id) {
        throw new Exception('Thiếu ID task');
    }
    
    if (!$task_type || !$task_description || !$start_date || !$end_date) {
        throw new Exception('Thiếu thông tin bắt buộc');
    }
    
    // Kiểm tra task có tồn tại không
    $stmt = $pdo->prepare("SELECT id, task_number FROM deployment_tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();
    
    if (!$task) {
        throw new Exception('Task không tồn tại');
    }
    
    // Cập nhật task
    $sql = "UPDATE deployment_tasks SET 
                task_type = ?,
                template_name = ?,
                task_description = ?,
                start_date = ?,
                end_date = ?,
                assignee_id = ?,
                status = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $task_type,
        $template_name,
        $task_description,
        $start_date,
        $end_date,
        $assignee_id,
        $status,
        $task_id
    ]);
    
    if ($stmt->rowCount() > 0) {
        // Lấy thông tin task đã cập nhật
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
        $updated_task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật task triển khai thành công',
            'data' => $updated_task
        ]);
    } else {
        throw new Exception('Không thể cập nhật task');
    }
    
} catch (Exception $e) {
    error_log("Error in update_deployment_task.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi cập nhật task triển khai: ' . $e->getMessage()
    ]);
}
?> 