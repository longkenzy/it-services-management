<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * IT CRM - Dashboard Page
 * File: dashboard.php
 * Mục đích: Trang dashboard chính với bảo vệ authentication
 * Tác giả: IT Support Team
 */

// Include các file cần thiết
require_once 'includes/session.php';

// Bảo vệ trang - yêu cầu đăng nhập
requireLogin();

// Lấy thông tin user hiện tại
$current_user = getCurrentUser();

// Kiểm tra nếu không có thông tin user (không bao giờ xảy ra nếu requireLogin() hoạt động đúng)
if (!$current_user) {
    redirectToLogin('Phiên đăng nhập không hợp lệ.');
}

// Lấy flash messages nếu có
$flash_messages = getFlashMessages();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <title>IT Services Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo filemtime('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="assets/css/alert.css?v=<?php echo filemtime('assets/css/alert.css'); ?>">
    
    <!-- No Border Radius Override -->
    <link rel="stylesheet" href="assets/css/no-border-radius.css?v=<?php echo filemtime('assets/css/no-border-radius.css'); ?>">
</head>
<body>
    <?php 
    // Include header chung
    include 'includes/header.php'; 
    ?>
    
    <!-- Flash messages will be shown via JavaScript alert system -->
    
    <!-- ===== MAIN CONTENT ===== -->
    <main class="main-content">
        <div class="container-fluid px-4 py-4">
            
            <!-- Page Title -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h1 class="page-title mb-0">Dashboard</h1>
                        <p class="text-muted mb-0">Chào mừng <strong><?php echo htmlspecialchars($current_user['fullname']); ?></strong> đến với IT Services Management</p>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>
                            Tạo case mới
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- User Info Card -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-user-circle me-2 text-primary"></i>
                                Thông tin đăng nhập
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Tên đăng nhập:</strong> <?php echo htmlspecialchars($current_user['username']); ?></p>
                                    <p class="mb-2"><strong>Họ và tên:</strong> <?php echo htmlspecialchars($current_user['fullname']); ?></p>
                                    <p class="mb-2"><strong>Vai trò:</strong> 
                                        <span class="badge bg-<?php echo $current_user['role'] === 'admin' ? 'danger' : ($current_user['role'] === 'leader' ? 'warning' : 'info'); ?>">
                                            <?php echo ucfirst($current_user['role']); ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Thời gian đăng nhập:</strong> <?php echo getLoginTimeFormatted(); ?></p>
                                    <p class="mb-2"><strong>Hoạt động cuối:</strong> <?php echo getLastActivityFormatted(); ?></p>
                                    <p class="mb-2"><strong>ID phiên:</strong> <code><?php echo session_id(); ?></code></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Access Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Case Nội Bộ
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">Quản lý</div>
                                </div>
                                <div class="col-auto">
                                    <a href="internal_cases.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-building me-1"></i>Truy cập
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Case Triển Khai
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">Quản lý</div>
                                </div>
                                <div class="col-auto">
                                    <a href="deployment_requests.php" class="btn btn-success btn-sm">
                                        <i class="fas fa-rocket me-1"></i>Truy cập
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Nhân Sự
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">Quản lý</div>
                                </div>
                                <div class="col-auto">
                                    <a href="staff.php" class="btn btn-info btn-sm">
                                        <i class="fas fa-users me-1"></i>Truy cập
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Thông Tin
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">Cá nhân</div>
                                </div>
                                <div class="col-auto">
                                    <a href="profile.php" class="btn btn-warning btn-sm">
                                        <i class="fas fa-user me-1"></i>Truy cập
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Demo Content -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Chào mừng đến với IT Services Management</h5>
                            <p class="card-text">
                                Đây là trang dashboard chính của hệ thống CRM. Bạn đã đăng nhập thành công với các tính năng:
                            </p>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i> ✅ Đăng nhập với database MySQL</li>
                                <li><i class="fas fa-check text-success me-2"></i> ✅ Session management bảo mật</li>
                                <li><i class="fas fa-check text-success me-2"></i> ✅ Phân quyền theo role</li>
                                <li><i class="fas fa-check text-success me-2"></i> ✅ Remember me functionality</li>
                                <li><i class="fas fa-check text-success me-2"></i> ✅ Auto logout khi hết hạn</li>
                                <li><i class="fas fa-check text-success me-2"></i> ✅ User activity logging</li>
                                <li><i class="fas fa-check text-success me-2"></i> ✅ CSRF protection</li>
                            </ul>
                            
                            <?php if (isAdmin()): ?>
                                <div class="card-text mt-3">
                                    <i class="fas fa-crown me-2 text-primary"></i>
                                    <strong>Quyền Admin:</strong> Bạn có quyền truy cập đầy đủ tất cả chức năng của hệ thống.
                                </div>
                            <?php elseif (isLeader()): ?>
                                <div class="card-text mt-3">
                                    <i class="fas fa-user-tie me-2 text-warning"></i>
                                    <strong>Quyền Leader:</strong> Bạn có quyền quản lý nhân viên và case.
                                </div>
                            <?php else: ?>
                                <div class="card-text mt-3">
                                    <i class="fas fa-user me-2 text-secondary"></i>
                                    <strong>Quyền User:</strong> Bạn có thể xem và xử lý các case được giao.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </main>
    
    <!-- jQuery (load trước) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Alert System -->
    <script src="assets/js/alert.js?v=<?php echo filemtime('assets/js/alert.js'); ?>"></script>
    
    <!-- Custom JavaScript -->
    <script src="assets/js/dashboard.js?v=<?php echo filemtime('assets/js/dashboard.js'); ?>"></script>
    
    <script>
    $(document).ready(function() {
        // Flash messages
        <?php if (!empty($flash_messages)): ?>
            <?php foreach ($flash_messages as $message): ?>
                showAlert('<?php echo addslashes($message['message']); ?>', '<?php echo $message['type']; ?>');
            <?php endforeach; ?>
        <?php endif; ?>
        
        // ===== DROPDOWN ACTIONS ===== //
        // Các dropdown actions đã được xử lý trong dashboard.js
        
        // Dashboard specific functionality
        
        // Dummy handlers để tránh xung đột
        $(document).on('click', '[data-action="profile"]', function(e) {
            e.preventDefault();
            $('#profileModal').modal('show');
        });
        
        // Xử lý click "Cài đặt"
        $(document).on('click', '[data-action="settings"]', function(e) {
            e.preventDefault();
            showInfo('Tính năng đang phát triển...');
        });
        
        // Xử lý click "Thông báo"
        $(document).on('click', '[data-action="notifications"]', function(e) {
            e.preventDefault();
            showInfo('Tính năng đang phát triển...');
        });
        
        // Xử lý click "Đăng xuất"
        $(document).on('click', '[data-action="logout"]', function(e) {
            e.preventDefault();
            showInfo('Đang đăng xuất...');
            setTimeout(function() {
                window.location.href = 'auth/logout.php';
            }, 1000);
        });
        
        // ===== CHANGE PASSWORD MODAL ===== //
        
        // Xử lý submit form đổi mật khẩu
        $('#changePasswordForm').on('submit', function(e) {
            e.preventDefault();
            
            const oldPassword = $('#old_password').val().trim();
            const newPassword = $('#new_password').val().trim();
            const confirmPassword = $('#confirm_password').val().trim();
            
            // Validation
            if (!oldPassword || !newPassword || !confirmPassword) {
                showChangePasswordError('Vui lòng điền đầy đủ thông tin');
                return;
            }
            
            if (newPassword.length < 6) {
                showChangePasswordError('Mật khẩu mới phải có ít nhất 6 ký tự');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                showChangePasswordError('Mật khẩu mới và xác nhận mật khẩu không khớp');
                return;
            }
            
            if (oldPassword === newPassword) {
                showChangePasswordError('Mật khẩu mới phải khác mật khẩu cũ');
                return;
            }
            
            // Disable submit button
            const submitBtn = $('#changePasswordForm button[type="submit"]');
            const originalText = submitBtn.text();
            submitBtn.prop('disabled', true).text('Đang xử lý...');
            
            // AJAX request
            $.ajax({
                url: 'change_password.php',
                method: 'POST',
                data: {
                    old_password: oldPassword,
                    new_password: newPassword,
                    confirm_password: confirmPassword
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Chỉ hiển thị 1 thông báo thành công
                        showSuccess('Đổi mật khẩu thành công!');
                        // Đóng modal sau 2 giây
                        setTimeout(function() {
                            $('#changePasswordModal').modal('hide');
                        }, 2000);
                    } else {
                        showChangePasswordError(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Change password error:', error);
                    showChangePasswordError('Có lỗi xảy ra. Vui lòng thử lại sau.');
                },
                complete: function() {
                    // Re-enable submit button
                    submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = '';
            
            if (password.length >= 6) strength += 25;
            if (password.length >= 8) strength += 25;
            if (/[a-z]/.test(password)) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;
            if (/[^A-Za-z0-9]/.test(password)) strength += 25;
            
            // Cap at 100%
            strength = Math.min(strength, 100);
            
            // Determine strength level
            if (strength < 25) {
                feedback = 'Rất yếu';
            } else if (strength < 50) {
                feedback = 'Yếu';
            } else if (strength < 75) {
                feedback = 'Trung bình';
            } else if (strength < 100) {
                feedback = 'Mạnh';
            } else {
                feedback = 'Rất mạnh';
            }
            
            return { strength, feedback };
        }
        
        // Update password strength indicator
        $('#new_password').on('input', function() {
            const password = $(this).val();
            const { strength, feedback } = checkPasswordStrength(password);
            
            const strengthFill = $('#strengthFill');
            const strengthText = $('#strengthText');
            
            strengthFill.removeClass('weak fair good strong');
            strengthText.removeClass('text-danger text-warning text-info text-success');
            
            if (strength < 25) {
                strengthFill.addClass('weak');
                strengthText.addClass('text-danger');
            } else if (strength < 50) {
                strengthFill.addClass('fair');
                strengthText.addClass('text-warning');
            } else if (strength < 75) {
                strengthFill.addClass('good');
                strengthText.addClass('text-info');
            } else {
                strengthFill.addClass('strong');
                strengthText.addClass('text-success');
            }
            
            strengthText.text(feedback);
        });
        
        // Helper functions cho change password modal
        function showChangePasswordError(message) {
            showError(message);
        }
        
        function showChangePasswordSuccess(message) {
            showSuccess(message);
        }
        
        // Reset modal khi đóng
        $('#changePasswordModal').on('hidden.bs.modal', function() {
            $('#changePasswordForm')[0].reset();
            $('#changePasswordForm button[type="submit"]').prop('disabled', false).text('Đổi mật khẩu');
        });
    });
    </script>

    <!-- ===== MODAL PROFILE (UPLOAD AVATAR) ===== -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form id="avatarUploadForm" method="post" enctype="multipart/form-data" action="upload_avatar.php">
            <div class="modal-header">
              <h5 class="modal-title" id="profileModalLabel"><i class="fas fa-user me-2"></i>Thông tin cá nhân</h5>
                                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" tabindex="-1"></button>
            </div>
            <div class="modal-body">
              <div class="text-center mb-3">
                <img id="profileAvatarPreview" src="<?php echo htmlspecialchars($current_user['avatar'] ?? 'assets/images/default-avatar.svg'); ?>" alt="Avatar" class="rounded-circle border" style="width: 100px; height: 100px; object-fit: cover;">
              </div>
              <div class="mb-3">
                <label for="avatarInput" class="form-label">Chọn ảnh đại diện mới</label>
                <input class="form-control" type="file" id="avatarInput" name="avatar" accept="image/*">
              </div>
              <div class="mb-3">
                <label class="form-label">Tên đăng nhập</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['username']); ?>" disabled>
              </div>
              <div class="mb-3">
                <label class="form-label">Họ và tên</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['fullname']); ?>" disabled>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
              <button type="submit" class="btn btn-primary">Lưu ảnh đại diện</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script>
    // Hiển thị modal khi click "Thông tin cá nhân"
    $(document).on('click', '[data-action="profile"]', function(e) {
      e.preventDefault();
      $('#profileModal').modal('show');
    });
    // Xem trước ảnh upload
    $('#avatarInput').on('change', function() {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          $('#profileAvatarPreview').attr('src', e.target.result);
        };
        reader.readAsDataURL(file);
      }
    });
    </script>


</body>
</html> 