<?php
/**
 * IT CRM - Workspace Page
 * File: workspace.php
 * Mục đích: Trang workspace với bảo vệ authentication
 */

// Include các file cần thiết
require_once 'includes/session.php';

// Bảo vệ trang - yêu cầu đăng nhập và role IT
requireLogin();

// Lấy thông tin user hiện tại
$current_user = getCurrentUser();

// Kiểm tra nếu không có thông tin user
if (!$current_user) {
    redirectToLogin('Phiên đăng nhập không hợp lệ.');
}

// Kiểm tra quyền truy cập - chỉ IT staff và admin mới được vào workspace
if (!canAccessWorkspace()) {
    redirectToLogin('Bạn không có quyền truy cập trang này.');
}

$user_fullname = $current_user['fullname'];
$user_id = $current_user['id'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <title>Workspace - IT Services Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo filemtime('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="assets/css/alert.css?v=<?php echo filemtime('assets/css/alert.css'); ?>">
    <link rel="stylesheet" href="assets/css/workspace.css?v=<?php echo filemtime('assets/css/workspace.css'); ?>">
    <style>
    /* CSS để đảm bảo modal workspace có z-index cao hơn */
    #modalContainer .modal {
        z-index: 9999 !important;
    }
    
    /* Ẩn modal đổi mật khẩu khi có modal workspace */
    #modalContainer .modal.show ~ .modal {
        display: none !important;
    }
    
    /* Ẩn modal đổi mật khẩu khi có modal workspace */
    #modalContainer .modal.show ~ #changePasswordModal {
        display: none !important;
        opacity: 0 !important;
        visibility: hidden !important;
    }
    
    /* Ẩn modal đổi mật khẩu khi có modal workspace (cách khác) */
    #modalContainer .modal.show + #changePasswordModal,
    #modalContainer .modal.show ~ #changePasswordModal {
        display: none !important;
        opacity: 0 !important;
        visibility: hidden !important;
        pointer-events: none !important;
    }
    
    /* Đảm bảo modal workspace hiển thị trên cùng */
    #modalContainer .modal.show {
        z-index: 9999 !important;
    }
    
    /* Ẩn tất cả modal khác khi có modal workspace */
    #modalContainer .modal.show ~ * {
        z-index: auto !important;
    }
    
    /* CSS mạnh hơn để ẩn modal đổi mật khẩu */
    #changePasswordModal[aria-hidden="true"],
    #changePasswordModal[style*="display: none"],
    #changePasswordModal[style*="z-index: 0"] {
        display: none !important;
        opacity: 0 !important;
        visibility: hidden !important;
        pointer-events: none !important;
        z-index: 0 !important;
    }
    
    /* Đảm bảo modal workspace luôn hiển thị trên cùng */
    #modalContainer .modal.show,
    #modalContainer .modal[style*="z-index: 9999"] {
        z-index: 9999 !important;
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
    }
    

    
    /* Đảm bảo modal workspace hiển thị */
    #modalContainer .modal {
        z-index: 9999 !important;
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
    }
    
    /* Size cho tất cả modal trong workspace */
    #modalContainer .modal-dialog {
        width: calc(100vw - 40px);
        max-width: none;
        margin: 20px auto;
    }
    
    /* Đảm bảo backdrop hiển thị */
    #modalContainer .modal-backdrop {
        z-index: 9998 !important;
    }
    
    /* CSS cho deployment-request-modal - y chang như gốc */
    .deployment-request-modal {
        border-radius: 0;
        height: 100%;
        display: flex;
        flex-direction: column;
        width: 100%;
    }
    
    .deployment-request-modal .modal-header {
        background: #5bc0de;
        color: black;
        border-bottom: 2px solid #dee2e6;
        padding: 0.75rem 1.5rem;
    }
    
    .deployment-request-modal .modal-title {
        font-weight: 600;
        font-size: 1.1rem;
    }
    
    .deployment-request-modal .modal-body {
        flex: 1;
        padding: 1.5rem;
        background-color: #f8f9fa;
        max-height: 70vh;
        overflow-y: auto;
    }
    
    .deployment-request-modal .modal-footer {
        background-color: #f8f9fa;
        border-top: 2px solid #dee2e6;
        padding: 0.75rem 1.5rem;
    }
    
    /* Form styles */
    .deployment-request-modal .form-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.25rem;
        font-size: 1rem;
    }
    
    .deployment-request-modal .form-control,
    .deployment-request-modal .form-select {
        border: 2px solid #e9ecef;
        padding: 0.6rem 0.8rem;
        font-size: 1rem;
        height: 48px;
        transition: all 0.3s ease;
    }
    
    .deployment-request-modal .form-control:focus,
    .deployment-request-modal .form-select:focus {
        border-color: #07f;
        box-shadow: 0 0.2rem rgba(0,123,255,0.25);
    }
    
    .deployment-request-modal .form-control[readonly] {
        background-color: #f8f9fa;
        color: #6c757d;
    }
    
    .deployment-request-modal .form-control:disabled {
        background-color: #e9ecef;
        color: #6c757d;
        cursor: not-allowed;
        opacity: 0.6;
    }
    
    .deployment-request-modal textarea.form-control {
        resize: vertical;
        min-height: 100px;
        height: 100px;
    }
    
    .deployment-request-modal .btn {
        padding: 0.5rem 1rem;
        font-weight: 600;
        border-radius: 0.375rem;
        transition: all 0.3s ease;
    }
    
    .deployment-request-modal .btn-warning {
        background-color: #ffc107;
        border-color: #ffc107;
        color: #000;
    }
    
    .deployment-request-modal .btn-warning:hover {
        background-color: #ffca2c;
        border-color: #ffca2c;
        color: #000;
    }
    
    .deployment-request-modal .btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
        color: #fff;
    }
    
    .deployment-request-modal .mb-3 {
        margin-bottom: 1rem !important;
    }
    
    .deployment-request-modal .row.g-4 {
        margin: 0 -0.5rem;
    }
    
    .deployment-request-modal .row.g-4 > .col-md-6 {
        padding: 0 0.5rem;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        #modalContainer .modal-dialog {
            width: calc(100vw - 40px);
            margin: 20px auto;
            max-width: none;
        }
        
        .deployment-request-modal {
            height: 100%;
        }
        
        .deployment-request-modal .modal-body {
            padding: 0.75em;
        }
        
        .deployment-request-modal .row {
            margin: 0;
        }
        
        .deployment-request-modal .col-md-6 {
            padding: 0;
            margin-bottom: 0.5em;
        }
    }
    
    /* Scrollbar styles */
    .deployment-request-modal .modal-body::-webkit-scrollbar {
        width: 8px;
    }
    
    .deployment-request-modal .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    .deployment-request-modal .modal-body::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }
    
    .deployment-request-modal .modal-body::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    </style>
