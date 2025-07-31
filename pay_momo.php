<?php
/**
 * IT CRM - Momo Payment Page
 * File: pay_momo.php
 * Mục đích: Trang thanh toán Momo - bước 2: Hiển thị QR code và xử lý thanh toán
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

// Lấy thông tin yêu cầu reset từ database
try {
    $stmt = $pdo->prepare("
        SELECT * FROM password_reset_requests 
        WHERE order_id = ? AND email = ? AND status = 'pending' 
        AND expires_at > NOW()
    ");
    $stmt->execute([$order_id, $email]);
    $reset_request = $stmt->fetch();
    
    if (!$reset_request) {
        // Xóa session và redirect về trang quên mật khẩu
        unset($_SESSION[$momo_config['session_prefix'] . 'email']);
        unset($_SESSION[$momo_config['session_prefix'] . 'order_id']);
        unset($_SESSION[$momo_config['session_prefix'] . 'user_id']);
        
        header('Location: forgot.php?error=invalid_request');
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Database error in pay_momo.php: " . $e->getMessage());
    header('Location: forgot.php?error=database_error');
    exit();
}

// Tạo QR code Momo
function generateMomoQR($phone, $amount, $content) {
    global $momo_config;
    
    $url = $momo_config['qr_api_url'];
    $data = [
        'phone' => $phone,
        'amount' => $amount,
        'content' => $content
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $momo_config['timeout']);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: ' . $momo_config['user_agent']
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && $response) {
        $result = json_decode($response, true);
        if ($result && isset($result['qrCode'])) {
            return $result['qrCode'];
        }
    }
    
    return null;
}

// Tạo QR code
$qr_code = generateMomoQR(
    $momo_config['phone_number'],
    $momo_config['amount'],
    $reset_request['payment_content']
);

// Nếu không tạo được QR code, sử dụng fallback
if (!$qr_code) {
    $qr_code = 'data:image/svg+xml;base64,' . base64_encode('
        <svg width="300" height="300" xmlns="http://www.w3.org/2000/svg">
            <rect width="300" height="300" fill="#f8f9fa"/>
            <text x="150" y="150" text-anchor="middle" fill="#6c757d" font-family="Arial" font-size="14">
                QR Code không khả dụng
            </text>
        </svg>
    ');
}

$error_message = '';
$success_message = '';

// Xử lý nút "Tôi đã thanh toán"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_payment'])) {
    header('Location: check_payment.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <title>Thanh Toán Momo - IT Services Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/login.css?v=<?php echo filemtime('assets/css/login.css'); ?>">
    <link rel="stylesheet" href="assets/css/alert.css?v=<?php echo filemtime('assets/css/alert.css'); ?>">
    
    <style>
        .payment-card {
            max-width: 600px;
            margin: 30px auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 15px;
            border: none;
        }
        
        .payment-header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        
        .payment-body {
            padding: 2rem;
        }
        
        .qr-container {
            text-align: center;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 1rem 0;
        }
        
        .qr-code {
            max-width: 300px;
            max-height: 300px;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 10px;
            background: white;
        }
        
        .payment-details {
            background: #e8f4fd;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1rem 0;
            border-left: 4px solid #007bff;
        }
        
        .payment-details h6 {
            color: #007bff;
            margin-bottom: 1rem;
        }
        
        .payment-details .row {
            margin-bottom: 0.5rem;
        }
        
        .payment-details .col-6 {
            font-weight: 500;
        }
        
        .payment-details .col-6:last-child {
            text-align: right;
            color: #6c757d;
        }
        
        .countdown {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
            text-align: center;
        }
        
        .countdown .time-remaining {
            font-size: 1.2rem;
            font-weight: bold;
            color: #856404;
        }
        
        .instructions {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        
        .instructions ol {
            margin-bottom: 0;
            padding-left: 1.2rem;
        }
        
        .instructions li {
            margin-bottom: 0.5rem;
            color: #495057;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card payment-card">
                    <div class="payment-header">
                        <h3><i class="fas fa-qrcode me-2"></i>Thanh Toán Momo</h3>
                        <p class="mb-0">Quét mã QR để thanh toán phí khôi phục mật khẩu</p>
                    </div>
                    
                    <div class="payment-body">
                        <!-- Payment Details -->
                        <div class="payment-details">
                            <h6><i class="fas fa-info-circle me-1"></i>Thông tin thanh toán</h6>
                            <div class="row">
                                <div class="col-6">Email:</div>
                                <div class="col-6"><?php echo htmlspecialchars($email); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-6">Mã đơn hàng:</div>
                                <div class="col-6"><?php echo htmlspecialchars($order_id); ?></div>
                            </div>
                            <div class="row">
                                <div class="col-6">Số tiền:</div>
                                <div class="col-6"><strong><?php echo number_format($momo_config['amount']); ?> VNĐ</strong></div>
                            </div>
                            <div class="row">
                                <div class="col-6">Nội dung:</div>
                                <div class="col-6"><?php echo htmlspecialchars($reset_request['payment_content']); ?></div>
                            </div>
                        </div>
                        
                        <!-- Countdown Timer -->
                        <div class="countdown">
                            <i class="fas fa-clock me-2"></i>
                            <span>Thời gian còn lại: </span>
                            <span class="time-remaining" id="countdown">30:00</span>
                        </div>
                        
                        <!-- QR Code -->
                        <div class="qr-container">
                            <h5 class="mb-3"><i class="fas fa-mobile-alt me-2"></i>Quét mã QR bằng ứng dụng Momo</h5>
                            <img src="<?php echo $qr_code; ?>" alt="Momo QR Code" class="qr-code">
                            <p class="mt-3 text-muted">
                                <i class="fas fa-phone me-1"></i>
                                Số điện thoại nhận tiền: <strong><?php echo $momo_config['phone_number']; ?></strong>
                            </p>
                        </div>
                        
                        <!-- Instructions -->
                        <div class="instructions">
                            <h6><i class="fas fa-list-ol me-1"></i>Hướng dẫn thanh toán</h6>
                            <ol>
                                <li>Mở ứng dụng Momo trên điện thoại</li>
                                <li>Chọn tính năng "Quét mã QR"</li>
                                <li>Quét mã QR bên trên</li>
                                <li>Kiểm tra thông tin thanh toán</li>
                                <li>Nhập mật khẩu và xác nhận thanh toán</li>
                                <li>Quay lại trang này và nhấn "Tôi đã thanh toán"</li>
                            </ol>
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
                        
                        <!-- Action Buttons -->
                        <div class="d-grid gap-2">
                            <form method="POST" action="">
                                <button type="submit" name="check_payment" class="btn btn-success btn-lg">
                                    <i class="fas fa-check me-2"></i>Tôi đã thanh toán
                                </button>
                            </form>
                            
                            <a href="forgot.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Quay lại
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
        // Countdown Timer
        function startCountdown() {
            const countdownElement = document.getElementById('countdown');
            let timeLeft = 30 * 60; // 30 minutes in seconds
            
            const timer = setInterval(function() {
                const minutes = Math.floor(timeLeft / 60);
                const seconds = timeLeft % 60;
                
                countdownElement.textContent = 
                    (minutes < 10 ? '0' : '') + minutes + ':' + 
                    (seconds < 10 ? '0' : '') + seconds;
                
                if (timeLeft <= 0) {
                    clearInterval(timer);
                    countdownElement.textContent = 'Hết thời gian';
                    countdownElement.style.color = '#dc3545';
                    
                    // Redirect to forgot page after timeout
                    setTimeout(function() {
                        window.location.href = 'forgot.php?error=timeout';
                    }, 2000);
                }
                
                timeLeft--;
            }, 1000);
        }
        
        // Start countdown when page loads
        document.addEventListener('DOMContentLoaded', startCountdown);
    </script>
</body>
</html> 