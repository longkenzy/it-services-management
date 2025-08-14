<?php
/**
 * API cập nhật deployment task
 * File: api/update_deployment_task.php
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

// Chỉ cho phép POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method không được phép']);
    exit;
}

try {
    $id = $_POST['id'] ?? '';
    $task_description = $_POST['task_description'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $status = $_POST['status'] ?? '';
    
    if (empty($id)) {
        throw new Exception('ID task không được để trống');
    }
    
    // Cập nhật deployment task
    $sql = "UPDATE deployment_tasks SET 
            task_description = ?, 
            notes = ?, 
            start_date = ?, 
            end_date = ?, 
            status = ?,
            updated_at = NOW()
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $task_description,
        $notes,
        $start_date,
        $end_date,
        $status,
        $id
    ]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật task thành công'
        ]);
    } else {
        throw new Exception('Không thể cập nhật task');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 