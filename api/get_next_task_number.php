<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/db.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');

$year = date('y'); // 2 số cuối năm
$month = date('m'); // 2 số của tháng
$prefix = "TTK{$year}{$month}";

try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(task_number, 7, 3) AS UNSIGNED)) as max_seq
        FROM deployment_tasks
        WHERE task_number LIKE ?
          AND MONTH(created_at) = ?
          AND YEAR(created_at) = ?");
    $stmt->execute([$prefix . '%', intval($month), 2000 + intval($year)]);
    $result = $stmt->fetch();
    $max_seq = $result['max_seq'] ?? null;
    $sequence = ($max_seq === null) ? 1 : ($max_seq + 1);
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