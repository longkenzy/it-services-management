<?php
/**
 * API: Phê duyệt/từ chối đơn nghỉ phép (2 cấp)
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

try {
    $current_user = getCurrentUser();
    
    // Kiểm tra quyền phê duyệt (admin hoặc hr)
    if (!in_array($current_user['role'], ['admin', 'hr'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Không có quyền phê duyệt đơn nghỉ phép']);
        exit;
    }
    
    // Lấy dữ liệu từ request
    $request_id = $_POST['request_id'] ?? null;
    $action = $_POST['action'] ?? null; // approve hoặc reject
    $comment = $_POST['comment'] ?? '';
    
    if (!$request_id || !$action) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin cần thiết']);
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
    
    // Xử lý theo role và trạng thái hiện tại
    $current_status = $leave_request['status'];
    $user_role = $current_user['role'];
    $result = false;
    
    if ($user_role === 'admin' || $user_role === 'hr_leader' || $user_role === 'sale_leader' || $user_role === 'it_leader') {
        // Admin và các Leader chỉ có thể phê duyệt đơn ở trạng thái "Chờ phê duyệt"
        if ($current_status !== 'Chờ phê duyệt') {
            echo json_encode(['success' => false, 'message' => 'Đơn nghỉ phép không ở trạng thái chờ phê duyệt']);
            exit;
        }
        
        if ($action === 'approve') {
            $new_status = 'Admin đã phê duyệt';
            $approval_message = 'Đơn nghỉ phép đã được admin/leader phê duyệt (chờ HR phê duyệt cuối)';
            
            // Cập nhật thông tin admin/leader
            $stmt = $pdo->prepare("UPDATE leave_requests SET 
                status = ?, 
                admin_approved_by = ?, 
                admin_approved_at = NOW(), 
                admin_approval_comment = ? 
                WHERE id = ?");
            $result = $stmt->execute([$new_status, $current_user['id'], $comment, $request_id]);
            
            if ($result) {
                // Gửi thông báo cho HR
                try {
                    $hr_ids = [];
                    $stmt = $pdo->prepare("SELECT id FROM staffs WHERE role = 'hr'");
                    $stmt->execute();
                    while ($hr = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $hr_ids[] = $hr['id'];
                    }
                    
                    foreach ($hr_ids as $hr_id) {
                        $notification_sql = "INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, ?, ?)";
                        $notification_stmt = $pdo->prepare($notification_sql);
                        $notification_stmt->execute([
                            $hr_id,
                            'Đơn nghỉ phép cần phê duyệt (Cấp 2)',
                            "Đơn nghỉ phép {$leave_request['request_code']} đã được admin/leader phê duyệt, cần HR phê duyệt cuối (Cấp 2)",
                            'leave_request',
                            $request_id
                        ]);
                    }
                } catch (Exception $e) {
                    // Bỏ qua lỗi notification
                }
            }
            
        } elseif ($action === 'reject') {
            $new_status = 'Từ chối bởi Admin';
            $approval_message = 'Đơn nghỉ phép đã bị admin/leader từ chối';
            
            // Cập nhật thông tin admin/leader
            $stmt = $pdo->prepare("UPDATE leave_requests SET 
                status = ?, 
                admin_approved_by = ?, 
                admin_approved_at = NOW(), 
                admin_approval_comment = ? 
                WHERE id = ?");
            $result = $stmt->execute([$new_status, $current_user['id'], $comment, $request_id]);
        }
        
    } elseif ($user_role === 'hr') {
        // HR chỉ có thể phê duyệt đơn ở trạng thái "Admin đã phê duyệt"
        if ($current_status !== 'Admin đã phê duyệt') {
            echo json_encode(['success' => false, 'message' => 'Đơn nghỉ phép chưa được admin phê duyệt hoặc không ở trạng thái phù hợp']);
            exit;
        }
        
        if ($action === 'approve') {
            $new_status = 'HR đã phê duyệt';
            $approval_message = 'Đơn nghỉ phép đã được HR phê duyệt cuối cùng';
            
            // Cập nhật thông tin HR
            $stmt = $pdo->prepare("UPDATE leave_requests SET 
                status = ?, 
                hr_approved_by = ?, 
                hr_approved_at = NOW(), 
                hr_approval_comment = ? 
                WHERE id = ?");
            $result = $stmt->execute([$new_status, $current_user['id'], $comment, $request_id]);
            
        } elseif ($action === 'reject') {
            $new_status = 'Từ chối bởi HR';
            $approval_message = 'Đơn nghỉ phép đã bị HR từ chối';
            
            // Cập nhật thông tin HR
            $stmt = $pdo->prepare("UPDATE leave_requests SET 
                status = ?, 
                hr_approved_by = ?, 
                hr_approved_at = NOW(), 
                hr_approval_comment = ? 
                WHERE id = ?");
            $result = $stmt->execute([$new_status, $current_user['id'], $comment, $request_id]);
        }
    }
    
    if ($result) {
        // Gửi thông báo cho người tạo đơn
        try {
            $notification_sql = "INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, ?, ?)";
            $notification_stmt = $pdo->prepare($notification_sql);
            $notification_stmt->execute([
                $leave_request['requester_id'],
                'Kết quả phê duyệt đơn nghỉ phép',
                "$approval_message. Mã đơn: {$leave_request['request_code']}. " . ($comment ? "Ghi chú: $comment" : ''),
                'leave_request',
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
                $action === 'approve' ? 'approve_leave_request' : 'reject_leave_request',
                "{$action} đơn nghỉ phép: {$leave_request['request_code']} (Role: {$user_role})"
            ]);
        } catch (Exception $e) {
            // Bỏ qua lỗi log
        }
        
        echo json_encode([
            'success' => true,
            'message' => $approval_message
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật đơn nghỉ phép']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
?> 