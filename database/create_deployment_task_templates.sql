-- ===============================================
-- IT CRM Database - Deployment Task Templates Table Setup
-- File: create_deployment_task_templates.sql
-- Mục đích: Tạo bảng deployment_task_templates để lưu các task mẫu
-- ===============================================

-- Sử dụng database it_crm_db
USE it_crm_db;

-- Tạo bảng deployment_task_templates
CREATE TABLE IF NOT EXISTS deployment_task_templates (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID tự tăng, khóa chính',
    template_name VARCHAR(255) NOT NULL COMMENT 'Tên task mẫu',
    task_type ENUM('onsite', 'offsite', 'remote') NOT NULL DEFAULT 'onsite' COMMENT 'Loại task',
    task_description TEXT COMMENT 'Mô tả task',
    estimated_duration INT DEFAULT 0 COMMENT 'Thời gian ước tính (phút)',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Trạng thái hoạt động',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật cuối'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng lưu các task mẫu cho deployment';

-- Tạo index
CREATE INDEX idx_template_name ON deployment_task_templates(template_name);
CREATE INDEX idx_task_type ON deployment_task_templates(task_type);
CREATE INDEX idx_is_active ON deployment_task_templates(is_active);

-- Thêm dữ liệu mẫu
INSERT INTO deployment_task_templates (template_name, task_type, task_description, estimated_duration) VALUES
('Cài đặt thiết bị', 'onsite', 'Cài đặt và cấu hình thiết bị mới theo yêu cầu', 120),
('Cấu hình phần mềm', 'offsite', 'Cấu hình phần mềm và hệ thống', 90),
('Kiểm tra hệ thống', 'onsite', 'Kiểm tra và đánh giá hệ thống hiện tại', 60),
('Đào tạo người dùng', 'onsite', 'Đào tạo người dùng sử dụng hệ thống', 180),
('Bảo trì định kỳ', 'onsite', 'Bảo trì và bảo dưỡng hệ thống định kỳ', 120),
('Khắc phục sự cố', 'onsite', 'Khắc phục các sự cố hệ thống', 240),
('Nghiệm thu dự án', 'onsite', 'Nghiệm thu và bàn giao dự án', 90);

-- Hiển thị thông báo thành công
SELECT 'Bảng deployment_task_templates đã được tạo thành công!' AS message;
