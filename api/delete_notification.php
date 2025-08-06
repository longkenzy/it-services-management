<?php
/**
 * API: Xóa thông báo
 * Method: POST
 * Parameters: notification_id
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
    $notification_id = $_POST['notification_id'] ?? '';
    $delete_all = $_POST['delete_all'] ?? false;
    
    if ($delete_all) {
        // Xóa tất cả thông báo của user
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
        $result = $stmt->execute([$current_user['id']]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa tất cả thông báo thành công'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa thông báo'
            ]);
        }
    } else {
        // Xóa thông báo cụ thể
        if (empty($notification_id)) {
            echo json_encode(['success' => false, 'message' => 'ID thông báo không được cung cấp']);
            exit;
        }
        
        // Kiểm tra xem thông báo có tồn tại và thuộc về user hiện tại không
        $stmt = $pdo->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->execute([$notification_id, $current_user['id']]);
        $notification = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$notification) {
            echo json_encode(['success' => false, 'message' => 'Thông báo không tồn tại hoặc không có quyền xóa']);
            exit;
        }
        
        // Xóa thông báo
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$notification_id, $current_user['id']]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa thông báo thành công'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa thông báo'
            ]);
        }
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi xóa thông báo: ' . $e->getMessage()
    ]);
}
?> 