-- Tạo bảng maintenance_requests (yêu cầu bảo trì)
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

-- Tạo bảng maintenance_cases (case bảo trì)
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

-- Tạo bảng maintenance_tasks (task bảo trì)
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

-- Thêm comment cho các bảng
ALTER TABLE maintenance_requests COMMENT = 'Bảng lưu trữ yêu cầu bảo trì';
ALTER TABLE maintenance_cases COMMENT = 'Bảng lưu trữ case bảo trì';
ALTER TABLE maintenance_tasks COMMENT = 'Bảng lưu trữ task bảo trì'; 