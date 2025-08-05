-- ===============================================
-- IT CRM Database - Update Role Column
-- File: database/update_role_column.sql
-- Mục đích: Cập nhật cột role để hỗ trợ các vai trò mới
-- ===============================================

-- Sử dụng database it_crm_db
USE thichho1_it_crm_db;

-- Cập nhật cột role để hỗ trợ các vai trò mới
ALTER TABLE staffs MODIFY COLUMN role ENUM('user', 'admin', 'hr', 'sale', 'it', 'leader') NOT NULL DEFAULT 'user' COMMENT 'Vai trò trong hệ thống';

-- Hiển thị thông báo thành công
SELECT 'Cột role đã được cập nhật thành công!' AS message; 