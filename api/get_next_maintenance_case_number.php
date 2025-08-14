<?php
header('Content-Type: application/json');
require_once '../includes/session.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Lấy dữ liệu từ request
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Lấy năm và tháng hiện tại
    $year = date('y'); // 2 số cuối năm
    $month = date('m'); // Tháng hiện tại
    $prefix = "CBT{$year}{$month}";
    
    // Tìm số thứ tự cao nhất trong tháng hiện tại
    $stmt = $pdo->prepare("
        SELECT case_code
        FROM maintenance_cases 
        WHERE case_code LIKE ?
        ORDER BY case_code DESC
        LIMIT 1
    ");
    $stmt->execute([$prefix . '%']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Lấy 3 chữ số cuối từ mã case cuối cùng
        $lastCode = $result['case_code'];
        $lastSequence = intval(substr($lastCode, -3));
        $nextSequence = $lastSequence + 1;
    } else {
        // Nếu chưa có mã nào trong tháng này
        $nextSequence = 1;
    }
    
    $caseCode = $prefix . str_pad($nextSequence, 3, '0', STR_PAD_LEFT);
    
    echo json_encode([
        'success' => true,
        'sequence' => $nextSequence,
        'case_code' => $caseCode,
        'prefix' => $prefix,
        'year' => $year,
        'month' => $month
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?> 
