<?php
/**
 * IT CRM - Get Blog Posts API
 * File: api/get_blog_posts.php
 * Mục đích: API lấy danh sách bài viết blog
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once '../includes/session.php';
require_once '../config/db.php';

try {
    // Lấy tham số từ request
    $status = $_GET['status'] ?? 'published'; // Mặc định chỉ lấy bài đã xuất bản
    $limit = intval($_GET['limit'] ?? 10);
    $offset = intval($_GET['offset'] ?? 0);
    
    // Validate limit
    if ($limit > 50) $limit = 50;
    if ($limit < 1) $limit = 10;
    
    // Validate status
    if (!in_array($status, ['draft', 'published', 'all'])) {
        $status = 'published';
    }
    
    // Xây dựng câu lệnh SQL
    $sql = "
        SELECT 
            bp.id,
            bp.title,
            bp.content,
            bp.summary,
            bp.featured_image,
            bp.status,
            bp.created_at,
            bp.updated_at,
            s.fullname as author_name,
            s.username as author_username
        FROM blog_posts bp
        LEFT JOIN staffs s ON bp.author_id = s.id
    ";
    
    $params = [];
    
    // Thêm điều kiện status nếu không phải 'all'
    if ($status !== 'all') {
        $sql .= " WHERE bp.status = ?";
        $params[] = $status;
    }
    
    // Thêm ORDER BY và LIMIT
    $sql .= " ORDER BY bp.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    // Thực thi câu lệnh
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Đếm tổng số bài viết
    $countSql = "SELECT COUNT(*) as total FROM blog_posts bp";
    if ($status !== 'all') {
        $countSql .= " WHERE bp.status = ?";
    }
    
    $countStmt = $pdo->prepare($countSql);
    if ($status !== 'all') {
        $countStmt->execute([$status]);
    } else {
        $countStmt->execute();
    }
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Format dữ liệu trả về
    foreach ($posts as &$post) {
        // Cắt ngắn content nếu quá dài
        if (strlen($post['content']) > 200) {
            $post['content_preview'] = substr($post['content'], 0, 200) . '...';
        } else {
            $post['content_preview'] = $post['content'];
        }
        
        // Format ngày tháng
        $post['created_at_formatted'] = date('d/m/Y H:i', strtotime($post['created_at']));
        $post['updated_at_formatted'] = date('d/m/Y H:i', strtotime($post['updated_at']));
        
        // Lấy thêm hình ảnh bổ sung
        $image_stmt = $pdo->prepare("
            SELECT image_path, image_name, image_size, image_type, sort_order 
            FROM blog_images 
            WHERE blog_post_id = ? 
            ORDER BY sort_order ASC
        ");
        $image_stmt->execute([$post['id']]);
        $post['additional_images'] = $image_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $posts,
        'pagination' => [
            'total' => intval($totalCount),
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $totalCount
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_blog_posts.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi database']);
} catch (Exception $e) {
    error_log("Error in get_blog_posts.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
?> 