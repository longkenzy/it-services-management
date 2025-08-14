<?php
header('Content-Type: application/json');
require_once '../includes/session.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $case_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($case_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid case ID']);
        exit;
    }
    
    // Lấy chi tiết maintenance case với thông tin nhân sự và request
    $stmt = $pdo->prepare("
        SELECT 
            mc.*, 
            mc.maintenance_request_id, 
            mr.request_code,
            s.fullname as assigned_to_name,
            creator.fullname as created_by_name
        FROM maintenance_cases mc
        LEFT JOIN maintenance_requests mr ON mc.maintenance_request_id = mr.id
        LEFT JOIN staffs s ON mc.assigned_to = s.id
        LEFT JOIN staffs creator ON mc.created_by = creator.id
        WHERE mc.id = ?
    ");
    
    $stmt->execute([$case_id]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$case) {
        echo json_encode(['success' => false, 'error' => 'Case not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $case
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
