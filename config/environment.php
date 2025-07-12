<?php
/**
 * IT CRM - Environment Configuration
 * File: config/environment.php
 * Mục đích: Cấu hình môi trường development/production
 * Tác giả: IT Support Team
 * Ngày tạo: 2024-01-01
 */

// Ngăn chặn truy cập trực tiếp
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Access denied.');
}

// Cấu hình môi trường
// Thay đổi thành 'production' khi deploy lên server thực
define('ENVIRONMENT', 'development'); // 'development' hoặc 'production'

// Cấu hình debug dựa trên môi trường
if (ENVIRONMENT === 'development') {
    // Development mode - hiển thị lỗi
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    define('DEBUG', true);
} else {
    // Production mode - ẩn lỗi
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
    define('DEBUG', false);
}

// Cấu hình logging
define('LOG_ERRORS', true);
define('LOG_FILE', __DIR__ . '/../logs/error.log');

// Cấu hình bảo mật
define('SESSION_TIMEOUT', ENVIRONMENT === 'development' ? 3600 : 1800); // 1h dev, 30min prod
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 phút

// Cấu hình upload
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

?> 