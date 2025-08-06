/**
 * IT Services Management - Leave Management JavaScript
 * Xử lý logic cho trang quản lý nghỉ phép
 */

// Biến global để kiểm tra quyền phê duyệt
let canApprove = false;
let canViewAll = false;
let currentUserId = null;

// Khởi tạo ngay khi script được load
if (typeof window.canApprove !== 'undefined') {
    canApprove = window.canApprove;
}

if (typeof window.canViewAll !== 'undefined') {
    canViewAll = window.canViewAll;
}

if (typeof window.currentUserId !== 'undefined') {
    currentUserId = window.currentUserId;
}



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
        $('#statusFilter, #typeFilter, #dateFromFilter, #dateToFilter').on('change', filterLeaveRequests);
        $('#searchInput').on('input', filterLeaveRequests);
        $('#clearFilters').on('click', clearAllFilters);
        
        // Date validation
        $('#start_date, #end_date').on('change', validateDates);
        $('#start_time, #end_time').on('change', validateTimes);
        $('#leave_days').on('input', calculateEndDate);
        $('#return_date').on('change', validateReturnDate);
        
        // Kiểm tra quyền phê duyệt từ biến global
        if (typeof window.canApprove !== 'undefined') {
            canApprove = window.canApprove;
        }
        
        // Kiểm tra quyền xem tất cả đơn
        if (typeof window.canViewAll !== 'undefined') {
            canViewAll = window.canViewAll;
        }
        
        // Lấy ID người dùng hiện tại
        if (typeof window.currentUserId !== 'undefined') {
            currentUserId = window.currentUserId;
        }

        

        

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
    
    const statusFilter = $('#statusFilter').val();
    const typeFilter = $('#typeFilter').val();
    const searchInput = $('#searchInput').val();
    const dateFromFilter = $('#dateFromFilter').val();
    const dateToFilter = $('#dateToFilter').val();
    
    jQuery.ajax({
        url: 'api/get_leave_requests.php',
        type: 'GET',
        data: {
            status: statusFilter,
            type: typeFilter,
            search: searchInput,
            dateFrom: dateFromFilter,
            dateTo: dateToFilter
        },
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
 * Kiểm tra quyền phê duyệt theo trạng thái
 */
function canUserApproveForStatus(status) {
    const userRole = window.currentUserRole;

    
    if (status === 'Chờ phê duyệt') {
        // Admin và các Leader có thể phê duyệt đơn mới
        const result = userRole === 'admin' || userRole === 'hr_leader' || userRole === 'sale_leader' || userRole === 'it_leader';
        return result;
    } else if (status === 'Admin đã phê duyệt') {
        // Chỉ HR có thể phê duyệt đơn đã được admin phê duyệt
        const result = userRole === 'hr';
        return result;
    }
    
    return false;
}

/**
 * Kiểm tra quyền phê duyệt (tổng quát)
 */
function canUserApprove() {
    return canApprove || window.canApprove || (window.currentUserRole && ['admin', 'hr', 'hr_leader', 'sale_leader', 'it_leader'].includes(window.currentUserRole));
}



/**
 * Hiển thị danh sách đơn nghỉ phép
 */
function displayLeaveRequests(requests) {
    const tbody = jQuery('#leaveRequestsTableBody');
    tbody.empty();
    
    // Hiển thị thông báo quyền xem
    showViewPermissionNotice();
    
    if (!requests || requests.length === 0) {
        showEmptyState();
        return;
    }
    
    hideEmptyState();
    
    console.log('Displaying requests:', requests.length, 'items');
    
            requests.forEach((request, index) => {
                console.log('Processing request', index + 1, ':', request.request_code);
        const canApproveThisRequest = canUserApproveForStatus(request.status);
        
        const approvalButtons = canApproveThisRequest ? `
            <button type="button" class="btn btn-outline-success" onclick="approveLeaveRequest(${request.id})" title="Phê duyệt">
                <i class="fas fa-check"></i>
            </button>
            <button type="button" class="btn btn-outline-danger" onclick="rejectLeaveRequest(${request.id})" title="Từ chối">
                <i class="fas fa-times"></i>
            </button>
        ` : '';
        

        
        const row = `
            <tr>
                <td class="text-center">
                    <span class="badge">${index + 1}</span>
                </td>
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
                            ${getNoticeDaysBadge(request.notice_days)}
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
                        ` : ''}
                        ${approvalButtons}
                        ${(canUserApprove() || window.currentUserRole === 'hr' || window.currentUserRole === 'admin') && ['Đã phê duyệt', 'HR đã phê duyệt', 'Admin đã phê duyệt', 'Từ chối bởi Admin', 'Từ chối bởi HR', 'Từ chối'].includes(request.status) ? `
                            <button type="button" class="btn btn-outline-danger" onclick="deleteLeaveRequest(${request.id})" title="Xóa đơn">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
        console.log('Appending row with STT:', index + 1);
        tbody.append(row);
    });
    
    console.log('Total rows added:', requests.length);
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
    // Show modal first
    $('#viewLeaveRequestModal').modal('show');
    
    // Show loading state
    $('#viewLeaveRequestModalBody').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Đang tải...</span>
            </div>
            <p class="mt-2 text-muted">Đang tải thông tin đơn nghỉ phép...</p>
        </div>
    `);
    
    $.ajax({
        url: 'api/get_leave_request_details.php',
        type: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                displayLeaveRequestDetails(response.data);
            } else {
                $('#viewLeaveRequestModalBody').html(`
                    <div class="text-center py-4">
                        <i class="fas fa-exclamation-triangle text-warning fa-2x mb-3"></i>
                        <h5 class="text-muted">Có lỗi xảy ra</h5>
                        <p class="text-muted">${response.message || 'Không thể tải thông tin đơn nghỉ phép'}</p>
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error details:', xhr.responseText);
            $('#viewLeaveRequestModalBody').html(`
                <div class="text-center py-4">
                    <i class="fas fa-exclamation-triangle text-danger fa-2x mb-3"></i>
                    <h5 class="text-muted">Có lỗi xảy ra</h5>
                    <p class="text-muted">Không thể tải thông tin đơn nghỉ phép</p>
                </div>
            `);
        }
    });
}

/**
 * Hiển thị chi tiết đơn nghỉ phép
 */
function displayLeaveRequestDetails(request) {
    const modalBody = $('#viewLeaveRequestModalBody');
    
    // Tạo nội dung modal với thiết kế đẹp và chuyên nghiệp
    const content = `
        <div class="leave-request-details">
            <!-- Header với logo và thông tin đơn -->
            <div class="leave-request-header mb-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <div class="company-logo me-3">
                                <i class="fas fa-building text-primary" style="font-size: 2.5rem;"></i>
                            </div>
                            <div>
                                <h4 class="mb-1 fw-bold text-dark">CÔNG TY IT SERVICES</h4>
                                <h6 class="mb-0 text-muted">ĐƠN XIN NGHỈ PHÉP</h6>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="request-info">
                            <div class="request-code mb-2">
                                <small class="text-muted d-block">Mã đơn:</small>
                                <span class="fw-bold text-primary">${request.request_code}</span>
                            </div>
                            <div class="request-status">
                                <small class="text-muted d-block">Trạng thái:</small>
                                ${getStatusBadge(request.status)}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thông tin người yêu cầu -->
            <div class="card mb-4 border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-user me-2"></i>
                        THÔNG TIN NGƯỜI YÊU CẦU
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="requester-profile d-flex align-items-center">
                                <div class="avatar-lg me-3">
                                    ${getAvatarHtml(request.requester_avatar, request.requester_name)}
                                </div>
                                <div class="requester-info">
                                    <h5 class="mb-1 fw-bold text-dark">${request.requester_name}</h5>
                                    <p class="mb-1 text-muted">${request.requester_position || 'Chưa cập nhật'}</p>
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        ${request.requester_department || ''} - ${request.requester_office || ''}
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="info-field">
                                        <label class="text-muted small fw-semibold">Chức vụ:</label>
                                        <div class="fw-semibold text-dark">${request.requester_position || 'Chưa cập nhật'}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="info-field">
                                        <label class="text-muted small fw-semibold">Phòng ban:</label>
                                        <div class="fw-semibold text-dark">${request.requester_department || 'Chưa cập nhật'}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="info-field">
                                        <label class="text-muted small fw-semibold">Văn phòng:</label>
                                        <div class="fw-semibold text-dark">${request.requester_office || 'Chưa cập nhật'}</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="info-field">
                                        <label class="text-muted small fw-semibold">Ngày gửi:</label>
                                        <div class="fw-semibold text-dark">${request.formatted_created_at}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thông tin nghỉ phép -->
            <div class="card mb-4 border-success">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-calendar-alt me-2"></i>
                        THÔNG TIN NGHỈ PHÉP
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="info-field">
                                <label class="text-muted small fw-semibold">Loại nghỉ phép:</label>
                                <div class="fw-semibold text-dark">${request.leave_type}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-field">
                                <label class="text-muted small fw-semibold">Số ngày nghỉ:</label>
                                <div class="fw-semibold text-primary fs-5">${request.leave_days} ngày</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-field">
                                <label class="text-muted small fw-semibold">Ngày bắt đầu:</label>
                                <div class="fw-semibold text-dark">
                                    <i class="fas fa-play-circle text-success me-1"></i>
                                    ${request.formatted_start_date} ${request.start_time}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-field">
                                <label class="text-muted small fw-semibold">Ngày kết thúc:</label>
                                <div class="fw-semibold text-dark">
                                    <i class="fas fa-stop-circle text-danger me-1"></i>
                                    ${request.formatted_end_date} ${request.end_time}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-field">
                                <label class="text-muted small fw-semibold">Ngày đi làm lại:</label>
                                <div class="fw-semibold text-dark">
                                    <i class="fas fa-undo text-info me-1"></i>
                                    ${request.formatted_return_date} ${request.return_time}
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-field">
                                <label class="text-muted small fw-semibold">Báo trước:</label>
                                <div class="fw-semibold text-dark">
                                    <i class="fas fa-clock me-1"></i>
                                    ${getNoticeDaysBadge(request.notice_days)}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lý do và bàn giao -->
            <div class="row g-4 mb-4">
                <div class="col-md-8">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0 fw-semibold">
                                <i class="fas fa-comment me-2"></i>
                                LÝ DO NGHỈ PHÉP
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="reason-content">
                                <p class="mb-0 fst-italic">"${request.reason || 'Không có lý do'}"</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0 fw-semibold">
                                <i class="fas fa-handshake me-2"></i>
                                BÀN GIAO VIỆC
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="handover-info">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-2">
                                        ${getAvatarHtml(null, request.handover_name)}
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark">${request.handover_name || 'Chưa chọn'}</div>
                                        <small class="text-muted">${request.handover_position || ''}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Thông tin phê duyệt -->
            ${getApprovalInfo(request)}

            <!-- Đính kèm -->
            ${getAttachmentInfo(request)}
        </div>
    `;
    
    modalBody.html(content);
}

/**
 * Tạo HTML cho thông tin phê duyệt
 */
function getApprovalInfo(request) {
    let approvalHtml = `
        <div class="card mb-4 border-secondary">
            <div class="card-header bg-secondary text-white">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-check-circle me-2"></i>
                    THÔNG TIN PHÊ DUYỆT
                </h6>
            </div>
            <div class="card-body">
    `;
    
    // Thông tin Admin approval
    if (request.admin_approved_by) {
        approvalHtml += `
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="approval-item border-primary">
                        <div class="d-flex align-items-center mb-3">
                            <div class="approval-icon me-2">
                                <i class="fas fa-user-shield text-primary"></i>
                            </div>
                            <div>
                                <span class="fw-bold text-primary">PHÊ DUYỆT CẤP 1</span>
                                <br><small class="text-muted">Quản trị viên</small>
                            </div>
                        </div>
                        <div class="approval-details">
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar-sm me-2">
                                    ${getAvatarHtml(null, request.admin_approver_name)}
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark">${request.admin_approver_name}</div>
                                    <small class="text-muted">${request.admin_approver_position || ''}</small>
                                </div>
                            </div>
                            <div class="approval-time">
                                <small class="text-muted">
                                    <i class="fas fa-calendar-check me-1"></i>
                                    ${request.formatted_admin_approved_at}
                                </small>
                            </div>
                            ${request.admin_approval_comment ? `
                                <div class="approval-comment mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-comment me-1"></i>
                                        Ghi chú: ${request.admin_approval_comment}
                                    </small>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
        `;
    }
    
    // Thông tin HR approval
    if (request.hr_approved_by) {
        approvalHtml += `
                <div class="col-md-6">
                    <div class="approval-item border-success">
                        <div class="d-flex align-items-center mb-3">
                            <div class="approval-icon me-2">
                                <i class="fas fa-user-tie text-success"></i>
                            </div>
                            <div>
                                <span class="fw-bold text-success">PHÊ DUYỆT CẤP 2</span>
                                <br><small class="text-muted">Nhân sự</small>
                            </div>
                        </div>
                        <div class="approval-details">
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar-sm me-2">
                                    ${getAvatarHtml(null, request.hr_approver_name)}
                                </div>
                                <div>
                                    <div class="fw-semibold text-dark">${request.hr_approver_name}</div>
                                    <small class="text-muted">${request.hr_approver_position || ''}</small>
                                </div>
                            </div>
                            <div class="approval-time">
                                <small class="text-muted">
                                    <i class="fas fa-calendar-check me-1"></i>
                                    ${request.formatted_hr_approved_at}
                                </small>
                            </div>
                            ${request.hr_approval_comment ? `
                                <div class="approval-comment mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-comment me-1"></i>
                                        Ghi chú: ${request.hr_approval_comment}
                                    </small>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
        `;
    }
    
    if (!request.admin_approved_by && !request.hr_approved_by) {
        approvalHtml += `
            <div class="col-12">
                <div class="text-center text-muted py-4">
                    <i class="fas fa-clock fa-3x mb-3 text-warning"></i>
                    <h6 class="mb-2">Đang chờ phê duyệt</h6>
                    <p class="mb-0 small">Đơn nghỉ phép đã được gửi và đang chờ xử lý</p>
                </div>
            </div>
        `;
    }
    
    approvalHtml += `
            </div>
        </div>
    `;
    
    return approvalHtml;
}

/**
 * Tạo HTML cho thông tin đính kèm
 */
function getAttachmentInfo(request) {
    if (!request.attachment) {
        return `
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0 fw-semibold">
                        <i class="fas fa-paperclip text-muted me-2"></i>
                        Tài liệu đính kèm
                    </h6>
                </div>
                <div class="card-body">
                    <div class="text-center text-muted py-3">
                        <i class="fas fa-file-alt fa-2x mb-2"></i>
                        <p class="mb-0">Không có tài liệu đính kèm</p>
                    </div>
                </div>
            </div>
        `;
    }
    
    return `
        <div class="card">
            <div class="card-header bg-light">
                <h6 class="mb-0 fw-semibold">
                    <i class="fas fa-paperclip text-primary me-2"></i>
                    Tài liệu đính kèm
                </h6>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <i class="fas fa-file-alt text-primary me-3" style="font-size: 1.5rem;"></i>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">${request.attachment}</div>
                        <small class="text-muted">Tài liệu đính kèm</small>
                    </div>
                    <a href="assets/uploads/leave_attachments/${request.attachment}" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-download me-1"></i>Tải xuống
                    </a>
                </div>
            </div>
        </div>
    `;
}

/**
 * Lọc đơn nghỉ phép
 */
function filterLeaveRequests() {
    const status = $('#statusFilter').val();
    const type = $('#typeFilter').val();
    const search = $('#searchInput').val();
    const dateFrom = $('#dateFromFilter').val();
    const dateTo = $('#dateToFilter').val();
    
    $.ajax({
        url: 'api/get_leave_requests.php',
        type: 'GET',
        data: {
            status: status,
            type: type,
            search: search,
            dateFrom: dateFrom,
            dateTo: dateTo
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
 * Xóa tất cả bộ lọc
 */
function clearAllFilters() {
    $('#statusFilter').val('');
    $('#typeFilter').val('');
    $('#dateFromFilter').val('');
    $('#dateToFilter').val('');
    $('#searchInput').val('');
    filterLeaveRequests();
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
    // Cập nhật thông báo theo quyền người dùng
    if (canViewAll) {
        // Admin/HR - có thể xem tất cả đơn
        $('#emptyStateTitle').text('Chưa có đơn nghỉ phép nào');
        $('#emptyStateMessage').text('Hiện tại chưa có đơn nghỉ phép nào trong hệ thống');
        $('#createRequestBtn').show();
    } else {
        // Nhân viên thường - chỉ xem đơn của mình
        $('#emptyStateTitle').text('Chưa có đơn nghỉ phép nào');
        $('#emptyStateMessage').text('Bạn chưa tạo đơn nghỉ phép nào. Bắt đầu tạo đơn nghỉ phép đầu tiên của bạn');
        $('#createRequestBtn').show();
    }
    
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
 * Hiển thị thông báo quyền xem
 */
function showViewPermissionNotice() {
    if (canViewAll) {
        // Admin/HR - có thể xem tất cả đơn
        $('#permissionMessage').text('Bạn đang xem tất cả đơn nghỉ phép trong hệ thống');
        $('#viewPermissionNotice').show();
    } else {
        // Nhân viên thường - chỉ xem đơn của mình
        $('#permissionMessage').text('Bạn đang xem đơn nghỉ phép của mình');
        $('#viewPermissionNotice').show();
    }
}

/**
 * Tạo badge trạng thái
 */
function getStatusBadge(status) {
    const statusClasses = {
        'Chờ phê duyệt': 'bg-warning',
        'Admin đã phê duyệt': 'bg-info',
        'HR đã phê duyệt': 'bg-success',
        'Đã phê duyệt': 'bg-success',
        'Từ chối bởi Admin': 'bg-danger',
        'Từ chối bởi HR': 'bg-danger',
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
 * Tạo badge cho số ngày báo trước
 */
function getNoticeDaysBadge(noticeDays) {
    if (!noticeDays && noticeDays !== 0) {
        return '<span class="badge bg-secondary">Chưa có</span>';
    }
    
    const days = parseInt(noticeDays);
    
    if (days > 0) {
        // Còn lại số ngày
        return `<span class="badge bg-success">Còn ${days} ngày</span>`;
    } else if (days < 0) {
        // Đã qua số ngày
        return `<span class="badge bg-warning">Đã qua ${Math.abs(days)} ngày</span>`;
    } else {
        // Hôm nay
        return `<span class="badge bg-info">Hôm nay</span>`;
    }
}

/**
 * Phê duyệt đơn nghỉ phép (Admin only)
 */
function approveLeaveRequest(requestId) {
    if (!confirm('Bạn có chắc chắn muốn phê duyệt đơn nghỉ phép này?')) {
        return;
    }
    
    $.ajax({
        url: 'api/approve_leave_request.php',
        type: 'POST',
        data: {
            request_id: requestId,
            action: 'approve',
            comment: ''
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
    
    $.ajax({
        url: 'api/approve_leave_request.php',
        type: 'POST',
        data: {
            request_id: requestId,
            action: 'reject',
            comment: ''
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

/**
 * Xóa đơn nghỉ phép
 */
function deleteLeaveRequest(requestId) {
    if (!confirm('Bạn có chắc chắn muốn xóa đơn nghỉ phép này? Hành động này không thể hoàn tác.')) {
        return;
    }
    
    $.ajax({
        url: 'api/delete_leave_request.php',
        type: 'POST',
        data: {
            request_id: requestId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('Đã xóa đơn nghỉ phép thành công!', 'success');
                loadLeaveRequests();
            } else {
                showAlert(response.message || 'Có lỗi xảy ra khi xóa đơn nghỉ phép', 'error');
            }
        },
        error: function(xhr, status, error) {
            showAlert('Có lỗi xảy ra khi xóa đơn nghỉ phép', 'error');
        }
    });
}

// Xử lý các action của header
$(document).ready(function() {
    
    
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
