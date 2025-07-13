<?php
/**
 * IT CRM - Session Manager
 * File: includes/session.php
 * Mục đích: Quản lý session, xác thực người dùng
 * Tác giả: IT Support Team
 */

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Access denied.');
}

// Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Định nghĩa các constant
define('SESSION_USER_ID', 'user_id');
define('SESSION_USERNAME', 'username');
define('SESSION_FULLNAME', 'fullname');
define('SESSION_ROLE', 'role');
define('SESSION_LOGIN_TIME', 'login_time');
define('SESSION_LAST_ACTIVITY', 'last_activity');

/**
 * Kiểm tra xem user đã đăng nhập chưa
 * @return bool True nếu đã đăng nhập, False nếu chưa
 */
function isLoggedIn() {
    return isset($_SESSION[SESSION_USER_ID]) && !empty($_SESSION[SESSION_USER_ID]);
}

/**
 * Lưu thông tin user vào session khi đăng nhập thành công
 * @param array $user_data Thông tin user từ database
 */
function setUserSession($user_data) {
    $_SESSION[SESSION_USER_ID] = $user_data['id'];
    $_SESSION[SESSION_USERNAME] = $user_data['username'];
    $_SESSION[SESSION_FULLNAME] = $user_data['fullname'];
    $_SESSION[SESSION_ROLE] = $user_data['role'];
    $_SESSION[SESSION_LOGIN_TIME] = time();
    $_SESSION[SESSION_LAST_ACTIVITY] = time();
    
    // Regenerate session ID để bảo mật
    session_regenerate_id(true);
}

/**
 * Lấy thông tin user hiện tại từ session
 * @return array|null Thông tin user hoặc null nếu chưa đăng nhập
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION[SESSION_USER_ID],
        'username' => $_SESSION[SESSION_USERNAME],
        'fullname' => $_SESSION[SESSION_FULLNAME],
        'role' => $_SESSION[SESSION_ROLE],
        'login_time' => $_SESSION[SESSION_LOGIN_TIME],
        'last_activity' => $_SESSION[SESSION_LAST_ACTIVITY]
    ];
}

/**
 * Lấy ID của user hiện tại
 * @return int|null ID user hoặc null nếu chưa đăng nhập
 */
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION[SESSION_USER_ID] : null;
}

/**
 * Lấy username của user hiện tại
 * @return string|null Username hoặc null nếu chưa đăng nhập
 */
function getCurrentUsername() {
    return isLoggedIn() ? $_SESSION[SESSION_USERNAME] : null;
}

/**
 * Lấy tên đầy đủ của user hiện tại
 * @return string|null Fullname hoặc null nếu chưa đăng nhập
 */
function getCurrentUserFullname() {
    return isLoggedIn() ? $_SESSION[SESSION_FULLNAME] : null;
}

/**
 * Lấy role của user hiện tại
 * @return string|null Role hoặc null nếu chưa đăng nhập
 */
function getCurrentUserRole() {
    return isLoggedIn() ? $_SESSION[SESSION_ROLE] : null;
}

/**
 * Kiểm tra xem user có role cụ thể không
 * @param string|array $roles Role cần kiểm tra (có thể là string hoặc array)
 * @return bool True nếu user có role đó, False nếu không
 */
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = getCurrentUserRole();
    
    if (is_array($roles)) {
        return in_array($userRole, $roles);
    }
    
    return $userRole === $roles;
}

/**
 * Kiểm tra xem user có phải admin không
 * @return bool True nếu là admin, False nếu không
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Kiểm tra xem user có phải leader không
 * @return bool True nếu là leader hoặc admin, False nếu không
 */
function isLeader() {
    return hasRole(['admin', 'leader']);
}

/**
 * Cập nhật thời gian hoạt động cuối cùng
 */
function updateLastActivity() {
    if (isLoggedIn()) {
        $_SESSION[SESSION_LAST_ACTIVITY] = time();
    }
}

/**
 * Kiểm tra session có hết hạn không
 * @param int $timeout Thời gian timeout (giây), mặc định 30 phút
 * @return bool True nếu hết hạn, False nếu còn hiệu lực
 */
