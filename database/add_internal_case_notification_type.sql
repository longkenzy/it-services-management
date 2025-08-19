-- Thêm loại thông báo internal_case vào bảng notifications
-- Cập nhật ENUM type để bao gồm 'internal_case'

ALTER TABLE notifications 
MODIFY COLUMN type ENUM('leave_request', 'leave_approved', 'leave_rejected', 'internal_case', 'system') DEFAULT 'system';

-- Thêm comment để giải thích loại thông báo mới
ALTER TABLE notifications 
MODIFY COLUMN type ENUM('leave_request', 'leave_approved', 'leave_rejected', 'internal_case', 'system') DEFAULT 'system' 
COMMENT 'leave_request: Yêu cầu nghỉ phép, leave_approved: Đơn nghỉ phép được duyệt, leave_rejected: Đơn nghỉ phép bị từ chối, internal_case: Case nội bộ, system: Thông báo hệ thống';
