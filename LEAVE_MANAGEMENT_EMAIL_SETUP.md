# Hướng dẫn cài đặt hệ thống Email cho Đơn nghỉ phép

## 📋 Tổng quan

Hệ thống quản lý đơn nghỉ phép với email thông báo tự động bao gồm:

1. **Form đơn nghỉ phép** - User nộp đơn qua web
2. **Email thông báo** - Tự động gửi email đến admin
3. **Duyệt đơn qua email** - Admin click nút duyệt trong email
4. **Cập nhật trạng thái** - Hiển thị trạng thái cho user

## 🚀 Cài đặt

### 1. Cài đặt PHPMailer

```bash
composer require phpmailer/phpmailer
```

### 2. Cập nhật Database

Chạy file SQL để thêm cột `approve_token`:

```sql
-- Thêm cột approve_token vào bảng leave_requests
ALTER TABLE `leave_requests` 
ADD COLUMN `approve_token` VARCHAR(32) NULL COMMENT 'Token để duyệt đơn nghỉ phép qua email' AFTER `status`,
ADD INDEX `idx_approve_token` (`approve_token`);

-- Cập nhật comment cho bảng
ALTER TABLE `leave_requests` COMMENT = 'Bảng quản lý đơn nghỉ phép với token duyệt email';
```

### 3. Cấu hình Email

Chỉnh sửa file `config/email.php`:

```php
$email_config = [
    'smtp_host' => 'smtp.office365.com',     // SMTP Host cho Outlook
    'smtp_port' => 587,                      // SMTP Port
    'smtp_secure' => 'tls',                  // Bảo mật TLS
    'smtp_auth' => true,                     // Yêu cầu xác thực
    
    // Thông tin email gửi
    'from_email' => 'your-email@outlook.com',  // Thay đổi email thực tế
    'from_name' => 'IT Services Management',   
    
    // Thông tin email admin nhận
    'admin_email' => 'admin@example.com',      // Thay đổi email admin thực tế
    'admin_name' => 'Quản trị viên',           
    
    // Thông tin đăng nhập SMTP
    'smtp_username' => 'your-email@outlook.com',  // Username SMTP
    'smtp_password' => 'your-password',           // Password SMTP
];

// Cấu hình URL website
$website_config = [
    'base_url' => 'http://localhost/it-services-management',  // Thay đổi URL thực tế
    'approve_url' => '/approve_leave.php',
];
```

### 4. Cấu hình Outlook SMTP

Để sử dụng Outlook SMTP, bạn cần:

1. **Bật 2FA** cho tài khoản Outlook
2. **Tạo App Password**:
   - Vào Microsoft Account → Security
   - Advanced security options → App passwords
   - Tạo password mới cho ứng dụng
3. **Sử dụng App Password** thay vì password thường

## 📁 Cấu trúc Files

```
├── config/
│   ├── email.php                    # Cấu hình email
│   └── db.php                       # Cấu hình database
├── api/
│   ├── submit_leave.php             # API submit đơn nghỉ phép
│   └── send_leave_approval_email.php # API gửi email thông báo
├── assets/
│   └── js/
│       └── leave_form.js            # JavaScript xử lý form
├── approve_leave.php                # Trang duyệt đơn qua email
├── leave_management.php             # Trang quản lý đơn nghỉ phép
└── database/
    └── add_approve_token_to_leave_requests.sql # SQL cập nhật database
```

## 🔧 Cấu hình chi tiết

### 1. Cấu hình SMTP Outlook

```php
// Trong config/email.php
$email_config = [
    'smtp_host' => 'smtp.office365.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    'smtp_auth' => true,
    'smtp_username' => 'your-email@outlook.com',
    'smtp_password' => 'your-app-password', // Sử dụng App Password
];
```

### 2. Cấu hình URL Website

```php
// Trong config/email.php
$website_config = [
    'base_url' => 'https://your-domain.com', // URL thực tế của website
    'approve_url' => '/approve_leave.php',
];
```

### 3. Cấu hình Email Admin

```php
// Trong config/email.php
$email_config = [
    'admin_email' => 'admin@yourcompany.com',
    'admin_name' => 'Quản trị viên',
];
```

## 🔐 Bảo mật

### 1. Token Duyệt Đơn

- Token được tạo ngẫu nhiên 16 ký tự
- Lưu trong database với index để tìm kiếm nhanh
- Token bị xóa sau khi đơn được duyệt
- Chỉ có hiệu lực một lần

### 2. Xác thực URL

- URL duyệt đơn chứa ID và token
- Hệ thống xác thực cả ID và token trước khi duyệt
- Chỉ duyệt được đơn có trạng thái "Chờ phê duyệt"

### 3. Bảo vệ CSRF

- Sử dụng token ngẫu nhiên cho mỗi đơn
- Token không thể đoán được
- Token có thời hạn (bị xóa sau khi duyệt)

## 📧 Template Email

### 1. Email HTML

Email được gửi dưới dạng HTML với:
- Header đẹp mắt
- Bảng thông tin đơn nghỉ phép
- Nút "Duyệt đơn" nổi bật
- Footer thông tin

### 2. Email Text

Email cũng có phiên bản text thuần cho các email client không hỗ trợ HTML.

## 🧪 Testing

### 1. Test Gửi Email

```php
// Test gửi email
$response = file_get_contents('http://localhost/api/send_leave_approval_email.php', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode(['leave_id' => 1])
    ]
]));
```

### 2. Test Duyệt Đơn

Truy cập URL: `http://localhost/approve_leave.php?id=1&token=abc123`

### 3. Test Form Submit

Sử dụng form trong `leave_management.php` để tạo đơn mới.

## 🚨 Troubleshooting

### 1. Email không gửi được

**Lỗi thường gặp:**
- Sai thông tin SMTP
- Chưa bật 2FA và App Password
- Firewall chặn port 587

**Giải pháp:**
```php
// Kiểm tra log lỗi
error_log("PHPMailer Error: " . $e->getMessage());

// Sử dụng mail() function làm fallback
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    return mail($to, $subject, $message, $headers);
}
```

### 2. Token không hợp lệ

**Nguyên nhân:**
- Token đã được sử dụng
- Đơn đã được duyệt
- Token không tồn tại

**Kiểm tra:**
```sql
SELECT * FROM leave_requests WHERE id = ? AND approve_token = ? AND status = 'Chờ phê duyệt';
```

### 3. URL không hoạt động

**Kiểm tra:**
- Cấu hình `base_url` đúng
- File `approve_leave.php` tồn tại
- Quyền truy cập file

## 📝 Logs

Hệ thống ghi log các hoạt động:

```
logs/
├── email_errors.log      # Lỗi gửi email
├── approval_errors.log   # Lỗi duyệt đơn
└── system_errors.log     # Lỗi hệ thống
```

## 🔄 Workflow

1. **User nộp đơn** → Form submit → `api/submit_leave.php`
2. **Lưu database** → Tạo token → Gửi email
3. **Admin nhận email** → Click nút duyệt → `approve_leave.php`
4. **Xác thực token** → Cập nhật trạng thái → Hiển thị thông báo
5. **User xem trạng thái** → Trang quản lý đơn nghỉ phép

## 📞 Hỗ trợ

Nếu gặp vấn đề, vui lòng:

1. Kiểm tra log lỗi
2. Xác nhận cấu hình email
3. Test từng bước workflow
4. Liên hệ admin để hỗ trợ

---

**Lưu ý:** Đảm bảo thay đổi tất cả thông tin cấu hình (email, password, URL) trước khi sử dụng trong môi trường production. 