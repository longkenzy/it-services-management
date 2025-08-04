-- Thêm cột featured_image vào bảng blog_posts
-- Chạy câu lệnh này nếu bảng blog_posts đã tồn tại

ALTER TABLE `blog_posts` 
ADD COLUMN `featured_image` varchar(500) DEFAULT NULL 
AFTER `summary`;

-- Kiểm tra kết quả
DESCRIBE `blog_posts`; 