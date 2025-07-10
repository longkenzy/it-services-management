

# IT Services Management - CRM System

## 📋 Project Description

A CRM (Customer Relationship Management) system for managing the daily tasks and operations of IT Support staff within the IT department.

## 🛠️ Technologies Used

* **Frontend**: HTML5, CSS3, JavaScript, jQuery
* **CSS Framework**: Bootstrap 5.3.0
* **Icons**: Font Awesome 6.4.0
* **Backend**: PHP (to be developed)
* **Database**: MySQL/phpMyAdmin (to be developed)

## 📁 Project Structure

```
it-web-final/
├── index.html              # Main login page
├── assets/
│   ├── css/
│   │   └── login.css       # Stylesheet for login page
│   ├── js/
│   │   └── login.js        # JavaScript for login functionality
│   └── images/             # Image assets
├── README.md               # Project documentation
└── (PHP files to be added later)
```

## 🚀 How to Run the Project

### 1. Run directly in browser

* Open `index.html` using any modern web browser
* Or use the **Live Server** extension in VS Code

### 2. Run on a local server

```bash
# Using Python
python -m http.server 8000

# Using PHP
php -S localhost:8000

# Using Node.js (http-server)
npx http-server
```

## 🔐 Demo Login Credentials

* **Email**: [admin@itsupport.com](mailto:admin@itsupport.com)
* **Password**: admin123

## ✨ Current Features

### Login Page

* [x] Fully responsive layout
* [x] Real-time form validation
* [x] Toggle password visibility
* [x] "Remember me" checkbox
* [x] "Forgot password" link
* [x] Smooth animations and transitions
* [x] Loading state on login
* [x] Success/error messages
* [x] Keyboard shortcuts (Enter, Escape)

### Design

* [x] Two-column layout: Logo (left), Form (right)
* [x] Animated gradient background
* [x] Font Awesome icons
* [x] Bootstrap components
* [x] Mobile-first responsive design

## 🎯 Upcoming Features

* [ ] PHP/MySQL login functionality
* [ ] Admin dashboard
* [ ] Employee management
* [ ] Task/ticket management
* [ ] Reporting and statistics
* [ ] Role-based access control
* [ ] API endpoints
* [ ] Notification system

## 📱 Responsive Design

The website is responsive and compatible with:

* Desktop (1200px+)
* Tablet (768px – 1199px)
* Mobile (< 768px)

## 🔧 Customization

### Change primary theme colors

Edit the following variables in `assets/css/login.css`:

```css
:root {
    --primary-color: #3b82f6;
    --secondary-color: #2563eb;
    --gradient-start: #1e3c72;
    --gradient-end: #2a5298;
}
```

### Modify animations

You can customize animations in the `@keyframes` section of the CSS file.

## 🐛 Debug & Console

Open Developer Tools (F12) to:

* View demo credentials
* Monitor event logs
* Detect JavaScript errors (if any)

## 📞 Support

If you encounter issues, please:

1. Check the browser console log
2. Ensure you have an internet connection (to load Bootstrap, Font Awesome)
3. Confirm browser compatibility

## 📄 License

This project is developed for educational and internal use only.

---

**Version**: 1.0.0
**Last Updated**: \$(date)
**Author**: IT Support Team
