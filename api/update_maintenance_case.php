<?php
header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../config/db.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $data = [
        'id' => $input['id'] ?? '',
        'case_code' => $input['case_code'] ?? '',
        'request_type' => $input['request_type'] ?? '',
        'request_detail_type' => $input['request_detail_type'] ?? '',
        'progress' => $input['progress'] ?? 0,
        'case_description' => $input['case_description'] ?? '',
        'notes' => $input['notes'] ?? '',
        'assigned_to' => $input['assigned_to'] ?? '',
        'work_type' => $input['work_type'] ?? '',
        'start_date' => $input['start_date'] ?? '',
        'end_date' => $input['end_date'] ?? '',
        'status' => $input['status'] ?? ''
    ];

    // Validation
    $errors = [];
    
    if (empty($data['id'])) {
        $errors[] = 'ID case không được để trống';
    }
    
    if (empty($data['case_code'])) {
        $errors[] = 'Mã case không được để trống';
    }
    
    if (empty($data['request_type'])) {
        $errors[] = 'Loại yêu cầu không được để trống';
    }
    
    if (empty($data['assigned_to'])) {
        $errors[] = 'Vui lòng chọn người được giao';
    }
    
    if (empty($data['status'])) {
        $errors[] = 'Vui lòng chọn trạng thái';
    }

    // Kiểm tra mã case đã tồn tại chưa (trừ record hiện tại)
    if (!empty($data['case_code'])) {
        $stmt = $pdo->prepare("SELECT id FROM maintenance_cases WHERE case_code = ? AND id != ?");
        $stmt->execute([$data['case_code'], $data['id']]);
        if ($stmt->fetch()) {
            $errors[] = 'Mã case đã tồn tại';
        }
    }

    // Kiểm tra người được giao có tồn tại không
    if (!empty($data['assigned_to'])) {
        $stmt = $pdo->prepare("SELECT id FROM staffs WHERE id = ? AND status = 'active' AND department = 'IT Dept.'");
        $stmt->execute([$data['assigned_to']]);
        if (!$stmt->fetch()) {
            $errors[] = 'Người được giao không tồn tại hoặc không thuộc phòng IT';
        }
    }

    // Nếu có lỗi validation
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
        exit;
    }

    // Xử lý ngày tháng
    $start_date = !empty($data['start_date']) ? $data['start_date'] : null;
    $end_date = !empty($data['end_date']) ? $data['end_date'] : null;

    // Update maintenance_cases
    $sql = "UPDATE maintenance_cases SET 
        case_code = ?, request_type = ?, request_detail_type = ?, case_description = ?, notes = ?, 
        assigned_to = ?, work_type = ?, start_date = ?, end_date = ?, status = ?, updated_at = NOW()
        WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $data['case_code'],
        $data['request_type'],
        $data['request_detail_type'],
        $data['case_description'],
        $data['notes'],
        $data['assigned_to'],
        $data['work_type'],
        $start_date,
        $end_date,
        $data['status'],
        $data['id']
    ]);

    if ($result) {
        // Log hoạt động
        $log_message = "Cập nhật case bảo trì: {$data['case_code']}";
        $log_sql = "INSERT INTO user_activity_logs (user_id, activity, details, ip_address) VALUES (?, ?, ?, ?)";
        $log_stmt = $pdo->prepare($log_sql);
        $log_stmt->execute([
            getCurrentUserId(),
            'UPDATE_MAINTENANCE_CASE',
            $log_message,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật case bảo trì thành công'
        ]);
    } else {
        throw new Exception('Không thể cập nhật case bảo trì');
    }

} catch (PDOException $e) {
    error_log("Database error in update_maintenance_case.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error in update_maintenance_case.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
?> 