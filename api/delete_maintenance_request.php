<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once '../config/db.php';
require_once '../includes/session.php';

// Kiểm tra đăng nhập
if (null === getCurrentUserId()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

// Chỉ chấp nhận POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method không được hỗ trợ']);
    exit;
}

// Lấy dữ liệu từ request body
$input = json_decode(file_get_contents('php://input'), true);
$request_id = $input['id'] ?? null;

if (!$request_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thiếu ID yêu cầu triển khai']);
    exit;
}

try {
    // Debug: Log request
    error_log("Attempting to delete maintenance request ID: " . $request_id);
    
    // Bắt đầu transaction
    $pdo->beginTransaction();
    
    // Kiểm tra xem maintenance request có tồn tại không
    $stmt = $pdo->prepare("SELECT id, request_code FROM maintenance_requests WHERE id = ?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        $pdo->rollBack();
        error_log("maintenance request not found: " . $request_id);
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy yêu cầu triển khai']);
        exit;
    }
    
    error_log("Found maintenance request: " . $request['request_code']);
    
    // Log hoạt động trước khi xóa
    
    // Xóa tất cả maintenance tasks liên quan trước (cascade từ cases)
    $stmt = $pdo->prepare("DELETE dt FROM maintenance_tasks dt 
                          INNER JOIN maintenance_cases dc ON dt.maintenance_case_id = dc.id 
                          WHERE dc.maintenance_request_id = ?");
    $stmt->execute([$request_id]);
    $deleted_tasks = $stmt->rowCount();
    
    // Xóa tất cả maintenance cases liên quan
    $stmt = $pdo->prepare("DELETE FROM maintenance_cases WHERE maintenance_request_id = ?");
    $stmt->execute([$request_id]);
    $deleted_cases = $stmt->rowCount();
    
    // Xóa maintenance request
    $stmt = $pdo->prepare("DELETE FROM maintenance_requests WHERE id = ?");
    $stmt->execute([$request_id]);
    
    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Không thể xóa yêu cầu triển khai']);
        exit;
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Log thành công
    error_log("Successfully deleted maintenance request: " . $request['request_code'] . 
              " (Cases: $deleted_cases, Tasks: $deleted_tasks)");
    
    echo json_encode([
        'success' => true, 
        'message' => 'Xóa yêu cầu triển khai thành công!',
        'deleted_cases' => $deleted_cases,
        'deleted_tasks' => $deleted_tasks,
        'request_code' => $request['request_code']
    ]);
    
} catch (PDOException $e) {
    // Rollback nếu có lỗi
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Database error in delete_maintenance_request.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Rollback nếu có lỗi
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("General error in delete_maintenance_request.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?> 
