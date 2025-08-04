/**
 * IT Services Management - Dashboard JavaScript
 * Xử lý tương tác cho header và dashboard
 */

$(document).ready(function() {
    
    // ===== KHỞI TẠO CÁC BIẾN ===== //
    const navLinks = $('.navbar-nav .nav-link');
    const userDropdown = $('#userDropdown');
    const workDropdown = $('#workDropdown');
            

    
    // ===== NAVIGATION FUNCTIONALITY ===== //
    
    // Xử lý click menu items
    navLinks.on('click', function(e) {
        if (!$(this).hasClass('dropdown-toggle')) {
            const linkId = $(this).attr('id');
            
            // Chỉ ngăn chặn hành vi mặc định cho homeLink, cho phép staffLink hoạt động bình thường
            if (linkId === 'homeLink') {
                e.preventDefault();
                
                // Remove active class from all nav links
                navLinks.removeClass('active');
                
                // Add active class to clicked link
                $(this).addClass('active');
                
                const linkText = $(this).text().trim();
                
                // Xử lý navigation
                handleNavigation(linkId, linkText);
            }
            // staffLink sẽ sử dụng href để chuyển hướng bình thường
        }
    });
    
    function handleNavigation(linkId, linkText) {
        switch(linkId) {
            case 'homeLink':
                loadDashboard();
                break;
            case 'staffLink':
                loadStaffPage();
                break;
            default:
                showNotification(`Đang tải trang: ${linkText}`, 'info');
        }
        
        // Update breadcrumb
        updateBreadcrumb(linkText);
    }
    
    function loadDashboard() {
        // Redirect to dashboard page
        window.location.href = 'dashboard.php';
    }
    
    function loadStaffPage() {
        // Redirect to staff page
        window.location.href = 'staff.php';
    }
    
    // ===== DROPDOWN FUNCTIONALITY ===== //
    
    // XÓA HOÀN TOÀN event handler cho .dropdown-item
    // Chỉ bind event cho các item có data-section hoặc data-action
    $('[data-section], [data-action]').on('click', function(e) {
        e.preventDefault();
        const $this = $(this);
        const section = $this.data('section');
        const action = $this.data('action');
        const itemText = $this.text().trim();
        if (section) {
            handleWorkSection(section, itemText);
        } else if (action) {
            handleUserAction(action, itemText);
        }
    });
    
    function handleWorkSection(section, itemText) {
        // Remove active from nav links
        navLinks.removeClass('active');
        
        // Add active to work dropdown
        workDropdown.addClass('active');
        
        switch(section) {
            case 'internal-case':
                loadInternalCases();
                break;
            case 'deployment-case':
                loadDeploymentCases();
                break;
            case 'maintenance-case':
                loadMaintenanceCases();
                break;
        }
        
        updateBreadcrumb(itemText);
        showNotification(`Đang tải: ${itemText}`, 'info');
    }
    
    function handleUserAction(action, itemText) {
        switch(action) {
            case 'profile':
                showUserProfile();
                break;
            case 'settings':
                showUserSettings();
                break;
            case 'notifications':
                showNotifications();
                break;
            case 'change-password':
                showChangePasswordModal();
                break;
            case 'logout':
                performLogout();
                break;
        }
    }
    
    function loadInternalCases() {
        
        // Redirect to internal cases page
        setTimeout(function() {
            window.location.href = 'internal_cases.php';
        }, 500);
    }
    
    // Xóa các function placeholder không có nội dung
    
    function showChangePasswordModal() {
        
        $('#changePasswordModal').modal('show');
    }
    
    // Đã xóa đoạn logic logout trùng lặp. Chỉ giữ lại một nơi duy nhất xử lý logout.
    function performLogout() {
        showNotification('Đang đăng xuất...', 'info');
        
        setTimeout(function() {
            // Redirect to logout handler
            window.location.href = 'auth/logout.php';
        }, 1000);
    }
    
    // ===== BREADCRUMB FUNCTIONALITY ===== //
    
    function updateBreadcrumb(pageName) {
        const breadcrumb = $('.breadcrumb');
        const activeItem = breadcrumb.find('.breadcrumb-item.active');
        
        activeItem.text(pageName);
        
        // Update page title
        $('.page-title').text(pageName);
    }
    
    // ===== NOTIFICATION SYSTEM ===== //
    
    function showNotification(message, type = 'info') {
        if (type === 'info') {
            showInfo(message);
        } else if (type === 'success') {
            showSuccess(message);
        } else if (type === 'warning') {
            showWarning(message);
        } else if (type === 'danger') {
            showError(message);
        } else {
            showAlert(message, type);
        }
    }
    
    // ===== RESPONSIVE FUNCTIONALITY ===== //
    
    // Xử lý responsive navbar
    const navbarToggler = $('.navbar-toggler');
    
    navbarToggler.on('click', function() {
        setTimeout(function() {
            if ($('.navbar-collapse').hasClass('show')) {
                $('body').addClass('navbar-open');
            } else {
                $('body').removeClass('navbar-open');
            }
        }, 100);
    });
    
    // Đóng navbar khi click outside (mobile)
    $(document).on('click', function(e) {
        if ($(window).width() <= 991 && 
            !$(e.target).closest('.navbar').length && 
            $('.navbar-collapse').hasClass('show')) {
            navbarToggler.click();
        }
    });
    
    // ===== KEYBOARD SHORTCUTS ===== //
    
    $(document).on('keydown', function(e) {
        // Escape để đóng dropdown
        if (e.key === 'Escape') {
            $('.dropdown-menu').removeClass('show');
        }
    });
    
    // ===== SMOOTH ANIMATIONS ===== //
    
    function addSmoothAnimations() {
        // Animate dropdown items
        $('.dropdown-menu').on('show.bs.dropdown', function() {
            $(this).find('.dropdown-item').each(function(index) {
                $(this).css({
                    'animation-delay': (index * 0.1) + 's',
                    'animation': 'fadeInUp 0.3s ease-out forwards'
                });
            });
        });
        
        // Animate nav links
        navLinks.on('mouseenter', function() {
            $(this).addClass('nav-link-hover');
        });
        
        navLinks.on('mouseleave', function() {
            $(this).removeClass('nav-link-hover');
        });
    }
    
    // ===== UTILITY FUNCTIONS ===== //
    
    function isMobile() {
        return $(window).width() <= 767;
    }
    
    function isTablet() {
        return $(window).width() <= 991 && $(window).width() > 767;
    }
    
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
    
    // ===== INITIALIZATION ===== //
    
    function init() {
        addSmoothAnimations();
        

        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        
    }
    
    // ===== WINDOW RESIZE HANDLER ===== //
    
    $(window).on('resize', debounce(function() {
        // Handle responsive changes
        if ($(window).width() > 991) {
            $('body').removeClass('navbar-open');
            $('.navbar-collapse').removeClass('show');
        }
    }, 250));
    
    // ===== START APPLICATION ===== //
    
    init();
    
    // ===== ĐỔI MẬT KHẨU - ĐÃ XÓA VÌ TRÙNG LẶP VỚI DASHBOARD.PHP ===== //
    // Event handler đã được xử lý trong dashboard.php
    
    // ===== DEMO DATA CONSOLE LOG ===== //
    // Demo data removed for production
    
});

 