<?php
// Simple test to check if the API works
header('Content-Type: application/json');

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';

// Start session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['fullname'] = 'Administrator';
$_SESSION['role'] = 'admin';

try {
    require_once 'includes/session.php';
    require_once 'config/db.php';
    
    echo json_encode([
        'success' => true,
        'message' => 'API test successful',
        'session_status' => session_status(),
        'is_logged_in' => isLoggedIn(),
        'user_id' => getCurrentUserId(),
        'is_admin' => isAdmin(),
        'can_edit' => canEditInternalCase(),
        'database_connected' => isset($pdo) && $pdo !== null,
        'request_method' => $_SERVER['REQUEST_METHOD']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
