<?php
/**
 * IT CRM - Change Password Handler
 * File: change_password.php
 * Mục đích: Xử lý đổi mật khẩu người dùng
 */

// Include các file cần thiết
require_once 'includes/session.php';

// Include database connection
if (!defined('INCLUDED')) {
    define('INCLUDED', true);
}
require_once 'config/db.php';

// Bảo vệ trang - yêu cầu đăng nhập
requireLogin();

// Lấy thông tin user hiện tại
$current_user = getCurrentUser();

$user_id = $current_user['id'] ?? null;
$username = $current_user['username'] ?? null;
$old = $_POST['old_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (!$user_id || !$username || !$old || !$new || !$confirm) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin!']);
    exit;
}

if ($new !== $confirm) {
    echo json_encode(['success' => false, 'message' => 'Mật khẩu xác nhận không khớp!']);
    exit;
}

// Lấy mật khẩu cũ từ bảng staffs (nơi user đăng nhập)
$stmt = $pdo->prepare('SELECT password FROM staffs WHERE id = ? AND username = ?');
$stmt->execute([$user_id, $username]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row || !password_verify($old, $row['password'])) {
    echo json_encode(['success' => false, 'message' => 'Mật khẩu cũ không đúng!']);
    exit;
}

// Update mật khẩu mới trong bảng staffs
$new_hash = password_hash($new, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('UPDATE staffs SET password = ? WHERE id = ? AND username = ?');
$stmt->execute([$new_hash, $user_id, $username]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Không thể cập nhật mật khẩu!']);
} 