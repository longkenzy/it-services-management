-- ===============================================
-- IT CRM Database - Remove progress_percentage column from deployment_tasks
-- File: remove_progress_percentage_column.sql
-- Mục đích: Xóa cột progress_percentage khỏi bảng deployment_tasks
-- ===============================================

-- Sử dụng database it_crm_db
USE it_crm_db;

-- Xóa cột progress_percentage
ALTER TABLE deployment_tasks DROP COLUMN IF EXISTS progress_percentage;

-- Hiển thị thông báo thành công
SELECT 'Cột progress_percentage đã được xóa khỏi bảng deployment_tasks!' AS message; 