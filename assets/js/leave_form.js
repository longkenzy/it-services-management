/**
 * JavaScript cho form đơn nghỉ phép
 * File: assets/js/leave_form.js
 * Mục đích: Xử lý submit form và gửi email thông báo
 */

class LeaveFormHandler {
    constructor() {
        this.form = document.getElementById('createLeaveRequestForm');
        this.submitBtn = document.getElementById('submitLeaveBtn');
        this.modal = document.getElementById('createLeaveRequestModal');
        this.alertContainer = document.getElementById('alertContainer');
        
        this.init();
    }
    
    init() {
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        }
        
        // Khởi tạo date pickers
        this.initDatePickers();
        
        // Khởi tạo validation
        this.initValidation();
    }
    
    /**
     * Khởi tạo date pickers
     */
    initDatePickers() {
        // Set min date cho start_date là ngày hôm nay
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const returnDateInput = document.getElementById('return_date');
        
        if (startDateInput) {
            const today = new Date().toISOString().split('T')[0];
            startDateInput.min = today;
            
            // Khi thay đổi start_date, cập nhật min của end_date
            startDateInput.addEventListener('change', () => {
                if (endDateInput) {
                    endDateInput.min = startDateInput.value;
                    if (endDateInput.value && endDateInput.value < startDateInput.value) {
                        endDateInput.value = startDateInput.value;
                    }
                }
            });
        }
        
        if (endDateInput) {
            // Khi thay đổi end_date, cập nhật min của return_date
            endDateInput.addEventListener('change', () => {
                if (returnDateInput) {
                    returnDateInput.min = endDateInput.value;
                    if (returnDateInput.value && returnDateInput.value < endDateInput.value) {
                        returnDateInput.value = endDateInput.value;
                    }
                }
            });
        }
    }
    
    /**
     * Khởi tạo validation
     */
    initValidation() {
        const inputs = this.form.querySelectorAll('input[required], select[required], textarea[required]');
        
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });
    }
    
    /**
     * Validate một field
     */
    validateField(field) {
        const value = field.value.trim();
        const fieldName = field.getAttribute('data-field-name') || field.name;
        
        // Xóa lỗi cũ
        this.clearFieldError(field);
        
        // Kiểm tra required
        if (field.hasAttribute('required') && !value) {
            this.showFieldError(field, `${fieldName} là bắt buộc`);
            return false;
        }
        
        // Kiểm tra email
        if (field.type === 'email' && value && !this.isValidEmail(value)) {
            this.showFieldError(field, 'Email không hợp lệ');
            return false;
        }
        
        // Kiểm tra số ngày nghỉ
        if (field.name === 'start_date' || field.name === 'end_date') {
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            if (startDate && endDate && startDate > endDate) {
                this.showFieldError(field, 'Ngày kết thúc phải sau ngày bắt đầu');
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Hiển thị lỗi cho field
     */
    showFieldError(field, message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback d-block';
        errorDiv.textContent = message;
        
        field.classList.add('is-invalid');
        field.parentNode.appendChild(errorDiv);
    }
    
    /**
     * Xóa lỗi cho field
     */
    clearFieldError(field) {
        field.classList.remove('is-invalid');
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    }
    
    /**
     * Kiểm tra email hợp lệ
     */
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    /**
     * Validate toàn bộ form
     */
    validateForm() {
        const inputs = this.form.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;
        
        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    /**
     * Xử lý submit form
     */
    async handleSubmit(e) {
        e.preventDefault();
        
        // Validate form
        if (!this.validateForm()) {
            this.showAlert('Vui lòng kiểm tra lại thông tin đã nhập', 'danger');
            return;
        }
        
        // Disable submit button
        this.setSubmitButtonState(false, 'Đang xử lý...');
        
        try {
            // Lấy dữ liệu form
            const formData = this.getFormData();
            
            // Gửi request
            const response = await this.submitLeaveRequest(formData);
            
            if (response.success) {
                this.showAlert(response.message, 'success');
                
                // Reset form
                this.form.reset();
                
                // Đóng modal
                if (this.modal) {
                    const modalInstance = bootstrap.Modal.getInstance(this.modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
                
                // Reload danh sách đơn nghỉ phép
                this.reloadLeaveRequests();
                
                // Hiển thị thông báo email nếu có
                if (response.warning) {
                    setTimeout(() => {
                        this.showAlert(response.warning, 'warning');
                    }, 2000);
                }
                
            } else {
                this.showAlert(response.message, 'danger');
            }
            
        } catch (error) {
            console.error('Error submitting leave request:', error);
            this.showAlert('Đã xảy ra lỗi khi gửi đơn. Vui lòng thử lại.', 'danger');
        } finally {
            // Enable submit button
            this.setSubmitButtonState(true, 'Gửi đơn');
        }
    }
    
    /**
     * Lấy dữ liệu từ form
     */
    getFormData() {
        const formData = new FormData(this.form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        // Xử lý file upload nếu có
        const fileInput = this.form.querySelector('input[type="file"]');
        if (fileInput && fileInput.files.length > 0) {
            data.attachment = fileInput.files[0];
        }
        
        return data;
    }
    
    /**
     * Gửi request submit đơn nghỉ phép
     */
    async submitLeaveRequest(data) {
        const response = await fetch('api/submit_leave.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    }
    
    /**
     * Cập nhật trạng thái submit button
     */
    setSubmitButtonState(enabled, text) {
        if (this.submitBtn) {
            this.submitBtn.disabled = !enabled;
            this.submitBtn.innerHTML = enabled ? 
                '<i class="fas fa-paper-plane me-2"></i>' + text :
                '<span class="spinner-border spinner-border-sm me-2" role="status"></span>' + text;
        }
    }
    
    /**
     * Hiển thị alert
     */
    showAlert(message, type = 'info') {
        if (!this.alertContainer) {
            // Tạo alert container nếu chưa có
            this.alertContainer = document.createElement('div');
            this.alertContainer.id = 'alertContainer';
            this.alertContainer.className = 'position-fixed top-0 end-0 p-3';
            this.alertContainer.style.zIndex = '9999';
            document.body.appendChild(this.alertContainer);
        }
        
        const alertId = 'alert-' + Date.now();
        const alertHtml = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas fa-${this.getAlertIcon(type)} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        this.alertContainer.insertAdjacentHTML('beforeend', alertHtml);
        
        // Auto hide sau 5 giây
        setTimeout(() => {
            const alert = document.getElementById(alertId);
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    }
    
    /**
     * Lấy icon cho alert
     */
    getAlertIcon(type) {
        const icons = {
            'success': 'check-circle',
            'danger': 'exclamation-triangle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
    
    /**
     * Reload danh sách đơn nghỉ phép
     */
    reloadLeaveRequests() {
        // Trigger event để reload data
        const event = new CustomEvent('leaveRequestSubmitted');
        document.dispatchEvent(event);
    }
}

// Khởi tạo khi DOM ready
document.addEventListener('DOMContentLoaded', function() {
    new LeaveFormHandler();
});

// Export cho sử dụng global
window.LeaveFormHandler = LeaveFormHandler; 