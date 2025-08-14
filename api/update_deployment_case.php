<?php
/**
 * API cập nhật deployment case
 * File: api/update_deployment_case.php
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

// Kiểm tra method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method không được hỗ trợ']);
    exit;
}

try {
    // Lấy dữ liệu từ form
    $id = $_POST['id'] ?? '';
    $case_code = $_POST['case_code'] ?? '';
    $case_description = $_POST['case_description'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $status = $_POST['status'] ?? '';
    
    // Validate dữ liệu
    if (empty($id)) {
        throw new Exception('ID case không được để trống');
    }
    
    if (empty($status)) {
        throw new Exception('Trạng thái không được để trống');
    }
    
    // Cập nhật deployment case
    $sql = "UPDATE deployment_cases SET 
            case_description = ?,
            notes = ?,
            start_date = ?,
            end_date = ?,
            status = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $case_description,
        $notes,
        $start_date ?: null,
        $end_date ?: null,
        $status,
        $id
    ]);
    
    if ($result) {
        $auto_updated_items = [];
        
        // Gọi API auto update status nếu case được cập nhật thành completed
        if ($status === 'completed') {
            $auto_update_data = [
                'type' => 'case',
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
            error_log("Auto update response for case {$id}: " . $response);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Cập nhật case thành công!',
            'auto_updated_items' => $auto_updated_items
        ]);
    } else {
        throw new Exception('Không thể cập nhật case');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 