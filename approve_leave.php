<?php
/**
 * Trang duyệt đơn nghỉ phép qua email
 * File: approve_leave.php
 * Mục đích: Xử lý duyệt đơn nghỉ phép thông qua link email
 */

// Include các file cần thiết
require_once 'config/db.php';
require_once 'config/email.php';

// Lấy tham số từ URL
$leave_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

$page_title = "Duyệt đơn nghỉ phép";
$error_message = '';
$success_message = '';
$leave_request = null;

// Xử lý duyệt đơn
if ($leave_id && $token) {
    try {
        // Xác thực token
        if (validateApproveToken($leave_id, $token)) {
            // Lấy thông tin đơn nghỉ phép
            $sql = "SELECT lr.*, s.fullname as requester_name, s.email as requester_email 
                    FROM leave_requests lr 
                    LEFT JOIN staffs s ON lr.requester_id = s.id 
                    WHERE lr.id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$leave_id]);
            $leave_request = $stmt->fetch();
            
            if ($leave_request) {
                // Duyệt đơn
                if (approveLeaveRequest($leave_id)) {
                    $success_message = '✅ Đơn nghỉ phép đã được duyệt thành công!';
                    
                    // Gửi email thông báo cho người yêu cầu (tùy chọn)
                    // sendApprovalNotificationToRequester($leave_request);
                } else {
                    $error_message = '❌ Không thể duyệt đơn nghỉ phép. Vui lòng thử lại.';
                }
            } else {
                $error_message = '❌ Không tìm thấy thông tin đơn nghỉ phép.';
            }
        } else {
            $error_message = '❌ Token không hợp lệ hoặc đơn nghỉ phép đã được duyệt.';
        }
    } catch (Exception $e) {
        error_log("Error approving leave request: " . $e->getMessage());
        $error_message = '❌ Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.';
    }
} else {
    $error_message = '❌ Link duyệt đơn không hợp lệ.';
}
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
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo filemtime('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="assets/css/alert.css?v=<?php echo filemtime('assets/css/alert.css'); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    
    <style>
        .approval-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .approval-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }
        
        .approval-header {
            background: #007bff;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .approval-body {
            padding: 40px;
        }
        
        .status-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        .success-icon {
            color: #28a745;
        }
        
        .error-icon {
            color: #dc3545;
        }
        
        .leave-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .info-label {
            font-weight: bold;
            color: #495057;
        }
        
        .info-value {
            color: #6c757d;
        }
        
        .btn-back {
            background: #6c757d;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-back:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="approval-container">
        <div class="approval-card">
            <div class="approval-header">
                <h1><i class="fas fa-calendar-check me-3"></i>Duyệt đơn nghỉ phép</h1>
                <p class="mb-0">Hệ thống quản lý IT Services</p>
            </div>
            
            <div class="approval-body">
                <?php if ($success_message): ?>
                    <!-- Thành công -->
                    <div class="text-center">
                        <div class="status-icon success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3 class="text-success mb-3">Duyệt đơn thành công!</h3>
                        <p class="text-muted mb-4"><?php echo $success_message; ?></p>
                        
                        <?php if ($leave_request): ?>
                            <div class="leave-info">
                                <h5 class="mb-3">
                                    <i class="fas fa-info-circle text-primary me-2"></i>
                                    Thông tin đơn nghỉ phép
                                </h5>
                                
                                <div class="info-row">
                                    <span class="info-label">Mã đơn:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($leave_request['request_code']); ?></span>
                                </div>
                                
                                <div class="info-row">
                                    <span class="info-label">Người yêu cầu:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($leave_request['requester_name']); ?></span>
                                </div>
                                
                                <div class="info-row">
                                    <span class="info-label">Loại nghỉ:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($leave_request['leave_type']); ?></span>
                                </div>
                                
                                <div class="info-row">
                                    <span class="info-label">Thời gian nghỉ:</span>
                                    <span class="info-value">
                                        <?php 
                                        echo date('d/m/Y H:i', strtotime($leave_request['start_date'])) . ' - ' . 
                                             date('d/m/Y H:i', strtotime($leave_request['end_date'])); 
                                        ?>
                                    </span>
                                </div>
                                
                                <div class="info-row">
                                    <span class="info-label">Số ngày nghỉ:</span>
                                    <span class="info-value"><?php echo $leave_request['leave_days']; ?> ngày</span>
                                </div>
                                
                                <div class="info-row">
                                    <span class="info-label">Trạng thái:</span>
                                    <span class="info-value">
                                        <span class="badge bg-success">Đã phê duyệt</span>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="leave_management.php" class="btn btn-back text-white">
                                <i class="fas fa-arrow-left me-2"></i>
                                Quay lại trang quản lý
                            </a>
                        </div>
                    </div>
                    
                <?php elseif ($error_message): ?>
                    <!-- Lỗi -->
                    <div class="text-center">
                        <div class="status-icon error-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h3 class="text-danger mb-3">Không thể duyệt đơn</h3>
                        <p class="text-muted mb-4"><?php echo $error_message; ?></p>
                        
                        <div class="mt-4">
                            <a href="leave_management.php" class="btn btn-back text-white">
                                <i class="fas fa-arrow-left me-2"></i>
                                Quay lại trang quản lý
                            </a>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- Loading hoặc thông tin không hợp lệ -->
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Đang xử lý...</span>
                        </div>
                        <p class="mt-3 text-muted">Đang xử lý yêu cầu duyệt đơn...</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto redirect sau 5 giây nếu thành công
        <?php if ($success_message): ?>
        setTimeout(function() {
            window.location.href = 'leave_management.php';
        }, 5000);
        <?php endif; ?>
    </script>
</body>
</html> 