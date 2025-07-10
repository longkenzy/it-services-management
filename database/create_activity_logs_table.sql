-- Tạo bảng user_activity_logs để lưu log hoạt động
CREATE TABLE IF NOT EXISTS user_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user_id (user_id),
    INDEX idx_activity (activity),
    INDEX idx_created_at (created_at)
);

-- Thêm comment cho bảng
ALTER TABLE user_activity_logs COMMENT = 'Lưu log hoạt động của người dùng'; 