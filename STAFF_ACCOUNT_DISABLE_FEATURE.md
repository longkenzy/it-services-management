# Tính năng Vô hiệu hóa Tài khoản cho Nhân sự Đã Nghỉ

## Tổng quan
Tính năng này đảm bảo rằng những nhân sự đã nghỉ việc (resigned = 1) sẽ không thể đăng nhập vào hệ thống nữa. Tài khoản của họ sẽ bị vô hiệu hóa tự động.

## Các thay đổi đã thực hiện

### 1. Cập nhật hệ thống đăng nhập (`auth/login.php`)
- Thêm kiểm tra trạng thái `resigned` khi đăng nhập
- Nếu nhân sự đã nghỉ (resigned = 1), hệ thống sẽ từ chối đăng nhập
- Hiển thị thông báo lỗi rõ ràng: "Tài khoản đã bị vô hiệu hóa do nhân sự đã nghỉ việc"

### 2. Cập nhật giao diện quản lý nhân sự (`staff.php`)
- Thêm cột "Tài khoản" mới trong bảng nhân sự
- Hiển thị trạng thái tài khoản: "Hoạt động" hoặc "Bị khóa"
- Thêm cảnh báo khi đánh dấu nhân sự đã nghỉ

### 3. Cập nhật JavaScript (`assets/js/staff.js`)
- Hiển thị thông báo cảnh báo khi checkbox "Đã nghỉ việc" được chọn
- Thêm thông báo trong modal xem chi tiết nhân sự đã nghỉ
- Cập nhật hiển thị badge trạng thái tài khoản

### 4. Cập nhật API (`api/get_staffs.php`)
- Thêm trường `username` và `role` vào response
- Đảm bảo trường `resigned` được trả về đầy đủ

### 5. Cập nhật xử lý thêm/sửa nhân sự
- **`add_staff.php`**: Thêm thông báo khi tạo nhân sự đã nghỉ
- **`update_staff.php`**: Thêm thông báo khi cập nhật trạng thái nghỉ việc

### 6. Cập nhật CSS (`assets/css/staff.css`)
- Tùy chỉnh hiển thị badge trạng thái tài khoản
- Thêm styling cho cảnh báo và thông báo

## Cách hoạt động

### Khi đăng nhập:
1. Hệ thống kiểm tra username và password
2. Nếu thông tin đúng, kiểm tra trạng thái `resigned`
3. Nếu `resigned = 1`, từ chối đăng nhập và hiển thị thông báo lỗi
4. Nếu `resigned = 0` hoặc NULL, cho phép đăng nhập bình thường

### Khi quản lý nhân sự:
1. **Thêm nhân sự mới**: Nếu đánh dấu "Đã nghỉ việc", tài khoản sẽ bị vô hiệu hóa ngay lập tức
2. **Cập nhật nhân sự**: Khi thay đổi trạng thái từ hoạt động sang nghỉ việc, tài khoản sẽ bị vô hiệu hóa
3. **Hiển thị thông tin**: Bảng nhân sự hiển thị rõ ràng trạng thái tài khoản

## Giao diện người dùng

### Trong bảng nhân sự:
- Cột "Trạng thái": Hiển thị "Hoạt động" hoặc "Đã nghỉ"
- Cột "Tài khoản": Hiển thị "Hoạt động" hoặc "Bị khóa"

### Trong modal thêm/sửa nhân sự:
- Cảnh báo khi đánh dấu "Đã nghỉ việc"
- Thông báo rõ ràng về việc tài khoản sẽ bị vô hiệu hóa

### Khi đăng nhập:
- Thông báo lỗi rõ ràng nếu tài khoản đã bị vô hiệu hóa

## Bảo mật

- Kiểm tra trạng thái `resigned` được thực hiện ở server-side
- Log được ghi lại khi có người cố gắng đăng nhập bằng tài khoản đã bị vô hiệu hóa
- Thông báo lỗi không tiết lộ thông tin nhạy cảm

## Tương thích

- Tính năng này tương thích với tất cả các vai trò hiện có
- Không ảnh hưởng đến dữ liệu nhân sự hiện tại
- Có thể dễ dàng bật/tắt bằng cách thay đổi trạng thái `resigned`

## Lưu ý

- Nhân sự đã nghỉ vẫn có thể được xem và chỉnh sửa thông tin (chỉ admin/manager)
- Dữ liệu nhân sự không bị xóa, chỉ tài khoản đăng nhập bị vô hiệu hóa
- Có thể khôi phục tài khoản bằng cách bỏ đánh dấu "Đã nghỉ việc" 