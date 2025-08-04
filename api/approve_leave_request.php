<?php
/**
 * API phê duyệt/từ chối đơn nghỉ phép
 */

header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../config/db.php';

// Chỉ admin mới có thể phê duyệt
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
    $required_fields = ['request_id', 'action'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            echo json_encode([
                'success' => false,
                'message' => "Thiếu thông tin: $field"
            ]);
            exit;
        }
    }
    
    $request_id = intval($input['request_id']);
    $action = $input['action']; // 'approve' hoặc 'reject'
    $notes = isset($input['notes']) ? trim($input['notes']) : '';
    $admin_id = $_SESSION[SESSION_USER_ID];
    
    // Validate action
    if (!in_array($action, ['approve', 'reject'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Hành động không hợp lệ'
        ]);
        exit;
    }
    
    // Kiểm tra đơn nghỉ phép có tồn tại và đang chờ phê duyệt không
    $stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE id = ? AND status = 'Chờ phê duyệt'");
    $stmt->execute([$request_id]);
    $leave_request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$leave_request) {
        echo json_encode([
            'success' => false,
            'message' => 'Đơn nghỉ phép không tồn tại hoặc đã được xử lý'
        ]);
        exit;
    }
    
    // Bắt đầu transaction
    $pdo->beginTransaction();
    
    try {
        // Cập nhật trạng thái đơn nghỉ phép
        $new_status = ($action === 'approve') ? 'Đã phê duyệt' : 'Từ chối';
        $sql = "UPDATE leave_requests SET 
                status = ?, 
                approved_by = ?, 
                approved_at = NOW(), 
                approval_notes = ? 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$new_status, $admin_id, $notes, $request_id]);
        
        // Tạo thông báo cho người yêu cầu
        $notification_title = ($action === 'approve') ? 'Đơn nghỉ phép đã được phê duyệt' : 'Đơn nghỉ phép đã bị từ chối';
        $notification_message = ($action === 'approve') 
            ? "Đơn nghỉ phép {$leave_request['request_code']} của bạn đã được phê duyệt."
            : "Đơn nghỉ phép {$leave_request['request_code']} của bạn đã bị từ chối. Lý do: " . ($notes ?: 'Không có');
        
        $notification_type = ($action === 'approve') ? 'leave_approved' : 'leave_rejected';
        
        $sql = "INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $leave_request['requester_id'],
            $notification_title,
            $notification_message,
            $notification_type,
            $request_id
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Đã $action đơn nghỉ phép thành công",
            'data' => [
                'request_id' => $request_id,
                'status' => $new_status,
                'approved_by' => $admin_id,
                'approved_at' => date('Y-m-d H:i:s'),
                'notes' => $notes
            ]
        ]);
        
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error approving leave request: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi xử lý đơn nghỉ phép'
    ]);
}
?> 