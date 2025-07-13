<?php
// Tắt hiển thị lỗi để tránh output thừa
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Define INCLUDED constant before including db.php
define('INCLUDED', true);
require_once '../config/db.php';
require_once '../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Validate required fields
    $required_fields = ['case_number', 'progress', 'case_description', 'assigned_to', 'status'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'error' => "Field '$field' is required"]);
            exit;
        }
    }
    
    // Get form data
    $case_number = $_POST['case_number'];
    $progress = $_POST['progress'];
    $case_description = $_POST['case_description'];
    $notes = $_POST['notes'] ?? '';
    $assigned_to = intval($_POST['assigned_to']);
    $priority = $_POST['priority'] ?? 'onsite';
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
    $status = $_POST['status'];
    $created_by = $_SESSION['user_id'];
    
    // Check if case number already exists
    $stmt = $pdo->prepare("SELECT id FROM deployment_cases WHERE case_number = ?");
    $stmt->execute([$case_number]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Case number already exists']);
        exit;
    }
    
    // Insert new case
    $stmt = $pdo->prepare("
        INSERT INTO deployment_cases (
            case_number, progress, case_description, notes, 
            created_by, assigned_to, priority, start_date, due_date, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $case_number, $progress, $case_description, $notes,
        $created_by, $assigned_to, $priority, $start_date, $due_date, $status
    ]);
    
    $case_id = $pdo->lastInsertId();
    
    // Log activity (silently fail if error)
    try {
        logUserActivity('create_deployment_case', "Created deployment case: $case_number");
    } catch (Exception $e) {
        // Log error but don't break the response
        error_log("Failed to log user activity: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Deployment case created successfully',
        'case_id' => $case_id
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?> 