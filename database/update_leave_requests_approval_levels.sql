-- ===============================================
-- IT CRM Database - Update Leave Requests for 2-Level Approval
-- File: database/update_leave_requests_approval_levels.sql
-- Mục đích: Cập nhật trạng thái đơn nghỉ phép để hỗ trợ 2 cấp phê duyệt
-- ===============================================

-- Sử dụng database
USE thichho1_it_crm_db;

-- Cập nhật cột status để hỗ trợ trạng thái mới
ALTER TABLE leave_requests MODIFY COLUMN status ENUM(
    'Chờ phê duyệt',           -- Đơn mới gửi, chờ admin phê duyệt
    'Admin đã phê duyệt',       -- Admin đã phê duyệt, chờ HR phê duyệt
    'HR đã phê duyệt',          -- HR đã phê duyệt cuối cùng
    'Đã phê duyệt',             -- Giữ lại cho tương thích
    'Từ chối bởi Admin',        -- Admin từ chối
    'Từ chối bởi HR',           -- HR từ chối
    'Từ chối'                   -- Giữ lại cho tương thích
) NOT NULL DEFAULT 'Chờ phê duyệt' COMMENT 'Trạng thái đơn nghỉ phép';

-- Thêm cột để lưu thông tin phê duyệt từng cấp
ALTER TABLE leave_requests ADD COLUMN admin_approved_by INT NULL COMMENT 'ID admin phê duyệt' AFTER approved_by;
ALTER TABLE leave_requests ADD COLUMN admin_approved_at DATETIME NULL COMMENT 'Thời gian admin phê duyệt' AFTER approved_at;
ALTER TABLE leave_requests ADD COLUMN admin_approval_comment TEXT NULL COMMENT 'Ghi chú phê duyệt của admin' AFTER approval_comment;

ALTER TABLE leave_requests ADD COLUMN hr_approved_by INT NULL COMMENT 'ID HR phê duyệt' AFTER admin_approved_by;
ALTER TABLE leave_requests ADD COLUMN hr_approved_at DATETIME NULL COMMENT 'Thời gian HR phê duyệt' AFTER admin_approved_at;
ALTER TABLE leave_requests ADD COLUMN hr_approval_comment TEXT NULL COMMENT 'Ghi chú phê duyệt của HR' AFTER admin_approval_comment;

-- Thêm foreign key constraints
ALTER TABLE leave_requests ADD CONSTRAINT fk_leave_requests_admin_approved_by 
    FOREIGN KEY (admin_approved_by) REFERENCES staffs(id) ON DELETE SET NULL;

ALTER TABLE leave_requests ADD CONSTRAINT fk_leave_requests_hr_approved_by 
    FOREIGN KEY (hr_approved_by) REFERENCES staffs(id) ON DELETE SET NULL;

-- Hiển thị thông báo thành công
SELECT 'Bảng leave_requests đã được cập nhật để hỗ trợ 2 cấp phê duyệt!' AS message; 