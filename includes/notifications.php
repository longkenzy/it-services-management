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
    <button class="btn btn-link nav-link position-relative" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-bell"></i>
        <?php if ($unread_count > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?php echo $unread_count > 99 ? '99+' : $unread_count; ?>
            </span>
        <?php endif; ?>
    </button>
    <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown" style="width: 350px; max-height: 400px; overflow-y: auto;">
        <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
            <h6 class="mb-0">Thông báo</h6>
            <?php if ($unread_count > 0): ?>
                <button class="btn btn-sm btn-outline-primary" id="markAllRead">
                    Đánh dấu đã đọc
                </button>
            <?php endif; ?>
        </div>
        <div id="notificationsList">
            <div class="text-center p-3">
                <div class="spinner-border spinner-border-sm" role="status">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
            </div>
        </div>
        <div class="p-2 border-top">
            <a href="#" class="text-decoration-none text-center d-block" id="viewAllNotifications">
                Xem tất cả thông báo
            </a>
        </div>
    </div>
</div>

<style>
.notification-dropdown {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border: none;
    border-radius: 0.5rem;
}

.notification-item {
    padding: 0.75rem;
    border-bottom: 1px solid #f8f9fa;
    cursor: pointer;
    transition: background-color 0.2s;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #e3f2fd;
}

.notification-item.unread:hover {
    background-color: #bbdefb;
}

.notification-title {
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.notification-message {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.notification-time {
    font-size: 0.75rem;
    color: #adb5bd;
}

.notification-badge {
    width: 8px;
    height: 8px;
    background-color: #dc3545;
    border-radius: 50%;
    display: inline-block;
    margin-right: 0.5rem;
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
        // Load notifications when dropdown is shown
        $('#notificationDropdown').on('show.bs.dropdown', function() {
            loadNotifications();
        });
        
        // Mark all as read
        $('#markAllRead').on('click', function(e) {
            e.preventDefault();
            markAllNotificationsRead();
        });
        
        // View all notifications
        $('#viewAllNotifications').on('click', function(e) {
            e.preventDefault();
            // Redirect to notifications page or show modal
            showAllNotifications();
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
                // Load notifications when dropdown is shown
                $('#notificationDropdown').on('show.bs.dropdown', function() {
                    loadNotifications();
                });
                
                // Mark all as read
                $('#markAllRead').on('click', function(e) {
                    e.preventDefault();
                    markAllNotificationsRead();
                });
                
                // View all notifications
                $('#viewAllNotifications').on('click', function(e) {
                    e.preventDefault();
                    // Redirect to notifications page or show modal
                    showAllNotifications();
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
        container.html('<div class="p-3 text-center text-muted">Không có thông báo nào</div>');
        return;
    }
    
    let html = '';
    notifications.forEach(notification => {
        const isUnread = notification.is_read == 0;
        const timeAgo = getTimeAgo(notification.created_at);
        
        html += `
            <div class="notification-item ${isUnread ? 'unread' : ''}" data-id="${notification.id}" data-type="${notification.type}" data-related-id="${notification.related_id}" onclick="handleNotificationClick(${notification.id}, '${notification.type}', ${notification.related_id})">
                <div class="d-flex align-items-start">
                    ${isUnread ? '<span class="notification-badge"></span>' : ''}
                    <div class="flex-grow-1">
                        <div class="notification-title">${notification.title}</div>
                        <div class="notification-message">${notification.message}</div>
                        <div class="notification-time">${timeAgo}</div>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.html(html);
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
                // Update UI
                jQuery('.notification-item').removeClass('unread');
                updateNotificationBadge();
                jQuery('#markAllRead').hide();
            }
        }
    });
}

function updateNotificationBadge() {
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not available');
        return;
    }
    
    // Update badge count
    const unreadCount = jQuery('.notification-item.unread').length;
    const badge = jQuery('#notificationDropdown .badge');
    
    console.log('Updating notification badge, unread count:', unreadCount);
    
    if (unreadCount > 0) {
        badge.text(unreadCount > 99 ? '99+' : unreadCount).show();
    } else {
        badge.hide();
    }
    
    // Also update the mark all read button
    if (unreadCount > 0) {
        jQuery('#markAllRead').show();
    } else {
        jQuery('#markAllRead').hide();
    }
}

function showAllNotifications() {
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not available');
        return;
    }
    
    // Show modal with all notifications
    jQuery('#allNotificationsModal').modal('show');
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
</script> 