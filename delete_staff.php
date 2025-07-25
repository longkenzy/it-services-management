<?php
/**
 * IT CRM - Delete Staff
 * File: delete_staff.php
 * Mục đích: Xóa nhân sự khỏi hệ thống
 */

// Include session management
require_once 'includes/session.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Bạn chưa đăng nhập'
    ]);
    exit();
}

// Kiểm tra quyền admin (tùy chọn)
$current_user = getCurrentUser();
if ($current_user['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Bạn không có quyền thực hiện thao tác này'
    ]);
    exit();
}

// Include database connection
require_once 'config/db.php';

// Kiểm tra method
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ'
    ]);
    exit();
}

// Lấy ID nhân sự cần xóa
$staff_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staff_id = isset($_POST['id']) ? intval($_POST['id']) : null;
} else {
    $staff_id = isset($_GET['id']) ? intval($_GET['id']) : null;
}

// Kiểm tra ID
if (!$staff_id || $staff_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'ID nhân sự không hợp lệ'
    ]);
    exit();
}

try {
    // Bắt đầu transaction
    $pdo->beginTransaction();
    
    // 1. Lấy thông tin nhân sự trước khi xóa
    $staff_query = "SELECT id, username, avatar, fullname FROM staffs WHERE id = ?";
    $staff_stmt = $pdo->prepare($staff_query);
    $staff_stmt->execute([$staff_id]);
    $staff = $staff_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$staff) {
        $pdo->rollBack();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy nhân sự với ID này'
        ]);
        exit();
    }
    
    $username = $staff['username'];
    $avatar = $staff['avatar'];
    $fullname = $staff['fullname'];
    
    // 2. Xóa ảnh đại diện nếu có
    if ($avatar && !empty($avatar)) {
        $avatar_path = 'assets/uploads/avatars/' . $avatar;
        if (file_exists($avatar_path)) {
            if (!unlink($avatar_path)) {
                // Không cần log lỗi xóa file avatar
            }
        }
    }
    
    // 3. Xóa dữ liệu nhân sự từ bảng staffs
    $delete_staff_query = "DELETE FROM staffs WHERE id = ?";
    $delete_staff_stmt = $pdo->prepare($delete_staff_query);
    $delete_staff_result = $delete_staff_stmt->execute([$staff_id]);
    
    if (!$delete_staff_result) {
        $pdo->rollBack();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Không thể xóa dữ liệu nhân sự'
        ]);
        exit();
    }
    
    // 4. Xóa tài khoản đăng nhập từ bảng staffs (nếu có)
    if ($username) {
        $delete_user_query = "DELETE FROM staffs WHERE username = ?";
        $delete_user_stmt = $pdo->prepare($delete_user_query);
        $delete_user_stmt->execute([$username]);
        
        // Không cần kiểm tra kết quả vì có thể user không tồn tại
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Log hoạt động
    
    // Trả về kết quả thành công
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => "Đã xóa thành công nhân sự: {$fullname}",
        'data' => [
            'staff_id' => $staff_id,
            'staff_name' => $fullname
        ]
    ]);
    
} catch (PDOException $e) {
    // Rollback nếu có lỗi
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log lỗi
    error_log("Lỗi xóa nhân sự: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi xóa nhân sự. Vui lòng thử lại!'
    ]);
    
} catch (Exception $e) {
    // Rollback nếu có lỗi
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log lỗi
    error_log("Lỗi không xác định khi xóa nhân sự: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra. Vui lòng thử lại!'
    ]);
}
?> 