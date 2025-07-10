<?php
/**
 * IT CRM - Logout Handler
 * File: auth/logout.php
 * Mục đích: Xử lý đăng xuất người dùng
 * Tác giả: IT Support Team
 */

// Include các file cần thiết
require_once '../includes/session.php';

// Thiết lập header cho JSON response
header('Content-Type: application/json; charset=utf-8');

try {
    // Ghi log hoạt động trước khi đăng xuất
    if (isLoggedIn()) {
        logUserActivity('logout', 'Đăng xuất từ IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }
    
    // Xóa remember me cookies nếu có
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
    
    if (isset($_COOKIE['saved_username'])) {
        setcookie('saved_username', '', time() - 3600, '/', '', false, false);
    }
    
    // Đăng xuất (xóa session)
    logout();
    
    // Trả về response thành công
    echo json_encode([
        'success' => true,
        'message' => 'Đăng xuất thành công!',
        'redirect' => 'index.html'
    ]);
    
} catch (Exception $e) {
    // Ghi log lỗi
    error_log("Error in logout: " . $e->getMessage());
    
    // Vẫn cố gắng đăng xuất
    logout();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi đăng xuất.',
        'redirect' => 'index.html'
    ]);
}

session_start();
session_destroy();
header("Location: ../index.html");
exit();

?> 