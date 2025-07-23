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
    
    $deployment_case_id = $input['deployment_case_id'] ?? null;
    $task_type = $input['task_type'] ?? null;
    $template_id = $input['template_id'] ?? null;
    $task_description = $input['task_description'] ?? null;
    $start_date = $input['start_date'] ?? null;
    $end_date = $input['end_date'] ?? null;
    $assignee_id = $input['assignee_id'] ?? null;
    
    // Validate dữ liệu
    if (!$deployment_case_id || !$task_type || !$task_description || !$start_date || !$end_date) {
        throw new Exception('Thiếu thông tin bắt buộc');
    }
    
    // Tạo task number: TTK + YYMM + sequence
    $current_year = date('y'); // 2 số cuối của năm
    $current_month = date('m'); // 2 số của tháng
    $prefix = "TTK{$current_year}{$current_month}";
    
    // Tìm số thứ tự tiếp theo
    $stmt = $pdo->prepare("
        SELECT MAX(CAST(SUBSTRING(task_number, 8, 3) AS UNSIGNED)) as max_seq
        FROM deployment_tasks
        WHERE task_number LIKE ?
    ");
    $stmt->execute([$prefix . '%']);
    $result = $stmt->fetch();
    $max_seq = $result['max_seq'] ?? 0;
    $sequence = $max_seq + 1;
    
    $task_number = $prefix . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    
    // Insert task mới
    $sql = "INSERT INTO deployment_tasks (
                task_number, 
                deployment_case_id, 
                task_type, 
                template_id, 
                task_description, 
                start_date, 
                end_date, 
                assignee_id, 
                status, 
                progress_percentage
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 0)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $task_number,
        $deployment_case_id,
        $task_type,
        $template_id,
        $task_description,
        $start_date,
        $end_date,
        $assignee_id
    ]);
    
    $task_id = $pdo->lastInsertId();
    
    // Lấy thông tin task vừa tạo
    $sql = "SELECT 
                dt.*,
                s.fullname as assignee_name,
                dtt.template_name
            FROM deployment_tasks dt
            LEFT JOIN staffs s ON dt.assignee_id = s.id
            LEFT JOIN deployment_task_templates dtt ON dt.template_id = dtt.id
            WHERE dt.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$task_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Tạo task triển khai thành công',
        'data' => $task
    ]);
    
} catch (Exception $e) {
    error_log("Error in create_deployment_task.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi tạo task triển khai: ' . $e->getMessage()
    ]);
}
?> 