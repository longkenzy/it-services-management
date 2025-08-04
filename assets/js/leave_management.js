/**
 * IT Services Management - Leave Management JavaScript
 * Xử lý logic cho trang quản lý nghỉ phép
 */

// Biến global để kiểm tra quyền admin
let isAdmin = false;

// Đảm bảo jQuery đã được load trước khi chạy
if (typeof jQuery === 'undefined') {
    console.error('jQuery is not loaded! Please check script loading order.');
} else {
    // Đảm bảo $ alias có sẵn
    if (typeof $ === 'undefined') {
        $ = jQuery;
    }
    
    console.log('jQuery loaded successfully:', jQuery.fn.jquery);
    
    // Khởi tạo khi jQuery đã sẵn sàng
    jQuery(document).ready(function() {
        // Load danh sách đơn nghỉ phép khi trang được tải
        loadLeaveRequests();
        
        // Load danh sách nhân viên cho dropdown bàn giao
        loadStaffList();
        
        // Event handlers
        $('#createLeaveRequestForm').on('submit', createLeaveRequest);
        $('#statusFilter, #typeFilter').on('change', filterLeaveRequests);
        $('#searchInput').on('input', filterLeaveRequests);
        
        // Date validation
        $('#start_date, #end_date').on('change', validateDates);
        $('#start_time, #end_time').on('change', validateTimes);
        $('#leave_days').on('input', calculateEndDate);
        $('#return_date').on('change', validateReturnDate);
        
        // Kiểm tra quyền admin từ server
        jQuery.ajax({
            url: 'api/check_admin_role.php',
            type: 'GET',
            success: function(response) {
                if (response.success && response.is_admin) {
                    isAdmin = true;
                }
            },
            error: function() {
                console.log('Could not check admin role');
            }
        });
    });
}

/**
 * Load danh sách nhân viên cho dropdown bàn giao
 */
function loadStaffList() {
    jQuery.ajax({
        url: 'api/get_staffs_for_dropdown.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                populateHandoverDropdown(response.data);
            } else {
                showAlert(response.message || 'Có lỗi xảy ra khi tải danh sách nhân viên', 'error');
            }
        },
        error: function(xhr, status, error) {
            showAlert('Có lỗi xảy ra khi tải danh sách nhân viên', 'error');
        }
    });
}

/**
 * Populate dropdown bàn giao với danh sách nhân viên
 */
function populateHandoverDropdown(staffs) {
    const dropdown = jQuery('#handover_to');
    dropdown.empty();
    dropdown.append('<option value="">-- Chọn người được bàn giao --</option>');
    
    staffs.forEach(staff => {
        const option = `<option value="${staff.id}">${staff.fullname} - ${staff.position} (${staff.department})</option>`;
        dropdown.append(option);
    });
}

/**
 * Load danh sách đơn nghỉ phép
 */
function loadLeaveRequests() {
    showLoading();
    
    jQuery.ajax({
        url: 'api/get_leave_requests.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            hideLoading();
            if (response.success) {
                displayLeaveRequests(response.data);
            } else {
                showAlert(response.message || 'Có lỗi xảy ra khi tải danh sách đơn nghỉ phép', 'error');
            }
        },
        error: function(xhr, status, error) {
            hideLoading();
            showAlert('Có lỗi xảy ra khi tải danh sách đơn nghỉ phép', 'error');
        }
    });
}

/**
 * Hiển thị danh sách đơn nghỉ phép
 */
