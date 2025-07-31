<?php
header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../config/db.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

try {
    // Lấy dữ liệu từ form
    $data = [
        'request_code' => $_POST['request_code'] ?? '',
        'po_number' => $_POST['po_number'] ?? '',
        'no_contract_po' => isset($_POST['no_contract_po']) ? 1 : 0,
        'contract_type' => $_POST['contract_type'] ?? '',
        'request_detail_type' => $_POST['request_detail_type'] ?? '',
        'email_subject_customer' => $_POST['email_subject_customer'] ?? '',
        'email_subject_internal' => $_POST['email_subject_internal'] ?? '',
        'expected_start' => $_POST['expected_start'] ?? '',
        'expected_end' => $_POST['expected_end'] ?? '',
        'customer_id' => $_POST['customer_id'] ?? '',
        'contact_person' => $_POST['contact_person'] ?? '',
        'contact_phone' => $_POST['contact_phone'] ?? '',
        'sale_id' => $_POST['sale_id'] ?? '',
        'requester_notes' => $_POST['requester_notes'] ?? '',
        'maintenance_manager' => $_POST['maintenance_manager'] ?? '',
        'maintenance_status' => $_POST['maintenance_status'] ?? '',
        'created_by' => getCurrentUserId()
    ];

    // Validation
    $errors = [];
    
    if (empty($data['request_code'])) {
        $errors[] = 'Mã yêu cầu không được để trống';
    }
    
    if (empty($data['customer_id'])) {
        $errors[] = 'Vui lòng chọn khách hàng';
    }
    
    if (empty($data['sale_id'])) {
        $errors[] = 'Vui lòng chọn sale phụ trách';
    }
    
    if (empty($data['maintenance_status'])) {
        $errors[] = 'Vui lòng chọn trạng thái bảo trì';
    }

    // Kiểm tra mã yêu cầu đã tồn tại chưa
    if (!empty($data['request_code'])) {
        $stmt = $pdo->prepare("SELECT id FROM maintenance_requests WHERE request_code = ?");
        $stmt->execute([$data['request_code']]);
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
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
        exit;
    }

    // Xử lý ngày tháng
    $expected_start = !empty($data['expected_start']) ? $data['expected_start'] : null;
    $expected_end = !empty($data['expected_end']) ? $data['expected_end'] : null;

    // Insert vào database
    $sql = "INSERT INTO maintenance_requests (
        request_code, po_number, no_contract_po, contract_type, request_detail_type,
        email_subject_customer, email_subject_internal, expected_start, expected_end,
        customer_id, contact_person, contact_phone, sale_id, requester_notes, maintenance_manager, maintenance_status, created_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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
        $data['created_by']
    ]);

    if ($result) {
        $request_id = $pdo->lastInsertId();
        
        // Log hoạt động
        if ($data['created_by']) {
            $log_message = "Tạo yêu cầu bảo trì mới: {$data['request_code']}";
            $log_sql = "INSERT INTO user_activity_logs (user_id, activity, details, ip_address) VALUES (?, ?, ?, ?)";
            $log_stmt = $pdo->prepare($log_sql);
            $log_stmt->execute([
                $data['created_by'],
                'CREATE_MAINTENANCE_REQUEST',
                $log_message,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Tạo yêu cầu bảo trì thành công',
            'request_id' => $request_id,
            'request_code' => $data['request_code']
        ]);
    } else {
        throw new Exception('Không thể tạo yêu cầu bảo trì');
    }

} catch (PDOException $e) {
    error_log("Database error in create_maintenance_request.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error in create_maintenance_request.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
?> 