// Dữ liệu khách hàng (sẽ được load từ PHP)
let partnerData = [];
// Flag để ngăn chặn form submission không mong muốn
let isOpeningCaseModal = false;

$(document).ready(function() {
    // Khởi tạo Select2
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Đợi một chút để đảm bảo DOM đã load xong
    setTimeout(function() {
        // Load danh sách yêu cầu bảo trì
        loadMaintenanceRequests();
    }, 100);
    
    // Ngăn chặn tất cả form submission không mong muốn khi đang mở modal case
    $(document).on('submit', 'form', function(e) {
        if (isOpeningCaseModal) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        }
    });

    // Xử lý form tạo yêu cầu bảo trì
    $('#addMaintenanceRequestForm').on('submit', function(e) {
        e.preventDefault();
        createMaintenanceRequest();
    });

    // Xử lý form cập nhật yêu cầu bảo trì (chỉ khi modal tồn tại)
    if ($('#editMaintenanceRequestForm').length > 0) {
        $('#editMaintenanceRequestForm').off('submit').on('submit', function(e) {
            // Kiểm tra xem form có bị disable tạm thời không
            if (this.hasAttribute('data-submit-disabled') || isOpeningCaseModal) {
                e.preventDefault();
                return false;
            }
            
            // Chỉ xử lý submit khi thực sự cần thiết
            if (e.target.id === 'editMaintenanceRequestForm') {
                e.preventDefault();
                updateMaintenanceRequest();
            }
        });
    }

    // Xử lý form tạo case bảo trì
    $('#addMaintenanceCaseForm').on('submit', function(e) {
        e.preventDefault();
        createMaintenanceCase();
    });

    // Xử lý form cập nhật case bảo trì
    $('#editMaintenanceCaseForm').on('submit', function(e) {
        e.preventDefault();
        updateMaintenanceCase();
    });

    // Xử lý form tạo task bảo trì
    $('#addMaintenanceTaskForm').on('submit', function(e) {
        e.preventDefault();
        createMaintenanceTask();
    });

    // Xử lý form cập nhật task bảo trì
    $('#editMaintenanceTaskForm').on('submit', function(e) {
        e.preventDefault();
        updateMaintenanceTask();
    });

    // Load mã yêu cầu tiếp theo khi mở modal tạo mới
    $('#addMaintenanceRequestModal').on('show.bs.modal', function() {
        loadNextRequestNumber();
    });

    // Load mã case tiếp theo khi mở modal tạo case
    $('#addMaintenanceCaseModal').on('show.bs.modal', function() {
        loadNextCaseNumber();
    });

    // Load mã task tiếp theo khi mở modal tạo task
    $('#addMaintenanceTaskModal').on('show.bs.modal', function() {
        loadNextTaskNumber();
    });
    
    // Ẩn bảng case bảo trì khi đóng modal edit yêu cầu (chỉ khi modal tồn tại)
    if ($('#editMaintenanceRequestModal').length > 0) {
        $('#editMaintenanceRequestModal').on('hidden.bs.modal', function() {
            $('#maintenance-cases-section').hide();
        });
    }
});

// Load danh sách yêu cầu bảo trì
function loadMaintenanceRequests() {
    $.ajax({
        url: 'api/get_maintenance_requests.php',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (!data.success || !Array.isArray(data.data)) {
                console.error('API error:', data.message);
                showAlert('Lỗi: ' + (data.message || 'Dữ liệu không hợp lệ'), 'error');
                return;
            }
            
            displayMaintenanceRequests(data.data);
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            showAlert('Có lỗi xảy ra khi tải danh sách yêu cầu bảo trì', 'error');
        }
    });
}

