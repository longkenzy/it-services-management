-- =====================================================
-- XÓA DỮ LIỆU TEST CỦA HỆ THỐNG MAINTENANCE
-- =====================================================

-- Xóa dữ liệu test theo thứ tự để tránh lỗi foreign key
-- 1. Xóa tasks trước
DELETE FROM maintenance_tasks WHERE task_number LIKE 'TMT%';

-- 2. Xóa cases
DELETE FROM maintenance_cases WHERE case_code LIKE 'CMT%';

-- 3. Xóa requests
DELETE FROM maintenance_requests WHERE request_code LIKE 'YC%';

-- Kiểm tra dữ liệu còn lại
SELECT 'maintenance_tasks' as table_name, COUNT(*) as record_count FROM maintenance_tasks
UNION ALL
SELECT 'maintenance_cases' as table_name, COUNT(*) as record_count FROM maintenance_cases
UNION ALL
SELECT 'maintenance_requests' as table_name, COUNT(*) as record_count FROM maintenance_requests; 