</head>
<body>
<?php require_once 'includes/header.php'; ?>
<main class="main-content">
  <div class="container-fluid px-4 py-4">
    <div class="page-header mb-4">
      <div class="row align-items-center">
        <div class="col">
          <h1 class="page-title mb-0">Workspace</h1>
          <p class="text-muted mb-0">Các task bạn cần làm hoặc đang làm</p>
        </div>
      </div>
    </div>
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <div class="d-flex flex-wrap align-items-center mb-3 gap-2">
              <button class="btn btn-outline-info" id="btn-filter-processing">Đang xử lý</button>
              <button class="btn btn-outline-success" id="btn-filter-done-this-month">Hoàn thành tháng này</button>
              <button class="btn btn-outline-success" id="btn-filter-done-last-month">Hoàn thành tháng trước</button>
              
            </div>
            <div class="table-responsive">
              <table id="workspace-table" class="table table-hover align-middle mb-0">
                                 <thead class="table-light">
                   <tr>
                     <th>STT</th>
                     <th>MÃ ITSM</th>
                     <th>LEVEL</th>
                     <th>LOẠI CASE</th>
                     <th>LOẠI DV</th>
                     <th>KHÁCH HÀNG</th>
                     <th>BẮT ĐẦU</th>
                     <th>KẾT THÚC</th>
                     <th>TRẠNG THÁI</th>
                   </tr>
                 </thead>
                                 <tbody id="workspace-tasks-table">
                   <tr><td colspan="9" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...</td></tr>
                 </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Modal Container cho các modal từ deployment_requests -->
