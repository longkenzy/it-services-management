# Hướng dẫn sửa lỗi Auto Increment cho bảng deployment_cases

## Vấn đề
Khi tạo deployment case mới, ID của case luôn bằng 0 thay vì tự động tăng. Đây là lỗi auto increment trong MySQL.

## Nguyên nhân có thể
1. Bảng `deployment_cases` không có AUTO_INCREMENT đúng cách
2. Auto increment counter bị reset về 0
3. Có dữ liệu với ID = 0 đã tồn tại
4. Cấu trúc cột `id` bị lỗi

## Cách sửa lỗi

### Phương pháp 1: Sử dụng nút trong giao diện (Khuyến nghị)
1. Đăng nhập vào hệ thống với quyền admin, IT hoặc IT Leader
2. Vào trang "Yêu cầu triển khai"
3. Mở modal chỉnh sửa một deployment request
4. Trong phần "QUẢN LÝ CASE TRIỂN KHAI", click nút **"Sửa lỗi Auto Increment"**
5. Xác nhận thao tác
6. Đợi hệ thống thực hiện và hiển thị kết quả

### Phương pháp 2: Chạy script PHP
1. Upload file `fix_deployment_cases_auto_increment.php` lên hosting
2. Truy cập: `https://your-domain.com/fix_deployment_cases_auto_increment.php`
3. Xem kết quả và thông báo

### Phương pháp 3: Chạy script SQL
1. Truy cập phpMyAdmin hoặc MySQL console
2. Chạy các lệnh SQL trong file `database/fix_deployment_cases_auto_increment.sql`
3. Thực hiện từng bước theo hướng dẫn trong file

## Các file liên quan

### Script sửa lỗi
- `fix_deployment_cases_auto_increment.php` - Script PHP chính
- `api/fix_deployment_cases_auto_increment.php` - API endpoint
- `database/fix_deployment_cases_auto_increment.sql` - Script SQL

### File đã cập nhật
- `api/create_deployment_case.php` - Thêm logging để debug
- `deployment_requests.php` - Thêm nút sửa lỗi

## Kiểm tra sau khi sửa
1. Tạo một deployment case mới
2. Kiểm tra ID của case có tự động tăng không
3. Xem log trong file `api/error_log.txt` và `api/fix_auto_increment.log`

## Lưu ý quan trọng
- **Backup database** trước khi thực hiện
- Chỉ chạy script sửa lỗi **một lần**
- Nếu vẫn gặp lỗi, kiểm tra log để debug
- Đảm bảo có quyền admin để thực hiện các thao tác database

## Troubleshooting

### Lỗi "Access denied"
- Kiểm tra quyền truy cập database
- Đảm bảo user có quyền ALTER TABLE

### Lỗi "Foreign key constraint"
- Kiểm tra dữ liệu trong bảng `deployment_requests` và `staffs`
- Đảm bảo các khóa ngoại tồn tại

### Lỗi "Duplicate entry"
- Kiểm tra dữ liệu trùng lặp trong bảng
- Xóa dữ liệu test nếu có

## Liên hệ hỗ trợ
Nếu gặp vấn đề, vui lòng:
1. Kiểm tra log files
2. Chụp màn hình lỗi
3. Liên hệ admin để được hỗ trợ
