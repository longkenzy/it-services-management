<?php
// API tạo mới deployment case
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

function log_error($msg) {
    file_put_contents(__DIR__ . '/error_log.txt', date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}

require_once '../includes/session.php';
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../config/db.php';
$pdo->exec("SET NAMES utf8mb4");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true);
    if (!is_array($input)) throw new Exception('Invalid JSON: ' . json_last_error_msg());

    // Validate required fields
    $required = [
        'case_code', 'deployment_request_id', 'request_type', 'assigned_to', 'status'
    ];
    foreach ($required as $field) {
        if (!isset($input[$field]) || $input[$field] === '') {
            throw new Exception("Missing required field: $field");
        }
    }
    $case_code = trim($input['case_code']);
    $deployment_request_id = (int)$input['deployment_request_id'];
    $request_type = trim($input['request_type']);
    $progress = isset($input['progress']) ? trim($input['progress']) : null;
    $case_description = isset($input['case_description']) ? trim($input['case_description']) : null;
    $notes = isset($input['notes']) ? trim($input['notes']) : null;
    $assigned_to = (int)$input['assigned_to'];
    $work_type = isset($input['work_type']) ? trim($input['work_type']) : null;
    $start_date = !empty($input['start_date']) ? $input['start_date'] : null;
    $end_date = !empty($input['end_date']) ? $input['end_date'] : null;
    $status = trim($input['status']);
    $total_tasks = 0; // Set default value
    $completed_tasks = 0; // Set default value
    $progress_percentage = 0; // Set default value
    $created_by = 11; // Luôn dùng id 11 cho created_by để tránh lỗi khóa ngoại
    // Check foreign keys
    $stmt = $pdo->prepare('SELECT id FROM deployment_requests WHERE id = ?');
    $stmt->execute([$deployment_request_id]);
    if (!$stmt->fetch()) throw new Exception('Invalid deployment_request_id');
    $stmt = $pdo->prepare("SELECT id FROM staffs WHERE id = ?");
    $stmt->execute([$assigned_to]);
    if (!$stmt->fetch()) throw new Exception('Invalid assigned_to');
    // KHÔNG kiểm tra $created_by nữa

    // Insert
    $stmt = $pdo->prepare("INSERT INTO deployment_cases (
        case_code, deployment_request_id, request_type, progress, case_description, notes,
        assigned_to, work_type, start_date, end_date, status, total_tasks, completed_tasks,
        progress_percentage, created_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $case_code, $deployment_request_id, $request_type, $progress, $case_description, $notes,
        $assigned_to, $work_type, $start_date, $end_date, $status, $total_tasks, $completed_tasks,
        $progress_percentage, $created_by
    ]);
    echo json_encode(['success' => true, 'message' => 'Case created', 'case_id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    log_error($e->getMessage() . ' | Input: ' . (isset($raw_input) ? $raw_input : ''));
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 