<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

require_once '../config/db.php';

// Bật query cache nếu có thể
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

$case_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($case_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid case ID']);
    exit;
}

// Tối ưu hóa query với index hints và chỉ select các trường cần thiết
$stmt = $pdo->prepare("
    SELECT 
        ic.id,
        ic.case_number,
        ic.requester_id,
        ic.handler_id,
        ic.case_type,
        ic.priority,
        ic.issue_title,
        ic.issue_description,
        ic.status,
        ic.notes,
        ic.start_date,
        ic.due_date,
        ic.created_at,
        ic.updated_at,
        requester.fullname as requester_name,
        requester.position as requester_position,
        handler.fullname as handler_name,
        handler.position as handler_position
    FROM internal_cases ic
    LEFT JOIN staffs requester ON ic.requester_id = requester.id
    LEFT JOIN staffs handler ON ic.handler_id = handler.id
    WHERE ic.id = ?
");
$stmt->execute([$case_id]);
$case = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$case) {
    echo json_encode(['success' => false, 'error' => 'Case not found']);
    exit;
}

echo json_encode(['success' => true, 'data' => $case]); 