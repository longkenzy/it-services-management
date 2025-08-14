<?php
/**
 * Update Leave Status Database
 * File: api/update_leave_status_database.php
 * Purpose: Update database to support new leave status values for departments
 */

// Include necessary files
require_once 'includes/session.php';
require_once 'config/db.php';

// Bảo vệ API - yêu cầu đăng nhập
requireLogin();

try {
    // Cập nhật cột status để hỗ trợ trạng thái mới theo phòng ban
    $sql = "ALTER TABLE leave_requests MODIFY COLUMN status ENUM(
        'Chờ phê duyệt',
        'IT Leader đã phê duyệt',
        'Sale Leader đã phê duyệt',
        'Admin đã phê duyệt',
        'HR đã phê duyệt',
        'Đã phê duyệt',
        'Từ chối bởi IT Leader',
        'Từ chối bởi Sale Leader',
        'Từ chối bởi Admin',
        'Từ chối bởi HR',
        'Từ chối'
    ) NOT NULL DEFAULT 'Chờ phê duyệt' COMMENT 'Trạng thái đơn nghỉ phép'";
    
    $pdo->exec($sql);
    
    // Thêm index để tối ưu hiệu suất
    try {
        $pdo->exec("CREATE INDEX idx_leave_requests_status ON leave_requests (status)");
    } catch (Exception $e) {
        // Index có thể đã tồn tại
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_leave_requests_requester_dept ON leave_requests (requester_id)");
    } catch (Exception $e) {
        // Index có thể đã tồn tại
    }
    
    // Thêm comment cho bảng
    $pdo->exec("ALTER TABLE leave_requests COMMENT = 'Bảng quản lý đơn nghỉ phép với quy trình phê duyệt theo phòng ban'");
    
    echo json_encode([
        'success' => true,
        'message' => 'Database updated successfully!'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error updating database: ' . $e->getMessage()
    ]);
}
?>
