-- Sửa lại cấu trúc bảng deployment_requests trên hosting
-- Chạy các lệnh SQL này trên hosting để khắc phục lỗi id = 0 và created_at = 0000-00-00

-- 1. Thêm AUTO_INCREMENT cho cột id
ALTER TABLE deployment_requests MODIFY COLUMN id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- 2. Sửa lại cột created_at để có default CURRENT_TIMESTAMP
ALTER TABLE deployment_requests MODIFY COLUMN created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- 3. Sửa lại cột updated_at để có default CURRENT_TIMESTAMP và ON UPDATE
ALTER TABLE deployment_requests MODIFY COLUMN updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 4. Kiểm tra lại cấu trúc bảng
DESCRIBE deployment_requests;

-- 5. Kiểm tra AUTO_INCREMENT hiện tại
SHOW TABLE STATUS LIKE 'deployment_requests'; 