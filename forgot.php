<?php
/**
 * IT CRM - Forgot Password Page
 * File: forgot.php
 * Mục đích: Trang quên mật khẩu - bước 1: Nhập email
 * Tác giả: IT Support Team
 */

// Include các file cần thiết
require_once 'includes/session.php';
require_once 'config/db.php';

// Load Momo API configuration
$momo_config = include 'config/momo_api.php';

// Nếu đã đăng nhập, redirect về dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    // Validation
    if (empty($email)) {
        $error_message = 'Vui lòng nhập email của bạn.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Email không hợp lệ.';
    } else {
        try {
            // Kiểm tra email có tồn tại trong database không
            $stmt = $pdo->prepare("SELECT id, username, fullname FROM staffs WHERE email = ? AND resigned = 0");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Tạo order_id duy nhất
                $order_id = $momo_config['order_prefix'] . '_' . time() . '_' . rand(1000, 9999);
                $payment_content = $momo_config['order_prefix'] . '-' . $order_id;
                
                // Lưu yêu cầu reset vào database
                $stmt = $pdo->prepare("
                    INSERT INTO password_reset_requests 
                    (email, order_id, amount, payment_content, status, expires_at) 
                    VALUES (?, ?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 30 MINUTE))
                ");
                $stmt->execute([
                    $email,
                    $order_id,
                    $momo_config['amount'],
                    $payment_content
                ]);
                
                // Lưu thông tin vào session
                $_SESSION[$momo_config['session_prefix'] . 'email'] = $email;
                $_SESSION[$momo_config['session_prefix'] . 'order_id'] = $order_id;
                $_SESSION[$momo_config['session_prefix'] . 'user_id'] = $user['id'];
                
                // Redirect đến trang thanh toán
                header('Location: pay_momo.php');
                exit();
                
            } else {
                $error_message = 'Email không tồn tại trong hệ thống hoặc tài khoản đã nghỉ việc.';
            }
            
        } catch (PDOException $e) {
            error_log("Database error in forgot.php: " . $e->getMessage());
            $error_message = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <title>Quên Mật Khẩu - IT Services Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/login.css?v=<?php echo filemtime('assets/css/login.css'); ?>">
    <link rel="stylesheet" href="assets/css/alert.css?v=<?php echo filemtime('assets/css/alert.css'); ?>">
    
    <style>
        .forgot-password-card {
            max-width: 500px;
            margin: 50px auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 15px;
            border: none;
        }
        
        .forgot-password-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        
        .forgot-password-body {
            padding: 2rem;
        }
        
        .payment-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
            border-left: 4px solid #007bff;
        }
        
        .payment-info h6 {
            color: #007bff;
            margin-bottom: 0.5rem;
        }
        
        .payment-info ul {
            margin-bottom: 0;
            padding-left: 1.2rem;
        }
        
        .payment-info li {
            margin-bottom: 0.25rem;
            color: #6c757d;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card forgot-password-card">
                    <div class="forgot-password-header">
                        <h3><i class="fas fa-lock me-2"></i>Quên Mật Khẩu</h3>
                        <p class="mb-0">Nhập email để khôi phục mật khẩu</p>
                    </div>
                    
                    <div class="forgot-password-body">
                        <!-- Payment Info -->
                        <div class="payment-info">
                            <h6><i class="fas fa-info-circle me-1"></i>Thông tin thanh toán</h6>
                            <ul>
                                <li>Phí khôi phục mật khẩu: <strong><?php echo number_format($momo_config['amount']); ?> VNĐ</strong></li>
                                <li>Thanh toán qua ứng dụng Momo</li>
                                <li>Thời gian xử lý: 30 phút</li>
                                <li>Bảo mật và an toàn</li>
                            </ul>
                        </div>
                        
                        <!-- Error/Success Messages -->
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success_message): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Forgot Password Form -->
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>Email đăng ký
                                </label>
                                <input type="email" 
                                       class="form-control form-control-lg" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       placeholder="Nhập email của bạn"
                                       required>
                                <div class="form-text">
                                    Email phải trùng với email đã đăng ký trong hệ thống
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-search me-2"></i>Tìm kiếm tài khoản
                                </button>
                            </div>
                        </form>
                        
                        <!-- Back to Login -->
                        <div class="text-center mt-4">
                            <a href="index.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-1"></i>Quay lại trang đăng nhập
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
</body>
</html> 