<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
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
    $type = $_GET['type'] ?? 'deployment';
    
    if ($type === 'deployment') {
        // Lấy số case triển khai cao nhất trong tháng hiện tại
        $now = new DateTime();
        $year = $now->format('y');
        $month = $now->format('m');
        $prefix = "CTK.{$year}{$month}";
        
        $stmt = $pdo->prepare("
            SELECT MAX(CAST(SUBSTRING(case_number, 10) AS UNSIGNED)) as max_sequence
            FROM deployment_cases 
            WHERE case_number LIKE ?
        ");
        $stmt->execute([$prefix . '%']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $nextSequence = ($result['max_sequence'] ?? 0) + 1;
        
        echo json_encode([
            'success' => true,
            'sequence' => $nextSequence,
            'case_number' => $prefix . str_pad($nextSequence, 3, '0', STR_PAD_LEFT)
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid type']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?> 