<div id="modalContainer"></div>

<!-- jQuery (load trước) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Alert System -->
<script src="assets/js/alert.js?v=<?php echo filemtime('assets/js/alert.js'); ?>"></script>
<!-- Custom JavaScript -->
<script src="assets/js/dashboard.js?v=<?php echo filemtime('assets/js/dashboard.js'); ?>"></script>
<script>
function formatDateForDisplay(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    return d.toLocaleDateString('vi-VN') + ' ' + d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
}

// Hàm load modal từ deployment_requests.php
function loadModalFromDeploymentRequests(modalType, id) {
    // Ngăn chặn event bubbling
    event.preventDefault();
    event.stopPropagation();
    
    console.log('Loading modal:', modalType, 'for ID:', id);
    
    const modalContainer = document.getElementById('modalContainer');
    
    // Hiển thị loading với z-index thấp hơn
    modalContainer.innerHTML = `
        <div class="modal fade" id="loadingModal" tabindex="-1" style="z-index: 9998;">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Đang tải...</span>
                        </div>
                        <p class="mt-2">Đang tải modal...</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
    loadingModal.show();
    
    // Xóa tất cả modal cũ trước khi load modal mới
    modalContainer.innerHTML = '';
    
    // Ẩn tất cả modal khác có thể đang hiển thị
    const existingModals = document.querySelectorAll('.modal');
    existingModals.forEach(modal => {
        const modalInstance = bootstrap.Modal.getInstance(modal);
        if (modalInstance) {
            modalInstance.hide();
        }
        // Thêm class để ẩn hoàn toàn
        modal.style.display = 'none';
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
    });
    
    // Xóa backdrop nếu có
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    
    // Xóa class modal-open từ body
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    // Ẩn modal đổi mật khẩu nếu có - cách mạnh hơn
    const passwordModal = document.getElementById('changePasswordModal');
    if (passwordModal) {
        const passwordModalInstance = bootstrap.Modal.getInstance(passwordModal);
        if (passwordModalInstance) {
            passwordModalInstance.hide();
        }
        passwordModal.style.display = 'none';
        passwordModal.classList.remove('show');
        passwordModal.setAttribute('aria-hidden', 'true');
        passwordModal.style.zIndex = '0';
        passwordModal.style.opacity = '0';
        passwordModal.style.visibility = 'hidden';
        passwordModal.style.pointerEvents = 'none';
    }
    
    // Đợi một chút để đảm bảo modal cũ đã được ẩn
    setTimeout(() => {
        // Fetch modal content từ deployment_requests.php
        fetch(`deployment_requests.php?ajax=1&modal=${modalType}&id=${id}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            return response.text();
        })
        .then(html => {
            console.log('Modal HTML received (full):', html);
            console.log('Modal HTML length:', html.length);
            console.log('Modal HTML first 200 chars:', html.substring(0, 200));
            
            // Ẩn loadingModal trước
            loadingModal.hide();
            
            // Xóa backdrop của loadingModal
            const loadingBackdrops = document.querySelectorAll('.modal-backdrop');
            loadingBackdrops.forEach(backdrop => backdrop.remove());
            
            // Xóa loadingModal khỏi DOM
            const loadingModalElement = document.getElementById('loadingModal');
            if (loadingModalElement) {
                loadingModalElement.remove();
            }
            
            // Xóa class modal-open từ body
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            
                        // Thêm modal mới vào container
            modalContainer.innerHTML = html;
            
            // Debug: Kiểm tra modal trong container
            console.log('Modal container children:', modalContainer.children.length);
            console.log('Modal container HTML:', modalContainer.innerHTML.substring(0, 500));
            
            // Đợi một chút để đảm bảo loadingModal đã được ẩn hoàn toàn
            setTimeout(() => {
                // Debug: Kiểm tra tất cả modal trong DOM
                const allModals = document.querySelectorAll('.modal');
                console.log('All modals in DOM:', allModals.length);
                allModals.forEach((modal, index) => {
                    console.log(`Modal ${index}:`, modal.id, modal.className, modal.style.display);
                });
                
                // Thêm CSS để đảm bảo modal mới có z-index cao hơn
                const newModal = document.querySelector('#modalContainer .modal');
                console.log('Found modal in container:', newModal);
                console.log('Modal ID:', newModal ? newModal.id : 'none');
                console.log('Modal classes:', newModal ? newModal.className : 'none');
                
                if (newModal) {
                    console.log('Modal found, showing...');
                    
                    // Đặt z-index cao hơn để đảm bảo hiển thị trên cùng
                    newModal.style.zIndex = '9999';
                    newModal.style.display = 'block';
                    newModal.style.opacity = '1';
                    newModal.style.visibility = 'visible';
                    
                    // Ẩn modal đổi mật khẩu trước khi show modal mới
                    const passwordModal = document.getElementById('changePasswordModal');
                    if (passwordModal) {
                        passwordModal.style.display = 'none';
                        passwordModal.classList.remove('show');
                        passwordModal.setAttribute('aria-hidden', 'true');
                        passwordModal.style.zIndex = '0';
                        passwordModal.style.opacity = '0';
                        passwordModal.style.visibility = 'hidden';
                        passwordModal.style.pointerEvents = 'none';
                    }
                    
                    const modal = new bootstrap.Modal(newModal);
                    console.log('Bootstrap modal instance:', modal);
                    
                    // Thêm event listener để debug
                    newModal.addEventListener('shown.bs.modal', function() {
                        console.log('Modal shown successfully');
                    });
                    
                    newModal.addEventListener('show.bs.modal', function() {
                        console.log('Modal showing...');
                    });
                    
                    modal.show();
                    console.log('Modal.show() called');
                    
                    // Kiểm tra backdrop
                    setTimeout(() => {
                        const backdrops = document.querySelectorAll('.modal-backdrop');
                        console.log('Backdrops found:', backdrops.length);
                        backdrops.forEach((backdrop, index) => {
                            console.log(`Backdrop ${index}:`, backdrop.style.zIndex, backdrop.style.display);
                        });
                        
                        // Kiểm tra modal sau khi show
                        console.log('Modal after show:', newModal.style.display, newModal.classList.contains('show'));
                    }, 100);
                    
                    // Load dữ liệu vào modal
                    loadModalData(modalType, id);
                    
                    // Load danh sách người thực hiện cho deployment task
                    if (modalType === 'editDeploymentTask') {
                        loadStaffList();
                    }
                    
                    // Thêm event listener cho form submit
                    const form = document.getElementById('editDeploymentTaskForm');
                    if (form) {
                        form.addEventListener('submit', handleDeploymentTaskSubmit);
                    }
                } else {
                    console.error('No modal found in response');
                    showAlert('Không tìm thấy modal trong response', 'error');
                }
            }, 200); // Tăng timeout lên 200ms
        })
        .catch(error => {
            console.error('Error loading modal:', error);
            loadingModal.hide();
            showAlert('Lỗi khi tải modal', 'error');
        });
    }, 500); // Tăng timeout lên 500ms
}

