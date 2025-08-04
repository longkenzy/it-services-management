<?php
/**
 * Script tạo bảng leave_requests nếu chưa tồn tại
 */

require_once '../config/db.php';

try {
    // Kiểm tra xem bảng đã tồn tại chưa
    $stmt = $pdo->query("SHOW TABLES LIKE 'leave_requests'");
    if ($stmt->rowCount() > 0) {
        echo "Bảng leave_requests đã tồn tại.\n";
        exit;
    }
    
    // Tạo bảng leave_requests
    $sql = "CREATE TABLE IF NOT EXISTS `leave_requests` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `request_code` varchar(20) NOT NULL COMMENT 'Mã đơn nghỉ phép',
        `requester_id` int(11) NOT NULL COMMENT 'ID người yêu cầu',
        `requester_position` varchar(100) DEFAULT NULL COMMENT 'Chức vụ người yêu cầu',
        `requester_department` varchar(100) DEFAULT NULL COMMENT 'Phòng ban người yêu cầu',
        `requester_office` varchar(100) DEFAULT NULL COMMENT 'Văn phòng người yêu cầu',
        `start_date` datetime NOT NULL COMMENT 'Ngày và giờ bắt đầu nghỉ',
        `end_date` datetime NOT NULL COMMENT 'Ngày và giờ kết thúc nghỉ',
        `return_date` datetime NOT NULL COMMENT 'Ngày và giờ đi làm lại',
        `leave_days` decimal(3,1) NOT NULL COMMENT 'Số ngày nghỉ',
        `leave_type` varchar(50) NOT NULL COMMENT 'Loại nghỉ phép',
        `reason` text NOT NULL COMMENT 'Lý do nghỉ phép',
        `handover_to` int(11) DEFAULT NULL COMMENT 'ID người được bàn giao việc',
        `attachment` varchar(255) DEFAULT NULL COMMENT 'File đính kèm',
        `status` enum('Chờ phê duyệt','Đã phê duyệt','Từ chối') NOT NULL DEFAULT 'Chờ phê duyệt' COMMENT 'Trạng thái phê duyệt',
        `approved_by` int(11) DEFAULT NULL COMMENT 'ID người phê duyệt',
        `approved_at` datetime DEFAULT NULL COMMENT 'Thời gian phê duyệt',
        `approval_notes` text DEFAULT NULL COMMENT 'Ghi chú phê duyệt',
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
        `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật',
        PRIMARY KEY (`id`),
        UNIQUE KEY `request_code` (`request_code`),
        KEY `requester_id` (`requester_id`),
        KEY `handover_to` (`handover_to`),
        KEY `approved_by` (`approved_by`),
        KEY `status` (`status`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng quản lý đơn nghỉ phép'";
    
    $pdo->exec($sql);
    echo "Đã tạo bảng leave_requests thành công.\n";
    
    // Tạo thư mục upload nếu chưa tồn tại
    $upload_dir = '../assets/uploads/leave_attachments/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
        echo "Đã tạo thư mục upload: $upload_dir\n";
    }
    
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
}
?> 