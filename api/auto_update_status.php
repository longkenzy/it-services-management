<?php
/**
 * Auto Update Status API
 * File: api/auto_update_status.php
 * Purpose: Tự động cập nhật trạng thái case và deployment request
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    require_once '../includes/session.php';
    require_once '../config/db.php';
    
    // Check if user is logged in
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }
    
    // Get input data
    $raw_input = file_get_contents('php://input');
    
    if (empty($raw_input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No input data']);
        exit;
    }
    
    $input = json_decode($raw_input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON: ' . json_last_error_msg()]);
        exit;
    }
    
    $type = $input['type'] ?? ''; // 'task' or 'case'
    $id = $input['id'] ?? 0;
    
    if (empty($type) || empty($id)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Type and ID required']);
        exit;
    }
    
    $updated_items = [];
    
    if ($type === 'task') {
        // Khi task được cập nhật, kiểm tra case
        $updated_items = updateCaseStatus($id);
    } elseif ($type === 'case') {
        // Khi case được cập nhật, kiểm tra deployment request
        $updated_items = updateDeploymentRequestStatus($id);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully',
        'updated_items' => $updated_items
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

/**
 * Cập nhật trạng thái case khi tất cả task hoàn thành
 */
function updateCaseStatus($task_id) {
    global $pdo;
    $updated_items = [];
    
    // Lấy thông tin case từ task
    $stmt = $pdo->prepare("SELECT deployment_case_id FROM deployment_tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $task = $stmt->fetch();
    
    if (!$task) {
        return $updated_items;
    }
    
    $case_id = $task['deployment_case_id'];
    
    // Kiểm tra xem tất cả task của case này có hoàn thành không
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_tasks,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
        FROM deployment_tasks 
        WHERE deployment_case_id = ?
    ");
    $stmt->execute([$case_id]);
    $result = $stmt->fetch();
    
    if ($result['total_tasks'] > 0 && $result['total_tasks'] == $result['completed_tasks']) {
        // Tất cả task đã hoàn thành, cập nhật case thành completed
        $stmt = $pdo->prepare("UPDATE deployment_cases SET status = 'completed', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$case_id]);
        
        $updated_items[] = [
            'type' => 'case',
            'id' => $case_id,
            'status' => 'completed'
        ];
        
        // Log activity
        logUserActivity('auto_update_case_status', "Auto updated case {$case_id} to completed");
    }
    
    return $updated_items;
}

/**
 * Cập nhật trạng thái deployment request khi tất cả case hoàn thành
 */
function updateDeploymentRequestStatus($case_id) {
    global $pdo;
    $updated_items = [];
    
    // Lấy thông tin deployment request từ case
    $stmt = $pdo->prepare("SELECT deployment_request_id FROM deployment_cases WHERE id = ?");
    $stmt->execute([$case_id]);
    $case = $stmt->fetch();
    
    if (!$case) {
        return $updated_items;
    }
    
    $request_id = $case['deployment_request_id'];
    
    // Kiểm tra xem tất cả case của request này có hoàn thành không
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_cases,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_cases
        FROM deployment_cases 
        WHERE deployment_request_id = ?
    ");
    $stmt->execute([$request_id]);
    $result = $stmt->fetch();
    
    if ($result['total_cases'] > 0 && $result['total_cases'] == $result['completed_cases']) {
        // Tất cả case đã hoàn thành, cập nhật deployment request thành completed
        $stmt = $pdo->prepare("UPDATE deployment_requests SET deployment_status = 'completed', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$request_id]);
        
        $updated_items[] = [
            'type' => 'deployment_request',
            'id' => $request_id,
            'status' => 'completed'
        ];
        
        // Log activity
        logUserActivity('auto_update_deployment_status', "Auto updated deployment request {$request_id} to completed");
    }
    
    return $updated_items;
}
?>
