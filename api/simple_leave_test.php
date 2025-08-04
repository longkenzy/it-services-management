<?php
/**
 * Script test đơn giản để thử insert vào leave_requests
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
    
    // Test data đơn giản
    $request_code = 'TEST' . time();
    $requester_id = $current_user['id'];
    $start_date = '2024-12-20 08:00:00';
    $end_date = '2024-12-20 17:00:00';
    $return_date = '2024-12-21 08:00:00';
    $leave_days = 1.0;
    $leave_type = 'Nghỉ phép năm';
    $reason = 'Test insert';
    $handover_to = $current_user['id'];
    
    // SQL insert đơn giản
    $sql = "INSERT INTO leave_requests (
                request_code, requester_id, start_date, end_date, return_date, 
                leave_days, leave_type, reason, handover_to, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Chờ phê duyệt')";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $request_code, $requester_id, $start_date, $end_date, $return_date,
        $leave_days, $leave_type, $reason, $handover_to
    ]);
    
    if ($result) {
        $request_id = $pdo->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Test insert thành công',
            'data' => [
                'id' => $request_id,
                'request_code' => $request_code
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