<?php
// Bảo vệ file khỏi truy cập trực tiếp (chỉ cho phép từ cùng domain)
if (!isset($_SERVER['HTTP_REFERER']) || !str_contains($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST'])) {
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
        http_response_code(403);
        exit('Access denied.');
    }
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
    $id = $_POST['id'] ?? null;
    if (empty($id)) {
        echo json_encode(['error' => 'ID case là bắt buộc']);
        exit;
    }
    $current_user_id = $_SESSION['user_id'];

    // Lấy thông tin case trước khi xóa
    $stmt = $pdo->prepare("SELECT * FROM deployment_cases WHERE id = ?");
    $stmt->execute([$id]);
    $case = $stmt->fetch();
    if (!$case) {
        echo json_encode(['error' => 'Case không tồn tại']);
        exit;
    }

    // Xóa case
    $deleteStmt = $pdo->prepare("DELETE FROM deployment_cases WHERE id = ?");
    $deleteStmt->execute([$id]);

    if ($deleteStmt->rowCount() > 0) {
        // Ghi log
        $log_message = "Xóa case triển khai: " . $case['case_number'] . " - " . ($case['case_description'] ?? '');
        $log_stmt = $pdo->prepare("
            INSERT INTO user_activity_logs (user_id, activity, details, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $log_stmt->execute([$current_user_id, 'delete_deployment_case', $log_message]);

        echo json_encode([
            'success' => true,
            'message' => 'Xóa case triển khai thành công',
            'deleted_case' => [
                'id' => $case['id'],
                'case_number' => $case['case_number'],
                'case_description' => $case['case_description']
            ]
        ]);
    } else {
        echo json_encode(['error' => 'Không thể xóa case triển khai']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?> 