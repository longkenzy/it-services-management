<?php
/**
 * API: Gửi email thông báo đơn nghỉ phép mới
 * File: api/send_leave_approval_email.php
 * Mục đích: Gửi email thông báo đến admin khi có đơn nghỉ phép mới
 */

// Thiết lập header
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include các file cần thiết
require_once '../config/db.php';
require_once '../config/email.php';

// Kiểm tra method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Nhận dữ liệu từ request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['leave_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin đơn nghỉ phép']);
    exit;
}

$leave_id = intval($input['leave_id']);

try {
    // Lấy thông tin đơn nghỉ phép
    $sql = "SELECT lr.*, s.fullname as requester_name, s.email as requester_email 
            FROM leave_requests lr 
            LEFT JOIN staffs s ON lr.requester_id = s.id 
            WHERE lr.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$leave_id]);
    $leave_request = $stmt->fetch();
    
    if (!$leave_request) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn nghỉ phép']);
        exit;
    }
    
    // Kiểm tra xem đã có token chưa
    if (empty($leave_request['approve_token'])) {
        // Tạo token mới
        $approve_token = generateApproveToken(16);
        
        // Cập nhật token vào database
        $update_sql = "UPDATE leave_requests SET approve_token = ? WHERE id = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute([$approve_token, $leave_id]);
    } else {
        $approve_token = $leave_request['approve_token'];
    }
    
    // Tạo URL duyệt đơn
    $approve_url = generateApproveUrl($leave_id, $approve_token);
    
    // Gửi email thông báo
    $email_sent = sendLeaveApprovalEmail($leave_request, $approve_url);
    
    if ($email_sent) {
        echo json_encode([
            'success' => true, 
            'message' => 'Email thông báo đã được gửi thành công',
            'approve_url' => $approve_url
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Không thể gửi email thông báo']);
    }
    
} catch (Exception $e) {
    error_log("Error sending leave approval email: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}

/**
 * Hàm gửi email thông báo đơn nghỉ phép mới
 * @param array $leave_request Thông tin đơn nghỉ phép
 * @param string $approve_url URL duyệt đơn
 * @return bool True nếu gửi thành công
 */
function sendLeaveApprovalEmail($leave_request, $approve_url) {
    global $email_config;
    
    // Kiểm tra xem có PHPMailer không
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        // Nếu chưa có PHPMailer, sử dụng mail() function
        return sendEmailWithMailFunction($leave_request, $approve_url);
    }
    
    try {
        // Sử dụng PHPMailer
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Cấu hình SMTP
        $mail->isSMTP();
        $mail->Host = $email_config['smtp_host'];
        $mail->SMTPAuth = $email_config['smtp_auth'];
        $mail->Username = $email_config['smtp_username'];
        $mail->Password = $email_config['smtp_password'];
        $mail->SMTPSecure = $email_config['smtp_secure'];
        $mail->Port = $email_config['smtp_port'];
        $mail->CharSet = 'UTF-8';
        
        // Người gửi và người nhận
        $mail->setFrom($email_config['from_email'], $email_config['from_name']);
        $mail->addAddress($email_config['admin_email'], $email_config['admin_name']);
        
        // Nội dung email
        $mail->isHTML(true);
        $mail->Subject = 'Đơn nghỉ phép mới - ' . $leave_request['request_code'];
        
        // Tạo nội dung HTML
        $email_body = createEmailBody($leave_request, $approve_url);
        $mail->Body = $email_body;
        $mail->AltBody = createPlainTextBody($leave_request, $approve_url);
        
        // Gửi email
        return $mail->send();
        
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Hàm gửi email sử dụng mail() function (fallback)
 * @param array $leave_request Thông tin đơn nghỉ phép
 * @param string $approve_url URL duyệt đơn
 * @return bool True nếu gửi thành công
 */
function sendEmailWithMailFunction($leave_request, $approve_url) {
    global $email_config;
    
    $subject = 'Đơn nghỉ phép mới - ' . $leave_request['request_code'];
    $headers = [
        'From: ' . $email_config['from_email'],
        'Reply-To: ' . $email_config['from_email'],
        'Content-Type: text/html; charset=UTF-8',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $email_body = createEmailBody($leave_request, $approve_url);
    
    return mail($email_config['admin_email'], $subject, $email_body, implode("\r\n", $headers));
}

/**
 * Hàm tạo nội dung email HTML
 * @param array $leave_request Thông tin đơn nghỉ phép
 * @param string $approve_url URL duyệt đơn
 * @return string Nội dung HTML
 */
function createEmailBody($leave_request, $approve_url) {
    $start_date = date('d/m/Y H:i', strtotime($leave_request['start_date']));
    $end_date = date('d/m/Y H:i', strtotime($leave_request['end_date']));
    $return_date = date('d/m/Y H:i', strtotime($leave_request['return_date']));
    
    return '
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Đơn nghỉ phép mới</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f8f9fa; }
            .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            .info-table th, .info-table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
            .info-table th { background: #e9ecef; font-weight: bold; }
            .approve-btn { 
                display: inline-block; 
                background: #28a745; 
                color: white; 
                padding: 12px 24px; 
                text-decoration: none; 
                border-radius: 5px; 
                font-weight: bold; 
                margin: 20px 0;
            }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>📋 Đơn nghỉ phép mới</h1>
                <p>Hệ thống quản lý IT Services</p>
            </div>
            
            <div class="content">
                <p>Xin chào <strong>' . $email_config['admin_name'] . '</strong>,</p>
                
                <p>Có một đơn nghỉ phép mới được gửi từ nhân viên <strong>' . htmlspecialchars($leave_request['requester_name']) . '</strong>.</p>
                
                <table class="info-table">
                    <tr>
                        <th>Mã đơn:</th>
                        <td>' . htmlspecialchars($leave_request['request_code']) . '</td>
                    </tr>
                    <tr>
                        <th>Người yêu cầu:</th>
                        <td>' . htmlspecialchars($leave_request['requester_name']) . '</td>
                    </tr>
                    <tr>
                        <th>Chức vụ:</th>
                        <td>' . htmlspecialchars($leave_request['requester_position']) . '</td>
                    </tr>
                    <tr>
                        <th>Phòng ban:</th>
                        <td>' . htmlspecialchars($leave_request['requester_department']) . '</td>
                    </tr>
                    <tr>
                        <th>Loại nghỉ:</th>
                        <td>' . htmlspecialchars($leave_request['leave_type']) . '</td>
                    </tr>
                    <tr>
                        <th>Thời gian nghỉ:</th>
                        <td>Từ: ' . $start_date . '<br>Đến: ' . $end_date . '</td>
                    </tr>
                    <tr>
                        <th>Ngày đi làm lại:</th>
                        <td>' . $return_date . '</td>
                    </tr>
                    <tr>
                        <th>Số ngày nghỉ:</th>
                        <td>' . $leave_request['leave_days'] . ' ngày</td>
                    </tr>
                    <tr>
                        <th>Lý do:</th>
                        <td>' . nl2br(htmlspecialchars($leave_request['reason'])) . '</td>
                    </tr>
                </table>
                
                <div style="text-align: center;">
                    <a href="' . $approve_url . '" class="approve-btn">
                        ✅ Duyệt đơn
                    </a>
                </div>
                
                <p><strong>Lưu ý:</strong> Link duyệt đơn chỉ có hiệu lực một lần và sẽ hết hạn sau khi đơn được duyệt.</p>
            </div>
            
            <div class="footer">
                <p>Email này được gửi tự động từ hệ thống quản lý IT Services.</p>
                <p>Vui lòng không trả lời email này.</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Hàm tạo nội dung email dạng text thuần
 * @param array $leave_request Thông tin đơn nghỉ phép
 * @param string $approve_url URL duyệt đơn
 * @return string Nội dung text
 */
function createPlainTextBody($leave_request, $approve_url) {
    $start_date = date('d/m/Y H:i', strtotime($leave_request['start_date']));
    $end_date = date('d/m/Y H:i', strtotime($leave_request['end_date']));
    $return_date = date('d/m/Y H:i', strtotime($leave_request['return_date']));
    
    return "
ĐƠN NGHỈ PHÉP MỚI

Mã đơn: {$leave_request['request_code']}
Người yêu cầu: {$leave_request['requester_name']}
Chức vụ: {$leave_request['requester_position']}
Phòng ban: {$leave_request['requester_department']}
Loại nghỉ: {$leave_request['leave_type']}
Thời gian nghỉ: Từ {$start_date} đến {$end_date}
Ngày đi làm lại: {$return_date}
Số ngày nghỉ: {$leave_request['leave_days']} ngày
Lý do: {$leave_request['reason']}

Để duyệt đơn, vui lòng truy cập link sau:
{$approve_url}

Lưu ý: Link duyệt đơn chỉ có hiệu lực một lần và sẽ hết hạn sau khi đơn được duyệt.

---
Email này được gửi tự động từ hệ thống quản lý IT Services.
Vui lòng không trả lời email này.";
}
?> 