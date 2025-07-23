-- Script cập nhật bảng deployment_cases để thêm giờ cho các trường ngày tháng
-- Chạy script này để thay đổi kiểu dữ liệu từ date sang datetime

USE it_crm_db;

-- Thay đổi kiểu dữ liệu của start_date từ date sang datetime
ALTER TABLE deployment_cases 
MODIFY COLUMN start_date datetime DEFAULT NULL COMMENT 'Ngày giờ bắt đầu';

-- Thay đổi kiểu dữ liệu của end_date từ date sang datetime  
ALTER TABLE deployment_cases 
MODIFY COLUMN end_date datetime DEFAULT NULL COMMENT 'Ngày giờ kết thúc';

-- Hiển thị thông báo thành công
SELECT 'Đã cập nhật thành công: start_date và end_date từ date sang datetime' AS message; 