<?php
/**
 * API tạo thông báo mới
 */

header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../config/db.php';

// Chỉ admin mới có thể tạo thông báo
if (!isLoggedIn() || !hasRole('admin')) {
    echo json_encode([
        'success' => false,
        'message' => 'Không có quyền truy cập'
    ]);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    // Validate input
    $required_fields = ['user_id', 'title', 'message', 'type'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            echo json_encode([
                'success' => false,
                'message' => "Thiếu thông tin: $field"
            ]);
            exit;
        }
    }
    
    $user_id = intval($input['user_id']);
    $title = trim($input['title']);
    $message = trim($input['message']);
    $type = $input['type'];
    $related_id = isset($input['related_id']) ? intval($input['related_id']) : null;
    
    // Validate type
    $allowed_types = ['leave_request', 'leave_approved', 'leave_rejected', 'system'];
    if (!in_array($type, $allowed_types)) {
        echo json_encode([
            'success' => false,
            'message' => 'Loại thông báo không hợp lệ'
        ]);
        exit;
    }
    
    // Kiểm tra user_id có tồn tại không
    $stmt = $pdo->prepare("SELECT id FROM staffs WHERE id = ?");
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Người dùng không tồn tại'
        ]);
        exit;
    }
    
    // Tạo thông báo
    $sql = "INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $title, $message, $type, $related_id]);
    
    $notification_id = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Thông báo đã được tạo thành công',
        'data' => [
            'id' => $notification_id,
            'user_id' => $user_id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'related_id' => $related_id,
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error creating notification: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi tạo thông báo'
    ]);
}
?> 