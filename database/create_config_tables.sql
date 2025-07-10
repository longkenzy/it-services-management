-- ===== BẢNG PHÒNG BAN ===== --
CREATE TABLE IF NOT EXISTS departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL COMMENT 'Tên phòng ban',
    office VARCHAR(255) DEFAULT NULL COMMENT 'Văn phòng',
    address TEXT DEFAULT NULL COMMENT 'Địa chỉ',
    description TEXT DEFAULT NULL COMMENT 'Mô tả',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT DEFAULT NULL COMMENT 'ID người tạo',
    updated_by INT DEFAULT NULL COMMENT 'ID người cập nhật',
    
    -- Indexes
    INDEX idx_name (name),
    INDEX idx_office (office),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Bảng quản lý phòng ban';

-- ===== BẢNG CÔNG TY ĐỐI TÁC ===== --
CREATE TABLE IF NOT EXISTS partner_companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL COMMENT 'Tên công ty',
    short_name VARCHAR(100) DEFAULT NULL COMMENT 'Tên viết tắt',
    address TEXT DEFAULT NULL COMMENT 'Địa chỉ công ty',
    contact_person VARCHAR(255) DEFAULT NULL COMMENT 'Người liên hệ',
    contact_phone VARCHAR(20) DEFAULT NULL COMMENT 'SĐT người liên hệ',
    contact_email VARCHAR(255) DEFAULT NULL COMMENT 'Email người liên hệ',
    description TEXT DEFAULT NULL COMMENT 'Mô tả',
    status ENUM('active', 'inactive') DEFAULT 'active' COMMENT 'Trạng thái',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT DEFAULT NULL COMMENT 'ID người tạo',
    updated_by INT DEFAULT NULL COMMENT 'ID người cập nhật',
    
    -- Indexes
    INDEX idx_name (name),
    INDEX idx_short_name (short_name),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Bảng quản lý công ty đối tác';

-- ===== BẢNG LOẠI CASE NỘI BỘ ===== --
CREATE TABLE IF NOT EXISTS internal_case_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL COMMENT 'Tên loại case nội bộ',
    description TEXT DEFAULT NULL COMMENT 'Mô tả',
    status ENUM('active', 'inactive') DEFAULT 'active' COMMENT 'Trạng thái',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Bảng loại case nội bộ';

-- ===== BẢNG LOẠI CASE TRIỂN KHAI ===== --
CREATE TABLE IF NOT EXISTS deployment_case_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL COMMENT 'Tên loại case triển khai',
    description TEXT DEFAULT NULL COMMENT 'Mô tả',
    status ENUM('active', 'inactive') DEFAULT 'active' COMMENT 'Trạng thái',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Bảng loại case triển khai';

-- ===== BẢNG LOẠI CASE BẢO TRÌ ===== --
CREATE TABLE IF NOT EXISTS maintenance_case_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL COMMENT 'Tên loại case bảo trì',
    description TEXT DEFAULT NULL COMMENT 'Mô tả',
    status ENUM('active', 'inactive') DEFAULT 'active' COMMENT 'Trạng thái',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Bảng loại case bảo trì';

-- ===== SAMPLE DATA ===== --

-- Thêm dữ liệu mẫu cho departments
INSERT INTO departments (name, office, address, description) VALUES
('Phòng IT', 'Văn phòng HN', '123 Phố Huế, Hà Nội', 'Phòng Công nghệ thông tin'),
('Phòng Nhân sự', 'Văn phòng HN', '123 Phố Huế, Hà Nội', 'Phòng Quản lý nhân sự'),
('Phòng Kế toán', 'Văn phòng HN', '123 Phố Huế, Hà Nội', 'Phòng Kế toán - Tài chính'),
('Phòng Marketing', 'Văn phòng HCM', '456 Nguyễn Huệ, TP.HCM', 'Phòng Marketing & Communications'),
('Phòng Kinh doanh', 'Văn phòng HCM', '456 Nguyễn Huệ, TP.HCM', 'Phòng Kinh doanh & Bán hàng');

