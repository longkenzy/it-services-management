<?php
/**
 * API: Lấy chi tiết đơn nghỉ phép
 * Method: GET
 * Parameters: id
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
    $request_id = $_GET['id'] ?? '';
    
    if (empty($request_id)) {
        echo json_encode(['success' => false, 'message' => 'ID đơn nghỉ phép không được cung cấp']);
        exit;
    }
    
    // Lấy thông tin đơn nghỉ phép
    $sql = "SELECT 
                lr.*,
                s.fullname as requester_name,
                s.avatar as requester_avatar,
                s.email as requester_email,
                h.fullname as handover_name,
                h.position as handover_position,
                a.fullname as approver_name,
                a.position as approver_position
            FROM leave_requests lr
            LEFT JOIN staffs s ON lr.requester_id = s.id
            LEFT JOIN staffs h ON lr.handover_to = h.id
            LEFT JOIN staffs a ON lr.approved_by = a.id
            WHERE lr.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn nghỉ phép']);
        exit;
    }
    
    // Kiểm tra quyền xem (chỉ người tạo hoặc admin/leader mới được xem)
    if ($request['requester_id'] != $current_user['id'] && !hasRole(['admin', 'leader'])) {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xem đơn nghỉ phép này']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $request
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi tải thông tin đơn nghỉ phép: ' . $e->getMessage()
    ]);
}
?> 