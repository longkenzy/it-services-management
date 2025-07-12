<?php
/**
 * Debug version of get_staffs.php
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include environment config
require_once '../config/environment.php';

// Include các file cần thiết
require_once '../config/db.php';
require_once '../includes/session.php';

// Thiết lập header cho JSON response
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'debug' => 'Starting debug...',
    'environment' => ENVIRONMENT,
    'session_started' => session_status() === PHP_SESSION_ACTIVE,
    'user_logged_in' => isset($_SESSION['user_id']),
    'pdo_connected' => isset($pdo) && $pdo instanceof PDO
]);

// Test database connection
try {
    $stmt = $pdo->query('SELECT 1');
    echo "\n" . json_encode(['db_test' => 'OK']);
} catch (Exception $e) {
    echo "\n" . json_encode(['db_error' => $e->getMessage()]);
}

// Test staffs table
try {
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM staffs');
    $count = $stmt->fetch()['count'];
    echo "\n" . json_encode(['staffs_count' => $count]);
} catch (Exception $e) {
    echo "\n" . json_encode(['staffs_error' => $e->getMessage()]);
}

?> 