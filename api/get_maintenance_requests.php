<?php
header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../config/db.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

try {
    $sql = "SELECT 
                mr.*,
                pc.name as customer_name,
                pc.contact_person,
                pc.contact_phone,
                sale.fullname as sale_name,
                creator.fullname as created_by_name,
                (
                    SELECT COUNT(*) FROM maintenance_cases mc WHERE mc.maintenance_request_id = mr.id
                ) as total_cases,
                (
                    SELECT COUNT(*) FROM maintenance_tasks mt WHERE mt.maintenance_request_id = mr.id
                ) as total_tasks,
                0 as progress_percentage
            FROM maintenance_requests mr
            LEFT JOIN partner_companies pc ON mr.customer_id = pc.id
            LEFT JOIN staffs sale ON mr.sale_id = sale.id
            LEFT JOIN staffs creator ON mr.created_by = creator.id
            ORDER BY mr.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $requests
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_maintenance_requests.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi lấy danh sách yêu cầu bảo trì'
    ]);
}
?> 