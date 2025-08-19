-- Script sửa lỗi auto increment cho bảng deployment_cases
-- Chạy script này trên hosting để khắc phục lỗi id = 0

-- 1. Kiểm tra cấu trúc bảng hiện tại
DESCRIBE deployment_cases;

-- 2. Kiểm tra dữ liệu hiện có
SELECT id, case_code, created_at FROM deployment_cases ORDER BY id ASC LIMIT 10;

-- 3. Kiểm tra auto increment hiện tại
SHOW TABLE STATUS LIKE 'deployment_cases';

-- 4. Tìm ID cao nhất
SELECT MAX(id) as max_id FROM deployment_cases;

-- 5. Sửa auto increment nếu cần (thay thế X bằng ID cao nhất + 1)
-- Ví dụ: nếu ID cao nhất là 5, thì set AUTO_INCREMENT = 6
-- ALTER TABLE deployment_cases AUTO_INCREMENT = X;

-- 6. Kiểm tra lại cấu trúc cột id
SHOW CREATE TABLE deployment_cases;

-- 7. Nếu cột id không có AUTO_INCREMENT, sửa lại
-- ALTER TABLE deployment_cases MODIFY COLUMN id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- 8. Kiểm tra lại auto increment sau khi sửa
SHOW TABLE STATUS LIKE 'deployment_cases';

-- 9. Test insert để kiểm tra (chỉ chạy nếu có dữ liệu test)
-- INSERT INTO deployment_cases (case_code, deployment_request_id, request_type, assigned_to, status, created_by) 
-- VALUES ('TEST_20250101', 1, 'Test Request Type', 1, 'Tiếp nhận', 1);

-- 10. Xóa record test nếu đã tạo
-- DELETE FROM deployment_cases WHERE case_code LIKE 'TEST_%';
