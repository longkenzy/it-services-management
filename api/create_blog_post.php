<?php
/**
 * IT CRM - Create Blog Post API
 * File: api/create_blog_post.php
 * Mục đích: API tạo bài viết blog mới
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once '../includes/session.php';
require_once '../config/db.php';

// Kiểm tra quyền truy cập - chỉ admin mới được tạo bài viết
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
    // Lấy dữ liệu từ request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    // Validate dữ liệu
    $title = trim($input['title'] ?? '');
    $content = trim($input['content'] ?? '');
    $summary = trim($input['summary'] ?? '');
    $featured_image = trim($input['featured_image'] ?? '');
    $additional_images = $input['additional_images'] ?? [];
    $status = $input['status'] ?? 'draft';
    
    // Kiểm tra dữ liệu bắt buộc
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Tiêu đề không được để trống']);
        exit;
    }
    
    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => 'Nội dung không được để trống']);
        exit;
    }
    
    // Validate status
    if (!in_array($status, ['draft', 'published'])) {
        $status = 'draft';
    }
    
    // Lấy thông tin user hiện tại
    $current_user = getCurrentUser();
    
    // Chuẩn bị câu lệnh SQL
    $stmt = $pdo->prepare("
        INSERT INTO blog_posts (title, content, summary, featured_image, author_id, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    // Thực thi câu lệnh
    $result = $stmt->execute([
        $title,
        $content,
        $summary,
        $featured_image,
        $current_user['id'],
        $status
    ]);
    
    if ($result) {
        $post_id = $pdo->lastInsertId();
        
        // Lưu các hình ảnh bổ sung nếu có
        if (!empty($additional_images) && is_array($additional_images)) {
            $image_stmt = $pdo->prepare("
                INSERT INTO blog_images (blog_post_id, image_path, image_name, image_size, image_type, sort_order, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            foreach ($additional_images as $index => $image_data) {
                if (isset($image_data['path']) && isset($image_data['name']) && isset($image_data['size']) && isset($image_data['type'])) {
                    $image_stmt->execute([
                        $post_id,
                        $image_data['path'],
                        $image_data['name'],
                        $image_data['size'],
                        $image_data['type'],
                        $index
                    ]);
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Bài viết đã được tạo thành công',
            'post_id' => $post_id,
            'data' => [
                'id' => $post_id,
                'title' => $title,
                'summary' => $summary,
                'featured_image' => $featured_image,
                'additional_images_count' => count($additional_images),
                'status' => $status,
                'author_id' => $current_user['id'],
                'author_name' => $current_user['fullname'],
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không thể tạo bài viết']);
    }
    
} catch (PDOException $e) {
    error_log("Database error in create_blog_post.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi database']);
} catch (Exception $e) {
    error_log("Error in create_blog_post.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
?> 