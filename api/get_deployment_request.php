<?php
header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../config/db.php';

if (null === getCurrentUserId()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$request_id = $_GET['id'] ?? null;

if (!$request_id) {
    echo json_encode(['success' => false, 'message' => 'ID yêu cầu không hợp lệ']);
    exit;
}

try {
    $sql = "SELECT 
                dr.*,
                pc.name as customer_name,
                pc.contact_person,
                pc.contact_phone,
                sale.fullname as sale_name,
                creator.fullname as created_by_name
            FROM deployment_requests dr
            LEFT JOIN partner_companies pc ON dr.customer_id = pc.id
            LEFT JOIN staffs sale ON dr.sale_id = sale.id
            LEFT JOIN staffs creator ON dr.created_by = creator.id
            WHERE dr.id = ?";
    

    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Yêu cầu triển khai không tồn tại']);
        exit;
    }
    

    
    echo json_encode([
        'success' => true,
        'data' => $request
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_deployment_request.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi lấy thông tin yêu cầu triển khai'
    ]);
}
?> 