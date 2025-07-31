<?php
header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../config/db.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$case_id = $_GET['id'] ?? null;

if (!$case_id) {
    echo json_encode(['success' => false, 'message' => 'ID case không hợp lệ']);
    exit;
}

try {
    $sql = "SELECT 
                mc.*,
                mr.request_code,
                s.fullname as assigned_to_name
            FROM maintenance_cases mc
            LEFT JOIN maintenance_requests mr ON mc.maintenance_request_id = mr.id
            LEFT JOIN staffs s ON mc.assigned_to = s.id
            WHERE mc.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$case_id]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$case) {
        echo json_encode(['success' => false, 'message' => 'Case bảo trì không tồn tại']);
        exit;
    }
    

    
    echo json_encode([
        'success' => true,
        'data' => $case
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_maintenance_case_details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi lấy thông tin case bảo trì'
    ]);
}
?> 