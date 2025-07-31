<?php
require_once '../config/db.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

if (!isset(getCurrentUserId())) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$request_id = $_GET['id'] ?? null;

if (!$request_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Request ID is required']);
    exit;
}

try {
    $sql = "SELECT 
                dr.*,
                pc.name as customer_name,
                pc.contact_person as customer_contact_person,
                pc.contact_phone as customer_contact_phone
            FROM deployment_requests dr
            LEFT JOIN partner_companies pc ON dr.customer_id = pc.id
            WHERE dr.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$request_id]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        http_response_code(404);
        echo json_encode(['error' => 'Request not found']);
        exit;
    }
    
    echo json_encode(['success' => true, 'data' => $request]);
    
} catch (PDOException $e) {
    error_log("Database error in get_deployment_request.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?> 