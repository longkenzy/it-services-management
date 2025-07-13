/**
 * IT Services Management - Dashboard JavaScript
 * Xử lý tương tác cho header và dashboard
 */

$(document).ready(function() {
    
    // ===== KHỞI TẠO CÁC BIẾN ===== //
    const searchInput = $('#searchInput');
    const searchForm = $('.search-form');
    const navLinks = $('.navbar-nav .nav-link');
    const userDropdown = $('#userDropdown');
    const workDropdown = $('#workDropdown');
    
    // ===== SEARCH FUNCTIONALITY ===== //
    
    // Xử lý tìm kiếm
    searchForm.on('submit', function(e) {
        e.preventDefault();
        performSearch();
    });
    
    // Tìm kiếm real-time (debounced)
    let searchTimeout;
    searchInput.on('input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val().trim();
        
        if (query.length >= 2) {
            searchTimeout = setTimeout(function() {
                performLiveSearch(query);
            }, 300);
        } else {
            hideLiveSearchResults();
        }
    });
    
    function performSearch() {
        const query = searchInput.val().trim();
        
        if (query === '') {
            showNotification('Vui lòng nhập từ khóa tìm kiếm', 'warning');
            return;
        }
        
        // Hiển thị loading
        showSearchLoading();
        
        // Simulate API call
        setTimeout(function() {
            hideSearchLoading();
            
            // Demo kết quả tìm kiếm
            const results = simulateSearchResults(query);
            displaySearchResults(results);
            

        }, 1000);
    }
    
    function performLiveSearch(query) {
        // Tìm kiếm live không hiển thị loading
        const results = simulateSearchResults(query);
        showLiveSearchResults(results);
    }
    
    function simulateSearchResults(query) {
        const mockData = [
            { type: 'case', title: 'Case #001 - Lỗi máy in phòng kế toán', status: 'open' },
            { type: 'case', title: 'Case #002 - Cài đặt phần mềm Office', status: 'in-progress' },
            { type: 'case', title: 'Case #003 - Bảo trì server', status: 'closed' },
            { type: 'staff', title: 'Nguyễn Văn A - IT Support', department: 'IT' },
            { type: 'staff', title: 'Trần Thị B - IT Manager', department: 'IT' },
        ];
        
        return mockData.filter(item => 
            item.title.toLowerCase().includes(query.toLowerCase())
        );
    }
    
    function showSearchLoading() {
        const searchBtn = searchForm.find('.btn');
        searchBtn.html('<i class="fas fa-spinner fa-spin"></i>');
        searchBtn.prop('disabled', true);
    }
    
    function hideSearchLoading() {
        const searchBtn = searchForm.find('.btn');
        searchBtn.html('<i class="fas fa-search"></i>');
        searchBtn.prop('disabled', false);
    }
    
    function showLiveSearchResults(results) {
        hideLiveSearchResults();
        
        if (results.length === 0) return;
        
        const resultsHtml = results.map(item => {
            const icon = item.type === 'case' ? 'fas fa-ticket-alt' : 'fas fa-user';
            const badge = item.status ? `<span class="badge bg-${getStatusColor(item.status)} ms-2">${item.status}</span>` : '';
            
            return `
                <div class="live-search-item" data-type="${item.type}">
                    <i class="${icon} me-2"></i>
                    ${item.title}
                    ${badge}
                </div>
            `;
        }).join('');
        
        const searchResults = $(`
            <div class="live-search-results">
                ${resultsHtml}
            </div>
        `);
        
        searchForm.append(searchResults);
        
        // Xử lý click vào kết quả
        searchResults.find('.live-search-item').on('click', function() {
            const type = $(this).data('type');
            const title = $(this).text().trim();
            
            searchInput.val(title);
            hideLiveSearchResults();
            
            showNotification(`Đã chọn: ${title}`, 'success');
        });
    }
    
    function hideLiveSearchResults() {
        $('.live-search-results').remove();
    }
    
    function displaySearchResults(results) {
        if (results.length === 0) {
            showNotification('Không tìm thấy kết quả nào', 'info');
            return;
        }
        
        showNotification(`Tìm thấy ${results.length} kết quả`, 'success');
        
        // Có thể hiển thị kết quả trong modal hoặc trang riêng

    }
    
    function getStatusColor(status) {
        const colors = {
            'open': 'danger',
            'in-progress': 'warning',
            'closed': 'success'
        };
        return colors[status] || 'secondary';
    }
    
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
        showNotification('Đang tải dashboard...', 'info');
        
        // Simulate loading
        setTimeout(function() {
            showNotification('Dashboard đã được tải', 'success');
        }, 1000);
    }
    
    function loadStaffPage() {
        showNotification('Đang chuyển đến trang nhân sự...', 'info');
        
        // Redirect to staff page
        setTimeout(function() {
            window.location.href = 'staff.php';
        }, 500);
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
        
        showNotification('Đang chuyển đến trang Case nội bộ...', 'info');
        
        // Redirect to internal cases page
        setTimeout(function() {
            window.location.href = 'internal_cases.php';
        }, 500);
    }
    
    function loadDeploymentCases() {
        
        // Simulate loading deployment cases
    }
    
    function loadMaintenanceCases() {
        
        // Simulate loading maintenance cases
    }
    
    function showUserProfile() {
        showNotification('Đang tải thông tin cá nhân...', 'info');
        
    }
    
    function showUserSettings() {
        showNotification('Đang tải cài đặt...', 'info');
        
        // Placeholder for regular user settings
    }
    
    function showNotifications() {
        showNotification('Đang tải thông báo...', 'info');
        
    }
    
    function showChangePasswordModal() {
        
        $('#changePasswordModal').modal('show');
    }
    
    // Đã xóa đoạn logic logout trùng lặp. Chỉ giữ lại một nơi duy nhất xử lý logout.
    // function performLogout() {
    //     showNotification('Đang đăng xuất...', 'info');
        
    //     setTimeout(function() {
    //         // Redirect to logout handler
    //         window.location.href = 'auth/logout.php';
    //     }, 1000);
    // }
    
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
    const navbarCollapse = $('.navbar-collapse');
    
    navbarToggler.on('click', function() {
        setTimeout(function() {
            if (navbarCollapse.hasClass('show')) {
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
            navbarCollapse.hasClass('show')) {
            navbarToggler.click();
        }
    });
    
    // ===== KEYBOARD SHORTCUTS ===== //
    
    $(document).on('keydown', function(e) {
        // Ctrl + K để focus vào search
        if (e.ctrlKey && e.key === 'k') {
            e.preventDefault();
            searchInput.focus();
        }
        
        // Escape để đóng dropdown và clear search
        if (e.key === 'Escape') {
            hideLiveSearchResults();
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
        
        // Set focus to search input on desktop
        if (!isMobile()) {
            setTimeout(function() {
                searchInput.focus();
            }, 500);
        }
        
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
            navbarCollapse.removeClass('show');
        }
    }, 250));
    
    // ===== START APPLICATION ===== //
    
    init();
    
    // ===== DEMO DATA CONSOLE LOG ===== //
    // Demo data removed for production
    
});

// ===== ADDITIONAL CSS FOR LIVE SEARCH ===== //

// Inject CSS for live search results
const liveSearchCSS = `
<style>
.live-search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #e9ecef;
    border-top: none;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    z-index: 1000;
    max-height: 300px;
    overflow-y: auto;
}

.live-search-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #f8f9fa;
    cursor: pointer;
    transition: all 0.2s ease;
}

.live-search-item:hover {
    background-color: #f8f9fa;
    color: #007bff;
}

.live-search-item:last-child {
    border-bottom: none;
}

.search-wrapper {
    position: relative;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.nav-link-hover {
    transform: translateY(-2px);
}

.navbar-open {
    overflow: hidden;
}

.navbar-open .navbar-collapse {
    max-height: calc(100vh - 60px);
    overflow-y: auto;
}
</style>
`;

// Inject CSS into head
$('head').append(liveSearchCSS); 