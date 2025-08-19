<?php
/**
 * Test API: Tạo user test có role it
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

try {
    // Kiểm tra xem đã có user it chưa
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM staffs WHERE role = 'it'");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Tạo user test it
        $stmt = $pdo->prepare("INSERT INTO staffs (staff_code, fullname, position, department, office, username, password, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'NV.IT.002',
            'IT User Test',
            'IT Engineer',
            'IT Dept.',
            'Hà Nội',
            'ituser',
            password_hash('123456', PASSWORD_DEFAULT),
            'it',
            'active'
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã tạo user IT test thành công',
            'data' => [
                'username' => 'ituser',
                'password' => '123456',
                'role' => 'it',
                'department' => 'IT Dept.'
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Đã có ' . $count . ' user(s) có role it',
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
