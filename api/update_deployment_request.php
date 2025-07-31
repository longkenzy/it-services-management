<?php
require_once '../config/db.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

$current_user_id = getCurrentUserId();
if (!$current_user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Debug logging

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$request_id = $input['id'] ?? null;
$fields = [
    'request_code', 'po_number', 'no_contract_po', 'contract_type', 'request_detail_type',
    'email_subject_customer', 'email_subject_internal', 'expected_start', 'expected_end',
    'customer_id', 'contact_person', 'contact_phone', 'sale_id', 'requester_notes',
    'deployment_manager', 'deployment_status'
];

$data = [];
foreach ($fields as $field) {
    $value = trim($input[$field] ?? '');
    // Handle date fields - convert empty string to null
    if (in_array($field, ['expected_start', 'expected_end']) && empty($value)) {
        $data[$field] = null;
    } else {
        $data[$field] = $value;
    }
}

// Validation
$errors = [];
if (empty($data['request_code']) || empty($data['customer_id']) || empty($data['sale_id']) || empty($data['deployment_status'])) {
    $errors[] = 'Vui lòng nhập đầy đủ các trường bắt buộc.';
}

// Validate foreign keys exist
if (!empty($data['customer_id'])) {
    $customer_check = $pdo->prepare("SELECT id FROM partner_companies WHERE id = ?");
    $customer_check->execute([$data['customer_id']]);
    if (!$customer_check->fetch()) {
        $errors[] = 'Khách hàng không tồn tại.';
    }
}

if (!empty($data['sale_id'])) {
    $sale_check = $pdo->prepare("SELECT id FROM staffs WHERE id = ? AND department = 'SALE Dept.' AND status = 'active'");
    $sale_check->execute([$data['sale_id']]);
    if (!$sale_check->fetch()) {
        $errors[] = 'Sale phụ trách không tồn tại hoặc không hoạt động.';
    }
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['error' => implode(', ', $errors)]);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $sql = "UPDATE deployment_requests SET 
                request_code = ?, po_number = ?, no_contract_po = ?, contract_type = ?, 
                request_detail_type = ?, email_subject_customer = ?, email_subject_internal = ?, 
                expected_start = ?, expected_end = ?, customer_id = ?, contact_person = ?, 
                contact_phone = ?, sale_id = ?, requester_notes = ?, deployment_manager = ?, 
                deployment_status = ?, updated_at = NOW()
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $data['request_code'],
        $data['po_number'],
        !empty($data['no_contract_po']) ? 1 : 0,
        $data['contract_type'],
        $data['request_detail_type'],
        $data['email_subject_customer'],
        $data['email_subject_internal'],
        $data['expected_start'],
        $data['expected_end'],
        $data['customer_id'],
        $data['contact_person'],
        $data['contact_phone'],
        $data['sale_id'],
        $data['requester_notes'],
        $data['deployment_manager'],
        $data['deployment_status'],
        $request_id
    ]);
    
    // Log activity
    $activity_sql = "INSERT INTO user_activity_logs (user_id, activity, details, created_at) 
                     VALUES (?, ?, ?, NOW())";
    $activity_stmt = $pdo->prepare($activity_sql);
    $activity_stmt->execute([
        getCurrentUserId(),
        'UPDATE deployment_requests',
        'Updated deployment request: ' . $data['request_code'] . ' (ID: ' . $request_id . ')'
    ]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Cập nhật yêu cầu triển khai thành công!']);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Database error in update_deployment_request.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 