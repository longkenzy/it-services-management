<?php
/**
 * Email Configuration for Leave Management System
 * Cấu hình email sử dụng PHPMailer với Outlook SMTP
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('INCLUDED')) {
    define('INCLUDED', true);
}

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Access denied.');
}

// ===== CẤU HÌNH EMAIL ===== //
$email_config = [
    'smtp_host' => 'smtp.office365.com',     // SMTP Host cho Outlook
    'smtp_port' => 587,                      // SMTP Port
    'smtp_secure' => 'tls',                  // Bảo mật TLS
    'smtp_auth' => true,                     // Yêu cầu xác thực
    
    // Thông tin email gửi
    'from_email' => 'your-email@outlook.com',  // Email gửi (thay đổi theo email thực tế)
    'from_name' => 'IT Services Management',   // Tên người gửi
    
    // Thông tin email admin nhận
    'admin_email' => 'admin@example.com',      // Email admin (thay đổi theo email thực tế)
    'admin_name' => 'Quản trị viên',           // Tên admin
    
    // Thông tin đăng nhập SMTP
    'smtp_username' => 'your-email@outlook.com',  // Username SMTP (thường là email)
    'smtp_password' => 'your-password',           // Password SMTP (thay đổi theo password thực tế)
];

// ===== CẤU HÌNH WEBSITE ===== //
$website_config = [
    'base_url' => 'http://localhost/it-services-management',  // URL gốc website
    'approve_url' => '/approve_leave.php',                    // URL trang duyệt đơn
];

/**
 * Hàm tạo token ngẫu nhiên cho việc duyệt đơn
 * @param int $length Độ dài token (mặc định 16)
 * @return string Token ngẫu nhiên
 */
function generateApproveToken($length = 16) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $token = '';
    
    for ($i = 0; $i < $length; $i++) {
        $token .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $token;
}

/**
 * Hàm tạo URL duyệt đơn
 * @param int $leave_id ID đơn nghỉ phép
 * @param string $token Token duyệt
 * @return string URL đầy đủ
 */
function generateApproveUrl($leave_id, $token) {
    global $website_config;
    return $website_config['base_url'] . $website_config['approve_url'] . '?id=' . $leave_id . '&token=' . $token;
}

/**
 * Hàm xác thực token duyệt đơn
 * @param int $leave_id ID đơn nghỉ phép
 * @param string $token Token duyệt
 * @return bool True nếu token hợp lệ
 */
function validateApproveToken($leave_id, $token) {
    global $pdo;
    
    try {
        $sql = "SELECT id FROM leave_requests WHERE id = ? AND approve_token = ? AND status = 'Chờ phê duyệt'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$leave_id, $token]);
        
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error validating approve token: " . $e->getMessage());
        return false;
    }
}

/**
 * Hàm cập nhật trạng thái đơn nghỉ phép thành đã duyệt
 * @param int $leave_id ID đơn nghỉ phép
 * @param int $approved_by ID người duyệt
 * @return bool True nếu cập nhật thành công
 */
function approveLeaveRequest($leave_id, $approved_by = null) {
    global $pdo;
    
    try {
        $sql = "UPDATE leave_requests SET 
                status = 'Đã phê duyệt',
                approved_by = ?,
                approved_at = NOW(),
                approve_token = NULL
                WHERE id = ? AND status = 'Chờ phê duyệt'";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$approved_by, $leave_id]);
        
        return $result && $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Error approving leave request: " . $e->getMessage());
        return false;
    }
}
?> 