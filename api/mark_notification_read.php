<?php
/**
 * API đánh dấu thông báo đã đọc
 */

header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../config/db.php';

if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Chưa đăng nhập'
    ]);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    $notification_id = isset($input['notification_id']) ? intval($input['notification_id']) : null;
    $mark_all = isset($input['mark_all']) ? $input['mark_all'] === 'true' : false;
    
    $user_id = $_SESSION[SESSION_USER_ID];
    
    if ($mark_all) {
        // Đánh dấu tất cả thông báo đã đọc
        $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        
        $affected_rows = $stmt->rowCount();
        
        echo json_encode([
            'success' => true,
            'message' => "Đã đánh dấu $affected_rows thông báo đã đọc"
        ]);
    } else {
        // Đánh dấu một thông báo cụ thể
        if (!$notification_id) {
            echo json_encode([
                'success' => false,
                'message' => 'Thiếu ID thông báo'
            ]);
            exit;
        }
        
        // Kiểm tra thông báo có thuộc về user không
        $sql = "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$notification_id, $user_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Đã đánh dấu thông báo đã đọc'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy thông báo'
            ]);
        }
    }
    
} catch (Exception $e) {
    error_log("Error marking notification read: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi đánh dấu thông báo'
    ]);
}
?> 