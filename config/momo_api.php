<?php
/**
 * Momo API Configuration
 * File: config/momo_api.php
 * Mục đích: Cấu hình cho tích hợp thanh toán Momo
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('INCLUDED')) {
    define('INCLUDED', true);
}

// ===== MOMO API CONFIGURATION ===== //
return [
    // API endpoints
    'qr_api_url' => 'https://momosv3.apimienphi.com/api/QRCode',
    'transaction_api_url' => 'https://momosv3.apimienphi.com/api/TransactionHistory',
    
    // Cấu hình thanh toán
    'phone_number' => '0123456789', // Số điện thoại Momo nhận tiền
    'amount' => 10000, // Số tiền cố định (VNĐ)
    'description' => 'Phí khôi phục mật khẩu', // Mô tả giao dịch
    
    // Cấu hình timeout và retry
    'timeout' => 30,
    'max_retries' => 3,
    'retry_delay' => 2, // seconds
    
    // Cấu hình cache
    'cache_duration' => 300, // 5 phút
    
    // Cấu hình bảo mật
    'order_prefix' => 'RESET',
    'session_prefix' => 'momo_reset_',
    
    // User agent cho API calls
    'user_agent' => 'IT-CRM-Momo-Client/1.0'
];

// ===== HƯỚNG DẪN CẤU HÌNH ===== //
/*
1. Thay đổi 'phone_number' thành số điện thoại Momo thật của bạn
2. Có thể điều chỉnh 'amount' theo nhu cầu
3. API miễn phí có thể có giới hạn request, cần monitor
4. Nên test kỹ trước khi deploy production
*/
?> 