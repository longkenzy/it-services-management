<?php
/**
 * Test API: Tạo user test có role it_leader
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

try {
    // Kiểm tra xem đã có user it_leader chưa
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM staffs WHERE role = 'it_leader'");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Tạo user test it_leader
        $stmt = $pdo->prepare("INSERT INTO staffs (staff_code, fullname, position, department, office, username, password, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'NV.IT.001',
            'IT Leader Test',
            'IT Leader',
            'IT Dept.',
            'Hà Nội',
            'itleader',
            password_hash('123456', PASSWORD_DEFAULT),
            'it_leader',
            'active'
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã tạo user IT Leader test thành công',
            'data' => [
                'username' => 'itleader',
                'password' => '123456',
                'role' => 'it_leader'
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Đã có ' . $count . ' user(s) có role it_leader',
            'data' => [
                'count' => $count
            ]
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
?>
