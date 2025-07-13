<?php
// Bật hiển thị lỗi để debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Log để debug
error_log("Debug: Script started");

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    error_log("Debug: User not logged in");
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

error_log("Debug: User logged in, ID: " . $_SESSION['user_id']);

require_once '../config/db.php';
error_log("Debug: Database included");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Debug: Method not POST");
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

error_log("Debug: POST method confirmed");

try {
    error_log("Debug: Starting validation");
    
    // Validate required fields
    $required_fields = ['case_number', 'progress', 'case_description', 'assigned_to', 'status'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            error_log("Debug: Missing field: $field");
            echo json_encode(['success' => false, 'error' => "Field '$field' is required"]);
            exit;
        }
    }
    
    error_log("Debug: All required fields present");
    
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
    
    error_log("Debug: Form data extracted");
    error_log("Debug: case_number = $case_number");
    error_log("Debug: progress = $progress");
    error_log("Debug: assigned_to = $assigned_to");
    error_log("Debug: created_by = $created_by");
    
    // Check if case number already exists
    error_log("Debug: Checking if case number exists");
    $stmt = $pdo->prepare("SELECT id FROM deployment_cases WHERE case_number = ?");
    $stmt->execute([$case_number]);
    if ($stmt->fetch()) {
        error_log("Debug: Case number already exists");
        echo json_encode(['success' => false, 'error' => 'Case number already exists']);
        exit;
    }
    
    error_log("Debug: Case number is unique");
    
    // Insert new case
    error_log("Debug: Starting insert");
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
    error_log("Debug: Insert successful, case_id = $case_id");
    
    // Log activity (silently fail if error)
    try {
        error_log("Debug: Starting user activity log");
        logUserActivity('create_deployment_case', "Created deployment case: $case_number");
        error_log("Debug: User activity logged successfully");
    } catch (Exception $e) {
        error_log("Debug: Failed to log user activity: " . $e->getMessage());
    }
    
    error_log("Debug: Sending success response");
    echo json_encode([
        'success' => true,
        'message' => 'Deployment case created successfully',
        'case_id' => $case_id
    ]);
    
} catch (PDOException $e) {
    error_log("Debug: PDO Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Debug: General Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}

error_log("Debug: Script completed");
?> 