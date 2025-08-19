<?php
/**
 * Debug API: Kiểm tra đơn nghỉ phép và department
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/db.php';

try {
    // Kiểm tra tất cả đơn nghỉ phép
    $stmt = $pdo->prepare("SELECT 
        lr.id, lr.request_code, lr.status, lr.created_at,
        s.fullname as requester_name, s.department as requester_department, s.role as requester_role
        FROM leave_requests lr
        LEFT JOIN staffs s ON lr.requester_id = s.id
        ORDER BY lr.created_at DESC");
    $stmt->execute();
    $all_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Kiểm tra đơn của phòng IT
    $stmt2 = $pdo->prepare("SELECT 
        lr.id, lr.request_code, lr.status, lr.created_at,
        s.fullname as requester_name, s.department as requester_department, s.role as requester_role
        FROM leave_requests lr
        LEFT JOIN staffs s ON lr.requester_id = s.id
        WHERE s.department LIKE '%IT%' OR s.department LIKE '%it%'
        ORDER BY lr.created_at DESC");
    $stmt2->execute();
    $it_requests = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    // Kiểm tra tất cả departments
    $stmt3 = $pdo->prepare("SELECT DISTINCT department FROM staffs WHERE department IS NOT NULL ORDER BY department");
    $stmt3->execute();
    $all_departments = $stmt3->fetchAll(PDO::FETCH_COLUMN);
    
    // Kiểm tra users có role it
    $stmt4 = $pdo->prepare("SELECT id, fullname, department, role FROM staffs WHERE role = 'it'");
    $stmt4->execute();
    $it_users = $stmt4->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'all_requests' => $all_requests,
            'it_requests' => $it_requests,
            'all_departments' => $all_departments,
            'it_users' => $it_users
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
?> 