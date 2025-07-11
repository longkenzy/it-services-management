<?php
/**
 * IT CRM - Partner Companies API
 * File: api/partner_companies.php
 * Mục đích: API xử lý CRUD operations cho công ty đối tác
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

try {
    switch ($method) {
        case 'GET':
            handleGetPartnerCompanies();
            break;
        case 'POST':
            handleCreatePartnerCompany();
            break;
        case 'PUT':
            handleUpdatePartnerCompany();
            break;
        case 'DELETE':
            handleDeletePartnerCompany();
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
 * Lấy danh sách công ty đối tác
 */
function handleGetPartnerCompanies() {
    global $pdo;
    
    try {
        // Lấy ID cụ thể nếu có
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $sql = "SELECT * FROM partner_companies WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $partner = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($partner) {
                echo json_encode([
                    'success' => true,
                    'data' => $partner
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Không tìm thấy công ty đối tác.'
                ]);
            }
        } else {
            $sql = "SELECT * FROM partner_companies ORDER BY short_name ASC, name ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $partners
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
 * Tạo công ty đối tác mới
 */
function handleCreatePartnerCompany() {
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
    
    // Email validation removed - contact_email field no longer exists
    
    // Validate status
    $status = $input['status'] ?? 'active';
    if (!in_array($status, ['active', 'inactive'])) {
        $status = 'active';
    }
    
    // Kiểm tra trùng lặp tên
    try {
        $check_sql = "SELECT id FROM partner_companies WHERE name = ?";
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
        $sql = "INSERT INTO partner_companies (name, short_name, address, contact_person, contact_phone, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)";
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
            $partner_id = $pdo->lastInsertId();
            
            // Log hoạt động
            logUserActivity('Tạo công ty đối tác', "Tạo công ty đối tác: {$name}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Tạo công ty đối tác thành công.',
                'data' => [
                    'id' => $partner_id,
                    'name' => $name
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Không thể tạo công ty đối tác.'
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
 * Cập nhật công ty đối tác
 */
function handleUpdatePartnerCompany() {
    global $pdo, $current_user;
    
    // Lấy ID từ URL
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID công ty đối tác không hợp lệ.'
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
    
    // Email validation removed - contact_email field no longer exists
    
    // Validate status
    $status = $input['status'] ?? 'active';
    if (!in_array($status, ['active', 'inactive'])) {
        $status = 'active';
    }
    
    // Kiểm tra công ty có tồn tại không
    try {
        $check_sql = "SELECT id FROM partner_companies WHERE id = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$id]);
        
        if ($check_stmt->rowCount() == 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy công ty đối tác.'
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
        $check_sql = "SELECT id FROM partner_companies WHERE name = ? AND id != ?";
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
        $sql = "UPDATE partner_companies SET name = ?, short_name = ?, address = ?, contact_person = ?, contact_phone = ?, status = ?, updated_by = ? WHERE id = ?";
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
            logUserActivity('Cập nhật công ty đối tác', "Cập nhật công ty đối tác ID: {$id} - {$name}");
            
            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật công ty đối tác thành công.',
                'data' => [
                    'id' => $id,
                    'name' => $name
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Không thể cập nhật công ty đối tác.'
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
 * Xóa công ty đối tác
 */
function handleDeletePartnerCompany() {
    global $pdo, $current_user;
    
    // Lấy ID từ URL
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID công ty đối tác không hợp lệ.'
        ]);
        return;
    }
    
    // Kiểm tra công ty có tồn tại không
    try {
        $check_sql = "SELECT name FROM partner_companies WHERE id = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$id]);
        $partner = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$partner) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy công ty đối tác.'
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
    
    // Xóa công ty đối tác
    try {
        $sql = "DELETE FROM partner_companies WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$id]);
        
        if ($result) {
            // Log hoạt động
            logUserActivity('Xóa công ty đối tác', "Xóa công ty đối tác: {$partner['name']} (ID: {$id})");
            
            echo json_encode([
                'success' => true,
                'message' => 'Xóa công ty đối tác thành công.',
                'data' => [
                    'id' => $id,
                    'name' => $partner['name']
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Không thể xóa công ty đối tác.'
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