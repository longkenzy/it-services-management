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
    // Lấy năm và tháng hiện tại
    $year = date('y'); // 2 số cuối năm
    $month = date('m'); // Tháng hiện tại
    $prefix = "BT{$year}{$month}";
    
    // Tìm số thứ tự cao nhất trong tháng hiện tại
    $stmt = $pdo->prepare("
        SELECT request_code
        FROM maintenance_requests 
        WHERE request_code LIKE ?
        ORDER BY request_code DESC
        LIMIT 1
    ");
    $stmt->execute([$prefix . '%']);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Lấy 3 chữ số cuối từ mã yêu cầu cuối cùng
        $lastCode = $result['request_code'];
        $lastSequence = intval(substr($lastCode, -3));
        $nextSequence = $lastSequence + 1;
    } else {
        // Nếu chưa có mã nào trong tháng này
        $nextSequence = 1;
    }
    
    $requestCode = $prefix . str_pad($nextSequence, 3, '0', STR_PAD_LEFT);
    
    echo json_encode([
        'success' => true,
        'sequence' => $nextSequence,
        'request_code' => $requestCode
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?> 
