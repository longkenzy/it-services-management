<?php
/**
 * IT CRM - EU Companies API
 * File: api/eu_companies.php
 * Mục đích: API xử lý CRUD operations cho công ty EU
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
            handleGetEUCompanies();
            break;
        case 'POST':
            handleCreateEUCompany();
            break;
        case 'PUT':
            handleUpdateEUCompany();
            break;
        case 'DELETE':
            handleDeleteEUCompany();
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
 * Lấy danh sách công ty EU
 */
function handleGetEUCompanies() {
    global $pdo;
    
    try {
        // Lấy ID cụ thể nếu có
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $sql = "SELECT * FROM eu_companies WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $company = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($company) {
                echo json_encode([
                    'success' => true,
                    'data' => $company
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Không tìm thấy công ty EU.'
                ]);
            }
        } else {
            $sql = "SELECT * FROM eu_companies ORDER BY name ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $companies
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
 * Tạo công ty EU mới
 */
function handleCreateEUCompany() {
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
            'message' => 'Tên công ty không được để trống.'
        ]);
        return;
    }
    
    // Validate name length
    if (strlen($name) > 255) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Tên công ty không được vượt quá 255 ký tự.'
        ]);
        return;
    }
    
    // Validate status
    $status = $input['status'] ?? 'active';
    if (!in_array($status, ['active', 'inactive'])) {
        $status = 'active';
    }
    
    // Kiểm tra trùng lặp tên
    try {
        $check_sql = "SELECT id FROM eu_companies WHERE name = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$name]);
        
        if ($check_stmt->rowCount() > 0) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Tên công ty đã tồn tại.'
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
        $sql = "INSERT INTO eu_companies (name, short_name, address, contact_person, contact_phone, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $name,
            trim($input['short_name'] ?? ''),
            trim($input['address'] ?? ''),
            trim($input['contact_person'] ?? ''),
            trim($input['contact_phone'] ?? ''),
            $status,
            $current_user['id']
        ]);
        
        if ($result) {
            $company_id = $pdo->lastInsertId();
            
            // Log hoạt động
            logUserActivity('Tạo công ty EU', "Tạo công ty EU: {$name}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Tạo công ty EU thành công.',
                'data' => [
                    'id' => $company_id,
                    'name' => $name
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Không thể tạo công ty EU.'
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
 * Cập nhật công ty EU
 */
function handleUpdateEUCompany() {
    global $pdo, $current_user;
    
    // Lấy ID từ URL
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID công ty EU không hợp lệ.'
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
            'message' => 'Tên công ty không được để trống.'
        ]);
        return;
    }
    
    // Validate status
    $status = $input['status'] ?? 'active';
    if (!in_array($status, ['active', 'inactive'])) {
        $status = 'active';
    }
    
    // Kiểm tra công ty có tồn tại không
    try {
        $check_sql = "SELECT id FROM eu_companies WHERE id = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$id]);
        
        if ($check_stmt->rowCount() == 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy công ty EU.'
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
        $check_sql = "SELECT id FROM eu_companies WHERE name = ? AND id != ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$name, $id]);
        
        if ($check_stmt->rowCount() > 0) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Tên công ty đã tồn tại.'
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
        $sql = "UPDATE eu_companies SET name = ?, short_name = ?, address = ?, contact_person = ?, contact_phone = ?, status = ?, updated_by = ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $name,
            trim($input['short_name'] ?? ''),
            trim($input['address'] ?? ''),
            trim($input['contact_person'] ?? ''),
            trim($input['contact_phone'] ?? ''),
            $status,
            $current_user['id'],
            $id
        ]);
        
        if ($result) {
            // Log hoạt động
            logUserActivity('Cập nhật công ty EU', "Cập nhật công ty EU ID: {$id} - {$name}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật công ty EU thành công.',
                'data' => [
                    'id' => $id,
                    'name' => $name
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Không thể cập nhật công ty EU.'
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
 * Xóa công ty EU
 */
function handleDeleteEUCompany() {
    global $pdo, $current_user;
    
    // Lấy ID từ URL
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID công ty EU không hợp lệ.'
        ]);
        return;
    }
    
    // Kiểm tra công ty có tồn tại không
    try {
        $check_sql = "SELECT name FROM eu_companies WHERE id = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$id]);
        $company = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$company) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy công ty EU.'
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
    
    // Xóa công ty EU
    try {
        $sql = "DELETE FROM eu_companies WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$id]);
        
        if ($result) {
            // Log hoạt động
            logUserActivity('Xóa công ty EU', "Xóa công ty EU: {$company['name']} (ID: {$id})");
            
            echo json_encode([
                'success' => true,
                'message' => 'Xóa công ty EU thành công.',
                'data' => [
                    'id' => $id,
                    'name' => $company['name']
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Không thể xóa công ty EU.'
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