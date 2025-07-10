<?php
/**
 * IT CRM - Get Staffs API
 * File: api/get_staffs.php
 * Mục đích: API lấy danh sách nhân sự với phân trang và tìm kiếm
 */

// Bật hiển thị lỗi để debug (tắt trong production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include các file cần thiết
require_once '../config/db.php';
require_once '../includes/session.php';

// Thiết lập header cho JSON response
header('Content-Type: application/json; charset=utf-8');

// Kiểm tra authentication
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để truy cập.'
    ]);
    exit();
}

// Chỉ cho phép GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được phép.'
    ]);
    exit();
}

try {
    // ===== LẤY THAM SỐ TỪ URL ===== //
    
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $department = isset($_GET['department']) ? trim($_GET['department']) : '';
    $position = isset($_GET['position']) ? trim($_GET['position']) : '';
    $contract_type = isset($_GET['contract_type']) ? trim($_GET['contract_type']) : '';
    $sort_by = isset($_GET['sort_by']) ? trim($_GET['sort_by']) : 'start_date';
    $sort_order = isset($_GET['sort_order']) ? trim($_GET['sort_order']) : 'ASC';
    
    // Validate parameters
    if ($page < 1) $page = 1;
    if ($limit < 1) $limit = 10;
    if ($limit > 100) $limit = 100; // Giới hạn tối đa 100 records
    
    // Validate sort parameters
    $allowed_sort_fields = ['staff_code', 'fullname', 'position', 'department', 'seniority', 'created_at', 'start_date'];
    if (!in_array($sort_by, $allowed_sort_fields)) {
        $sort_by = 'start_date';
    }
    
    $sort_order = strtoupper($sort_order);
    if (!in_array($sort_order, ['ASC', 'DESC'])) {
        $sort_order = 'DESC'; // Mặc định sắp xếp theo ngày vào làm mới nhất trước
    }
    
    // ===== XÂY DỰNG QUERY ===== //
    
    $where_conditions = [];
    $params = [];
    
    // Tìm kiếm theo tên, mã nhân viên, email
    if (!empty($search)) {
        $where_conditions[] = "(fullname LIKE :search OR staff_code LIKE :search OR email_work LIKE :search OR email_personal LIKE :search)";
        $params['search'] = '%' . $search . '%';
    }
    
    // Lọc theo phòng ban
    if (!empty($department)) {
        $where_conditions[] = "department = :department";
        $params['department'] = $department;
    }
    
    // Lọc theo chức vụ
    if (!empty($position)) {
        $where_conditions[] = "position = :position";
        $params['position'] = $position;
    }
    
    // Lọc theo loại hợp đồng
    if (!empty($contract_type)) {
        $where_conditions[] = "job_type = :contract_type";
        $params['contract_type'] = $contract_type;
    }
    
    // Chỉ lấy nhân sự đang làm việc
    $where_conditions[] = "status = 'active'";
    
    // Tạo WHERE clause
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    // ===== ĐẾM TỔNG SỐ RECORDS ===== //
    
    $count_sql = "SELECT COUNT(*) as total FROM staffs $where_clause";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch()['total'];
    
    // ===== LẤY DỮ LIỆU VỚI PHÂN TRANG ===== //
    
    $sql = "SELECT 
                id, staff_code, fullname, username, birth_date, gender, 
                avatar, position, department, office, phone_main, email_work, 
                job_type, seniority, status, role, start_date, created_at, updated_at
            FROM staffs 
            $where_clause 
            ORDER BY start_date IS NULL, start_date ASC";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->execute();
    $staffs = $stmt->fetchAll();
    
    // ===== XỬ LÝ DỮ LIỆU ===== //
    
    $processed_staffs = [];
    foreach ($staffs as $staff) {
        // Xử lý avatar - nếu không có thì dùng avatar mặc định
        $avatar_url = 'assets/images/default-avatar.svg';
        if (!empty($staff['avatar'])) {
            // Nếu avatar chứa đường dẫn đầy đủ thì dùng luôn
            if (strpos($staff['avatar'], '/') !== false) {
                $avatar_url = $staff['avatar'];
            } else {
                // Nếu chỉ là tên file thì thêm đường dẫn
                $avatar_url = 'assets/uploads/avatars/' . $staff['avatar'];
            }
        }
        
        // Tính tuổi từ ngày sinh
        $age = null;
        if ($staff['birth_date']) {
            $birth_date = new DateTime($staff['birth_date']);
            $current_date = new DateTime();
            $age = $current_date->diff($birth_date)->y;
        }
        
        $processed_staffs[] = [
            'id' => $staff['id'],
            'staff_code' => $staff['staff_code'],
            'fullname' => $staff['fullname'],
            'username' => $staff['username'],
            'birth_date' => $staff['birth_date'],
            'age' => $age,
            'gender' => $staff['gender'],
            'avatar' => $avatar_url,
            'position' => $staff['position'],
            'department' => $staff['department'],
            'office' => $staff['office'],
            'phone' => $staff['phone_main'],
            'email' => $staff['email_work'],
            'job_type' => $staff['job_type'],
            'seniority' => $staff['seniority'],
            'status' => $staff['status'],
            'role' => $staff['role'],
            'start_date' => $staff['start_date'],
            'created_at' => $staff['created_at'],
            'updated_at' => $staff['updated_at']
        ];
    }
    
    // ===== LẤY THỐNG KÊ PHÒNG BAN ===== //
    
    $dept_sql = "SELECT department, COUNT(*) as count FROM staffs WHERE status = 'active' AND department IS NOT NULL AND department != '' GROUP BY department ORDER BY count DESC";
    $dept_stmt = $pdo->prepare($dept_sql);
    $dept_stmt->execute();
    $departments = $dept_stmt->fetchAll();
    
    // ===== LẤY THỐNG KÊ CHỨC VỤ ===== //
    
    $pos_sql = "SELECT position, COUNT(*) as count FROM staffs WHERE status = 'active' AND position IS NOT NULL AND position != '' GROUP BY position ORDER BY count DESC";
    $pos_stmt = $pdo->prepare($pos_sql);
    $pos_stmt->execute();
    $positions = $pos_stmt->fetchAll();
    
    // ===== TÍNH TOÁN PHÂN TRANG ===== //
    
    // ===== TRẢ VỀ KẾT QUẢ ===== //
    
    echo json_encode([
        'success' => true,
        'data' => [
            'staffs' => $processed_staffs,
            'statistics' => [
                'departments' => $departments,
                'positions' => $positions,
                'total_active_staff' => $total_records
            ],
            'filters' => [
                'search' => $search,
                'department' => $department,
                'position' => $position,
                'contract_type' => $contract_type,
                'sort_by' => $sort_by,
                'sort_order' => $sort_order
            ]
        ],
        'message' => 'Lấy dữ liệu nhân sự thành công.'
    ]);
    
} catch (PDOException $e) {
    // Lỗi database
    error_log("Database error in get_staffs: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống. Vui lòng thử lại sau.',
        'error' => $e->getMessage()
    ]);
    
} catch (Exception $e) {
    // Lỗi khác
    error_log("General error in get_staffs: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau.',
        'error' => $e->getMessage()
    ]);
}

?> 