<?php
/**
 * IT CRM - Change Password
 * File: change_password.php
 * Mục đích: Xử lý đổi mật khẩu người dùng
 */

// Include session management
require_once 'includes/session.php';

// Set content type
header('Content-Type: application/json; charset=utf-8');

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Bạn chưa đăng nhập'
    ]);
    exit();
}

// Kiểm tra method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method không được hỗ trợ'
    ]);
    exit();
}

// Include database connection
require_once 'config/db.php';

// Lấy thông tin user hiện tại
$current_user = getCurrentUser();

try {
    // Lấy và validate dữ liệu
    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    // Validation
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng điền đầy đủ thông tin'
        ]);
        exit();
    }
    
    if (strlen($new_password) < 6) {
        echo json_encode([
            'success' => false,
            'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự'
        ]);
        exit();
    }
    
    if ($new_password !== $confirm_password) {
        echo json_encode([
            'success' => false,
            'message' => 'Mật khẩu mới và xác nhận mật khẩu không khớp'
        ]);
        exit();
    }
    
    if ($current_password === $new_password) {
        echo json_encode([
            'success' => false,
            'message' => 'Mật khẩu mới phải khác mật khẩu hiện tại'
        ]);
        exit();
    }
    
    // Bắt đầu transaction
    $pdo->beginTransaction();
    
    // ===== KIỂM TRA MẬT KHẨU HIỆN TẠI ===== //
    
    // Lấy mật khẩu hiện tại từ bảng staffs
    $check_sql = "SELECT password FROM staffs WHERE username = ? LIMIT 1";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$current_user['username']]);
    $user_data = $check_stmt->fetch();
    
    if (!$user_data) {
        throw new Exception('Không tìm thấy thông tin người dùng');
    }
    
    // Kiểm tra mật khẩu hiện tại
    if (!password_verify($current_password, $user_data['password'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Mật khẩu hiện tại không đúng'
        ]);
        exit();
    }
    
    // ===== CẬP NHẬT MẬT KHẨU MỚI ===== //
    
    // Mã hóa mật khẩu mới
    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Cập nhật mật khẩu trong bảng staffs
    $update_sql = "UPDATE staffs SET password = ?, updated_at = NOW() WHERE username = ?";
    $update_stmt = $pdo->prepare($update_sql);
    $result = $update_stmt->execute([$new_password_hash, $current_user['username']]);
    
    if (!$result) {
        throw new Exception('Không thể cập nhật mật khẩu');
    }
    
    // Ghi log hoạt động
    logUserActivity('change_password', 'Đổi mật khẩu thành công');
    
    // Commit transaction
    $pdo->commit();
    
    // Trả về kết quả thành công
    echo json_encode([
        'success' => true,
        'message' => 'Đổi mật khẩu thành công!'
    ]);
    
} catch (PDOException $e) {
    // Rollback transaction
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    // Log lỗi
    error_log("Change password database error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi cơ sở dữ liệu. Vui lòng thử lại sau.'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    // Log lỗi
    error_log("Change password error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Đóng kết nối
$pdo = null;
?> 