<?php
/**
 * API tạo thông báo cho internal case
 * Chỉ gửi thông báo cho người xử lý (handler) của case
 */

header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../config/db.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized - Please login first'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    // Validate input
    $required_fields = ['case_id', 'case_number', 'handler_id', 'issue_title'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            echo json_encode([
                'success' => false,
                'message' => "Thiếu thông tin: $field"
            ]);
            exit;
        }
    }
    
    $case_id = intval($input['case_id']);
    $case_number = trim($input['case_number']);
    $handler_id = intval($input['handler_id']);
    $issue_title = trim($input['issue_title']);
    $requester_name = isset($input['requester_name']) ? trim($input['requester_name']) : '';
    
    // Kiểm tra handler_id có tồn tại không
    $stmt = $pdo->prepare("SELECT id, fullname FROM staffs WHERE id = ?");
    $stmt->execute([$handler_id]);
    $handler = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$handler) {
        echo json_encode([
            'success' => false,
            'message' => 'Người xử lý không tồn tại'
        ]);
        exit;
    }
    
    // Tạo nội dung thông báo
    $title = "Case nội bộ mới được giao";
    $message = "Bạn có case nội bộ mới cần xử lý: $case_number - $issue_title";
    
    if (!empty($requester_name)) {
        $message .= " (Yêu cầu bởi: $requester_name)";
    }
    
    // Kiểm tra xem bảng notifications có loại 'internal_case' chưa
    $check_type_stmt = $pdo->prepare("
        SELECT COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'notifications' 
        AND COLUMN_NAME = 'type'
    ");
    $check_type_stmt->execute();
    $type_info = $check_type_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Nếu chưa có loại 'internal_case', thêm vào
    if ($type_info && !str_contains($type_info['COLUMN_TYPE'], 'internal_case')) {
        $alter_sql = "ALTER TABLE notifications MODIFY COLUMN type ENUM('leave_request', 'leave_approved', 'leave_rejected', 'internal_case', 'system') DEFAULT 'system'";
        $pdo->exec($alter_sql);
    }
    
    // Tạo thông báo
    $sql = "INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, 'internal_case', ?)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$handler_id, $title, $message, $case_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Thông báo đã được gửi thành công',
            'notification_id' => $pdo->lastInsertId(),
            'handler_name' => $handler['fullname']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể tạo thông báo'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error creating internal case notification: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
}
?>
