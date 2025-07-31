# Slider Setup Guide

## Tổng quan
Dashboard đã được cập nhật với slider hình ảnh tự động chạy ở đầu trang.

## Cách sử dụng

### 1. Upload hình ảnh
1. Truy cập thư mục: `assets/images/slider/`
2. Upload các hình ảnh với tên theo format:
   - `image-slide01.jpg`
   - `image-slide02.jpg`
   - `image-slide03.jpg`
   - ... (tối đa 10 hình)

### 2. Định dạng hình ảnh hỗ trợ
- **JPG/JPEG**: `image-slide01.jpg`
- **PNG**: `image-slide01.png`
- **WebP**: `image-slide01.webp`

### 3. Kích thước khuyến nghị
- **Chiều rộng**: 1200px - 1920px
- **Chiều cao**: 400px (tỷ lệ 3:1 hoặc 4:1)
- **Định dạng**: Landscape (ngang)

## Tính năng slider

### Tự động chạy
- ✅ Chuyển slide mỗi 5 giây
- ✅ Tạm dừng khi hover chuột
- ✅ Tiếp tục khi rời chuột

### Điều khiển thủ công
- ✅ Nút mũi tên trái/phải
- ✅ Dots indicator ở dưới
- ✅ Click vào dot để chuyển slide

### Hiệu ứng
- ✅ Fade transition mượt mà
- ✅ Overlay gradient với text
- ✅ Responsive trên mọi thiết bị

## Cấu trúc thư mục
```
assets/
└── images/
    └── slider/
        ├── image-slide01.jpg
        ├── image-slide02.jpg
        ├── image-slide03.jpg
        └── ...
```

## Fallback
Nếu không có hình ảnh nào được upload:
- Hiển thị 3 slides mẫu với placeholder
- Nội dung về Smart Services
- Vẫn có đầy đủ tính năng slider

## Tùy chỉnh

### Thay đổi thời gian chuyển slide
Trong file `dashboard.php`, tìm dòng:
```javascript
}, 5000); // Change slide every 5 seconds
```
Thay đổi số `5000` (milliseconds) theo ý muốn.

### Thay đổi nội dung text
Trong function `createDefaultSlides()`, chỉnh sửa:
```javascript
{
    src: 'assets/images/placeholder-news.svg',
    title: 'Smart Services',
    description: 'Giải pháp công nghệ toàn diện cho doanh nghiệp'
}
```

### Thay đổi style
CSS cho slider nằm trong `<style>` tag của `dashboard.php`:
- `.hero-slider`: Kích thước slider
- `.slide-overlay`: Màu overlay
- `.slide-content`: Style cho text

## Troubleshooting

### Slider không hiển thị
1. Kiểm tra tên file hình ảnh đúng format
2. Kiểm tra đường dẫn thư mục
3. Kiểm tra console browser có lỗi JavaScript

### Hình ảnh không load
1. Kiểm tra quyền truy cập file
2. Kiểm tra định dạng file hỗ trợ
3. Kiểm tra kích thước file (không quá lớn)

### Slider không tự động chạy
1. Kiểm tra JavaScript console
2. Kiểm tra jQuery đã load
3. Kiểm tra function `initSlider()` được gọi

## Liên hệ
Nếu gặp vấn đề, vui lòng liên hệ IT Support Team. 