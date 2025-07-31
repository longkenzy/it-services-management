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
    $request_id = $input['id'] ?? null;
    
    if (!$request_id) {
        echo json_encode(['success' => false, 'message' => 'ID yêu cầu không hợp lệ']);
        exit;
    }
    
    // Kiểm tra yêu cầu có tồn tại không
    $stmt = $pdo->prepare("SELECT request_code FROM maintenance_requests WHERE id = ?");
    $stmt->execute([$request_id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Yêu cầu bảo trì không tồn tại']);
        exit;
    }
    
    // Bắt đầu transaction
    $pdo->beginTransaction();
    
    try {
        // Xóa các task bảo trì liên quan
        $stmt = $pdo->prepare("DELETE FROM maintenance_tasks WHERE maintenance_request_id = ?");
        $stmt->execute([$request_id]);
        
        // Xóa các case bảo trì liên quan
        $stmt = $pdo->prepare("DELETE FROM maintenance_cases WHERE maintenance_request_id = ?");
        $stmt->execute([$request_id]);
        
        // Xóa yêu cầu bảo trì
        $stmt = $pdo->prepare("DELETE FROM maintenance_requests WHERE id = ?");
        $stmt->execute([$request_id]);
        
        // Log hoạt động
        $log_message = "Xóa yêu cầu bảo trì: {$request['request_code']}";
        $log_sql = "INSERT INTO user_activity_logs (user_id, activity, details, ip_address) VALUES (?, ?, ?, ?)";
        $log_stmt = $pdo->prepare($log_sql);
        $log_stmt->execute([
            getCurrentUserId(),
            'DELETE_MAINTENANCE_REQUEST',
            $log_message,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Xóa yêu cầu bảo trì thành công'
        ]);
        
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in delete_maintenance_request.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi xóa yêu cầu bảo trì: ' . $e->getMessage()
    ]);
}
?> 