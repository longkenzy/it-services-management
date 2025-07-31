-- Thêm trường request_detail_type vào bảng maintenance_cases
ALTER TABLE maintenance_cases 
ADD COLUMN request_detail_type VARCHAR(200) AFTER request_type;

-- Thêm comment cho trường mới
ALTER TABLE maintenance_cases 
MODIFY COLUMN request_detail_type VARCHAR(200) COMMENT 'Loại yêu cầu chi tiết'; 