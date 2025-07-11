<?php
/**
 * IT CRM - Departments API
 * File: api/departments.php
 * Mục đích: API xử lý CRUD operations cho phòng ban
 */

// Include các file cần thiết
require_once '../config/db.php';
require_once '../includes/session.php';

// Thiết lập header
header('Content-Type: application/json; charset=utf-8');

// Kiểm tra authentication và quyền admin
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Bạn không có quyền truy cập.'
    ]);
    exit();
}

// Lấy thông tin user hiện tại
$current_user = getCurrentUser();

// Xử lý request method
$method = $_SERVER['REQUEST_METHOD'];

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Access denied.');
}

try {
    switch ($method) {
        case 'GET':
            handleGetDepartments();
            break;
        case 'POST':
            handleCreateDepartment();
            break;
        case 'PUT':
            handleUpdateDepartment();
            break;
        case 'DELETE':
            handleDeleteDepartment();
            break;
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Phương thức không được hỗ trợ.'
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
}

/**
 * Lấy danh sách phòng ban
 */
function handleGetDepartments() {
    global $pdo;
    
    try {
        // Lấy ID cụ thể nếu có
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $sql = "SELECT * FROM departments WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $department = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($department) {
                echo json_encode([
                    'success' => true,
                    'data' => $department
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Không tìm thấy phòng ban.'
                ]);
            }
        } else {
            $sql = "SELECT * FROM departments ORDER BY name ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $departments
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi database: ' . $e->getMessage()
        ]);
    }
}

/**
 * Tạo phòng ban mới
 */
function handleCreateDepartment() {
    global $pdo, $current_user;
    
    // Lấy dữ liệu từ request
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    // Validate required fields
    $name = trim($input['name'] ?? '');
    if (empty($name)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Tên phòng ban không được để trống.'
        ]);
        return;
    }
    
    // Validate name length
    if (strlen($name) > 255) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Tên phòng ban không được vượt quá 255 ký tự.'
        ]);
        return;
    }
    
    // Kiểm tra trùng lặp
    try {
        $check_sql = "SELECT id FROM departments WHERE name = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$name]);
        
        if ($check_stmt->rowCount() > 0) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Tên phòng ban đã tồn tại.'
            ]);
            return;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi kiểm tra trùng lặp: ' . $e->getMessage()
        ]);
        return;
    }
    
    // Thêm mới
    try {
        $sql = "INSERT INTO departments (name, office, address, created_by) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $name,
            trim($input['office'] ?? ''),
            trim($input['address'] ?? ''),
            $current_user['id']
        ]);
        
        if ($result) {
            $department_id = $pdo->lastInsertId();
            
            // Log hoạt động
            logUserActivity('Tạo phòng ban', "Tạo phòng ban: {$name}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Tạo phòng ban thành công.',
                'data' => [
                    'id' => $department_id,
                    'name' => $name
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Không thể tạo phòng ban.'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi database: ' . $e->getMessage()
        ]);
    }
}

/**
 * Cập nhật phòng ban
 */
function handleUpdateDepartment() {
    global $pdo, $current_user;
    
    // Lấy ID từ URL
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID phòng ban không hợp lệ.'
        ]);
        return;
    }
    
    // Lấy dữ liệu từ request
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        parse_str(file_get_contents('php://input'), $input);
    }
    
    // Validate required fields
    $name = trim($input['name'] ?? '');
    if (empty($name)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Tên phòng ban không được để trống.'
        ]);
        return;
    }
    
    // Kiểm tra phòng ban có tồn tại không
    try {
        $check_sql = "SELECT id FROM departments WHERE id = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$id]);
        
        if ($check_stmt->rowCount() == 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy phòng ban.'
            ]);
            return;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi kiểm tra tồn tại: ' . $e->getMessage()
        ]);
        return;
    }
    
    // Kiểm tra trùng lặp tên (trừ chính nó)
    try {
        $check_sql = "SELECT id FROM departments WHERE name = ? AND id != ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$name, $id]);
        
        if ($check_stmt->rowCount() > 0) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Tên phòng ban đã tồn tại.'
            ]);
            return;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi kiểm tra trùng lặp: ' . $e->getMessage()
        ]);
        return;
    }
    
    // Cập nhật
    try {
        $sql = "UPDATE departments SET name = ?, office = ?, address = ?, updated_by = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $name,
            trim($input['office'] ?? ''),
            trim($input['address'] ?? ''),
            $current_user['id'],
            $id
        ]);
        
        if ($result) {
            // Log hoạt động
            logUserActivity('Cập nhật phòng ban', "Cập nhật phòng ban ID: {$id} - {$name}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật phòng ban thành công.',
                'data' => [
                    'id' => $id,
                    'name' => $name
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Không thể cập nhật phòng ban.'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi database: ' . $e->getMessage()
        ]);
    }
}

/**
 * Xóa phòng ban
 */
function handleDeleteDepartment() {
    global $pdo, $current_user;
    
    // Lấy ID từ URL
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID phòng ban không hợp lệ.'
        ]);
        return;
    }
    
    // Kiểm tra phòng ban có tồn tại không
    try {
        $check_sql = "SELECT name FROM departments WHERE id = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$id]);
        $department = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$department) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy phòng ban.'
            ]);
            return;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi kiểm tra tồn tại: ' . $e->getMessage()
        ]);
        return;
    }
    
    // Xóa phòng ban
    try {
        $sql = "DELETE FROM departments WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$id]);
        
        if ($result) {
            // Log hoạt động
            logUserActivity('Xóa phòng ban', "Xóa phòng ban: {$department['name']} (ID: {$id})");
            
            echo json_encode([
                'success' => true,
                'message' => 'Xóa phòng ban thành công.',
                'data' => [
                    'id' => $id,
                    'name' => $department['name']
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Không thể xóa phòng ban.'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi database: ' . $e->getMessage()
        ]);
    }
}
?> 