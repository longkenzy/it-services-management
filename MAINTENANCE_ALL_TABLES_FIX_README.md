# Hướng dẫn sửa lỗi Auto Increment cho tất cả bảng Maintenance

## Mô tả vấn đề
Các bảng maintenance (`maintenance_requests`, `maintenance_cases`, `maintenance_tasks`) bị lỗi auto-increment reset về 0, khiến ID luôn bằng 0 khi tạo mới.

## Các bảng bị ảnh hưởng
1. **`maintenance_requests`** - Yêu cầu bảo trì
2. **`maintenance_cases`** - Case bảo trì  
3. **`maintenance_tasks`** - Task bảo trì

## Các file fix đã tạo

### 1. File fix tổng hợp (Khuyến nghị)
- **`fix_all_maintenance_auto_increment.php`**
  - Fix tất cả 3 bảng maintenance cùng lúc
  - Hiển thị kết quả chi tiết cho từng bảng
  - Tóm tắt kết quả cuối cùng

### 2. File fix riêng lẻ

#### Cho bảng maintenance_requests:
- **`fix_maintenance_requests_auto_increment.php`** - File PHP chính
- **`api/fix_maintenance_requests_auto_increment.php`** - API endpoint
- **`database/fix_maintenance_requests_auto_increment.sql`** - File SQL
- **`test_maintenance_auto_increment.php`** - File test

#### Cho bảng maintenance_cases:
- **`fix_maintenance_cases_auto_increment.php`** - File PHP chính
- **`api/fix_maintenance_cases_auto_increment.php`** - API endpoint
- **`database/fix_maintenance_cases_auto_increment.sql`** - File SQL
- **`test_maintenance_cases_auto_increment.php`** - File test

#### Cho bảng maintenance_tasks:
- **`fix_maintenance_tasks_auto_increment.php`** - File PHP chính
- **`api/fix_maintenance_tasks_auto_increment.php`** - API endpoint
- **`database/fix_maintenance_tasks_auto_increment.sql`** - File SQL
- **`test_maintenance_tasks_auto_increment.php`** - File test

## Cách sử dụng

### Phương pháp 1: Fix tất cả cùng lúc (Khuyến nghị)
1. Truy cập: `http://your-domain/fix_all_maintenance_auto_increment.php`
2. File sẽ tự động xử lý tất cả 3 bảng
3. Xem kết quả chi tiết và tóm tắt

### Phương pháp 2: Fix từng bảng riêng lẻ
1. **Yêu cầu bảo trì**: `fix_maintenance_requests_auto_increment.php`
2. **Case bảo trì**: `fix_maintenance_cases_auto_increment.php`
3. **Task bảo trì**: `fix_maintenance_tasks_auto_increment.php`

### Phương pháp 3: Sử dụng API
```javascript
// Fix maintenance_requests
fetch('/api/fix_maintenance_requests_auto_increment.php', {
    method: 'POST'
});

// Fix maintenance_cases
fetch('/api/fix_maintenance_cases_auto_increment.php', {
    method: 'POST'
});

// Fix maintenance_tasks
fetch('/api/fix_maintenance_tasks_auto_increment.php', {
    method: 'POST'
});
```

### Phương pháp 4: Test trước khi fix
1. **Test requests**: `test_maintenance_auto_increment.php`
2. **Test cases**: `test_maintenance_cases_auto_increment.php`
3. **Test tasks**: `test_maintenance_tasks_auto_increment.php`

## Các bước fix chi tiết

### Bước 1: Kiểm tra cấu trúc bảng
```sql
DESCRIBE maintenance_requests;
DESCRIBE maintenance_cases;
DESCRIBE maintenance_tasks;
```

### Bước 2: Kiểm tra AUTO_INCREMENT hiện tại
```sql
SHOW TABLE STATUS LIKE 'maintenance_requests';
SHOW TABLE STATUS LIKE 'maintenance_cases';
SHOW TABLE STATUS LIKE 'maintenance_tasks';
```

### Bước 3: Tìm ID cao nhất
```sql
SELECT MAX(id) as max_id FROM maintenance_requests;
SELECT MAX(id) as max_id FROM maintenance_cases;
SELECT MAX(id) as max_id FROM maintenance_tasks;
```

### Bước 4: Sửa cấu trúc cột id (nếu cần)
```sql
ALTER TABLE maintenance_requests MODIFY COLUMN id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;
ALTER TABLE maintenance_cases MODIFY COLUMN id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;
ALTER TABLE maintenance_tasks MODIFY COLUMN id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;
```

### Bước 5: Reset AUTO_INCREMENT
```sql
ALTER TABLE maintenance_requests AUTO_INCREMENT = X;  -- X = max_id + 1
ALTER TABLE maintenance_cases AUTO_INCREMENT = Y;     -- Y = max_id + 1
ALTER TABLE maintenance_tasks AUTO_INCREMENT = Z;     -- Z = max_id + 1
```

## Kiểm tra sau khi fix

### 1. Tạo yêu cầu bảo trì mới
- Kiểm tra ID được tạo có đúng không
- Xác nhận ID tiếp theo sẽ tăng dần

### 2. Tạo case bảo trì mới
- Kiểm tra ID được tạo có đúng không
- Xác nhận ID tiếp theo sẽ tăng dần

### 3. Tạo task bảo trì mới
- Kiểm tra ID được tạo có đúng không
- Xác nhận ID tiếp theo sẽ tăng dần

### 4. Chạy file test
- Test từng bảng riêng lẻ để xác nhận hoạt động

## Lưu ý quan trọng

- **Backup database** trước khi thực hiện fix
- **Chỉ admin/super_admin** mới có thể thực hiện fix
- **Test** sau khi fix để đảm bảo hoạt động bình thường
- **Log** hoạt động sẽ được ghi vào bảng `user_activity_logs`
- **Thứ tự fix**: Nên fix theo thứ tự requests → cases → tasks (do quan hệ khóa ngoại)

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

### Lỗi khóa ngoại
- Đảm bảo các bảng liên quan đã được fix trước
- Kiểm tra dữ liệu tham chiếu có tồn tại không

## Cấu trúc thư mục

```
it-services-management/
├── fix_all_maintenance_auto_increment.php (KHuyến nghị)
├── fix_maintenance_requests_auto_increment.php
├── fix_maintenance_cases_auto_increment.php
├── fix_maintenance_tasks_auto_increment.php
├── test_maintenance_auto_increment.php
├── test_maintenance_cases_auto_increment.php
├── test_maintenance_tasks_auto_increment.php
├── MAINTENANCE_ALL_TABLES_FIX_README.md
├── api/
│   ├── fix_maintenance_requests_auto_increment.php
│   ├── fix_maintenance_cases_auto_increment.php
│   └── fix_maintenance_tasks_auto_increment.php
├── database/
│   ├── fix_maintenance_requests_auto_increment.sql
│   ├── fix_maintenance_cases_auto_increment.sql
│   └── fix_maintenance_tasks_auto_increment.sql
└── maintenance_requests.php (đã cập nhật với nút fix)
```

## Liên hệ hỗ trợ

Nếu gặp vấn đề, vui lòng liên hệ IT Support Team với thông tin:
- Lỗi cụ thể gặp phải
- Log lỗi từ file error log
- Screenshot (nếu có)
- Bảng nào bị lỗi

---
*Tạo bởi: IT Support Team*  
*Ngày tạo: 2024-12-19*  
*Phiên bản: 1.0*