function displayLeaveRequests(requests) {
    const tbody = jQuery('#leaveRequestsTableBody');
    tbody.empty();
    
    if (!requests || requests.length === 0) {
        showEmptyState();
        return;
    }
    
    hideEmptyState();
    
    requests.forEach(request => {
        const row = `
            <tr>
                <td>
                    <span class="badge bg-primary">${request.request_code}</span>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm me-2">
                            ${getAvatarHtml(request.requester_avatar, request.requester_name)}
                        </div>
                        <div>
                            <div class="fw-semibold">Người yêu cầu:</div>
                            <div class="text-muted mb-1">${request.requester_name}</div>
                            <div class="fw-semibold">Chức vụ:</div>
                            <div class="text-muted mb-1">${request.requester_position || ''}</div>
                            <div class="fw-semibold">Phòng ban:</div>
                            <div class="text-muted mb-1">${request.requester_department || ''}</div>
                            <div class="fw-semibold">Văn phòng:</div>
                            <div class="text-muted">${request.requester_office || ''}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <div>
                        <div class="fw-semibold">Ngày bắt đầu:</div>
                        <div class="text-muted mb-1">${formatDateTime(request.start_date)}</div>
                        <div class="fw-semibold">Ngày kết thúc:</div>
                        <div class="text-muted mb-1">${formatDateTime(request.end_date)}</div>
                        <div class="fw-semibold">Ngày đi làm lại:</div>
                        <div class="text-muted mb-1">${formatDate(request.return_date)}</div>
                        <div class="fw-semibold">Số ngày nghỉ:</div>
                        <div class="text-muted">${request.leave_days}</div>
                    </div>
                </td>
                <td>
                    <div>
                        <div class="fw-semibold">Loại ngày phép:</div>
                        <div class="text-muted mb-1">${request.leave_type}</div>
                        <div class="fw-semibold">Lý do nghỉ:</div>
                        <div class="text-muted mb-1">${request.reason}</div>
                        <div class="fw-semibold">Đã bàn giao việc cho:</div>
                        <div class="text-muted mb-1">${request.handover_name || 'Chưa bàn giao'}</div>
                        <div class="fw-semibold">Báo trước (ngày):</div>
                        <div class="text-muted">
                            <span class="badge bg-success">${request.notice_days || '0'}</span>
                        </div>
                    </div>
                </td>
                <td>
                    ${request.attachment ? 
                        `<a href="assets/uploads/leave_attachments/${request.attachment}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download"></i>
                        </a>` : 
                        '<span class="text-muted">Không có</span>'
                    }
                </td>
                <td>
                    <div>
                        <div class="fw-semibold">Người gửi:</div>
                        <div class="text-muted mb-1">${request.requester_name}</div>
                        <div class="fw-semibold">Ngày gửi:</div>
                        <div class="text-muted">${formatDateTime(request.created_at)}</div>
                    </div>
                </td>
                <td>
                    ${getStatusBadge(request.status)}
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-info" onclick="viewLeaveRequest(${request.id})" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${request.status === 'Chờ phê duyệt' ? `
                            <button type="button" class="btn btn-outline-warning" onclick="editLeaveRequest(${request.id})" title="Chỉnh sửa">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="cancelLeaveRequest(${request.id})" title="Hủy đơn">
                                <i class="fas fa-times"></i>
                            </button>
                            ${isAdmin ? `
                                <button type="button" class="btn btn-outline-success" onclick="approveLeaveRequest(${request.id})" title="Phê duyệt">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger" onclick="rejectLeaveRequest(${request.id})" title="Từ chối">
                                    <i class="fas fa-times"></i>
                                </button>
                            ` : ''}
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

/**
 * Tạo đơn nghỉ phép mới
 */
function createLeaveRequest(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    $.ajax({
        url: 'api/create_leave_request.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showAlert('Đơn nghỉ phép đã được gửi thành công!', 'success');
                $('#createLeaveRequestModal').modal('hide');
                $('#createLeaveRequestForm')[0].reset();
                loadLeaveRequests();
            } else {
                showAlert(response.message || 'Có lỗi xảy ra khi tạo đơn nghỉ phép', 'error');
            }
        },
        error: function(xhr, status, error) {
            showAlert('Có lỗi xảy ra khi tạo đơn nghỉ phép', 'error');
        }
    });
}

/**
 * Xem chi tiết đơn nghỉ phép
 */
function viewLeaveRequest(id) {
    $.ajax({
        url: 'api/get_leave_request_details.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayLeaveRequestDetails(response.data);
                $('#viewLeaveRequestModal').modal('show');
            } else {
                showAlert(response.message || 'Có lỗi xảy ra khi tải thông tin đơn nghỉ phép', 'error');
            }
        },
        error: function(xhr, status, error) {
            showAlert('Có lỗi xảy ra khi tải thông tin đơn nghỉ phép', 'error');
        }
    });
}

