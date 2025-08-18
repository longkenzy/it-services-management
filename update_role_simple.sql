-- Cách đơn giản để cập nhật ENUM role
-- Chạy từng câu lệnh một cách cẩn thận

-- Bước 1: Kiểm tra cấu trúc hiện tại
DESCRIBE staffs;

-- Bước 2: Cập nhật ENUM role (cách an toàn)
ALTER TABLE staffs MODIFY COLUMN role ENUM('user', 'admin', 'hr', 'sale', 'it', 'leader', 'hr leader', 'sale leader', 'it leader') NOT NULL DEFAULT 'user';

-- Bước 3: Kiểm tra kết quả
DESCRIBE staffs;
SELECT DISTINCT role FROM staffs;

-- Bước 4: Nếu cần, cập nhật một số user thành role 'it'
-- UPDATE staffs SET role = 'it' WHERE id IN (1, 2, 3); -- Thay đổi ID theo nhu cầu
