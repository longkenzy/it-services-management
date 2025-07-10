# IT Services Management - CRM System

## 📋 Mô tả dự án

Hệ thống CRM (Customer Relationship Management) để quản lý các task công việc của nhân viên IT Support trong phòng ban IT.

## 🛠️ Công nghệ sử dụng

- **Frontend**: HTML5, CSS3, JavaScript, jQuery
- **CSS Framework**: Bootstrap 5.3.0
- **Icons**: Font Awesome 6.4.0
- **Backend**: PHP (sẽ được phát triển)
- **Database**: MySQL/phpMyAdmin (sẽ được phát triển)

## 📁 Cấu trúc dự án

```
it-web-final/
├── index.html              # Trang đăng nhập chính
├── assets/
│   ├── css/
│   │   └── login.css       # Stylesheet cho trang đăng nhập
│   ├── js/
│   │   └── login.js        # JavaScript cho trang đăng nhập
│   └── images/             # Thư mục chứa hình ảnh
├── README.md               # Tài liệu hướng dẫn
└── (các file PHP sẽ được thêm sau)
```

## 🚀 Hướng dẫn chạy dự án

### 1. Chạy trực tiếp với trình duyệt
- Mở file `index.html` bằng trình duyệt web
- Hoặc sử dụng Live Server extension trong VS Code

### 2. Chạy với local server
```bash
# Sử dụng Python
python -m http.server 8000

# Sử dụng PHP
php -S localhost:8000

# Sử dụng Node.js (với http-server)
npx http-server
```

## 🔐 Thông tin đăng nhập demo

- **Email**: admin@itsupport.com
- **Password**: admin123

## ✨ Tính năng hiện tại

### Trang đăng nhập
- [x] Giao diện responsive (tương thích mọi thiết bị)
- [x] Validation form real-time
- [x] Toggle hiển thị/ẩn mật khẩu
- [x] Checkbox "Remember me"
- [x] Link "Forgot password"
- [x] Animation và hiệu ứng smooth
- [x] Loading state khi đăng nhập
- [x] Thông báo lỗi/thành công
- [x] Keyboard shortcuts (Enter, Escape)

### Thiết kế
- [x] Giao diện 2 cột: Logo bên trái, Form bên phải
- [x] Gradient background với hiệu ứng động
- [x] Icons Font Awesome
- [x] Bootstrap components
- [x] Responsive design (Mobile-first)

## 🎯 Tính năng sẽ phát triển

- [ ] Xử lý đăng nhập với PHP/MySQL
- [ ] Dashboard quản lý
- [ ] Quản lý nhân viên
- [ ] Quản lý task/ticket
- [ ] Báo cáo thống kê
- [ ] Hệ thống phân quyền
- [ ] API endpoints
- [ ] Notification system

## 📱 Responsive Design

Website được thiết kế responsive, tương thích với:
- Desktop (1200px+)
- Tablet (768px - 1199px)
- Mobile (< 768px)

## 🔧 Customization

### Thay đổi màu sắc chủ đạo
Chỉnh sửa trong file `assets/css/login.css`:
```css
:root {
    --primary-color: #3b82f6;
    --secondary-color: #2563eb;
    --gradient-start: #1e3c72;
    --gradient-end: #2a5298;
}
```

### Thay đổi animation
Tùy chỉnh các animation trong phần `@keyframes` của CSS file.

## 🐛 Debug & Console

Mở Developer Tools (F12) để xem:
- Thông tin demo credentials
- Log các sự kiện
- Lỗi JavaScript (nếu có)

## 📞 Hỗ trợ

Nếu gặp vấn đề, vui lòng:
1. Kiểm tra console log
2. Đảm bảo có kết nối internet (để load Bootstrap, Font Awesome)
3. Kiểm tra tương thích trình duyệt

## 📄 License

Dự án này được phát triển cho mục đích học tập và sử dụng nội bộ.

---

**Phiên bản**: 1.0.0  
**Ngày cập nhật**: $(date)  
**Tác giả**: IT Support Team 