-- Kiểm tra cấu trúc bảng maintenance_cases
USE it_services_management;

-- Hiển thị cấu trúc bảng
DESCRIBE maintenance_cases;

-- Hiển thị tất cả các cột
SHOW COLUMNS FROM maintenance_cases;

-- Kiểm tra xem có cột progress nào không
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'it_services_management' 
AND TABLE_NAME = 'maintenance_cases' 
AND COLUMN_NAME = 'progress';

-- Kiểm tra dữ liệu mẫu
SELECT id, case_code, progress, created_at FROM maintenance_cases LIMIT 5;
