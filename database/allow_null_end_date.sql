-- ===============================================
-- IT CRM Database - Allow NULL for date fields in deployment_tasks
-- File: allow_null_end_date.sql
-- Mục đích: Cho phép cột start_date và end_date có thể NULL
-- ===============================================

-- Sử dụng database it_crm_db
USE it_crm_db;

-- Sửa cột start_date để cho phép NULL
ALTER TABLE deployment_tasks MODIFY COLUMN start_date DATETIME NULL COMMENT 'Thời gian bắt đầu task';

-- Sửa cột end_date để cho phép NULL
ALTER TABLE deployment_tasks MODIFY COLUMN end_date DATETIME NULL COMMENT 'Thời gian kết thúc task';

-- Hiển thị thông báo thành công
SELECT 'Các cột start_date và end_date trong bảng deployment_tasks đã được sửa để cho phép NULL!' AS message; 