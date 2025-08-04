<?php
/**
 * IT CRM - Upload Multiple Blog Images API
 * File: api/upload_multiple_blog_images.php
 * Mục đích: API upload nhiều hình ảnh cho blog
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
    if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
        echo json_encode(['success' => false, 'message' => 'Không có file được upload']);
        exit;
    }
    
    $uploaded_files = [];
    $errors = [];
    $max_size = 5 * 1024 * 1024; // 5MB
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    
    // Tạo thư mục nếu chưa có
    $upload_dir = '../assets/uploads/blog_images/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Xử lý từng file
    for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
        $file = [
            'name' => $_FILES['images']['name'][$i],
            'type' => $_FILES['images']['type'][$i],
            'tmp_name' => $_FILES['images']['tmp_name'][$i],
            'error' => $_FILES['images']['error'][$i],
            'size' => $_FILES['images']['size'][$i]
        ];
        
        // Kiểm tra lỗi upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "File {$file['name']}: Lỗi upload";
            continue;
        }
        
        // Kiểm tra kích thước file
        if ($file['size'] > $max_size) {
            $errors[] = "File {$file['name']}: Quá lớn (tối đa 5MB)";
            continue;
        }
        
        // Kiểm tra loại file
        $file_type = mime_content_type($file['tmp_name']);
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "File {$file['name']}: Loại file không được hỗ trợ";
            continue;
        }
        
        // Tạo tên file mới
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'blog_' . time() . '_' . uniqid() . '_' . $i . '.' . $extension;
        $filepath = $upload_dir . $filename;
        
        // Upload file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            $relative_path = 'assets/uploads/blog_images/' . $filename;
            
            $uploaded_files[] = [
                'original_name' => $file['name'],
                'filename' => $filename,
                'path' => $relative_path,
                'size' => $file['size'],
                'type' => $file_type
            ];
        } else {
            $errors[] = "File {$file['name']}: Không thể lưu file";
        }
    }
    
    // Trả về kết quả
    if (empty($uploaded_files)) {
        echo json_encode([
            'success' => false,
            'message' => 'Không có file nào được upload thành công',
            'errors' => $errors
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Upload thành công ' . count($uploaded_files) . ' file',
            'data' => [
                'uploaded_files' => $uploaded_files,
                'total_uploaded' => count($uploaded_files),
                'errors' => $errors
            ]
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error in upload_multiple_blog_images.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi upload file']);
}
?> 