// Hiển thị danh sách yêu cầu bảo trì
function displayMaintenanceRequests(requests) {
    // Tìm table body
    let tbody = document.getElementById('maintenance-requests-table');
    
    if (!tbody) {
        console.error('Table body not found');
        return;
    }
    
    tbody.innerHTML = '';

    if (!requests || requests.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="11" class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">Chưa có yêu cầu bảo trì nào</h5>
                    <p class="text-muted">Bấm nút "Tạo yêu cầu bảo trì" để bắt đầu</p>
                </td>
            </tr>
        `;
        return;
    }

    requests.forEach(request => {
        const row = `
            <tr>
                <td><strong class="text-warning">${request.request_code}</strong></td>
                <td>
                    <div class="contract-info">
                        <div class="fw-bold">${request.contract_type || 'N/A'}</div>
                        <small class="text-muted">${request.request_detail_type || 'N/A'}</small>
                    </div>
                </td>
                <td>
                    <div class="customer-info">
                        <div class="fw-bold">${request.customer_name || 'N/A'}</div>
                        <small class="text-muted">
                            <i class="fas fa-user me-1"></i>${request.contact_person || 'N/A'}
                        </small><br>
                        <small class="text-muted">
                            <i class="fas fa-phone me-1"></i>${request.contact_phone || 'N/A'}
                        </small>
                    </div>
                </td>
                <td><span class="text-dark">${request.sale_name || 'N/A'}</span></td>
                <td>
                    ${request.expected_start ? 
                        `<div class="text-wrap" style="white-space: pre-line;">
                            <strong>Từ</strong><br>
                            ${formatDate(request.expected_start)}<br>
                            <strong>Đến</strong><br>
                            ${request.expected_end ? formatDate(request.expected_end) : '(Chưa xác định)'}
                        </div>` : 
                        '<span class="text-muted">Chưa có</span>'
                    }
                </td>
                <td>
                    ${request.requester_notes ? 
                        `<div class="text-wrap" style="max-width: 200px; white-space: pre-wrap; word-wrap: break-word;">
                            ${request.requester_notes}
                        </div>` : 
                        '<span class="text-muted">-</span>'
                    }
                </td>
                <td><span class="text-dark">${request.maintenance_status || 'N/A'}</span></td>
                <td><span class="text-dark">${request.total_cases || 0}</span></td>
                <td><span class="text-dark">${request.total_tasks || 0}</span></td>
                <td>
                    <div class="progress" style="width: 80px; height: 20px;">
                        <div class="progress-bar bg-warning" style="width: ${request.progress_percentage || 0}%" title="${request.progress_percentage || 0}%">
                            <small>${request.progress_percentage || 0}%</small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-${getStatusClass(request.maintenance_status)}">
                        ${request.maintenance_status || 'N/A'}
                    </span>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-warning" onclick="editMaintenanceRequest(${request.id})" title="Chỉnh sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteMaintenanceRequest(${request.id})" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

// Tạo yêu cầu bảo trì
function createMaintenanceRequest() {
    // Validation
    const requiredFields = ['request_code', 'customer_id', 'sale_id', 'maintenance_status'];
    let isValid = true;
    
    requiredFields.forEach(function(fieldId) {
        const field = $('#' + fieldId);
        const value = field.val();
        if (!value) {
            isValid = false;
            field.addClass('is-invalid');
        } else {
            field.removeClass('is-invalid');
        }
    });
    
    if (!isValid) {
        showAlert('Vui lòng điền đầy đủ các trường bắt buộc', 'error');
        return;
    }
    
    // Tạo FormData object
    const formData = new FormData();
    formData.append('request_code', $('#request_code').val());
    formData.append('po_number', $('#po_number').val());
    formData.append('no_contract_po', $('#no_contract_po').is(':checked') ? 1 : 0);
    formData.append('contract_type', $('#contract_type').val());
    formData.append('request_detail_type', $('#request_detail_type').val());
    formData.append('email_subject_customer', $('#email_subject_customer').val());
    formData.append('email_subject_internal', $('#email_subject_internal').val());
    formData.append('expected_start', $('#expected_start').val());
    formData.append('expected_end', $('#expected_end').val());
    formData.append('customer_id', $('#customer_id').val());
    formData.append('contact_person', $('#contact_person').val());
    formData.append('contact_phone', $('#contact_phone').val());
    formData.append('sale_id', $('#sale_id').val());
    formData.append('requester_notes', $('#requester_notes').val());
    formData.append('maintenance_manager', $('#maintenance_manager').val());
    formData.append('maintenance_status', $('#maintenance_status').val());

    $.ajax({
        url: 'api/create_maintenance_request.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                $('#addMaintenanceRequestModal').modal('hide');
                $('#addMaintenanceRequestForm')[0].reset();
                // Reload toàn bộ trang để đảm bảo modal edit được render lại
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                showAlert(response.error || response.message, 'error');
            }
        },
        error: function() {
            showAlert('Có lỗi xảy ra khi tạo yêu cầu bảo trì', 'error');
        }
    });
}

