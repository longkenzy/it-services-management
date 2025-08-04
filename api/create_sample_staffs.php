<?php
/**
 * Script tạo staff mẫu
 */

header('Content-Type: application/json');

require_once '../config/db.php';

try {
    // Kiểm tra xem đã có staff nào chưa
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM staffs");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($count['count'] > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Đã có ' . $count['count'] . ' nhân viên trong database'
        ]);
        exit;
    }
    
    // Tạo staff mẫu
    $sample_staffs = [
        [
            'staff_code' => 'NV001',
            'fullname' => 'Nguyễn Văn A',
            'email' => 'nguyenvana@example.com',
            'phone' => '0123456789',
            'position' => 'Nhân viên',
            'department' => 'IT',
            'office' => 'Hà Nội',
            'status' => 'Đang làm việc'
        ],
        [
            'staff_code' => 'NV002',
            'fullname' => 'Trần Thị B',
            'email' => 'tranthib@example.com',
            'phone' => '0987654321',
            'position' => 'Trưởng nhóm',
            'department' => 'HR',
            'office' => 'TP.HCM',
            'status' => 'Đang làm việc'
        ],
        [
            'staff_code' => 'NV003',
            'fullname' => 'Lê Văn C',
            'email' => 'levanc@example.com',
            'phone' => '0369852147',
            'position' => 'Quản lý',
            'department' => 'Sales',
            'office' => 'Đà Nẵng',
            'status' => 'Đang làm việc'
        ]
    ];
    
    $insert_sql = "INSERT INTO staffs (
        staff_code, fullname, email, phone, position, department, office, status, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $pdo->prepare($insert_sql);
    
    foreach ($sample_staffs as $staff) {
        $stmt->execute([
            $staff['staff_code'],
            $staff['fullname'],
            $staff['email'],
            $staff['phone'],
            $staff['position'],
            $staff['department'],
            $staff['office'],
            $staff['status']
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã tạo ' . count($sample_staffs) . ' nhân viên mẫu'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?> 