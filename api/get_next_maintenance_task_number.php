<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');

$year = date('y'); // 2 số cuối năm
$month = date('m'); // 2 số của tháng
$prefix = "TBT{$year}{$month}";

try {
    $pdo = getConnection();
    
    // Tìm task number lớn nhất trong tháng hiện tại
    $stmt = $pdo->prepare("SELECT task_number FROM maintenance_tasks 
            WHERE task_number LIKE ? 
            AND MONTH(created_at) = ?
            AND YEAR(created_at) = ?
            ORDER BY task_number DESC 
            LIMIT 1");
    $stmt->execute([$prefix . '%', intval($month), 2000 + intval($year)]);
    $result = $stmt->fetch();
    
    if ($result) {
        // Lấy 3 số cuối của task number
        $lastNumber = intval(substr($result['task_number'], -3));
        $sequence = $lastNumber + 1;
    } else {
        // Task đầu tiên trong tháng
        $sequence = 1;
    }
    
    $task_number = $prefix . str_pad($sequence, 3, '0', STR_PAD_LEFT);
    echo json_encode([
        'success' => true,
        'task_number' => $task_number
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
