# Hướng dẫn sửa lỗi Auto Increment cho bảng deployment_tasks

## Vấn đề
Khi tạo deployment task mới, ID của task luôn bằng 0 thay vì tự động tăng. Đây là lỗi auto increment trong MySQL.

## Nguyên nhân có thể
1. Bảng `deployment_tasks` không có AUTO_INCREMENT đúng cách
2. Auto increment counter bị reset về 0
3. Có dữ liệu với ID = 0 đã tồn tại
4. Cấu trúc cột `id` bị lỗi

## Cách sửa lỗi

### Phương pháp 1: Chạy script PHP (Khuyến nghị)
1. Upload file `fix_deployment_tasks_auto_increment.php` lên hosting
2. Truy cập file này qua trình duyệt: `https://your-domain.com/fix_deployment_tasks_auto_increment.php`
3. Đợi script chạy xong và hiển thị kết quả
4. Xóa file sau khi sửa xong

### Phương pháp 2: Chạy script SQL
1. Truy cập phpMyAdmin hoặc MySQL client
2. Chạy từng lệnh trong file `database/fix_deployment_tasks_auto_increment.sql`:

```sql
-- 1. Xóa dữ liệu lỗi (ID = 0)
DELETE FROM deployment_tasks WHERE id = 0;

-- 2. Thêm PRIMARY KEY và AUTO_INCREMENT
ALTER TABLE deployment_tasks MODIFY COLUMN id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- 3. Sửa dữ liệu timestamp lỗi
UPDATE deployment_tasks SET created_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP 
WHERE created_at = '0000-00-00 00:00:00' OR updated_at = '0000-00-00 00:00:00';

-- 4. Kiểm tra kết quả
SHOW TABLE STATUS LIKE 'deployment_tasks';
SELECT MAX(id) as max_id FROM deployment_tasks;
```

### Phương pháp 3: Sử dụng API endpoint
1. Gửi POST request đến: `/api/fix_deployment_tasks_auto_increment.php`
2. API sẽ tự động kiểm tra và sửa lỗi
3. Trả về kết quả dạng JSON

## Kiểm tra sau khi sửa

### 1. Kiểm tra cấu trúc bảng
```sql
DESCRIBE deployment_tasks;
```
Cột `id` phải có:
- `Key`: PRI (Primary Key)
- `Extra`: auto_increment

### 2. Kiểm tra auto increment
```sql
SHOW TABLE STATUS LIKE 'deployment_tasks';
```
Trường `Auto_increment` phải lớn hơn ID cao nhất trong bảng.

### 3. Test tạo task mới
Tạo một deployment task mới và kiểm tra ID có được tự động tăng không.

## Các file script đã tạo

1. **`fix_deployment_tasks_auto_increment.php`** - Script PHP chính
2. **`database/fix_deployment_tasks_auto_increment.sql`** - Script SQL
3. **`api/fix_deployment_tasks_auto_increment.php`** - API endpoint
4. **`README_FIX_DEPLOYMENT_TASKS.md`** - File hướng dẫn này

## Lưu ý quan trọng

⚠️ **Backup dữ liệu trước khi chạy script**
- Luôn backup database trước khi thực hiện sửa lỗi
- Script sẽ xóa các record có ID = 0

⚠️ **Kiểm tra quyền truy cập**
- Đảm bảo có quyền ALTER TABLE trên database
- Đảm bảo có quyền DELETE và UPDATE

⚠️ **Sau khi sửa**
- Xóa các file script sau khi sửa xong
- Kiểm tra lại chức năng tạo task
- Monitor log để đảm bảo không có lỗi

## Troubleshooting

### Lỗi "Access denied"
- Kiểm tra quyền database user
- Đảm bảo có quyền ALTER, DELETE, UPDATE

### Lỗi "Table doesn't exist"
- Kiểm tra tên bảng có đúng không
- Đảm bảo đang ở đúng database

### Lỗi "Duplicate entry"
- Có thể có duplicate key constraint
- Kiểm tra và xóa duplicate trước khi sửa

### Lỗi "Foreign key constraint"
- Kiểm tra foreign key constraints
- Đảm bảo không vi phạm referential integrity

## Liên hệ hỗ trợ

Nếu gặp vấn đề, vui lòng:
1. Kiểm tra log lỗi
2. Chụp màn hình kết quả script
3. Cung cấp thông tin database version
4. Liên hệ admin để được hỗ trợ
