<?php
/**
 * API: Lấy danh sách nhân viên cho dropdown
 * Method: GET
 * Parameters: search (optional)
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
    $search = $_GET['search'] ?? '';
    
    // Xây dựng câu query
    $sql = "SELECT id, fullname, position, department, office FROM staffs WHERE resigned = 0";
    $params = [];
    
    // Tìm kiếm
    if (!empty($search)) {
        $sql .= " AND (fullname LIKE ? OR position LIKE ? OR department LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    $sql .= " ORDER BY fullname ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $staffs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $staffs,
        'total' => count($staffs)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi tải danh sách nhân viên: ' . $e->getMessage()
    ]);
}
?> 