<?php
/**
 * Component thông báo cho header
 */
require_once 'session.php';

// Lấy số thông báo chưa đọc
$unread_count = 0;
if (isLoggedIn()) {
    try {
        global $pdo;
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$_SESSION[SESSION_USER_ID]]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $unread_count = $result['count'];
        

    } catch (Exception $e) {
        // Ignore error

    }
}
?>

<!-- Notification Dropdown -->
<div class="dropdown">
    <button class="btn btn-link nav-link position-relative notification-btn" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-bell notification-icon"></i>
        <?php if ($unread_count > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill notification-badge-pulse">
                <?php echo $unread_count > 99 ? '99+' : $unread_count; ?>
            </span>
        <?php endif; ?>
    </button>
    <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
        <div class="notification-header">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="fas fa-bell text-primary me-2"></i>
                    <h6 class="mb-0 fw-bold">Thông báo</h6>
                </div>
                <div class="d-flex gap-1">
                    <?php if ($unread_count > 0): ?>
                        <button class="btn btn-sm btn-outline-primary mark-all-read-btn" id="markAllRead">
                            <i class="fas fa-check-double me-1"></i>
                            Đánh dấu đã đọc
                        </button>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-outline-danger delete-all-btn" id="deleteAllNotifications" title="Xóa tất cả thông báo">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="notification-body">
            <div id="notificationsList">
                <div class="notification-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-2 text-muted small">Đang tải thông báo...</p>
                </div>
            </div>
        </div>
        <div class="notification-footer">
            <a href="#" class="view-all-link" id="viewAllNotifications">
                <i class="fas fa-external-link-alt me-1"></i>
                Xem tất cả thông báo
            </a>
        </div>
    </div>
</div>

<style>
/* Notification Button */
.notification-btn {
    transition: all 0.3s ease;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    position: relative;
}

.notification-btn:hover {
    background-color: rgba(59, 130, 246, 0.1);
    transform: scale(1.05);
}

.notification-icon {
    font-size: 1.1rem;
    color: #6c757d;
    transition: color 0.3s ease;
}

.notification-btn:hover .notification-icon {
    color: #3b82f6;
}

/* Notification Badge */
.notification-badge-pulse {
    background: linear-gradient(45deg, #ef4444, #dc2626);
    border: 2px solid #fff;
    font-size: 0.7rem;
    font-weight: 600;
    animation: pulse 2s infinite;
    box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
    }
}

/* Notification Dropdown */
.notification-dropdown {
    width: 380px;
    max-height: 500px;
    border: none;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    padding: 0;
    overflow: hidden;
    background: #fff;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    overflow-x: hidden;
    overflow-y: auto;
}

/* Notification Header */
.notification-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 1.25rem;
    border-bottom: none;
}

.notification-header h6 {
    color: white;
    font-size: 1rem;
    font-weight: 600;
}

.mark-all-read-btn {
    background: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    transition: all 0.3s ease;
}

.mark-all-read-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    border-color: rgba(255, 255, 255, 0.4);
    color: white;
    transform: translateY(-1px);
}

.delete-all-btn {
    background: rgba(220, 53, 69, 0.2);
    border: 1px solid rgba(220, 53, 69, 0.3);
    color: white;
    font-size: 0.75rem;
    padding: 0.375rem 0.5rem;
    border-radius: 20px;
    transition: all 0.3s ease;
}

.delete-all-btn:hover {
    background: rgba(220, 53, 69, 0.3);
    border-color: rgba(220, 53, 69, 0.4);
    color: white;
    transform: translateY(-1px);
}

/* Prevent dropdown from closing when clicking buttons */
.notification-header button,
.notification-actions button {
    pointer-events: auto;
}

.notification-dropdown {
    pointer-events: auto;
}

/* Notification Body */
.notification-body {
    max-height: 350px;
    overflow-y: auto;
    overflow-x: hidden;
    background: #fff;
    word-wrap: break-word;
    word-break: break-word;
}