// Cập nhật yêu cầu bảo trì
function updateMaintenanceRequest() {
    // Prevent multiple calls
    if (window.isUpdatingRequest) {
        return;
    }
    
    // Kiểm tra xem form có bị disable tạm thời không
    const form = document.getElementById('editMaintenanceRequestForm');
    if (form && (form.hasAttribute('data-submit-disabled') || isOpeningCaseModal)) {
        return;
    }
    
    window.isUpdatingRequest = true;
    const formData = {
        id: $('#edit_request_id').val(),
        request_code: $('#edit_request_code').val(),
        po_number: $('#edit_po_number').val(),
        no_contract_po: $('#edit_no_contract_po').is(':checked') ? 1 : 0,
        contract_type: $('#edit_contract_type').val(),
        request_detail_type: $('#edit_request_detail_type').val(),
        email_subject_customer: $('#edit_email_subject_customer').val(),
        email_subject_internal: $('#edit_email_subject_internal').val(),
        expected_start: $('#edit_expected_start').val(),
        expected_end: $('#edit_expected_end').val(),
        customer_id: $('#edit_customer_id').select2('val') || $('#edit_customer_id').val(),
        contact_person: $('#edit_contact_person').val(),
        contact_phone: $('#edit_contact_phone').val(),
        sale_id: $('#edit_sale_id').select2('val') || $('#edit_sale_id').val(),
        requester_notes: $('#edit_requester_notes').val(),
        maintenance_manager: $('#edit_maintenance_manager').val(),
        maintenance_status: $('#edit_maintenance_status').val()
    };
    

    
    // Validation client-side
    if (!formData.id) {
        showAlert('ID yêu cầu không được để trống', 'error');
        return;
    }
    
    if (!formData.request_code) {
        showAlert('Mã yêu cầu không được để trống', 'error');
        return;
    }
    
    if (!formData.customer_id) {
        showAlert('Vui lòng chọn khách hàng', 'error');
        return;
    }
    
    // Tạm thời bỏ validation sale_id
    // if (!formData.sale_id) {
    //     showAlert('Vui lòng chọn sale phụ trách', 'error');
    //     return;
    // }
    
    if (!formData.maintenance_status) {
        showAlert('Vui lòng chọn trạng thái bảo trì', 'error');
        return;
    }

    // Disable submit button to prevent duplicate requests
    const submitBtn = $('#editMaintenanceRequestForm button[type="submit"]');
    submitBtn.prop('disabled', true).text('Đang cập nhật...');
    
    $.ajax({
        url: 'api/update_maintenance_request.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(formData),
        timeout: 10000, // 10 seconds timeout
        cache: false, // Disable cache
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                $('#editMaintenanceRequestModal').modal('hide');
                loadMaintenanceRequests();
            } else {
                showAlert(response.error || response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            
            if (status === 'timeout') {
                showAlert('Request timeout. Vui lòng thử lại.', 'error');
            } else if (xhr.status === 400) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    showAlert(response.error || 'Dữ liệu không hợp lệ', 'error');
                } catch (e) {
                    showAlert('Có lỗi xảy ra khi cập nhật yêu cầu bảo trì', 'error');
                }
            } else {
                showAlert('Có lỗi xảy ra khi cập nhật yêu cầu bảo trì', 'error');
            }
        },
        complete: function() {
            // Re-enable submit button
            submitBtn.prop('disabled', false).text('Cập nhật yêu cầu');
            window.isUpdatingRequest = false;
        }
    });
}

// Xóa yêu cầu bảo trì
function deleteMaintenanceRequest(id) {
    if (confirm('Bạn có chắc chắn muốn xóa yêu cầu bảo trì này?')) {
        $.ajax({
            url: 'api/delete_maintenance_request.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ id: id }),
                    success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                loadMaintenanceRequests();
            } else {
                showAlert(response.message, 'error');
            }
        },
        error: function() {
            showAlert('Có lỗi xảy ra khi xóa yêu cầu bảo trì', 'error');
        }
        });
    }
}

// Xem chi tiết yêu cầu bảo trì
function viewMaintenanceRequest(id) {
    $.ajax({
        url: 'api/get_maintenance_request.php',
        type: 'GET',
        data: { id: id },
        success: function(response) {
            if (response.success) {
                const request = response.data;
                $('#view_request_code').text(request.request_code);
                $('#view_customer_name').text(request.customer_name || 'N/A');
                $('#view_sale_name').text(request.sale_name || 'N/A');
                $('#view_maintenance_status').text(request.maintenance_status || 'N/A');
                $('#viewMaintenanceRequestModal').modal('show');
            } else {
                showAlert(response.message, 'error');
            }
        },
        error: function() {
            showAlert('Có lỗi xảy ra khi tải thông tin yêu cầu bảo trì', 'error');
        }
    });
}

