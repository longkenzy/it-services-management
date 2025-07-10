<?php
require_once '../config/db.php';
require_once '../includes/session.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.']);
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM staffs WHERE id = ?");
$stmt->execute([$id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

if ($staff) {
    echo json_encode(['success' => true, 'data' => $staff]);
} else {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhân sự']);
} 