.notification-body::-webkit-scrollbar {
    width: 6px;
}

.notification-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.notification-body::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.notification-body::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Notification Items */
.notification-item {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f1f3f4;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
    background: #fff;
}

.notification-item:hover {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    transform: translateX(5px);
}

.notification-item.unread {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border-left: 4px solid #3b82f6;
}

.notification-item.unread:hover {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
}

.notification-item.unread::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 6px;
    height: 6px;
    background: #3b82f6;
    border-radius: 50%;
    animation: notification-pulse 2s infinite;
}

@keyframes notification-pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
    }
    70% {
        box-shadow: 0 0 0 6px rgba(59, 130, 246, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);
    }
}

.notification-title {
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.375rem;
    color: #1f2937;
    line-height: 1.4;
    word-wrap: break-word;
    word-break: break-word;
    max-width: 100%;
}

.notification-message {
    font-size: 0.8rem;
    color: #6b7280;
    margin-bottom: 0.5rem;
    line-height: 1.5;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-wrap: break-word;
    word-break: break-word;
    max-width: 100%;
}

.notification-time {
    font-size: 0.75rem;
    color: #9ca3af;
    font-weight: 500;
}

.notification-icon-wrapper {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(59, 130, 246, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.notification-icon-wrapper i {
    font-size: 1rem;
}

/* Notification Actions */
.notification-actions {
    opacity: 0;
    transition: opacity 0.3s ease;
    margin-left: 0.5rem;
}

.notification-item:hover .notification-actions {
    opacity: 1;
}

.delete-notification-btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    border-radius: 4px;
    transition: all 0.3s ease;
    background: transparent;
    border: 1px solid #dc3545;
    color: #dc3545;
}

.delete-notification-btn:hover {
    background: #dc3545;
    color: white;
    transform: scale(1.1);
}

.notification-content {
    cursor: pointer;
    flex-grow: 1;
}

/* Notification Footer */
.notification-footer {
    background: #f8fafc;
    padding: 0.75rem 1.25rem;
    border-top: 1px solid #e5e7eb;
    text-align: center;
}

.view-all-link {
    color: #3b82f6;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    background: rgba(59, 130, 246, 0.1);
}

.view-all-link:hover {
    color: #2563eb;
    background: rgba(59, 130, 246, 0.15);
    transform: translateY(-1px);
    text-decoration: none;
}

/* Empty State */
.notification-empty {
    text-align: center;
    padding: 2rem 1rem;
    color: #9ca3af;
}

.notification-empty i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Loading State */
.notification-loading {
    text-align: center;
    padding: 2rem 1rem;
}

/* Responsive Design */
@media (max-width: 576px) {
    .notification-dropdown {
        width: 320px;
        margin-right: 1rem;
        max-width: calc(100vw - 2rem);
    }
    
    .notification-header {
        padding: 0.75rem 1rem;
    }
    
    .notification-item {
        padding: 0.75rem 1rem;
    }
    
    .notification-footer {
        padding: 0.5rem 1rem;
    }
}

/* Prevent horizontal scroll on all screen sizes */
@media (max-width: 400px) {
    .notification-dropdown {
        width: calc(100vw - 2rem);
        margin-right: 0.5rem;
    }
}
</style>

<script>
// Đảm bảo jQuery đã được load trước khi chạy script
if (typeof jQuery !== 'undefined') {
    // Đảm bảo $ alias có sẵn
    if (typeof $ === 'undefined') {
        $ = jQuery;
    }
    
    $(document).ready(function() {
        // Update badge count on page load
        updateNotificationBadgeCount();
        
        // Load notifications when dropdown is shown
        $('#notificationDropdown').on('show.bs.dropdown', function() {
            loadNotifications();
        });
        
        // Mark all as read
        $('#markAllRead').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Ngăn chặn event bubble
            markAllNotificationsRead();
        });
        
        // View all notifications
        $('#viewAllNotifications').on('click', function(e) {
            e.preventDefault();
            // Redirect to notifications page or show modal
            showAllNotifications();
        });
        
        // Delete all notifications
        $('#deleteAllNotifications').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Ngăn chặn event bubble
            deleteAllNotifications();
        });
    });
} else {
    // Nếu jQuery chưa load, đợi và thử lại
    function waitForJQuery() {
        if (typeof jQuery !== 'undefined') {
            if (typeof $ === 'undefined') {
                $ = jQuery;
            }
            
            $(document).ready(function() {
                // Update badge count on page load
                updateNotificationBadgeCount();
                
                // Load notifications when dropdown is shown
                $('#notificationDropdown').on('show.bs.dropdown', function() {
                    loadNotifications();
                });
                
                // Mark all as read
                $('#markAllRead').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation(); // Ngăn chặn event bubble
                    markAllNotificationsRead();
                });
                
                // View all notifications
                $('#viewAllNotifications').on('click', function(e) {
                    e.preventDefault();
                    // Redirect to notifications page or show modal
                    showAllNotifications();
                });
                
                // Delete all notifications
                $('#deleteAllNotifications').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation(); // Ngăn chặn event bubble
                    deleteAllNotifications();
                });
            });
        } else {
            setTimeout(waitForJQuery, 100);
        }
    }
    waitForJQuery();
}

