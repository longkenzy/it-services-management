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
    
    // Lấy thông tin đơn nghỉ phép với 2-level approval
    $sql = "SELECT 
                lr.*,
                s.fullname as requester_name,
                s.avatar as requester_avatar,
                s.position as requester_position,
                s.department as requester_department,
                s.office as requester_office,
                h.fullname as handover_name,
                h.position as handover_position,
                h.department as handover_department,
                admin_approver.fullname as admin_approver_name,
                admin_approver.position as admin_approver_position,
                hr_approver.fullname as hr_approver_name,
                hr_approver.position as hr_approver_position
            FROM leave_requests lr
            LEFT JOIN staffs s ON lr.requester_id = s.id
            LEFT JOIN staffs h ON lr.handover_to = h.id
            LEFT JOIN staffs admin_approver ON lr.admin_approved_by = admin_approver.id
            LEFT JOIN staffs hr_approver ON lr.hr_approved_by = hr_approver.id
            WHERE lr.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn nghỉ phép']);
        exit;
    }
    
    // Cho phép tất cả user xem tất cả đơn nghỉ phép
    // (đã bỏ điều kiện kiểm tra quyền)
    
    // Format lại dữ liệu để hiển thị
    $request['formatted_start_date'] = date('d/m/Y', strtotime($request['start_date']));
    $request['formatted_end_date'] = date('d/m/Y', strtotime($request['end_date']));
    $request['formatted_return_date'] = date('d/m/Y', strtotime($request['return_date']));
    $request['formatted_created_at'] = date('d/m/Y H:i', strtotime($request['created_at']));
    
    if ($request['admin_approved_at']) {
        $request['formatted_admin_approved_at'] = date('d/m/Y H:i', strtotime($request['admin_approved_at']));
    }
    
    if ($request['hr_approved_at']) {
        $request['formatted_hr_approved_at'] = date('d/m/Y H:i', strtotime($request['hr_approved_at']));
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