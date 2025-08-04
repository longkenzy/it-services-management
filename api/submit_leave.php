<?php
/**
 * API: Xử lý submit đơn nghỉ phép
 * File: api/submit_leave.php
 * Mục đích: Lưu đơn nghỉ phép vào DB và gửi email thông báo
 */

// Thiết lập header
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include các file cần thiết
require_once '../config/db.php';
require_once '../config/email.php';
require_once '../includes/session.php';

// Kiểm tra method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thực hiện thao tác này']);
    exit;
}

// Nhận dữ liệu từ request
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
    exit;
}

// Validate dữ liệu đầu vào
$required_fields = ['start_date', 'end_date', 'return_date', 'leave_type', 'reason'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc']);
        exit;
    }
}

try {
    $current_user = getCurrentUser();
    
    // Tính số ngày nghỉ
    $start_date = new DateTime($input['start_date']);
    $end_date = new DateTime($input['end_date']);
    $return_date = new DateTime($input['return_date']);
    
    // Tính số ngày nghỉ (bao gồm cả ngày cuối)
    $interval = $start_date->diff($end_date);
    $leave_days = $interval->days + 1; // +1 để bao gồm cả ngày cuối
    
    // Tạo mã đơn nghỉ phép
    $request_code = generateLeaveRequestCode();
    
    // Tạo token duyệt
    $approve_token = generateApproveToken(16);
    
    // Chuẩn bị dữ liệu để lưu
    $leave_data = [
        'request_code' => $request_code,
        'requester_id' => $current_user['id'],
        'requester_position' => $current_user['position'] ?? '',
        'requester_department' => $current_user['department'] ?? '',
        'requester_office' => $current_user['office'] ?? '',
        'start_date' => $input['start_date'],
        'end_date' => $input['end_date'],
        'return_date' => $input['return_date'],
        'leave_days' => $leave_days,
        'leave_type' => $input['leave_type'],
        'reason' => $input['reason'],
        'handover_to' => !empty($input['handover_to']) ? intval($input['handover_to']) : null,
        'attachment' => !empty($input['attachment']) ? $input['attachment'] : null,
        'approve_token' => $approve_token
    ];
    
    // Lưu đơn nghỉ phép vào database
    $leave_id = saveLeaveRequest($leave_data);
    
    if ($leave_id) {
        // Gửi email thông báo
        $email_sent = sendLeaveApprovalEmailById($leave_id);
        
        $response = [
            'success' => true,
            'message' => 'Đơn nghỉ phép đã được gửi thành công!',
            'data' => [
                'leave_id' => $leave_id,
                'request_code' => $request_code,
                'email_sent' => $email_sent
            ]
        ];
        
        if (!$email_sent) {
            $response['warning'] = 'Đơn đã được lưu nhưng không thể gửi email thông báo. Vui lòng liên hệ admin.';
        }
        
        echo json_encode($response);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Không thể lưu đơn nghỉ phép. Vui lòng thử lại.']);
    }
    
} catch (Exception $e) {
    error_log("Error submitting leave request: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}

/**
 * Hàm tạo mã đơn nghỉ phép
 * @return string Mã đơn nghỉ phép
 */
function generateLeaveRequestCode() {
    global $pdo;
    
    $prefix = 'NP' . date('ymd'); // NP + YYMMDD
    $counter = 1;
    
    // Tìm số thứ tự tiếp theo
    $sql = "SELECT COUNT(*) as count FROM leave_requests WHERE request_code LIKE ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$prefix . '%']);
    $result = $stmt->fetch();
    
    $counter = $result['count'] + 1;
    
    return $prefix . str_pad($counter, 3, '0', STR_PAD_LEFT);
}

/**
 * Hàm lưu đơn nghỉ phép vào database
 * @param array $data Dữ liệu đơn nghỉ phép
 * @return int|false ID đơn nghỉ phép hoặc false nếu lỗi
 */
function saveLeaveRequest($data) {
    global $pdo;
    
    try {
        $sql = "INSERT INTO leave_requests (
                    request_code, requester_id, requester_position, requester_department, 
                    requester_office, start_date, end_date, return_date, leave_days, 
                    leave_type, reason, handover_to, attachment, approve_token, 
                    status, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Chờ phê duyệt', NOW()
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['request_code'],
            $data['requester_id'],
            $data['requester_position'],
            $data['requester_department'],
            $data['requester_office'],
            $data['start_date'],
            $data['end_date'],
            $data['return_date'],
            $data['leave_days'],
            $data['leave_type'],
            $data['reason'],
            $data['handover_to'],
            $data['attachment'],
            $data['approve_token']
        ]);
        
        return $pdo->lastInsertId();
        
    } catch (PDOException $e) {
        error_log("Error saving leave request: " . $e->getMessage());
        return false;
    }
}

/**
 * Hàm gửi email thông báo đơn nghỉ phép mới theo ID
 * @param int $leave_id ID đơn nghỉ phép
 * @return bool True nếu gửi thành công
 */
function sendLeaveApprovalEmailById($leave_id) {
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
            return false;
        }
        
        // Tạo URL duyệt đơn
        $approve_url = generateApproveUrl($leave_id, $leave_request['approve_token']);
        
        // Gửi email thông báo
        return sendLeaveApprovalEmail($leave_request, $approve_url);
        
    } catch (Exception $e) {
        error_log("Error sending leave approval email by ID: " . $e->getMessage());
        return false;
    }
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
    global $email_config;
    
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