// Hàm load dữ liệu vào modal
function loadModalData(modalType, id) {
    console.log('Loading data for modal:', modalType, 'ID:', id);
    
    if (modalType === 'editDeploymentCase') {
        // Load case data
        fetch(`api/get_deployment_case.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                console.log('Deployment case data:', data);
                if (data.success) {
                    populateEditCaseModal(data.data);
                } else {
                    console.error('Error loading case data:', data.message);
                }
            })
            .catch(error => {
                console.error('Error fetching case data:', error);
            });
    } else if (modalType === 'editDeploymentTask') {
        // Load task data
        fetch(`api/get_deployment_task.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                console.log('Deployment task data:', data);
                if (data.success) {
                    populateEditTaskModal(data.data);
                } else {
                    console.error('Error loading task data:', data.message);
                }
            })
            .catch(error => {
                console.error('Error fetching task data:', error);
            });
    } else if (modalType === 'editMaintenanceCase') {
        // Load maintenance case data
        fetch(`api/get_maintenance_case_details.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                console.log('Maintenance case data:', data);
                if (data.success) {
                    populateEditMaintenanceCaseModal(data.data);
                } else {
                    console.error('Error loading maintenance case data:', data.message);
                }
            })
            .catch(error => {
                console.error('Error fetching maintenance case data:', error);
            });
    } else if (modalType === 'editMaintenanceTask') {
        // Load maintenance task data
        fetch(`api/get_maintenance_task_details.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                console.log('Maintenance task data:', data);
                if (data.success) {
                    populateEditMaintenanceTaskModal(data.data);
                } else {
                    console.error('Error loading maintenance task data:', data.message);
                }
            })
            .catch(error => {
                console.error('Error fetching maintenance task data:', error);
            });
    } else if (modalType === 'editInternalCase') {
        // Load internal case data
        fetch(`api/get_internal_case_details.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                console.log('Internal case data:', data);
                if (data.success) {
                    populateEditInternalCaseModal(data.data);
                } else {
                    console.error('Error loading internal case data:', data.message);
                }
            })
            .catch(error => {
                console.error('Error fetching internal case data:', error);
            });
    }
}

// Hàm populate dữ liệu vào modal edit case
function populateEditCaseModal(caseData) {
    document.getElementById('edit_case_id').value = caseData.id;
    document.getElementById('edit_case_code').value = caseData.case_code || '';
    document.getElementById('edit_case_description').value = caseData.case_description || '';
    document.getElementById('edit_case_notes').value = caseData.notes || '';
    document.getElementById('edit_case_start_date').value = caseData.start_date || '';
    document.getElementById('edit_case_end_date').value = caseData.end_date || '';
    document.getElementById('edit_case_status').value = caseData.status || 'Tiếp nhận';
}

// Hàm populate dữ liệu vào modal edit task
function populateEditTaskModal(taskData) {
    console.log('Populating task data:', taskData);
    
    document.getElementById('edit_task_id').value = taskData.id;
    document.getElementById('edit_task_number').value = taskData.task_number || '';
    document.getElementById('edit_task_type').value = taskData.task_type || '';
    document.getElementById('edit_task_template').value = taskData.template_name || '';
    document.getElementById('edit_task_name').value = taskData.task_description || '';
    document.getElementById('edit_task_note').value = taskData.notes || '';
    document.getElementById('edit_task_assignee_id').value = taskData.assignee_id || '';
    
    // Format datetime for datetime-local input
    if (taskData.start_date) {
        const startDate = new Date(taskData.start_date);
        const startDateStr = startDate.toISOString().slice(0, 16);
        document.getElementById('edit_task_start_date').value = startDateStr;
    }
    
    if (taskData.end_date) {
        const endDate = new Date(taskData.end_date);
        const endDateStr = endDate.toISOString().slice(0, 16);
        document.getElementById('edit_task_end_date').value = endDateStr;
    }
    
    document.getElementById('edit_task_status').value = taskData.status || 'Tiếp nhận';
}

// Hàm populate dữ liệu vào modal edit maintenance case
function populateEditMaintenanceCaseModal(caseData) {
    document.getElementById('edit_maintenance_case_id').value = caseData.id;
    document.getElementById('edit_maintenance_case_code').value = caseData.case_code || '';
    document.getElementById('edit_maintenance_case_description').value = caseData.case_description || '';
    document.getElementById('edit_maintenance_case_notes').value = caseData.notes || '';
    document.getElementById('edit_maintenance_case_start_date').value = caseData.start_date || '';
    document.getElementById('edit_maintenance_case_end_date').value = caseData.end_date || '';
    document.getElementById('edit_maintenance_case_status').value = caseData.status || 'Tiếp nhận';
}

// Hàm populate dữ liệu vào modal edit maintenance task
function populateEditMaintenanceTaskModal(taskData) {
    document.getElementById('edit_maintenance_task_id').value = taskData.id;
    document.getElementById('edit_maintenance_task_description').value = taskData.task_description || '';
    document.getElementById('edit_maintenance_task_notes').value = taskData.notes || '';
    document.getElementById('edit_maintenance_task_start_date').value = taskData.start_date || '';
    document.getElementById('edit_maintenance_task_end_date').value = taskData.end_date || '';
    document.getElementById('edit_maintenance_task_status').value = taskData.status || 'Tiếp nhận';
}

// Hàm populate dữ liệu vào modal edit internal case
function populateEditInternalCaseModal(caseData) {
    document.getElementById('edit_internal_case_id').value = caseData.id;
    document.getElementById('edit_internal_case_code').value = caseData.case_code || '';
    document.getElementById('edit_internal_case_description').value = caseData.case_description || '';
    document.getElementById('edit_internal_case_notes').value = caseData.notes || '';
    document.getElementById('edit_internal_case_start_date').value = caseData.start_date || '';
    document.getElementById('edit_internal_case_end_date').value = caseData.end_date || '';
    document.getElementById('edit_internal_case_status').value = caseData.status || 'Tiếp nhận';
}

// Hàm load danh sách staff
function loadStaffList() {
    fetch('api/get_staff_list.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('edit_task_assignee_id');
                select.innerHTML = '<option value="">-- Chọn người thực hiện --</option>';
                
                data.data.forEach(staff => {
                    const option = document.createElement('option');
                    option.value = staff.id;
                    option.textContent = staff.fullname;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading staff list:', error);
        });
}

// Hàm xử lý submit form deployment task
function handleDeploymentTaskSubmit(e) {
    e.preventDefault();
    console.log('Submitting deployment task form...');
    
    const formData = new FormData(e.target);
    
    fetch('api/update_deployment_task.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Update response:', data);
        if (data.success) {
            showAlert('Cập nhật task thành công!', 'success');
            // Đóng modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editDeploymentTaskModal'));
            modal.hide();
            // Reload workspace tasks
            if (typeof loadWorkspaceTasks === 'function') {
                loadWorkspaceTasks();
            }
        } else {
            showAlert(data.message || 'Có lỗi xảy ra!', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating task:', error);
        showAlert('Có lỗi xảy ra khi cập nhật!', 'error');
    });
}

function loadWorkspaceTasks(statusFilter = 'processing') {
    const tbody = document.getElementById('workspace-tasks-table');
    tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...</td></tr>';
    fetch('api/get_workspace_tasks.php?status=' + encodeURIComponent(statusFilter))
        .then(res => res.json())
        .then(data => {
                         if (!data.success || !Array.isArray(data.data) || data.data.length === 0) {
                 tbody.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">Không có task nào</td></tr>';
                 return;
             }
            tbody.innerHTML = '';
                         data.data.forEach((item, idx) => {
                 // Debug: Log level của từng item
                 console.log(`Item ${idx}: ID=${item.id}, Level=${item.level}, Code=${item.case_code}`);
                
                                 // Tạo row chính với click handler
                 const mainRow = `
                     <tr class="workspace-row" data-item-id="${item.id}" style="cursor: pointer;">
                         <td style="padding-left: 25px;">${idx + 1}</td>
                         <td>${item.case_code || ''}</td>
                         <td><span class="badge ${item.level === 'Case' ? 'bg-primary' : (item.level === 'Task' ? 'bg-success' : (item.level === 'Case Bảo trì' ? 'bg-warning' : (item.level === 'Task Bảo trì' ? 'bg-warning' : 'bg-info')))}">${item.level || ''}</span></td>
                         <td>${item.case_type || ''}</td>
                         <td>${item.service_type || ''}</td>
                         <td>${item.customer_name || ''}</td>
                         <td>${item.start_date ? formatDateForDisplay(item.start_date) : ''}</td>
                         <td>${item.end_date ? formatDateForDisplay(item.end_date) : ''}</td>
                         <td><span class="badge ${item.status === 'Tiếp nhận' ? 'status-received' : (item.status === 'Đang xử lý' ? 'status-processing' : (item.status === 'Hoàn thành' ? 'status-completed' : (item.status === 'Huỷ' ? 'status-cancelled' : 'bg-secondary')))}">${item.status}</span></td>
                     </tr>
                 `;
                
                                 // Tạo row chi tiết (ẩn ban đầu)
                 const detailRow = `
                     <tr class="workspace-detail-row" data-item-id="${item.id}" style="display: none;">
                         <td colspan="9">
                            <div class="detail-content p-3 bg-light border-start border-4 border-primary">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3"><i class="fas fa-info-circle me-2"></i>Thông tin chi tiết</h6>
                                        <table class="table table-sm table-borderless">
                                            <tr>
                                                <td class="fw-bold" style="width: 120px;">Mã ITSM:</td>
                                                <td>${item.case_code || 'N/A'}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Loại:</td>
                                                <td>${item.level || 'N/A'}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Loại Case:</td>
                                                <td>${item.case_type || 'N/A'}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Loại DV:</td>
                                                <td>${item.service_type || 'N/A'}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Khách hàng:</td>
                                                <td>${item.customer_name || 'N/A'}</td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-primary mb-3"><i class="fas fa-calendar-alt me-2"></i>Thời gian</h6>
                                        <table class="table table-sm table-borderless">
                                            <tr>
                                                <td class="fw-bold" style="width: 120px;">Bắt đầu:</td>
                                                <td>${item.start_date ? formatDateForDisplay(item.start_date) : 'N/A'}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Kết thúc:</td>
                                                <td>${item.end_date ? formatDateForDisplay(item.end_date) : 'N/A'}</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-bold">Trạng thái:</td>
                                                <td><span class="badge ${item.status === 'Tiếp nhận' ? 'status-received' : (item.status === 'Đang xử lý' ? 'status-processing' : (item.status === 'Hoàn thành' ? 'status-completed' : (item.status === 'Huỷ' ? 'status-cancelled' : 'bg-secondary')))}">${item.status}</span></td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button onclick="loadModalFromDeploymentRequests('${item.level === 'Case' ? 'editDeploymentCase' : (item.level === 'Task' ? 'editDeploymentTask' : (item.level === 'Case Bảo trì' ? 'editMaintenanceCase' : (item.level === 'Task Bảo trì' ? 'editMaintenanceTask' : 'editInternalCase')))}', '${item.id}'); return false;" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit me-1"></i>Chỉnh sửa
                                            </button>
                                            <button onclick="toggleRowDetail('${item.id}'); return false;" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-times me-1"></i>Đóng
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
                
                tbody.innerHTML += mainRow + detailRow;
            });
            
            // Thêm event listeners cho các row
            addRowClickHandlers();
        });
}

