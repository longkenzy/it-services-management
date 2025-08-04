<?php
/**
 * IT CRM - Create Blog Images Table
 * File: create_blog_images_table.php
 * Mục đích: Tạo bảng blog_images để lưu nhiều hình ảnh cho mỗi bài viết
 */

require_once 'includes/session.php';
require_once 'config/db.php';

// Kiểm tra quyền truy cập - chỉ admin mới được tạo bảng
if (!hasRole('admin')) {
    die('Không có quyền truy cập');
}

try {
    // SQL để tạo bảng blog_images
    $sql = "
    CREATE TABLE IF NOT EXISTS `blog_images` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `blog_post_id` int(11) NOT NULL,
      `image_path` varchar(500) NOT NULL,
      `image_name` varchar(255) NOT NULL,
      `image_size` int(11) NOT NULL,
      `image_type` varchar(100) NOT NULL,
      `sort_order` int(11) DEFAULT 0,
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `blog_post_id` (`blog_post_id`),
      KEY `sort_order` (`sort_order`),
      CONSTRAINT `fk_blog_images_post` 
      FOREIGN KEY (`blog_post_id`) REFERENCES `blog_posts` (`id`) 
      ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Thực thi câu lệnh SQL
    $result = $pdo->exec($sql);
    
    if ($result !== false) {
        echo "<h2>✅ Tạo bảng blog_images thành công!</h2>";
        echo "<p>Bảng <code>blog_images</code> đã được tạo trong database.</p>";
        echo "<p>Bây giờ bạn có thể:</p>";
        echo "<ul>";
        echo "<li>Upload nhiều hình ảnh cho blog posts</li>";
        echo "<li>Xem hình ảnh bổ sung trong trang chi tiết bài viết</li>";
        echo "<li>Xem số lượng hình ảnh trên dashboard</li>";
        echo "</ul>";
        echo "<p><a href='blog.php'>Quay lại trang Blog</a> | <a href='dashboard.php'>Quay lại Dashboard</a></p>";
    } else {
        echo "<h2>❌ Lỗi khi tạo bảng</h2>";
        echo "<p>Có lỗi xảy ra khi tạo bảng <code>blog_images</code>.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2>❌ Lỗi Database</h2>";
    echo "<p>Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Vui lòng kiểm tra lại cấu hình database.</p>";
} catch (Exception $e) {
    echo "<h2>❌ Lỗi</h2>";
    echo "<p>Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Xóa file này sau khi sử dụng
echo "<hr>";
echo "<p><small><strong>Lưu ý:</strong> Vui lòng xóa file <code>create_blog_images_table.php</code> này sau khi sử dụng để bảo mật.</small></p>";
?> 