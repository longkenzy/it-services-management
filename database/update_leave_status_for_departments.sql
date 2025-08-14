-- ===============================================
-- IT CRM Database - Update Leave Status for Departments
-- File: database/update_leave_status_for_departments.sql
-- Mục đích: Cập nhật trạng thái đơn nghỉ phép để hỗ trợ phòng ban
-- ===============================================

-- Sử dụng database
USE thichho1_it_crm_db;

-- Cập nhật cột status để hỗ trợ trạng thái mới theo phòng ban
ALTER TABLE leave_requests MODIFY COLUMN status ENUM(
    'Chờ phê duyệt',           -- Đơn mới gửi, chờ leader/admin phê duyệt
    'IT Leader đã phê duyệt',   -- IT Leader đã phê duyệt, chờ HR phê duyệt
    'Sale Leader đã phê duyệt', -- Sale Leader đã phê duyệt, chờ HR phê duyệt
    'Admin đã phê duyệt',       -- Admin đã phê duyệt, chờ HR phê duyệt
    'HR đã phê duyệt',          -- HR đã phê duyệt cuối cùng
    'Đã phê duyệt',             -- Giữ lại cho tương thích
    'Từ chối bởi IT Leader',    -- IT Leader từ chối
    'Từ chối bởi Sale Leader',  -- Sale Leader từ chối
    'Từ chối bởi Admin',        -- Admin từ chối
    'Từ chối bởi HR',           -- HR từ chối
    'Từ chối'                   -- Giữ lại cho tương thích
) NOT NULL DEFAULT 'Chờ phê duyệt' COMMENT 'Trạng thái đơn nghỉ phép';

-- Thêm cột để lưu thông tin phòng ban của người yêu cầu (nếu chưa có)
-- Lưu ý: Cột department đã có trong bảng staffs, không cần thêm vào leave_requests

-- Cập nhật các trạng thái cũ để tương thích
UPDATE leave_requests SET status = 'Admin đã phê duyệt' WHERE status = 'Admin đã phê duyệt';
UPDATE leave_requests SET status = 'HR đã phê duyệt' WHERE status = 'HR đã phê duyệt';
UPDATE leave_requests SET status = 'Từ chối bởi Admin' WHERE status = 'Từ chối bởi Admin';
UPDATE leave_requests SET status = 'Từ chối bởi HR' WHERE status = 'Từ chối bởi HR';

-- Thêm index để tối ưu hiệu suất
CREATE INDEX idx_leave_requests_status ON leave_requests (status);
CREATE INDEX idx_leave_requests_requester_dept ON leave_requests (requester_id);

-- Thêm comment cho bảng
ALTER TABLE leave_requests COMMENT = 'Bảng quản lý đơn nghỉ phép với quy trình phê duyệt theo phòng ban';

-- Hiển thị kết quả
SELECT 'Database updated successfully!' as message;