// Chỉnh sửa yêu cầu bảo trì
function editMaintenanceRequest(id) {
    // Kiểm tra xem modal edit có tồn tại không
    if ($('#editMaintenanceRequestModal').length === 0) {
        showAlert('Modal chỉnh sửa không tồn tại. Vui lòng reload trang và thử lại.', 'error');
        return;
    }
    
    $.ajax({
        url: 'api/get_maintenance_request.php',
        type: 'GET',
        data: { id: id },
        success: function(response) {
            if (response.success) {
                const request = response.data;
                $('#edit_request_id').val(request.id);
                $('#edit_request_code').val(request.request_code);
                
                // Lưu request_id vào sessionStorage để sử dụng sau này
                if (typeof sessionStorage !== 'undefined') {
                    sessionStorage.setItem('current_maintenance_request_id', request.id);
                }
                $('#edit_po_number').val(request.po_number);
                $('#edit_no_contract_po').prop('checked', request.no_contract_po == 1);
                $('#edit_contract_type').val(request.contract_type);
                $('#edit_request_detail_type').val(request.request_detail_type);
                $('#edit_email_subject_customer').val(request.email_subject_customer);
                $('#edit_email_subject_internal').val(request.email_subject_internal);
                $('#edit_expected_start').val(request.expected_start);
                $('#edit_expected_end').val(request.expected_end);
                $('#edit_customer_id').val(request.customer_id).trigger('change');
                $('#edit_contact_person').val(request.contact_person);
                $('#edit_contact_phone').val(request.contact_phone);
                $('#edit_sale_id').val(request.sale_id).trigger('change');
                $('#edit_requester_notes').val(request.requester_notes);
                $('#edit_maintenance_manager').val(request.maintenance_manager);
                $('#edit_maintenance_status').val(request.maintenance_status);
                
                // Tự động điền thông tin liên hệ nếu có customer_id
                if (request.customer_id) {
                    // Tìm thông tin khách hàng từ dữ liệu đã có
                    const selectedPartner = partnerData.find(partner => partner.id == request.customer_id);
                    if (selectedPartner) {
                        $('#edit_contact_person').val(selectedPartner.contact_person || request.contact_person || 'Chưa có thông tin');
                        $('#edit_contact_phone').val(selectedPartner.contact_phone || request.contact_phone || 'Chưa có thông tin');
                    }
                }
                
                $('#editMaintenanceRequestModal').modal('show');
                
                // Hiển thị bảng case bảo trì khi có yêu cầu được chọn
                $('#maintenance-cases-section').show();
                
                // Load danh sách case bảo trì cho yêu cầu này
                loadMaintenanceCases(request.id);
            } else {
                showAlert(response.message, 'error');
            }
        },
        error: function() {
            showAlert('Có lỗi xảy ra khi tải thông tin yêu cầu bảo trì', 'error');
        }
    });
}

// Load mã yêu cầu tiếp theo
function loadNextRequestNumber() {
    $.ajax({
        url: 'api/get_next_maintenance_request_number.php',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                $('#request_code').val(response.request_code);
            }
        }
    });
}

// Load mã case tiếp theo
function loadNextCaseNumber() {
    $.ajax({
        url: 'api/get_next_maintenance_case_number.php',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                $('#case_code').val(response.case_code);
            }
        }
    });
}

// Load mã task tiếp theo
function loadNextTaskNumber() {
    $.ajax({
        url: 'api/get_next_maintenance_task_number_simple.php',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                $('#task_number').val(response.task_code);
            }
        }
    });
}

// Tạo case bảo trì
function createMaintenanceCase() {
    // Validation trước khi submit
    const caseCode = $('#case_code').val();
    const requestType = $('#case_request_type').val();
    const assignedTo = $('#case_assigned_to').val();
    const maintenanceRequestId = $('#maintenance_request_id').val();
    
    if (!caseCode) {
        showAlert('Mã case không được để trống', 'error');
        return;
    }
    
    if (!requestType) {
        showAlert('Loại yêu cầu không được để trống', 'error');
        return;
    }
    
    if (!assignedTo) {
        showAlert('Vui lòng chọn người được giao', 'error');
        return;
    }
    
    if (!maintenanceRequestId) {
        showAlert('ID yêu cầu bảo trì không được để trống', 'error');
        return;
    }
    
    const formData = {
        case_code: caseCode,
        request_type: requestType,
        request_detail_type: $('#case_request_detail_type').val(),
        progress: 0, // Default value since progress field was removed
        case_description: $('#case_description').val(),
        notes: $('#case_notes').val(),
        assigned_to: assignedTo,
        work_type: $('#case_work_type').val(),
        start_date: $('#case_start_date').val(),
        end_date: $('#case_end_date').val(),
        status: $('#case_status').val(),
        maintenance_request_id: maintenanceRequestId
    };
    




    $.ajax({
        url: 'api/create_maintenance_case.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(formData),
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                $('#createMaintenanceCaseModal').modal('hide');
                $('#createMaintenanceCaseForm')[0].reset();
                
                // Đảm bảo load lại danh sách cases sau khi tạo thành công
                setTimeout(function() {
                    loadMaintenanceCases();
                }, 100);
            } else {
                showAlert(response.error || response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            showAlert('Có lỗi xảy ra khi tạo case bảo trì', 'error');
        }
    });
}