// Functions cũ để mở trang mới (giữ lại để backup)
function openEditDeploymentCase(caseCode) {
    window.open(`deployment_requests.php?case_id=${caseCode}&open_edit_modal=1`, '_blank');
}

function openEditDeploymentTask(taskNumber) {
    window.open(`deployment_requests.php?task_id=${taskNumber}&open_edit_modal=1`, '_blank');
}

function openEditMaintenanceCase(caseCode) {
    window.open(`maintenance_requests.php?case_id=${caseCode}&open_edit_modal=1`, '_blank');
}

function openEditMaintenanceTask(taskNumber) {
    window.open(`maintenance_requests.php?task_id=${taskNumber}&open_edit_modal=1`, '_blank');
}

function openEditInternalCase(caseNumber) {
    window.open(`internal_cases.php?case_id=${caseNumber}&open_edit_modal=1`, '_blank');
}

// Hàm thêm event listeners cho các row
function addRowClickHandlers() {
    const rows = document.querySelectorAll('.workspace-row');
    rows.forEach(row => {
        row.addEventListener('click', function(e) {
            // Ngăn chặn click nếu click vào button
            if (e.target.tagName === 'BUTTON' || e.target.closest('button')) {
                return;
            }
            
            const itemId = this.getAttribute('data-item-id');
            toggleRowDetail(itemId);
        });
    });
}

