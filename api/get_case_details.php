<?php
// Bảo vệ file khỏi truy cập trực tiếp (chỉ cho phép từ cùng domain)
if (!isset($_SERVER['HTTP_REFERER']) || !str_contains($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST'])) {
    // Cho phép truy cập từ AJAX requests
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(403);
    exit('Access denied.');
    }
}
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
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
    
    // Lấy chi tiết case với thông tin nhân sự
    $stmt = $pdo->prepare("
        SELECT 
            ic.*,
            r.fullname as requester_name,
            h.fullname as handler_name
        FROM internal_cases ic
        LEFT JOIN staffs r ON ic.requester_id = r.id
        LEFT JOIN staffs h ON ic.handler_id = h.id
        WHERE ic.id = ?
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