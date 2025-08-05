<?php
/**
 * API lấy danh sách thông báo của user
 */

header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../config/db.php';

if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Chưa đăng nhập'
    ]);
    exit;
}

try {
    $user_id = $_SESSION[SESSION_USER_ID];
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
    $unread_only = isset($_GET['unread_only']) ? $_GET['unread_only'] === 'true' : false;
    
    $offset = ($page - 1) * $limit;
    
    // Xây dựng query
    $where_conditions = ["user_id = ?"];
    $params = [$user_id];
    
    if ($unread_only) {
        $where_conditions[] = "is_read = 0";
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Đếm tổng số thông báo
    $count_sql = "SELECT COUNT(*) as total FROM notifications WHERE $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Lấy danh sách thông báo
    $sql = "SELECT * FROM notifications WHERE $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters
    foreach ($params as $index => $param) {
        $stmt->bindValue($index + 1, $param);
    }
    $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tính toán pagination
    $total_pages = ceil($total / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'notifications' => $notifications,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_records' => $total,
                'limit' => $limit,
                'has_next' => $page < $total_pages,
                'has_prev' => $page > 1
            ]
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error getting notifications: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi tải thông báo'
    ]);
}
?> 