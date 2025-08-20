-- =====================================================
-- FIX AUTO INCREMENT CHO BẢNG MAINTENANCE_TASKS
-- =====================================================
-- File: database/fix_maintenance_tasks_auto_increment.sql
-- Mục đích: Sửa lỗi auto-increment bị reset về 0
-- Tác giả: IT Support Team
-- Ngày tạo: 2024-12-19
-- =====================================================

-- Bước 1: Kiểm tra cấu trúc bảng hiện tại
DESCRIBE maintenance_tasks;

-- Bước 2: Kiểm tra giá trị AUTO_INCREMENT hiện tại
SHOW TABLE STATUS LIKE 'maintenance_tasks';

-- Bước 3: Tìm ID cao nhất trong bảng
SELECT MAX(id) as max_id FROM maintenance_tasks;

-- Bước 4: Sửa cấu trúc cột id (nếu cần)
-- Chạy lệnh này nếu cột id không có auto_increment
-- ALTER TABLE maintenance_tasks MODIFY COLUMN id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- Bước 5: Reset AUTO_INCREMENT về giá trị đúng
-- Thay thế X bằng giá trị (max_id + 1) từ bước 3
-- ALTER TABLE maintenance_tasks AUTO_INCREMENT = X;

-- Bước 6: Kiểm tra lại sau khi sửa
SHOW TABLE STATUS LIKE 'maintenance_tasks';

-- =====================================================
-- HƯỚNG DẪN SỬ DỤNG:
-- =====================================================
-- 1. Chạy các lệnh SELECT để kiểm tra trạng thái hiện tại
-- 2. Nếu cột id không có auto_increment, chạy lệnh ALTER TABLE ở bước 4
-- 3. Tính toán giá trị AUTO_INCREMENT mới = max_id + 1
-- 4. Chạy lệnh ALTER TABLE AUTO_INCREMENT = X với X là giá trị đã tính
-- 5. Kiểm tra lại bằng lệnh SHOW TABLE STATUS
-- =====================================================
