<?php
/**
 * API: Kiểm tra quyền phê duyệt đơn nghỉ phép
 * Method: GET
 * Response: JSON với thông tin quyền
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/session.php';
require_once '../config/db.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

try {
    $current_user = getCurrentUser();
    
    // Kiểm tra quyền phê duyệt (admin hoặc hr)
    $can_approve = in_array($current_user['role'], ['admin', 'hr']);
    
    echo json_encode([
        'success' => true,
        'can_approve' => $can_approve,
        'user_role' => $current_user['role']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi kiểm tra quyền: ' . $e->getMessage()
    ]);
}
?> 