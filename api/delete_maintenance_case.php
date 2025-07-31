<?php
header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../config/db.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $case_id = $input['id'] ?? null;

    if (!$case_id) {
        echo json_encode(['success' => false, 'message' => 'ID case không hợp lệ']);
        exit;
    }

    // Bắt đầu transaction
    $pdo->beginTransaction();

    try {
        // Lấy thông tin case trước khi xóa để log
        $stmt = $pdo->prepare("SELECT case_code FROM maintenance_cases WHERE id = ?");
        $stmt->execute([$case_id]);
        $case = $stmt->fetch();

        if (!$case) {
            throw new Exception('Case bảo trì không tồn tại');
        }

        // Xóa các tasks liên quan trước
        $stmt = $pdo->prepare("DELETE FROM maintenance_tasks WHERE maintenance_case_id = ?");
        $stmt->execute([$case_id]);

        // Xóa case
        $stmt = $pdo->prepare("DELETE FROM maintenance_cases WHERE id = ?");
        $stmt->execute([$case_id]);

        // Commit transaction
        $pdo->commit();

        // Log hoạt động
        $log_message = "Xóa case bảo trì: {$case['case_code']}";
        $log_sql = "INSERT INTO user_activity_logs (user_id, activity, details, ip_address) VALUES (?, ?, ?, ?)";
        $log_stmt = $pdo->prepare($log_sql);
        $log_stmt->execute([
            getCurrentUserId(),
            'DELETE_MAINTENANCE_CASE',
            $log_message,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Xóa case bảo trì thành công'
        ]);

    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in delete_maintenance_case.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
?> 