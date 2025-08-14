<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';
require_once '../includes/session.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Debug: Log dữ liệu đầu vào
    error_log("POST data: " . print_r($_POST, true));
    
    // Lấy dữ liệu từ form
    $data = [
        'request_code' => trim($_POST['request_code'] ?? ''),
        'po_number' => trim($_POST['po_number'] ?? ''),
        'no_contract_po' => isset($_POST['no_contract_po']) ? 1 : 0,
        'contract_type' => trim($_POST['contract_type'] ?? ''),
        'request_detail_type' => trim($_POST['request_detail_type'] ?? ''),
        'email_subject_customer' => trim($_POST['email_subject_customer'] ?? ''),
        'email_subject_internal' => trim($_POST['email_subject_internal'] ?? ''),
        'expected_start' => trim($_POST['expected_start'] ?? ''),
        'expected_end' => trim($_POST['expected_end'] ?? ''),
        'customer_id' => trim($_POST['customer_id'] ?? ''),
        'contact_person' => trim($_POST['contact_person'] ?? ''),
        'contact_phone' => trim($_POST['contact_phone'] ?? ''),
        'sale_id' => trim($_POST['sale_id'] ?? ''),
        'requester_notes' => trim($_POST['requester_notes'] ?? ''),
        'maintenance_manager' => trim($_POST['maintenance_manager'] ?? 'Trần Nguyễn Anh Khoa'),
        'maintenance_status' => trim($_POST['maintenance_status'] ?? 'Tiếp nhận'),
        'created_by' => null
    ];

    // Kiểm tra xem user hiện tại có tồn tại trong bảng staffs không
    $current_user_id = getCurrentUserId();
    if ($current_user_id && !empty($current_user_id)) {
        $stmt = $pdo->prepare("SELECT id FROM staffs WHERE id = ?");
        $stmt->execute([$current_user_id]);
        if ($stmt->fetch()) {
            $data['created_by'] = $current_user_id;
        }
    }

    // Validation
    $errors = [];
    
    // Kiểm tra các trường bắt buộc
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
        $errors[] = 'Vui lòng chọn trạng thái triển khai';
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

    // Kiểm tra sale có tồn tại không (theo logic mới: tất cả nhân viên trừ IT Dept và chưa nghỉ việc)
    if (!empty($data['sale_id'])) {
        $stmt = $pdo->prepare("SELECT id FROM staffs WHERE id = ? AND (department != 'IT Dept.' OR department IS NULL) AND (resigned != 1 OR resigned IS NULL)");
        $stmt->execute([$data['sale_id']]);
        if (!$stmt->fetch()) {
            $errors[] = 'Sale phụ trách không tồn tại hoặc không hoạt động';
        }
    }

    // Debug: Log errors nếu có
    if (!empty($errors)) {
        error_log("Validation errors: " . print_r($errors, true));
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
        customer_id, contact_person, contact_phone, sale_id, requester_notes,
        maintenance_manager, maintenance_status, created_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    
    // Debug: Log SQL và data
    error_log("SQL: " . $sql);
    error_log("Data: " . print_r([
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
    ], true));
    
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
        
        // Debug: Log request_id
        error_log("Created request_id: " . $request_id);
        
        // Log hoạt động (chỉ log nếu có user_id hợp lệ)
        if ($data['created_by']) {
            $log_message = "Tạo yêu cầu triển khai mới: {$data['request_code']}";
            $log_sql = "INSERT INTO user_activity_logs (user_id, activity, details, ip_address) VALUES (?, ?, ?, ?)";
            $log_stmt = $pdo->prepare($log_sql);
            $log_stmt->execute([
                $data['created_by'],
                'CREATE_maintenance_REQUEST',
                $log_message,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Tạo yêu cầu triển khai thành công',
            'request_id' => $request_id,
            'request_code' => $data['request_code']
        ]);
    } else {
        throw new Exception('Không thể tạo yêu cầu triển khai');
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
