<?php
/**
 * API kiểm tra session
 */

header('Content-Type: application/json');

try {
    require_once '../includes/session.php';
    
    $response = [
        'success' => true,
        'session_status' => 'unknown',
        'user_info' => null,
        'session_id' => session_id(),
        'session_data' => $_SESSION ?? []
    ];
    
    if (isLoggedIn()) {
        $current_user = getCurrentUser();
        $response['session_status'] = 'logged_in';
        $response['user_info'] = [
            'id' => $current_user['id'],
            'username' => $current_user['username'],
            'fullname' => $current_user['fullname'],
            'role' => $current_user['role'] ?? 'unknown'
        ];
    } else {
        $response['session_status'] = 'not_logged_in';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?> 