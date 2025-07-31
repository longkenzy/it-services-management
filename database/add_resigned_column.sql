-- ===============================================
-- IT CRM Database - Add Resigned Column to Staffs Table
-- File: add_resigned_column.sql
-- Mục đích: Thêm cột resigned để đánh dấu nhân viên đã nghỉ việc
-- ===============================================

-- Sử dụng database it_crm_db
USE it_crm_db;

-- Thêm cột resigned vào bảng staffs
ALTER TABLE staffs 
ADD COLUMN resigned TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Đánh dấu nhân viên đã nghỉ việc: 0 = chưa nghỉ, 1 = đã nghỉ';

-- Tạo index cho cột resigned để tăng tốc độ truy vấn
CREATE INDEX idx_resigned ON staffs(resigned);

-- Hiển thị thông báo thành công
SELECT 'Cột resigned đã được thêm thành công vào bảng staffs!' AS message; 