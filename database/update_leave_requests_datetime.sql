-- Cập nhật bảng leave_requests để sử dụng datetime thay vì date
-- Thêm các trường datetime mới
ALTER TABLE `leave_requests` 
ADD COLUMN `start_datetime` datetime DEFAULT NULL COMMENT 'Ngày và giờ bắt đầu nghỉ' AFTER `start_date`,
ADD COLUMN `end_datetime` datetime DEFAULT NULL COMMENT 'Ngày và giờ kết thúc nghỉ' AFTER `end_date`,
ADD COLUMN `return_datetime` datetime DEFAULT NULL COMMENT 'Ngày và giờ đi làm lại' AFTER `return_date`;

-- Cập nhật dữ liệu hiện có (nếu có)
UPDATE `leave_requests` SET 
    `start_datetime` = CONCAT(`start_date`, ' 08:00:00'),
    `end_datetime` = CONCAT(`end_date`, ' 17:00:00'),
    `return_datetime` = CONCAT(`return_date`, ' 08:00:00')
WHERE `start_datetime` IS NULL;

-- Xóa các trường date cũ
ALTER TABLE `leave_requests` 
DROP COLUMN `start_date`,
DROP COLUMN `end_date`,
DROP COLUMN `return_date`;

-- Đổi tên các trường datetime
ALTER TABLE `leave_requests` 
CHANGE `start_datetime` `start_date` datetime NOT NULL COMMENT 'Ngày và giờ bắt đầu nghỉ',
CHANGE `end_datetime` `end_date` datetime NOT NULL COMMENT 'Ngày và giờ kết thúc nghỉ',
CHANGE `return_datetime` `return_date` datetime NOT NULL COMMENT 'Ngày và giờ đi làm lại'; 