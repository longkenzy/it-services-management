-- ===============================================
-- IT CRM Database - Recreate Maintenance Tables
-- File: recreate_maintenance_tables.sql
-- Mục đích: Xóa các bảng maintenance cũ và tạo lại với cấu trúc giống deployment
-- ===============================================

-- Sử dụng database
USE thichho1_it_crm_db;

-- ===============================================
-- BƯỚC 1: XÓA CÁC BẢNG MAINTENANCE CŨ
-- ===============================================

-- Xóa foreign key constraints trước
SET FOREIGN_KEY_CHECKS = 0;

-- Xóa các bảng maintenance cũ
DROP TABLE IF EXISTS maintenance_tasks;
DROP TABLE IF EXISTS maintenance_cases;
DROP TABLE IF EXISTS maintenance_requests;
DROP TABLE IF EXISTS maintenance_case_types;

-- Bật lại foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ===============================================
-- BƯỚC 2: TẠO BẢNG MAINTENANCE_REQUESTS MỚI
-- ===============================================

CREATE TABLE IF NOT EXISTS `maintenance_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_code` varchar(20) NOT NULL COMMENT 'Mã yêu cầu bảo trì',
  `po_number` varchar(100) DEFAULT NULL COMMENT 'Số PO',
  `no_contract_po` tinyint(1) DEFAULT 0 COMMENT 'Không có hợp đồng PO',
  `contract_type` varchar(100) DEFAULT NULL COMMENT 'Loại hợp đồng',
  `request_detail_type` varchar(100) DEFAULT NULL COMMENT 'Loại chi tiết yêu cầu',
  `email_subject_customer` text DEFAULT NULL COMMENT 'Tiêu đề email khách hàng',
  `email_subject_internal` text DEFAULT NULL COMMENT 'Tiêu đề email nội bộ',
  `expected_start` date DEFAULT NULL COMMENT 'Ngày bắt đầu dự kiến',
  `expected_end` date DEFAULT NULL COMMENT 'Ngày kết thúc dự kiến',
  `customer_id` int(11) DEFAULT NULL COMMENT 'ID khách hàng',
  `contact_person` varchar(100) DEFAULT NULL COMMENT 'Người liên hệ',
  `contact_phone` varchar(20) DEFAULT NULL COMMENT 'Số điện thoại liên hệ',
  `sale_id` int(11) DEFAULT NULL COMMENT 'ID sale phụ trách',
  `requester_notes` text DEFAULT NULL COMMENT 'Ghi chú người yêu cầu',
  `maintenance_manager` varchar(100) DEFAULT NULL COMMENT 'Quản lý bảo trì',
  `maintenance_status` varchar(20) NOT NULL DEFAULT 'Tiếp nhận' COMMENT 'Trạng thái bảo trì',
  `created_by` int(11) DEFAULT NULL COMMENT 'ID người tạo',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_code` (`request_code`),
  KEY `customer_id` (`customer_id`),
  KEY `sale_id` (`sale_id`),
  KEY `created_by` (`created_by`),
  KEY `maintenance_status` (`maintenance_status`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_maintenance_requests_customer` FOREIGN KEY (`customer_id`) REFERENCES `partner_companies` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_maintenance_requests_sale` FOREIGN KEY (`sale_id`) REFERENCES `staffs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_maintenance_requests_created` FOREIGN KEY (`created_by`) REFERENCES `staffs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng quản lý yêu cầu bảo trì';

-- ===============================================
-- BƯỚC 3: TẠO BẢNG MAINTENANCE_CASES MỚI
-- ===============================================

