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
        .blog-form {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .blog-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            min-height: 200px;
        }
        
        .blog-preview h3 {
            color: #495057;
            margin-bottom: 15px;
        }
        
        .blog-preview .meta {
            color: #6c757d;
            font-size: 0.9em;
            margin-bottom: 15px;
        }
        
        .blog-preview .content {
            line-height: 1.6;
            color: #212529;
        }
        
        .status-badge {
            font-size: 0.8em;
            padding: 4px 8px;
        }
        
        .draft-badge {
            background-color: #ffc107;
            color: #212529;
        }
        
        .published-badge {
            background-color: #28a745;
            color: white;
        }
        
        /* Main content layout */
        .main-content {
            padding: 2rem 0;
            min-height: calc(100vh - 80px);
        }
        
        .container-fluid {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        /* Image Upload Styles */
        .image-upload-container {
            position: relative;
        }
        
        .image-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
            position: relative;
        }
        
        .image-upload-area:hover {
            border-color: #007bff;
            background: #e3f2fd;
        }
        
        .image-upload-area.dragover {
            border-color: #28a745;
            background: #d4edda;
        }
        
        .upload-placeholder {
            pointer-events: none;
        }
        
        .image-preview {
            position: relative;
            display: inline-block;
            max-width: 100%;
        }
        
        .image-preview img {
            max-width: 300px;
            max-height: 200px;
            object-fit: cover;
        }
        
        .remove-image {
            position: absolute;
            top: -10px;
            right: -10px;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        
        .upload-progress {
            margin-top: 1rem;
        }
        
        .progress {
            height: 6px;
        }
        
        /* Multiple Images Upload Styles */
        .multiple-images-upload-container {
            position: relative;
        }
        
        .multiple-images-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
            position: relative;
        }
        
        .multiple-images-upload-area:hover {
            border-color: #17a2b8;
            background: #e3f2fd;
        }
        
        .multiple-images-upload-area.dragover {
            border-color: #28a745;
            background: #d4edda;
        }
        
        .multiple-images-preview {
            margin-top: 1rem;
        }
        
        .image-item {
            position: relative;
            margin-bottom: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
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
            background: rgba(0,0,0,0.7);
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
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        
        .image-item-info {
            padding: 0.5rem;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
        
        .image-item-name {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .image-item-size {
            font-size: 0.7rem;
            color: #adb5bd;
        }
        
        .upload-progress-item {
            margin-top: 0.5rem;
        }
        
        .upload-progress-item .progress {
            height: 4px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">
                
                <!-- Page Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-blog me-2 text-primary"></i>
                        Quản lý Blog
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="previewBtn">
                                <i class="fas fa-eye me-1"></i>
                                Xem trước
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Alert Messages -->
                <div id="alertContainer"></div>
                
                <!-- Blog Form -->
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card blog-form">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-edit me-2"></i>
                                    Tạo bài viết mới
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="blogForm">
                                    <div class="mb-3">
                                        <label for="title" class="form-label fw-semibold">
                                            <i class="fas fa-heading me-2 text-primary"></i>
                                            Tiêu đề bài viết
                                        </label>
                                        <input type="text" class="form-control" id="title" name="title" 
                                               placeholder="Nhập tiêu đề bài viết..." required>
                                    </div>
                                    
                                                                         <div class="mb-3">
                                         <label for="summary" class="form-label fw-semibold">
                                             <i class="fas fa-align-left me-2 text-success"></i>
                                             Tóm tắt
                                         </label>
                                         <textarea class="form-control" id="summary" name="summary" rows="3" 
                                                   placeholder="Nhập tóm tắt bài viết..."></textarea>
                                     </div>
                                     
                                     <div class="mb-3">
                                         <label for="featuredImage" class="form-label fw-semibold">
                                             <i class="fas fa-image me-2 text-warning"></i>
                                             Hình ảnh đại diện
                                         </label>
                                         <div class="image-upload-container">
                                             <input type="file" class="form-control" id="featuredImage" 
                                                    accept="image/*" style="display: none;">
                                             <div class="image-upload-area" id="imageUploadArea">
                                                 <div class="upload-placeholder">
                                                     <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                                     <p class="text-muted mb-2">Click để chọn hình ảnh</p>
                                                     <small class="text-muted">Hỗ trợ: JPG, PNG, GIF, WEBP (Tối đa 5MB)</small>
                                                 </div>
                                                 <div class="image-preview" id="imagePreview" style="display: none;">
                                                     <img id="previewImg" src="" alt="Preview" class="img-fluid rounded">
                                                     <button type="button" class="btn btn-sm btn-danger remove-image" id="removeImage">
                                                         <i class="fas fa-times"></i>
                                                     </button>
                                                 </div>
                                             </div>
                                             <input type="hidden" id="featuredImagePath" name="featured_image">
                                         </div>
                                     </div>
                                     
                                     <div class="mb-3">
                                         <label class="form-label fw-semibold">
                                             <i class="fas fa-images me-2 text-info"></i>
                                             Hình ảnh bổ sung
                                         </label>
                                         <div class="multiple-images-upload-container">
                                             <input type="file" class="form-control" id="multipleImages" 
                                                    accept="image/*" multiple style="display: none;">
                                             <div class="multiple-images-upload-area" id="multipleImagesUploadArea">
                                                 <div class="upload-placeholder">
                                                     <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
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
                                     
                                     <div class="mb-3">
                                        <label for="content" class="form-label fw-semibold">
                                            <i class="fas fa-file-alt me-2 text-info"></i>
                                            Nội dung bài viết
                                        </label>
                                        <textarea class="form-control" id="content" name="content" rows="15" 
                                                  placeholder="Nhập nội dung bài viết..." required></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="status" class="form-label fw-semibold">
                                            <i class="fas fa-toggle-on me-2 text-warning"></i>
                                            Trạng thái
                                        </label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="draft">Bản nháp</option>
                                            <option value="published">Xuất bản</option>
                                        </select>
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>
                                            Lưu bài viết
                                        </button>
                                        <button type="button" class="btn btn-secondary" id="resetBtn">
                                            <i class="fas fa-undo me-2"></i>
                                            Làm mới
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preview Panel -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-eye me-2"></i>
                                    Xem trước
                                </h5>
                            </div>
                            <div class="card-body">
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