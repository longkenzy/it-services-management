<?php
/**
 * Script test để thử insert vào bảng leave_requests
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
    
    // Test data
    $test_data = [
        'request_code' => 'TEST' . time(),
        'requester_id' => $current_user['id'],
        'requester_position' => $current_user['position'] ?? 'Nhân viên',
        'requester_department' => $current_user['department'] ?? 'IT',
        'requester_office' => $current_user['office'] ?? 'Hà Nội',
        'start_date' => '2024-12-20 08:00:00',
        'end_date' => '2024-12-20 17:00:00',
        'return_date' => '2024-12-21 08:00:00',
        'leave_days' => 1.0,
        'leave_type' => 'Nghỉ phép năm',
        'reason' => 'Test insert',
        'handover_to' => $current_user['id'], // Sử dụng chính user hiện tại
        'attachment' => null,
        'status' => 'Chờ phê duyệt'
    ];
    
    // SQL insert
    $sql = "INSERT INTO leave_requests (
                request_code, requester_id, requester_position, requester_department, requester_office,
                start_date, end_date, return_date, leave_days, leave_type, reason, handover_to,
                attachment, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $test_data['request_code'],
        $test_data['requester_id'],
        $test_data['requester_position'],
        $test_data['requester_department'],
        $test_data['requester_office'],
        $test_data['start_date'],
        $test_data['end_date'],
        $test_data['return_date'],
        $test_data['leave_days'],
        $test_data['leave_type'],
        $test_data['reason'],
        $test_data['handover_to'],
        $test_data['attachment'],
        $test_data['status']
    ]);
    
    if ($result) {
        $request_id = $pdo->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Test insert thành công',
            'data' => [
                'id' => $request_id,
                'request_code' => $test_data['request_code']
            ]
        ]);
        
        // Xóa bản ghi test
        $pdo->prepare("DELETE FROM leave_requests WHERE id = ?")->execute([$request_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Test insert thất bại']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?> 