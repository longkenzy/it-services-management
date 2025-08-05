<?php
require_once 'config/db.php';
require_once 'includes/session.php';

echo "=== Debug API tạo đơn nghỉ phép ===\n";

// Giả lập session
$_SESSION[SESSION_USER_ID] = 11; // Sử dụng user_id thực tế

echo "Session user ID: " . $_SESSION[SESSION_USER_ID] . "\n";

// Giả lập getCurrentUser()
$stmt = $pdo->prepare("SELECT * FROM staffs WHERE id = ?");
$stmt->execute([$_SESSION[SESSION_USER_ID]]);
$current_user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Current user:\n";
print_r($current_user);

// Giả lập dữ liệu POST
$_POST = [
    'start_date' => '2025-01-01',
    'start_time' => '08:00',
    'end_date' => '2025-01-02',
    'end_time' => '17:00',
    'return_date' => '2025-01-03',
    'return_time' => '08:00',
    'leave_days' => '1.0',
    'leave_type' => 'Nghỉ phép năm',
    'reason' => 'Test reason',
    'handover_to' => '11'
];

echo "\nPOST data:\n";
print_r($_POST);

// Thực hiện logic tạo đơn nghỉ phép
try {
    // Tạo mã đơn nghỉ phép
    $current_year = date('y');
    $current_month = date('m');
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM leave_requests WHERE request_code LIKE ?");
    $month_pattern = "NP{$current_year}{$current_month}%";
    $stmt->execute([$month_pattern]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $next_number = $result['count'] + 1;
    
    $request_code = "NP{$current_year}{$current_month}" . str_pad($next_number, 3, '0', STR_PAD_LEFT);
    
    echo "\nRequest code: " . $request_code . "\n";
    
    // Kết hợp ngày và giờ
    $start_datetime = $_POST['start_date'] . ' ' . $_POST['start_time'] . ':00';
    $end_datetime = $_POST['end_date'] . ' ' . $_POST['end_time'] . ':00';
    $return_datetime = $_POST['return_date'] . ' ' . $_POST['return_time'] . ':00';
    
    echo "Start datetime: " . $start_datetime . "\n";
    echo "End datetime: " . $end_datetime . "\n";
    echo "Return datetime: " . $return_datetime . "\n";
    
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
        $current_user['position'] ?? '',
        $current_user['department'] ?? '',
        $current_user['office'] ?? '',
        $start_datetime,
        $end_datetime,
        $return_datetime,
        $_POST['leave_days'],
        $_POST['leave_type'],
        $_POST['reason'],
        $_POST['handover_to'],
        null,
        'Chờ phê duyệt'
    ]);
    
    if ($result) {
        $request_id = $pdo->lastInsertId();
        echo "\nTạo thành công! ID: " . $request_id . "\n";
        
        // Kiểm tra record vừa tạo
        $stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE id = ?");
        $stmt->execute([$request_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Record vừa tạo:\n";
        print_r($record);
        
        // Xóa record test
        $stmt = $pdo->prepare("DELETE FROM leave_requests WHERE id = ?");
        $stmt->execute([$request_id]);
        echo "Đã xóa record test\n";
    } else {
        echo "Lỗi khi tạo record\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
?> 