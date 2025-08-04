/**
 * IT Services Management - Login Page JavaScript
 * Xử lý các tương tác và validation cho trang đăng nhập
 */

$(document).ready(function() {
    
    // ===== KHỞI TẠO CÁC BIẾN ===== //
    const loginForm = $('#loginForm');
    const emailInput = $('#email');
    const passwordInput = $('#password');
    const togglePasswordBtn = $('#togglePassword');
    const rememberMeCheckbox = $('#rememberMe');
    const loginBtn = $('.login-btn');
    const btnText = $('.btn-text');
    const spinner = $('.spinner-border');
    
    // ===== TOGGLE PASSWORD VISIBILITY ===== //
    togglePasswordBtn.on('click', function() {
        const passwordField = passwordInput[0];
        const icon = $(this).find('i');
        
        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
            $(this).attr('title', 'Ẩn mật khẩu');
        } else {
            passwordField.type = 'password';
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
            $(this).attr('title', 'Hiện mật khẩu');
        }
    });
    
    // ===== INPUT VALIDATION ===== //
    function validateUsernameOrEmail(input) {
        // Cho phép cả username và email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        // Username: chỉ chứa chữ cái, số, dấu gạch dưới, dấu chấm, ít nhất 3 ký tự
        const usernameRegex = /^[a-zA-Z0-9._]{3,}$/;
        
        return emailRegex.test(input) || usernameRegex.test(input);
    }
    
    function validatePassword(password) {
        return password.length >= 6;
    }
    
    function showInputError(inputElement, message) {
        const inputGroup = inputElement.closest('.input-group');
        inputGroup.addClass('error');
        
        // Xóa thông báo lỗi cũ nếu có
        inputGroup.next('.error-message').remove();
        
        // Thêm thông báo lỗi mới
        inputGroup.after(`<div class="error-message text-danger small mt-1">${message}</div>`);
    }
    
    function clearInputError(inputElement) {
        const inputGroup = inputElement.closest('.input-group');
        inputGroup.removeClass('error');
        inputGroup.next('.error-message').remove();
    }
    
    // ===== REAL-TIME VALIDATION ===== //
    emailInput.on('input blur', function() {
        const input = $(this).val().trim();
        
        if (input === '') {
            clearInputError($(this));
        } else if (!validateUsernameOrEmail(input)) {
            showInputError($(this), 'Vui lòng nhập username hoặc email hợp lệ');
        } else {
            clearInputError($(this));
        }
    });
    
    passwordInput.on('input blur', function() {
        const password = $(this).val();
        
        if (password === '') {
            clearInputError($(this));
        } else if (!validatePassword(password)) {
            showInputError($(this), 'Mật khẩu phải có ít nhất 6 ký tự');
        } else {
            clearInputError($(this));
        }
    });
    
    // ===== FORM SUBMISSION ===== //
    loginForm.on('submit', function(e) {
        e.preventDefault();
        
        const usernameOrEmail = emailInput.val().trim();
        const password = passwordInput.val();
        let isValid = true;
        
        // Validate username or email
        if (usernameOrEmail === '') {
            showInputError(emailInput, 'Vui lòng nhập username hoặc email');
            isValid = false;
        } else if (!validateUsernameOrEmail(usernameOrEmail)) {
            showInputError(emailInput, 'Vui lòng nhập username hoặc email hợp lệ');
            isValid = false;
        } else {
            clearInputError(emailInput);
        }
        
        // Validate password
        if (password === '') {
            showInputError(passwordInput, 'Vui lòng nhập mật khẩu');
            isValid = false;
        } else if (!validatePassword(password)) {
            showInputError(passwordInput, 'Mật khẩu phải có ít nhất 6 ký tự');
            isValid = false;
        } else {
            clearInputError(passwordInput);
        }
        
        // Nếu validation thành công
        if (isValid) {
            performLogin(usernameOrEmail, password);
        }
    });
    
    // ===== PERFORM LOGIN ===== //
    function performLogin(usernameOrEmail, password) {
        // Hiển thị loading state
        showLoadingState();
        
        // Tạo dữ liệu gửi đến server
        const loginData = {
            username: usernameOrEmail, // Có thể là username hoặc email
            password: password,
            remember: rememberMeCheckbox.is(':checked')
        };
        
        // Gửi AJAX request đến server
        $.ajax({
            url: 'auth/login.php',
            method: 'POST',
            dataType: 'json',
            data: JSON.stringify(loginData),
            contentType: 'application/json',
            timeout: 10000, // 10 giây timeout
            success: function(response) {
                hideLoadingState();
                
                if (response.success) {
                    // Đăng nhập thành công
                    showSuccessMessage(response.message || 'Đăng nhập thành công!');
                    
                    // Lưu thông tin remember me
                    if (rememberMeCheckbox.is(':checked')) {
                        localStorage.setItem('rememberMe', 'true');
                        localStorage.setItem('savedUsername', usernameOrEmail);
                    } else {
                        localStorage.removeItem('rememberMe');
                        localStorage.removeItem('savedUsername');
                    }
                    
                    // Chuyển hướng sau 1.5 giây
                    setTimeout(function() {
                        window.location.href = response.redirect || 'dashboard.php';
                    }, 1500);
                    
                } else {
                    // Đăng nhập thất bại
                    showErrorMessage(response.message || 'Đăng nhập thất bại!');
                }
            },
            error: function(xhr, status, error) {
                hideLoadingState();
                
                let errorMessage = 'Có lỗi xảy ra khi đăng nhập!';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (status === 'timeout') {
                    errorMessage = 'Kết nối quá chậm. Vui lòng thử lại!';
                } else if (status === 'error') {
                    errorMessage = 'Không thể kết nối đến server!';
                }
                
                showErrorMessage(errorMessage);
                
            }
        });
    }
    
    // ===== LOADING STATES ===== //
    function showLoadingState() {
        loginBtn.addClass('loading');
        loginBtn.prop('disabled', true);
        emailInput.prop('disabled', true);
        passwordInput.prop('disabled', true);
        togglePasswordBtn.prop('disabled', true);
    }
    
    function hideLoadingState() {
        loginBtn.removeClass('loading');
        loginBtn.prop('disabled', false);
        emailInput.prop('disabled', false);
        passwordInput.prop('disabled', false);
        togglePasswordBtn.prop('disabled', false);
    }
    
    // ===== NOTIFICATION MESSAGES ===== //
    function showSuccessMessage(message) {
        hideLoadingState();
        showSuccess(message);
    }
    
    function showErrorMessage(message) {
        showError(message);
    }
    
    function showInfoMessage(message) {
        showInfo(message);
    }
    
    // ===== REMEMBER ME FUNCTIONALITY ===== //
    function loadRememberedData() {
        if (localStorage.getItem('rememberMe') === 'true') {
            const savedUsername = localStorage.getItem('savedUsername');
            if (savedUsername) {
                emailInput.val(savedUsername);
                rememberMeCheckbox.prop('checked', true);
            }
        }
    }
    

    
    // ===== KEYBOARD SHORTCUTS ===== //
    $(document).on('keydown', function(e) {
        // Enter key để submit form
        if (e.key === 'Enter' && (emailInput.is(':focus') || passwordInput.is(':focus'))) {
            loginForm.submit();
        }
        
        // Escape key để clear form
        if (e.key === 'Escape') {
            clearForm();
        }
    });
    
    function clearForm() {
        emailInput.val('');
        passwordInput.val('');
        rememberMeCheckbox.prop('checked', false);
        clearInputError(emailInput);
        clearInputError(passwordInput);
        // Xóa tất cả toast notifications
        if (typeof alertSystem !== 'undefined') {
            alertSystem.hideAll();
        }
    }
    
    // ===== SMOOTH ANIMATIONS ===== //
    function addSmoothAnimations() {
        // Animate input focus
        $('.form-control').on('focus', function() {
            $(this).closest('.input-group').addClass('focused');
        });
        
        $('.form-control').on('blur', function() {
            $(this).closest('.input-group').removeClass('focused');
        });
        
        // Animate button clicks
        $('.login-btn').on('mousedown', function() {
            $(this).addClass('clicked');
        });
        
        $('.login-btn').on('mouseup mouseleave', function() {
            $(this).removeClass('clicked');
        });
    }
    
    // ===== KHỞI TẠO ===== //
    function init() {
        loadRememberedData();
        addSmoothAnimations();
        
        // Focus vào email input khi trang load
        setTimeout(function() {
            emailInput.focus();
        }, 500);
        

    }
    
    // Khởi tạo ứng dụng
    init();
    
    // ===== DEMO CREDENTIALS INFO ===== //
    // Demo credentials: admin / 123456
    
});

// ===== ADDITIONAL UTILITY FUNCTIONS ===== //

/**
 * Debounce function để tối ưu performance
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Check if device is mobile
 */
function isMobile() {
    return window.innerWidth <= 768;
}

/**
 * Format error messages
 */
function formatErrorMessage(error) {
    if (typeof error === 'string') {
        return error;
    }
    
    if (error.message) {
        return error.message;
    }
    
    return 'Đã xảy ra lỗi không xác định';
} 