function isSessionExpired($timeout = 1800) {
    if (!isLoggedIn()) {
        return true;
    }
    
    $lastActivity = $_SESSION[SESSION_LAST_ACTIVITY] ?? 0;
    return (time() - $lastActivity) > $timeout;
}

/**
 * Đăng xuất user - xóa tất cả session
 */
function logout() {
    // Xóa tất cả session variables
    $_SESSION = array();
    
    // Xóa session cookie nếu có
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Hủy session
    session_destroy();
}

/**
 * Chuyển hướng đến trang đăng nhập
 * @param string $message Thông báo lỗi (optional)
 */
function redirectToLogin($message = '') {
    $redirect_url = 'index.html';
    
    if (!empty($message)) {
        $redirect_url .= '?error=' . urlencode($message);
    }
    
    header('Location: ' . $redirect_url);
    exit();
}

/**
 * Chuyển hướng đến trang dashboard
 */
function redirectToDashboard() {
    header('Location: dashboard.html');
    exit();
}

/**
 * Bảo vệ trang - yêu cầu đăng nhập
 * @param string|array $required_roles Role cần thiết để truy cập (optional)
 */
function requireLogin($required_roles = null) {
    // Cập nhật thời gian hoạt động
    updateLastActivity();
    
    // Kiểm tra session hết hạn
    if (isSessionExpired()) {
        logout();
        redirectToLogin('Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại.');
    }
    
    // Kiểm tra đăng nhập
    if (!isLoggedIn()) {
        redirectToLogin('Vui lòng đăng nhập để tiếp tục.');
    }
    
    // Kiểm tra quyền truy cập nếu có yêu cầu
    if ($required_roles !== null && !hasRole($required_roles)) {
        header('HTTP/1.0 403 Forbidden');
        die('Bạn không có quyền truy cập trang này.');
    }
}

/**
 * Bảo vệ trang chỉ dành cho admin
 */
function requireAdmin() {
    requireLogin('admin');
}

/**
 * Bảo vệ trang chỉ dành cho leader trở lên
 */
function requireLeader() {
    requireLogin(['admin', 'leader']);
}

/**
 * Lấy thời gian đăng nhập dạng readable
 * @return string Thời gian đăng nhập
 */
function getLoginTimeFormatted() {
    if (!isLoggedIn()) {
        return '';
    }
    
    $loginTime = $_SESSION[SESSION_LOGIN_TIME] ?? 0;
    return date('d/m/Y H:i:s', $loginTime);
}

/**
 * Lấy thời gian hoạt động cuối dạng readable
 * @return string Thời gian hoạt động cuối
 */
function getLastActivityFormatted() {
    if (!isLoggedIn()) {
        return '';
    }
    
    $lastActivity = $_SESSION[SESSION_LAST_ACTIVITY] ?? 0;
    return date('d/m/Y H:i:s', $lastActivity);
}

/**
 * Tạo CSRF token để bảo vệ form
 * @return string CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Kiểm tra CSRF token
 * @param string $token Token cần kiểm tra
 * @return bool True nếu token hợp lệ, False nếu không
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Ghi log hoạt động user
 * @param string $action Hành động
 * @param string $details Chi tiết (optional)
 */
function logUserActivity($action, $details = '') {
    if (!isLoggedIn()) {
        return;
    }
    
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => getCurrentUserId(),
        'username' => getCurrentUsername(),
        'action' => $action,
        'details' => $details,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    // Ghi vào file log (có thể thay bằng database sau)
    $log_file = 'logs/user_activity.log';
    
    try {
        if (!file_exists('logs')) {
            mkdir('logs', 0755, true);
        }
        
        if (is_writable('logs') || is_writable($log_file)) {
            file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
        }
    } catch (Exception $e) {
        // Silently fail - không làm gián đoạn response chính
        error_log("Failed to write user activity log: " . $e->getMessage());
    }
}

/**
 * Hiển thị thông báo flash message
 * @param string $type Loại thông báo (success, error, warning, info)
 * @param string $message Nội dung thông báo
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Lấy và xóa flash messages
 * @return array Danh sách flash messages
 */
function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

// Auto-update last activity cho các request
if (isLoggedIn()) {
    updateLastActivity();
}

?> 