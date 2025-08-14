<?php
/**
 * IT CRM - Session Manager
 * File: includes/session.php
 * Mục đích: Quản lý session, xác thực người dùng
 * Tác giả: IT Support Team
 */

// Cấu hình session trước khi bắt đầu (chỉ khi chưa có output)
if (!headers_sent()) {
    ini_set('session.cookie_lifetime', 0); // Session cookie sẽ tồn tại cho đến khi browser đóng
    ini_set('session.gc_maxlifetime', 3600); // 1 giờ
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_cookies', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_path', '/'); // Đảm bảo cookie có thể truy cập từ tất cả paths
}

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
    $_SESSION[SESSION_ROLE] = trim($user_data['role']); // Trim để loại bỏ khoảng trắng
    $_SESSION[SESSION_LOGIN_TIME] = time();
    $_SESSION[SESSION_LAST_ACTIVITY] = time();
    
    // Regenerate session ID để bảo mật
    session_regenerate_id(true);
}

/**
 * Lấy thông tin user hiện tại từ session và database
 * @return array|null Thông tin user hoặc null nếu chưa đăng nhập
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    // Thông tin cơ bản từ session
    $user = [
        'id' => $_SESSION[SESSION_USER_ID],
        'username' => $_SESSION[SESSION_USERNAME],
        'fullname' => $_SESSION[SESSION_FULLNAME],
        'role' => $_SESSION[SESSION_ROLE],
        'login_time' => $_SESSION[SESSION_LOGIN_TIME],
        'last_activity' => $_SESSION[SESSION_LAST_ACTIVITY]
    ];
    
    // Lấy thông tin chi tiết từ database
    try {
        global $pdo;
        if (isset($pdo) && $pdo) {
            $stmt = $pdo->prepare("SELECT position, department, office, staff_code FROM staffs WHERE id = ?");
            $stmt->execute([$_SESSION[SESSION_USER_ID]]);
            $staff_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($staff_info) {
                $user = array_merge($user, $staff_info);
            }
        }
    } catch (Exception $e) {
        // Log error nhưng không làm crash
        error_log("Error getting staff info: " . $e->getMessage());
    }
    
    return $user;
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
 * Kiểm tra xem user có phải IT staff không (it, it_leader)
 * @return bool True nếu là IT staff, False nếu không
 */
function isITStaff() {
    return hasRole(['it', 'it_leader']);
}

/**
 * Kiểm tra xem user có quyền truy cập workspace không
 * @return bool True nếu có quyền truy cập workspace, False nếu không
 */
function canAccessWorkspace() {
    return hasRole(['admin', 'it', 'it_leader']);
}

/**
 * Kiểm tra xem user có quyền xem tất cả internal cases không
 * @return bool True nếu có quyền xem tất cả, False nếu chỉ xem của mình
 */
function canViewAllInternalCases() {
    return hasRole(['admin', 'it', 'it_leader']);
}

/**
 * Kiểm tra xem user có quyền chỉnh sửa internal case không
 * @return bool True nếu có quyền chỉnh sửa, False nếu không
 */
function canEditInternalCase() {
    return hasRole(['admin', 'it', 'it_leader']);
}

/**
 * Kiểm tra xem user có quyền xóa internal case không
 * @return bool True nếu có quyền xóa, False nếu không
 */
function canDeleteInternalCase() {
    return hasRole('admin');
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
 * @param int $timeout Thời gian timeout (giây), mặc định 1 giờ
 * @return bool True nếu hết hạn, False nếu còn hiệu lực
 */
function isSessionExpired($timeout = 3600) {
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
    $redirect_url = 'index.php';
    
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
    header('Location: dashboard.php');
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
        // Log lỗi quyền truy cập
        $user = getCurrentUser();
        error_log("Access denied - User: " . ($user['username'] ?? 'unknown') . ", Role: " . ($user['role'] ?? 'none') . ", Required: " . (is_array($required_roles) ? json_encode($required_roles) : $required_roles));
        
        http_response_code(403);
        echo '<!DOCTYPE html>
<html>
<head>
    <title>403 Forbidden</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .error { color: #d32f2f; font-size: 24px; margin-bottom: 20px; }
        .message { color: #666; margin-bottom: 30px; }
        .back-link { color: #1976d2; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="error">403 Forbidden</div>
    <div class="message">Bạn không có quyền truy cập trang này.</div>
    <a href="dashboard.php" class="back-link">← Quay về Dashboard</a>
</body>
</html>';
        exit();
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

/**
 * Log hoạt động của user
 * @param string $action Hành động thực hiện
 * @param string $details Chi tiết hành động
 */
function logUserActivity($action, $details = '') {
    try {
        global $pdo;
        
        if (!isset($pdo) || !$pdo) {
            return; // Không có kết nối database
        }
        
        $user_id = getCurrentUserId();
        $username = getCurrentUsername();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $sql = "INSERT INTO user_activity_logs (user_id, activity, details, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, $action, $details, $ip_address, $user_agent]);
        
    } catch (Exception $e) {
        // Log lỗi nhưng không làm crash ứng dụng
        error_log("Failed to log user activity: " . $e->getMessage());
    }
}

// Auto-update last activity cho các request
if (isLoggedIn()) {
    updateLastActivity();
}

?> 