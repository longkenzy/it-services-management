<?php
require_once '../config/db.php';
require_once '../includes/session.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get IT staffs
    $sql = "SELECT id, fullname FROM staffs 
            WHERE department = 'IT Dept.' 
            AND status = 'active' 
            AND resigned = 0 
            ORDER BY fullname ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $staffs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $staffs
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_it_staffs.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu']);
} catch (Exception $e) {
    error_log("Error in get_it_staffs.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
?>