/**
 * Hiển thị chi tiết đơn nghỉ phép
 */
function displayLeaveRequestDetails(request) {
    const modalBody = $('#viewLeaveRequestModalBody');
    
    const content = `
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Mã đơn:</label>
                <div class="form-control-plaintext">
                    <span class="badge bg-primary fs-6">${request.request_code}</span>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Trạng thái:</label>
                <div class="form-control-plaintext">
                    ${getStatusBadge(request.status)}
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Người yêu cầu:</label>
                <div class="form-control-plaintext">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm me-2">
                            ${getAvatarHtml(request.requester_avatar, request.requester_name)}
                        </div>
                        <div>
                            <div class="fw-semibold">${request.requester_name}</div>
                            <small class="text-muted">${request.requester_position || ''}</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Chức vụ:</label>
                <div class="form-control-plaintext">${request.requester_position || ''}</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Phòng ban:</label>
                <div class="form-control-plaintext">${request.requester_department || ''}</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Văn phòng:</label>
                <div class="form-control-plaintext">${request.requester_office || ''}</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Loại nghỉ phép:</label>
                <div class="form-control-plaintext">${request.leave_type}</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Số ngày nghỉ:</label>
                <div class="form-control-plaintext">${request.leave_days} ngày</div>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Ngày bắt đầu:</label>
                <div class="form-control-plaintext">${formatDateTime(request.start_date)}</div>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Ngày kết thúc:</label>
                <div class="form-control-plaintext">${formatDateTime(request.end_date)}</div>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Ngày đi làm lại:</label>
                <div class="form-control-plaintext">${formatDateTime(request.return_date)}</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Đã bàn giao việc cho:</label>
                <div class="form-control-plaintext">
                    ${request.handover_name ? `${request.handover_name} - ${request.handover_position || ''}` : 'Chưa bàn giao'}
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">Đính kèm:</label>
                <div class="form-control-plaintext">
                    ${request.attachment ? 
                        `<a href="assets/uploads/leave_attachments/${request.attachment}" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download me-1"></i>Tải xuống
                        </a>` : 
                        '<span class="text-muted">Không có</span>'
                    }
                </div>
            </div>
            <div class="col-12">
                <label class="form-label fw-semibold">Lý do nghỉ phép:</label>
                <div class="form-control-plaintext">${request.reason}</div>
            </div>
            ${request.attachment ? `
                <div class="col-12">
                    <label class="form-label fw-semibold">Tài liệu đính kèm:</label>
                    <div class="form-control-plaintext">
                        <a href="assets/uploads/leave_attachments/${request.attachment}" target="_blank" class="btn btn-outline-primary">
                            <i class="fas fa-download me-2"></i>Tải xuống tài liệu
                        </a>
                    </div>
                </div>
            ` : ''}
            <div class="col-md-6">
                <label class="form-label fw-semibold">Ngày gửi:</label>
                <div class="form-control-plaintext">
                    ${formatDate(request.created_at)} ${formatTime(request.created_at)}
                </div>
            </div>
            ${request.approved_by ? `
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Người phê duyệt:</label>
                    <div class="form-control-plaintext">${request.approver_name}</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Ngày phê duyệt:</label>
                    <div class="form-control-plaintext">
                        ${formatDate(request.approved_at)} ${formatTime(request.approved_at)}
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Ghi chú phê duyệt:</label>
                    <div class="form-control-plaintext">${request.approval_notes || 'Không có'}</div>
                </div>
            ` : ''}
        </div>
    `;
    
    modalBody.html(content);
}

/**
 * Lọc đơn nghỉ phép
 */
