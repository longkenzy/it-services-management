<?php
// API lấy danh sách case triển khai theo maintenance_request_id
header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';
require_once '../includes/session.php';
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
$maintenance_request_id = isset($_GET['maintenance_request_id']) ? (int)$_GET['maintenance_request_id'] : 0;
if ($maintenance_request_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Thiếu maintenance_request_id']);
    exit;
}
$sql = "SELECT dc.*, s.fullname as assigned_to_name, (
    SELECT COUNT(*) FROM maintenance_tasks dt WHERE dt.maintenance_case_id = dc.id
) as total_tasks,
(
    SELECT COUNT(*) FROM maintenance_tasks dt WHERE dt.maintenance_case_id = dc.id AND dt.status = 'Hoàn thành'
) as completed_tasks
FROM maintenance_cases dc
LEFT JOIN staffs s ON dc.assigned_to = s.id
WHERE dc.maintenance_request_id = ?
ORDER BY dc.id ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$maintenance_request_id]);
$cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success' => true, 'data' => $cases]);