// Hàm toggle row detail
function toggleRowDetail(itemId) {
    const mainRow = document.querySelector(`.workspace-row[data-item-id="${itemId}"]`);
    const detailRow = document.querySelector(`.workspace-detail-row[data-item-id="${itemId}"]`);
    
    if (!mainRow || !detailRow) return;
    
    const isExpanded = detailRow.style.display !== 'none';
    
    if (isExpanded) {
        // Đóng row
        detailRow.style.display = 'none';
        mainRow.classList.remove('table-active');
        mainRow.style.backgroundColor = '';
    } else {
        // Đóng tất cả row khác trước
        const allDetailRows = document.querySelectorAll('.workspace-detail-row');
        const allMainRows = document.querySelectorAll('.workspace-row');
        
        allDetailRows.forEach(row => {
            row.style.display = 'none';
        });
        
        allMainRows.forEach(row => {
            row.classList.remove('table-active');
            row.style.backgroundColor = '';
        });
        
        // Mở row hiện tại
        detailRow.style.display = 'table-row';
        mainRow.classList.add('table-active');
        mainRow.style.backgroundColor = '#e3f2fd';
    }
}

document.getElementById('btn-filter-processing').onclick = function() { loadWorkspaceTasks('processing'); };
document.getElementById('btn-filter-done-last-month').onclick = function() { loadWorkspaceTasks('done_last_month'); };
document.getElementById('btn-filter-done-this-month').onclick = function() { loadWorkspaceTasks('done_this_month'); };

// Event listener để ẩn modal đổi mật khẩu khi có modal workspace
document.addEventListener('DOMContentLoaded', function() { 
    loadWorkspaceTasks('processing');
    
    // Theo dõi khi modal workspace được show
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                const modal = mutation.target;
                if (modal.classList.contains('show') && modal.id !== 'changePasswordModal') {
                    // Ẩn modal đổi mật khẩu
                    const passwordModal = document.getElementById('changePasswordModal');
                    if (passwordModal) {
                        passwordModal.style.display = 'none';
                        passwordModal.classList.remove('show');
                        passwordModal.setAttribute('aria-hidden', 'true');
                        passwordModal.style.zIndex = '0';
                        passwordModal.style.opacity = '0';
                        passwordModal.style.visibility = 'hidden';
                        passwordModal.style.pointerEvents = 'none';
                    }
                }
            }
        });
    });
    
    // Theo dõi tất cả modal
    document.querySelectorAll('.modal').forEach(function(modal) {
        observer.observe(modal, { attributes: true });
    });
});
</script>
</body>
</html> 