<?php
/**
 * Test API: Kiểm tra notifications
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

try {
    // Kiểm tra notifications mới nhất
    $stmt = $pdo->prepare("SELECT n.*, s.fullname as user_name, s.role as user_role 
                           FROM notifications n 
                           LEFT JOIN staffs s ON n.user_id = s.id 
                           WHERE n.type = 'leave_request' 
                           ORDER BY n.created_at DESC 
                           LIMIT 10");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kiểm tra notifications cho IT leaders
    $stmt2 = $pdo->prepare("SELECT n.*, s.fullname as user_name, s.role as user_role 
                            FROM notifications n 
                            LEFT JOIN staffs s ON n.user_id = s.id 
                            WHERE n.type = 'leave_request' AND s.role = 'it_leader'
                            ORDER BY n.created_at DESC 
                            LIMIT 5");
    $stmt2->execute();
    $it_leader_notifications = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'recent_notifications' => $notifications,
            'it_leader_notifications' => $it_leader_notifications
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
?>
