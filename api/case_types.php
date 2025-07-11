<?php
require_once '../config/db.php';
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

// Get type and action from different sources
$type = $input['type'] ?? $_POST['type'] ?? $_GET['type'] ?? '';
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

$table_map = [
    'internal' => 'internal_case_types',
    'deployment' => 'deployment_case_types',
    'maintenance' => 'maintenance_case_types',
];

if (!isset($table_map[$type])) {
    echo json_encode(['success' => false, 'message' => 'Loại case không hợp lệ!']);
    exit;
}
$table = $table_map[$type];

try {
    if ($action === 'list') {
        $stmt = $pdo->query("SELECT * FROM `$table` ORDER BY id ASC");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }
    
    if ($action === 'add') {
        // Support both JSON and form data
        $name = trim($input['name'] ?? $_POST['name'] ?? '');
        $status = $input['status'] ?? $_POST['status'] ?? 'active';
        
        if ($name === '') {
            echo json_encode(['success' => false, 'message' => 'Tên loại case không được để trống!']);
            exit;
        }
        
        // Kiểm tra trùng lặp tên
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE name = ?");
        $stmt->execute([$name]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo json_encode(['success' => false, 'message' => 'Tên loại case đã tồn tại! Vui lòng chọn tên khác.']);
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO `$table` (name, status) VALUES (?, ?)");
        $stmt->execute([$name, $status]);
        $newId = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'message' => 'Thêm thành công!', 'data' => ['id' => $newId]]);
        exit;
    }
    
    if ($action === 'update') {
        // Get ID from URL parameter for REST API
        $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
        
        // Support both JSON and form data
        $name = trim($input['name'] ?? $_POST['name'] ?? '');
        $status = $input['status'] ?? $_POST['status'] ?? 'active';
        
        if ($id <= 0 || $name === '') {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin!']);
            exit;
        }
        
        // Kiểm tra trùng lặp tên (loại trừ record hiện tại)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE name = ? AND id != ?");
        $stmt->execute([$name, $id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            echo json_encode(['success' => false, 'message' => 'Tên loại case đã tồn tại! Vui lòng chọn tên khác.']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE `$table` SET name=?, status=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$name, $status, $id]);
        echo json_encode(['success' => true, 'message' => 'Cập nhật thành công!']);
        exit;
    }
    
    if ($action === 'delete') {
        // Get ID from URL parameter for REST API
        $id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Thiếu ID!']);
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM `$table` WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Đã xoá!']);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Action không hợp lệ!']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
} 