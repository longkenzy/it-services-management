<?php
/**
 * IT CRM - Login Handler
 * File: auth/login.php
 * Mục đích: Xử lý đăng nhập người dùng chỉ dùng bảng staffs
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php';
require_once '../includes/session.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được phép.'
    ]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$remember_me = isset($input['remember']) && $input['remember'];

$errors = [];
if (empty($username)) {
    $errors[] = 'Vui lòng nhập tên đăng nhập hoặc email.';
} elseif (strlen($username) < 3) {
    $errors[] = 'Tên đăng nhập hoặc email phải có ít nhất 3 ký tự.';
} elseif (strlen($username) > 100) {
    $errors[] = 'Tên đăng nhập hoặc email không được quá 100 ký tự.';
}
if (empty($password)) {
    $errors[] = 'Vui lòng nhập mật khẩu.';
} elseif (strlen($password) < 6) {
    $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
}
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
    // Đăng nhập chỉ dùng bảng staffs
    $sql = "SELECT id, username, password, fullname, role FROM staffs WHERE username = :username LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['username' => $username]);
    $staff = $stmt->fetch();

    if (!$staff) {
        error_log("Login failed: Staff not found - " . $username . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Tên đăng nhập hoặc mật khẩu không đúng.'
        ]);
        exit();
    }

    if (!password_verify($password, $staff['password'])) {
        error_log("Login failed: Wrong password for staff " . $username . " from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Tên đăng nhập hoặc mật khẩu không đúng.'
        ]);
        exit();
    }

    // Đăng nhập thành công, lưu vào session
    // Trim role để loại bỏ khoảng trắng
    $staff['role'] = trim($staff['role']);
    setUserSession($staff);

    if ($remember_me) {
        $remember_token = bin2hex(random_bytes(32));
        setcookie('remember_token', $remember_token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
        setcookie('saved_username', $username, time() + (30 * 24 * 60 * 60), '/', '', false, false);
    }

    // Ghi log thành công
    // logUserActivity('login', 'Đăng nhập thành công từ IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

    echo json_encode([
        'success' => true,
        'message' => 'Đăng nhập thành công!',
        'user' => [
            'id' => $staff['id'],
            'username' => $staff['username'],
            'fullname' => $staff['fullname'],
            'role' => $staff['role']
        ],
        'redirect' => 'dashboard.php'
    ]);

} catch (PDOException $e) {
    error_log("Database error in login: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống. Vui lòng thử lại sau.'
    ]);
} catch (Exception $e) {
    error_log("General error in login: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau.'
    ]);
} 