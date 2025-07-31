# Sửa lỗi Session Management - IT Services Management

## Vấn đề đã được báo cáo
Khi đăng nhập trên trình duyệt và đã vào được website, khi mở tab mới vào web thì bị bắt phải đăng nhập lại. Điều này là sai và cần được sửa.

## Nguyên nhân gốc rễ
1. **Cấu hình session không đúng**: Session timeout quá ngắn (30 phút) và cấu hình session cookie không tối ưu
2. **Quản lý session không nhất quán**: Một số file gọi `session_start()` riêng lẻ thay vì sử dụng session management tập trung
3. **Logout function có vấn đề**: File `auth/logout.php` có 2 lần gọi `session_destroy()` gây xung đột
4. **Sử dụng session variables trực tiếp**: Nhiều file sử dụng `$_SESSION['user_id']` thay vì sử dụng các function wrapper

## Các thay đổi đã thực hiện

### 1. Cải thiện cấu hình session (`includes/session.php`)
- Tăng session timeout từ 30 phút lên 1 giờ (3600 giây)
- Thêm cấu hình session cookie tối ưu:
  - `session.cookie_lifetime = 0` (session cookie tồn tại cho đến khi browser đóng)
  - `session.gc_maxlifetime = 3600` (1 giờ)
  - `session.use_strict_mode = 1`
  - `session.use_cookies = 1`
  - `session.use_only_cookies = 1`
  - `session.cookie_httponly = 1`
  - `session.cookie_samesite = 'Lax'`

### 2. Sửa file logout (`auth/logout.php`)
- Loại bỏ việc gọi `session_start()` và `session_destroy()` trùng lặp
- Chỉ sử dụng function `logout()` từ session management

### 3. Chuẩn hóa các file chính
- **`workspace.php`**: Sử dụng `requireLogin()` và `getCurrentUser()` thay vì kiểm tra session trực tiếp
- **`update_staff.php`**: Sử dụng `requireAdmin()` và `getCurrentUser()`
- **`change_password.php`**: Sử dụng `requireLogin()` và `getCurrentUser()`

### 4. Sửa tất cả các file API
- Thay thế `session_start()` bằng `require_once '../includes/session.php'`
- Thay thế `if (!isset($_SESSION['user_id']))` bằng `if (!isLoggedIn())`
- Thay thế `$_SESSION['user_id']` bằng `getCurrentUserId()`
- Thay thế `$_SESSION['username']` bằng `getCurrentUsername()`
- Thay thế `$_SESSION['role']` bằng `getCurrentUserRole()`
- Thay thế `$_SESSION['fullname']` bằng `getCurrentUserFullname()`

## Các file đã được sửa

### Files chính:
- `includes/session.php` - Cải thiện cấu hình session
- `auth/logout.php` - Sửa logout function
- `workspace.php` - Chuẩn hóa session management
- `update_staff.php` - Chuẩn hóa session management
- `change_password.php` - Chuẩn hóa session management

### Files API (32 files):
- `api/get_staff_list.php`
- `api/get_maintenance_request.php`
- `api/update_maintenance_request.php`
- `api/update_maintenance_task.php`
- `api/update_maintenance_case.php`
- `api/update_deployment_case.php`
- `api/update_case.php`
- `api/mark_deployment_case_completed.php`
- `api/get_workspace_tasks.php`
- `api/get_partner_contacts.php`
- `api/get_next_request_number.php`
- `api/get_next_maintenance_task_number.php`
- `api/get_next_maintenance_request_number.php`
- `api/get_next_maintenance_case_number.php`
- `api/get_next_case_number.php`
- `api/get_maintenance_task_details.php`
- `api/get_maintenance_requests.php`
- `api/get_maintenance_case_details.php`
- `api/get_maintenance_cases.php`
- `api/get_deployment_cases.php`
- `api/get_case_details.php`
- `api/delete_maintenance_task.php`
- `api/delete_maintenance_request.php`
- `api/delete_maintenance_case.php`
- `api/delete_case.php`
- `api/delete_deployment_case.php`
- `api/create_maintenance_task.php`
- `api/create_maintenance_request.php`
- `api/create_maintenance_case.php`
- `api/create_deployment_case.php`
- `api/create_case.php`

## Kết quả mong đợi
1. **Session được duy trì giữa các tab**: Khi đăng nhập ở một tab, các tab mới sẽ không yêu cầu đăng nhập lại
2. **Session timeout dài hơn**: Session sẽ tồn tại trong 1 giờ thay vì 30 phút
3. **Quản lý session nhất quán**: Tất cả các file đều sử dụng cùng một cách quản lý session
4. **Bảo mật tốt hơn**: Session cookie được cấu hình với các tùy chọn bảo mật

## Cách test
1. Đăng nhập vào website
2. Mở tab mới và truy cập vào website
3. Kiểm tra xem có phải đăng nhập lại không
4. Sử dụng file `test_session.php` để kiểm tra thông tin session

## Lưu ý
- Các thay đổi này sẽ không ảnh hưởng đến dữ liệu hiện có
- Session cũ sẽ bị mất khi deploy, người dùng sẽ cần đăng nhập lại
- Nếu vẫn có vấn đề, có thể cần kiểm tra cấu hình PHP và web server 