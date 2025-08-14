<?php
// Simplified version of update_case.php for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    // Include dependencies first (before session_start)
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
    
    // Validate case_id
    if (empty($input['case_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Case ID required']);
        exit;
    }
    
    $case_id = $input['case_id'];
    $current_user_id = $_SESSION['user_id'];
    
    // Get current case
    $stmt = $pdo->prepare("SELECT * FROM internal_cases WHERE id = ?");
    $stmt->execute([$case_id]);
    $current_case = $stmt->fetch();
    
    if (!$current_case) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Case not found']);
        exit;
    }
    
    // Simple update - just status and notes
    $updates = [];
    $params = [];
    
    if (isset($input['status'])) {
        $updates[] = "status = ?";
        $params[] = $input['status'];
    }
    
    if (isset($input['notes'])) {
        $updates[] = "notes = ?";
        $params[] = $input['notes'];
    }
    
    if (empty($updates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No data to update']);
        exit;
    }
    
    // Add updated_at
    $updates[] = "updated_at = NOW()";
    $params[] = $case_id;
    
    // Execute update
    $sql = "UPDATE internal_cases SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    
    if (!$stmt->execute($params)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Database update failed']);
        exit;
    }
    
    // Log activity
    try {
        $log_stmt = $pdo->prepare("
            INSERT INTO user_activity_logs (user_id, activity, details, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $log_stmt->execute([
            $current_user_id, 
            'update_case', 
            'Updated case: ' . $current_case['case_number'],
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        // Log error but don't crash
        error_log("Failed to log activity: " . $e->getMessage());
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Case updated successfully'
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
?>
