-- Script sửa chữa bảng leave_requests
-- Chạy script này nếu gặp vấn đề với id = 0 và created_at = 0000-00-00

-- 1. Kiểm tra và sửa chữa cấu trúc bảng
ALTER TABLE `leave_requests` 
MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT,
MODIFY COLUMN `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
MODIFY COLUMN `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật';

-- 2. Đảm bảo auto increment hoạt động đúng
ALTER TABLE `leave_requests` AUTO_INCREMENT = 1;

-- 3. Sửa chữa dữ liệu bị lỗi (nếu có)
UPDATE `leave_requests` 
SET `created_at` = NOW() 
WHERE `created_at` = '0000-00-00 00:00:00' OR `created_at` IS NULL;

-- 4. Kiểm tra và sửa chữa các record có id = 0 (nếu có)
-- Lưu ý: Chỉ chạy nếu thực sự cần thiết
-- DELETE FROM `leave_requests` WHERE `id` = 0;

-- 5. Đảm bảo các ràng buộc khóa ngoại
ALTER TABLE `leave_requests` 
ADD CONSTRAINT `leave_requests_ibfk_1` 
FOREIGN KEY (`requester_id`) REFERENCES `staffs` (`id`) ON DELETE CASCADE;

ALTER TABLE `leave_requests` 
ADD CONSTRAINT `leave_requests_ibfk_2` 
FOREIGN KEY (`handover_to`) REFERENCES `staffs` (`id`) ON DELETE SET NULL;

-- 6. Tạo lại index nếu cần
CREATE INDEX IF NOT EXISTS `idx_leave_requests_requester_id` ON `leave_requests` (`requester_id`);
CREATE INDEX IF NOT EXISTS `idx_leave_requests_status` ON `leave_requests` (`status`);
CREATE INDEX IF NOT EXISTS `idx_leave_requests_created_at` ON `leave_requests` (`created_at`);

-- 7. Kiểm tra cài đặt MySQL
-- Đảm bảo strict mode được bật để tránh lỗi dữ liệu
SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

-- 8. Kiểm tra timezone
SET time_zone = '+07:00';

-- 9. Hiển thị thông tin bảng sau khi sửa chữa
SELECT 
    'leave_requests' as table_name,
    COUNT(*) as total_records,
    MIN(id) as min_id,
    MAX(id) as max_id,
    MIN(created_at) as earliest_created,
    MAX(created_at) as latest_created
FROM `leave_requests`; 