function loadNotifications() {
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not available');
        return;
    }
    
    jQuery.ajax({
        url: 'api/get_notifications.php',
        type: 'GET',
        data: {
            limit: 10,
            unread_only: false
        },
        success: function(response) {
            if (response.success) {
                displayNotifications(response.data.notifications);
            } else {
                jQuery('#notificationsList').html('<div class="p-3 text-center text-muted">Có lỗi xảy ra khi tải thông báo</div>');
            }
        },
        error: function() {
            jQuery('#notificationsList').html('<div class="p-3 text-center text-muted">Có lỗi xảy ra khi tải thông báo</div>');
        }
    });
}

function displayNotifications(notifications) {
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not available');
        return;
    }
    
    const container = jQuery('#notificationsList');
    
    if (!notifications || notifications.length === 0) {
        container.html(`
            <div class="notification-empty">
                <i class="fas fa-bell-slash"></i>
                <h6 class="mb-2">Không có thông báo nào</h6>
                <p class="small text-muted mb-0">Bạn đã cập nhật tất cả thông báo</p>
            </div>
        `);
        return;
    }
    
    let html = '';
    notifications.forEach(notification => {
        const isUnread = notification.is_read == 0;
        const timeAgo = getTimeAgo(notification.created_at);
        const notificationIcon = getNotificationIcon(notification.type);
        
        html += `
            <div class="notification-item ${isUnread ? 'unread' : ''}" data-id="${notification.id}" data-type="${notification.type}" data-related-id="${notification.related_id}">
                <div class="d-flex align-items-start">
                    <div class="notification-icon-wrapper me-3">
                        <i class="${notificationIcon}"></i>
                    </div>
                    <div class="flex-grow-1 notification-content" onclick="handleNotificationClick(${notification.id}, '${notification.type}', ${notification.related_id})">
                        <div class="notification-title">${notification.title}</div>
                        <div class="notification-message">${notification.message}</div>
                        <div class="notification-time">
                            <i class="fas fa-clock me-1"></i>
                            ${timeAgo}
                        </div>
                    </div>
                    <div class="notification-actions">
                        <button class="btn btn-sm btn-outline-danger delete-notification-btn" onclick="deleteNotification(${notification.id}, event)" title="Xóa thông báo">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.html(html);
}

function getNotificationIcon(type) {
    const icons = {
        'leave_request': 'fas fa-calendar-plus text-primary',
        'leave_approved': 'fas fa-check-circle text-success',
        'leave_rejected': 'fas fa-times-circle text-danger',
        'internal_case': 'fas fa-building text-info',
        'system': 'fas fa-cog text-info',
        'default': 'fas fa-bell text-warning'
    };
    
    return icons[type] || icons['default'];
}

function handleNotificationClick(notificationId, type, relatedId) {
    console.log('Notification clicked:', notificationId, type, relatedId);
    
    // Mark as read immediately in UI
    const notificationItem = jQuery(`.notification-item[data-id="${notificationId}"]`);
    if (notificationItem.length > 0) {
        notificationItem.removeClass('unread');
        notificationItem.find('.notification-badge').remove();
        updateNotificationBadge();
    }
    
    // Mark as read in database
    markNotificationRead(notificationId);
    
    // Handle different notification types
    switch (type) {
        case 'leave_request':
            // Redirect to leave management page
            window.location.href = 'leave_management.php';
            break;
        case 'leave_approved':
        case 'leave_rejected':
            // Redirect to leave management page
            window.location.href = 'leave_management.php';
            break;
        case 'internal_case':
            // Redirect to internal cases page
            window.location.href = 'internal_cases.php';
            break;
        default:
            // Do nothing
            break;
    }
}

function markNotificationRead(notificationId) {
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not available');
        return;
    }
    
    console.log('Marking notification as read:', notificationId);
    
    jQuery.ajax({
        url: 'api/mark_notification_read.php',
        type: 'POST',
        data: {
            notification_id: notificationId
        },
        dataType: 'json',
        success: function(response) {
            console.log('Mark notification response:', response);
            if (response.success) {
                // Update UI immediately
                jQuery(`.notification-item[data-id="${notificationId}"]`).removeClass('unread');
                jQuery(`.notification-item[data-id="${notificationId}"] .notification-badge`).remove();
                updateNotificationBadge();
            } else {
                console.error('Failed to mark notification as read:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error marking notification as read:', error);
            console.error('Response:', xhr.responseText);
        }
    });
}

function markAllNotificationsRead() {
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not available');
        return;
    }
    
    jQuery.ajax({
        url: 'api/mark_notification_read.php',
        type: 'POST',
        data: {
            mark_all: 'true'
        },
        success: function(response) {
            if (response.success) {
                // Update UI immediately without reloading
                jQuery('.notification-item').removeClass('unread');
                jQuery('.notification-item').removeClass('unread::before');
                updateNotificationBadge();
                jQuery('#markAllRead').hide();
            } else {
                showNotificationMessage('Có lỗi xảy ra khi đánh dấu đã đọc: ' + (response.message || 'Lỗi không xác định'), 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error marking all notifications as read:', error);
            showNotificationMessage('Có lỗi xảy ra khi đánh dấu đã đọc', 'error');
        }
    });
}

function updateNotificationBadge() {
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not available');
        return;
    }
    
    // Update badge count from server
    updateNotificationBadgeCount();
    
    // Update mark all read button based on local unread items
    const unreadCount = jQuery('.notification-item.unread').length;
    
    if (unreadCount > 0) {
        jQuery('#markAllRead').show();
    } else {
        jQuery('#markAllRead').hide();
    }
}

function deleteAllNotifications() {
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not available');
        return;
    }
    
    if (!confirm('Bạn có chắc chắn muốn xóa tất cả thông báo? Hành động này không thể hoàn tác.')) {
        return;
    }
    
    jQuery.ajax({
        url: 'api/delete_notification.php',
        type: 'POST',
        data: {
            delete_all: 'true'
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Clear all notifications from UI
                jQuery('.notification-item').fadeOut(300, function() {
                    jQuery(this).remove();
                    displayNotifications([]);
                    updateNotificationBadge();
                });
                
                // Hide the mark all read and delete all buttons
                jQuery('#markAllRead').hide();
                jQuery('#deleteAllNotifications').hide();
                
                showNotificationMessage('Đã xóa tất cả thông báo thành công!', 'success');
            } else {
                showNotificationMessage('Có lỗi xảy ra khi xóa thông báo: ' + (response.message || 'Lỗi không xác định'), 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error deleting all notifications:', error);
            showNotificationMessage('Có lỗi xảy ra khi xóa thông báo', 'error');
        }
    });
}

function showAllNotifications() {
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not available');
        return;
    }
    
    // Show modal with all notifications
    jQuery('#allNotificationsModal').modal('show');
}

function deleteNotification(notificationId, event) {
    event.stopPropagation(); // Ngăn chặn event bubble
    
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not available');
        return;
    }
    
    if (!confirm('Bạn có chắc chắn muốn xóa thông báo này?')) {
        return;
    }
    
    console.log('Deleting notification:', notificationId);
    
    jQuery.ajax({
        url: 'api/delete_notification.php',
        type: 'POST',
        data: {
            notification_id: notificationId
        },
        dataType: 'json',
        success: function(response) {
            console.log('Delete notification response:', response);
            if (response.success) {
                // Remove the notification item from UI
                jQuery(`.notification-item[data-id="${notificationId}"]`).fadeOut(300, function() {
                    jQuery(this).remove();
                    updateNotificationBadge();
                    
                    // Check if no notifications left
                    if (jQuery('.notification-item').length === 0) {
                        displayNotifications([]);
                    }
                });
                
                // Show success message
                showNotificationMessage('Đã xóa thông báo thành công!', 'success');
            } else {
                showNotificationMessage('Có lỗi xảy ra khi xóa thông báo: ' + (response.message || 'Lỗi không xác định'), 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error deleting notification:', error);
            console.error('Response:', xhr.responseText);
            showNotificationMessage('Có lỗi xảy ra khi xóa thông báo', 'error');
        }
    });
}

function showNotificationMessage(message, type) {
    // Create a temporary message element
    const messageElement = jQuery('<div>')
        .addClass(`alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`)
        .css({
            position: 'fixed',
            top: '20px',
            right: '20px',
            zIndex: 9999,
            minWidth: '300px'
        })
        .html(`
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `);
    
    jQuery('body').append(messageElement);
    
    // Auto remove after 3 seconds
    setTimeout(function() {
        messageElement.fadeOut(300, function() {
            jQuery(this).remove();
        });
    }, 3000);
}

function getTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) {
        return 'Vừa xong';
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `${minutes} phút trước`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `${hours} giờ trước`;
    } else {
        const days = Math.floor(diffInSeconds / 86400);
        return `${days} ngày trước`;
    }
}

// Auto refresh notifications every 30 seconds
setInterval(function() {
    if (typeof jQuery !== 'undefined' && jQuery('#notificationDropdown').hasClass('show')) {
        loadNotifications();
    }
}, 30000);

// Auto update notification badge every 10 seconds
setInterval(function() {
    if (typeof jQuery !== 'undefined') {
        updateNotificationBadgeCount();
    }
}, 10000);

// Function to update notification badge count from server
function updateNotificationBadgeCount() {
    if (typeof jQuery === 'undefined') {
        return;
    }
    
    jQuery.ajax({
        url: 'api/get_notification_count.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const unreadCount = response.data.unread_count;
                const badge = jQuery('#notificationDropdown .badge');
                
                if (unreadCount > 0) {
                    badge.text(unreadCount > 99 ? '99+' : unreadCount).show();
                } else {
                    badge.hide();
                }
                
                // Also update the mark all read button visibility
                if (unreadCount > 0) {
                    jQuery('#markAllRead').show();
                } else {
                    jQuery('#markAllRead').hide();
                }
            }
        },
        error: function() {
            // Silently fail, don't show error for badge updates
        }
    });
}

// Function to force update badge (can be called from other parts of the app)
function forceUpdateNotificationBadge() {
    updateNotificationBadgeCount();
}
</script> 