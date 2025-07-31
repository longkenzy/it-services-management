<?php
// Bảo vệ file khỏi truy cập trực tiếp (chỉ cho phép từ cùng domain)
if (!isset($_SERVER['HTTP_REFERER']) || !str_contains($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST'])) {
    // Cho phép truy cập từ AJAX requests
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
        // Cho phép truy cập trực tiếp cho testing
        if (!isset($_GET['test'])) {
            http_response_code(403);
            exit('Access denied.');
        }
    }
}
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/session.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Please login first']);
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
    
    // Lấy chi tiết deployment case với thông tin nhân sự và request
    $stmt = $pdo->prepare("
        SELECT 
            dc.*, 
            dc.deployment_request_id, 
            dr.request_code,
            s.fullname as assigned_to_name,
            creator.fullname as created_by_name
        FROM deployment_cases dc
        LEFT JOIN deployment_requests dr ON dc.deployment_request_id = dr.id
        LEFT JOIN staffs s ON dc.assigned_to = s.id
        LEFT JOIN staffs creator ON dc.created_by = creator.id
        WHERE dc.id = ?
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