-- Cập nhật bảng deployment_case_tasks để thêm các trường mới
USE it_crm_db;

-- Thêm trường task_description
ALTER TABLE deployment_case_tasks 
ADD COLUMN task_description TEXT COMMENT 'Mô tả chi tiết của task' AFTER template_name;

-- Thêm trường progress_percentage
ALTER TABLE deployment_case_tasks 
ADD COLUMN progress_percentage INT DEFAULT 0 COMMENT 'Tiến độ hoàn thành task (%)' AFTER status;

-- Cập nhật trường start_date và end_date thành DATETIME để lưu cả thời gian
ALTER TABLE deployment_case_tasks 
MODIFY COLUMN start_date DATETIME COMMENT 'Thời gian bắt đầu task';

ALTER TABLE deployment_case_tasks 
MODIFY COLUMN end_date DATETIME COMMENT 'Thời gian kết thúc task';

-- Thêm index để tối ưu hiệu suất
CREATE INDEX idx_deployment_case_tasks_case_status ON deployment_case_tasks (deployment_case_id, status);
CREATE INDEX idx_deployment_case_tasks_assignee ON deployment_case_tasks (assignee_id);

-- Hiển thị thông báo thành công
SELECT 'Bảng deployment_case_tasks đã được cập nhật thành công!' AS message; 