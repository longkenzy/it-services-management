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
    // Đọc JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    $id = $input['id'] ?? '';
    $task_description = $input['task_description'] ?? $input['task_name'] ?? '';
    $notes = $input['notes'] ?? $input['task_note'] ?? '';
    $start_date = $input['start_date'] ?? '';
    $end_date = $input['end_date'] ?? '';
    $status = $input['status'] ?? '';
    
    // Debug: Log input data
    error_log("Update task input: " . json_encode($input));
    error_log("Update task ID: " . $id);
    error_log("Update task task_name: " . $task_description);
    error_log("Update task task_note: " . $notes);
    
    if (empty($id)) {
        throw new Exception('ID task không được để trống');
    }
    
    // Cập nhật deployment task
    $sql = "UPDATE deployment_tasks SET 
            task_description = ?, 
            start_date = ?, 
            end_date = ?, 
            status = ?,
            updated_at = NOW()
            WHERE id = ?";
    
    error_log("Update task SQL: " . $sql);
    error_log("Update task params: " . json_encode([$task_description, $start_date, $end_date, $status, $id]));
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $task_description,
        $start_date,
        $end_date,
        $status,
        $id
    ]);
    
    error_log("Update task result: " . ($result ? 'true' : 'false'));
    
    if ($result) {
        $auto_updated_items = [];
        
        // Gọi API auto update status nếu task được cập nhật thành completed
        if ($status === 'completed') {
            $auto_update_data = [
                'type' => 'task',
                'id' => $id
            ];
            
            // Gọi API auto update
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . '/it-services-management/api/auto_update_status.php');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($auto_update_data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'X-Requested-With: XMLHttpRequest'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            // Parse response để lấy thông tin auto update
            $auto_update_result = json_decode($response, true);
            if ($auto_update_result && isset($auto_update_result['updated_items'])) {
                $auto_updated_items = $auto_update_result['updated_items'];
            }
            
            // Log auto update response
            error_log("Auto update response for task {$id}: " . $response);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật task thành công',
            'auto_updated_items' => $auto_updated_items
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