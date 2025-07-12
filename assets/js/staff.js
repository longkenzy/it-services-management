/**
 * IT Services Management - Staff Page JavaScript
 * File: assets/js/staff.js
 * Mục đích: Xử lý tương tác và hiển thị dữ liệu nhân sự
 */

$(document).ready(function() {
    
    // ===== KHỞI TẠO CÁC BIẾN ===== //
    let currentPage = 1;
    let currentLimit = 10;
    let currentFilters = {
        search: '',
        department: '',
        position: '',
        sort_by: 'start_date',
        sort_order: 'ASC',
        gender: ''
    };
    let selectedStaffs = [];
    let staffData = [];
    let isLoading = false;

    
    // ===== KHỞI TẠO TRANG ===== //
    function init() {
        setupEventListeners();
        // Ép dropdown luôn chọn đúng mặc định
        $('#sortFilter').val('start_date:ASC');
        currentFilters.sort_by = 'start_date';
        currentFilters.sort_order = 'ASC';
        loadStaffData();
    }
    
    // ===== THIẾT LẬP EVENT LISTENERS ===== //
    function setupEventListeners() {
        // Header interactions
        setupHeaderEventListeners();
        
        // Search input
        $('#staffSearchInput').on('input', debounce(function() {
            currentFilters.search = $(this).val().trim();
            currentPage = 1;
            loadStaffData();
        }, 500));
        
        // Remove duplicate - handled in setupHeaderEventListeners
        
        // Filter selects
        $('#departmentFilter').on('change', function() {
            currentFilters.department = $(this).val();
            currentPage = 1;
            loadStaffData();
        });
        
        $('#positionFilter').on('change', function() {
            currentFilters.position = $(this).val();
            console.log('Filter by position:', currentFilters.position); // DEBUG
            currentPage = 1;
            loadStaffData();
        });

        $('#genderFilter').on('change', function() {
            currentFilters.gender = $(this).val();
            currentPage = 1;
            loadStaffData();
        });
        
        $('#sortFilter').on('change', function() {
            const sortValue = $(this).val().split(':');
            currentFilters.sort_by = sortValue[0];
            currentFilters.sort_order = sortValue[1];
            currentPage = 1;
            loadStaffData();
        });
        
        // Reset filter
        $('#btnResetFilter').on('click', function() {
            resetFilters();
        });
        
        // Add staff buttons
        $('#btnAddStaff, #btnAddFirstStaff').on('click', function() {
            showAddStaffModal();
        });
        
        // Add staff form handlers
        setupAddStaffFormHandlers();
        
        // Select all checkbox
        $('#selectAllCheckbox').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.staff-checkbox').prop('checked', isChecked);
            updateSelectedStaffs();
        });
        
        // Bulk actions
        $('#btnSelectAll').on('click', function() {
            $('.staff-checkbox').prop('checked', true);
            $('#selectAllCheckbox').prop('checked', true);
            updateSelectedStaffs();
        });
        
        $('#btnExport').on('click', function() {
            exportStaffData();
        });
        
        // Additional keyboard shortcuts (Ctrl+K handled in setupHeaderEventListeners)
        $(document).on('keydown', function(e) {
            // Ctrl+N for add new staff
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                showAddStaffModal();
            }
        });
    }
    
    // ===== LOAD DỮ LIỆU NHÂN SỰ ===== //
    function loadStaffData() {
        if (isLoading) return;
        
        isLoading = true;
        showLoadingState();
        
        // Tạo URL với parameters
        const params = new URLSearchParams({
            page: currentPage,
            limit: currentLimit,
            ...currentFilters
        });
        
        // Gọi API
        $.ajax({
            url: `api/get_staffs.php?${params.toString()}`,
            method: 'GET',
            dataType: 'json',
            timeout: 10000,
            success: function(response) {
                isLoading = false;
                hideLoadingState();
                
                if (response.success) {
                    staffData = response.data.staffs;
                    renderStaffTable(staffData);
                    updateStatistics(response.data.statistics);
                    updateFilterOptions(response.data.statistics);
                    
                    // Show empty state if no data
                    if (staffData.length === 0) {
                        showEmptyState();
                    } else {
                        hideEmptyState();
                    }
                } else {
                    showErrorMessage(response.message || 'Không thể tải dữ liệu nhân sự');
                }
            },
            error: function(xhr, status, error) {
                isLoading = false;
                hideLoadingState();
                
                let errorMessage = 'Có lỗi xảy ra khi tải dữ liệu nhân sự';
                
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
    
    // ===== RENDER BẢNG NHÂN SỰ ===== //
    function renderStaffTable(staffs) {
        const tbody = $('#staffTableBody');
        tbody.empty();
        
        if (staffs.length === 0) {
            return;
        }
        
        staffs.forEach(staff => {
            const row = createStaffRow(staff);
            tbody.append(row);
        });
        
        // Setup row event listeners
        setupRowEventListeners();
        
        // Add fade-in animation
        tbody.find('tr').addClass('fade-in');
    }
    
    // ===== TẠO DÒNG NHÂN SỰ ===== //
    function createStaffRow(staff) {
        const contractClass = staff.job_type ? staff.job_type.toLowerCase().replace(/\s+/g, '-') : '';
        const genderClass = staff.gender ? staff.gender.toLowerCase() : '';
        
        return `
            <tr data-staff-id="${staff.id}">
                <td>
                    <div class="form-check">
                        <input class="form-check-input staff-checkbox" type="checkbox" value="${staff.id}">
                    </div>
                </td>
                <td>
                    <strong class="text-primary">${staff.staff_code}</strong>
                </td>
                <td>
                    <span class="fw-bold">${staff.fullname}</span>
                </td>
                <td>${staff.birth_date ? new Date(staff.birth_date).getFullYear() : ''}</td>
                <td>
                    <span class="gender-badge ${genderClass}">${staff.gender || ''}</span>
                </td>
                <td>
                    <img src="${staff.avatar}" alt="Avatar" class="staff-avatar" 
                         onerror="this.src='assets/images/default-avatar.svg'"
                         style="cursor: pointer;" onclick="showAvatarModal('${staff.avatar}', '${staff.fullname}')">
                </td>
                <td>
                    <span class="fw-semibold text-dark">${staff.position || ''}</span>
                </td>
                <td>
                    <span class="badge bg-secondary">${staff.department || ''}</span>
                </td>
                <td>
                    ${staff.phone ? `<a href="tel:${staff.phone}" class="text-decoration-none">
                        ${staff.phone}
                    </a>` : ''}
                </td>
                <td>
                    ${staff.email ? `<a href="mailto:${staff.email}" class="text-decoration-none">
                        ${staff.email}
                    </a>` : ''}
                </td>
                <td>
                    <span class="contract-badge ${contractClass}">${staff.job_type || ''}</span>
                </td>
                <td>
                    <div class="d-flex">
                        <button class="action-btn btn-view" onclick="viewStaff(${staff.id})" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn btn-edit" onclick="editStaff(${staff.id})" title="Chỉnh sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn btn-delete" onclick="deleteStaff(${staff.id})" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }
    
    // ===== THIẾT LẬP EVENT LISTENERS CHO DÒNG ===== //
    function setupRowEventListeners() {
        // Checkbox selection
        $('.staff-checkbox').on('change', function() {
            updateSelectedStaffs();
        });
        
        // Row click (except on checkboxes and action buttons)
        $('#staffTableBody tr').on('click', function(e) {
            if ($(e.target).is('input[type="checkbox"]') || 
                $(e.target).closest('.action-btn').length > 0 ||
                $(e.target).is('a') ||
                $(e.target).closest('a').length > 0) {
                return;
            }
            
            const staffId = $(this).data('staff-id');
            viewStaff(staffId);
        });
    }
    
    // ===== CẬP NHẬT THỐNG KÊ ===== //
    function updateStatistics(stats) {
        $('#totalStaff').text(stats.total_active_staff || 0);
        $('#totalDepartments').text(stats.departments ? stats.departments.length : 0);
        $('#totalPositions').text(stats.positions ? stats.positions.length : 0);
    }
    
    // ===== CẬP NHẬT TÙY CHỌN LỌC ===== //
    function updateFilterOptions(stats) {
        // Update department filter
        const deptFilter = $('#departmentFilter');
        const currentDept = deptFilter.val();
        deptFilter.find('option:not(:first)').remove();
        
        stats.departments.forEach(dept => {
            deptFilter.append(`<option value="${dept.department}">${dept.department} (${dept.count})</option>`);
        });
        deptFilter.val(currentDept);
        
        // Update position filter
        const posFilter = $('#positionFilter');
        const currentPos = posFilter.val();
        posFilter.find('option:not(:first)').remove();
        
        stats.positions.forEach(pos => {
            posFilter.append(`<option value="${pos.position}">${pos.position} (${pos.count})</option>`);
        });
        posFilter.val(currentPos);

        // Update gender filter
        const genderFilter = $('#genderFilter');
        const currentGender = genderFilter.val();
        genderFilter.find('option:not(:first)').remove();

        stats.genders.forEach(gender => {
            genderFilter.append(`<option value="${gender.gender}">${gender.gender} (${gender.count})</option>`);
        });
        genderFilter.val(currentGender);
    }
    
    // ===== HIỂN THỊ/ẨN TRẠNG THÁI ===== //
    function showLoadingState() {
        $('#loadingState').removeClass('d-none');
        $('#staffTableContainer').addClass('d-none');
        $('#emptyState').addClass('d-none');
    }
    
    function hideLoadingState() {
        $('#loadingState').addClass('d-none');
        $('#staffTableContainer').removeClass('d-none');
    }
    
    function showEmptyState() {
        $('#staffTableContainer').addClass('d-none');
        $('#emptyState').removeClass('d-none');
    }
    
    function hideEmptyState() {
        $('#emptyState').addClass('d-none');
        $('#staffTableContainer').removeClass('d-none');
    }
    
    // ===== RESET BỘ LỌC ===== //
    function resetFilters() {
        currentFilters = {
            search: '',
            department: '',
            position: '',
            sort_by: 'start_date',
            sort_order: 'ASC',
            gender: ''
        };
        
        $('#staffSearchInput').val('');
        $('#globalSearch').val('');
        $('#departmentFilter').val('');
        $('#positionFilter').val('');
        $('#genderFilter').val('');
        $('#sortFilter').val('start_date:ASC');
        
        currentPage = 1;
        loadStaffData();
    }
    
    // ===== CẬP NHẬT DANH SÁCH ĐƯỢC CHỌN ===== //
    function updateSelectedStaffs() {
        selectedStaffs = [];
        $('.staff-checkbox:checked').each(function() {
            selectedStaffs.push(parseInt($(this).val()));
        });
        
        // Update select all checkbox
        const totalCheckboxes = $('.staff-checkbox').length;
        const checkedCheckboxes = $('.staff-checkbox:checked').length;
        
        if (checkedCheckboxes === 0) {
            $('#selectAllCheckbox').prop('indeterminate', false).prop('checked', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            $('#selectAllCheckbox').prop('indeterminate', false).prop('checked', true);
        } else {
            $('#selectAllCheckbox').prop('indeterminate', true);
        }
        

    }
    
    // ===== HIỂN THỊ THÔNG BÁO ===== //
    function showErrorMessage(message) {
        showError(message);
    }
    
    function showSuccessMessage(message) {
        showSuccess(message);
    }
    
    // ===== POSITIONS MANAGEMENT ===== //
    
    function populatePositions(callback) {
        const select = $('#position_id');
        
        if (select.length === 0) {
            if (callback) callback();
            return;
        }
        
        // Load positions from API
        $.ajax({
            url: 'api/positions.php?action=list',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response && response.data) {
                    // Clear and populate
                    select.empty();
                    select.append('<option value="">--Chọn chức vụ--</option>');
                    
                    response.data.forEach(function(position) {
                        select.append(`<option value="${position.id}" data-department="${position.department_name}">${position.name}</option>`);
                    });
                }
                
                // Call callback when done
                if (callback) callback();
            },
            error: function(xhr, status, error) {
                if (callback) callback();
            }
        });
    }

    // ===== SHOW ADD STAFF MODAL ===== //
    function showAddStaffModal() {
        resetAddStaffForm();
        populatePositions(function() {
            $('#addStaffModal').modal('show');
            $('#staff_code').focus();
        });
    }
    
    // ===== SHOW EDIT STAFF MODAL ===== //
    function showEditStaffModal(staff) {
        // Thay đổi title và button của modal
        $('#addStaffModalLabel').html('<i class="fas fa-user-edit me-2"></i>Chỉnh sửa nhân sự');
        $('#addStaffForm button[type="submit"]').html('<i class="fas fa-save me-2"></i>Cập nhật nhân sự');
        
        // Thêm hidden input để lưu staff ID
        if (!$('#staff_id').length) {
            $('#addStaffForm').prepend('<input type="hidden" id="staff_id" name="staff_id">');
        }
        $('#staff_id').val(staff.id);
        
        // Populate positions first, then fill form data
        populatePositions(function() {
            // Điền toàn bộ dữ liệu vào form sau khi positions đã load xong
            // THÔNG TIN CHUNG
            $('#staff_code').val(staff.employee_code || staff.staff_code || '');
            $('#fullname').val(staff.fullname || '');
            $('#birth_date').val(staff.birth_date || '');
            $('#gender').val(staff.gender || '');
            $('#hometown').val(staff.hometown || '');
            $('#religion').val(staff.religion || '');
            $('#ethnicity').val(staff.ethnicity || '');
            
            // THỜI GIAN CÔNG TÁC
            $('#start_date').val(staff.start_date || '');
            $('#seniority').val(staff.seniority || 0);
            $('#seniority_display').val((staff.seniority || 0) + ' năm');
            
            // LIÊN HỆ
            $('#phone_main').val(staff.phone_main || '');
            $('#phone_alt').val(staff.phone_alt || '');
            $('#email_personal').val(staff.email_personal || staff.personal_email || '');
            $('#email_work').val(staff.email_work || staff.work_email || '');
            $('#place_of_birth').val(staff.place_of_birth || '');
            $('#address_perm').val(staff.address_perm || staff.address || '');
            $('#address_temp').val(staff.address_temp || '');
            
            // CÔNG VIỆC - Set position and department values
            $('#department').val(staff.department || '');
            $('#position_name').val(staff.position || '');
            
            $('#job_type').val(staff.job_type || '');
            $('#office').val(staff.office || staff.office_location || '');
            $('#office_address').val(staff.office_address || '');
            
            // TÀI KHOẢN ĐĂNG NHẬP
            $('#username').val(staff.username || '');
            // Password để trống khi edit
            $('#password').val('');
            $('#role').val(staff.role || 'user');
            
            // Xử lý avatar thông minh
            let avatarPath = staff.avatar ? staff.avatar.trim() : '';
            if (avatarPath) {
                // Nếu đã có dấu / thì dùng luôn, còn không thì nối đường dẫn
                if (!avatarPath.includes('/')) {
                    avatarPath = 'assets/uploads/avatars/' + avatarPath;
                }
                $('.react-logo-container').hide();
                $('#avatarPreview')
                    .attr('src', avatarPath)
                    .removeClass('d-none')
                    .show();
            } else {
                $('.react-logo-container').show();
                $('#avatarPreview')
                    .attr('src', 'assets/images/default-avatar.svg')
                    .addClass('d-none')
                    .hide();
            }
            
            // Tính toán và hiển thị thâm niên
            if (staff.start_date) {
                calculateSeniority();
            } else {
                $('#seniority_display').val((staff.seniority || 0) + ' năm');
                $('#seniority').val(staff.seniority || 0);
            }
            
            // Thay đổi placeholder cho password
            $('#password').attr('placeholder', 'Để trống nếu không đổi mật khẩu');
            $('#password').removeAttr('required');
            
            // Set correct position value after dropdown is populated
            if (staff.position) {
                // Find option that matches position name
                $('#position_id option').each(function() {
                    const optionText = $(this).text();
                    const optionValue = $(this).val();
                    if (optionText === staff.position) {
                        $(this).prop('selected', true);
                        $('#position_id').val(optionValue);
                        $('#position_id').trigger('change');
                        return false; // break the loop
                    }
                });
            }
            
            // Hiển thị modal sau khi tất cả đã sẵn sàng
            $('#addStaffModal').modal('show');
        });
    }
    
    // Thêm hàm showViewStaffModal
    function showViewStaffModal(staff) {
        // Gọi lại modal chỉnh sửa nhưng chuyển sang chế độ readonly
        showEditStaffModal(staff);
        // Đổi title
        $('#addStaffModalLabel').html('<i class="fas fa-user me-2"></i>Xem thông tin nhân sự');
        // Disable tất cả input, select, textarea, file trong form (KHÔNG disable button)
        $('#addStaffForm input, #addStaffForm select, #addStaffForm textarea, #addStaffForm [type="file"]').prop('disabled', true).prop('readonly', true);
        // Enable nút Đóng và nút Chỉnh sửa
        $('#addStaffForm button[data-bs-dismiss], #btnViewToEdit').prop('disabled', false).prop('readonly', false);
        // Thêm nút Chỉnh sửa nếu chưa có (ở footer)
        if ($('#btnViewToEdit').length === 0) {
            $('<button type="button" class="btn btn-primary ms-2" id="btnViewToEdit"><i class="fas fa-edit me-2"></i>Chỉnh sửa</button>')
                .insertBefore($('#addStaffModal .modal-footer button[data-bs-dismiss]'));
        }
        // Ẩn nút Lưu/Thêm
        $('#addStaffForm button[type="submit"]').hide();
        // Sự kiện chuyển sang chế độ chỉnh sửa
        $('#btnViewToEdit').off('click').on('click', function() {
            // Enable lại các input
            $('#addStaffForm input, #addStaffForm select, #addStaffForm textarea, #addStaffForm [type="file"]').removeAttr('disabled').removeAttr('readonly');
            // Hiện nút Lưu/Thêm
            $('#addStaffForm button[type="submit"]').show();
            // Đổi title
            $('#addStaffModalLabel').html('<i class="fas fa-user-edit me-2"></i>Chỉnh sửa nhân sự');
            // Ẩn nút Chỉnh sửa
            $('#btnViewToEdit').remove();
        });
    }
    


    // ===== SETUP ADD STAFF FORM HANDLERS ===== //
    function setupAddStaffFormHandlers() {
        // Position change handler - auto fill department
        $('#position_id').on('change', function() {
            const selectedOption = $(this).find('option:selected');
            const departmentName = selectedOption.data('department') || '';
            const positionName = selectedOption.text().split(' (')[0] || ''; // Get position name without department
            
            $('#department').val(departmentName);
            $('#position_name').val(positionName);
        });
        

        
        // Avatar preview
        $('#avatar').on('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('.react-logo-container').hide();
                    $('#avatarPreview').attr('src', e.target.result).removeClass('d-none');
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Toggle password visibility
        $('#togglePassword').on('click', function() {
            const passwordField = $('#password');
            const icon = $(this).find('i');
            
            if (passwordField.attr('type') === 'password') {
                passwordField.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                passwordField.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
        
        // Auto calculate seniority when start date changes
        $('#start_date').on('change', function() {
            calculateSeniority();
        });
        
        // Form submission
        $('#addStaffForm').on('submit', function(e) {
            e.preventDefault();
            submitAddStaffForm();
        });
        
        // Keyboard navigation for modal
        $('#addStaffModal').on('keydown', function(e) {
            // ESC to close modal
            if (e.key === 'Escape') {
                $('#addStaffModal').modal('hide');
            }
            
            // Ctrl+Enter to submit form
            if (e.ctrlKey && e.key === 'Enter') {
                e.preventDefault();
                submitAddStaffForm();
            }
        });
        
        // Auto-scroll to invalid fields
        $('#addStaffForm input, #addStaffForm select, #addStaffForm textarea').on('invalid', function() {
            const modalBody = $('#addStaffModal .modal-body');
            const invalidField = $(this);
            const fieldOffset = invalidField.offset().top;
            const modalOffset = modalBody.offset().top;
            const scrollTop = modalBody.scrollTop();
            
            modalBody.animate({
                scrollTop: scrollTop + fieldOffset - modalOffset - 100
            }, 300);
        });
    }
    
    // ===== CALCULATE SENIORITY ===== //
    function calculateSeniority() {
        const startDateValue = $('#start_date').val();
        const errorElement = $('#start_date_error');
        
        // Xóa error message cũ
        errorElement.remove();
        $('#start_date').removeClass('is-invalid');
        
        if (startDateValue) {
            const startDate = new Date(startDateValue);
            const currentDate = new Date();
            
            // Kiểm tra ngày vào làm không được lớn hơn ngày hiện tại
            if (startDate > currentDate) {
                // Hiển thị lỗi
                $('#start_date').addClass('is-invalid');
                $('#start_date').after('<div id="start_date_error" class="text-danger small mt-1">Ngày vào làm không được lớn hơn ngày hiện tại</div>');
                $('#seniority_display').val('');
                $('#seniority').val('0');
                return;
            }
            
            // Tính toán chi tiết năm, tháng, ngày
            let years = currentDate.getFullYear() - startDate.getFullYear();
            let months = currentDate.getMonth() - startDate.getMonth();
            let days = currentDate.getDate() - startDate.getDate();
            
            // Điều chỉnh nếu ngày âm
            if (days < 0) {
                months--;
                const lastDayOfPrevMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 0).getDate();
                days += lastDayOfPrevMonth;
            }
            
            // Điều chỉnh nếu tháng âm
            if (months < 0) {
                years--;
                months += 12;
            }
            
            // Tạo text hiển thị
            let seniorityText = '';
            if (years > 0) {
                seniorityText += years + ' năm';
            }
            if (months > 0) {
                if (seniorityText) seniorityText += ' ';
                seniorityText += months + ' tháng';
            }
            if (days > 0) {
                if (seniorityText) seniorityText += ' ';
                seniorityText += days + ' ngày';
            }
            if (!seniorityText) {
                seniorityText = '0 ngày';
            }
            
            $('#seniority_display').val(seniorityText);
            
            // Lưu tổng số tháng để sort/filter
            const totalMonths = years * 12 + months;
            $('#seniority').val(totalMonths);
        } else {
            $('#seniority_display').val('');
            $('#seniority').val('0');
        }
    }
    
    // ===== RESET ADD STAFF FORM ===== //
    function resetAddStaffForm() {
        // Reset form EXCEPT position select options
        const form = $('#addStaffForm')[0];
        const elements = form.elements;
        for (let i = 0; i < elements.length; i++) {
            const element = elements[i];
            if (element.id !== 'position_id' && element.type !== 'button' && element.type !== 'submit') {
                if (element.type === 'checkbox' || element.type === 'radio') {
                    element.checked = false;
                } else if (element.tagName === 'SELECT' && element.id !== 'position_id') {
                    element.selectedIndex = 0;
                } else {
                    element.value = '';
                }
            }
        }
        // Only reset value, not options
        $('#position_id').val('').trigger('change');
        $('.react-logo-container').show();
        $('#avatarPreview').addClass('d-none').attr('src', '');
        $('#seniority').val(0);
        $('#seniority_display').val('');
        $('#password').attr('type', 'password');
        $('#togglePassword i').removeClass('fa-eye-slash').addClass('fa-eye');
        $('#department').val('');
        $('#position_name').val('');
        $('#start_date_error').remove();
        $('#start_date').removeClass('is-invalid');
        $('.is-invalid').removeClass('is-invalid');
        $('#addStaffModalLabel').html('<i class="fas fa-user-plus me-2"></i>Thêm nhân sự mới');
        $('#addStaffForm button[type="submit"]').html('<i class="fas fa-plus me-2"></i>Thêm nhân sự');
        $('#password').attr('placeholder', '').attr('required', true);
        $('#staff_id').remove();
    }
    
    // ===== SUBMIT ADD STAFF FORM ===== //
    function submitAddStaffForm() {
        // Kiểm tra xem đây là thêm mới hay cập nhật
        const isEdit = $('#staff_id').length > 0 && $('#staff_id').val();
        
        // Validate required fields
        let requiredFields = ['staff_code', 'fullname', 'username'];
        
        // Với chế độ edit, password không bắt buộc
        if (!isEdit) {
            requiredFields.push('password');
        }
        
        let isValid = true;
        
        requiredFields.forEach(field => {
            const value = $(`#${field}`).val().trim();
            if (!value) {
                $(`#${field}`).addClass('is-invalid');
                isValid = false;
            } else {
                $(`#${field}`).removeClass('is-invalid');
            }
        });
        
        // Kiểm tra lỗi ngày vào làm
        if ($('#start_date_error').length > 0) {
            isValid = false;
        }
        
        if (!isValid) {
            if ($('#start_date_error').length > 0) {
                showNotification('Ngày vào làm không được lớn hơn ngày hiện tại', 'error');
            } else {
                showNotification('Vui lòng điền đầy đủ các trường bắt buộc', 'error');
            }
            
            // Scroll to first invalid field
            const firstInvalidField = $('#addStaffForm .is-invalid').first();
            if (firstInvalidField.length) {
                const modalBody = $('#addStaffModal .modal-body');
                const fieldOffset = firstInvalidField.offset().top;
                const modalOffset = modalBody.offset().top;
                const scrollTop = modalBody.scrollTop();
                
                modalBody.animate({
                    scrollTop: scrollTop + fieldOffset - modalOffset - 100
                }, 300);
                
                // Focus on the invalid field
                firstInvalidField.focus();
            }
            
            return;
        }
        
        // Show loading
        const submitBtn = $('#addStaffForm button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Đang xử lý...').prop('disabled', true);
        
        // Prepare form data
        const formData = new FormData($('#addStaffForm')[0]);
        
        // Chọn URL dựa trên chế độ
        const url = isEdit ? 'update_staff.php' : 'add_staff.php';
        const actionText = isEdit ? 'cập nhật' : 'thêm';
        
        // Submit form
        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.message, 'success');
                    $('#addStaffModal').modal('hide');
                    loadStaffData(); // Reload staff list
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = `Có lỗi xảy ra khi ${actionText} nhân sự`;
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (status === 'timeout') {
                    errorMessage = 'Kết nối quá chậm. Vui lòng thử lại!';
                }
                
                showNotification(errorMessage, 'error');
                
            },
            complete: function() {
                // Restore button
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    }
    
    function showAvatarModal(avatar, fullname) {
        // Create modal for viewing avatar
        const modal = $(`
            <div class="modal fade" id="avatarModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Ảnh đại diện - ${fullname}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="${avatar}" alt="${fullname}" class="img-fluid rounded" 
                                 onerror="this.src='assets/images/default-avatar.svg'">
                        </div>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        modal.modal('show');
        
        // Remove modal when hidden
        modal.on('hidden.bs.modal', function() {
            modal.remove();
        });
    }
    
    function exportStaffData() {
        if (selectedStaffs.length === 0) {
            showWarning('Vui lòng chọn ít nhất một nhân sự để xuất dữ liệu!');
            return;
        }
        
        showInfo(`Chức năng xuất Excel cho ${selectedStaffs.length} nhân sự sẽ được phát triển trong phiên bản tiếp theo!`);
    }
    


    // ===== GLOBAL FUNCTIONS ===== //
    window.viewStaff = function(staffId) {
        const staff = staffData.find(s => s.id === staffId);
        if (staff) {
            // Gọi API lấy chi tiết nhân sự mới nhất
            $.ajax({
                url: `api/get_staff_detail.php?id=${staffId}`,
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        showViewStaffModal(response.data);
                    } else {
                        showNotification(response.message || 'Không lấy được dữ liệu nhân sự', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    showNotification('Lỗi khi lấy dữ liệu nhân sự: ' + (xhr.responseJSON?.message || error), 'error');
                }
            });
        }
    };
    
    window.editStaff = function(staffId) {
        // Gọi API lấy chi tiết nhân sự
        $.ajax({
            url: `api/get_staff_detail.php?id=${staffId}`,
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    showEditStaffModal(response.data);
                } else {
                    showNotification(response.message || 'Không lấy được dữ liệu nhân sự', 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Lỗi khi lấy dữ liệu nhân sự: ' + (xhr.responseJSON?.message || error), 'error');
            }
        });
    };
    
    window.deleteStaff = function(staffId) {
        const staff = staffData.find(s => s.id === staffId);
        if (staff) {
            showDeleteConfirmation(staff);
        }
    };
    
    // ===== HEADER EVENT LISTENERS ===== //
    function setupHeaderEventListeners() {
        // User dropdown actions
        $('[data-action="logout"]').on('click', function(e) {
            e.preventDefault();
            handleLogout();
        });
        
        $('[data-action="profile"]').on('click', function(e) {
            e.preventDefault();
            showNotification('Chức năng thông tin cá nhân đang được phát triển', 'info');
        });
        
        $('[data-action="settings"]').on('click', function(e) {
            e.preventDefault();
            showNotification('Chức năng cài đặt đang được phát triển', 'info');
        });
        
        $('[data-action="notifications"]').on('click', function(e) {
            e.preventDefault();
            showNotification('Chức năng thông báo đang được phát triển', 'info');
        });
        
        // Work dropdown actions
        $('[data-section="internal-case"]').on('click', function(e) {
            e.preventDefault();
            showNotification('Chức năng Case nội bộ đang được phát triển', 'info');
        });
        
        $('[data-section="deployment-case"]').on('click', function(e) {
            e.preventDefault();
            showNotification('Chức năng Case triển khai đang được phát triển', 'info');
        });
        
        $('[data-section="maintenance-case"]').on('click', function(e) {
            e.preventDefault();
            showNotification('Chức năng Case bảo trì đang được phát triển', 'info');
        });
        
        // Navigation links
        $('#homeLink').on('click', function(e) {
            // e.preventDefault(); // Bỏ preventDefault để trình duyệt chuyển trang bình thường
            // showLoadingMessage('Đang chuyển về trang chủ...'); // Bỏ loading
            // setTimeout(function() {
            //     window.location.href = 'dashboard.php';
            // }, 500);
            // Chỉ để mặc định, không can thiệp
        });
        
        // Search form
        $('.search-form').on('submit', function(e) {
            e.preventDefault();
            const searchValue = $('#staffSearchInput').val().trim();
            if (searchValue) {
                currentFilters.search = searchValue;
                currentPage = 1;
                loadStaffData();
            }
        });
        
        // Global search input
        $('#staffSearchInput').on('input', debounce(function() {
            const searchValue = $(this).val().trim();
            if (searchValue) {
                currentFilters.search = searchValue;
                currentPage = 1;
                loadStaffData();
            } else if (searchValue === '') {
                currentFilters.search = '';
                currentPage = 1;
                loadStaffData();
            }
        }, 500));
        
        // Keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl+K for search
            if (e.ctrlKey && e.key === 'k') {
                e.preventDefault();
                $('#staffSearchInput').focus();
            }
        });
    }
    
    // ===== HANDLE LOGOUT ===== //
    function handleLogout() {
        showLoadingMessage('Đang đăng xuất...');
        
        // Redirect to logout
        window.location.href = 'auth/logout.php';
    }
    
    // ===== SHOW NOTIFICATION ===== //
    function showNotification(message, type = 'info') {
        if (type === 'info') {
            showInfo(message);
        } else if (type === 'success') {
            showSuccess(message);
        } else if (type === 'warning') {
            showWarning(message);
        } else if (type === 'error') {
            showError(message);
        } else {
            showAlert(message, type);
        }
    }
    
    // ===== SHOW LOADING MESSAGE ===== //
    function showLoadingMessage(message) {
        // Remove existing loading messages
        $('.loading-overlay').remove();
        
        const loading = $(`
            <div class="loading-overlay position-fixed top-50 start-50 translate-middle" style="z-index: 9999;">
                <div class="card shadow">
                    <div class="card-body text-center">
                        <div class="spinner-border text-primary mb-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mb-0">${message}</p>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(loading);
    }
    
    // ===== XÓA NHÂN SỰ ===== //
    function showDeleteConfirmation(staff) {
        // Remove existing confirmation modals
        $('.delete-confirmation-modal').remove();
        
        const modal = $(`
            <div class="modal fade delete-confirmation-modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Xác nhận xóa nhân sự
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <i class="fas fa-user-times fa-3x text-danger mb-3"></i>
                                <h5 class="text-danger">Bạn có chắc chắn muốn xóa nhân sự này?</h5>
                                <p class="text-muted">Hành động này không thể hoàn tác!</p>
                            </div>
                            
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-info-circle me-2"></i>Thông tin nhân sự sẽ bị xóa:</h6>
                                <ul class="mb-0 mt-2">
                                    <li><strong>Họ và tên:</strong> ${staff.fullname}</li>
                                    <li><strong>Mã số:</strong> ${staff.employee_code || 'N/A'}</li>
                                    <li><strong>Username:</strong> ${staff.username || 'N/A'}</li>
                                    <li><strong>Ảnh đại diện:</strong> ${staff.avatar ? 'Có' : 'Không có'}</li>
                                </ul>
                            </div>
                            
                            <div class="alert alert-info">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    Hệ thống sẽ xóa: Dữ liệu nhân sự, tài khoản đăng nhập (nếu có), và ảnh đại diện.
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Hủy
                            </button>
                            <button type="button" class="btn btn-danger" onclick="confirmDeleteStaff(${staff.id})">
                                <i class="fas fa-trash me-2"></i>Xóa nhân sự
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        modal.modal('show');
        
        // Remove modal when hidden
        modal.on('hidden.bs.modal', function() {
            modal.remove();
        });
    }
    
    // Global function for confirming delete
    window.confirmDeleteStaff = function(staffId) {
        const staff = staffData.find(s => s.id === staffId);
        if (!staff) return;
        
        // Close confirmation modal
        $('.delete-confirmation-modal').modal('hide');
        
        // Show loading
        showLoadingMessage('Đang xóa nhân sự...');
        
        // Send delete request
        $.ajax({
            url: 'delete_staff.php',
            method: 'POST',
            data: { id: staffId },
            dataType: 'json',
            timeout: 10000,
            success: function(response) {
                // Remove loading
                $('.loading-overlay').remove();
                
                if (response.success) {
                    // Show success message
                    showSuccessMessage(response.message);
                    
                    // Reload staff data
                    setTimeout(function() {
                        loadStaffData();
                    }, 1000);
                } else {
                    // Show error message
                    showErrorMessage(response.message || 'Có lỗi xảy ra khi xóa nhân sự');
                }
            },
            error: function(xhr, status, error) {
                // Remove loading
                $('.loading-overlay').remove();
                
                let errorMessage = 'Có lỗi xảy ra khi xóa nhân sự';
                
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
    };
    
    // ===== UTILITY FUNCTIONS ===== //
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
    
    // ===== KHỞI TẠO ỨNG DỤNG ===== //
    init();
    
}); 