-- ===============================================
-- IT CRM Database - Staffs Table Setup
-- File: create_staffs_table.sql
-- Mục đích: Tạo bảng staffs để quản lý thông tin nhân sự
-- ===============================================

-- Sử dụng database it_crm_db
USE it_crm_db;

-- Tạo bảng staffs để lưu thông tin nhân sự
CREATE TABLE IF NOT EXISTS staffs (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID tự tăng, khóa chính',
    employee_code VARCHAR(20) NOT NULL UNIQUE COMMENT 'Mã số nhân viên, không trùng lặp',
    fullname VARCHAR(100) NOT NULL COMMENT 'Họ và tên đầy đủ',
    username VARCHAR(50) NOT NULL UNIQUE COMMENT 'Username đăng nhập',
    birth_year INT NOT NULL COMMENT 'Năm sinh',
    gender ENUM('Nam', 'Nữ', 'Khác') NOT NULL DEFAULT 'Nam' COMMENT 'Giới tính',
    avatar VARCHAR(255) DEFAULT NULL COMMENT 'Đường dẫn ảnh đại diện',
    position VARCHAR(100) NOT NULL COMMENT 'Chức vụ',
    department VARCHAR(100) NOT NULL COMMENT 'Phòng ban',
    office VARCHAR(100) NOT NULL COMMENT 'Văn phòng làm việc',
    phone VARCHAR(20) NOT NULL COMMENT 'Số điện thoại chính',
    email VARCHAR(100) NOT NULL COMMENT 'Email công việc',
    contract_type ENUM('Chính thức', 'Thử việc', 'Tạm thời', 'Thực tập') NOT NULL DEFAULT 'Chính thức' COMMENT 'Loại hợp đồng',
    seniority INT NOT NULL DEFAULT 0 COMMENT 'Thâm niên (tính theo năm)',
    status ENUM('Đang làm việc', 'Nghỉ phép', 'Đã nghỉ việc') NOT NULL DEFAULT 'Đang làm việc' COMMENT 'Trạng thái làm việc',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật cuối'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng lưu thông tin nhân sự';

-- Tạo các index để tăng tốc độ truy vấn
CREATE INDEX idx_employee_code ON staffs(employee_code);
CREATE INDEX idx_fullname ON staffs(fullname);
CREATE INDEX idx_username ON staffs(username);
CREATE INDEX idx_department ON staffs(department);
CREATE INDEX idx_position ON staffs(position);
CREATE INDEX idx_status ON staffs(status);

-- Hiển thị thông báo thành công
SELECT 'Bảng staffs đã được tạo thành công!' AS message; 