<?php
require_once '../config/db.php';
require_once '../includes/session.php';
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

if (null === getCurrentUserId()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

try {
    $sql = "SELECT 
    dr.*,
    pc.name as customer_name,
    pc.contact_person,
    pc.contact_phone,
    sale.fullname as sale_name,
    creator.fullname as created_by_name,
    (
        SELECT COUNT(*) FROM deployment_cases dc WHERE dc.deployment_request_id = dr.id
    ) as total_cases,
    (
        SELECT COUNT(*) FROM deployment_tasks dt WHERE dt.deployment_request_id = dr.id
    ) as total_tasks,
    (
        SELECT COUNT(*) FROM deployment_cases dc WHERE dc.deployment_request_id = dr.id AND dc.status = 'Hoàn thành'
    ) as completed_cases
FROM deployment_requests dr
LEFT JOIN partner_companies pc ON dr.customer_id = pc.id
LEFT JOIN staffs sale ON dr.sale_id = sale.id
LEFT JOIN staffs creator ON dr.created_by = creator.id
ORDER BY dr.created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Tính progress_percentage cho từng request
    foreach ($requests as &$req) {
        $total = (int)($req['total_cases'] ?? 0);
        $done = (int)($req['completed_cases'] ?? 0);
        $req['progress_percentage'] = $total > 0 ? round($done / $total * 100) : 0;
    }
    echo json_encode(['success' => true, 'data' => $requests]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 