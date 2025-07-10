/**
 * Modern Alert System JavaScript
 * Hệ thống thông báo đẹp mắt với animation và tự động ẩn
 */

class AlertSystem {
    constructor() {
        this.container = null;
        this.alerts = [];
        this.init();
    }

    // Khởi tạo container
    init() {
        // Tạo container nếu chưa có
        if (!document.querySelector('.alert-container')) {
            this.container = document.createElement('div');
            this.container.className = 'alert-container';
            document.body.appendChild(this.container);
        } else {
            this.container = document.querySelector('.alert-container');
        }
    }

    // Tạo alert element
    createAlert(message, type = 'default', options = {}) {
        const alertId = 'alert-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        
        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.id = alertId;
        
        // Tạo icon
        const icon = document.createElement('span');
        icon.className = 'alert-icon';
        
        // Tạo content
        const content = document.createElement('div');
        content.className = 'alert-content';
        content.textContent = message;
        
        // Tạo close button
        const closeBtn = document.createElement('button');
        closeBtn.className = 'alert-close';
        closeBtn.innerHTML = '×';
        closeBtn.onclick = () => this.hideAlert(alertId);
        
        // Tạo progress bar nếu có auto dismiss
        let progressBar = null;
        if (options.autoDismiss !== false) {
            progressBar = document.createElement('div');
            progressBar.className = 'alert-progress';
            progressBar.style.width = '100%';
        }
        
        // Lắp ráp alert
        alert.appendChild(icon);
        alert.appendChild(content);
        alert.appendChild(closeBtn);
        if (progressBar) {
            alert.appendChild(progressBar);
        }
        
        return { alert, alertId, progressBar };
    }

    // Hiển thị alert
    showAlert(message, type = 'default', options = {}) {
        const { alert, alertId, progressBar } = this.createAlert(message, type, options);
        
        // Thêm vào container
        this.container.appendChild(alert);
        this.alerts.push(alertId);
        
        // Trigger show animation
        setTimeout(() => {
            alert.classList.add('show');
        }, 10);
        
        // Auto dismiss
        const duration = options.duration || 3000;
        if (options.autoDismiss !== false && duration > 0) {
            // Animate progress bar
            if (progressBar) {
                progressBar.style.transition = `width ${duration}ms linear`;
                setTimeout(() => {
                    progressBar.style.width = '0%';
                }, 50);
            }
            
            // Auto hide after duration
            setTimeout(() => {
                this.hideAlert(alertId);
            }, duration);
        }
        
        return alertId;
    }

    // Ẩn alert
    hideAlert(alertId) {
        const alert = document.getElementById(alertId);
        if (alert) {
            alert.classList.add('hide');
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
                this.alerts = this.alerts.filter(id => id !== alertId);
            }, 300);
        }
    }

    // Ẩn tất cả alerts
    hideAll() {
        this.alerts.forEach(alertId => this.hideAlert(alertId));
    }

    // Các phương thức tiện ích cho từng loại alert
    success(message, options = {}) {
        return this.showAlert(message, 'success', options);
    }

    error(message, options = {}) {
        return this.showAlert(message, 'danger', { ...options, autoDismiss: false });
    }

    warning(message, options = {}) {
        return this.showAlert(message, 'warning', options);
    }

    info(message, options = {}) {
        return this.showAlert(message, 'info', options);
    }

    default(message, options = {}) {
        return this.showAlert(message, 'default', options);
    }
}

// Tạo instance global
const alertSystem = new AlertSystem();

// Các hàm tiện ích global
function showAlert(message, type = 'default', options = {}) {
    return alertSystem.showAlert(message, type, options);
}

function showSuccess(message, options = {}) {
    return alertSystem.success(message, options);
}

function showError(message, options = {}) {
    return alertSystem.error(message, options);
}

function showWarning(message, options = {}) {
    return alertSystem.warning(message, options);
}

function showInfo(message, options = {}) {
    return alertSystem.info(message, options);
}

// Thay thế alert() mặc định
function customAlert(message, type = 'info') {
    return showAlert(message, type);
}

// Thay thế confirm() với alert đẹp hơn
function customConfirm(message, onConfirm, onCancel) {
    const alertId = showWarning(message, { 
        autoDismiss: false,
        duration: 0 
    });
    
    // Tạm thời sử dụng confirm mặc định
    // Có thể phát triển modal confirm sau
    const result = confirm(message);
    alertSystem.hideAlert(alertId);
    
    if (result && onConfirm) {
        onConfirm();
    } else if (!result && onCancel) {
        onCancel();
    }
    
    return result;
}

// Export cho module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { AlertSystem, alertSystem, showAlert, showSuccess, showError, showWarning, showInfo };
}

// Tự động khởi tạo khi DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Đảm bảo alert system đã được khởi tạo
    if (!alertSystem.container) {
        alertSystem.init();
    }
}); 