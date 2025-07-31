<?php
/**
 * IT CRM - Reset Password Page
 * File: reset_password.php
 * Mục đích: Trang đặt mật khẩu mới - bước 4: Sau khi thanh toán thành công
 * Tác giả: IT Support Team
 */

// Include các file cần thiết
require_once 'includes/session.php';
require_once 'config/db.php';

// Load Momo API configuration
$momo_config = include 'config/momo_api.php';

// Kiểm tra session
$email = $_SESSION[$momo_config['session_prefix'] . 'email'] ?? null;
$order_id = $_SESSION[$momo_config['session_prefix'] . 'order_id'] ?? null;
$user_id = $_SESSION[$momo_config['session_prefix'] . 'user_id'] ?? null;

if (!$email || !$order_id || !$user_id) {
    header('Location: forgot.php');
    exit();
}

// Kiểm tra trạng thái thanh toán
try {
    $stmt = $pdo->prepare("
        SELECT * FROM password_reset_requests 
        WHERE order_id = ? AND email = ? AND status = 'paid' 
        AND expires_at > NOW()
    ");
    $stmt->execute([$order_id, $email]);
    $reset_request = $stmt->fetch();
    
    if (!$reset_request) {
        // Xóa session và redirect về trang quên mật khẩu
        unset($_SESSION[$momo_config['session_prefix'] . 'email']);
        unset($_SESSION[$momo_config['session_prefix'] . 'order_id']);
        unset($_SESSION[$momo_config['session_prefix'] . 'user_id']);
        
        header('Location: forgot.php?error=payment_required');
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Database error in reset_password.php: " . $e->getMessage());
    header('Location: forgot.php?error=database_error');
    exit();
}

$error_message = '';
$success_message = '';

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    // Validation
    if (empty($new_password)) {
        $error_message = 'Vui lòng nhập mật khẩu mới.';
    } elseif (strlen($new_password) < 6) {
        $error_message = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'Mật khẩu xác nhận không khớp.';
    } else {
        try {
            // Hash mật khẩu mới
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Cập nhật mật khẩu trong database
            $stmt = $pdo->prepare("
                UPDATE staffs 
                SET password = ? 
                WHERE id = ? AND email = ?
            ");
            $stmt->execute([$hashed_password, $user_id, $email]);
            
            if ($stmt->rowCount() > 0) {
                // Cập nhật trạng thái reset thành công
                $stmt = $pdo->prepare("
                    UPDATE password_reset_requests 
                    SET status = 'completed', completed_at = NOW() 
                    WHERE order_id = ?
                ");
                $stmt->execute([$order_id]);
                
                // Xóa session
                unset($_SESSION[$momo_config['session_prefix'] . 'email']);
                unset($_SESSION[$momo_config['session_prefix'] . 'order_id']);
                unset($_SESSION[$momo_config['session_prefix'] . 'user_id']);
                
                // Redirect đến trang thành công
                header('Location: reset_password_done.php');
                exit();
                
            } else {
                $error_message = 'Không thể cập nhật mật khẩu. Vui lòng thử lại.';
            }
            
        } catch (PDOException $e) {
            error_log("Database error updating password: " . $e->getMessage());
            $error_message = 'Có lỗi xảy ra khi cập nhật mật khẩu. Vui lòng thử lại sau.';
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
    <title>Đặt Mật Khẩu Mới - IT Services Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/login.css?v=<?php echo filemtime('assets/css/login.css'); ?>">
    <link rel="stylesheet" href="assets/css/alert.css?v=<?php echo filemtime('assets/css/alert.css'); ?>">
    
    <style>
        .reset-password-card {
            max-width: 500px;
            margin: 50px auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 15px;
            border: none;
        }
        
        .reset-password-header {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        
        .reset-password-body {
            padding: 2rem;
        }
        
        .user-info {
            background: #e8f4fd;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
            border-left: 4px solid #007bff;
        }
        
        .user-info h6 {
            color: #007bff;
            margin-bottom: 0.5rem;
        }
        
        .password-strength {
            margin-top: 0.5rem;
        }
        
        .strength-bar {
            height: 4px;
            border-radius: 2px;
            background: #e9ecef;
            overflow: hidden;
            margin-top: 0.25rem;
        }
        
        .strength-fill {
            height: 100%;
            transition: width 0.3s ease, background-color 0.3s ease;
        }
        
        .strength-text {
            font-size: 0.75rem;
            margin-top: 0.25rem;
        }
        
        .password-requirements {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
            font-size: 0.875rem;
        }
        
        .password-requirements h6 {
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .password-requirements ul {
            margin-bottom: 0;
            padding-left: 1.2rem;
        }
        
        .password-requirements li {
            margin-bottom: 0.25rem;
            color: #6c757d;
        }
        
        .requirement-met {
            color: #28a745 !important;
        }
        
        .requirement-not-met {
            color: #dc3545 !important;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card reset-password-card">
                    <div class="reset-password-header">
                        <h3><i class="fas fa-key me-2"></i>Đặt Mật Khẩu Mới</h3>
                        <p class="mb-0">Tạo mật khẩu mới cho tài khoản của bạn</p>
                    </div>
                    
                    <div class="reset-password-body">
                        <!-- User Info -->
                        <div class="user-info">
                            <h6><i class="fas fa-user me-1"></i>Thông tin tài khoản</h6>
                            <div class="row">
                                <div class="col-6">Email:</div>
                                <div class="col-6"><?php echo htmlspecialchars($email); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-6">Mã đơn hàng:</div>
                                <div class="col-6"><?php echo htmlspecialchars($order_id); ?></div>
                            </div>
                        </div>
                        
                        <!-- Password Requirements -->
                        <div class="password-requirements">
                            <h6><i class="fas fa-shield-alt me-1"></i>Yêu cầu mật khẩu</h6>
                            <ul>
                                <li id="req-length">Ít nhất 6 ký tự</li>
                                <li id="req-uppercase">Có chữ hoa</li>
                                <li id="req-lowercase">Có chữ thường</li>
                                <li id="req-number">Có số</li>
                                <li id="req-special">Có ký tự đặc biệt</li>
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
                        
                        <!-- Reset Password Form -->
                        <form method="POST" action="" id="resetPasswordForm">
                            <div class="mb-3">
                                <label for="new_password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>Mật khẩu mới
                                </label>
                                <input type="password" 
                                       class="form-control form-control-lg" 
                                       id="new_password" 
                                       name="new_password" 
                                       placeholder="Nhập mật khẩu mới"
                                       required>
                                <div class="password-strength">
                                    <div class="strength-bar">
                                        <div class="strength-fill" id="strengthFill" style="width: 0%; background-color: #e9ecef;"></div>
                                    </div>
                                    <div class="strength-text" id="strengthText">Độ mạnh mật khẩu</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>Xác nhận mật khẩu
                                </label>
                                <input type="password" 
                                       class="form-control form-control-lg" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       placeholder="Nhập lại mật khẩu mới"
                                       required>
                                <div class="form-text">
                                    Nhập lại mật khẩu để xác nhận
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                    <i class="fas fa-save me-2"></i>Lưu mật khẩu mới
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
    
    <script>
        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = '';
            
            // Length check
            if (password.length >= 6) strength += 20;
            if (password.length >= 8) strength += 20;
            
            // Character type checks
            if (/[a-z]/.test(password)) strength += 20;
            if (/[A-Z]/.test(password)) strength += 20;
            if (/[0-9]/.test(password)) strength += 20;
            if (/[^A-Za-z0-9]/.test(password)) strength += 20;
            
            // Cap at 100%
            strength = Math.min(strength, 100);
            
            // Determine strength level
            if (strength < 20) {
                feedback = 'Rất yếu';
            } else if (strength < 40) {
                feedback = 'Yếu';
            } else if (strength < 60) {
                feedback = 'Trung bình';
            } else if (strength < 80) {
                feedback = 'Mạnh';
            } else {
                feedback = 'Rất mạnh';
            }
            
            return { strength, feedback };
        }
        
        // Update password requirements
        function updateRequirements(password) {
            const requirements = {
                length: password.length >= 6,
                uppercase: /[A-Z]/.test(password),
                lowercase: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };
            
            // Update requirement indicators
            document.getElementById('req-length').className = requirements.length ? 'requirement-met' : 'requirement-not-met';
            document.getElementById('req-uppercase').className = requirements.uppercase ? 'requirement-met' : 'requirement-not-met';
            document.getElementById('req-lowercase').className = requirements.lowercase ? 'requirement-met' : 'requirement-not-met';
            document.getElementById('req-number').className = requirements.number ? 'requirement-met' : 'requirement-not-met';
            document.getElementById('req-special').className = requirements.special ? 'requirement-met' : 'requirement-not-met';
        }
        
        // Update password strength indicator
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const { strength, feedback } = checkPasswordStrength(password);
            
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            strengthFill.style.width = strength + '%';
            strengthText.textContent = feedback;
            
            // Update color based on strength
            if (strength < 20) {
                strengthFill.style.backgroundColor = '#dc3545';
            } else if (strength < 40) {
                strengthFill.style.backgroundColor = '#fd7e14';
            } else if (strength < 60) {
                strengthFill.style.backgroundColor = '#ffc107';
            } else if (strength < 80) {
                strengthFill.style.backgroundColor = '#17a2b8';
            } else {
                strengthFill.style.backgroundColor = '#28a745';
            }
            
            // Update requirements
            updateRequirements(password);
        });
        
        // Form validation
        document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword.length < 6) {
                e.preventDefault();
                alert('Mật khẩu phải có ít nhất 6 ký tự.');
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Mật khẩu xác nhận không khớp.');
                return false;
            }
            
            // Disable submit button to prevent double submission
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...';
        });
    </script>
</body>
</html> 