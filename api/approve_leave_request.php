<?php
/**
 * API: Phê duyệt đơn nghỉ phép
 * Method: POST
 * Parameters: request_id, action (approve/reject), comment (optional)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/session.php';
require_once '../config/db.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

// Kiểm tra quyền (chỉ admin và hr mới được phê duyệt)
$current_user = getCurrentUser();
if (!in_array($current_user['role'], ['admin', 'hr'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền phê duyệt đơn nghỉ phép']);
    exit;
}

// Kiểm tra method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method không được hỗ trợ']);
    exit;
}

try {
    // Lấy dữ liệu từ request
    $request_id = $_POST['request_id'] ?? '';
    $action = $_POST['action'] ?? ''; // approve hoặc reject
    $comment = $_POST['comment'] ?? '';
    
    // Validate dữ liệu
    if (empty($request_id) || empty($action)) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin cần thiết']);
        exit;
    }
    
    if (!in_array($action, ['approve', 'reject'])) {
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
        exit;
    }
    
    // Lấy thông tin đơn nghỉ phép
    $stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE id = ?");
    $stmt->execute([$request_id]);
    $leave_request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$leave_request) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn nghỉ phép']);
        exit;
    }
    
    // Kiểm tra trạng thái hiện tại
    if ($leave_request['status'] !== 'Chờ phê duyệt') {
        echo json_encode(['success' => false, 'message' => 'Đơn nghỉ phép đã được xử lý']);
        exit;
    }
    
    // Cập nhật trạng thái đơn nghỉ phép
    $new_status = ($action === 'approve') ? 'Đã phê duyệt' : 'Từ chối';
    $approver_id = $current_user['id'];
    $approver_name = $current_user['fullname'];
    
    $update_sql = "UPDATE leave_requests SET 
                    status = ?, 
                    approved_by = ?, 
                    approved_at = NOW(), 
                    approval_comment = ?
                    WHERE id = ?";
    
    $stmt = $pdo->prepare($update_sql);
    $result = $stmt->execute([
        $new_status,
        $approver_id,
        $comment,
        $request_id
    ]);
    
    if ($result) {
        // Gửi thông báo cho người tạo đơn
        try {
            $notification_title = ($action === 'approve') ? 
                'Đơn nghỉ phép đã được phê duyệt' : 
                'Đơn nghỉ phép đã bị từ chối';
            
            $notification_message = ($action === 'approve') ?
                "Đơn nghỉ phép của bạn (Mã: {$leave_request['request_code']}) đã được phê duyệt bởi {$approver_name}." :
                "Đơn nghỉ phép của bạn (Mã: {$leave_request['request_code']}) đã bị từ chối bởi {$approver_name}.";
            
            if (!empty($comment)) {
                $notification_message .= " Lý do: {$comment}";
            }
            
            $notification_sql = "INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, ?, ?)";
            $notification_stmt = $pdo->prepare($notification_sql);
            $notification_stmt->execute([
                $leave_request['requester_id'],
                $notification_title,
                $notification_message,
                'leave_approval',
                $request_id
            ]);
        } catch (Exception $e) {
            // Bỏ qua lỗi notification
        }
        
        // Log hoạt động
        try {
            $log_sql = "INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())";
            $log_stmt = $pdo->prepare($log_sql);
            $log_stmt->execute([
                $current_user['id'],
                'approve_leave_request',
                "Phê duyệt đơn nghỉ phép: {$leave_request['request_code']} - {$new_status}"
            ]);
        } catch (Exception $e) {
            // Bỏ qua lỗi log
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Đã {$new_status} đơn nghỉ phép thành công",
            'data' => [
                'request_code' => $leave_request['request_code'],
                'status' => $new_status,
                'approved_by' => $approver_name
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật đơn nghỉ phép']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi phê duyệt đơn nghỉ phép: ' . $e->getMessage()
    ]);
}
?> 