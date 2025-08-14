<?php
// Set headers first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Bảo vệ file khỏi truy cập trực tiếp (chỉ cho phép từ POST requests)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once '../includes/session.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Please login first']);
    exit;
}

// Debug: Check if session is working
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Session not found']);
    exit;
}

require_once '../config/db.php';

// Check if database connection is available
if (!isset($pdo)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection not available']);
    exit;
}

try {
    $raw_input = file_get_contents('php://input');
    
    if (empty($raw_input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No input data received']);
        exit;
    }
    
    $input = json_decode($raw_input, true);
    
    // Validate JSON input
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON data: ' . json_last_error_msg()]);
        exit;
    }
    
    // Validate required fields
    if (empty($input['case_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Case ID là bắt buộc']);
        exit;
    }
    
    $case_id = $input['case_id'];
    $current_user_id = getCurrentUserId();
    
    // Lấy thông tin case hiện tại
    $stmt = $pdo->prepare("SELECT * FROM internal_cases WHERE id = ?");
    $stmt->execute([$case_id]);
    $current_case = $stmt->fetch();
    
    if (!$current_case) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Case không tồn tại']);
        exit;
    }
    
    // Chuẩn bị dữ liệu cập nhật theo phân quyền
    $updates = [];
    $params = [];
    
    // Kiểm tra quyền của user
    $is_admin = isAdmin();
    $can_edit = canEditInternalCase();
    
    if (!$can_edit) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Bạn không có quyền chỉnh sửa case này']);
        exit;
    }
    
    // Các trường có thể cập nhật theo phân quyền
    if ($is_admin) {
        // Admin có thể cập nhật tất cả trường
        $updatable_fields = ['requester_id', 'handler_id', 'case_type', 'priority', 
                            'issue_title', 'issue_description', 'status', 'notes', 
                            'start_date', 'due_date'];
    } else {
        // IT staff chỉ có thể cập nhật status, due_date, notes
        $updatable_fields = ['status', 'due_date', 'notes'];
    }
    
    foreach ($updatable_fields as $field) {
        if (isset($input[$field])) {
            // Validate priority field
            if ($field === 'priority') {
                $valid_priorities = ['onsite', 'offsite', 'remote'];
                if (!in_array($input[$field], $valid_priorities)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Giá trị priority không hợp lệ']);
                    exit;
                }
            }
            
            // Validate status field
            if ($field === 'status') {
                $valid_statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
                if (!in_array($input[$field], $valid_statuses)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Giá trị status không hợp lệ']);
                    exit;
                }
            }
            
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
            http_response_code(400);
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
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Không có dữ liệu để cập nhật']);
        exit;
    }
    
    // Thêm updated_at
    $updates[] = "updated_at = NOW()";
    $params[] = $case_id;
    
    // Cập nhật case
    $sql = "UPDATE internal_cases SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    
    if (!$stmt->execute($params)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Lỗi khi cập nhật case']);
        exit;
    }
    
    // Ghi log
    try {
        $log_message = "Cập nhật case: " . $current_case['case_number'];
        $log_stmt = $pdo->prepare("
            INSERT INTO user_activity_logs (user_id, activity, details, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $log_stmt->execute([
            $current_user_id, 
            'update_case', 
            $log_message,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        // Log lỗi nhưng không làm crash API
        error_log("Failed to log activity: " . $e->getMessage());
    }
    
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