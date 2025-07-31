# 🔐 Tính Năng Quên Mật Khẩu Có Trả Phí Momo

## 📋 Tổng Quan

Tính năng "Quên mật khẩu có trả phí bằng Momo" cho phép người dùng khôi phục mật khẩu thông qua thanh toán 10,000 VNĐ qua ứng dụng Momo. Hệ thống sử dụng API miễn phí để tạo QR code và kiểm tra giao dịch.

## 🚀 Quy Trình Hoạt Động

### Bước 1: Nhập Email
- Người dùng truy cập trang "Quên mật khẩu"
- Nhập email đã đăng ký trong hệ thống
- Hệ thống kiểm tra email tồn tại và tạo order_id

### Bước 2: Thanh Toán Momo
- Hệ thống tạo QR code Momo với nội dung `RESET-{order_id}`
- Người dùng quét mã QR và thanh toán 10,000 VNĐ
- Hiển thị countdown timer 30 phút

### Bước 3: Xác Minh Thanh Toán
- Người dùng nhấn "Tôi đã thanh toán"
- Hệ thống gọi API kiểm tra lịch sử giao dịch Momo
- Xác minh giao dịch có nội dung và số tiền đúng

### Bước 4: Đặt Mật Khẩu Mới
- Sau khi xác minh thành công, hiển thị form đặt mật khẩu mới
- Kiểm tra độ mạnh mật khẩu và yêu cầu bảo mật
- Cập nhật mật khẩu vào database

### Bước 5: Hoàn Thành
- Hiển thị trang thành công
- Tự động chuyển về trang đăng nhập sau 10 giây

## 📁 Cấu Trúc File

```
├── database/
│   └── create_password_reset_tables.sql    # SQL tạo bảng
├── config/
│   └── momo_api.php                        # Cấu hình Momo API
├── forgot.php                              # Trang nhập email
├── pay_momo.php                            # Trang thanh toán QR
├── check_payment.php                       # Trang kiểm tra thanh toán
├── reset_password.php                      # Trang đặt mật khẩu mới
├── reset_password_done.php                 # Trang hoàn thành
└── MOMO_PASSWORD_RESET_SETUP.md           # File hướng dẫn này
```

## 🗄️ Cấu Trúc Database

### Bảng `password_reset_requests`
```sql
CREATE TABLE password_reset_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    order_id VARCHAR(50) UNIQUE NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 10000.00,
    payment_content VARCHAR(100) NOT NULL,
    status ENUM('pending', 'paid', 'completed', 'expired') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    expires_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 30 MINUTE)
);
```

### Bảng `momo_transactions`
```sql
CREATE TABLE momo_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(100) NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_content VARCHAR(100) NOT NULL,
    status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
    momo_response TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## ⚙️ Cấu Hình

### 1. Chạy SQL tạo bảng
```bash
mysql -u username -p database_name < database/create_password_reset_tables.sql
```

### 2. Cấu hình Momo API
Chỉnh sửa file `config/momo_api.php`:

```php
return [
    'phone_number' => '0123456789', // Số điện thoại Momo nhận tiền
    'amount' => 10000,              // Số tiền cố định (VNĐ)
    'qr_api_url' => 'https://momosv3.apimienphi.com/api/QRCode',
    'transaction_api_url' => 'https://momosv3.apimienphi.com/api/TransactionHistory',
    // ... các cấu hình khác
];
```

### 3. Cấu hình quan trọng
- **phone_number**: Số điện thoại Momo thật của bạn
- **amount**: Số tiền thanh toán (mặc định 10,000 VNĐ)
- **timeout**: Thời gian chờ API (mặc định 30 giây)
- **cache_duration**: Thời gian cache (mặc định 5 phút)

## 🔧 Tính Năng

### ✅ Đã Hoàn Thành
- [x] Trang nhập email với validation
- [x] Tạo QR code Momo tự động
- [x] Countdown timer 30 phút
- [x] Kiểm tra giao dịch qua API
- [x] Form đặt mật khẩu mới với validation
- [x] Kiểm tra độ mạnh mật khẩu
- [x] Lưu lịch sử giao dịch
- [x] Session management
- [x] Responsive design
- [x] Error handling

### 🎨 Giao Diện
- **Bootstrap 5**: Framework CSS hiện đại
- **Font Awesome**: Icons đẹp mắt
- **Gradient backgrounds**: Thiết kế hiện đại
- **Responsive**: Tương thích mobile
- **Loading animations**: UX tốt hơn

### 🔒 Bảo Mật
- **Password hashing**: Sử dụng `password_hash()`
- **Session validation**: Kiểm tra session mỗi bước
- **SQL injection protection**: Sử dụng prepared statements
- **XSS protection**: `htmlspecialchars()` cho output
- **CSRF protection**: Session-based validation

## 🚀 Cách Sử Dụng

### 1. Truy cập trang quên mật khẩu
```
http://your-domain.com/forgot.php
```

### 2. Nhập email đã đăng ký
- Hệ thống kiểm tra email tồn tại
- Tạo order_id duy nhất

### 3. Quét mã QR Momo
- Mở ứng dụng Momo
- Quét mã QR hiển thị
- Thanh toán 10,000 VNĐ

### 4. Xác nhận thanh toán
- Nhấn "Tôi đã thanh toán"
- Hệ thống kiểm tra giao dịch

### 5. Đặt mật khẩu mới
- Nhập mật khẩu mới
- Xác nhận mật khẩu
- Lưu mật khẩu

## 🐛 Troubleshooting

### Lỗi thường gặp

#### 1. QR Code không hiển thị
```
Nguyên nhân: API Momo không hoạt động
Giải pháp: Kiểm tra internet và thử lại
```

#### 2. Không tìm thấy giao dịch
```
Nguyên nhân: Giao dịch chưa được xử lý
Giải pháp: Đợi vài phút rồi thử lại
```

#### 3. Session expired
```
Nguyên nhân: Quá 30 phút
Giải pháp: Bắt đầu lại từ đầu
```

#### 4. Database error
```
Nguyên nhân: Bảng chưa được tạo
Giải pháp: Chạy SQL tạo bảng
```

### Debug Mode
Thêm vào đầu file PHP để debug:
```php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

## 📊 Monitoring

### Log Files
- **Error logs**: `/logs/error.log`
- **Payment logs**: Database table `momo_transactions`

### Metrics cần theo dõi
- Số lượng yêu cầu reset
- Tỷ lệ thanh toán thành công
- Thời gian xử lý trung bình
- Lỗi API Momo

## 🔄 Cập Nhật

### Version 1.0
- ✅ Tính năng cơ bản hoàn thành
- ✅ Giao diện responsive
- ✅ Bảo mật cơ bản

### Version 1.1 (Planned)
- 🔄 Email notification
- 🔄 SMS verification
- 🔄 Admin dashboard
- 🔄 Payment analytics

## 📞 Hỗ Trợ

### Liên hệ
- **Email**: support@company.com
- **Phone**: 0123456789

### Tài liệu tham khảo
- [Momo API Documentation](https://momosv3.apimienphi.com/)
- [Bootstrap 5 Documentation](https://getbootstrap.com/docs/5.3/)
- [PHP Password Hashing](https://www.php.net/manual/en/function.password-hash.php)

---

**Lưu ý**: Đây là tính năng thu phí, cần test kỹ trước khi deploy production. API Momo miễn phí có thể có giới hạn request. 