// Cập nhật case bảo trì
function updateMaintenanceCase() {
    const formData = {
        id: $('#edit_case_id').val(),
        case_code: $('#edit_case_code').val(),
        request_type: $('#edit_request_type').val(),
        request_detail_type: $('#edit_request_detail_type').val(),
        case_description: $('#edit_case_description').val(),
        notes: $('#edit_notes').val(),
        assigned_to: $('#edit_assigned_to').val(),
        work_type: $('#edit_work_type').val(),
        start_date: $('#edit_start_date').val(),
        end_date: $('#edit_end_date').val(),
        status: $('#edit_status').val()
    };

    $.ajax({
        url: 'api/update_maintenance_case.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(formData),
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                $('#editMaintenanceCaseModal').modal('hide');
                loadMaintenanceCases();
            } else {
                showAlert(response.error || response.message, 'error');
            }
        },
        error: function() {
            showAlert('Có lỗi xảy ra khi cập nhật case bảo trì', 'error');
        }
    });
}

// Xóa case bảo trì
function deleteMaintenanceCase(id) {
    if (confirm('Bạn có chắc chắn muốn xóa case bảo trì này?')) {
        $.ajax({
            url: 'api/delete_maintenance_case.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ id: id }),
                    success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                loadMaintenanceCases();
            } else {
                showAlert(response.message, 'error');
            }
        },
        error: function() {
            showAlert('Có lỗi xảy ra khi xóa case bảo trì', 'error');
        }
        });
    }
}

