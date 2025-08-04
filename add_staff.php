<?php
/**
 * IT CRM - Add Staff Handler
 * File: add_staff.php
 * Mục đích: Xử lý thêm nhân sự mới vào hệ thống
 */

require_once 'includes/session.php';
require_once 'config/db.php';

// Bảo vệ trang - yêu cầu đăng nhập và quyền admin/manager
requireLogin();
if (!isAdmin() && !isManager()) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện chức năng này']);
    exit;
}

header('Content-Type: application/json');

// Debug: Log request
error_log("Add staff request received: " . json_encode($_POST));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

try {
    // Validate các trường bắt buộc
    $required_fields = ['staff_code', 'fullname', 'username', 'password'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Trường {$field} không được để trống"]);
            exit;
        }
    }

    // Kiểm tra mã nhân viên đã tồn tại trong bảng staffs
    $check_staff_sql = "SELECT id FROM staffs WHERE staff_code = ?";
    $check_staff_stmt = $pdo->prepare($check_staff_sql);
    $check_staff_stmt->execute([$_POST['staff_code']]);
    
    if ($check_staff_stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Mã nhân viên đã tồn tại']);
        exit;
    }
    
    // Kiểm tra username đã tồn tại trong bảng staffs chưa
    $check_user_sql = "SELECT id FROM staffs WHERE username = ?";
    $check_user_stmt = $pdo->prepare($check_user_sql);
    $check_user_stmt->execute([$_POST['username']]);
    
    if ($check_user_stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Username đã tồn tại trong hệ thống nhân sự']);
        exit;
    }

    // Xử lý upload avatar
    $avatar_path = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        $file = $_FILES['avatar'];

        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Chỉ cho phép ảnh JPG, PNG, GIF, WEBP']);
            exit;
        }
        if ($file['size'] > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'Dung lượng ảnh tối đa 2MB']);
            exit;
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $targetDir = 'assets/uploads/avatars/';
        $filename = 'staff_' . $_POST['staff_code'] . '_' . time() . '.' . $ext;
        $targetFile = $targetDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
            echo json_encode(['success' => false, 'message' => 'Không thể lưu file ảnh']);
            exit;
        }
        $avatar_path = $targetFile;
    }

    // Tính thâm niên từ ngày vào làm
    $seniority = 0;
    if (!empty($_POST['start_date'])) {
        $start_date = new DateTime($_POST['start_date']);
        $current_date = new DateTime();
        $diff = $current_date->diff($start_date);
        $seniority = $diff->y; // Số năm
    }

    // Mã hóa password
    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Xử lý position_id để lấy tên chức vụ và phòng ban
    $position_name = $_POST['position'] ?? null; // fallback từ hidden field
    $department_name = $_POST['department'] ?? null; // fallback từ form field
    
    if (!empty($_POST['position_id'])) {
        // Lấy thông tin chức vụ và phòng ban từ bảng positions
        $position_sql = "SELECT p.name as position_name, d.name as department_name 
                        FROM positions p 
                        LEFT JOIN departments d ON p.department_id = d.id 
                        WHERE p.id = ?";
        $position_stmt = $pdo->prepare($position_sql);
        $position_stmt->execute([$_POST['position_id']]);
        $position_data = $position_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($position_data) {
            $position_name = $position_data['position_name'];
            $department_name = $position_data['department_name'];
        }
    }

    // Chuẩn bị dữ liệu để insert
    $insert_sql = "INSERT INTO staffs (
        staff_code, fullname, username, password, birth_date, gender, hometown, 
        religion, ethnicity, position, job_type, department, office, office_address, 
        start_date, seniority, phone_main, phone_alt, email_personal, email_work, 
        place_of_birth, address_perm, address_temp, avatar, role, resigned, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $insert_stmt = $pdo->prepare($insert_sql);
    
    $params = [
        $_POST['staff_code'],
        $_POST['fullname'],
        $_POST['username'],
        $hashed_password,
        !empty($_POST['birth_date']) ? $_POST['birth_date'] : null,
        $_POST['gender'] ?? null,
        $_POST['hometown'] ?? null,
        $_POST['religion'] ?? null,
        $_POST['ethnicity'] ?? null,
        $position_name,
        $_POST['job_type'] ?? null,
        $department_name,
        $_POST['office'] ?? null,
        $_POST['office_address'] ?? null,
        !empty($_POST['start_date']) ? $_POST['start_date'] : null,
        $seniority,
        $_POST['phone_main'] ?? null,
        $_POST['phone_alt'] ?? null,
        $_POST['email_personal'] ?? null,
        $_POST['email_work'] ?? null,
        $_POST['place_of_birth'] ?? null,
        $_POST['address_perm'] ?? null,
        $_POST['address_temp'] ?? null,
        $avatar_path,
        $_POST['role'] ?? 'user',
        isset($_POST['resigned']) ? 1 : 0
    ];
    
    // Debug: Log parameters
    error_log("Add staff parameters: " . json_encode($params));
    
    $result = $insert_stmt->execute($params);

    if ($result) {
        $staff_id = $pdo->lastInsertId();
        
        // Log hoạt động (nếu hàm tồn tại)
        if (function_exists('logUserActivity')) {
            logUserActivity("Thêm nhân sự mới", "{$_POST['fullname']} ({$_POST['staff_code']})");
        }
        
        $response = [
            'success' => true, 
            'message' => 'Thêm nhân sự thành công!',
            'staff_id' => $staff_id,
            'staff_code' => $_POST['staff_code'],
            'fullname' => $_POST['fullname'],
            'username' => $_POST['username']
        ];
        
        // Debug: Log success response
        error_log("Add staff success response: " . json_encode($response));
        
        echo json_encode($response);
    } else {
        // Kiểm tra lỗi PDO nếu có
        $errorInfo = $insert_stmt->errorInfo();
        $errorMessage = 'Có lỗi xảy ra khi thêm nhân sự';
        if ($errorInfo[2]) {
            $errorMessage .= ': ' . $errorInfo[2];
        }
        echo json_encode(['success' => false, 'message' => $errorMessage]);
    }

} catch (PDOException $e) {
    error_log("Add staff error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Add staff error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
} 