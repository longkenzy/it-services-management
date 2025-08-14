-- ===============================================
-- IT CRM Database - Update Maintenance Tasks to Match Deployment Tasks
-- File: update_maintenance_tasks_to_match_deployment.sql
-- Mục đích: Sửa cấu trúc bảng maintenance_tasks để giống với deployment_tasks
-- ===============================================

-- Sử dụng database
USE it_services_management;

-- ===============================================
-- BƯỚC 1: BACKUP DỮ LIỆU HIỆN TẠI
-- ===============================================

-- Tạo bảng backup
CREATE TABLE IF NOT EXISTS maintenance_tasks_backup AS SELECT * FROM maintenance_tasks;

-- ===============================================
-- BƯỚC 2: XÓA CÁC CỘT KHÔNG CẦN THIẾT
-- ===============================================

-- Xóa các cột có trong maintenance_tasks nhưng không có trong deployment_tasks
ALTER TABLE maintenance_tasks DROP COLUMN IF EXISTS task_name;
ALTER TABLE maintenance_tasks DROP COLUMN IF EXISTS priority;
ALTER TABLE maintenance_tasks DROP COLUMN IF EXISTS estimated_hours;
ALTER TABLE maintenance_tasks DROP COLUMN IF EXISTS actual_hours;
ALTER TABLE maintenance_tasks DROP COLUMN IF EXISTS progress_percentage;
ALTER TABLE maintenance_tasks DROP COLUMN IF EXISTS notes;
ALTER TABLE maintenance_tasks DROP COLUMN IF EXISTS created_by;

-- ===============================================
-- BƯỚC 3: THÊM CÁC CỘT THIẾU
-- ===============================================

-- Thêm cột template_name (nếu chưa có)
ALTER TABLE maintenance_tasks ADD COLUMN IF NOT EXISTS template_name VARCHAR(255) COMMENT 'Tên task mẫu';

-- ===============================================
-- BƯỚC 4: SỬA KIỂU DỮ LIỆU VÀ CONSTRAINT
-- ===============================================

-- Sửa kiểu dữ liệu task_number từ VARCHAR(20) thành VARCHAR(50)
ALTER TABLE maintenance_tasks MODIFY COLUMN task_number VARCHAR(50) NOT NULL COMMENT 'Mã task';

-- Sửa kiểu dữ liệu task_type từ VARCHAR(50) thành VARCHAR(50) NOT NULL
ALTER TABLE maintenance_tasks MODIFY COLUMN task_type VARCHAR(50) NOT NULL COMMENT 'Loại task';

-- Sửa kiểu dữ liệu task_description từ TEXT thành TEXT NOT NULL
ALTER TABLE maintenance_tasks MODIFY COLUMN task_description TEXT NOT NULL COMMENT 'Mô tả task';

-- Sửa kiểu dữ liệu start_date từ DATETIME thành DATETIME NOT NULL
ALTER TABLE maintenance_tasks MODIFY COLUMN start_date DATETIME NOT NULL COMMENT 'Thời gian bắt đầu';

-- Sửa kiểu dữ liệu end_date từ DATETIME thành DATETIME (có thể NULL)
ALTER TABLE maintenance_tasks MODIFY COLUMN end_date DATETIME NULL COMMENT 'Thời gian kết thúc';

-- Sửa kiểu dữ liệu status từ VARCHAR(20) NOT NULL thành VARCHAR(20) với default 'pending'
ALTER TABLE maintenance_tasks MODIFY COLUMN status VARCHAR(20) DEFAULT 'pending' COMMENT 'Trạng thái task';

-- Sửa kiểu dữ liệu created_at và updated_at
ALTER TABLE maintenance_tasks MODIFY COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE maintenance_tasks MODIFY COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- ===============================================
-- BƯỚC 5: CẬP NHẬT INDEX VÀ CONSTRAINT
-- ===============================================

-- Xóa các index cũ không cần thiết
DROP INDEX IF EXISTS idx_maintenance_case_id ON maintenance_tasks;
DROP INDEX IF EXISTS idx_maintenance_request_id ON maintenance_tasks;
DROP INDEX IF EXISTS idx_created_by ON maintenance_tasks;
DROP INDEX IF EXISTS idx_status ON maintenance_tasks;
DROP INDEX IF EXISTS idx_created_at ON maintenance_tasks;

-- Tạo index mới giống deployment_tasks
CREATE INDEX idx_maintenance_case_id ON maintenance_tasks(maintenance_case_id);
CREATE INDEX idx_maintenance_request_id ON maintenance_tasks(maintenance_request_id);
CREATE INDEX idx_assignee_id ON maintenance_tasks(assignee_id);
CREATE INDEX idx_status ON maintenance_tasks(status);

-- ===============================================
-- BƯỚC 6: KIỂM TRA KẾT QUẢ
-- ===============================================

-- Hiển thị cấu trúc bảng sau khi cập nhật
DESCRIBE maintenance_tasks;

-- Hiển thị các index của bảng
SHOW INDEX FROM maintenance_tasks;

-- ===============================================
-- BƯỚC 7: HIỂN THỊ THÔNG BÁO THÀNH CÔNG
-- ===============================================

SELECT 'Bảng maintenance_tasks đã được cập nhật để giống với deployment_tasks!' AS message;
SELECT 'Các thay đổi chính:' AS info;
SELECT '- Đã xóa các cột: task_name, priority, estimated_hours, actual_hours, progress_percentage, notes, created_by' AS change;
SELECT '- Đã thêm cột: template_name' AS change;
SELECT '- Đã sửa kiểu dữ liệu các cột để giống deployment_tasks' AS change;
SELECT '- Đã cập nhật index và constraint' AS change;
