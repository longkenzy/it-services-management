<?php
/**
 * Script kiểm tra thông tin user hiện tại
 */

header('Content-Type: application/json');

require_once '../includes/session.php';
require_once '../config/db.php';

try {
    if (!isLoggedIn()) {
        echo json_encode([
            'success' => false,
            'message' => 'User chưa đăng nhập'
        ]);
        exit;
    }
    
    $current_user = getCurrentUser();
    
    // Kiểm tra thông tin từ session
    $session_info = [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'fullname' => $_SESSION['fullname'] ?? null,
        'role' => $_SESSION['role'] ?? null
    ];
    
    // Kiểm tra thông tin từ database
    $stmt = $pdo->prepare("SELECT id, fullname, position, department, office, staff_code FROM staffs WHERE id = ?");
    $stmt->execute([$current_user['id']]);
    $staff_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'session_info' => $session_info,
        'current_user' => $current_user,
        'staff_info_from_db' => $staff_info,
        'has_position' => !empty($current_user['position']),
        'has_department' => !empty($current_user['department']),
        'has_office' => !empty($current_user['office'])
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?> 