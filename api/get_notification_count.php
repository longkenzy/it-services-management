<?php
/**
 * API: Lấy số thông báo chưa đọc
 * Method: GET
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
    
    // Lấy số thông báo chưa đọc
    $stmt = $pdo->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$current_user['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $unread_count = (int)$result['unread_count'];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'unread_count' => $unread_count
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi lấy số thông báo: ' . $e->getMessage()
    ]);
}
?> 