<?php
/**
 * IT CRM - Blog Management
 * File: blog.php
 * Mục đích: Trang quản lý blog - chỉ admin mới truy cập được
 */

require_once 'includes/session.php';

// Kiểm tra quyền truy cập - chỉ admin mới được vào
if (!hasRole('admin')) {
    header('Location: dashboard.php');
    exit;
}

$current_user = getCurrentUser();
$page_title = 'Quản lý Blog';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - IT Services Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/dashboard.css" rel="stylesheet">
    <link href="assets/css/no-border-radius.css" rel="stylesheet">
    
    <style>
        /* Blog Page Specific Styles */
        .blog-page {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .blog-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 2rem auto;
            padding: 2rem;
        }
        
        .blog-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .blog-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .blog-header h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .blog-header p {
            font-size: 1.2rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .blog-form-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .form-section-title {
            color: #2c3e50;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid #667eea;
            display: inline-block;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #34495e;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-label i {
            width: 20px;
            text-align: center;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            background: white;
        }
        
        .form-control::placeholder {
            color: #adb5bd;
        }
        
        .btn-custom {
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .btn-custom::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-custom:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
            color: white;
        }
        
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(149, 165, 166, 0.3);
        }
        
        /* Image Upload Styles */
        .image-upload-container {
            position: relative;
        }
        
        .image-upload-area {
            border: 3px dashed #667eea;
            border-radius: 15px;
            padding: 3rem 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            position: relative;
            overflow: hidden;
        }
        
        .image-upload-area::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(102, 126, 234, 0.1) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }
        
        .image-upload-area:hover::before {
            transform: translateX(100%);
        }
        
        .image-upload-area:hover {
            border-color: #764ba2;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }
        
        .image-upload-area.dragover {
            border-color: #27ae60;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            transform: scale(1.02);
        }
        
        .upload-placeholder {
            pointer-events: none;
            position: relative;
            z-index: 1;
        }
        
        .upload-placeholder i {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 1rem;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .image-preview {
            position: relative;
            display: inline-block;
            max-width: 100%;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .image-preview img {
            max-width: 300px;
            max-height: 200px;
            object-fit: cover;
            border-radius: 15px;
        }
        
        .remove-image {
            position: absolute;
            top: -10px;
            right: -10px;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            background: #e74c3c;
            color: white;
            border: 3px solid white;
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
            transition: all 0.3s ease;
        }
        
        .remove-image:hover {
            background: #c0392b;
            transform: scale(1.1);
        }
        
        /* Multiple Images Upload Styles */
        .multiple-images-upload-container {
            position: relative;
        }
        
        .multiple-images-upload-area {
            border: 3px dashed #17a2b8;
            border-radius: 15px;
            padding: 3rem 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            position: relative;
            overflow: hidden;
        }
        
        .multiple-images-upload-area::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(23, 162, 184, 0.1) 50%, transparent 70%);
            transform: translateX(-100%);
            transition: transform 0.6s;
        }
        
        .multiple-images-upload-area:hover::before {
            transform: translateX(100%);
        }
        
        .multiple-images-upload-area:hover {
            border-color: #138496;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(23, 162, 184, 0.2);
        }
        
        .multiple-images-upload-area.dragover {
            border-color: #27ae60;
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            transform: scale(1.02);
        }
        
        .multiple-images-preview {
            margin-top: 1.5rem;
        }
        
        .image-item {
            position: relative;
            margin-bottom: 1.5rem;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            overflow: hidden;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .image-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .image-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .image-item-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .image-item:hover .image-item-overlay {
            opacity: 1;
        }
        
        .image-item-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .image-item-actions .btn {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            border-radius: 8px;
        }
        
        .image-item-info {
            padding: 1rem;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        
        .image-item-name {
            font-size: 0.9rem;
            color: #495057;
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 500;
        }
        
        .image-item-size {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .upload-progress-item {
            margin-top: 0.75rem;
        }
        
        .upload-progress-item .progress {
            height: 8px;
            border-radius: 10px;
            background: #e9ecef;
        }
        
        .upload-progress-item .progress-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }
        
        /* Preview Panel Styles */
        .preview-panel {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 2rem;
        }
        
        .blog-preview {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 2rem;
            min-height: 300px;
            position: relative;
            overflow: hidden;
        }
        
        .blog-preview::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="preview-pattern" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="%23e9ecef" opacity="0.3"/></pattern></defs><rect width="100" height="100" fill="url(%23preview-pattern)"/></svg>');
            opacity: 0.5;
        }
        
        .blog-preview h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        
        .blog-preview .meta {
            color: #6c757d;
            font-size: 0.9em;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .blog-preview .content {
            line-height: 1.8;
            color: #495057;
            position: relative;
            z-index: 1;
        }
        
        .status-badge {
            font-size: 0.8em;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .draft-badge {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
        }
        
        .published-badge {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .blog-container {
                margin: 1rem;
                padding: 1rem;
            }
            
            .blog-header h1 {
                font-size: 2rem;
            }
            
            .blog-header p {
                font-size: 1rem;
            }
            
            .form-section-title {
                font-size: 1.25rem;
            }
            
            .image-upload-area,
            .multiple-images-upload-area {
                padding: 2rem 1rem;
            }
            
            .upload-placeholder i {
                font-size: 3rem;
            }
        }
        
        /* Loading Animation */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Success Animation */
        .success-checkmark {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #27ae60;
            color: white;
            text-align: center;
            line-height: 20px;
            font-size: 12px;
            animation: bounce 0.6s ease-in-out;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
    </style>
</head>
<body class="blog-page">
    <?php include 'includes/header.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">
            <div class="blog-container">
                
                <!-- Blog Header -->
                <div class="blog-header">
                    <h1>
                        <i class="fas fa-blog me-3"></i>
                        Quản lý Blog
                    </h1>
                    <p>Tạo và quản lý các bài viết blog cho website với giao diện hiện đại</p>
                </div>
                
                <!-- Alert Messages -->
                <div id="alertContainer"></div>
                
                <!-- Blog Form Section -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="blog-form-section">
                            <h3 class="form-section-title">
                                <i class="fas fa-edit me-2"></i>
                                Tạo bài viết mới
                            </h3>
                            
                            <form id="blogForm">
                                <div class="form-group">
                                    <label for="title" class="form-label">
                                        <i class="fas fa-heading text-primary"></i>
                                        Tiêu đề bài viết
                                    </label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           placeholder="Nhập tiêu đề bài viết..." required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="summary" class="form-label">
                                        <i class="fas fa-align-left text-success"></i>
                                        Tóm tắt
                                    </label>
                                    <textarea class="form-control" id="summary" name="summary" rows="3" 
                                              placeholder="Nhập tóm tắt bài viết..."></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="featuredImage" class="form-label">
                                        <i class="fas fa-image text-warning"></i>
                                        Hình ảnh đại diện
                                    </label>
                                    <div class="image-upload-container">
                                        <input type="file" class="form-control" id="featuredImage" 
                                               accept="image/*" style="display: none;">
                                        <div class="image-upload-area" id="imageUploadArea">
                                            <div class="upload-placeholder">
                                                <i class="fas fa-cloud-upload-alt"></i>
                                                <p class="text-muted mb-2">Click để chọn hình ảnh</p>
                                                <small class="text-muted">Hỗ trợ: JPG, PNG, GIF, WEBP (Tối đa 5MB)</small>
                                            </div>
                                            <div class="image-preview" id="imagePreview" style="display: none;">
                                                <img id="previewImg" src="" alt="Preview" class="img-fluid">
                                                <button type="button" class="btn btn-sm remove-image" id="removeImage">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <input type="hidden" id="featuredImagePath" name="featured_image">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-images text-info"></i>
                                        Hình ảnh bổ sung
                                    </label>
                                    <div class="multiple-images-upload-container">
                                        <input type="file" class="form-control" id="multipleImages" 
                                               accept="image/*" multiple style="display: none;">
                                        <div class="multiple-images-upload-area" id="multipleImagesUploadArea">
                                            <div class="upload-placeholder">
                                                <i class="fas fa-images"></i>
                                                <p class="text-muted mb-2">Click để chọn nhiều hình ảnh</p>
                                                <small class="text-muted">Hỗ trợ: JPG, PNG, GIF, WEBP (Tối đa 5MB mỗi file)</small>
                                                <br>
                                                <small class="text-muted">Có thể chọn nhiều file cùng lúc</small>
                                            </div>
                                        </div>
                                        <div class="multiple-images-preview" id="multipleImagesPreview" style="display: none;">
                                            <div class="row" id="multipleImagesGrid">
                                                <!-- Images will be added here dynamically -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="content" class="form-label">
                                        <i class="fas fa-file-alt text-info"></i>
                                        Nội dung bài viết
                                    </label>
                                    <textarea class="form-control" id="content" name="content" rows="15" 
                                              placeholder="Nhập nội dung bài viết..." required></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="status" class="form-label">
                                        <i class="fas fa-toggle-on text-warning"></i>
                                        Trạng thái
                                    </label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="draft">Bản nháp</option>
                                        <option value="published">Xuất bản</option>
                                    </select>
                                </div>
                                
                                <div class="d-flex gap-3">
                                    <button type="submit" class="btn btn-custom btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        Lưu bài viết
                                    </button>
                                    <button type="button" class="btn btn-custom btn-secondary" id="resetBtn">
                                        <i class="fas fa-undo me-2"></i>
                                        Làm mới
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Preview Panel -->
                    <div class="col-lg-4">
                        <div class="preview-panel">
                            <h3 class="form-section-title">
                                <i class="fas fa-eye me-2"></i>
                                Xem trước
                            </h3>
                            <div class="blog-preview" id="blogPreview">
                                <div class="text-center text-muted">
                                    <i class="fas fa-file-alt fa-3x mb-3"></i>
                                    <p>Nội dung xem trước sẽ hiển thị ở đây</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </main>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/dashboard.js"></script>
    <script src="assets/js/blog.js"></script>
</body>
</html> 