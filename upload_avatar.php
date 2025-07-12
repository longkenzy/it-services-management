<?php
/**
 * IT CRM - Avatar Upload Handler
 * File: upload_avatar.php
 * Mục đích: Xử lý upload ảnh đại diện cho user
 * Tác giả: IT Support Team
 */

require_once 'includes/session.php';
requireLogin();
require_once 'config/db.php';

// Thiết lập header cho JSON response
header('Content-Type: application/json; charset=utf-8');

// Kiểm tra phương thức request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false, 
        'message' => 'Phương thức không hợp lệ'
    ]);
    exit;
}

// Kiểm tra file upload
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false, 
        'message' => 'Vui lòng chọn file ảnh hợp lệ'
    ]);
    exit;
}

$file = $_FILES['avatar'];

// Kiểm tra định dạng file
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF)'
    ]);
    exit;
}

// Kiểm tra kích thước file (5MB)
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    echo json_encode([
        'success' => false, 
        'message' => 'Dung lượng ảnh tối đa 5MB'
    ]);
    exit;
}

try {
    // Lấy thông tin user hiện tại
    $currentUser = getCurrentUser();
    $username = $currentUser['username'];
    
    // Lấy thông tin avatar hiện tại từ database
    $stmt = $pdo->prepare('SELECT avatar FROM staffs WHERE username = ?');
    $stmt->execute([$username]);
    $currentAvatar = $stmt->fetchColumn();
    
    // Tạo thư mục upload nếu chưa tồn tại
    $targetDir = 'assets/uploads/avatars/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Tạo tên file mới
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'user_' . $currentUser['id'] . '_' . time() . '.' . $ext;
    $targetFile = $targetDir . $filename;
    
    // Upload file mới
    if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Không thể lưu file ảnh'
        ]);
        exit;
    }
    
    // Cập nhật đường dẫn avatar vào database
    $stmt = $pdo->prepare('UPDATE staffs SET avatar = ? WHERE username = ?');
    $stmt->execute([$filename, $username]);
    
    // Xóa avatar cũ nếu tồn tại
    if ($currentAvatar && $currentAvatar !== $filename) {
        $oldAvatarPath = $targetDir . $currentAvatar;
        if (file_exists($oldAvatarPath)) {
            unlink($oldAvatarPath);
        }
    }
    
    // Ghi log hoạt động
    logUserActivity('avatar_update', 'Cập nhật ảnh đại diện');
    
    echo json_encode([
        'success' => true,
        'avatar' => $targetFile,
        'message' => 'Cập nhật ảnh đại diện thành công!'
    ]);
    
} catch (Exception $e) {
    // Ghi log lỗi
    error_log("Error in avatar upload: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi cập nhật ảnh đại diện: ' . $e->getMessage()
    ]);
}
?> 