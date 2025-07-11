<?php
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Access denied.');
}
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

// Get HTTP method and handle different request types
$method = $_SERVER['REQUEST_METHOD'];
$input = null;

// Read JSON input for POST and PUT requests
if ($method === 'POST' || $method === 'PUT') {
    // For testing, use global variable if available
    if (isset($GLOBALS['php_input'])) {
        $rawInput = $GLOBALS['php_input'];
    } else {
        $rawInput = file_get_contents('php://input');
    }
    $input = json_decode($rawInput, true);
}

// Get action from different sources
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Map HTTP methods to actions for REST API
if (!$action) {
    switch ($method) {
        case 'GET':
            $action = 'list';
            break;
        case 'POST':
            $action = 'add';
            break;
        case 'PUT':
            $action = 'update';
            break;
        case 'DELETE':
            $action = 'delete';
            break;
    }
}

try {
    if ($action === 'list') {
        // Get positions with department name
        $stmt = $pdo->query("
            SELECT p.*, d.name as department_name 
            FROM positions p 
            LEFT JOIN departments d ON p.department_id = d.id 
            WHERE p.status = 'active'
            ORDER BY d.name ASC, p.name ASC
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
    
    if ($action === 'add') {
        // Support both JSON and form data
        $name = trim($input['name'] ?? $_POST['name'] ?? '');
        $department_id = intval($input['department_id'] ?? $_POST['department_id'] ?? 0);
        $status = $input['status'] ?? $_POST['status'] ?? 'active';
        
        if ($name === '' || $department_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Tên chức vụ và phòng ban không được để trống!']);
            exit;
        }
        
        // Kiểm tra phòng ban có tồn tại không
        $dept_stmt = $pdo->prepare("SELECT id FROM departments WHERE id = ?");
        $dept_stmt->execute([$department_id]);
        if (!$dept_stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Phòng ban không tồn tại!']);
            exit;
        }
        
        // Kiểm tra trùng lặp tên trong cùng phòng ban
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM positions WHERE name = ? AND department_id = ?");
        $stmt->execute([$name, $department_id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo json_encode(['success' => false, 'message' => 'Chức vụ này đã tồn tại trong phòng ban! Vui lòng chọn tên khác.']);
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO positions (name, department_id, status) VALUES (?, ?, ?)");
        $stmt->execute([$name, $department_id, $status]);
        $newId = $pdo->lastInsertId();
        
        // Get department name for response
        $dept_stmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
        $dept_stmt->execute([$department_id]);
        $department_name = $dept_stmt->fetchColumn();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Thêm chức vụ thành công!', 
            'data' => [
                'id' => $newId,
                'department_name' => $department_name
            ]
        ]);
        exit;
    }
    
    if ($action === 'update') {
        // Get ID from URL parameter for REST API
        $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
        
        // Support both JSON and form data
        $name = trim($input['name'] ?? $_POST['name'] ?? '');
        $department_id = intval($input['department_id'] ?? $_POST['department_id'] ?? 0);
        $status = $input['status'] ?? $_POST['status'] ?? 'active';
        
        if ($id <= 0 || $name === '' || $department_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc!']);
            exit;
        }
        
        // Kiểm tra phòng ban có tồn tại không
        $dept_stmt = $pdo->prepare("SELECT id FROM departments WHERE id = ?");
        $dept_stmt->execute([$department_id]);
        if (!$dept_stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Phòng ban không tồn tại!']);
            exit;
        }
        
        // Kiểm tra trùng lặp tên trong cùng phòng ban (loại trừ record hiện tại)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM positions WHERE name = ? AND department_id = ? AND id != ?");
        $stmt->execute([$name, $department_id, $id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo json_encode(['success' => false, 'message' => 'Chức vụ này đã tồn tại trong phòng ban! Vui lòng chọn tên khác.']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE positions SET name=?, department_id=?, status=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$name, $department_id, $status, $id]);
        
        // Get department name for response
        $dept_stmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
        $dept_stmt->execute([$department_id]);
        $department_name = $dept_stmt->fetchColumn();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Cập nhật chức vụ thành công!',
            'data' => [
                'department_name' => $department_name
            ]
        ]);
        exit;
    }
    
    if ($action === 'delete') {
        // Get ID from URL parameter for REST API
        $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Thiếu ID chức vụ!']);
            exit;
        }
        
        // Kiểm tra chức vụ có tồn tại không
        $check_sql = "SELECT name FROM positions WHERE id = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$id]);
        $position = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$position) {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy chức vụ!']);
            exit;
        }
        
        // Hard delete - thực sự xóa khỏi database
        $stmt = $pdo->prepare("DELETE FROM positions WHERE id=?");
        $result = $stmt->execute([$id]);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Đã xóa chức vụ!',
                'data' => [
                    'id' => $id,
                    'name' => $position['name']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể xóa chức vụ!']);
        }
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Action không hợp lệ!']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
} 