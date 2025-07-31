<?php
/**
 * IT CRM - Reset Password Done Page
 * File: reset_password_done.php
 * Mục đích: Trang hoàn thành reset mật khẩu - bước cuối
 * Tác giả: IT Support Team
 */

// Nếu đã đăng nhập, redirect về dashboard
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <title>Hoàn Thành Reset Mật Khẩu - IT Services Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/login.css?v=<?php echo filemtime('assets/css/login.css'); ?>">
    <link rel="stylesheet" href="assets/css/alert.css?v=<?php echo filemtime('assets/css/alert.css'); ?>">
    
    <style>
        .success-card {
            max-width: 500px;
            margin: 50px auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 15px;
            border: none;
        }
        
        .success-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        
        .success-body {
            padding: 2rem;
        }
        
        .success-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #28a745;
        }
        
        .success-message {
            text-align: center;
            padding: 2rem;
            background: #d4edda;
            border-radius: 10px;
            margin: 1rem 0;
            border: 1px solid #c3e6cb;
        }
        
        .next-steps {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        
        .next-steps h6 {
            color: #495057;
            margin-bottom: 1rem;
        }
        
        .next-steps ol {
            margin-bottom: 0;
            padding-left: 1.2rem;
        }
        
        .next-steps li {
            margin-bottom: 0.5rem;
            color: #6c757d;
        }
        
        .auto-redirect {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
            text-align: center;
        }
        
        .countdown {
            font-weight: bold;
            color: #856404;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card success-card">
                    <div class="success-header">
                        <h3><i class="fas fa-check-circle me-2"></i>Hoàn Thành!</h3>
                        <p class="mb-0">Mật khẩu đã được đặt lại thành công</p>
                    </div>
                    
                    <div class="success-body">
                        <!-- Success Message -->
                        <div class="success-message">
                            <div class="success-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h4>Chúc mừng!</h4>
                            <p class="mb-0">Mật khẩu của bạn đã được đặt lại thành công. Bạn có thể đăng nhập bằng mật khẩu mới ngay bây giờ.</p>
                        </div>
                        
                        <!-- Next Steps -->
                        <div class="next-steps">
                            <h6><i class="fas fa-list-ol me-1"></i>Bước tiếp theo</h6>
                            <ol>
                                <li>Quay lại trang đăng nhập</li>
                                <li>Đăng nhập bằng email và mật khẩu mới</li>
                                <li>Kiểm tra thông tin tài khoản</li>
                                <li>Bắt đầu sử dụng hệ thống</li>
                            </ol>
                        </div>
                        
                        <!-- Security Tips -->
                        <div class="next-steps">
                            <h6><i class="fas fa-shield-alt me-1"></i>Lưu ý bảo mật</h6>
                            <ul>
                                <li>Không chia sẻ mật khẩu với người khác</li>
                                <li>Đăng xuất khi không sử dụng</li>
                                <li>Thay đổi mật khẩu định kỳ</li>
                                <li>Báo cáo ngay nếu có hoạt động bất thường</li>
                            </ul>
                        </div>
                        
                        <!-- Auto Redirect -->
                        <div class="auto-redirect">
                            <i class="fas fa-clock me-2"></i>
                            <span>Tự động chuyển đến trang đăng nhập sau </span>
                            <span class="countdown" id="countdown">10</span>
                            <span> giây</span>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="d-grid gap-2">
                            <a href="index.php" class="btn btn-success btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập ngay
                            </a>
                            
                            <a href="forgot.php" class="btn btn-outline-secondary">
                                <i class="fas fa-question-circle me-2"></i>Trợ giúp khác
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="text-center mt-4">
                    <small class="text-muted">
                        © 2024 IT Services Management. All rights reserved.
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Scripts -->
    <script src="assets/js/alert.js"></script>
    
    <script>
        // Auto redirect countdown
        let countdown = 10;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(function() {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(timer);
                window.location.href = 'index.php';
            }
        }, 1000);
        
        // Show success message
        document.addEventListener('DOMContentLoaded', function() {
            // Có thể thêm hiệu ứng animation hoặc thông báo khác ở đây
            console.log('Password reset completed successfully!');
        });
    </script>
</body>
</html> 