CREATE TABLE IF NOT EXISTS `maintenance_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_code` varchar(20) NOT NULL COMMENT 'Mã case (BTR2507001)',
  `maintenance_request_id` int(11) NOT NULL COMMENT 'ID yêu cầu bảo trì',
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
  KEY `maintenance_request_id` (`maintenance_request_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `created_by` (`created_by`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_maintenance_cases_request` FOREIGN KEY (`maintenance_request_id`) REFERENCES `maintenance_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_maintenance_cases_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `staffs` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_maintenance_cases_created` FOREIGN KEY (`created_by`) REFERENCES `staffs` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng quản lý case bảo trì';

-- ===============================================
-- BƯỚC 4: TẠO BẢNG MAINTENANCE_TASKS MỚI
-- ===============================================

CREATE TABLE IF NOT EXISTS `maintenance_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_number` varchar(20) NOT NULL COMMENT 'Mã task',
  `maintenance_case_id` int(11) NOT NULL COMMENT 'ID case bảo trì',
  `maintenance_request_id` int(11) DEFAULT NULL COMMENT 'ID yêu cầu bảo trì',
  `task_name` varchar(255) NOT NULL COMMENT 'Tên task',
  `task_description` text DEFAULT NULL COMMENT 'Mô tả task',
  `task_type` varchar(50) DEFAULT NULL COMMENT 'Loại task',
  `priority` varchar(20) DEFAULT 'Trung bình' COMMENT 'Độ ưu tiên: Cao, Trung bình, Thấp',
  `assigned_to` int(11) DEFAULT NULL COMMENT 'ID người được giao',
  `start_date` datetime DEFAULT NULL COMMENT 'Thời gian bắt đầu',
  `end_date` datetime DEFAULT NULL COMMENT 'Thời gian kết thúc',
  `estimated_hours` decimal(5,2) DEFAULT 0.00 COMMENT 'Số giờ ước tính',
  `actual_hours` decimal(5,2) DEFAULT 0.00 COMMENT 'Số giờ thực tế',
  `status` varchar(20) NOT NULL DEFAULT 'Chờ xử lý' COMMENT 'Trạng thái: Chờ xử lý, Đang xử lý, Hoàn thành, Huỷ',
  `progress_percentage` int(3) DEFAULT 0 COMMENT 'Tiến độ hoàn thành (%)',
  `notes` text DEFAULT NULL COMMENT 'Ghi chú',
  `created_by` int(11) NOT NULL COMMENT 'ID người tạo',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_number` (`task_number`),
  KEY `maintenance_case_id` (`maintenance_case_id`),
  KEY `maintenance_request_id` (`maintenance_request_id`),
  KEY `assigned_to` (`assigned_to`),
  KEY `created_by` (`created_by`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_maintenance_tasks_case` FOREIGN KEY (`maintenance_case_id`) REFERENCES `maintenance_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_maintenance_tasks_request` FOREIGN KEY (`maintenance_request_id`) REFERENCES `maintenance_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_maintenance_tasks_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `staffs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_maintenance_tasks_created` FOREIGN KEY (`created_by`) REFERENCES `staffs` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng quản lý task bảo trì';

-- ===============================================
-- BƯỚC 5: TẠO BẢNG MAINTENANCE_CASE_TYPES MỚI
-- ===============================================

CREATE TABLE IF NOT EXISTS `maintenance_case_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(100) NOT NULL COMMENT 'Tên loại case bảo trì',
  `type_code` varchar(20) NOT NULL COMMENT 'Mã loại case',
  `description` text DEFAULT NULL COMMENT 'Mô tả loại case',
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'Trạng thái hoạt động',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật',
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_code` (`type_code`),
  KEY `is_active` (`is_active`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng quản lý loại case bảo trì';

-- ===============================================
-- BƯỚC 6: TẠO CÁC INDEX BỔ SUNG
-- ===============================================

-- Index cho maintenance_requests
CREATE INDEX `idx_maintenance_requests_customer_status` ON `maintenance_requests` (`customer_id`, `maintenance_status`);
CREATE INDEX `idx_maintenance_requests_sale_status` ON `maintenance_requests` (`sale_id`, `maintenance_status`);
CREATE INDEX `idx_maintenance_requests_created_date` ON `maintenance_requests` (`created_by`, `created_at`);

-- Index cho maintenance_cases
CREATE INDEX `idx_maintenance_cases_request_status` ON `maintenance_cases` (`maintenance_request_id`, `status`);
CREATE INDEX `idx_maintenance_cases_assigned_status` ON `maintenance_cases` (`assigned_to`, `status`);
CREATE INDEX `idx_maintenance_cases_created_date` ON `maintenance_cases` (`created_by`, `created_at`);

-- Index cho maintenance_tasks
CREATE INDEX `idx_maintenance_tasks_case_status` ON `maintenance_tasks` (`maintenance_case_id`, `status`);
CREATE INDEX `idx_maintenance_tasks_request_status` ON `maintenance_tasks` (`maintenance_request_id`, `status`);
CREATE INDEX `idx_maintenance_tasks_assigned_status` ON `maintenance_tasks` (`assigned_to`, `status`);
CREATE INDEX `idx_maintenance_tasks_created_date` ON `maintenance_tasks` (`created_by`, `created_at`);

-- ===============================================
-- BƯỚC 7: THÊM DỮ LIỆU MẪU CHO MAINTENANCE_CASE_TYPES
-- ===============================================

INSERT INTO `maintenance_case_types` (`type_name`, `type_code`, `description`, `is_active`) VALUES
('Bảo trì định kỳ', 'BTR_DK', 'Bảo trì theo lịch định kỳ', 1),
('Bảo trì sự cố', 'BTR_SC', 'Bảo trì khi có sự cố xảy ra', 1),
('Bảo trì nâng cấp', 'BTR_NC', 'Bảo trì và nâng cấp hệ thống', 1),
('Bảo trì khắc phục', 'BTR_KP', 'Bảo trì khắc phục lỗi', 1),
('Bảo trì tối ưu', 'BTR_TO', 'Bảo trì tối ưu hiệu suất', 1);

-- ===============================================
-- BƯỚC 8: HIỂN THỊ THÔNG BÁO THÀNH CÔNG
-- ===============================================

SELECT 'Các bảng maintenance đã được tạo lại thành công!' AS message;
SELECT 'maintenance_requests' AS table_name, 'Đã tạo' AS status
UNION ALL
SELECT 'maintenance_cases' AS table_name, 'Đã tạo' AS status
UNION ALL
SELECT 'maintenance_tasks' AS table_name, 'Đã tạo' AS status
UNION ALL
SELECT 'maintenance_case_types' AS table_name, 'Đã tạo' AS status;
