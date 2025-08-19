<?php
/**
 * API: Tạo đơn nghỉ phép mới
 * Method: POST
 * Parameters: leave_type, leave_days, start_date, end_date, reason, attachment (optional)
 */

// Bật error reporting cho debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Log function
function logError($message, $data = null) {
    $log_file = '../logs/api_errors.log';
    $log_dir = dirname($log_file);
    
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message";
    if ($data) {
        $log_entry .= " | Data: " . json_encode($data);
    }
    $log_entry .= "\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

try {
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

    // Bắt đầu transaction để đảm bảo tính toàn vẹn dữ liệu
    $pdo->beginTransaction();
    
    $current_user = getCurrentUser();
    
    // Lấy dữ liệu từ form
    $start_date = $_POST['start_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '08:30';
    $end_date = $_POST['end_date'] ?? '';
    $end_time = $_POST['end_time'] ?? '18:00';
    $return_date = $_POST['return_date'] ?? '';
    $return_time = $_POST['return_time'] ?? '08:30';
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
                attachment, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
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
        
        // Kiểm tra xem ID có hợp lệ không
        if ($request_id <= 0) {
            // Thử cách khác để lấy ID
            $stmt = $pdo->query("SELECT LAST_INSERT_ID() as last_id");
            $last_id_result = $stmt->fetch(PDO::FETCH_ASSOC);
            $request_id = $last_id_result['last_id'];
            
            // Nếu vẫn không được, thử lấy ID từ request_code
            if ($request_id <= 0) {
                $stmt = $pdo->prepare("SELECT id FROM leave_requests WHERE request_code = ? ORDER BY id DESC LIMIT 1");
                $stmt->execute([$request_code]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    $request_id = $result['id'];
                } else {
                    throw new Exception('Không thể lấy ID của đơn nghỉ phép vừa tạo');
                }
            }
        }
        
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
        
        // Tạo thông báo cho admin và leader
        try {
            $notification_sql = "INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, ?, ?)";
            $notification_stmt = $pdo->prepare($notification_sql);
            
            // Lấy thông tin phòng ban của người yêu cầu
            $requester_dept_sql = "SELECT department FROM staffs WHERE id = ?";
            $requester_dept_stmt = $pdo->prepare($requester_dept_sql);
            $requester_dept_stmt->execute([$current_user['id']]);
            $requester_dept = $requester_dept_stmt->fetchColumn();
            
            // Debug log
            error_log("Create leave request - User ID: " . $current_user['id'] . ", Department: '" . $requester_dept . "'");
            error_log("Department contains 'IT': " . (strpos($requester_dept, 'IT') !== false ? 'true' : 'false'));
            error_log("Department contains 'it': " . (strpos($requester_dept, 'it') !== false ? 'true' : 'false'));
            
            // Gửi thông báo theo phòng ban
            if ($requester_dept && strpos($requester_dept, 'HR') !== false) {
                // HR: gửi cho hr_leader
                $leader_ids = [];
                $stmt = $pdo->prepare("SELECT id FROM staffs WHERE role = 'hr_leader'");
                $stmt->execute();
                while ($leader = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $leader_ids[] = $leader['id'];
                }
                
                foreach ($leader_ids as $leader_id) {
                    $notification_stmt->execute([
                        $leader_id,
                        'Đơn nghỉ phép mới cần phê duyệt',
                        "Có đơn nghỉ phép mới từ phòng HR: {$request_code}. Vui lòng phê duyệt.",
                        'leave_request',
                        $request_id
                    ]);
                }
                
            } elseif ($requester_dept && (strpos($requester_dept, 'IT') !== false || strpos($requester_dept, 'it') !== false)) {
                // IT: gửi cho it_leader
                $leader_ids = [];
                $stmt = $pdo->prepare("SELECT id FROM staffs WHERE role = 'it_leader'");
                $stmt->execute();
                while ($leader = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $leader_ids[] = $leader['id'];
                }
                
                // Debug log
                error_log("IT department detected. Found " . count($leader_ids) . " IT leaders: " . implode(', ', $leader_ids));
                
                foreach ($leader_ids as $leader_id) {
                    $notification_stmt->execute([
                        $leader_id,
                        'Đơn nghỉ phép IT mới cần phê duyệt',
                        "Có đơn nghỉ phép mới từ phòng IT: {$request_code}. Vui lòng phê duyệt (Cấp 1).",
                        'leave_request',
                        $request_id
                    ]);
                    error_log("Notification sent to IT leader ID: " . $leader_id);
                }
                
            } elseif ($requester_dept && strpos($requester_dept, 'SALE') !== false) {
                // SALE: gửi cho sale_leader
                $leader_ids = [];
                $stmt = $pdo->prepare("SELECT id FROM staffs WHERE role = 'sale_leader'");
                $stmt->execute();
                while ($leader = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $leader_ids[] = $leader['id'];
                }
                
                foreach ($leader_ids as $leader_id) {
                    $notification_stmt->execute([
                        $leader_id,
                        'Đơn nghỉ phép SALE mới cần phê duyệt',
                        "Có đơn nghỉ phép mới từ phòng SALE: {$request_code}. Vui lòng phê duyệt (Cấp 1).",
                        'leave_request',
                        $request_id
                    ]);
                }
                
            } else {
                // Các phòng ban khác: gửi cho admin
                $admin_ids = [];
                $stmt = $pdo->prepare("SELECT id FROM staffs WHERE role = 'admin'");
                $stmt->execute();
                while ($admin = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $admin_ids[] = $admin['id'];
                }
                
                // Debug log
                error_log("Other department detected. Found " . count($admin_ids) . " admins: " . implode(', ', $admin_ids));
                
                foreach ($admin_ids as $admin_id) {
                    $notification_stmt->execute([
                        $admin_id,
                        'Đơn nghỉ phép mới cần phê duyệt',
                        "Có đơn nghỉ phép mới: {$request_code}. Vui lòng phê duyệt.",
                        'leave_request',
                        $request_id
                    ]);
                }
            }
            
        } catch (Exception $e) {
            // Bỏ qua lỗi notification
            error_log("Error sending notification: " . $e->getMessage());
        }
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Đơn nghỉ phép đã được tạo thành công',
            'data' => [
                'request_code' => $request_code,
                'id' => $request_id
            ]
        ]);
    } else {
        // Rollback nếu insert thất bại
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi tạo đơn nghỉ phép']);
    }
    
} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log lỗi
    logError('create_leave_request error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi tạo đơn nghỉ phép: ' . $e->getMessage()
    ]);
}
?> 