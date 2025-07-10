-- ===============================================
-- IT CRM Database Setup Script
-- File: create_database.sql
-- Mục đích: Tạo database và bảng users cho hệ thống CRM
-- ===============================================

-- Tạo database it_crm_db nếu chưa tồn tại
CREATE DATABASE IF NOT EXISTS it_crm_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Sử dụng database vừa tạo
USE it_crm_db;

-- Tạo bảng users để lưu thông tin đăng nhập
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT 'ID tự tăng, khóa chính',
    username VARCHAR(100) NOT NULL UNIQUE COMMENT 'Tên đăng nhập, không trùng lặp',
    password VARCHAR(255) NOT NULL COMMENT 'Mật khẩu đã mã hóa bằng password_hash',
    fullname VARCHAR(100) NOT NULL COMMENT 'Họ và tên đầy đủ',
    role VARCHAR(50) NOT NULL DEFAULT 'user' COMMENT 'Vai trò: admin, leader, user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo tài khoản',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật cuối'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Bảng lưu thông tin người dùng';

-- Tạo index cho cột username để tăng tốc độ truy vấn
CREATE INDEX idx_username ON users(username);

-- Tạo index cho cột role để tăng tốc độ truy vấn theo vai trò
CREATE INDEX idx_role ON users(role);

-- Hiển thị thông báo thành công
SELECT 'Database và bảng users đã được tạo thành công!' AS message; 