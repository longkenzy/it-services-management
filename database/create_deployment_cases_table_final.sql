-- Tạo bảng deployment_cases với cấu trúc đúng theo yêu cầu
CREATE TABLE IF NOT EXISTS `deployment_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_code` varchar(20) NOT NULL COMMENT 'Mã case (CTK2507001)',
  `deployment_request_id` int(11) NOT NULL COMMENT 'ID yêu cầu triển khai',
  `request_type` varchar(100) NOT NULL COMMENT 'Loại yêu cầu',
  `progress` varchar(50) DEFAULT NULL COMMENT 'Tiến trình',
  `case_description` text DEFAULT NULL COMMENT 'Mô tả case',
  `notes` text DEFAULT NULL COMMENT 'Ghi chú',
  `assigned_to` int(11) NOT NULL COMMENT 'ID người phụ trách',
  `work_type` varchar(20) DEFAULT NULL COMMENT 'Hình thức: Onsite, Offsite, Remote',
  `start_date` date DEFAULT NULL COMMENT 'Ngày bắt đầu',
  `end_date` date DEFAULT NULL COMMENT 'Ngày kết thúc',
  `status` varchar(20) NOT NULL DEFAULT 'Tiếp nhận' COMMENT 'Trạng thái: Tiếp nhận, Đang xử lý, Hoàn thành, Huỷ',
  `total_tasks` int(11) DEFAULT 0 COMMENT 'Tổng số task',
  `completed_tasks` int(11) DEFAULT 0 COMMENT 'Số task hoàn thành',
  `progress_percentage` int(3) DEFAULT 0 COMMENT 'Tiến độ (%)',
  `created_by` int(11) NOT NULL COMMENT 'ID người tạo',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `case_code` (`case_code`),
  KEY `deployment_request_id` (`deployment_request_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `created_by` (`created_by`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_deployment_cases_request` FOREIGN KEY (`deployment_request_id`) REFERENCES `deployment_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_deployment_cases_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `staffs` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_deployment_cases_created` FOREIGN KEY (`created_by`) REFERENCES `staffs` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng quản lý case triển khai';

-- Thêm index để tối ưu hiệu suất
CREATE INDEX `idx_deployment_cases_request_status` ON `deployment_cases` (`deployment_request_id`, `status`);
CREATE INDEX `idx_deployment_cases_assigned_status` ON `deployment_cases` (`assigned_to`, `status`);
CREATE INDEX `idx_deployment_cases_created_date` ON `deployment_cases` (`created_by`, `created_at`); 