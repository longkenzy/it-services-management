# STAFF RESIGNED FEATURE SUMMARY

## Mục đích
Thêm trường "Đã nghỉ" vào modal xem thông tin nhân sự để đánh dấu nhân viên đã nghỉ việc.

## Các thay đổi đã thực hiện

### 1. Database Changes
- **File**: `database/add_resigned_column.sql`
- **Thay đổi**: Thêm cột `resigned` vào bảng `staffs`
- **Kiểu dữ liệu**: `TINYINT(1)` (0 = chưa nghỉ, 1 = đã nghỉ)
- **Mặc định**: 0 (chưa nghỉ)

### 2. Frontend Changes

#### 2.1. Modal Form (staff.php)
- **Vị trí**: Phần CÔNG VIỆC trong modal thêm/sửa nhân sự, **ngay sau trường "Ngày vào làm"**
- **Thay đổi**: Thêm checkbox "Đã nghỉ việc"
- **Code**:
```html
<div class="mb-2">
    <div class="row align-items-center">
        <div class="col-4">
            <label class="form-label mb-0">Đã nghỉ việc</label>
        </div>
        <div class="col-8">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="resigned" id="resigned" value="1">
                <label class="form-check-label" for="resigned">
                    Đánh dấu nhân viên đã nghỉ việc
                </label>
            </div>
        </div>
    </div>
</div>
```

#### 2.2. JavaScript (assets/js/staff.js)
- **Hàm**: `showEditStaffModal(staff)`
- **Thay đổi**: Thêm xử lý trường `resigned`
- **Code**:
```javascript
// Đã nghỉ việc
$('#resigned').prop('checked', staff.resigned == 1);
```

### 3. Backend Changes

#### 3.1. API Get Staffs (api/get_staffs.php)
- **Thay đổi**: Thêm trường `resigned` vào SELECT query
- **Thay đổi**: Thêm trường `resigned` vào response data

#### 3.2. Add Staff (add_staff.php)
- **Thay đổi**: Thêm trường `resigned` vào INSERT query
- **Thay đổi**: Xử lý giá trị checkbox trong form data
- **Code**:
```php
isset($_POST['resigned']) ? 1 : 0
```

#### 3.3. Update Staff (update_staff.php)
- **Thay đổi**: Thêm trường `resigned` vào UPDATE query
- **Thay đổi**: Xử lý giá trị checkbox trong form data
- **Code**:
```php
'resigned' => isset($_POST['resigned']) ? 1 : 0
```

## Cách sử dụng

### 1. Thêm nhân sự mới
- Mở modal "Thêm nhân sự mới"
- Điền thông tin bắt buộc
- Tích vào checkbox "Đã nghỉ việc" nếu nhân viên đã nghỉ
- Lưu thông tin

### 2. Xem/Chỉnh sửa nhân sự
- Click vào nút "Xem" hoặc "Sửa" trong danh sách nhân sự
- Modal sẽ hiển thị trạng thái "Đã nghỉ việc"
- Có thể thay đổi trạng thái này
- Lưu thay đổi

### 3. Database
- Giá trị 0: Nhân viên chưa nghỉ việc
- Giá trị 1: Nhân viên đã nghỉ việc

## Files đã thay đổi

### Database
- ✅ `database/add_resigned_column.sql` (mới tạo)

### Frontend
- ✅ `staff.php` - Thêm checkbox vào modal
- ✅ `assets/js/staff.js` - Xử lý trường resigned

### Backend
- ✅ `api/get_staffs.php` - Thêm trường resigned vào API
- ✅ `add_staff.php` - Xử lý thêm nhân sự với trường resigned
- ✅ `update_staff.php` - Xử lý cập nhật nhân sự với trường resigned

## Lưu ý quan trọng
1. **Chạy SQL script** để thêm cột `resigned` vào database
2. **Kiểm tra quyền truy cập** - chỉ admin/manager mới có thể thay đổi trạng thái này
3. **Backup database** trước khi chạy SQL script
4. **Test kỹ** tính năng thêm/sửa nhân sự sau khi triển khai

## Status
✅ **HOÀN THÀNH** - Tính năng "Đã nghỉ" đã được thêm vào hệ thống quản lý nhân sự. 