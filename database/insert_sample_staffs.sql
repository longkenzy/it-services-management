-- ===============================================
-- IT CRM Database - Sample Staff Data
-- File: insert_sample_staffs.sql  
-- Mục đích: Thêm dữ liệu mẫu cho bảng staffs
-- ===============================================

USE it_crm_db;

-- Thêm dữ liệu mẫu cho bảng staffs
INSERT INTO staffs (
    employee_code, fullname, username, birth_year, gender, 
    position, department, office, phone, email, 
    contract_type, seniority, status
) VALUES 
(
    'NV001', 'Nguyễn Văn An', 'admin', 1990, 'Nam',
    'Trưởng phòng IT', 'Công nghệ thông tin', 'Hà Nội', '0901234567', 'admin@company.com',
    'Chính thức', 5, 'Đang làm việc'
),
(
    'NV002', 'Trần Thị Bình', 'user1', 1992, 'Nữ', 
    'Nhân viên IT', 'Công nghệ thông tin', 'Hà Nội', '0901234568', 'user1@company.com',
    'Chính thức', 3, 'Đang làm việc'
),
(
    'NV003', 'Lê Văn Cường', 'user2', 1988, 'Nam',
    'Kỹ sư phần mềm', 'Công nghệ thông tin', 'Hà Nội', '0901234569', 'user2@company.com', 
    'Chính thức', 7, 'Đang làm việc'
),
(
    'NV004', 'Phạm Thị Dung', 'user3', 1995, 'Nữ',
    'Nhân viên HR', 'Nhân sự', 'Hà Nội', '0901234570', 'user3@company.com',
    'Chính thức', 2, 'Đang làm việc'
),
(
    'NV005', 'Hoàng Văn Em', 'user4', 1993, 'Nam',
    'Kế toán', 'Tài chính', 'Hà Nội', '0901234571', 'user4@company.com',
    'Chính thức', 4, 'Đang làm việc'
);

-- Hiển thị kết quả
SELECT 'Đã thêm dữ liệu mẫu cho bảng staffs thành công!' as message;
SELECT COUNT(*) as total_records FROM staffs; 