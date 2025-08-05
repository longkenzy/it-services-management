-- ===============================================
-- IT CRM Database - Update Leave Requests Comment Field
-- File: database/update_leave_requests_comment.sql
-- Mục đích: Cập nhật tên trường approval_notes thành approval_comment
-- ===============================================

-- Sử dụng database it_crm_db
USE thichho1_it_crm_db;

-- Cập nhật tên trường approval_notes thành approval_comment
ALTER TABLE leave_requests CHANGE COLUMN approval_notes approval_comment text DEFAULT NULL COMMENT 'Ghi chú phê duyệt';

-- Hiển thị thông báo thành công
SELECT 'Trường approval_comment đã được cập nhật thành công!' AS message; 