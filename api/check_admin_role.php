<?php
/**
 * API kiểm tra quyền admin
 */

header('Content-Type: application/json');
require_once '../includes/session.php';

if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Chưa đăng nhập',
        'is_admin' => false
    ]);
    exit;
}

try {
    $is_admin = hasRole('admin');
    
    echo json_encode([
        'success' => true,
        'is_admin' => $is_admin,
        'user_role' => $_SESSION[SESSION_ROLE] ?? 'user'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi kiểm tra quyền',
        'is_admin' => false
    ]);
}
?> 