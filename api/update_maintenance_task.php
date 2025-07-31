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
        'task_code' => $input['task_code'] ?? '',
        'task_name' => $input['task_name'] ?? '',
        'task_description' => $input['task_description'] ?? '',
        'assigned_to' => $input['assigned_to'] ?? '',
        'priority' => $input['priority'] ?? '',
        'start_date' => $input['start_date'] ?? '',
        'end_date' => $input['end_date'] ?? '',
        'status' => $input['status'] ?? '',
        'progress' => $input['progress'] ?? 0,
        'notes' => $input['notes'] ?? ''
    ];

    // Validation
    $errors = [];
    
    if (empty($data['id'])) {
        $errors[] = 'ID task không được để trống';
    }
    
    if (empty($data['task_code'])) {
        $errors[] = 'Mã task không được để trống';
    }
    
    if (empty($data['task_name'])) {
        $errors[] = 'Tên task không được để trống';
    }
    
    if (empty($data['assigned_to'])) {
        $errors[] = 'Vui lòng chọn người được giao';
    }
    
    if (empty($data['status'])) {
        $errors[] = 'Vui lòng chọn trạng thái';
    }

    // Kiểm tra mã task đã tồn tại chưa (trừ record hiện tại)
    if (!empty($data['task_code'])) {
        $stmt = $pdo->prepare("SELECT id FROM maintenance_tasks WHERE task_code = ? AND id != ?");
        $stmt->execute([$data['task_code'], $data['id']]);
        if ($stmt->fetch()) {
            $errors[] = 'Mã task đã tồn tại';
        }
    }

    // Kiểm tra người được giao có tồn tại không
    if (!empty($data['assigned_to'])) {
        $stmt = $pdo->prepare("SELECT id FROM staffs WHERE id = ? AND status = 'active'");
        $stmt->execute([$data['assigned_to']]);
        if (!$stmt->fetch()) {
            $errors[] = 'Người được giao không tồn tại hoặc không hoạt động';
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

    // Update database
    $sql = "UPDATE maintenance_tasks SET 
            task_code = ?, task_name = ?, task_description = ?, assigned_to = ?, 
            priority = ?, start_date = ?, end_date = ?, status = ?, 
            progress = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $data['task_code'],
        $data['task_name'],
        $data['task_description'],
        $data['assigned_to'],
        $data['priority'],
        $start_date,
        $end_date,
        $data['status'],
        $data['progress'],
        $data['notes'],
        $data['id']
    ]);

    if ($result) {
        // Log hoạt động
        $log_message = "Cập nhật task bảo trì: {$data['task_code']}";
        $log_sql = "INSERT INTO user_activity_logs (user_id, activity, details, ip_address) VALUES (?, ?, ?, ?)";
        $log_stmt = $pdo->prepare($log_sql);
        $log_stmt->execute([
            getCurrentUserId(),
            'UPDATE_MAINTENANCE_TASK',
            $log_message,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật task bảo trì thành công'
        ]);
    } else {
        throw new Exception('Không thể cập nhật task bảo trì');
    }

} catch (PDOException $e) {
    error_log("Database error in update_maintenance_task.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error in update_maintenance_task.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
?> 