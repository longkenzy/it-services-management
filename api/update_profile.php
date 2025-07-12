<?php
// API cập nhật thông tin cá nhân cho user hiện tại
require_once '../config/db.php';
require_once '../includes/session.php';
header('Content-Type: application/json; charset=utf-8');
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Vui lòng đăng nhập']);
    exit();
}
$user = getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Không xác định được user']);
    exit();
}
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success'=>false,'message'=>'Dữ liệu không hợp lệ']); exit();
}
$fields = [
    'fullname', 'email_work', 'phone_main', 'birth_date', 'gender', 'address'
];
$update = [];
$params = [];
foreach ($fields as $f) {
    if (isset($data[$f])) {
        $update[] = "$f = :$f";
        $params[$f] = trim($data[$f]);
    }
}
if (empty($update)) {
    echo json_encode(['success'=>false,'message'=>'Không có dữ liệu cập nhật']); exit();
}
$params['id'] = $user['id'];
$sql = "UPDATE staffs SET ".implode(',', $update).", updated_at=NOW() WHERE id=:id";
$stmt = $pdo->prepare($sql);
if ($stmt->execute($params)) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'message'=>'Lỗi khi cập nhật']);
} 