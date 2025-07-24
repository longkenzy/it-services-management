-- ===============================================
-- IT CRM Database - Update Deployment Tasks Table
-- File: update_deployment_tasks_table.sql
-- Mục đích: Xóa cột template_id và thêm cột deployment_request_id
-- ===============================================

-- Sử dụng database it_crm_db
USE it_crm_db;

-- Xóa cột template_id (nếu tồn tại)
ALTER TABLE deployment_tasks DROP COLUMN IF EXISTS template_id;

-- Thêm cột deployment_request_id
ALTER TABLE deployment_tasks ADD COLUMN deployment_request_id INT COMMENT 'ID của deployment request';

-- Thêm foreign key constraint (nếu cần)
-- ALTER TABLE deployment_tasks ADD CONSTRAINT fk_deployment_tasks_request 
-- FOREIGN KEY (deployment_request_id) REFERENCES deployment_requests(id);

-- Tạo index cho cột mới
CREATE INDEX idx_deployment_request_id ON deployment_tasks(deployment_request_id);

-- Hiển thị thông báo thành công
SELECT 'Bảng deployment_tasks đã được cập nhật thành công!' AS message; 