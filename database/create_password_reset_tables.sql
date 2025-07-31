-- ===== PASSWORD RESET TABLES ===== --
-- File: database/create_password_reset_tables.sql
-- Mục đích: Tạo bảng cho tính năng quên mật khẩu có trả phí Momo

-- Bảng lưu trữ yêu cầu reset password
CREATE TABLE IF NOT EXISTS password_reset_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    order_id VARCHAR(50) UNIQUE NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 10000.00,
    payment_content VARCHAR(100) NOT NULL,
    status ENUM('pending', 'paid', 'completed', 'expired') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    expires_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 30 MINUTE),
    INDEX idx_email (email),
    INDEX idx_order_id (order_id),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bảng lưu trữ lịch sử giao dịch Momo (optional - để tracking)
CREATE TABLE IF NOT EXISTS momo_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(100) NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_content VARCHAR(100) NOT NULL,
    status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
    momo_response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm comment cho bảng
ALTER TABLE password_reset_requests COMMENT = 'Bảng lưu trữ yêu cầu reset password có trả phí Momo';
ALTER TABLE momo_transactions COMMENT = 'Bảng lưu trữ lịch sử giao dịch Momo'; 