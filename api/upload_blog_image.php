<?php
/**
 * IT CRM - Upload Blog Image API
 * File: api/upload_blog_image.php
 * Mục đích: API upload hình ảnh cho blog
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once '../includes/session.php';
require_once '../config/db.php';

// Kiểm tra quyền truy cập - chỉ admin mới được upload
if (!hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Chỉ chấp nhận POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Kiểm tra có file được upload không
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Không có file được upload hoặc lỗi upload']);
        exit;
    }
    
    $file = $_FILES['image'];
    
    // Kiểm tra kích thước file (tối đa 5MB)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $max_size) {
        echo json_encode(['success' => false, 'message' => 'File quá lớn. Kích thước tối đa là 5MB']);
        exit;
    }
    
    // Kiểm tra loại file
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($file['tmp_name']);
    
    if (!in_array($file_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Loại file không được hỗ trợ. Chỉ chấp nhận JPG, PNG, GIF, WEBP']);
        exit;
    }
    
    // Tạo tên file mới
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'blog_' . time() . '_' . uniqid() . '.' . $extension;
    
    // Đường dẫn lưu file
    $upload_dir = '../assets/uploads/blog_images/';
    $filepath = $upload_dir . $filename;
    
    // Tạo thư mục nếu chưa có
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Upload file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Trả về đường dẫn tương đối
        $relative_path = 'assets/uploads/blog_images/' . $filename;
        
        echo json_encode([
            'success' => true,
            'message' => 'Upload hình ảnh thành công',
            'data' => [
                'filename' => $filename,
                'path' => $relative_path,
                'size' => $file['size'],
                'type' => $file_type
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể lưu file']);
    }
    
} catch (Exception $e) {
    error_log("Error in upload_blog_image.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi upload file']);
}
?> 