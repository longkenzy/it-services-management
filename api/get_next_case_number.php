<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/session.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../config/db.php';

// Chỉ chấp nhận POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Đọc dữ liệu JSON từ request body
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    $year = $input['year'] ?? date('y');
    $month = $input['month'] ?? date('m');
    
    // Lấy số thứ tự lớn nhất hiện có trong tháng
    $stmt = $pdo->prepare("
        SELECT MAX(CAST(SUBSTRING(case_code, 8, 3) AS UNSIGNED)) as max_seq
        FROM deployment_cases
        WHERE case_code LIKE ?
        AND MONTH(created_at) = ?
        AND YEAR(created_at) = ?
    ");
    $stmt->execute(["CTK{$year}{$month}%", intval($month), 2000 + intval($year)]);
    $result = $stmt->fetch();
    $max_seq = $result['max_seq'] ?? null;
    $sequence = ($max_seq === null) ? 1 : ($max_seq + 1);
    
    $case_code = "CTK{$year}{$month}" . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    
    echo json_encode([
        'success' => true,
        'case_code' => $case_code,
        'sequence' => $sequence
    ]);
    
} catch (Exception $e) {
    error_log("Error getting next case number: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?> 