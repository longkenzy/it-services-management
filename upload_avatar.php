<?php
require_once 'includes/session.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng chọn file ảnh hợp lệ']);
    exit;
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 2 * 1024 * 1024; // 2MB
$file = $_FILES['avatar'];

if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Chỉ cho phép ảnh JPG, PNG, GIF, WEBP']);
    exit;
}
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'Dung lượng ảnh tối đa 2MB']);
    exit;
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$userId = getCurrentUser()['id'];
$targetDir = 'assets/uploads/avatars/';
$filename = 'user_' . $userId . '_' . time() . '.' . $ext;
$targetFile = $targetDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
    echo json_encode(['success' => false, 'message' => 'Không thể lưu file ảnh']);
    exit;
}

// Cập nhật đường dẫn avatar vào database
require_once 'config/db.php';
try {
    $stmt = $pdo->prepare('UPDATE users SET avatar = ? WHERE id = ?');
    $stmt->execute([$targetFile, $userId]);
    $_SESSION['user']['avatar'] = $targetFile;
    echo json_encode(['success' => true, 'avatar' => $targetFile, 'message' => 'Cập nhật ảnh đại diện thành công']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
} 