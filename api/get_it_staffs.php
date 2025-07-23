<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

try {
    $pdo = getConnection();
    
    // Lấy danh sách staff thuộc IT Dept
    $sql = "SELECT 
                id,
                employee_code,
                fullname,
                email,
                phone,
                position,
                department
            FROM staffs
            WHERE department = 'IT Dept.'
            ORDER BY fullname ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $staffs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $staffs
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_it_staffs.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi tải danh sách staff IT: ' . $e->getMessage()
    ]);
}
?> 