function filterLeaveRequests() {
    const status = $('#statusFilter').val();
    const type = $('#typeFilter').val();
    const search = $('#searchInput').val();
    
    $.ajax({
        url: 'api/get_leave_requests.php',
        type: 'GET',
        data: {
            status: status,
            type: type,
            search: search
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayLeaveRequests(response.data);
            } else {
                showAlert(response.message || 'Có lỗi xảy ra khi lọc đơn nghỉ phép', 'error');
            }
        },
        error: function(xhr, status, error) {
            showAlert('Có lỗi xảy ra khi lọc đơn nghỉ phép', 'error');
        }
    });
}

/**
 * Validate ngày tháng
 */
function validateDates() {
    const startDate = $('#start_date').val();
    const endDate = $('#end_date').val();
    
    if (startDate && endDate) {
        if (startDate > endDate) {
            showAlert('Ngày bắt đầu không thể sau ngày kết thúc', 'error');
            $('#end_date').val('');
        }
    }
}

/**
 * Validate thời gian
 */
function validateTimes() {
    const startDate = $('#start_date').val();
    const endDate = $('#end_date').val();
    const startTime = $('#start_time').val();
    const endTime = $('#end_time').val();
    
    if (startDate && endDate && startTime && endTime) {
        if (startDate === endDate && startTime >= endTime) {
            showAlert('Thời gian bắt đầu phải trước thời gian kết thúc', 'error');
            $('#end_time').val('');
        }
    }
}

/**
 * Tính toán ngày kết thúc dựa trên số ngày nghỉ
 */
function calculateEndDate() {
    const startDate = $('#start_date').val();
    const leaveDays = parseFloat($('#leave_days').val());
    
    if (startDate && leaveDays > 0) {
        const start = new Date(startDate);
        const end = new Date(start);
        end.setDate(start.getDate() + Math.ceil(leaveDays) - 1);
        
        const endDateStr = end.toISOString().split('T')[0];
        $('#end_date').val(endDateStr);
        
        // Tự động tính ngày đi làm lại
        const returnDate = new Date(end);
        returnDate.setDate(end.getDate() + 1);
        $('#return_date').val(returnDate.toISOString().split('T')[0]);
        
        // Tự động set thời gian mặc định nếu chưa có
        if (!$('#start_time').val()) {
            $('#start_time').val('08:00');
        }
        if (!$('#end_time').val()) {
            $('#end_time').val('17:00');
        }
        if (!$('#return_time').val()) {
            $('#return_time').val('08:00');
        }
    }
}

/**
 * Validate ngày đi làm lại
 */
function validateReturnDate() {
    const endDate = $('#end_date').val();
    const returnDate = $('#return_date').val();
    
    if (endDate && returnDate) {
        if (new Date(returnDate) <= new Date(endDate)) {
            showAlert('Ngày đi làm lại phải sau ngày kết thúc nghỉ', 'error');
            $('#return_date').val('');
        }
    }
}

/**
 * Hiển thị loading
 */
function showLoading() {
    $('#loadingState').show();
    $('.leave-requests-table').hide();
    $('#emptyState').hide();
}

/**
 * Ẩn loading
 */
function hideLoading() {
    $('#loadingState').hide();
    $('.leave-requests-table').show();
}

/**
 * Hiển thị trạng thái trống
 */
function showEmptyState() {
    $('#emptyState').show();
    $('.leave-requests-table').hide();
}

/**
 * Ẩn trạng thái trống
 */
function hideEmptyState() {
    $('#emptyState').hide();
    $('.leave-requests-table').show();
}

/**
 * Tạo badge trạng thái
 */
function getStatusBadge(status) {
    const statusClasses = {
        'Chờ phê duyệt': 'bg-warning',
        'Đã phê duyệt': 'bg-success',
        'Từ chối': 'bg-danger'
    };
    
    const statusClass = statusClasses[status] || 'bg-secondary';
    return `<span class="badge ${statusClass}">${status}</span>`;
}

/**
 * Tạo HTML avatar
 */
