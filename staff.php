<?php
/**
 * IT CRM - Staff Management Page
 * File: staff.php
 * Mục đích: Trang quản lý nhân sự
 */

// Include session management
require_once 'includes/session.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    header('Location: index.html');
    exit();
}

// Lấy thông tin user hiện tại
$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách nhân sự - IT Services Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo filemtime('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="assets/css/staff.css?v=<?php echo filemtime('assets/css/staff.css'); ?>">
    <link rel="stylesheet" href="assets/css/alert.css?v=<?php echo filemtime('assets/css/alert.css'); ?>">
    
    <!-- No Border Radius Override -->
    <link rel="stylesheet" href="assets/css/no-border-radius.css?v=<?php echo filemtime('assets/css/no-border-radius.css'); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
    <?php 
    // Include header chung
    include 'includes/header.php'; 
    ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">
            <!-- Success/Error messages will be shown via JavaScript alert system -->
            
            <!-- Page Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="page-title">
                            <i class="fas fa-users me-2"></i>
                            Danh sách nhân sự
                        </h1>
                        <p class="page-subtitle">Quản lý thông tin nhân sự trong hệ thống</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <button class="btn btn-primary btn-add-staff" id="btnAddStaff">
                            <i class="fas fa-plus me-2"></i>
                            Thêm nhân sự mới
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Tìm kiếm</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                    <input type="text" class="form-control" placeholder="Tên, mã NV, email..." id="staffSearchInput">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Phòng ban</label>
                                <select class="form-select" id="departmentFilter">
                                    <option value="">Tất cả phòng ban</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Chức vụ</label>
                                <select class="form-select" id="positionFilter">
                                    <option value="">Tất cả chức vụ</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Giới tính</label>
                                <select class="form-select" id="genderFilter">
                                    <option value="">Tất cả</option>
                                    <option value="Nam">Nam</option>
                                    <option value="Nữ">Nữ</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Sắp xếp</label>
                                <select class="form-select" id="sortFilter">
                                    <option value="start_date:DESC" selected>Ngày vào làm (Mới nhất)</option>
                                    <option value="start_date:ASC">Ngày vào làm (Cũ nhất)</option>
                                    <option value="employee_code:ASC">Mã NV (A-Z)</option>
                                    <option value="fullname:ASC">Tên (A-Z)</option>
                                    <option value="fullname:DESC">Tên (Z-A)</option>
                                    <option value="seniority:DESC">Thâm niên (Cao-Thấp)</option>
                                    <option value="created_at:DESC">Mới nhất</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button class="btn btn-outline-secondary w-100" id="btnResetFilter">
                                    <i class="fas fa-refresh"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Section -->
            <div class="stats-section">
                <div class="row">
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number" id="totalStaff">0</div>
                                <div class="stat-label">Tổng số nhân sự</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number" id="totalDepartments">0</div>
                                <div class="stat-label">Phòng ban</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-number" id="totalPositions">0</div>
                                <div class="stat-label">Chức vụ</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Staff Table -->
            <div class="table-section">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-table me-2"></i>
                                    Danh sách nhân sự
                                </h5>
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="table-actions">
                                    <button class="btn btn-sm btn-outline-primary" id="btnSelectAll">
                                        <i class="fas fa-check-square me-1"></i>
                                        Chọn tất cả
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" id="btnExport">
                                        <i class="fas fa-download me-1"></i>
                                        Xuất Excel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <!-- Loading State -->
                        <div id="loadingState" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Đang tải...</span>
                            </div>
                            <p class="mt-2 text-muted">Đang tải dữ liệu nhân sự...</p>
                        </div>

                        <!-- Empty State -->
                        <div id="emptyState" class="text-center py-5 d-none">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Chưa có nhân sự</h5>
                            <p class="text-muted">Hiện tại chưa có nhân sự nào trong hệ thống.</p>
                            <button class="btn btn-primary" id="btnAddFirstStaff">
                                <i class="fas fa-plus me-2"></i>
                                Thêm nhân sự đầu tiên
                            </button>
                        </div>

                        <!-- Staff Table -->
                        <div id="staffTableContainer">
                            <table class="table table-hover staff-table">
                                <thead>
                                    <tr>
                                        <th width="40">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="selectAllCheckbox">
                                            </div>
                                        </th>
                                                                    <th>MSNV</th>
                            <th>Họ và tên</th>
                                        <th>Năm sinh</th>
                                        <th>Giới tính</th>
                                        <th>Avatar</th>
                                                                    <th>Chức vụ</th>
                            <th>Phòng ban</th>
                            <th>SĐT Chính</th>
                                        <th>Email công việc</th>
                                        <th>Loại hợp đồng</th>
                                        <th width="80">Option</th>
                                    </tr>
                                </thead>
                                <tbody id="staffTableBody">
                                    <!-- Data will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            
        </div>
    </main>

    <!-- ===== MODAL THÊM NHÂN SỰ ===== -->
    <div class="modal fade" id="addStaffModal" tabindex="-1" aria-labelledby="addStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content">
                <form id="addStaffForm" method="post" enctype="multipart/form-data">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="addStaffModalLabel">
                            <i class="fas fa-user-plus me-2"></i>Thêm nhân sự mới
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- CỘT TRÁI -->
                            <div class="col-6 pe-2">
                                <!-- THÔNG TIN CHUNG -->
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>THÔNG TIN CHUNG</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Mã số nhân viên <span class="text-danger">*</span></label>
                                                </div>
                                                <div class="col-8">
                                                    <input type="text" class="form-control" name="staff_code" id="staff_code" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Họ và tên <span class="text-danger">*</span></label>
                                                </div>
                                                <div class="col-8">
                                                    <input type="text" class="form-control" name="fullname" id="fullname" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Năm sinh</label>
                                                </div>
                                                <div class="col-8">
                                                    <input type="date" class="form-control" name="birth_date" id="birth_date">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Giới tính</label>
                                                </div>
                                                <div class="col-8">
                                                    <select class="form-select" name="gender" id="gender">
                                                        <option value="">--Chọn--</option>
                                                        <option value="Nam">Nam</option>
                                                        <option value="Nữ">Nữ</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Quê quán</label>
                                                </div>
                                                <div class="col-8">
                                                    <input type="text" class="form-control" name="hometown" id="hometown">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Tôn giáo</label>
                                                </div>
                                                <div class="col-8">
                                                    <input type="text" class="form-control" name="religion" id="religion">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Dân tộc</label>
                                                </div>
                                                <div class="col-8">
                                                    <input type="text" class="form-control" name="ethnicity" id="ethnicity">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- THỜI GIAN CÔNG TÁC -->
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fas fa-calendar me-2"></i>THỜI GIAN CÔNG TÁC</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Thâm niên</label>
                                                </div>
                                                <div class="col-8">
                                                    <input type="text" class="form-control" name="seniority_display" id="seniority_display" readonly placeholder="Tự động">
                                                    <input type="hidden" name="seniority" id="seniority" value="0">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Ngày vào làm</label>
                                                </div>
                                                <div class="col-8">
                                                    <input type="date" class="form-control" name="start_date" id="start_date">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- LIÊN HỆ -->
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fas fa-address-book me-2"></i>LIÊN HỆ</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">SĐT chính</label>
                                                </div>
                                                <div class="col-8">
                                                    <input type="tel" class="form-control" name="phone_main" id="phone_main">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">SĐT phụ</label>
                                                </div>
                                                <div class="col-8">
                                                    <input type="tel" class="form-control" name="phone_alt" id="phone_alt">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Mail cá nhân</label>
                                                </div>
                                                <div class="col-8">
                                                    <input type="email" class="form-control" name="email_personal" id="email_personal">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Email công việc</label>
                                                </div>
                                                <div class="col-8">
                                                    <input type="email" class="form-control" name="email_work" id="email_work">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Nơi sinh</label>
                                                </div>
                                                <div class="col-8">
                                                    <input type="text" class="form-control" name="place_of_birth" id="place_of_birth">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Địa chỉ thường trú</label>
                                                </div>
                                                <div class="col-8">
                                                    <textarea class="form-control" name="address_perm" id="address_perm" rows="2"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Địa chỉ tạm trú</label>
                                                </div>
                                                <div class="col-8">
                                                    <textarea class="form-control" name="address_temp" id="address_temp" rows="2"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CỘT PHẢI -->
                            <div class="col-6 ps-2">
                                <!-- ẢNH ĐẠI DIỆN -->
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fas fa-camera me-2"></i>ẢNH ĐẠI DIỆN</h6>
                                    </div>
                                    <div class="card-body text-center">
                                        <div class="avatar-upload-container mb-3">
                                            <div class="react-logo-container mb-3">
                                                <svg width="120" height="120" viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
                                                    <defs>
                                                        <linearGradient id="reactGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                                            <stop offset="0%" style="stop-color:#61dafb;stop-opacity:1" />
                                                            <stop offset="100%" style="stop-color:#21a0c4;stop-opacity:1" />
                                                        </linearGradient>
                                                    </defs>
                                                    <circle cx="60" cy="60" r="58" fill="#f8f9fa" stroke="#dee2e6" stroke-width="2"/>
                                                    <g transform="translate(60,60)">
                                                        <circle cx="0" cy="0" r="4" fill="#61dafb"/>
                                                        <ellipse cx="0" cy="0" rx="30" ry="12" fill="none" stroke="#61dafb" stroke-width="2"/>
                                                        <ellipse cx="0" cy="0" rx="30" ry="12" fill="none" stroke="#61dafb" stroke-width="2" transform="rotate(60)"/>
                                                        <ellipse cx="0" cy="0" rx="30" ry="12" fill="none" stroke="#61dafb" stroke-width="2" transform="rotate(120)"/>
                                                    </g>
                                                </svg>
                                            </div>
                                            <img id="avatarPreview" src="" alt="Avatar Preview" 
                                                 class="rounded-circle border d-none" style="width: 120px; height: 120px; object-fit: cover;">
                                        </div>
                                        <div class="mb-3">
                                            <label for="avatar" class="form-label">Chọn ảnh đại diện</label>
                                            <input type="file" class="form-control" name="avatar" id="avatar" accept="image/*">
                                        </div>
                                        <small class="text-muted">Chấp nhận: JPG, PNG, GIF. Tối đa 2MB</small>
                                    </div>
                                </div>

                                <!-- CÔNG VIỆC -->
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fas fa-briefcase me-2"></i>CÔNG VIỆC</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Chức vụ</label>
                                                </div>
                                                <div class="col-8">
                                                    <select class="form-select" name="position_id" id="position_id">
                                                        <!-- Options will be loaded dynamically -->
                                                    </select>
                                                    <input type="hidden" name="position" id="position_name">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Loại hình làm việc</label>
                                                </div>
                                                <div class="col-8">
                                                    <select class="form-select" name="job_type" id="job_type">
                                                        <option value="">--Chọn--</option>
                                                        <option value="Chính thức">Chính thức</option>
                                                        <option value="Thử việc">Thử việc</option>
                                                        <option value="Học việc">Học việc</option>
                                                        <option value="Thực tập">Thực tập</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Phòng ban</label>
                                                </div>
                                                <div class="col-8">
                                                    <input type="text" class="form-control" name="department" id="department" readonly>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Văn phòng làm việc</label>
                                                </div>
                                                <div class="col-8">
                                                    <input type="text" class="form-control" name="office" id="office">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Địa chỉ văn phòng</label>
                                                </div>
                                                <div class="col-8">
                                                    <textarea class="form-control" name="office_address" id="office_address" rows="2"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- TÀI KHOẢN ĐĂNG NHẬP -->
                                <div class="card mb-3">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0"><i class="fas fa-key me-2"></i>TÀI KHOẢN ĐĂNG NHẬP</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Username <span class="text-danger">*</span></label>
                                                </div>
                                                <div class="col-8">
                                                    <input type="text" class="form-control" name="username" id="username" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Password <span class="text-danger">*</span></label>
                                                </div>
                                                <div class="col-8">
                                                    <div class="input-group">
                                                        <input type="password" class="form-control" name="password" id="password" required>
                                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-2">
                                            <div class="row align-items-center">
                                                <div class="col-4">
                                                    <label class="form-label mb-0">Vai trò</label>
                                                </div>
                                                <div class="col-8">
                                                    <select class="form-select" name="role" id="role">
                                                        <option value="user">Nhân viên</option>
                                                        <option value="leader">Trưởng nhóm</option>
                                                        <option value="admin">Quản trị viên</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Hidden div to ensure scrollbar appears -->
                        <div style="height: 20px; width: 1px; visibility: hidden;"></div>
                    </div>
                    <div class="modal-footer">
                        <div class="row w-100">
                            <div class="col-6 pe-2">
                                <!-- Cột trái - trống -->
                            </div>
                            <div class="col-6 ps-2">
                                <!-- Cột phải - chứa nút -->
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="fas fa-times me-2"></i>Hủy
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Thêm nhân sự
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- jQuery (load trước) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom CSS for Add Staff Modal -->
    <style>
        /* Custom styles for large add staff modal */
        #addStaffModal .modal-dialog {
            max-width: 95vw !important;
            width: 95vw !important;
            height: 95vh !important;
            margin: 2.5vh auto !important;
        }
        
        #addStaffModal .modal-content {
            height: 100% !important;
            display: flex !important;
            flex-direction: column !important;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        #addStaffModal .modal-header {
            flex-shrink: 0 !important;
            border-bottom: 2px solid #dee2e6;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }
        
        #addStaffModal .modal-body {
            flex: 1 !important;
            overflow-y: scroll !important;
            overflow-x: hidden !important;
            padding: 1rem !important;
            max-height: calc(95vh - 120px) !important;
        }
        
        #addStaffModal .modal-footer {
            flex-shrink: 0 !important;
            border-top: 2px solid #dee2e6;
            padding: 1rem 1.5rem;
            background-color: #f8f9fa;
        }
        
        /* Force scrollbar to always show */
        #addStaffModal .modal-body {
            scrollbar-width: thin;
            scrollbar-color: #c1c1c1 #f1f1f1;
        }
        
        /* Webkit scrollbar styling */
        #addStaffModal .modal-body::-webkit-scrollbar {
            width: 12px !important;
            background: #f1f1f1;
        }
        
        #addStaffModal .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1 !important;
            border-radius: 6px;
        }
        
        #addStaffModal .modal-body::-webkit-scrollbar-thumb {
            background: #c1c1c1 !important;
            border-radius: 6px;
            border: 2px solid #f1f1f1;
        }
        
        #addStaffModal .modal-body::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8 !important;
        }
        
        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            #addStaffModal .modal-dialog {
                margin: 0.5rem !important;
                max-width: calc(100vw - 1rem) !important;
                width: calc(100vw - 1rem) !important;
                height: calc(100vh - 1rem) !important;
            }
            
            #addStaffModal .modal-body {
                max-height: calc(100vh - 140px) !important;
            }
        }
        
        /* Form styling improvements */
        #addStaffModal .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border: 1px solid rgba(0, 0, 0, 0.125);
            margin-bottom: 0.75rem;
        }
        
        #addStaffModal .card-header {
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.75rem 1rem;
        }
        
        #addStaffModal .card-body {
            padding: 1rem;
        }
        
        /* Reduce spacing for form elements */
        #addStaffModal .form-label {
            margin-bottom: 0.25rem;
            font-weight: 500;
        }
        
        #addStaffModal .form-control,
        #addStaffModal .form-select {
            margin-bottom: 0.5rem;
        }
        
        /* Avatar preview improvements */
        #addStaffModal .avatar-upload-container {
            position: relative;
            display: inline-block;
        }
        
        #addStaffModal #avatarPreview {
            transition: all 0.3s ease;
            border: 3px solid #e9ecef;
        }
        
        #addStaffModal #avatarPreview:hover {
            border-color: #007bff;
            transform: scale(1.05);
        }
        
        /* Form input improvements */
        #addStaffModal .form-control:focus,
        #addStaffModal .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        /* Ensure content has enough height to scroll */
        /* #addStaffModal .modal-body .row {
            min-height: 100vh;
        } */
        
        /* Add some padding at the bottom */
        #addStaffModal .modal-body::after {
            content: "";
            display: block;
            height: 2rem;
        }
    </style>

    <!-- Alert System -->
    <script src="assets/js/alert.js?v=<?php echo filemtime('assets/js/alert.js'); ?>"></script>
    
    <!-- Custom JavaScript -->
    <script src="assets/js/staff.js?v=<?php echo filemtime('assets/js/staff.js'); ?>"></script>
    
    <script>
    $(document).ready(function() {
        // Handle URL parameters for success/error messages
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('success')) {
            const successType = urlParams.get('success');
            const name = urlParams.get('name') || 'nhân sự';
            
            switch(successType) {
                case 'staff_updated':
                    showSuccess(`Đã cập nhật thành công thông tin: <strong>${name}</strong>`);
                    break;
                default:
                    showSuccess('Thao tác thành công!');
            }
        }
        
        if (urlParams.has('error')) {
            const errorType = urlParams.get('error');
            
            switch(errorType) {
                case 'no_permission':
                    showError('Bạn không có quyền truy cập chức năng này!');
                    break;
                case 'invalid_id':
                    showError('ID nhân sự không hợp lệ!');
                    break;
                case 'staff_not_found':
                    showError('Không tìm thấy nhân sự!');
                    break;
                case 'database_error':
                    showError('Có lỗi xảy ra với cơ sở dữ liệu!');
                    break;
                default:
                    showError('Có lỗi xảy ra!');
            }
        }
        
        // ===== DROPDOWN ACTIONS ===== //
        
        // Xử lý click "Đổi mật khẩu"
        $(document).on('click', '[data-action="change-password"]', function(e) {
            e.preventDefault();
            $('#changePasswordModal').modal('show');
            // Reset form khi mở modal
            $('#changePasswordForm')[0].reset();
            $('#changePasswordError').addClass('d-none');
            $('#changePasswordSuccess').addClass('d-none');
        });
        
        // Xử lý click "Thông tin cá nhân"
        $(document).on('click', '[data-action="profile"]', function(e) {
            e.preventDefault();
            showInfo('Tính năng đang phát triển...');
        });
        
        // Xử lý click "Cài đặt"
        $(document).on('click', '[data-action="settings"]', function(e) {
            e.preventDefault();
            showInfo('Tính năng đang phát triển...');
        });
        
        // Xử lý click "Thông báo"
        $(document).on('click', '[data-action="notifications"]', function(e) {
            e.preventDefault();
            showInfo('Tính năng đang phát triển...');
        });
        
        // Xử lý click "Đăng xuất"
        $(document).on('click', '[data-action="logout"]', function(e) {
            e.preventDefault();
            showInfo('Đang đăng xuất...');
            setTimeout(function() {
                window.location.href = 'auth/logout.php';
            }, 1000);
        });
        
        // ===== CHANGE PASSWORD MODAL ===== //
        
        // Xử lý submit form đổi mật khẩu
        $('#changePasswordForm').on('submit', function(e) {
            e.preventDefault();
            
            const oldPassword = $('#old_password').val().trim();
            const newPassword = $('#new_password').val().trim();
            const confirmPassword = $('#confirm_password').val().trim();
            
            // Validation
            if (!oldPassword || !newPassword || !confirmPassword) {
                showChangePasswordError('Vui lòng điền đầy đủ thông tin');
                return;
            }
            
            if (newPassword.length < 6) {
                showChangePasswordError('Mật khẩu mới phải có ít nhất 6 ký tự');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                showChangePasswordError('Mật khẩu mới và xác nhận mật khẩu không khớp');
                return;
            }
            
            if (oldPassword === newPassword) {
                showChangePasswordError('Mật khẩu mới phải khác mật khẩu cũ');
                return;
            }
            
            // Disable submit button
            const submitBtn = $('#changePasswordForm button[type="submit"]');
            const originalText = submitBtn.text();
            submitBtn.prop('disabled', true).text('Đang xử lý...');
            
            // AJAX request
            $.ajax({
                url: 'change_password.php',
                method: 'POST',
                data: {
                    old_password: oldPassword,
                    new_password: newPassword,
                    confirm_password: confirmPassword
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showChangePasswordSuccess(response.message);
                        // Đóng modal sau 2 giây
                        setTimeout(function() {
                            $('#changePasswordModal').modal('hide');
                            showSuccess('Đổi mật khẩu thành công!');
                        }, 2000);
                    } else {
                        showChangePasswordError(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Change password error:', error);
                    showChangePasswordError('Có lỗi xảy ra. Vui lòng thử lại sau.');
                },
                complete: function() {
                    // Re-enable submit button
                    submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = '';
            
            if (password.length >= 6) strength += 25;
            if (password.length >= 8) strength += 25;
            if (/[a-z]/.test(password)) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;
            if (/[^A-Za-z0-9]/.test(password)) strength += 25;
            
            // Cap at 100%
            strength = Math.min(strength, 100);
            
            // Determine strength level
            if (strength < 25) {
                feedback = 'Rất yếu';
            } else if (strength < 50) {
                feedback = 'Yếu';
            } else if (strength < 75) {
                feedback = 'Trung bình';
            } else if (strength < 100) {
                feedback = 'Mạnh';
            } else {
                feedback = 'Rất mạnh';
            }
            
            return { strength, feedback };
        }
        
        // Update password strength indicator
        $('#new_password').on('input', function() {
            const password = $(this).val();
            const { strength, feedback } = checkPasswordStrength(password);
            
            const strengthFill = $('#strengthFill');
            const strengthText = $('#strengthText');
            
            strengthFill.removeClass('weak fair good strong');
            strengthText.removeClass('text-danger text-warning text-info text-success');
            
            if (strength < 25) {
                strengthFill.addClass('weak');
                strengthText.addClass('text-danger');
            } else if (strength < 50) {
                strengthFill.addClass('fair');
                strengthText.addClass('text-warning');
            } else if (strength < 75) {
                strengthFill.addClass('good');
                strengthText.addClass('text-info');
            } else {
                strengthFill.addClass('strong');
                strengthText.addClass('text-success');
            }
            
            strengthText.text(feedback);
        });
        
        // Helper functions cho change password modal
        function showChangePasswordError(message) {
            showError(message);
        }
        
        function showChangePasswordSuccess(message) {
            showSuccess(message);
        }
        
        // Reset modal khi đóng
        $('#changePasswordModal').on('hidden.bs.modal', function() {
            $('#changePasswordForm')[0].reset();
            $('#changePasswordForm button[type="submit"]').prop('disabled', false).text('Đổi mật khẩu');
        });
    });
    </script>
</body>
</html> 