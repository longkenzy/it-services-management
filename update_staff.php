<?php
/**
 * IT CRM - Update Staff Page
 * File: update_staff.php
 * Mục đích: Trang cập nhật thông tin nhân viên
 */

// Include các file cần thiết
require_once 'includes/session.php';
require_once 'config/db.php';

// Bảo vệ trang - chỉ admin mới được truy cập
requireAdmin();

// Lấy thông tin user hiện tại
$current_user = getCurrentUser();

// Kiểm tra method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method không hợp lệ']);
    exit();
}

// Lấy và validate dữ liệu
$staff_id = isset($_POST['staff_id']) ? intval($_POST['staff_id']) : 0;

if (!$staff_id) {
    echo json_encode(['success' => false, 'message' => 'ID nhân sự không hợp lệ']);
    exit();
}

// Lấy thông tin nhân sự hiện tại
try {
    $stmt = $pdo->prepare("SELECT * FROM staffs WHERE id = ?");
    $stmt->execute([$staff_id]);
    $current_staff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$current_staff) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhân sự']);
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching current staff: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu']);
    exit();
}

// Xử lý position_id để lấy tên chức vụ và phòng ban
$position_name = trim($_POST['position'] ?? ''); // fallback từ hidden field
$department_name = trim($_POST['department'] ?? ''); // fallback từ form field

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

// Validate và sanitize dữ liệu input
$data = [
    'staff_code' => trim($_POST['staff_code'] ?? ''),
    'fullname' => trim($_POST['fullname'] ?? ''),
    'birth_date' => $_POST['birth_date'] ?? null,
    'gender' => $_POST['gender'] ?? '',
    'hometown' => trim($_POST['hometown'] ?? ''),
    'religion' => trim($_POST['religion'] ?? ''),
    'ethnicity' => trim($_POST['ethnicity'] ?? ''),
    'phone_main' => trim($_POST['phone_main'] ?? ''),
    'phone_alt' => trim($_POST['phone_alt'] ?? ''),
    'email_work' => trim($_POST['email_work'] ?? ''),
    'email_personal' => trim($_POST['email_personal'] ?? ''),
    'place_of_birth' => trim($_POST['place_of_birth'] ?? ''),
    'address_perm' => trim($_POST['address_perm'] ?? ''),
    'address_temp' => trim($_POST['address_temp'] ?? ''),
    'position' => $position_name,
    'department' => $department_name,
    'office' => trim($_POST['office'] ?? ''),
    'office_address' => trim($_POST['office_address'] ?? ''),
    'job_type' => $_POST['job_type'] ?? '',
    'start_date' => $_POST['start_date'] ?? null,
    'seniority' => floatval($_POST['seniority'] ?? 0),
    'username' => trim($_POST['username'] ?? ''),
    'password' => trim($_POST['password'] ?? ''),
    'role' => $_POST['role'] ?? 'user',
    'resigned' => isset($_POST['resigned']) ? 1 : 0
];

// Validate required fields
if (empty($data['staff_code']) || empty($data['fullname']) || empty($data['username'])) {
    echo json_encode(['success' => false, 'message' => 'Mã nhân viên, họ tên và username không được để trống']);
    exit();
}

