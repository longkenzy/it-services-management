-- =====================================================
-- TẠO CÁC BẢNG DATABASE CHO HỆ THỐNG MAINTENANCE
-- =====================================================

-- 1. Tạo bảng maintenance_requests (yêu cầu bảo trì)
CREATE TABLE IF NOT EXISTS maintenance_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_code VARCHAR(50) UNIQUE NOT NULL,
    po_number VARCHAR(100),
    no_contract_po BOOLEAN DEFAULT FALSE,
    contract_type VARCHAR(200),
    request_detail_type VARCHAR(200),
    email_subject_customer TEXT,
    email_subject_internal TEXT,
    expected_start DATE,
    expected_end DATE,
    customer_id INT,
    contact_person VARCHAR(100),
    contact_phone VARCHAR(20),
    sale_id INT,
    requester_notes TEXT,
    maintenance_manager VARCHAR(100),
    maintenance_status ENUM('Tiếp nhận', 'Đang xử lý', 'Hoàn thành', 'Huỷ') DEFAULT 'Tiếp nhận',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (customer_id) REFERENCES partner_companies(id) ON DELETE SET NULL,
    FOREIGN KEY (sale_id) REFERENCES staffs(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES staffs(id) ON DELETE SET NULL,
    
    INDEX idx_request_code (request_code),
    INDEX idx_customer_id (customer_id),
    INDEX idx_sale_id (sale_id),
    INDEX idx_maintenance_status (maintenance_status),
    INDEX idx_created_at (created_at)
);

