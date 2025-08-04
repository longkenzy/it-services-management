<?php
/**
 * IT CRM - View Blog Post
 * File: view_blog_post.php
 * Mục đích: Trang xem chi tiết bài viết blog
 */

require_once 'includes/session.php';
require_once 'config/db.php';

// Lấy ID bài viết từ URL
$post_id = intval($_GET['id'] ?? 0);

if (!$post_id) {
    header('Location: dashboard.php');
    exit;
}

try {
    // Lấy thông tin bài viết
    $stmt = $pdo->prepare("
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
        WHERE bp.id = ? AND bp.status = 'published'
    ");
    
    $stmt->execute([$post_id]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$post) {
        header('Location: dashboard.php');
        exit;
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
    $image_stmt->execute([$post_id]);
    $post['additional_images'] = $image_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error in view_blog_post.php: " . $e->getMessage());
    header('Location: dashboard.php');
    exit;
}

$page_title = $post['title'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - IT Services Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/dashboard.css" rel="stylesheet">
    <link href="assets/css/no-border-radius.css" rel="stylesheet">
    
    <style>
        .blog-post-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 0;
        }
        
        .blog-post-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .blog-post-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 1rem;
            line-height: 1.2;
        }
        
        .blog-post-meta {
            color: #6c757d;
            font-size: 0.95rem;
        }
        
        .blog-post-meta i {
            margin-right: 0.5rem;
        }
        
        .blog-post-meta .author {
            color: #007bff;
            font-weight: 500;
        }
        
        .blog-post-image {
            margin: 2rem 0;
            text-align: center;
        }
        
        .blog-post-image img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .blog-post-summary {
            font-size: 1.1rem;
            color: #6c757d;
            font-style: italic;
            margin: 1.5rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            border-radius: 0 4px 4px 0;
        }
        
        .blog-post-content {
            font-size: 1.05rem;
            line-height: 1.8;
            color: #212529;
        }
        
        .blog-post-content p {
            margin-bottom: 1.5rem;
        }
        
        .blog-post-content h2, 
        .blog-post-content h3, 
        .blog-post-content h4 {
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #495057;
        }
        
        .blog-post-footer {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid #dee2e6;
            text-align: center;
        }
        
        .back-to-dashboard {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .back-to-dashboard:hover {
            background: #0056b3;
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .blog-post-title {
                font-size: 2rem;
            }
            
            .blog-post-container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">
            <div class="blog-post-container">
                
                <!-- Blog Post Header -->
                <div class="blog-post-header">
                    <h1 class="blog-post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
                    <div class="blog-post-meta">
                        <span class="author">
                            <i class="fas fa-user"></i>
                            <?php echo htmlspecialchars($post['author_name']); ?>
                        </span>
                        <span class="mx-3">
                            <i class="fas fa-calendar"></i>
                            <?php echo $post['created_at_formatted']; ?>
                        </span>
                        <?php if ($post['updated_at'] !== $post['created_at']): ?>
                        <span class="mx-3">
                            <i class="fas fa-edit"></i>
                            Cập nhật: <?php echo $post['updated_at_formatted']; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Blog Post Image -->
                <?php if ($post['featured_image']): ?>
                <div class="blog-post-image">
                    <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                         alt="<?php echo htmlspecialchars($post['title']); ?>" 
                         class="img-fluid">
                </div>
                <?php endif; ?>
                
                <!-- Blog Post Summary -->
                <?php if ($post['summary']): ?>
                <div class="blog-post-summary">
                    <?php echo nl2br(htmlspecialchars($post['summary'])); ?>
                </div>
                <?php endif; ?>
                
                <!-- Blog Post Content -->
                <div class="blog-post-content">
                    <?php 
                    // Format content - chuyển đổi xuống dòng thành HTML
                    $content = htmlspecialchars($post['content']);
                    $content = nl2br($content);
                    echo $content;
                    ?>
                </div>
                
                <!-- Additional Images -->
                <?php if (!empty($post['additional_images'])): ?>
                <div class="blog-post-additional-images">
                    <h4 class="mb-3">
                        <i class="fas fa-images me-2"></i>
                        Hình ảnh bổ sung
                    </h4>
                    <div class="row">
                        <?php foreach ($post['additional_images'] as $image): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="additional-image-item">
                                <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($image['image_name']); ?>" 
                                     class="img-fluid rounded" 
                                     style="width: 100%; height: 200px; object-fit: cover; cursor: pointer;"
                                     onclick="openImageModal('<?php echo htmlspecialchars($image['image_path']); ?>', '<?php echo htmlspecialchars($image['image_name']); ?>')">
                                <div class="image-caption mt-2">
                                    <small class="text-muted"><?php echo htmlspecialchars($image['image_name']); ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Blog Post Footer -->
                <div class="blog-post-footer">
                    <a href="dashboard.php" class="back-to-dashboard">
                        <i class="fas fa-arrow-left"></i>
                        Quay lại Dashboard
                    </a>
                </div>
                
            </div>
        </div>
    </main>
    
    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Xem hình ảnh</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" alt="" class="img-fluid">
                    <div class="mt-3">
                        <small class="text-muted" id="modalImageName"></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/dashboard.js"></script>
    
    <script>
        function openImageModal(imagePath, imageName) {
            document.getElementById('modalImage').src = imagePath;
            document.getElementById('modalImage').alt = imageName;
            document.getElementById('modalImageName').textContent = imageName;
            
            const modal = new bootstrap.Modal(document.getElementById('imageModal'));
            modal.show();
        }
    </script>
</body>
</html> 