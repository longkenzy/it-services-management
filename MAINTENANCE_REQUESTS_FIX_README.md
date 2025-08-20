# Hướng dẫn sửa lỗi Auto Increment cho bảng maintenance_requests

## Mô tả vấn đề
Bảng `maintenance_requests` bị lỗi auto-increment reset về 0, khiến ID luôn bằng 0 khi tạo yêu cầu bảo trì mới.

## Nguyên nhân có thể
1. Cột `id` bị mất thuộc tính `AUTO_INCREMENT`
2. Giá trị `AUTO_INCREMENT` bị reset về 1 hoặc NULL
3. Cấu trúc bảng bị thay đổi do import/export dữ liệu

## Các file fix đã tạo

### 1. File PHP chính: `fix_maintenance_requests_auto_increment.php`
- **Mục đích**: Fix auto-increment thông qua giao diện web
- **Cách sử dụng**: Truy cập `http://your-domain/fix_maintenance_requests_auto_increment.php`
- **Ưu điểm**: Dễ sử dụng, có giao diện trực quan

### 2. File API: `api/fix_maintenance_requests_auto_increment.php`
- **Mục đích**: Fix auto-increment thông qua API call
- **Cách sử dụng**: Gửi POST request đến endpoint
- **Yêu cầu**: Quyền admin hoặc super_admin
- **Response**: JSON với chi tiết từng bước thực hiện

### 3. File SQL: `database/fix_maintenance_requests_auto_increment.sql`
- **Mục đích**: Fix auto-increment thông qua SQL commands
- **Cách sử dụng**: Chạy từng lệnh SQL trong phpMyAdmin hoặc MySQL client

## Cách sử dụng

### Phương pháp 1: Sử dụng file PHP chính (Khuyến nghị)
1. Truy cập: `http://your-domain/fix_maintenance_requests_auto_increment.php`
2. File sẽ tự động thực hiện các bước sau:
   - Kiểm tra cấu trúc bảng
   - Tìm ID cao nhất hiện tại
   - Sửa cấu trúc cột id nếu cần
   - Reset AUTO_INCREMENT về giá trị đúng
   - Test insert để kiểm tra
3. Xem kết quả và thông báo hoàn thành

### Phương pháp 2: Sử dụng API
```javascript
fetch('/api/fix_maintenance_requests_auto_increment.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    }
})
.then(response => response.json())
.then(data => {
    console.log('Fix result:', data);
});
```

### Phương pháp 3: Sử dụng SQL (Thủ công)
1. Mở phpMyAdmin hoặc MySQL client
2. Chạy các lệnh trong file `database/fix_maintenance_requests_auto_increment.sql`
3. Thay thế `X` bằng giá trị (max_id + 1) từ kết quả SELECT

## Các bước fix chi tiết

### Bước 1: Kiểm tra cấu trúc bảng
```sql
DESCRIBE maintenance_requests;
```

### Bước 2: Kiểm tra AUTO_INCREMENT hiện tại
```sql
SHOW TABLE STATUS LIKE 'maintenance_requests';
```

### Bước 3: Tìm ID cao nhất
```sql
SELECT MAX(id) as max_id FROM maintenance_requests;
```

### Bước 4: Sửa cấu trúc cột id (nếu cần)
```sql
ALTER TABLE maintenance_requests MODIFY COLUMN id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;
```

### Bước 5: Reset AUTO_INCREMENT
```sql
ALTER TABLE maintenance_requests AUTO_INCREMENT = X;  -- X = max_id + 1
```

### Bước 6: Kiểm tra lại
```sql
SHOW TABLE STATUS LIKE 'maintenance_requests';
```

## Kiểm tra sau khi fix

1. Tạo một yêu cầu bảo trì mới
2. Kiểm tra ID được tạo có đúng không
3. Xác nhận ID tiếp theo sẽ tăng dần

## Lưu ý quan trọng

- **Backup dữ liệu**: Luôn backup database trước khi thực hiện fix
- **Quyền truy cập**: Cần quyền admin để thực hiện các thao tác ALTER TABLE
- **Test**: Luôn test sau khi fix để đảm bảo hoạt động bình thường
- **Log**: Các hoạt động fix sẽ được log vào bảng `user_activity_logs`

## Troubleshooting

### Lỗi "Access denied"
- Kiểm tra quyền truy cập database
- Đảm bảo user có quyền ALTER TABLE

### Lỗi "Table doesn't exist"
- Kiểm tra tên bảng có đúng không
- Đảm bảo database đã được chọn

### Lỗi "Duplicate entry"
- Kiểm tra xem có record nào có ID = 0 không
- Xóa record có ID = 0 nếu có

## Liên hệ hỗ trợ

Nếu gặp vấn đề, vui lòng liên hệ IT Support Team với thông tin:
- Lỗi cụ thể gặp phải
- Log lỗi từ file error log
- Screenshot (nếu có)

---
*Tạo bởi: IT Support Team*  
*Ngày tạo: 2024-12-19*  
*Phiên bản: 1.0*
