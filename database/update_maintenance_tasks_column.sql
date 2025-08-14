-- ===============================================
-- IT CRM Database - Update Maintenance Tasks Column
-- File: update_maintenance_tasks_column.sql
-- Mục đích: Đổi tên cột task_code thành task_number để thống nhất với deployment_tasks
-- ===============================================

-- Sử dụng database
USE it_services_management;

-- ===============================================
-- BƯỚC 1: ĐỔI TÊN CỘT TỪ task_code THÀNH task_number
-- ===============================================

-- Đổi tên cột task_code thành task_number
ALTER TABLE maintenance_tasks CHANGE COLUMN task_code task_number VARCHAR(20) NOT NULL COMMENT 'Mã task';

-- ===============================================
-- BƯỚC 2: CẬP NHẬT INDEX
-- ===============================================

-- Xóa index cũ nếu có
DROP INDEX IF EXISTS task_code ON maintenance_tasks;
DROP INDEX IF EXISTS idx_task_code ON maintenance_tasks;

-- Tạo index mới cho task_number
CREATE UNIQUE INDEX idx_task_number ON maintenance_tasks(task_number);

-- ===============================================
-- BƯỚC 3: KIỂM TRA KẾT QUẢ
-- ===============================================

-- Hiển thị cấu trúc bảng sau khi cập nhật
DESCRIBE maintenance_tasks;

-- Hiển thị các index của bảng
SHOW INDEX FROM maintenance_tasks;

-- ===============================================
-- BƯỚC 4: HIỂN THỊ THÔNG BÁO THÀNH CÔNG
-- ===============================================

SELECT 'Cột task_code đã được đổi thành task_number thành công!' AS message;
SELECT 'maintenance_tasks' AS table_name, 'task_number' AS column_name, 'Đã cập nhật' AS status;
