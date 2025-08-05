<?php
/**
 * API: Lấy danh sách nhân viên đầy đủ cho trang staff
 * Method: GET
 * Parameters: page, limit, search, department, position (optional)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/session.php';
require_once '../config/db.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

try {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $search = $_GET['search'] ?? '';
    $department = $_GET['department'] ?? '';
    $position = $_GET['position'] ?? '';
    $gender = $_GET['gender'] ?? '';
    $sort_by = $_GET['sort_by'] ?? 'created_at';
    $sort_order = $_GET['sort_order'] ?? 'DESC';
    
    // Tính offset
    $offset = ($page - 1) * $limit;
    
    // Xây dựng câu query đếm tổng số
    $countSql = "SELECT COUNT(*) as total FROM staffs s WHERE 1=1";
    $countParams = [];
    
    // Xây dựng câu query chính
    $sql = "SELECT 
                s.id,
                s.staff_code,
                s.fullname,
                s.birth_date,
                s.gender,
                s.position,
                s.department,
                s.office,
                s.phone_main,
                s.phone_alt,
                s.email_work,
                s.email_personal,
                s.status,
                s.job_type,
                s.resigned,
                s.username,
                s.role,
                s.avatar,
                s.created_at,
                s.updated_at
            FROM staffs s
            WHERE 1=1";
    $params = [];
    
    // Tìm kiếm
    if (!empty($search)) {
        $searchCondition = " AND (s.fullname LIKE ? OR s.staff_code LIKE ?)";
        $searchTerm = "%$search%";
        $countSql .= $searchCondition;
        $sql .= $searchCondition;
        $countParams[] = $searchTerm;
        $countParams[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Lọc theo phòng ban
    if (!empty($department)) {
        $countSql .= " AND s.department = ?";
        $sql .= " AND s.department = ?";
        $countParams[] = $department;
        $params[] = $department;
    }
    
    // Lọc theo chức vụ
    if (!empty($position)) {
        $countSql .= " AND s.position = ?";
        $sql .= " AND s.position = ?";
        $countParams[] = $position;
        $params[] = $position;
    }
    
    // Lọc theo giới tính
    if (!empty($gender)) {
        $countSql .= " AND s.gender = ?";
        $sql .= " AND s.gender = ?";
        $countParams[] = $gender;
        $params[] = $gender;
    }
    
    // Đếm tổng số
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Xử lý filter theo trạng thái nghỉ việc
    if ($sort_by === 'resigned') {
        $countSql .= " AND s.resigned = 1";
        $sql .= " AND s.resigned = 1";
        $sort_by = 'created_at'; // Reset sort_by về created_at cho resigned
    } elseif ($sort_by === 'active') {
        $countSql .= " AND (s.resigned = 0 OR s.resigned IS NULL)";
        $sql .= " AND (s.resigned = 0 OR s.resigned IS NULL)";
        $sort_by = 'start_date'; // Reset sort_by về start_date cho active để sắp xếp theo ngày vào làm
    }
    
    // Validate sort_by và sort_order
    $allowed_sort_fields = ['created_at', 'fullname', 'staff_code', 'start_date', 'seniority'];
    $allowed_sort_orders = ['ASC', 'DESC'];
    
    if (!in_array($sort_by, $allowed_sort_fields)) {
        $sort_by = 'start_date';
    }
    if (!in_array(strtoupper($sort_order), $allowed_sort_orders)) {
        $sort_order = 'ASC';
    }
    
    // Thêm ORDER BY và LIMIT
    $sql .= " ORDER BY s.$sort_by $sort_order LIMIT ? OFFSET ?";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind tất cả parameters
    foreach ($params as $index => $param) {
        $stmt->bindValue($index + 1, $param);
    }
    $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $staffs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tính toán thông tin pagination
    $totalPages = ceil($totalCount / $limit);
    
    // Tính toán statistics chi tiết
    $totalStmt = $pdo->query("SELECT COUNT(*) as total FROM staffs");
    $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $activeStmt = $pdo->query("SELECT COUNT(*) as active FROM staffs WHERE resigned = 0 OR resigned IS NULL");
    $active = $activeStmt->fetch(PDO::FETCH_ASSOC)['active'];
    
    $resignedStmt = $pdo->query("SELECT COUNT(*) as resigned FROM staffs WHERE resigned = 1");
    $resigned = $resignedStmt->fetch(PDO::FETCH_ASSOC)['resigned'];
    
    // Tính toán theo giới tính
    $maleStmt = $pdo->query("SELECT COUNT(*) as male FROM staffs WHERE gender = 'Nam'");
    $male = $maleStmt->fetch(PDO::FETCH_ASSOC)['male'];
    
    $femaleStmt = $pdo->query("SELECT COUNT(*) as female FROM staffs WHERE gender = 'Nữ'");
    $female = $femaleStmt->fetch(PDO::FETCH_ASSOC)['female'];
    
    $statistics = [
        'total' => $total,
        'active' => $active,
        'resigned' => $resigned,
        'male' => $male,
        'female' => $female
    ];
    
    // Lấy danh sách departments và positions cho filter
    $deptStmt = $pdo->query("SELECT DISTINCT department, COUNT(*) as count FROM staffs WHERE department IS NOT NULL AND department != '' GROUP BY department ORDER BY department");
    $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $posStmt = $pdo->query("SELECT DISTINCT position, COUNT(*) as count FROM staffs WHERE position IS NOT NULL AND position != '' GROUP BY position ORDER BY position");
    $positions = $posStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tạo danh sách genders (nếu có cột gender)
    $genders = [];
    try {
        $genderStmt = $pdo->query("SELECT DISTINCT gender, COUNT(*) as count FROM staffs WHERE gender IS NOT NULL AND gender != '' GROUP BY gender ORDER BY gender");
        $genders = $genderStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Nếu không có cột gender, tạo danh sách rỗng
        $genders = [];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'staffs' => $staffs,
            'statistics' => $statistics,
            'departments' => $departments,
            'positions' => $positions,
            'genders' => $genders,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalCount,
                'limit' => $limit,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi tải danh sách nhân viên: ' . $e->getMessage()
    ]);
}
?> 