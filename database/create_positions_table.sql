-- ===== BẢNG CHỨC VỤ ===== --
CREATE TABLE IF NOT EXISTS positions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL COMMENT 'Tên chức vụ',
    department_id INT NOT NULL COMMENT 'ID phòng ban',
    description TEXT DEFAULT NULL COMMENT 'Mô tả chức vụ',
    status ENUM('active', 'inactive') DEFAULT 'active' COMMENT 'Trạng thái',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT DEFAULT NULL COMMENT 'ID người tạo',
    updated_by INT DEFAULT NULL COMMENT 'ID người cập nhật',
    
    -- Foreign Keys
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE ON UPDATE CASCADE,
    
    -- Indexes
    INDEX idx_name (name),
    INDEX idx_department_id (department_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Bảng quản lý chức vụ';

-- ===== SAMPLE DATA ===== --

-- Thêm dữ liệu mẫu cho positions
INSERT INTO positions (name, department_id, description, status) VALUES
-- Phòng IT (id = 1)
('Trưởng phòng IT', 1, 'Quản lý phòng Công nghệ thông tin', 'active'),
('Lập trình viên Senior', 1, 'Phát triển phần mềm cấp cao', 'active'),
('Lập trình viên', 1, 'Phát triển phần mềm', 'active'),
('System Admin', 1, 'Quản trị hệ thống', 'active'),
('IT Support', 1, 'Hỗ trợ kỹ thuật', 'active'),

-- Phòng Nhân sự (id = 2)
('Trưởng phòng Nhân sự', 2, 'Quản lý phòng Nhân sự', 'active'),
('Chuyên viên Tuyển dụng', 2, 'Tuyển dụng nhân sự', 'active'),
('Chuyên viên C&B', 2, 'Quản lý lương thưởng phúc lợi', 'active'),

-- Phòng Kế toán (id = 3)
('Trưởng phòng Kế toán', 3, 'Quản lý phòng Kế toán', 'active'),
('Kế toán trưởng', 3, 'Kế toán tổng hợp', 'active'),
('Kế toán viên', 3, 'Kế toán chi tiết', 'active'),

-- Phòng Marketing (id = 4)
('Trưởng phòng Marketing', 4, 'Quản lý phòng Marketing', 'active'),
('Marketing Manager', 4, 'Quản lý marketing', 'active'),
('Content Creator', 4, 'Tạo nội dung', 'active'),

-- Phòng Kinh doanh (id = 5)
('Trưởng phòng Kinh doanh', 5, 'Quản lý phòng Kinh doanh', 'active'),
('Sales Manager', 5, 'Quản lý bán hàng', 'active'),
('Nhân viên Kinh doanh', 5, 'Nhân viên bán hàng', 'active'); 