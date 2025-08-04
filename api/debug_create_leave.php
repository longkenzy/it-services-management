<?php
/**
 * Script debug chi tiết để xác định lỗi trong create_leave_request.php
 */

require_once '../includes/session.php';
require_once '../config/db.php';

header('Content-Type: application/json');

try {
    // Kiểm tra đăng nhập
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
        exit;
    }
    
    $current_user = getCurrentUser();
    
    // Simulate POST data
    $_POST = [
        'start_date' => '2024-12-20',
        'start_time' => '08:00',
        'end_date' => '2024-12-20',
        'end_time' => '17:00',
        'return_date' => '2024-12-21',
        'return_time' => '08:00',
        'leave_days' => '1.0',
        'leave_type' => 'Nghỉ phép năm',
        'reason' => 'Test debug',
        'handover_to' => $current_user['id']
    ];
    
    // Validate dữ liệu
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
    
    // Tạo mã đơn nghỉ phép
    $current_year = date('y');
    $current_month = date('m');
    
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
    
    // Test data
    $test_data = [
        'request_code' => $request_code,
        'requester_id' => $current_user['id'],
        'requester_position' => $user_info['position'] ?? '',
        'requester_department' => $user_info['department'] ?? '',
        'requester_office' => $user_info['office'] ?? '',
        'start_date' => $start_datetime,
        'end_date' => $end_datetime,
        'return_date' => $return_datetime,
        'leave_days' => $leave_days,
        'leave_type' => $leave_type,
        'reason' => $reason,
        'handover_to' => $handover_to,
        'attachment' => null,
        'status' => 'Chờ phê duyệt'
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Validation passed',
        'data' => $test_data
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?> 