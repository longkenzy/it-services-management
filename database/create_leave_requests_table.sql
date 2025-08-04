-- Tạo bảng leave_requests để quản lý đơn nghỉ phép
CREATE TABLE IF NOT EXISTS `leave_requests` (
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
  KEY `created_at` (`created_at`),
  CONSTRAINT `leave_requests_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `staffs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_requests_ibfk_2` FOREIGN KEY (`handover_to`) REFERENCES `staffs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `leave_requests_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `staffs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng quản lý đơn nghỉ phép';

-- Thêm dữ liệu mẫu (tùy chọn)
INSERT INTO `leave_requests` (`request_code`, `requester_id`, `requester_position`, `requester_department`, `requester_office`, `start_date`, `end_date`, `return_date`, `leave_days`, `leave_type`, `reason`, `handover_to`, `status`, `created_at`) VALUES
('NP2508001', 1, 'Nhân viên', 'IT', 'Hà Nội', '2024-01-15 08:00:00', '2024-01-17 17:00:00', '2024-01-18 08:00:00', 3.0, 'Nghỉ phép năm', 'Nghỉ phép năm để đi du lịch cùng gia đình', 2, 'Chờ phê duyệt', NOW()),
('NP2508002', 2, 'Trưởng nhóm', 'HR', 'TP.HCM', '2024-01-20 08:00:00', '2024-01-20 17:00:00', '2024-01-21 08:00:00', 1.0, 'Nghỉ ốm', 'Bị cảm cúm, cần nghỉ để điều trị', 3, 'Đã phê duyệt', NOW()),
('NP2508003', 3, 'Quản lý', 'Sales', 'Đà Nẵng', '2024-01-25 13:00:00', '2024-01-25 17:00:00', '2024-01-26 08:00:00', 0.5, 'Nghỉ việc riêng', 'Có việc riêng cần xử lý buổi chiều', 1, 'Từ chối', NOW()); 