// Kiểm tra staff_code trùng lặp (trừ chính nó)
try {
    $stmt = $pdo->prepare("SELECT id FROM staffs WHERE staff_code = ? AND id != ?");
    $stmt->execute([$data['staff_code'], $staff_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Mã nhân viên đã tồn tại']);
        exit();
    }
} catch (PDOException $e) {
    error_log("Error checking duplicate employee code: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi kiểm tra mã nhân viên']);
    exit();
}

// Kiểm tra username trùng lặp (trừ chính nó)
try {
    // Kiểm tra trong bảng staffs (trừ chính nó)
    $stmt = $pdo->prepare("SELECT username FROM staffs WHERE username = ? AND username != ?");
    $stmt->execute([$data['username'], $current_staff['username'] ?? '']);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username đã tồn tại trong danh sách nhân sự']);
        exit();
    }
} catch (PDOException $e) {
    error_log("Error checking duplicate username: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi kiểm tra username']);
    exit();
}

try {
    // Bắt đầu transaction
    $pdo->beginTransaction();
    
    // Xử lý upload avatar nếu có
    $new_avatar = $current_staff['avatar'] ?? '';
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'assets/uploads/avatars/';
        $file_info = $_FILES['avatar'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file_info['type'], $allowed_types)) {
            throw new Exception('Chỉ chấp nhận file ảnh (JPEG, PNG, GIF)');
        }
        
        // Validate file size (max 5MB)
        if ($file_info['size'] > 5 * 1024 * 1024) {
            throw new Exception('Kích thước file không được vượt quá 5MB');
        }
        
        // Generate unique filename
        $extension = pathinfo($file_info['name'], PATHINFO_EXTENSION);
        $new_filename = 'staff_' . $data['staff_code'] . '_' . time() . '.' . $extension;
        $upload_path = $upload_dir . $new_filename;
        
        // Create directory if not exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Move uploaded file
        if (move_uploaded_file($file_info['tmp_name'], $upload_path)) {
            // Delete old avatar if exists
            if ($new_avatar && !empty($new_avatar)) {
                // Xử lý đường dẫn avatar cũ
                $old_avatar_path = '';
                if (str_contains($new_avatar, '/')) {
                    // Đường dẫn đầy đủ
                    $old_avatar_path = $new_avatar;
                } else {
                    // Chỉ là tên file
                    $old_avatar_path = $upload_dir . $new_avatar;
                }
                
                // Xóa file cũ nếu tồn tại
                if (file_exists($old_avatar_path)) {
                    unlink($old_avatar_path);
                    error_log("Deleted old avatar: " . $old_avatar_path);
                }
            }
            $new_avatar = $new_filename;
        } else {
            throw new Exception('Không thể upload file ảnh');
        }
    }
    
    // Cập nhật thông tin nhân sự
    $update_staff_sql = "UPDATE staffs SET 
        staff_code = ?, fullname = ?, birth_date = ?, gender = ?, 
        hometown = ?, religion = ?, ethnicity = ?, phone_main = ?, 
        phone_alt = ?, email_work = ?, email_personal = ?, place_of_birth = ?, 
        address_perm = ?, address_temp = ?, position = ?, department = ?, 
        office = ?, office_address = ?, job_type = ?, start_date = ?, 
        seniority = ?, username = ?, role = ?, resigned = ?, avatar = ?, updated_at = NOW() 
        WHERE id = ?";
    
    $stmt = $pdo->prepare($update_staff_sql);
    $result = $stmt->execute([
        $data['staff_code'], $data['fullname'], $data['birth_date'], $data['gender'],
        $data['hometown'], $data['religion'], $data['ethnicity'], $data['phone_main'],
        $data['phone_alt'], $data['email_work'], $data['email_personal'], $data['place_of_birth'],
        $data['address_perm'], $data['address_temp'], $data['position'], $data['department'],
        $data['office'], $data['office_address'], $data['job_type'], $data['start_date'],
        $data['seniority'], $data['username'], $data['role'], $data['resigned'], $new_avatar, $staff_id
    ]);
    
    if (!$result) {
        throw new Exception('Không thể cập nhật thông tin nhân sự');
    }
    
    // Cập nhật password nếu có
    if (!empty($data['password'])) {
        $update_password_sql = "UPDATE staffs SET password = ? WHERE id = ?";
        $stmt = $pdo->prepare($update_password_sql);
        $stmt->execute([password_hash($data['password'], PASSWORD_DEFAULT), $staff_id]);
    }
    
    // Cập nhật password nếu có
    if (!empty($data['password'])) {
        $update_password_sql = "UPDATE staffs SET password = ? WHERE id = ?";
        $stmt = $pdo->prepare($update_password_sql);
        $stmt->execute([password_hash($data['password'], PASSWORD_DEFAULT), $staff_id]);
    }
    
    // Commit transaction
    $pdo->commit();
    
    // Log hoạt động
    $log_message = "Admin {$_SESSION['username']} đã cập nhật thông tin nhân sự: {$data['fullname']} (ID: {$staff_id})";
    error_log($log_message);
    
    // Trả về kết quả thành công
    $message = "Đã cập nhật thành công thông tin nhân sự: {$data['fullname']}";
    
    // Thêm thông báo về trạng thái tài khoản nếu đã nghỉ
    if ($data['resigned'] == 1) {
        $message .= ". Tài khoản đăng nhập đã bị vô hiệu hóa.";
    }
    
    echo json_encode([
        'success' => true, 
        'message' => $message,
        'name' => $data['fullname']
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    $pdo->rollback();
    
    // Log lỗi
    error_log("Error updating staff: " . $e->getMessage());
    
    // Trả về lỗi
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?> 