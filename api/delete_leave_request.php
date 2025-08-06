<?php
/**
 * API: Xóa đơn nghỉ phép
 * Method: POST
 * Parameters: request_id
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
    
    // Kiểm tra quyền xóa (chỉ admin và HR)
    if (!in_array($current_user['role'], ['admin', 'hr'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa đơn nghỉ phép']);
        exit;
    }
    
    // Lấy request_id
    $request_id = $_POST['request_id'] ?? '';
    
    if (empty($request_id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin đơn nghỉ phép']);
        exit;
    }
    
    // Kiểm tra đơn nghỉ phép tồn tại và đã được phê duyệt
    $stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE id = ?");
    $stmt->execute([$request_id]);
    $leave_request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$leave_request) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn nghỉ phép']);
        exit;
    }
    
    // Chỉ cho phép xóa đơn đã được phê duyệt hoặc từ chối
    $allowed_statuses = ['Đã phê duyệt', 'HR đã phê duyệt', 'Admin đã phê duyệt', 'Từ chối bởi Admin', 'Từ chối bởi HR', 'Từ chối'];
    if (!in_array($leave_request['status'], $allowed_statuses)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Chỉ có thể xóa đơn đã được phê duyệt hoặc từ chối']);
        exit;
    }
    
    // Bắt đầu transaction
    $pdo->beginTransaction();
    
    try {
        // Xóa đơn nghỉ phép
        $stmt = $pdo->prepare("DELETE FROM leave_requests WHERE id = ?");
        $stmt->execute([$request_id]);
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa đơn nghỉ phép thành công'
        ]);
        
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi xóa đơn nghỉ phép: ' . $e->getMessage()
    ]);
}
?> 