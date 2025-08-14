<?php
/**
 * API lấy dữ liệu deployment task
 * File: api/get_deployment_task.php
 */

require_once '../config/db.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (null === getCurrentUserId()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

try {
    $id = $_GET['id'] ?? '';
    
    if (empty($id)) {
        throw new Exception('ID task không được để trống');
    }
    
    // Lấy thông tin deployment task
    $sql = "SELECT * FROM deployment_tasks WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$task) {
        throw new Exception('Task không tồn tại');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $task
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
