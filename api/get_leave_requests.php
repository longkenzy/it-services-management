<?php
/**
 * API: Lấy danh sách đơn nghỉ phép
 * Method: GET
 * Parameters: status, type, search (optional)
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
    $current_user = getCurrentUser();
    
    // Lấy các tham số lọc
    $status = $_GET['status'] ?? '';
    $type = $_GET['type'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // Xây dựng câu query
    $sql = "SELECT 
                lr.*,
                s.fullname as requester_name,
                s.avatar as requester_avatar,
                s.position as requester_position,
                s.department as requester_department,
                s.office as requester_office,
                h.fullname as handover_name,
                h.position as handover_position,
                a.fullname as approver_name
            FROM leave_requests lr
            LEFT JOIN staffs s ON lr.requester_id = s.id
            LEFT JOIN staffs h ON lr.handover_to = h.id
            LEFT JOIN staffs a ON lr.approved_by = a.id
            WHERE 1=1";
    
    $params = [];
    
    // Lọc theo trạng thái
    if (!empty($status)) {
        $sql .= " AND lr.status = ?";
        $params[] = $status;
    }
    
    // Lọc theo loại nghỉ phép
    if (!empty($type)) {
        $sql .= " AND lr.leave_type = ?";
        $params[] = $type;
    }
    
    // Tìm kiếm
    if (!empty($search)) {
        $sql .= " AND (lr.request_code LIKE ? OR s.fullname LIKE ? OR lr.reason LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    
    // Chỉ hiển thị đơn của người dùng hiện tại (trừ admin/leader)
    if (!hasRole(['admin', 'leader'])) {
        $sql .= " AND lr.requester_id = ?";
        $params[] = $current_user['id'];
    }
    
    $sql .= " ORDER BY lr.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $requests,
        'total' => count($requests)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi tải danh sách đơn nghỉ phép: ' . $e->getMessage()
    ]);
}
?> 