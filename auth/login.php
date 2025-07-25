<?php
/**
 * IT CRM - Login Handler
 * File: auth/login.php
 * Mục đích: Xử lý đăng nhập người dùng
 * Tác giả: IT Support Team
 */

// Bật hiển thị lỗi để debug (tắt trong production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include các file cần thiết
require_once '../config/db.php';
require_once '../includes/session.php';

// Thiết lập header cho JSON response
header('Content-Type: application/json; charset=utf-8');

// Chỉ cho phép POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được phép.'
    ]);
    exit();
}

// Lấy dữ liệu từ POST request
$input = json_decode(file_get_contents('php://input'), true);

// Nếu không có dữ liệu JSON, lấy từ $_POST
if (!$input) {
    $input = $_POST;
}

$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$remember_me = isset($input['remember']) && $input['remember'];

// ===== VALIDATION ===== //

$errors = [];

// Validate username/email
if (empty($username)) {
    $errors[] = 'Vui lòng nhập tên đăng nhập hoặc email.';
} elseif (strlen($username) < 3) {
    $errors[] = 'Tên đăng nhập hoặc email phải có ít nhất 3 ký tự.';
} elseif (strlen($username) > 100) {
    $errors[] = 'Tên đăng nhập hoặc email không được quá 100 ký tự.';
}

// Validate password
if (empty($password)) {
    $errors[] = 'Vui lòng nhập mật khẩu.';
} elseif (strlen($password) < 6) {
    $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
}

// Nếu có lỗi validation, trả về lỗi
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => implode(' ', $errors),
        'errors' => $errors
    ]);
    exit();
}

try {
    // ===== KIỂM TRA THÔNG TIN ĐĂNG NHẬP ===== //
    
    // Tìm user trong database (hỗ trợ cả username và email)
    // Nếu input có chứa @, coi như email và tìm trong cột username
    // Nếu không có @, tìm trực tiếp trong cột username
    $sql = "SELECT id, username, password, fullname, role, created_at FROM users WHERE username = :username LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();
    
    // Kiểm tra user có tồn tại không
    if (!$user) {
        // Ghi log thất bại
        error_log("Login failed: User not found - " . $username . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Tên đăng nhập/email hoặc mật khẩu không đúng.'
        ]);
        exit();
    }
    
    // Kiểm tra mật khẩu
    if (!password_verify($password, $user['password'])) {
        // Ghi log thất bại
        error_log("Login failed: Wrong password for user " . $username . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Tên đăng nhập/email hoặc mật khẩu không đúng.'
        ]);
        exit();
    }
    
    // ===== ĐĂNG NHẬP THÀNH CÔNG ===== //
    
    // Lưu thông tin vào session
    setUserSession($user);
    
    // Xử lý Remember Me
    if ($remember_me) {
        // Tạo remember token (có thể lưu vào database sau)
        $remember_token = bin2hex(random_bytes(32));
        setcookie('remember_token', $remember_token, time() + (30 * 24 * 60 * 60), '/', '', false, true); // 30 ngày
        
        // Lưu username để auto-fill
        setcookie('saved_username', $username, time() + (30 * 24 * 60 * 60), '/', '', false, false);
    }
    
    // Ghi log thành công
    // logUserActivity('login', 'Đăng nhập thành công từ IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    
    // Cập nhật thời gian đăng nhập cuối vào database (optional)
    try {
        $update_sql = "UPDATE users SET updated_at = NOW() WHERE id = :id";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->execute(['id' => $user['id']]);
    } catch (Exception $e) {
        // Không quan trọng nếu update thất bại
        error_log("Failed to update last login time: " . $e->getMessage());
    }
    
    // Trả về thông tin thành công
    echo json_encode([
        'success' => true,
        'message' => 'Đăng nhập thành công!',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'fullname' => $user['fullname'],
            'role' => $user['role']
        ],
        'redirect' => 'dashboard.php'
    ]);
    
} catch (PDOException $e) {
    // Lỗi database
    error_log("Database error in login: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống. Vui lòng thử lại sau.'
    ]);
    
} catch (Exception $e) {
    // Lỗi khác
    error_log("General error in login: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau.'
    ]);
}

?> 