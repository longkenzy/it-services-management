-- Thêm cột approve_token vào bảng leave_requests
ALTER TABLE `leave_requests` 
ADD COLUMN `approve_token` VARCHAR(32) NULL COMMENT 'Token để duyệt đơn nghỉ phép qua email' AFTER `status`,
ADD INDEX `idx_approve_token` (`approve_token`);

-- Cập nhật comment cho bảng
ALTER TABLE `leave_requests` COMMENT = 'Bảng quản lý đơn nghỉ phép với token duyệt email'; 