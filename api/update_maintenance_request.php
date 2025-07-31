<?php
// Prevent caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../config/db.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Debug: Log input data
    error_log("Update maintenance request input: " . json_encode($input));
    
    $data = [
        'id' => $input['id'] ?? '',
        'request_code' => $input['request_code'] ?? '',
        'po_number' => $input['po_number'] ?? '',
        'no_contract_po' => $input['no_contract_po'] ?? 0,
        'contract_type' => $input['contract_type'] ?? '',
        'request_detail_type' => $input['request_detail_type'] ?? '',
        'email_subject_customer' => $input['email_subject_customer'] ?? '',
        'email_subject_internal' => $input['email_subject_internal'] ?? '',
        'expected_start' => $input['expected_start'] ?? '',
        'expected_end' => $input['expected_end'] ?? '',
        'customer_id' => $input['customer_id'] ?? '',
        'contact_person' => $input['contact_person'] ?? '',
        'contact_phone' => $input['contact_phone'] ?? '',
        'sale_id' => $input['sale_id'] ?? '',
        'requester_notes' => $input['requester_notes'] ?? '',
        'maintenance_manager' => $input['maintenance_manager'] ?? '',
        'maintenance_status' => $input['maintenance_status'] ?? ''
    ];

    // Validation
    $errors = [];
    
    // Debug: Log each field for validation
    error_log("Validating ID: " . $data['id']);
    error_log("Validating request_code: " . $data['request_code']);
    error_log("Validating customer_id: " . $data['customer_id']);
    error_log("Validating sale_id: " . $data['sale_id']);
    error_log("Validating maintenance_status: " . $data['maintenance_status']);
    
    if (empty($data['id'])) {
        $errors[] = 'ID yêu cầu không được để trống';
    }
    
    if (empty($data['request_code'])) {
        $errors[] = 'Mã yêu cầu không được để trống';
    }
    
    if (empty($data['customer_id'])) {
        $errors[] = 'Vui lòng chọn khách hàng';
    }
    
    // Tạm thời bỏ validation sale_id vì có thể không có sale nào
    // if (empty($data['sale_id'])) {
    //     $errors[] = 'Vui lòng chọn sale phụ trách';
    // }
    
    if (empty($data['maintenance_status'])) {
        $errors[] = 'Vui lòng chọn trạng thái bảo trì';
    }

    // Kiểm tra mã yêu cầu đã tồn tại chưa (trừ record hiện tại)
    if (!empty($data['request_code'])) {
        $stmt = $pdo->prepare("SELECT id FROM maintenance_requests WHERE request_code = ? AND id != ?");
        $stmt->execute([$data['request_code'], $data['id']]);
        if ($stmt->fetch()) {
            $errors[] = 'Mã yêu cầu đã tồn tại';
        }
    }

    // Kiểm tra khách hàng có tồn tại không
    if (!empty($data['customer_id'])) {
        $stmt = $pdo->prepare("SELECT id FROM partner_companies WHERE id = ?");
        $stmt->execute([$data['customer_id']]);
        if (!$stmt->fetch()) {
            $errors[] = 'Khách hàng không tồn tại';
        }
    }

    // Kiểm tra sale có tồn tại không
    if (!empty($data['sale_id'])) {
        $stmt = $pdo->prepare("SELECT id FROM staffs WHERE id = ? AND department = 'SALE Dept.' AND status = 'active'");
        $stmt->execute([$data['sale_id']]);
        if (!$stmt->fetch()) {
            $errors[] = 'Sale phụ trách không tồn tại hoặc không hoạt động';
        }
    }

    // Nếu có lỗi validation
    if (!empty($errors)) {
        error_log("Validation errors: " . implode(', ', $errors));
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
        exit;
    }
    
    // Đảm bảo chỉ có một response
    if (headers_sent()) {
        error_log("Headers already sent in update_maintenance_request.php");
        exit;
    }

    // Xử lý ngày tháng
    $expected_start = !empty($data['expected_start']) ? $data['expected_start'] : null;
    $expected_end = !empty($data['expected_end']) ? $data['expected_end'] : null;

    // Update database
    $sql = "UPDATE maintenance_requests SET 
            request_code = ?, po_number = ?, no_contract_po = ?, contract_type = ?, 
            request_detail_type = ?, email_subject_customer = ?, email_subject_internal = ?, 
            expected_start = ?, expected_end = ?, customer_id = ?, contact_person = ?, 
            contact_phone = ?, sale_id = ?, requester_notes = ?, maintenance_manager = ?, 
            maintenance_status = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $data['request_code'],
        $data['po_number'],
        $data['no_contract_po'],
        $data['contract_type'],
        $data['request_detail_type'],
        $data['email_subject_customer'],
        $data['email_subject_internal'],
        $expected_start,
        $expected_end,
        $data['customer_id'],
        $data['contact_person'],
        $data['contact_phone'],
        $data['sale_id'],
        $data['requester_notes'],
        $data['maintenance_manager'],
        $data['maintenance_status'],
        $data['id']
    ]);

    if ($result) {
        // Log hoạt động
        $log_message = "Cập nhật yêu cầu bảo trì: {$data['request_code']}";
        $log_sql = "INSERT INTO user_activity_logs (user_id, activity, details, ip_address) VALUES (?, ?, ?, ?)";
        $log_stmt = $pdo->prepare($log_sql);
        $log_stmt->execute([
            getCurrentUserId(),
            'UPDATE_MAINTENANCE_REQUEST',
            $log_message,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        // Đảm bảo chỉ có một response
        if (!headers_sent()) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật yêu cầu bảo trì thành công'
            ]);
        }
        exit;
    } else {
        throw new Exception('Không thể cập nhật yêu cầu bảo trì');
    }

} catch (PDOException $e) {
    error_log("Database error in update_maintenance_request.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error in update_maintenance_request.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
?> 