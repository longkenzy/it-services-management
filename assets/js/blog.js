/**
 * IT CRM - Blog Management JavaScript
 * File: assets/js/blog.js
 * Mục đích: Xử lý logic cho trang quản lý blog
 */

$(document).ready(function() {
    
    // ===== KHỞI TẠO CÁC BIẾN ===== //
    const blogForm = $('#blogForm');
    const titleInput = $('#title');
    const summaryInput = $('#summary');
    const contentInput = $('#content');
    const statusSelect = $('#status');
    const previewBtn = $('#previewBtn');
    const resetBtn = $('#resetBtn');
    const blogPreview = $('#blogPreview');
    const alertContainer = $('#alertContainer');
    
    // Image upload elements
    const featuredImageInput = $('#featuredImage');
    const imageUploadArea = $('#imageUploadArea');
    const imagePreview = $('#imagePreview');
    const previewImg = $('#previewImg');
    const removeImageBtn = $('#removeImage');
    const featuredImagePath = $('#featuredImagePath');
    
    // Multiple images upload elements
    const multipleImagesInput = $('#multipleImages');
    const multipleImagesUploadArea = $('#multipleImagesUploadArea');
    const multipleImagesPreview = $('#multipleImagesPreview');
    const multipleImagesGrid = $('#multipleImagesGrid');
    
    // Store uploaded additional images
    let additionalImages = [];
    
    // ===== FORM SUBMISSION ===== //
    blogForm.on('submit', function(e) {
        e.preventDefault();
        
        // Lấy dữ liệu từ form
        const formData = {
            title: titleInput.val().trim(),
            summary: summaryInput.val().trim(),
            content: contentInput.val().trim(),
            featured_image: featuredImagePath.val(),
            additional_images: additionalImages,
            status: statusSelect.val()
        };
        
        // Validate dữ liệu
        if (!formData.title) {
            showAlert('Vui lòng nhập tiêu đề bài viết', 'danger');
            titleInput.focus();
            return;
        }
        
        if (!formData.content) {
            showAlert('Vui lòng nhập nội dung bài viết', 'danger');
            contentInput.focus();
            return;
        }
        
        // Disable form và hiển thị loading
        const submitBtn = blogForm.find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Đang lưu...');
        
        // Gửi request tạo bài viết
        fetch('api/create_blog_post.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                
                // Reset form nếu lưu thành công
                blogForm[0].reset();
                resetImageUpload();
                resetMultipleImagesUpload();
                updatePreview();
                
                // Hiển thị thông tin bài viết đã tạo
                showAlert(`Bài viết "${formData.title}" đã được tạo thành công với ID: ${data.post_id}`, 'success');
                
            } else {
                showAlert(data.message || 'Có lỗi xảy ra khi tạo bài viết', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Có lỗi xảy ra khi kết nối đến server', 'danger');
        })
        .finally(() => {
            // Enable lại form
            submitBtn.prop('disabled', false).html(originalText);
        });
    });
    
    // ===== PREVIEW FUNCTIONALITY ===== //
    
    // Cập nhật preview khi nhập liệu
    titleInput.on('input', updatePreview);
    summaryInput.on('input', updatePreview);
    contentInput.on('input', updatePreview);
    statusSelect.on('change', updatePreview);
    
    // ===== IMAGE UPLOAD FUNCTIONALITY ===== //
    
    // Click vào upload area
    imageUploadArea.on('click', function() {
        featuredImageInput.click();
    });
    
    // Xử lý khi chọn file
    featuredImageInput.on('change', function() {
        const file = this.files[0];
        if (file) {
            uploadImage(file);
        }
    });
    
    // Drag and drop
    imageUploadArea.on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('dragover');
    });
    
    imageUploadArea.on('dragleave', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
    });
    
    imageUploadArea.on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
        
        const files = e.originalEvent.dataTransfer.files;
        if (files.length > 0) {
            uploadImage(files[0]);
        }
    });
    
    // Xóa hình ảnh
    removeImageBtn.on('click', function(e) {
        e.stopPropagation();
        resetImageUpload();
    });
    
    // ===== MULTIPLE IMAGES UPLOAD FUNCTIONALITY ===== //
    
    // Click vào multiple images upload area
    multipleImagesUploadArea.on('click', function() {
        multipleImagesInput.click();
    });
    
    // Xử lý khi chọn multiple files
    multipleImagesInput.on('change', function() {
        const files = Array.from(this.files);
        if (files.length > 0) {
            uploadMultipleImages(files);
        }
    });
    
    // Drag and drop cho multiple images
    multipleImagesUploadArea.on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('dragover');
    });
    
    multipleImagesUploadArea.on('dragleave', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
    });
    
    multipleImagesUploadArea.on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
        
        const files = Array.from(e.originalEvent.dataTransfer.files);
        if (files.length > 0) {
            uploadMultipleImages(files);
        }
    });
    
    // Nút xem trước
    previewBtn.on('click', function() {
        updatePreview();
        showAlert('Đã cập nhật xem trước', 'info');
    });
    
    // Nút làm mới
    resetBtn.on('click', function() {
        if (confirm('Bạn có chắc muốn làm mới form? Tất cả dữ liệu sẽ bị mất.')) {
            blogForm[0].reset();
            resetImageUpload();
            resetMultipleImagesUpload();
            updatePreview();
            showAlert('Đã làm mới form', 'info');
        }
    });
    
    // ===== HELPER FUNCTIONS ===== //
    
    function updatePreview() {
        const title = titleInput.val().trim();
        const summary = summaryInput.val().trim();
        const content = contentInput.val().trim();
        const featuredImage = featuredImagePath.val();
        const status = statusSelect.val();
        
        if (!title && !summary && !content) {
            // Hiển thị placeholder khi không có dữ liệu
            blogPreview.html(`
                <div class="text-center text-muted">
                    <i class="fas fa-file-alt fa-3x mb-3"></i>
                    <p>Nội dung xem trước sẽ hiển thị ở đây</p>
                </div>
            `);
            return;
        }
        
        // Tạo HTML cho preview
        let previewHTML = '';
        
        if (title) {
            previewHTML += `<h3>${escapeHtml(title)}</h3>`;
        }
        
        // Meta information
        const statusText = status === 'published' ? 'Đã xuất bản' : 'Bản nháp';
        const statusClass = status === 'published' ? 'published-badge' : 'draft-badge';
        
        previewHTML += `
            <div class="meta">
                <span class="badge ${statusClass} status-badge">${statusText}</span>
                <span class="ms-2">${new Date().toLocaleDateString('vi-VN')}</span>
            </div>
        `;
        
        if (featuredImage) {
            previewHTML += `<div class="mb-3"><img src="${escapeHtml(featuredImage)}" alt="Featured Image" class="img-fluid rounded" style="max-width: 100%; max-height: 200px; object-fit: cover;"></div>`;
        }
        
        if (summary) {
            previewHTML += `<p class="text-muted mb-3"><em>${escapeHtml(summary)}</em></p>`;
        }
        
        if (content) {
            // Chuyển đổi xuống dòng thành <br> và <p>
            const formattedContent = content
                .replace(/\n\n/g, '</p><p>')
                .replace(/\n/g, '<br>');
            
            previewHTML += `<div class="content"><p>${formattedContent}</p></div>`;
        }
        
        blogPreview.html(previewHTML);
    }
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // ===== IMAGE UPLOAD FUNCTIONS ===== //
    
    function uploadImage(file) {
        // Kiểm tra loại file
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showAlert('Loại file không được hỗ trợ. Chỉ chấp nhận JPG, PNG, GIF, WEBP', 'danger');
            return;
        }
        
        // Kiểm tra kích thước (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showAlert('File quá lớn. Kích thước tối đa là 5MB', 'danger');
            return;
        }
        
        // Hiển thị preview tạm thời
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.attr('src', e.target.result);
            imagePreview.show();
            $('.upload-placeholder').hide();
        };
        reader.readAsDataURL(file);
        
        // Upload file
        const formData = new FormData();
        formData.append('image', file);
        
        fetch('api/upload_blog_image.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                featuredImagePath.val(data.data.path);
                showAlert('Upload hình ảnh thành công', 'success');
                updatePreview();
            } else {
                showAlert(data.message || 'Lỗi upload hình ảnh', 'danger');
                resetImageUpload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Có lỗi xảy ra khi upload hình ảnh', 'danger');
            resetImageUpload();
        });
    }
    
    function resetImageUpload() {
        featuredImageInput.val('');
        featuredImagePath.val('');
        imagePreview.hide();
        $('.upload-placeholder').show();
        updatePreview();
    }
    
    // ===== MULTIPLE IMAGES UPLOAD FUNCTIONS ===== //
    
    function uploadMultipleImages(files) {
        // Kiểm tra số lượng file (tối đa 10 file)
        if (files.length > 10) {
            showAlert('Tối đa chỉ được upload 10 file cùng lúc', 'warning');
            files = files.slice(0, 10);
        }
        
        // Kiểm tra từng file
        const validFiles = [];
        const errors = [];
        
        files.forEach((file, index) => {
            // Kiểm tra loại file
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                errors.push(`File ${file.name}: Loại file không được hỗ trợ`);
                return;
            }
            
            // Kiểm tra kích thước (5MB)
            if (file.size > 5 * 1024 * 1024) {
                errors.push(`File ${file.name}: Quá lớn (tối đa 5MB)`);
                return;
            }
            
            validFiles.push(file);
        });
        
        // Hiển thị lỗi nếu có
        if (errors.length > 0) {
            showAlert('Một số file không hợp lệ:\n' + errors.join('\n'), 'warning');
        }
        
        if (validFiles.length === 0) {
            return;
        }
        
        // Upload từng file
        validFiles.forEach((file, index) => {
            uploadSingleImage(file, index);
        });
    }
    
    function uploadSingleImage(file, index) {
        // Hiển thị preview tạm thời
        const reader = new FileReader();
        reader.onload = function(e) {
            const imageId = 'temp-image-' + Date.now() + '-' + index;
            const imageHtml = `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="image-item" id="${imageId}">
                        <img src="${e.target.result}" alt="${file.name}" class="img-fluid">
                        <div class="image-item-overlay">
                            <div class="image-item-actions">
                                <button type="button" class="btn btn-sm btn-danger" onclick="removeImageItem('${imageId}')">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="image-item-info">
                            <div class="image-item-name">${file.name}</div>
                            <div class="image-item-size">${formatFileSize(file.size)}</div>
                        </div>
                        <div class="upload-progress-item">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            multipleImagesGrid.append(imageHtml);
            multipleImagesPreview.show();
            $('.upload-placeholder').hide();
        };
        reader.readAsDataURL(file);
        
        // Upload file
        const formData = new FormData();
        formData.append('images[]', file);
        
        fetch('api/upload_multiple_blog_images.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.uploaded_files.length > 0) {
                const uploadedFile = data.data.uploaded_files[0];
                
                // Cập nhật progress bar
                const progressBar = $(`#temp-image-${Date.now()}-${index} .progress-bar`);
                progressBar.css('width', '100%').removeClass('progress-bar-animated');
                
                // Thêm vào danh sách uploaded images
                additionalImages.push({
                    path: uploadedFile.path,
                    name: uploadedFile.original_name,
                    size: uploadedFile.size,
                    type: uploadedFile.type
                });
                
                showAlert(`Upload thành công: ${uploadedFile.original_name}`, 'success');
            } else {
                showAlert(data.message || 'Lỗi upload file', 'danger');
                // Xóa preview nếu upload thất bại
                $(`#temp-image-${Date.now()}-${index}`).remove();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Có lỗi xảy ra khi upload file', 'danger');
            // Xóa preview nếu upload thất bại
            $(`#temp-image-${Date.now()}-${index}`).remove();
        });
    }
    
    function removeImageItem(imageId) {
        const imageItem = $(`#${imageId}`);
        const imageName = imageItem.find('.image-item-name').text();
        
        // Xóa khỏi danh sách additionalImages
        additionalImages = additionalImages.filter(img => img.name !== imageName);
        
        // Xóa khỏi DOM
        imageItem.closest('.col-md-6').remove();
        
        // Ẩn preview nếu không còn image nào
        if (multipleImagesGrid.children().length === 0) {
            multipleImagesPreview.hide();
            $('.upload-placeholder').show();
        }
        
        showAlert(`Đã xóa: ${imageName}`, 'info');
    }
    
    function resetMultipleImagesUpload() {
        multipleImagesInput.val('');
        additionalImages = [];
        multipleImagesPreview.hide();
        multipleImagesGrid.empty();
        $('.upload-placeholder').show();
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    function showAlert(message, type = 'info') {
        const alertId = 'alert-' + Date.now();
        const alertHtml = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas fa-${getAlertIcon(type)} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        alertContainer.append(alertHtml);
        
        // Tự động ẩn sau 5 giây
        setTimeout(() => {
            $(`#${alertId}`).fadeOut();
        }, 5000);
    }
    
    function getAlertIcon(type) {
        const icons = {
            'success': 'check-circle',
            'danger': 'exclamation-triangle',
            'warning': 'exclamation-circle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
    
    // ===== KEYBOARD SHORTCUTS ===== //
    
    $(document).on('keydown', function(e) {
        // Ctrl + Enter để submit form
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            blogForm.submit();
        }
        
        // Ctrl + S để lưu
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            blogForm.submit();
        }
        
        // Ctrl + P để xem trước
        if (e.ctrlKey && e.key === 'p') {
            e.preventDefault();
            updatePreview();
            showAlert('Đã cập nhật xem trước', 'info');
        }
    });
    
    // ===== INITIALIZATION ===== //
    
    // Cập nhật preview lần đầu
    updatePreview();
    
    // Focus vào title input
    titleInput.focus();
    
}); 