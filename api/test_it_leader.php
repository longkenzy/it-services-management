<?php
/**
 * Test API: Kiểm tra user có role it_leader
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

try {
    // Kiểm tra user có role it_leader
    $stmt = $pdo->prepare("SELECT id, fullname, department, role FROM staffs WHERE role = 'it_leader'");
    $stmt->execute();
    $it_leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kiểm tra user có role it
    $stmt2 = $pdo->prepare("SELECT id, fullname, department, role FROM staffs WHERE role = 'it'");
    $stmt2->execute();
    $it_users = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    // Kiểm tra tất cả roles
    $stmt3 = $pdo->prepare("SELECT DISTINCT role FROM staffs ORDER BY role");
    $stmt3->execute();
    $all_roles = $stmt3->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'it_leaders' => $it_leaders,
            'it_users' => $it_users,
            'all_roles' => $all_roles
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
?>
