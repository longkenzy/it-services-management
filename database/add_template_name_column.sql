-- ===============================================
-- IT CRM Database - Add template_name column to deployment_tasks
-- File: add_template_name_column.sql
-- Mục đích: Thêm cột template_name vào bảng deployment_tasks
-- ===============================================

-- Sử dụng database it_crm_db
USE it_crm_db;

-- Thêm cột template_name
ALTER TABLE deployment_tasks ADD COLUMN template_name VARCHAR(255) COMMENT 'Tên task mẫu';

-- Hiển thị thông báo thành công
SELECT 'Cột template_name đã được thêm vào bảng deployment_tasks!' AS message; 