// Load danh sách cases bảo trì
function loadMaintenanceCases() {
    // Lấy request ID từ nhiều nguồn khác nhau
    let requestId = $('#edit_request_id').val();
    
    // Nếu không có từ modal edit, thử lấy từ modal tạo case
    if (!requestId) {
        requestId = $('#maintenance_request_id').val();
    }
    
    // Nếu vẫn không có, thử lấy từ URL parameter hoặc session
    if (!requestId) {
        // Có thể lưu request_id vào sessionStorage khi mở modal edit
        requestId = sessionStorage.getItem('current_maintenance_request_id');
    }
    
    if (!requestId) {
        console.error('No request ID found from any source');
        return;
    }
    
    $.ajax({
        url: 'api/get_maintenance_cases.php',
        type: 'GET',
        data: { maintenance_request_id: requestId },
        success: function(response) {
            if (response.success) {
                displayMaintenanceCases(response.data);
            } else {
                showAlert('Lỗi: ' + response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            showAlert('Có lỗi xảy ra khi tải danh sách cases bảo trì', 'error');
        }
    });
}

// Hiển thị danh sách cases bảo trì
function displayMaintenanceCases(cases) {
    const tbody = $('#maintenance-cases-table');
    tbody.empty();

    if (!cases || cases.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="12" class="text-center text-muted py-3">
                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                    Chưa có case bảo trì nào
                </td>
            </tr>
        `);
        return;
    }

    cases.forEach((case_item, index) => {
        const row = `
            <tr>
                <td class="text-center">${index + 1}</td>
                <td class="text-center"><strong class="text-primary">${case_item.case_code || ''}</strong></td>
                <td class="text-center">${case_item.case_description || ''}</td>
                <td class="text-center">${case_item.notes || ''}</td>
                <td class="text-center">${case_item.assigned_to_name || ''}</td>
                <td class="text-center">${formatDate(case_item.start_date)}</td>
                <td class="text-center">${formatDate(case_item.end_date)}</td>
                <td class="text-center">
                    <span class="badge bg-${(case_item.status === 'Hoàn thành' ? 'success' : (case_item.status === 'Đang xử lý' ? 'warning' : (case_item.status === 'Huỷ' ? 'danger' : 'secondary')))}">
                        ${case_item.status || 'Tiếp nhận'}
                    </span>
                </td>
                <td class="text-center">${case_item.total_tasks || 0}</td>
                <td class="text-center">${case_item.completed_tasks || 0}</td>
                <td class="text-center">${case_item.work_type || ''}</td>
                <td class="text-center">
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-outline-warning" onclick="editMaintenanceCase(${case_item.id})" title="Chỉnh sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteMaintenanceCase(${case_item.id})" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

// Chỉnh sửa case bảo trì
function editMaintenanceCase(id) {
    // Ngăn chặn event bubbling để tránh form submission không mong muốn
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    // Set flag để ngăn chặn form submission
    isOpeningCaseModal = true;
    setTimeout(() => {
        isOpeningCaseModal = false;
    }, 3000);
    
    // Tạm thời disable form submission để tránh submit nhầm
    const editRequestForm = document.getElementById('editMaintenanceRequestForm');
    if (editRequestForm) {
        editRequestForm.setAttribute('data-submit-disabled', 'true');
        setTimeout(() => {
            editRequestForm.removeAttribute('data-submit-disabled');
        }, 3000);
    }
    
    $.ajax({
        url: 'api/get_maintenance_case_details.php',
        type: 'GET',
        data: { id: id },
        success: function(response) {
            if (response.success) {
                const case_item = response.data;
                
                // Hiển thị modal trước khi set giá trị để tránh trigger events
                $('#editMaintenanceCaseModal').modal('show');
                
                // Set giá trị sau khi modal đã hiển thị
                setTimeout(function() {
                    $('#edit_case_id').val(case_item.id);
                    $('#edit_maintenance_request_id').val(case_item.maintenance_request_id);
                    $('#edit_case_code').val(case_item.case_code || '');
                    $('#edit_case_request_code').val(case_item.request_code || '');
                    $('#edit_request_type').val(case_item.request_type || '');
                    $('#edit_case_request_detail_type').val(case_item.request_detail_type || '');
                    $('#edit_case_description').val(case_item.case_description || '');
                    $('#edit_notes').val(case_item.notes || '');
                    $('#edit_assigned_to').val(case_item.assigned_to || '');
                    $('#edit_work_type').val(case_item.work_type || '');
                    $('#edit_start_date').val(case_item.start_date && case_item.start_date !== '0000-00-00 00:00:00' ? case_item.start_date.split(' ')[0] : '');
                    $('#edit_end_date').val(case_item.end_date && case_item.end_date !== '0000-00-00 00:00:00' ? case_item.end_date.split(' ')[0] : '');
                    $('#edit_status').val(case_item.status || 'Tiếp nhận');
                }, 100);
            } else {
                showAlert(response.message, 'error');
            }
        },
        error: function() {
            showAlert('Có lỗi xảy ra khi tải thông tin case bảo trì', 'error');
        }
    });
}

// Tạo task bảo trì
function createMaintenanceTask() {
    const formData = {
        task_number: $('#task_number').val(),
        task_name: $('#task_name').val(),
        task_type: $('#task_type').val(),
        task_template: $('#task_template').val(),
        assigned_to: $('#task_assignee_id').val(),
        start_date: $('#task_start_date').val(),
        end_date: $('#task_end_date').val(),
        status: $('#task_status').val(),
        notes: $('#task_note').val(),
        maintenance_case_id: $('#task_maintenance_case_id').val(),
        maintenance_request_id: $('#task_maintenance_request_id').val()
    };

    $.ajax({
        url: 'api/create_maintenance_task.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(formData),
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                $('#createMaintenanceTaskForm')[0].reset();
                
                // Đóng modal tạo task trước
                $('#createMaintenanceTaskModal').modal('hide');
                
                // Reload danh sách tasks trong modal edit case
                setTimeout(() => {
                    loadMaintenanceTasks();
                }, 300);
                
                // Cũng reload danh sách cases để cập nhật số lượng tasks
                setTimeout(() => {
                    loadMaintenanceCases();
                }, 300);
                
                // Đảm bảo modal edit case vẫn mở và hiển thị tasks mới
                if ($('#editMaintenanceCaseModal').hasClass('show')) {
                    setTimeout(() => {
                        loadMaintenanceTasks();
                    }, 500);
                }
            } else {
                showAlert(response.error || response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', xhr.responseText);
            showAlert('Có lỗi xảy ra khi tạo task bảo trì', 'error');
        }
    });
}

// Cập nhật task bảo trì
function updateMaintenanceTask() {
    const formData = {
        id: $('#edit_task_id').val(),
        task_number: $('#edit_task_code').val(),
        task_type: $('#edit_task_type').val(),
        template_name: $('#edit_task_template').val(),
        task_description: $('#edit_task_name').val(),
        assignee_id: $('#edit_task_assigned_to').val(),
        start_date: $('#edit_task_start_date').val() || null,
        end_date: $('#edit_task_end_date').val() || null,
        status: $('#edit_task_status').val(),
        notes: $('#edit_task_note').val()
    };

    $.ajax({
        url: 'api/update_maintenance_task.php',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(formData),
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                $('#editMaintenanceTaskModal').modal('hide');
                loadMaintenanceTasks();
                // Cũng reload danh sách cases để cập nhật số lượng tasks
                loadMaintenanceCases();
            } else {
                showAlert(response.error || response.message, 'error');
            }
        },
        error: function() {
            showAlert('Có lỗi xảy ra khi cập nhật task bảo trì', 'error');
        }
    });
}

// Đóng modal chỉnh sửa case bảo trì
function closeEditCaseModal() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('editMaintenanceCaseModal'));
    if (modal) {
        modal.hide();
    }
}

