<?php
/**
 * API: Tạo đơn nghỉ phép mới
 * Method: POST
 * Parameters: leave_type, leave_days, start_date, end_date, reason, attachment (optional)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/session.php';
require_once '../config/db.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

// Kiểm tra method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method không được hỗ trợ']);
    exit;
}

try {
    $current_user = getCurrentUser();
    
    // Lấy dữ liệu từ form
    $start_date = $_POST['start_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '08:00';
    $end_date = $_POST['end_date'] ?? '';
    $end_time = $_POST['end_time'] ?? '17:00';
    $return_date = $_POST['return_date'] ?? '';
    $return_time = $_POST['return_time'] ?? '08:00';
    $leave_days = $_POST['leave_days'] ?? '';
    $leave_type = $_POST['leave_type'] ?? '';
    $reason = $_POST['reason'] ?? '';
    $handover_to = $_POST['handover_to'] ?? '';
    
    // Validate dữ liệu
    if (empty($start_date) || empty($end_date) || empty($return_date) || empty($leave_days) || empty($leave_type) || empty($reason) || empty($handover_to)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
        exit;
    }
    
    // Validate thời gian
    if (empty($start_time) || empty($end_time) || empty($return_time)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thời gian']);
        exit;
    }
    
    // Validate số ngày nghỉ
    if (!is_numeric($leave_days) || $leave_days <= 0 || $leave_days > 30) {
        echo json_encode(['success' => false, 'message' => 'Số ngày nghỉ không hợp lệ']);
        exit;
    }
    
    // Validate ngày tháng
    if (strtotime($start_date) > strtotime($end_date)) {
        echo json_encode(['success' => false, 'message' => 'Ngày bắt đầu không thể sau ngày kết thúc']);
        exit;
    }
    
    if (strtotime($end_date) > strtotime($return_date)) {
        echo json_encode(['success' => false, 'message' => 'Ngày kết thúc không thể sau ngày đi làm lại']);
        exit;
    }
    
    // Validate thời gian khi cùng ngày
    if ($start_date === $end_date && $start_time >= $end_time) {
        echo json_encode(['success' => false, 'message' => 'Thời gian bắt đầu phải trước thời gian kết thúc']);
        exit;
    }
    
    // Kiểm tra ngày bắt đầu không được trong quá khứ
    if (strtotime($start_date) < strtotime(date('Y-m-d'))) {
        echo json_encode(['success' => false, 'message' => 'Ngày bắt đầu không thể trong quá khứ']);
        exit;
    }
    
    // Xử lý file đính kèm
    $attachment = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['attachment'];
        $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Kiểm tra loại file
        if (!in_array($file_extension, $allowed_types)) {
            echo json_encode(['success' => false, 'message' => 'Loại file không được hỗ trợ']);
            exit;
        }
        
        // Kiểm tra kích thước file (5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Kích thước file không được vượt quá 5MB']);
            exit;
        }
        
        // Tạo tên file mới
        $attachment = 'leave_' . time() . '_' . $current_user['id'] . '.' . $file_extension;
        $upload_path = '../assets/uploads/leave_attachments/' . $attachment;
        
        // Tạo thư mục nếu chưa tồn tại
        if (!is_dir('../assets/uploads/leave_attachments/')) {
            mkdir('../assets/uploads/leave_attachments/', 0777, true);
        }
        
        // Upload file
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi upload file']);
            exit;
        }
    }
    
    // Tạo mã đơn nghỉ phép theo format: NP + 2 số cuối năm + tháng + số thứ tự
    $current_year = date('y'); // 2 số cuối của năm
    $current_month = date('m'); // tháng hiện tại
    
    // Lấy số thứ tự tiếp theo trong tháng này
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM leave_requests WHERE request_code LIKE ?");
    $month_pattern = "NP{$current_year}{$current_month}%";
    $stmt->execute([$month_pattern]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $next_number = $result['count'] + 1;
    
    $request_code = "NP{$current_year}{$current_month}" . str_pad($next_number, 3, '0', STR_PAD_LEFT);
    
    // Lấy thông tin người yêu cầu
    $user_info = $current_user;
    
    // Kết hợp ngày và giờ
    $start_datetime = $start_date . ' ' . $start_time . ':00';
    $end_datetime = $end_date . ' ' . $end_time . ':00';
    $return_datetime = $return_date . ' ' . $return_time . ':00';
    
    // Lưu đơn nghỉ phép vào database
    $sql = "INSERT INTO leave_requests (
                request_code, requester_id, requester_position, requester_department, requester_office,
                start_date, end_date, return_date, leave_days, leave_type, reason, handover_to,
                attachment, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $request_code,
        $current_user['id'],
        $user_info['position'] ?? '',
        $user_info['department'] ?? '',
        $user_info['office'] ?? '',
        $start_datetime,
        $end_datetime,
        $return_datetime,
        $leave_days,
        $leave_type,
        $reason,
        $handover_to,
        $attachment,
        'Chờ phê duyệt'
    ]);
    
    if ($result) {
        $request_id = $pdo->lastInsertId();
        
        // Log hoạt động (bỏ qua nếu có lỗi)
        try {
            $log_sql = "INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())";
            $log_stmt = $pdo->prepare($log_sql);
            $log_stmt->execute([
                $current_user['id'],
                'create_leave_request',
                "Tạo đơn nghỉ phép: $request_code"
            ]);
        } catch (Exception $e) {
            // Bỏ qua lỗi log
        }
        
        // Tạo thông báo cho admin (bỏ qua nếu có lỗi)
        try {
            $admin_ids = [];
            $stmt = $pdo->prepare("SELECT id FROM staffs WHERE role = 'admin'");
            $stmt->execute();
            while ($admin = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $admin_ids[] = $admin['id'];
            }
            
            // Gửi thông báo cho tất cả admin
            foreach ($admin_ids as $admin_id) {
                $notification_sql = "INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, ?, ?)";
                $notification_stmt = $pdo->prepare($notification_sql);
                $notification_stmt->execute([
                    $admin_id,
                    'Đơn nghỉ phép mới cần phê duyệt',
                    "Có đơn nghỉ phép mới từ {$current_user['fullname']} cần phê duyệt. Mã đơn: $request_code",
                    'leave_request',
                    $request_id
                ]);
            }
        } catch (Exception $e) {
            // Bỏ qua lỗi notification
        }
        

        
        echo json_encode([
            'success' => true,
            'message' => 'Đơn nghỉ phép đã được tạo thành công',
            'data' => [
                'request_code' => $request_code,
                'id' => $request_id
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi tạo đơn nghỉ phép']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi tạo đơn nghỉ phép: ' . $e->getMessage()
    ]);
}
?> 