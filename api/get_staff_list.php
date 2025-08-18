<?php
/**
 * IT CRM - Get Staff List API
 * File: api/get_staff_list.php
 * Mục đích: API lấy danh sách nhân viên
 */

// Bảo vệ file khỏi truy cập trực tiếp (chỉ cho phép từ cùng domain)
if (!isset($_SERVER['HTTP_REFERER']) || !str_contains($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST'])) {
    // Cho phép truy cập từ AJAX requests
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
        http_response_code(403);
        exit('Access denied.');
    }
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include các file cần thiết
require_once '../includes/session.php';
require_once '../config/db.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $all = isset($_GET['all']) && $_GET['all'] == '1';
    $department = $_GET['department'] ?? '';
    
    // Lấy danh sách nhân sự
    if ($all) {
        $stmt = $pdo->prepare("SELECT id, fullname, position FROM staffs WHERE status = 'active' ORDER BY fullname");
        $stmt->execute();
    } elseif ($department) {
        $stmt = $pdo->prepare("SELECT id, fullname, position FROM staffs WHERE status = 'active' AND department = ? ORDER BY fullname");
        $stmt->execute([$department]);
    } else {
        $stmt = $pdo->prepare("SELECT id, fullname, position FROM staffs WHERE status = 'active' AND department = 'IT Dept.' ORDER BY fullname");
        $stmt->execute();
    }
    
    $staff_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $staff_list
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 