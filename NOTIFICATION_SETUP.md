# Hướng dẫn Setup Tính năng Thông báo cho Internal Case

## Tổng quan
Tính năng này sẽ tự động gửi thông báo cho người xử lý (handler) khi có case nội bộ mới được tạo.

## Các file đã được tạo/cập nhật

### 1. API mới
- `api/create_internal_case_notification.php` - API tạo thông báo cho internal case
- `api/update_notifications_table.php` - API cập nhật bảng notifications

### 2. Database
- `database/add_internal_case_notification_type.sql` - Script SQL thêm loại thông báo mới
- `database/update_notifications_simple.sql` - Script SQL đơn giản để chạy

### 3. Files đã cập nhật
- `api/create_case.php` - Thêm logic gửi thông báo sau khi tạo case
- `includes/notifications.php` - Thêm hỗ trợ loại thông báo `internal_case`
- `internal_cases.php` - Cập nhật thông báo thành công

## Cách setup

### Bước 1: Cập nhật Database
Chạy script SQL sau trong phpMyAdmin hoặc MySQL client:

```sql
-- Cập nhật ENUM để thêm 'internal_case'
ALTER TABLE notifications 
MODIFY COLUMN type ENUM('leave_request', 'leave_approved', 'leave_rejected', 'internal_case', 'system') DEFAULT 'system';
```

Hoặc chạy file `database/update_notifications_simple.sql`

### Bước 2: Kiểm tra quyền truy cập
Đảm bảo các file API có thể truy cập được và có quyền ghi vào database.

### Bước 3: Test tính năng
1. Đăng nhập vào hệ thống
2. Vào trang "Case Nội Bộ"
3. Tạo một case nội bộ mới với người xử lý được chỉ định
4. Kiểm tra xem người xử lý có nhận được thông báo không

## Cách hoạt động

### Khi tạo case nội bộ:
1. User chọn người xử lý và nhấn "Tạo Case nội bộ"
2. Case được tạo thành công trong database
3. API `create_case.php` tự động gọi `create_internal_case_notification.php`
4. Thông báo được tạo cho người xử lý với:
   - Title: "Case nội bộ mới được giao"
   - Message: "Bạn có case nội bộ mới cần xử lý: [Mã case] - [Tiêu đề] (Yêu cầu bởi: [Tên người yêu cầu])"
   - Type: "internal_case"
   - Related ID: ID của case

### Khi người xử lý nhận thông báo:
1. Thông báo xuất hiện trong dropdown notifications
2. Icon: Building icon (fas fa-building) với màu info
3. Khi click vào thông báo: Chuyển đến trang internal_cases.php

## Lưu ý quan trọng

1. **Chỉ người xử lý nhận thông báo**: Các user khác không nhận được thông báo này
2. **Thông báo thành công**: Khi tạo case, user sẽ thấy thông báo "Đã gửi thông báo cho người xử lý: [Tên]"
3. **Lỗi thông báo**: Nếu việc gửi thông báo thất bại, case vẫn được tạo thành công (không ảnh hưởng đến việc tạo case)
4. **Database**: Cần cập nhật bảng notifications trước khi sử dụng tính năng

## Troubleshooting

### Lỗi "Loại thông báo không hợp lệ"
- Chạy script SQL để cập nhật bảng notifications
- Kiểm tra xem cột `type` đã có giá trị `internal_case` chưa

### Không nhận được thông báo
- Kiểm tra log lỗi trong console browser
- Kiểm tra quyền truy cập database
- Đảm bảo API `create_internal_case_notification.php` có thể truy cập được

### Thông báo không hiển thị đúng
- Kiểm tra file `includes/notifications.php` đã được cập nhật
- Clear cache browser nếu cần
