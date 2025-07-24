<?php
// Chuẩn hóa lỗi PHP: luôn trả về JSON, ghi log ra file
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

set_exception_handler(function($e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
});
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => "$errstr in $errfile:$errline"]);
    exit;
});
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $error['message']]);
        exit;
    }
});

require_once '../config/db.php';
require_once '../includes/session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$deployment_case_id = isset($_GET['deployment_case_id']) ? intval($_GET['deployment_case_id']) : 0;
if ($deployment_case_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid deployment case ID']);
    exit;
}

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
            s.fullname as assignee_name
        FROM deployment_tasks dt
        LEFT JOIN staffs s ON dt.assignee_id = s.id
        WHERE dt.deployment_case_id = ?
        ORDER BY dt.created_at ASC";

$pdo = getConnection();
$stmt = $pdo->prepare($sql);
$stmt->execute([$deployment_case_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success' => true, 'data' => $tasks]); 