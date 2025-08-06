# Tóm tắt cập nhật vai trò mới

## Các vai trò mới đã thêm:
- **HR Leader** (`hr_leader`)
- **Sale Leader** (`sale_leader`) 
- **IT Leader** (`it_leader`)

## Files đã được cập nhật:

### 1. Database
- `database/update_role_column.sql` - Cập nhật ENUM để bao gồm các vai trò mới
- `api/update_role_column.php` - Script PHP để cập nhật database

### 2. Frontend
- `staff.php` - Thêm các option mới vào modal tạo nhân sự

### 3. Backend Logic
- `api/create_leave_request.php` - Cập nhật logic gửi thông báo cho admin và leader
- `api/approve_leave_request.php` - Cập nhật logic phê duyệt để bao gồm các leader
- `assets/js/leave_management.js` - Cập nhật logic phân quyền trong JavaScript

## Quyền hạn của các vai trò mới:

### HR Leader (`hr_leader`)
- Có thể phê duyệt đơn nghỉ phép (cấp 1)
- Nhận thông báo khi có đơn nghỉ phép mới
- Có thể tạo và quản lý nhân sự

### Sale Leader (`sale_leader`)
- Có thể phê duyệt đơn nghỉ phép (cấp 1)
- Nhận thông báo khi có đơn nghỉ phép mới
- Có thể tạo và quản lý nhân sự

### IT Leader (`it_leader`)
- Có thể phê duyệt đơn nghỉ phép (cấp 1)
- Nhận thông báo khi có đơn nghỉ phép mới
- Có thể tạo và quản lý nhân sự

## Quy trình phê duyệt đơn nghỉ phép:
1. **Cấp 1**: Admin hoặc Leader (HR Leader, Sale Leader, IT Leader) phê duyệt
2. **Cấp 2**: HR phê duyệt cuối cùng

## Test Results:
✅ Database đã được cập nhật thành công
✅ Các vai trò mới đã được thêm vào ENUM
✅ Modal tạo nhân sự đã được cập nhật
✅ Logic phê duyệt đã được cập nhật
✅ JavaScript phân quyền đã được cập nhật

## Lưu ý:
- Các vai trò mới có quyền tương đương với Admin trong việc phê duyệt đơn nghỉ phép
- HR vẫn là vai trò duy nhất có thể phê duyệt cuối cùng (cấp 2)
- Tất cả các vai trò leader đều có thể tạo và quản lý nhân sự 