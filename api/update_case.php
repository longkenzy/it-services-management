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
header('Access-Control-Allow-Methods: POST, PUT');
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
    
    // Validate JSON input
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
        exit;
    }
    
    // Validate required fields
    if (empty($input['case_id'])) {
        echo json_encode(['success' => false, 'error' => 'Case ID là bắt buộc']);
        exit;
    }
    
    $case_id = $input['case_id'];
    $current_user_id = $_SESSION['user_id'];
    
    // Lấy thông tin case hiện tại
    $stmt = $pdo->prepare("SELECT * FROM internal_cases WHERE id = ?");
    $stmt->execute([$case_id]);
    $current_case = $stmt->fetch();
    
    if (!$current_case) {
        echo json_encode(['success' => false, 'error' => 'Case không tồn tại']);
        exit;
    }
    
    // Chuẩn bị dữ liệu cập nhật
    $updates = [];
    $params = [];
    
    // Các trường có thể cập nhật
    $updatable_fields = ['requester_id', 'handler_id', 'case_type', 'priority', 
                        'issue_title', 'issue_description', 'status', 'notes', 
                        'start_date', 'due_date'];
    
    foreach ($updatable_fields as $field) {
        if (isset($input[$field])) {
            $updates[] = "$field = ?";
            $params[] = $input[$field];
        }
    }
    
    // Validate date range if both dates are being updated
    $new_start_date = isset($input['start_date']) ? $input['start_date'] : $current_case['start_date'];
    $new_due_date = isset($input['due_date']) ? $input['due_date'] : $current_case['due_date'];
    
    if (!empty($new_start_date) && !empty($new_due_date)) {
        $start = new DateTime($new_start_date);
        $end = new DateTime($new_due_date);
        
        if ($end <= $start) {
            echo json_encode(['success' => false, 'error' => 'Ngày kết thúc phải lớn hơn ngày bắt đầu']);
            exit;
        }
    }
    
    // Kiểm tra nếu status thay đổi thành 'completed'
    if (isset($input['status']) && $input['status'] === 'completed' && $current_case['status'] !== 'completed') {
        $updates[] = "completed_at = NOW()";
    }
    
    // Kiểm tra nếu status thay đổi từ 'completed' sang trạng thái khác
    if (isset($input['status']) && $input['status'] !== 'completed' && $current_case['status'] === 'completed') {
        $updates[] = "completed_at = NULL";
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'error' => 'Không có dữ liệu để cập nhật']);
        exit;
    }
    
    // Thêm updated_at
    $updates[] = "updated_at = NOW()";
    $params[] = $case_id;
    
    // Cập nhật case
    $sql = "UPDATE internal_cases SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    // Ghi log
    $log_message = "Cập nhật case: " . $current_case['case_number'];
    $log_stmt = $pdo->prepare("
        INSERT INTO user_activity_logs (user_id, activity, details, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    $log_stmt->execute([$current_user_id, 'update_case', $log_message]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật case thành công'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?> 