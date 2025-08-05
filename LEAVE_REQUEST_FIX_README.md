# Khắc phục lỗi Leave Request - ID = 0 và Created_at = 0000-00-00

## Vấn đề
Sau khi deploy lên web thật, khi tạo đơn nghỉ phép mới:
- ID trong database = 0
- Created_at = 0000-00-00 00:00:00
- Các đơn tiếp theo cũng gặp vấn đề tương tự

## Nguyên nhân có thể
1. **Vấn đề với Auto Increment**: Cấu trúc bảng không đúng hoặc auto increment bị reset
2. **Vấn đề với Timezone**: MySQL không được cấu hình timezone đúng
3. **Vấn đề với SQL Mode**: Strict mode không được bật
4. **Vấn đề với Transaction**: Không có transaction hoặc rollback không đúng

## Cách khắc phục

### Bước 1: Chạy script debug
```bash
# Truy cập vào file debug để kiểm tra
http://your-domain.com/debug_leave_creation.php
```

### Bước 2: Chạy script sửa chữa
```bash
# Truy cập vào file sửa chữa
http://your-domain.com/fix_leave_requests.php
```

### Bước 3: Kiểm tra cấu hình database
Đảm bảo file `config/db.php` có các cài đặt sau:

```php
// Thiết lập timezone cho MySQL
$pdo->exec("SET time_zone = '+07:00'");

// Thiết lập strict mode
$pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
```

### Bước 4: Kiểm tra cấu trúc bảng
Chạy script SQL sau trong phpMyAdmin hoặc MySQL client:

```sql
-- Kiểm tra cấu trúc bảng
DESCRIBE leave_requests;

-- Kiểm tra auto increment
SHOW TABLE STATUS LIKE 'leave_requests';

-- Kiểm tra timezone
SELECT @@global.time_zone, @@session.time_zone, NOW() as current_time;
```

### Bước 5: Sửa chữa thủ công (nếu cần)

#### Sửa cấu trúc bảng:
```sql
ALTER TABLE `leave_requests` 
MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT,
MODIFY COLUMN `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
MODIFY COLUMN `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

-- Reset auto increment
ALTER TABLE `leave_requests` AUTO_INCREMENT = 1;
```

#### Sửa dữ liệu bị lỗi:
```sql
-- Sửa created_at = 0000-00-00
UPDATE `leave_requests` 
SET `created_at` = NOW() 
WHERE `created_at` = '0000-00-00 00:00:00' OR `created_at` IS NULL;

-- Xóa records có id = 0 (nếu có)
DELETE FROM `leave_requests` WHERE `id` = 0;
```

## Các thay đổi đã thực hiện

### 1. Sửa file `api/create_leave_request.php`:
- Thêm transaction để đảm bảo tính toàn vẹn dữ liệu
- Thêm explicit `created_at = NOW()` trong câu INSERT
- Thêm kiểm tra `lastInsertId()` có hợp lệ không
- Thêm rollback khi có lỗi

### 2. Tạo các file debug và sửa chữa:
- `debug_leave_creation.php`: Kiểm tra chi tiết vấn đề
- `fix_leave_requests.php`: Script sửa chữa tự động
- `database/fix_leave_requests_table.sql`: Script SQL sửa chữa

## Kiểm tra sau khi sửa chữa

1. **Tạo đơn nghỉ phép test**:
   - Đăng nhập vào hệ thống
   - Tạo một đơn nghỉ phép mới
   - Kiểm tra ID và created_at trong database

2. **Kiểm tra dữ liệu**:
```sql
SELECT id, request_code, created_at 
FROM leave_requests 
ORDER BY id DESC 
LIMIT 5;
```

3. **Kiểm tra auto increment**:
```sql
SHOW TABLE STATUS LIKE 'leave_requests';
```

## Lưu ý quan trọng

1. **Backup database** trước khi chạy script sửa chữa
2. **Kiểm tra quyền** của user database có đủ quyền ALTER TABLE
3. **Test trên môi trường staging** trước khi áp dụng lên production
4. **Monitor logs** để đảm bảo không có lỗi mới

## Liên hệ hỗ trợ

Nếu vẫn gặp vấn đề sau khi thực hiện các bước trên, vui lòng:
1. Chạy script debug và gửi kết quả
2. Kiểm tra error logs của web server
3. Kiểm tra MySQL error logs
4. Cung cấp thông tin về hosting environment 