-- Thêm dữ liệu mẫu cho partner_companies
INSERT INTO partner_companies (name, short_name, address, contact_person, contact_phone, contact_email, description, status) VALUES
('AICe (Chiếu Sáng Điện Tử)', 'AICe', '123 Đường Sáng, TP.HCM', 'Nguyễn Văn A', '0901000001', 'aice@partner.com', 'Công ty chiếu sáng điện tử', 'active'),
('AMPERE', 'AMPERE', '234 Đường Điện, Hà Nội', 'Trần Thị B', '0901000002', 'ampere@partner.com', 'Công ty điện lực', 'active'),
('AURECON', 'AURECON', '345 Đường Công Nghệ, Đà Nẵng', 'Lê Văn C', '0901000003', 'aurecon@partner.com', 'Công ty tư vấn kỹ thuật', 'active'),
('BASE TÂN PHÚ (Liên Việt)', 'BASE TP', '456 Đường Tân Phú, TP.HCM', 'Phạm Thị D', '0901000004', 'basetanphu@partner.com', 'Công ty liên doanh', 'active'),
('BCA', 'BCA', '567 Đường BCA, Hà Nội', 'Ngô Văn E', '0901000005', 'bca@partner.com', 'Công ty bảo hiểm', 'active'),
('DEVEE ENT', 'DEVEE', '678 Đường Phát Triển, TP.HCM', 'Đỗ Thị F', '0901000006', 'devee@partner.com', 'Công ty phát triển giải pháp', 'active'),
('DIPLOMAT', 'DIPLOMAT', '789 Đường Ngoại Giao, Hà Nội', 'Vũ Văn G', '0901000007', 'diplomat@partner.com', 'Công ty ngoại giao', 'active'),
('ELECTRONIC TRIPOD VN (Biên Hòa)', 'TRIPOD VN', '890 Đường Công Nghiệp, Biên Hòa', 'Bùi Thị H', '0901000008', 'tripodvn@partner.com', 'Công ty điện tử', 'active'),
('ELITE LONG THÀNH', 'ELITE LT', '901 Đường Long Thành, Đồng Nai', 'Nguyễn Văn I', '0901000009', 'elitelongthanh@partner.com', 'Công ty sản xuất', 'active'),
('EVEREST EDU', 'EVEREST EDU', '101 Đường Giáo Dục, TP.HCM', 'Trần Thị K', '0901000010', 'everestedu@partner.com', 'Công ty giáo dục', 'active'),
('GFT VN', 'GFT VN', '202 Đường Công Nghệ, Hà Nội', 'Lê Văn L', '0901000011', 'gftvn@partner.com', 'Công ty công nghệ', 'active'),
('GIA PHÚC KHANG', 'GPK', '303 Đường Gia Phúc, TP.HCM', 'Phạm Thị M', '0901000012', 'giaphuckhang@partner.com', 'Công ty xây dựng', 'active'),
('HASKONINGDHV VN', 'HASKONINGDHV', '404 Đường Tư Vấn, Hà Nội', 'Ngô Văn N', '0901000013', 'haskoningdhv@partner.com', 'Công ty tư vấn quốc tế', 'active'),
('HỘI LỘC', 'HỘI LỘC', '505 Đường Hội Lộc, TP.HCM', 'Đỗ Thị O', '0901000014', 'hoiloc@partner.com', 'Công ty sản xuất', 'active'),
('HUDECO', 'HUDECO', '606 Đường Hude, Hà Nội', 'Vũ Văn P', '0901000015', 'hudeco@partner.com', 'Công ty xây dựng', 'active'),
('IOM (Tổ chức Di cư Quốc tế)', 'IOM', '707 Đường Quốc Tế, TP.HCM', 'Bùi Thị Q', '0901000016', 'iom@partner.com', 'Tổ chức quốc tế', 'active'),
('LAPBEE', 'LAPBEE', '808 Đường Lapbee, Hà Nội', 'Nguyễn Văn R', '0901000017', 'lapbee@partner.com', 'Công ty công nghệ', 'active'),
('LÊ HUY', 'LÊ HUY', '909 Đường Lê Huy, TP.HCM', 'Trần Thị S', '0901000018', 'lehuy@partner.com', 'Công ty thương mại', 'active'),
('LOTTE VN', 'LOTTE VN', '111 Đường Lotte, Hà Nội', 'Lê Văn T', '0901000019', 'lottevn@partner.com', 'Công ty bán lẻ', 'active'),
('MAXLINK', 'MAXLINK', '222 Đường Maxlink, TP.HCM', 'Phạm Thị U', '0901000020', 'maxlink@partner.com', 'Công ty viễn thông', 'active'),
('MÁY CHỦ VIỆT', 'MÁY CHỦ VIỆT', '333 Đường Máy Chủ, Hà Nội', 'Ngô Văn V', '0901000021', 'maychuviet@partner.com', 'Công ty máy chủ', 'active'),
('MEMORYZONE (Tin học siêu tốc)', 'MEMORYZONE', '444 Đường Tin Học, TP.HCM', 'Đỗ Thị X', '0901000022', 'memoryzone@partner.com', 'Công ty tin học', 'active'),
('NAB (Phát Triển Phần Mềm VN)', 'NAB', '555 Đường Phần Mềm, Hà Nội', 'Vũ Văn Y', '0901000023', 'nab@partner.com', 'Công ty phần mềm', 'active'),
('NETVIET', 'NETVIET', '666 Đường Netviet, TP.HCM', 'Bùi Thị Z', '0901000024', 'netviet@partner.com', 'Công ty truyền thông', 'active'),
('NGUYÊN KIM', 'NGUYÊN KIM', '777 Đường Nguyên Kim, Hà Nội', 'Nguyễn Văn AA', '0901000025', 'nguyenkim@partner.com', 'Công ty điện máy', 'active'),
('NHÂN SINH PHÚC', 'NHÂN SINH PHÚC', '888 Đường Nhân Sinh, TP.HCM', 'Trần Thị BB', '0901000026', 'nhansinhphuc@partner.com', 'Công ty dịch vụ', 'active'),
('NIELSENIQ', 'NIELSENIQ', '999 Đường Nielsen, Hà Nội', 'Lê Văn CC', '0901000027', 'nielseniq@partner.com', 'Công ty nghiên cứu thị trường', 'active'),
('NTEC (Công Nghệ Mới)', 'NTEC', '1010 Đường Công Nghệ, TP.HCM', 'Phạm Thị DD', '0901000028', 'ntec@partner.com', 'Công ty công nghệ mới', 'active'),
('NTG VN', 'NTG VN', '1111 Đường NTG, Hà Nội', 'Ngô Văn EE', '0901000029', 'ntgvn@partner.com', 'Công ty giải pháp', 'active'),
('PHAN NGUYỄN', 'PHAN NGUYỄN', '1212 Đường Phan Nguyễn, TP.HCM', 'Đỗ Thị FF', '0901000030', 'phannguyen@partner.com', 'Công ty thương mại', 'active'),
('PHARMACITY', 'PHARMACITY', '1313 Đường Pharmacy, Hà Nội', 'Vũ Văn GG', '0901000031', 'pharmacity@partner.com', 'Chuỗi nhà thuốc', 'active'),
('QUẢNG TIN', 'QUẢNG TIN', '1414 Đường Quảng Tin, TP.HCM', 'Bùi Thị HH', '0901000032', 'quangtin@partner.com', 'Công ty quảng cáo', 'active'),
('SCG XI MĂNG VN (CN TPHCM)', 'SCG XM VN', '1515 Đường Xi Măng, TP.HCM', 'Nguyễn Văn II', '0901000033', 'scgximang@partner.com', 'Công ty xi măng', 'active'),
('SEARA', 'SEARA', '1616 Đường Seara, Hà Nội', 'Trần Thị JJ', '0901000034', 'seara@partner.com', 'Công ty dịch vụ', 'active'),
('SIÊU VIỆT', 'SIÊU VIỆT', '1717 Đường Siêu Việt, TP.HCM', 'Lê Văn KK', '0901000035', 'sieuviet@partner.com', 'Công ty dịch vụ', 'active'),
('SMART SERVICES', 'SMART SERVICES', '1818 Đường Smart, Hà Nội', 'Phạm Thị LL', '0901000036', 'smartservices@partner.com', 'Công ty dịch vụ thông minh', 'active'),
('TÂM SEN', 'TÂM SEN', '1919 Đường Tâm Sen, TP.HCM', 'Ngô Văn MM', '0901000037', 'tamsen@partner.com', 'Công ty dịch vụ', 'active'),
('TATA COFFEE VN', 'TATA COFFEE', '2020 Đường Coffee, Hà Nội', 'Đỗ Thị NN', '0901000038', 'tatacoffee@partner.com', 'Công ty cà phê', 'active'),
('THÀNH NHÂN', 'THÀNH NHÂN', '2121 Đường Thành Nhân, TP.HCM', 'Vũ Văn OO', '0901000039', 'thanhnhan@partner.com', 'Công ty thương mại', 'active'),
('TOTALENERGIES LPG VN', 'TOTAL LPG', '2222 Đường Năng Lượng, Hà Nội', 'Bùi Thị PP', '0901000040', 'totalenergieslpg@partner.com', 'Công ty năng lượng', 'active'),
('TOTALENERGIES MKG VN', 'TOTAL MKG', '2323 Đường Năng Lượng, TP.HCM', 'Nguyễn Văn QQ', '0901000041', 'totalenergiesmkg@partner.com', 'Công ty năng lượng', 'active'),
('UGREEN SG (Vi Tính SG)', 'UGREEN SG', '2424 Đường Ugreen, TP.HCM', 'Trần Thị RR', '0901000042', 'ugreensg@partner.com', 'Công ty máy tính', 'active'),
('VANTAGE LOGISTICS', 'VANTAGE', '2525 Đường Logistics, Hà Nội', 'Lê Văn SS', '0901000043', 'vantagelogistics@partner.com', 'Công ty logistics', 'active'),
('VINA TECH', 'VINA TECH', '2626 Đường Vina Tech, TP.HCM', 'Phạm Thị TT', '0901000044', 'vinatech@partner.com', 'Công ty công nghệ', 'active'),
('WUERTH VN', 'WUERTH VN', '2727 Đường Wuerth, Hà Nội', 'Ngô Văn UU', '0901000045', 'wuerthvn@partner.com', 'Công ty vật tư', 'active'); 