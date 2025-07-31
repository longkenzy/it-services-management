<?php
/**
 * IT CRM - Logout Handler
 * File: auth/logout.php
 * Mục đích: Xử lý đăng xuất người dùng
 * Tác giả: IT Support Team
 */

// Include các file cần thiết
require_once '../includes/session.php';

try {
    // Ghi log hoạt động trước khi đăng xuất
    if (isLoggedIn()) {
        // logUserActivity('logout', 'Đăng xuất từ IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
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
    
    // Redirect về trang đăng nhập
    header('Location: ../index.php');
    exit();
    
} catch (Exception $e) {
    // Ghi log lỗi
    error_log("Error in logout: " . $e->getMessage());
    
    // Vẫn cố gắng đăng xuất
    logout();
    
    // Redirect về trang đăng nhập ngay cả khi có lỗi
    header('Location: ../index.php');
    exit();
}

?> 