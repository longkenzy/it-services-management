<?php
/**
 * IT CRM - Check Payment Page
 * File: check_payment.php
 * Mục đích: Trang kiểm tra thanh toán Momo - bước 3: Xác minh giao dịch
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
    error_log("Database error in check_payment.php: " . $e->getMessage());
    header('Location: forgot.php?error=database_error');
    exit();
}

// Hàm kiểm tra giao dịch Momo
function checkMomoTransaction($phone, $amount, $content) {
    global $momo_config;
    
    $url = $momo_config['transaction_api_url'];
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
        if ($result && isset($result['success']) && $result['success']) {
            return [
                'success' => true,
                'transaction_id' => $result['transactionId'] ?? null,
                'message' => $result['message'] ?? 'Giao dịch thành công'
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => 'Không tìm thấy giao dịch hoặc API lỗi'
    ];
}

// Xử lý kiểm tra thanh toán
$payment_status = null;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['check'])) {
    // Kiểm tra giao dịch Momo
    $payment_result = checkMomoTransaction(
        $momo_config['phone_number'],
        $momo_config['amount'],
        $reset_request['payment_content']
    );
    
    if ($payment_result['success']) {
        try {
            // Cập nhật trạng thái thanh toán thành công
            $stmt = $pdo->prepare("
                UPDATE password_reset_requests 
                SET status = 'paid', paid_at = NOW() 
                WHERE order_id = ?
            ");
            $stmt->execute([$order_id]);
            
            // Lưu thông tin giao dịch
            $stmt = $pdo->prepare("
                INSERT INTO momo_transactions 
                (order_id, transaction_id, amount, payment_content, status, momo_response) 
                VALUES (?, ?, ?, ?, 'success', ?)
            ");
            $stmt->execute([
                $order_id,
                $payment_result['transaction_id'],
                $momo_config['amount'],
                $reset_request['payment_content'],
                json_encode($payment_result)
            ]);
            
            $payment_status = 'success';
            
        } catch (PDOException $e) {
            error_log("Database error updating payment status: " . $e->getMessage());
            $error_message = 'Có lỗi xảy ra khi cập nhật trạng thái thanh toán.';
        }
        
    } else {
        $payment_status = 'failed';
        $error_message = $payment_result['message'];
        
        // Lưu thông tin giao dịch thất bại
        try {
            $stmt = $pdo->prepare("
                INSERT INTO momo_transactions 
                (order_id, amount, payment_content, status, momo_response) 
                VALUES (?, ?, ?, 'failed', ?)
            ");
            $stmt->execute([
                $order_id,
                $momo_config['amount'],
                $reset_request['payment_content'],
                json_encode($payment_result)
            ]);
        } catch (PDOException $e) {
            error_log("Database error saving failed transaction: " . $e->getMessage());
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
    <title>Kiểm Tra Thanh Toán - IT Services Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/login.css?v=<?php echo filemtime('assets/css/login.css'); ?>">
    <link rel="stylesheet" href="assets/css/alert.css?v=<?php echo filemtime('assets/css/alert.css'); ?>">
    
    <style>
        .check-payment-card {
            max-width: 600px;
            margin: 50px auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 15px;
            border: none;
        }
        
        .check-payment-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
            text-align: center;
        }
        
        .check-payment-body {
            padding: 2rem;
        }
        
        .payment-status {
            text-align: center;
            padding: 2rem;
            border-radius: 10px;
            margin: 1rem 0;
        }
        
        .payment-status.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .payment-status.failed {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .payment-status.pending {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        
        .status-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .transaction-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1rem 0;
        }
        
        .transaction-details h6 {
            color: #495057;
            margin-bottom: 1rem;
        }
        
        .transaction-details .row {
            margin-bottom: 0.5rem;
        }
        
        .transaction-details .col-6 {
            font-weight: 500;
        }
        
        .transaction-details .col-6:last-child {
            text-align: right;
            color: #6c757d;
        }
        
        .loading-spinner {
            text-align: center;
            padding: 2rem;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card check-payment-card">
                    <div class="check-payment-header">
                        <h3><i class="fas fa-search me-2"></i>Kiểm Tra Thanh Toán</h3>
                        <p class="mb-0">Đang xác minh giao dịch Momo...</p>
                    </div>
                    
                    <div class="check-payment-body">
                        <!-- Transaction Details -->
                        <div class="transaction-details">
                            <h6><i class="fas fa-info-circle me-1"></i>Thông tin giao dịch</h6>
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
                        
                        <?php if ($payment_status === null): ?>
                            <!-- Loading State -->
                            <div class="loading-spinner">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Đang kiểm tra...</span>
                                </div>
                                <p class="mt-3 text-muted">Đang kiểm tra giao dịch Momo...</p>
                                
                                <!-- Auto-check after 3 seconds -->
                                <script>
                                    setTimeout(function() {
                                        window.location.href = 'check_payment.php?check=1';
                                    }, 3000);
                                </script>
                            </div>
                            
                            <!-- Manual Check Button -->
                            <div class="d-grid gap-2">
                                <a href="check_payment.php?check=1" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sync-alt me-2"></i>Kiểm tra ngay
                                </a>
                                <a href="pay_momo.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Quay lại trang thanh toán
                                </a>
                            </div>
                            
                        <?php elseif ($payment_status === 'success'): ?>
                            <!-- Success State -->
                            <div class="payment-status success">
                                <div class="status-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h4>Thanh toán thành công!</h4>
                                <p>Giao dịch đã được xác minh. Bạn có thể tiếp tục đặt mật khẩu mới.</p>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="reset_password.php" class="btn btn-success btn-lg">
                                    <i class="fas fa-key me-2"></i>Đặt mật khẩu mới
                                </a>
                            </div>
                            
                        <?php elseif ($payment_status === 'failed'): ?>
                            <!-- Failed State -->
                            <div class="payment-status failed">
                                <div class="status-icon">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                                <h4>Thanh toán chưa thành công</h4>
                                <p><?php echo htmlspecialchars($error_message); ?></p>
                                <p class="mb-0"><strong>Lưu ý:</strong> Giao dịch có thể mất vài phút để hiển thị. Vui lòng thử lại sau.</p>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="check_payment.php?check=1" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sync-alt me-2"></i>Kiểm tra lại
                                </a>
                                <a href="pay_momo.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Quay lại trang thanh toán
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Error Message -->
                        <?php if ($error_message && $payment_status !== 'failed'): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
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