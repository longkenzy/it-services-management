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
    // Debug: Log input data
    error_log("=== CREATE MAINTENANCE TASK DEBUG ===");
    error_log("Raw input: " . print_r($input, true));
    
    // Validate required fields
    $data = [
        'task_number' => $input['task_number'] ?? '',
        'task_description' => $input['task_description'] ?? '',
        'task_type' => $input['task_type'] ?? '',
        'task_template' => $input['task_template'] ?? '',
        'assigned_to' => $input['assigned_to'] ?? '',
        'start_date' => $input['start_date'] ?? '',
        'end_date' => $input['end_date'] ?? '',
        'status' => $input['status'] ?? 'Tiếp nhận',
        'notes' => $input['notes'] ?? '',
        'maintenance_case_id' => $input['maintenance_case_id'] ?? '',
        'maintenance_request_id' => $input['maintenance_request_id'] ?? ''
    ];
    
    // Debug: Log processed data
    error_log("Processed data: " . print_r($data, true));
    error_log("task_description value: '" . $data['task_description'] . "'");
    error_log("task_description empty check: " . (empty($data['task_description']) ? 'TRUE' : 'FALSE'));

    // Validate required fields
    error_log("=== VALIDATION DEBUG ===");
    error_log("Checking task_description: '" . $data['task_description'] . "'");
    error_log("Empty check result: " . (empty($data['task_description']) ? 'TRUE' : 'FALSE'));
    
    if (empty($data['task_description'])) {
        error_log("VALIDATION FAILED: task_description is empty");
        echo json_encode(['success' => false, 'message' => 'Tên task là bắt buộc']);
        exit;
    }
    
    error_log("VALIDATION PASSED: task_description is not empty");

    if (empty($data['task_type'])) {
        echo json_encode(['success' => false, 'message' => 'Loại task là bắt buộc']);
        exit;
    }

    if (empty($data['maintenance_case_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID case là bắt buộc']);
        exit;
    }

    // Check if task number already exists
    if (!empty($data['task_number'])) {
        $stmt = $pdo->prepare("SELECT id FROM maintenance_tasks WHERE task_number = ?");
        $stmt->execute([$data['task_number']]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Số task đã tồn tại']);
            exit;
        }
    }

    // Validate case exists
    if (!empty($data['maintenance_case_id'])) {
        $stmt = $pdo->prepare("SELECT id FROM maintenance_cases WHERE id = ?");
        $stmt->execute([$data['maintenance_case_id']]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Case không tồn tại']);
            exit;
        }
    }

    // Validate request exists
    if (!empty($data['maintenance_request_id'])) {
        $stmt = $pdo->prepare("SELECT id FROM maintenance_requests WHERE id = ?");
        $stmt->execute([$data['maintenance_request_id']]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Yêu cầu bảo trì không tồn tại']);
            exit;
        }
    }

    // Insert task - Updated SQL to match new structure
    $sql = "INSERT INTO maintenance_tasks (
        task_number, task_description, task_type, template_name, assigned_to, 
        start_date, end_date, status, maintenance_case_id, maintenance_request_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    error_log("=== INSERT DEBUG ===");
    error_log("SQL: " . $sql);
    error_log("Data to insert: " . print_r([
        $data['task_number'], // task_number
        $data['task_description'], // task_description
        $data['task_type'],
        $data['task_template'], // template_name
        $data['assigned_to'] ?: null, // Convert empty string to null for int column
        $data['start_date'] ?: null,
        $data['end_date'] ?: null,
        $data['status'],
        $data['maintenance_case_id'] ?: null, // Convert empty string to null for int column
        $data['maintenance_request_id'] ?: null, // Convert empty string to null for int column
    ], true));

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['task_number'], // task_number
        $data['task_description'], // task_description
        $data['task_type'],
        $data['task_template'], // template_name
        $data['assigned_to'] ?: null, // Convert empty string to null for int column
        $data['start_date'] ?: null,
        $data['end_date'] ?: null,
        $data['status'],
        $data['maintenance_case_id'] ?: null, // Convert empty string to null for int column
        $data['maintenance_request_id'] ?: null, // Convert empty string to null for int column
    ]);
    
    error_log("INSERT SUCCESSFUL");

    $taskId = $pdo->lastInsertId();
    error_log("Task created with ID: " . $taskId);
    error_log("Task data: " . json_encode([
        'task_number' => $data['task_number'],
        'task_description' => $data['task_description'],
        'task_type' => $data['task_type'],
        'template_name' => $data['task_template'],
        'maintenance_case_id' => $data['maintenance_case_id'],
        'maintenance_request_id' => $data['maintenance_request_id']
    ]));

    // Log activity
    $activitySql = "INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, NOW())";
    $activityStmt = $pdo->prepare($activitySql);
    $activityStmt->execute([
        $_SESSION['user_id'],
        'CREATE_MAINTENANCE_TASK',
        json_encode([
            'task_id' => $taskId,
            'task_number' => $data['task_number'],
            'task_description' => $data['task_description'],
            'case_id' => $data['maintenance_case_id']
        ]),
        $_SERVER['REMOTE_ADDR']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Tạo task bảo trì thành công',
        'task_id' => $taskId
    ]);

} catch (PDOException $e) {
    error_log("=== PDO EXCEPTION ===");
    error_log("Database error in create_maintenance_task.php: " . $e->getMessage());
    error_log("Error code: " . $e->getCode());
    error_log("Error file: " . $e->getFile());
    error_log("Error line: " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("=== GENERAL EXCEPTION ===");
    error_log("Error in create_maintenance_task.php: " . $e->getMessage());
    error_log("Error code: " . $e->getCode());
    error_log("Error file: " . $e->getFile());
    error_log("Error line: " . $e->getLine());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
?>