-- 2. Tạo bảng maintenance_cases (case bảo trì)
CREATE TABLE IF NOT EXISTS maintenance_cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_code VARCHAR(50) UNIQUE NOT NULL,
    request_type VARCHAR(200),
    progress INT DEFAULT 0,
    case_description TEXT,
    notes TEXT,
    assigned_to INT,
    work_type VARCHAR(100),
    start_date DATETIME,
    end_date DATETIME,
    status ENUM('Tiếp nhận', 'Đang xử lý', 'Hoàn thành', 'Huỷ') DEFAULT 'Tiếp nhận',
    maintenance_request_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (assigned_to) REFERENCES staffs(id) ON DELETE SET NULL,
    FOREIGN KEY (maintenance_request_id) REFERENCES maintenance_requests(id) ON DELETE CASCADE,
    
    INDEX idx_case_code (case_code),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_maintenance_request_id (maintenance_request_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- 3. Tạo bảng maintenance_tasks (task bảo trì)
CREATE TABLE IF NOT EXISTS maintenance_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_number VARCHAR(50) UNIQUE NOT NULL,
    task_type VARCHAR(100),
    template_name VARCHAR(200),
    task_description TEXT,
    notes TEXT,
    assignee_id INT,
    start_date DATETIME,
    end_date DATETIME,
    status ENUM('Tiếp nhận', 'Đang xử lý', 'Hoàn thành', 'Huỷ') DEFAULT 'Tiếp nhận',
    maintenance_case_id INT,
    maintenance_request_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (assignee_id) REFERENCES staffs(id) ON DELETE SET NULL,
    FOREIGN KEY (maintenance_case_id) REFERENCES maintenance_cases(id) ON DELETE CASCADE,
    FOREIGN KEY (maintenance_request_id) REFERENCES maintenance_requests(id) ON DELETE CASCADE,
    
    INDEX idx_task_number (task_number),
    INDEX idx_assignee_id (assignee_id),
    INDEX idx_maintenance_case_id (maintenance_case_id),
    INDEX idx_maintenance_request_id (maintenance_request_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- =====================================================
-- THÊM DỮ LIỆU TEST
-- =====================================================

-- 4. Thêm dữ liệu test cho maintenance_requests
INSERT INTO maintenance_requests (
    request_code, 
    po_number, 
    no_contract_po, 
    contract_type, 
    request_detail_type,
    email_subject_customer, 
    email_subject_internal, 
    expected_start, 
    expected_end,
    customer_id, 
    contact_person, 
    contact_phone, 
    sale_id, 
    requester_notes, 
    maintenance_manager, 
    maintenance_status, 
    created_by
) VALUES 
('YC2412001', 'PO-MT-2024-001', 0, 'Hợp đồng bảo trì hệ thống', 'Bảo trì hệ thống định kỳ', 
 'Bảo trì hệ thống tháng 12/2024', 'Bảo trì hệ thống ABC Corp', '2024-12-01', '2024-12-31',
 1, 'Nguyễn Văn A', '0901234567', 1, 'Bảo trì định kỳ hệ thống ERP', 'Trần Nguyễn Anh Khoa', 'Đang xử lý', 1),

('YC2412002', 'PO-MT-2024-002', 0, 'Hợp đồng bảo trì thiết bị', 'Bảo trì thiết bị mạng', 
 'Bảo trì thiết bị mạng XYZ', 'Bảo trì switch và router', '2024-12-05', '2024-12-15',
 2, 'Trần Thị B', '0909876543', 2, 'Kiểm tra và bảo trì thiết bị mạng', 'Trần Nguyễn Anh Khoa', 'Tiếp nhận', 1),

('YC2412003', 'PO-MT-2024-003', 1, 'Hợp đồng bảo trì phần mềm', 'Khắc phục sự cố', 
 'Khắc phục lỗi hệ thống', 'Sửa lỗi database server', '2024-12-10', '2024-12-12',
 3, 'Lê Văn C', '0905555555', 1, 'Database bị lỗi connection timeout', 'Trần Nguyễn Anh Khoa', 'Hoàn thành', 1);

-- 5. Thêm dữ liệu test cho maintenance_cases
INSERT INTO maintenance_cases (
    case_code,
    request_type,
    progress,
    case_description,
    notes,
    assigned_to,
    work_type,
    start_date,
    end_date,
    status,
    maintenance_request_id
) VALUES 
('CMT2412001', 'Bảo trì hệ thống ERP', 75, 'Kiểm tra và tối ưu hóa database ERP', 'Cần backup trước khi thực hiện', 3, 'Bảo trì định kỳ', '2024-12-01 08:00:00', '2024-12-31 17:00:00', 'Đang xử lý', 1),

('CMT2412002', 'Bảo trì thiết bị mạng', 0, 'Kiểm tra switch và router', 'Thay thế cable bị hỏng', 4, 'Bảo trì thiết bị', '2024-12-05 09:00:00', '2024-12-15 16:00:00', 'Tiếp nhận', 2),

('CMT2412003', 'Khắc phục sự cố database', 100, 'Sửa lỗi connection timeout', 'Đã thay thế connection pool', 5, 'Khắc phục sự cố', '2024-12-10 10:00:00', '2024-12-12 15:00:00', 'Hoàn thành', 3);

-- 6. Thêm dữ liệu test cho maintenance_tasks
INSERT INTO maintenance_tasks (
    task_number,
    task_type,
    template_name,
    task_description,
    notes,
    assignee_id,
    start_date,
    end_date,
    status,
    maintenance_case_id,
    maintenance_request_id
) VALUES 
('TMT2412001', 'Kiểm tra database', 'Template kiểm tra DB', 'Kiểm tra performance database ERP', 'Chạy script phân tích', 3, '2024-12-01 08:00:00', '2024-12-01 12:00:00', 'Hoàn thành', 1, 1),

('TMT2412002', 'Tối ưu hóa query', 'Template tối ưu query', 'Tối ưu hóa các query chậm', 'Đã tạo index mới', 3, '2024-12-02 08:00:00', '2024-12-02 16:00:00', 'Hoàn thành', 1, 1),

('TMT2412003', 'Backup dữ liệu', 'Template backup', 'Backup toàn bộ database', 'Backup thành công', 3, '2024-12-03 08:00:00', '2024-12-03 10:00:00', 'Đang xử lý', 1, 1),

('TMT2412004', 'Kiểm tra switch', 'Template kiểm tra switch', 'Kiểm tra trạng thái các port switch', 'Phát hiện port 5 bị lỗi', 4, '2024-12-05 09:00:00', '2024-12-05 11:00:00', 'Tiếp nhận', 2, 2),

('TMT2412005', 'Thay thế cable', 'Template thay cable', 'Thay thế cable mạng bị hỏng', 'Cần mua cable mới', 4, '2024-12-06 09:00:00', '2024-12-06 14:00:00', 'Tiếp nhận', 2, 2),

('TMT2412006', 'Sửa lỗi connection', 'Template sửa lỗi', 'Sửa lỗi connection timeout', 'Đã tăng connection pool', 5, '2024-12-10 10:00:00', '2024-12-10 15:00:00', 'Hoàn thành', 3, 3),

('TMT2412007', 'Test hệ thống', 'Template test', 'Test lại hệ thống sau khi sửa', 'Hệ thống hoạt động bình thường', 5, '2024-12-11 09:00:00', '2024-12-11 16:00:00', 'Hoàn thành', 3, 3);

-- =====================================================
-- THÊM COMMENT CHO CÁC BẢNG
-- =====================================================

ALTER TABLE maintenance_requests COMMENT = 'Bảng lưu trữ yêu cầu bảo trì';
ALTER TABLE maintenance_cases COMMENT = 'Bảng lưu trữ case bảo trì';
ALTER TABLE maintenance_tasks COMMENT = 'Bảng lưu trữ task bảo trì';

-- =====================================================
-- KIỂM TRA DỮ LIỆU ĐÃ TẠO
-- =====================================================

-- Xem danh sách các bảng maintenance
SHOW TABLES LIKE 'maintenance%';

-- Xem cấu trúc bảng maintenance_requests
DESCRIBE maintenance_requests;

-- Xem dữ liệu test trong bảng maintenance_requests
SELECT * FROM maintenance_requests;

-- Xem dữ liệu test trong bảng maintenance_cases
SELECT * FROM maintenance_cases;

-- Xem dữ liệu test trong bảng maintenance_tasks
SELECT * FROM maintenance_tasks;

-- Kiểm tra quan hệ giữa các bảng
SELECT 
    mr.request_code,
    mr.contract_type,
    mr.maintenance_status,
    COUNT(mc.id) as total_cases,
    COUNT(mt.id) as total_tasks
FROM maintenance_requests mr
LEFT JOIN maintenance_cases mc ON mr.id = mc.maintenance_request_id
LEFT JOIN maintenance_tasks mt ON mr.id = mt.maintenance_request_id
GROUP BY mr.id, mr.request_code, mr.contract_type, mr.maintenance_status; 