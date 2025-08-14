<?php
/**
 * API lấy dữ liệu deployment case
 * File: api/get_deployment_case.php
 */

require_once '../config/db.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (null === getCurrentUserId()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

try {
    $id = $_GET['id'] ?? '';
    
    if (empty($id)) {
        throw new Exception('ID case không được để trống');
    }
    
    // Lấy thông tin deployment case
    $sql = "SELECT * FROM deployment_cases WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $case = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$case) {
        throw new Exception('Case không tồn tại');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $case
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
