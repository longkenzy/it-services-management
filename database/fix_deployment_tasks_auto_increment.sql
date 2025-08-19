-- Script sửa lỗi auto increment cho bảng deployment_tasks
-- Chạy từng lệnh một theo thứ tự

-- 1. Xóa dữ liệu lỗi (ID = 0)
DELETE FROM deployment_tasks WHERE id = 0;

-- 2. Thêm PRIMARY KEY và AUTO_INCREMENT cho cột id
ALTER TABLE deployment_tasks MODIFY COLUMN id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- 3. Sửa dữ liệu timestamp lỗi
UPDATE deployment_tasks SET created_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP 
WHERE created_at = '0000-00-00 00:00:00' OR updated_at = '0000-00-00 00:00:00';

-- 4. Kiểm tra kết quả
SHOW TABLE STATUS LIKE 'deployment_tasks';
SELECT MAX(id) as max_id FROM deployment_tasks;