// Xóa task bảo trì
function deleteMaintenanceTask(id) {
    if (confirm('Bạn có chắc chắn muốn xóa task bảo trì này?')) {
        $.ajax({
            url: 'api/delete_maintenance_task.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ id: id }),
                    success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                loadMaintenanceTasks();
            } else {
                showAlert(response.message, 'error');
            }
        },
        error: function() {
            showAlert('Có lỗi xảy ra khi xóa task bảo trì', 'error');
        }
        });
    }
}

// Load danh sách tasks bảo trì (cho modal edit case)
function loadMaintenanceTasks() {
    // Lấy case ID từ nhiều nguồn khác nhau
    let caseId = $('#edit_case_id').val();
    let requestId = $('#edit_maintenance_request_id').val();
    
    // Nếu không có từ modal edit case, thử lấy từ modal tạo task
    if (!caseId) {
        caseId = $('#task_maintenance_case_id').val();
        requestId = $('#task_maintenance_request_id').val();
    }
    
    // Nếu vẫn không có, thử lấy từ các trường khác
    if (!caseId) {
        caseId = $('#edit_task_case_id').val();
        requestId = $('#edit_task_request_id').val();
    }
    
    if (!caseId) {
        console.warn('No case ID found, cannot load maintenance tasks');
        return;
    }
    
    $.ajax({
        url: 'api/get_maintenance_tasks.php',
        type: 'GET',
        data: { 
            maintenance_case_id: caseId,
            maintenance_request_id: requestId 
        },
        success: function(response) {
            if (response.success) {
                displayMaintenanceTasks(response.data);
            } else {
                console.error('API error:', response.message);
                showAlert('Lỗi: ' + response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading maintenance tasks:', error);
            showAlert('Có lỗi xảy ra khi tải danh sách tasks bảo trì', 'error');
        }
    });
}



// Hiển thị danh sách tasks bảo trì
function displayMaintenanceTasks(tasks) {
    const tbody = $('#edit-maintenance-tasks-table');
    tbody.empty();

    if (!tasks || tasks.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="10" class="text-center text-muted py-3">
                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                    Chưa có task bảo trì nào
                </td>
            </tr>
        `);
        return;
    }

    tasks.forEach((task, index) => {
        const row = `
            <tr>
                <td class="text-center">${index + 1}</td>
                <td class="text-center"><strong class="text-primary">${task.task_number || task.task_code || 'N/A'}</strong></td>
                <td class="text-center">${task.task_type || 'N/A'}</td>
                <td class="text-center">${task.template_name || 'N/A'}</td>
                <td class="text-center">${task.task_description || 'N/A'}</td>
                <td class="text-center">${formatDateTimeForDisplay(task.start_date)}</td>
                <td class="text-center">${formatDateTimeForDisplay(task.end_date)}</td>
                <td class="text-center">${task.assignee_name || 'N/A'}</td>
                <td class="text-center">
                    <span class="badge bg-${(task.status === 'Hoàn thành' ? 'success' : (task.status === 'Đang xử lý' ? 'warning' : (task.status === 'Huỷ' ? 'danger' : 'secondary')))}">
                        ${task.status || 'Tiếp nhận'}
                    </span>
                </td>
                <td class="text-center">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-warning" onclick="console.log('Edit button clicked for task:', ${task.id}); editMaintenanceTask(${task.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteMaintenanceTask(${task.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}



// Chỉnh sửa task bảo trì
function editMaintenanceTask(id) {
    console.log('editMaintenanceTask called with id:', id);
    
    // Lưu task ID để sử dụng sau khi load staff
    window.currentEditTaskId = id;
    
    // Load danh sách staff trước
    $.ajax({
        url: 'api/get_it_staffs.php',
        type: 'GET',
        success: function(response) {
            console.log('Staff loaded successfully:', response);
            const select = document.getElementById('edit_task_assigned_to');
            if (select) {
                select.innerHTML = '<option value="">-- Chọn người thực hiện --</option>';
                if (response.success && Array.isArray(response.data)) {
                    response.data.forEach(staff => {
                        const option = document.createElement('option');
                        option.value = staff.id;
                        option.textContent = staff.fullname;
                        select.appendChild(option);
                    });
                }
                
                // Sau khi load staff xong, load thông tin task
                loadTaskDetails(window.currentEditTaskId);
            } else {
                console.error('edit_task_assigned_to select not found');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading staff:', error);
            showAlert('Có lỗi xảy ra khi tải danh sách nhân viên', 'error');
        }
    });
}

// Hàm load thông tin task sau khi đã load staff
function loadTaskDetails(taskId) {
    console.log('loadTaskDetails called with taskId:', taskId);
    
    $.ajax({
        url: 'api/get_maintenance_task_details.php',
        type: 'GET',
        data: { id: taskId },
        success: function(response) {
            console.log('Task details loaded:', response);
            if (response.success) {
                const task = response.data;
                $('#edit_task_id').val(task.id);
                $('#edit_task_code').val(task.task_number);
                $('#edit_task_type').val(task.task_type);
                $('#edit_task_template').val(task.template_name);
                $('#edit_task_name').val(task.task_description);
                $('#edit_task_note').val(task.notes);
                $('#edit_task_assigned_to').val(task.assignee_id).trigger('change');
                
                // Xử lý ngày tháng
                if (task.start_date && task.start_date !== '0000-00-00 00:00:00') {
                    $('#edit_task_start_date').val(task.start_date.split(' ')[0]);
                } else {
                    $('#edit_task_start_date').val('');
                }
                
                if (task.end_date && task.end_date !== '0000-00-00 00:00:00') {
                    $('#edit_task_end_date').val(task.end_date.split(' ')[0]);
                } else {
                    $('#edit_task_end_date').val('');
                }
                
                $('#edit_task_status').val(task.status);
                $('#edit_task_progress').val(task.progress || 0);
                $('#edit_task_notes').val(task.notes);
                
                console.log('Showing editMaintenanceTaskModal');
                const modal = document.getElementById('editMaintenanceTaskModal');
                console.log('Modal element:', modal);
                if (modal) {
                    $('#editMaintenanceTaskModal').modal('show');
                } else {
                    console.error('Modal not found in DOM');
                    showAlert('Modal không được tìm thấy', 'error');
                }
            } else {
                showAlert(response.message, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading task details:', error);
            showAlert('Có lỗi xảy ra khi tải thông tin task bảo trì', 'error');
        }
    });
}

// Format date
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN');
}

// Format datetime for display
function formatDateTimeForDisplay(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('vi-VN');
}

// Function get status class for badge
function getStatusClass(status) {
    switch (status) {
        case 'Hoàn thành':
            return 'success';
        case 'Đang xử lý':
            return 'warning';
        case 'Huỷ':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Show alert function
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alert-container');
    if (!alertContainer) return;
    
    let alertClass = 'alert-default';
    
    switch (type) {
        case 'success':
            alertClass = 'alert-success';
            break;
        case 'error':
            alertClass = 'alert-danger';
            break;
        case 'warning':
            alertClass = 'alert-warning';
            break;
        case 'info':
        default:
            alertClass = 'alert-info';
            break;
    }
    
    const alertId = 'alert-' + Date.now();
    const alertHtml = `
        <div id="${alertId}" class="alert ${alertClass}">
            <div class="alert-icon"></div>
            <div class="alert-content">${message}</div>
            <button type="button" class="alert-close" onclick="closeAlert('${alertId}')">×</button>
            <div class="alert-progress" style="width: 100%; animation: progress 4s linear forwards;"></div>
        </div>
    `;
    
    alertContainer.insertAdjacentHTML('beforeend', alertHtml);
    
    // Show animation
    setTimeout(() => {
        const alert = document.getElementById(alertId);
        if (alert) {
            alert.classList.add('show');
        }
    }, 100);
    
    // Auto remove after 4 seconds
    setTimeout(() => {
        closeAlert(alertId);
    }, 4000);
}

// Close alert function
function closeAlert(alertId) {
    const alert = document.getElementById(alertId);
    if (alert) {
        alert.classList.add('hide');
        setTimeout(() => {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 300);
    }
}

// Load dữ liệu ban đầu
$(document).ready(function() {
    loadMaintenanceRequests();
}); 