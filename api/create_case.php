<?php
// Bảo vệ file khỏi truy cập trực tiếp (chỉ cho phép từ cùng domain)
if (!isset($_SERVER['HTTP_REFERER']) || !str_contains($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST'])) {
    // Cho phép truy cập từ AJAX requests
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
        http_response_code(403);
        exit('Access denied.');
    }
}
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Please login first']);
    exit;
}

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
        exit;
    }
    
    // Validate required fields
    $required_fields = ['requester_id', 'handler_id', 'case_type', 'issue_title', 'issue_description', 'status'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            echo json_encode(['success' => false, 'error' => "Trường '$field' là bắt buộc"]);
            exit;
        }
    }
    
    // Tạo số case tự động
    $case_number = generateCaseNumber($pdo);
    
    // Chuẩn bị dữ liệu
    $current_user_id = $_SESSION['user_id'];
    $requester_id = $input['requester_id'];
    $handler_id = $input['handler_id'];
    $case_type = $input['case_type'];
    $priority = $input['priority'] ?? 'onsite';
    $issue_title = $input['issue_title'];
    $issue_description = $input['issue_description'];
    $status = $input['status'];
    $notes = $input['notes'] ?? '';
    $start_date = !empty($input['start_date']) ? $input['start_date'] : null;
    $due_date = !empty($input['due_date']) ? $input['due_date'] : null;
    $transferred_by = 'Trần Nguyễn Anh Khoa';
    
    // Validate date range
    if (!empty($start_date) && !empty($due_date)) {
        $start = new DateTime($start_date);
        $end = new DateTime($due_date);
        
        if ($end <= $start) {
            echo json_encode(['success' => false, 'error' => 'Ngày kết thúc phải lớn hơn ngày bắt đầu']);
            exit;
        }
    }
    
    // Tự động set completed_at nếu status là completed
    $completed_at = null;
    if ($status === 'completed') {
        $completed_at = date('Y-m-d H:i:s');
    }
    
    // Insert case vào database
    $stmt = $pdo->prepare("
        INSERT INTO internal_cases (
            case_number, requester_id, handler_id, transferred_by, case_type, priority,
            issue_title, issue_description, status, notes, start_date, due_date, completed_at, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $result = $stmt->execute([
        $case_number,
        $requester_id,
        $handler_id,
        $transferred_by,
        $case_type,
        $priority,
        $issue_title,
        $issue_description,
        $status,
        $notes,
        $start_date,
        $due_date,
        $completed_at
    ]);
    
    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Database insert failed']);
        exit;
    }
    
    $case_id = $pdo->lastInsertId();
    
    // Ghi log
    try {
        $log_message = "Tạo case nội bộ mới: $case_number";
        $log_stmt = $pdo->prepare("
            INSERT INTO user_activity_logs (user_id, activity, details, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $log_stmt->execute([$current_user_id, 'create_case', $log_message]);
    } catch (Exception $e) {
        // Ignore logging errors - don't fail the case creation
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Tạo case thành công',
        'case_id' => $case_id,
        'case_number' => $case_number
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

function generateCaseNumber($pdo) {
    $year = date('y'); // 2 số cuối của năm (25)
    $month = date('m'); // tháng với 2 chữ số (07)
    
    // Lấy tổng số case hiện có trong database
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM internal_cases");
        $stmt->execute();
        $count = $stmt->fetch()['count'] + 1;
    } catch (Exception $e) {
        // Nếu lỗi, sử dụng số sequence mặc định
        $count = 1;
    }
    
    // Format số thứ tự thành 3 chữ số (001, 002, ...)
    $sequence = str_pad($count, 3, '0', STR_PAD_LEFT);
    
    return "CNB.{$year}{$month}{$sequence}";
}
?> 