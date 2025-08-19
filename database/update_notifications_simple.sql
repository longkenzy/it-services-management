-- Script cập nhật bảng notifications để hỗ trợ loại thông báo internal_case
-- Chạy script này trong phpMyAdmin hoặc MySQL client

-- Kiểm tra xem bảng notifications có tồn tại không
SHOW TABLES LIKE 'notifications';

-- Kiểm tra cấu trúc hiện tại của cột type
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'notifications' 
AND COLUMN_NAME = 'type';

-- Cập nhật ENUM để thêm 'internal_case'
ALTER TABLE notifications 
MODIFY COLUMN type ENUM('leave_request', 'leave_approved', 'leave_rejected', 'internal_case', 'system') DEFAULT 'system';

-- Kiểm tra lại sau khi cập nhật
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'notifications' 
AND COLUMN_NAME = 'type';
