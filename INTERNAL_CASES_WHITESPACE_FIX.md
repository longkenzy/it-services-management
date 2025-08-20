# Khắc phục vấn đề khoảng trắng thừa trong Case nội bộ

## Vấn đề
Trong trang Case nội bộ, các cột "Ghi chú" và "Mô tả chi tiết" có hiển thị khoảng trắng thừa ở trên đầu text. Điều này xảy ra do:

1. **Dữ liệu không được trim() khi lưu vào database**
2. **CSS `white-space: pre-wrap` giữ nguyên khoảng trắng**
3. **Dữ liệu hiện có trong database có khoảng trắng thừa**

## Nguyên nhân
- API `create_case.php` và `update_case.php` không trim() dữ liệu trước khi lưu
- CSS `.notes-content` và `.description-content` sử dụng `white-space: pre-wrap`
- Dữ liệu hiện có trong database có khoảng trắng thừa

## Cách khắc phục đã thực hiện

### 1. Sửa API tạo case (`api/create_case.php`)
```php
// Thêm trim() cho các trường text
$case_type = trim($input['case_type']);
$issue_title = trim($input['issue_title']);
$issue_description = trim($input['issue_description']);
$notes = trim($input['notes'] ?? '');
```

### 2. Sửa API cập nhật case (`api/update_case.php`)
```php
// Trim text fields to remove leading/trailing whitespace
$value = $input[$field];
if (in_array($field, ['case_type', 'issue_title', 'issue_description', 'notes'])) {
    $value = trim($value);
}
```

### 3. Sửa hiển thị dữ liệu (`internal_cases.php`)
```php
// Trim dữ liệu trước khi hiển thị
<?php if (!empty(trim($case['notes']))): ?>
    <span class="notes-content">
        <?php echo nl2br(htmlspecialchars(trim($case['notes']))); ?>
    </span>
<?php endif; ?>
```

### 4. Tạo script dọn dẹp dữ liệu (`clean_internal_cases_data.php`)
Script này sẽ:
- Tìm tất cả records có khoảng trắng thừa
- Hiển thị chi tiết các record cần dọn dẹp
- Thực hiện UPDATE để trim() dữ liệu
- Báo cáo kết quả

## Cách sử dụng

### Bước 1: Chạy script dọn dẹp
Truy cập: `http://your-domain.com/clean_internal_cases_data.php`

Script sẽ:
1. Kiểm tra kết nối database
2. Đếm số record cần dọn dẹp
3. Hiển thị chi tiết các record có vấn đề
4. Thực hiện dọn dẹp
5. Báo cáo kết quả

### Bước 2: Kiểm tra kết quả
Sau khi chạy script, vào trang Case nội bộ để kiểm tra:
- Các cột "Ghi chú" và "Mô tả chi tiết" không còn khoảng trắng thừa
- Dữ liệu hiển thị gọn gàng, không có dòng trống ở đầu

### Bước 3: Test tạo case mới
Tạo một case nội bộ mới để đảm bảo:
- Dữ liệu được trim() đúng cách
- Không có khoảng trắng thừa được lưu

## Lưu ý quan trọng

1. **Backup database trước khi chạy script dọn dẹp**
2. **Script chỉ ảnh hưởng đến các trường text, không ảnh hưởng đến dữ liệu khác**
3. **Sau khi sửa, tất cả case mới sẽ không có vấn đề khoảng trắng**
4. **CSS `white-space: pre-wrap` vẫn được giữ để hiển thị xuống dòng đúng cách**

## Kiểm tra sau khi sửa

1. **Dữ liệu hiện có**: Không còn khoảng trắng thừa
2. **Case mới**: Dữ liệu được trim() đúng cách
3. **Hiển thị**: Text gọn gàng, không có dòng trống ở đầu
4. **Xuống dòng**: Vẫn hoạt động bình thường với `nl2br()`

## Troubleshooting

### Nếu vẫn còn khoảng trắng
1. Kiểm tra xem script dọn dẹp đã chạy thành công chưa
2. Clear cache browser
3. Kiểm tra lại database xem có record nào bị bỏ sót không

### Nếu xuống dòng không hoạt động
1. Đảm bảo `nl2br()` vẫn được sử dụng
2. Kiểm tra CSS `white-space: pre-wrap` không bị thay đổi
3. Test với text có nhiều dòng

### Nếu có lỗi database
1. Kiểm tra quyền truy cập database
2. Đảm bảo bảng `internal_cases` tồn tại
3. Kiểm tra log lỗi PHP
