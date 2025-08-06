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
    $dateFrom = $_GET['dateFrom'] ?? '';
    $dateTo = $_GET['dateTo'] ?? '';
    
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
    
    // Phân quyền xem đơn nghỉ phép
    // Chỉ admin và HR mới có quyền xem tất cả đơn
    // Nhân viên thường chỉ xem được đơn của mình
    if (!in_array($current_user['role'], ['admin', 'hr'])) {
        $sql .= " AND lr.requester_id = ?";
        $params[] = $current_user['id'];
    }
    
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
    
    // Tìm kiếm theo tên người nghỉ phép
    if (!empty($search)) {
        $sql .= " AND s.fullname LIKE ?";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
    }
    
    // Lọc theo khoảng thời gian
    if (!empty($dateFrom)) {
        $sql .= " AND DATE(lr.start_date) >= ?";
        $params[] = $dateFrom;
    }
    
    if (!empty($dateTo)) {
        $sql .= " AND DATE(lr.start_date) <= ?";
        $params[] = $dateTo;
    }
    
    $sql .= " ORDER BY lr.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tính toán số ngày báo trước cho mỗi đơn nghỉ phép
    $currentDate = new DateTime();
    $currentDate->setTime(0, 0, 0); // Reset time to start of day for accurate day calculation
    
    foreach ($requests as &$request) {
        if (!empty($request['start_date'])) {
            $startDate = new DateTime($request['start_date']);
            $startDate->setTime(0, 0, 0); // Reset time to start of day
            
            $interval = $currentDate->diff($startDate);
            
            // Nếu ngày bắt đầu đã qua, hiển thị số ngày đã qua (số âm)
            // Nếu ngày bắt đầu chưa đến, hiển thị số ngày còn lại (số dương)
            if ($startDate < $currentDate) {
                $request['notice_days'] = -$interval->days; // Số âm để chỉ ra đã qua
            } else {
                $request['notice_days'] = $interval->days; // Số dương để chỉ ra còn lại
            }
        } else {
            $request['notice_days'] = 0;
        }
    }
    
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