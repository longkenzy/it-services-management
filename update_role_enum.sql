-- Cập nhật ENUM role trong bảng staffs để thêm giá trị 'it'
-- Chạy câu lệnh này trên database hosting thật

-- Bước 1: Tạo bảng tạm với ENUM mới
CREATE TABLE staffs_temp LIKE staffs;

-- Bước 2: Cập nhật cột role trong bảng tạm
ALTER TABLE staffs_temp MODIFY COLUMN role ENUM('user', 'admin', 'hr', 'sale', 'it', 'leader', 'hr leader', 'sale leader', 'it leader') NOT NULL DEFAULT 'user';

-- Bước 3: Copy dữ liệu từ bảng cũ sang bảng tạm
INSERT INTO staffs_temp SELECT * FROM staffs;

-- Bước 4: Xóa bảng cũ và đổi tên bảng tạm
DROP TABLE staffs;
RENAME TABLE staffs_temp TO staffs;

-- Bước 5: Kiểm tra kết quả
DESCRIBE staffs;
SELECT DISTINCT role FROM staffs;
