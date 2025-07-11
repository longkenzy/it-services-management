<?php
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Access denied.');
}
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($input['case_id'])) {
        echo json_encode(['error' => 'Case ID là bắt buộc']);
        exit;
    }
    
    $case_id = $input['case_id'];
    $current_user_id = $_SESSION['user_id'];
    
    // Lấy thông tin case trước khi xóa
    $stmt = $pdo->prepare("SELECT * FROM internal_cases WHERE id = ?");
    $stmt->execute([$case_id]);
    $case = $stmt->fetch();
    
    if (!$case) {
        echo json_encode(['error' => 'Case không tồn tại']);
        exit;
    }
    
    // Kiểm tra quyền xóa (có thể thêm logic phân quyền ở đây)
    // Ví dụ: chỉ cho phép người tạo hoặc admin xóa
    // if ($case['requester_id'] != $current_user_id && $current_user_role != 'admin') {
    //     echo json_encode(['error' => 'Bạn không có quyền xóa case này']);
    //     exit;
    // }
    
    // Xóa case
    $deleteStmt = $pdo->prepare("DELETE FROM internal_cases WHERE id = ?");
    $deleteStmt->execute([$case_id]);
    
    if ($deleteStmt->rowCount() > 0) {
        // Ghi log
        $log_message = "Xóa case: " . $case['case_number'] . " - " . $case['issue_title'];
        $log_stmt = $pdo->prepare("
            INSERT INTO user_activity_logs (user_id, activity, details, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $log_stmt->execute([$current_user_id, 'delete_case', $log_message]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Xóa case thành công',
            'deleted_case' => [
                'id' => $case['id'],
                'case_number' => $case['case_number'],
                'issue_title' => $case['issue_title']
            ]
        ]);
    } else {
        echo json_encode(['error' => 'Không thể xóa case']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 