function getAvatarHtml(avatar, name) {
    if (avatar) {
        return `<img src="assets/uploads/avatars/${avatar}" alt="${name}" class="rounded-circle" width="32" height="32">`;
    } else {
        const initials = name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
        const colors = ['#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6f42c1'];
        const color = colors[Math.floor(Math.random() * colors.length)];
        return `<div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 32px; height: 32px; background-color: ${color}; font-size: 12px;">${initials}</div>`;
    }
}

/**
 * Format ngày tháng
 */
function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN');
}

/**
 * Format ngày tháng và giờ
 */
function formatDateTime(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN') + ' ' + date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
}

/**
 * Format thời gian
 */
function formatTime(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
}

/**
 * Phê duyệt đơn nghỉ phép (Admin only)
 */
function approveLeaveRequest(requestId) {
    if (!confirm('Bạn có chắc chắn muốn phê duyệt đơn nghỉ phép này?')) {
        return;
    }
    
    const notes = prompt('Nhập ghi chú phê duyệt (không bắt buộc):') || '';
    
    $.ajax({
        url: 'api/approve_leave_request.php',
        type: 'POST',
        data: {
            request_id: requestId,
            action: 'approve',
            notes: notes
        },
        success: function(response) {
            if (response.success) {
                showAlert('Đã phê duyệt đơn nghỉ phép thành công!', 'success');
                loadLeaveRequests();
            } else {
                showAlert(response.message || 'Có lỗi xảy ra khi phê duyệt đơn nghỉ phép', 'error');
            }
        },
        error: function() {
            showAlert('Có lỗi xảy ra khi phê duyệt đơn nghỉ phép', 'error');
        }
    });
}

/**
 * Từ chối đơn nghỉ phép (Admin only)
 */
function rejectLeaveRequest(requestId) {
    if (!confirm('Bạn có chắc chắn muốn từ chối đơn nghỉ phép này?')) {
        return;
    }
    
    const notes = prompt('Nhập lý do từ chối:') || '';
    if (!notes) {
        showAlert('Vui lòng nhập lý do từ chối', 'error');
        return;
    }
    
    $.ajax({
        url: 'api/approve_leave_request.php',
        type: 'POST',
        data: {
            request_id: requestId,
            action: 'reject',
            notes: notes
        },
        success: function(response) {
            if (response.success) {
                showAlert('Đã từ chối đơn nghỉ phép thành công!', 'success');
                loadLeaveRequests();
            } else {
                showAlert(response.message || 'Có lỗi xảy ra khi từ chối đơn nghỉ phép', 'error');
            }
        },
        error: function() {
            showAlert('Có lỗi xảy ra khi từ chối đơn nghỉ phép', 'error');
        }
    });
}

// Xử lý các action của header
$(document).ready(function() {
    console.log('Setting up header actions in leave_management.js');
    
    // Xử lý click "Đăng xuất"
    $(document).on('click', '[data-action="logout"]', function(e) {
        console.log('Logout clicked from leave_management.js');
        e.preventDefault();
        showAlert('Đang đăng xuất...', 'info');
        setTimeout(function() {
            window.location.href = 'auth/logout.php';
        }, 1000);
    });
    
    // Xử lý click "Thông tin cá nhân"
    $(document).on('click', '[data-action="profile"]', function(e) {
        e.preventDefault();
        showAlert('Tính năng đang phát triển...', 'info');
    });
    
    // Xử lý click "Cài đặt"
    $(document).on('click', '[data-action="settings"]', function(e) {
        e.preventDefault();
        showAlert('Tính năng đang phát triển...', 'info');
    });
    
    // Xử lý click "Thông báo"
    $(document).on('click', '[data-action="notifications"]', function(e) {
        e.preventDefault();
        showAlert('Tính năng đang phát triển...', 'info');
    });
    
    // Xử lý click "Đổi mật khẩu"
    $(document).on('click', '[data-action="change-password"]', function(e) {
        e.preventDefault();
        $('#changePasswordModal').modal('show');
        // Reset form khi mở modal
        $('#changePasswordForm')[0].reset();
        $('#changePasswordError').addClass('d-none');
        $('#changePasswordSuccess').addClass('d-none');
    });
});