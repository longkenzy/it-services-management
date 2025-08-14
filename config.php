<?php
/**
 * IT CRM - Configuration Page
 * File: config.php
 * Mục đích: Trang cấu hình hệ thống dành cho Admin
 * Tác giả: IT Support Team
 */

// Include các file cần thiết
require_once 'includes/session.php';
require_once 'config/db.php';

// Bảo vệ trang - chỉ admin mới được truy cập
requireAdmin();

// Lấy thông tin user hiện tại
$current_user = getCurrentUser();

// Lấy dữ liệu phòng ban
try {
    $dept_sql = "SELECT * FROM departments ORDER BY name ASC";
    $dept_stmt = $pdo->prepare($dept_sql);
    $dept_stmt->execute();
    $departments = $dept_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $departments = [];
    error_log("Error loading departments: " . $e->getMessage());
}

// Lấy dữ liệu công ty đối tác
try {
    $partner_sql = "SELECT * FROM partner_companies ORDER BY short_name ASC, name ASC";
    $partner_stmt = $pdo->prepare($partner_sql);
    $partner_stmt->execute();
    $partner_companies = $partner_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $partner_companies = [];
    error_log("Error loading partner companies: " . $e->getMessage());
}

// Lấy dữ liệu công ty EU
try {
    $eu_sql = "SELECT * FROM eu_companies ORDER BY name ASC";
    $eu_stmt = $pdo->prepare($eu_sql);
    $eu_stmt->execute();
    $eu_companies = $eu_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $eu_companies = [];
    error_log("Error loading EU companies: " . $e->getMessage());
}

// Lấy dữ liệu chức vụ
try {
    $positions_sql = "
        SELECT p.*, d.name as department_name 
        FROM positions p 
        LEFT JOIN departments d ON p.department_id = d.id 
        WHERE p.status = 'active'
        ORDER BY d.name ASC, p.name ASC
    ";
    $positions_stmt = $pdo->prepare($positions_sql);
    $positions_stmt->execute();
    $positions = $positions_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $positions = [];
    error_log("Error loading positions: " . $e->getMessage());
}

// Lấy dữ liệu loại case từ database
$internal_case_types = [];
$deployment_case_types = [];
$maintenance_case_types = [];

try {
    $internal_stmt = $pdo->query("SELECT * FROM internal_case_types ORDER BY id ASC");
    $internal_case_types = $internal_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $deployment_stmt = $pdo->query("SELECT * FROM deployment_case_types ORDER BY id ASC");
    $deployment_case_types = $deployment_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $maintenance_stmt = $pdo->query("SELECT * FROM maintenance_case_types ORDER BY id ASC");
    $maintenance_case_types = $maintenance_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error loading case types: " . $e->getMessage());
}

// Lấy flash messages nếu có
$flash_messages = getFlashMessages();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <title>Cấu hình hệ thống - IT Services Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo filemtime('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="assets/css/alert.css?v=<?php echo filemtime('assets/css/alert.css'); ?>">
    <link rel="stylesheet" href="assets/css/table-improvements.css?v=<?php echo filemtime('assets/css/table-improvements.css'); ?>">
    
    <!-- Inline editing CSS -->
    <style>
        /* Inline editing styles */
        .new-case-type-row, .new-position-row {
            background-color: #f8f9fa;
            border-left: 3px solid #007bff;
        }
        
        .new-case-type-row input, .new-position-row input, .new-position-row select {
            border: 1px solid #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .new-case-type-row .btn-success, .new-position-row .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }
        
        .new-case-type-row .btn-success:hover, .new-position-row .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        
        /* Editing row styles */
        tr.editing-row {
            background-color: #fff3cd;
            border-left: 3px solid #ffc107;
        }
        
        tr.editing-row input {
            border: 1px solid #ffc107;
            box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
        }
        
        .config-table th, .config-table td {
            vertical-align: middle;
            padding: 12px;
        }
        
        .config-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .btn-group .btn {
            margin-right: 0;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">
            
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-0">
                                <i class="fas fa-cog me-2 text-primary"></i>
                                Cấu hình hệ thống
                            </h1>
                            <p class="text-muted mb-0">Quản lý phòng ban và công ty đối tác</p>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-danger me-2">
                                <i class="fas fa-crown me-1"></i>
                                Admin Only
                            </span>
                            <small class="text-muted">
                                Chỉ Admin mới có quyền truy cập trang này
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Configuration Tabs -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" id="configTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="departments-tab" data-bs-toggle="tab" 
                                            data-bs-target="#departments" type="button" role="tab" 
                                            aria-controls="departments" aria-selected="true">
                                        <i class="fas fa-building me-2"></i>
                                        Phòng ban
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="partners-tab" data-bs-toggle="tab" 
                                            data-bs-target="#partners" type="button" role="tab" 
                                            aria-controls="partners" aria-selected="false">
                                        <i class="fas fa-handshake me-2"></i>
                                        Công ty đối tác
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="eu-companies-tab" data-bs-toggle="tab" 
                                            data-bs-target="#eu-companies" type="button" role="tab" 
                                            aria-controls="eu-companies" aria-selected="false">
                                        <i class="fas fa-globe me-2"></i>
                                        Công ty EU
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="positions-tab" data-bs-toggle="tab" 
                                            data-bs-target="#positions" type="button" role="tab" 
                                            aria-controls="positions" aria-selected="false">
                                        <i class="fas fa-users-cog me-2"></i>
                                        Chức vụ
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="internal-case-types-tab" data-bs-toggle="tab" 
                                            data-bs-target="#internal-case-types" type="button" role="tab" 
                                            aria-controls="internal-case-types" aria-selected="false">
                                        <i class="fas fa-building me-2"></i>
                                        Loại Case Nội bộ
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="deployment-case-types-tab" data-bs-toggle="tab" 
                                            data-bs-target="#deployment-case-types" type="button" role="tab" 
                                            aria-controls="deployment-case-types" aria-selected="false">
                                        <i class="fas fa-hard-hat me-2"></i>
                                        Loại Case Triển khai
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="maintenance-case-types-tab" data-bs-toggle="tab" 
                                            data-bs-target="#maintenance-case-types" type="button" role="tab" 
                                            aria-controls="maintenance-case-types" aria-selected="false">
                                        <i class="fas fa-tools me-2"></i>
                                        Loại Case Bảo trì
                                    </button>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="card-body">
                            <div class="tab-content" id="configTabsContent">
                                
                                <!-- Tab: Departments -->
                                <div class="tab-pane fade show active" id="departments" role="tabpanel" 
                                     aria-labelledby="departments-tab">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">
                                            <i class="fas fa-building me-2"></i>
                                            Quản lý phòng ban
                                        </h5>
                                        <button class="btn btn-primary" id="btnAddDepartment" onclick="addNewDepartmentRow()">
                                            <i class="fas fa-plus me-2"></i>
                                            Thêm phòng ban mới
                                        </button>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0 config-table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 60px;">STT</th>
                                                    <th>Tên phòng ban</th>
                                                    <th>Văn phòng</th>
                                                    <th>Địa chỉ</th>
                                                    <th style="width: 150px;">Hành động</th>
                                                </tr>
                                            </thead>
                                            <tbody id="departments-tbody">
                                                <?php if (!empty($departments)): ?>
                                                    <?php foreach ($departments as $index => $dept): ?>
                                                        <tr>
                                                            <td><?php echo $index + 1; ?></td>
                                                                                                        <td>
                                                <div class="fw-semibold">
                                                    <?php echo htmlspecialchars($dept['name']); ?>
                                                </div>
                                            </td>
                                                            <td><?php echo htmlspecialchars($dept['office'] ?? 'N/A'); ?></td>
                                                            <td>
                                                                <div class="text-truncate" style="max-width: 200px;" 
                                                                     title="<?php echo htmlspecialchars($dept['address'] ?? ''); ?>">
                                                                    <?php echo htmlspecialchars($dept['address'] ?? 'N/A'); ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group" role="group">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                            onclick="editDepartment(<?php echo $dept['id']; ?>)"
                                                                            title="Chỉnh sửa">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                            onclick="deleteDepartment(<?php echo $dept['id']; ?>, '<?php echo htmlspecialchars($dept['name']); ?>')"
                                                                            title="Xóa">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted py-4">
                                                            <i class="fas fa-building fa-2x mb-2"></i>
                                                            <br>
                                                            Chưa có phòng ban nào
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Tab: Partner Companies -->
                                <div class="tab-pane fade" id="partners" role="tabpanel" 
                                     aria-labelledby="partners-tab">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">
                                            <i class="fas fa-handshake me-2"></i>
                                            Quản lý công ty đối tác
                                        </h5>
                                        <button class="btn btn-primary" id="btnAddPartner" onclick="addNewPartnerRow()">
                                            <i class="fas fa-plus me-2"></i>
                                            Thêm công ty đối tác
                                        </button>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0 config-table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 60px;">STT</th>
                                                    <th style="width: 200px;">Tên công ty</th>
                                                    <th style="width: 120px;">Tên viết tắt</th>
                                                    <th style="width: 300px;">Địa chỉ</th>
                                                    <th style="width: 140px;">Người liên hệ</th>
                                                    <th style="width: 130px;">SĐT người liên hệ</th>
                                                    <th style="width: 120px;">Trạng thái</th>
                                                    <th style="width: 130px;">Hành động</th>
                                                </tr>
                                            </thead>
                                            <tbody id="partners-tbody">
                                                <?php if (!empty($partner_companies)): ?>
                                                    <?php foreach ($partner_companies as $index => $partner): ?>
                                                        <tr>
                                                            <td><?php echo $index + 1; ?></td>
                                                                                                        <td>
                                                <div class="fw-semibold">
                                                    <?php echo htmlspecialchars($partner['name']); ?>
                                                </div>
                                            </td>
                                                            <td><?php echo htmlspecialchars($partner['short_name'] ?? 'N/A'); ?></td>
                                                            <td>
                                                                <div style="word-wrap: break-word; white-space: normal; line-height: 1.4;">
                                                                    <?php echo htmlspecialchars($partner['address'] ?? 'N/A'); ?>
                                                                </div>
                                                            </td>
                                                                                                                                                                    <td>
                                                                <div><?php echo htmlspecialchars($partner['contact_person'] ?? 'N/A'); ?></div>
                                            </td>
                                                            <td>
                                                                <div><?php echo htmlspecialchars($partner['contact_phone'] ?? 'N/A'); ?></div>
                                                            </td>
                                                            <td>
                                                                <?php if ($partner['status'] === 'active'): ?>
                                                                    <span class="badge bg-success">Hoạt động</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">Ngừng hoạt động</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group" role="group">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                            onclick="editPartner(<?php echo $partner['id']; ?>)"
                                                                            title="Chỉnh sửa">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                            onclick="deletePartner(<?php echo $partner['id']; ?>, '<?php echo htmlspecialchars($partner['name']); ?>')"
                                                                            title="Xóa">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="8" class="text-center text-muted py-4">
                                                            <i class="fas fa-handshake fa-2x mb-2"></i>
                                                            <br>
                                                            Chưa có công ty đối tác nào
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Tab: EU Companies -->
                                <div class="tab-pane fade" id="eu-companies" role="tabpanel" 
                                     aria-labelledby="eu-companies-tab">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">
                                            <i class="fas fa-globe me-2"></i>
                                            Quản lý công ty EU
                                        </h5>
                                        <button class="btn btn-primary" id="btnAddEUCompany" onclick="addNewEUCompanyRow()">
                                            <i class="fas fa-plus me-2"></i>
                                            Thêm công ty EU
                                        </button>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0 config-table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 60px;">STT</th>
                                                    <th style="width: 200px;">Tên công ty</th>
                                                    <th style="width: 120px;">Tên viết tắt</th>
                                                    <th style="width: 300px;">Địa chỉ</th>
                                                    <th style="width: 140px;">Người liên hệ</th>
                                                    <th style="width: 130px;">SĐT người liên hệ</th>
                                                    <th style="width: 120px;">Trạng thái</th>
                                                    <th style="width: 130px;">Hành động</th>
                                                </tr>
                                            </thead>
                                            <tbody id="eu-companies-tbody">
                                                <?php if (!empty($eu_companies)): ?>
                                                    <?php foreach ($eu_companies as $index => $company): ?>
                                                        <tr>
                                                            <td><?php echo $index + 1; ?></td>
                                                            <td>
                                                                <div class="fw-semibold">
                                                                    <?php echo htmlspecialchars($company['name']); ?>
                                                                </div>
                                                            </td>
                                                            <td><?php echo htmlspecialchars($company['short_name'] ?? 'N/A'); ?></td>
                                                            <td>
                                                                <div style="word-wrap: break-word; white-space: normal; line-height: 1.4;">
                                                                    <?php echo htmlspecialchars($company['address'] ?? 'N/A'); ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="text-truncate" style="max-width: 150px;" 
                                                                     title="<?php echo htmlspecialchars($company['contact_person'] ?? ''); ?>">
                                                                    <?php echo htmlspecialchars($company['contact_person'] ?? 'N/A'); ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="text-truncate" style="max-width: 150px;" 
                                                                     title="<?php echo htmlspecialchars($company['contact_phone'] ?? ''); ?>">
                                                                    <?php echo htmlspecialchars($company['contact_phone'] ?? 'N/A'); ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <?php if ($company['status'] === 'active'): ?>
                                                                    <span class="badge bg-success">Hoạt động</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-secondary">Ngừng hoạt động</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group" role="group">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                            onclick="editEUCompany(<?php echo $company['id']; ?>)"
                                                                            title="Chỉnh sửa">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                            onclick="deleteEUCompany(<?php echo $company['id']; ?>, '<?php echo htmlspecialchars($company['name']); ?>')"
                                                                            title="Xóa">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="8" class="text-center text-muted py-4">
                                                            <i class="fas fa-globe fa-2x mb-2"></i>
                                                            <br>
                                                            Chưa có công ty EU nào
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Tab: Positions -->
                                <div class="tab-pane fade" id="positions" role="tabpanel" aria-labelledby="positions-tab">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">
                                            <i class="fas fa-users-cog me-2"></i>
                                            Quản lý chức vụ
                                        </h5>
                                        <button class="btn btn-primary" id="btnAddPosition" onclick="addNewPositionRow()">
                                            <i class="fas fa-plus me-2"></i>
                                            Thêm chức vụ
                                        </button>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0 config-table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 60px;">STT</th>
                                                    <th style="width: 300px;">Tên chức vụ</th>
                                                    <th style="width: 200px;">Thuộc phòng ban</th>
                                                    <th style="width: 130px;">Hành động</th>
                                                </tr>
                                            </thead>
                                            <tbody id="positions-tbody">
                                                <?php if (!empty($positions)): ?>
                                                    <?php foreach ($positions as $index => $position): ?>
                                                        <tr>
                                                            <td><?php echo $index + 1; ?></td>
                                                            <td>
                                                                <div class="fw-semibold">
                                                                    <?php echo htmlspecialchars($position['name']); ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-info">
                                                                    <?php echo htmlspecialchars($position['department_name'] ?? 'N/A'); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group" role="group">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                            onclick="editPosition(<?php echo $position['id']; ?>)"
                                                                            title="Chỉnh sửa">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                            onclick="deletePosition(<?php echo $position['id']; ?>, '<?php echo htmlspecialchars($position['name']); ?>')"
                                                                            title="Xóa">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted py-4">
                                                            <i class="fas fa-users-cog fa-2x mb-2"></i>
                                                            <br>
                                                            Chưa có chức vụ nào
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Tab: Internal Case Types -->
                                <div class="tab-pane fade" id="internal-case-types" role="tabpanel" aria-labelledby="internal-case-types-tab">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">
                                            <i class="fas fa-building me-2"></i>
                                            Loại Case Nội bộ
                                        </h5>
                                        <button class="btn btn-primary" id="btnAddInternalCaseType" onclick="addNewCaseTypeRow('internal')">
                                            <i class="fas fa-plus me-2"></i> Thêm loại case
                                        </button>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0 config-table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 60px;">STT</th>
                                                    <th>Tên loại case</th>
                                                    <th style="width: 120px;">Hành động</th>
                                                </tr>
                                            </thead>
                                            <tbody id="internal-case-types-tbody">
                                                <?php if (!empty($internal_case_types)): ?>
                                                    <?php foreach ($internal_case_types as $index => $type): ?>
                                                        <tr>
                                                            <td><?php echo $index + 1; ?></td>
                                                            <td>
                                                                <div class="fw-semibold">
                                                                    <?php echo htmlspecialchars($type['name']); ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group" role="group">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                            onclick="editCaseType(<?php echo $type['id']; ?>, 'internal')"
                                                                            title="Chỉnh sửa">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                            onclick="deleteCaseType(<?php echo $type['id']; ?>, '<?php echo htmlspecialchars($type['name']); ?>', 'internal')"
                                                                            title="Xóa">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center text-muted py-4">
                                                            <i class="fas fa-building fa-2x mb-2"></i>
                                                            <br>
                                                            Chưa có loại case nội bộ nào
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Tab: Deployment Case Types -->
                                <div class="tab-pane fade" id="deployment-case-types" role="tabpanel" aria-labelledby="deployment-case-types-tab">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">
                                            <i class="fas fa-hard-hat me-2"></i>
                                            Loại Case Triển khai
                                        </h5>
                                        <button class="btn btn-primary" id="btnAddDeploymentCaseType" onclick="addNewCaseTypeRow('deployment')">
                                            <i class="fas fa-plus me-2"></i> Thêm loại case
                                        </button>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0 config-table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 60px;">STT</th>
                                                    <th>Tên loại case</th>
                                                    <th style="width: 120px;">Hành động</th>
                                                </tr>
                                            </thead>
                                            <tbody id="deployment-case-types-tbody">
                                                <?php if (!empty($deployment_case_types)): ?>
                                                    <?php foreach ($deployment_case_types as $index => $type): ?>
                                                        <tr>
                                                            <td><?php echo $index + 1; ?></td>
                                                            <td>
                                                                <div class="fw-semibold">
                                                                    <?php echo htmlspecialchars($type['name']); ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group" role="group">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                            onclick="editCaseType(<?php echo $type['id']; ?>, 'deployment')"
                                                                            title="Chỉnh sửa">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                            onclick="deleteCaseType(<?php echo $type['id']; ?>, '<?php echo htmlspecialchars($type['name']); ?>', 'deployment')"
                                                                            title="Xóa">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center text-muted py-4">
                                                            <i class="fas fa-hard-hat fa-2x mb-2"></i>
                                                            <br>
                                                            Chưa có loại case triển khai nào
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                
                                <!-- Tab: Maintenance Case Types -->
                                <div class="tab-pane fade" id="maintenance-case-types" role="tabpanel" aria-labelledby="maintenance-case-types-tab">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">
                                            <i class="fas fa-tools me-2"></i>
                                            Loại Case Bảo trì
                                        </h5>
                                        <button class="btn btn-primary" id="btnAddMaintenanceCaseType" onclick="addNewCaseTypeRow('maintenance')">
                                            <i class="fas fa-plus me-2"></i> Thêm loại case
                                        </button>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0 config-table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 60px;">STT</th>
                                                    <th>Tên loại case</th>
                                                    <th style="width: 120px;">Hành động</th>
                                                </tr>
                                            </thead>
                                            <tbody id="maintenance-case-types-tbody">
                                                <?php if (!empty($maintenance_case_types)): ?>
                                                    <?php foreach ($maintenance_case_types as $index => $type): ?>
                                                        <tr>
                                                            <td><?php echo $index + 1; ?></td>
                                                            <td>
                                                                <div class="fw-semibold">
                                                                    <?php echo htmlspecialchars($type['type_name'] ?? ''); ?>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="btn-group" role="group">
                                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                            onclick="editCaseType(<?php echo $type['id']; ?>, 'maintenance')"
                                                                            title="Chỉnh sửa">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                            onclick="deleteCaseType(<?php echo $type['id']; ?>, '<?php echo htmlspecialchars($type['type_name'] ?? ''); ?>', 'maintenance')"
                                                                            title="Xóa">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center text-muted py-4">
                                                            <i class="fas fa-tools fa-2x mb-2"></i>
                                                            <br>
                                                            Chưa có loại case bảo trì nào
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Modal forms have been removed - now using inline editing -->
    <!-- All add/edit functionality is now handled directly in the table rows -->
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Alert JS -->
    <script src="assets/js/alert.js?v=<?php echo filemtime('assets/js/alert.js'); ?>"></script>
    
    <!-- Case Types JS -->
    <script src="assets/js/case-types.js?v=<?php echo filemtime('assets/js/case-types.js'); ?>"></script>
    
    <!-- Departments JS -->
    <script src="assets/js/departments.js?v=<?php echo filemtime('assets/js/departments.js'); ?>"></script>
    
    <!-- Partners JS -->
    <script src="assets/js/partners.js?v=<?php echo filemtime('assets/js/partners.js'); ?>"></script>
    
    <!-- EU Companies JS -->
    <script src="assets/js/eu-companies.js?v=<?php echo filemtime('assets/js/eu-companies.js'); ?>"></script>
    
    <!-- Positions JS -->
    <script src="assets/js/positions.js?v=<?php echo filemtime('assets/js/positions.js'); ?>"></script>
    
    <!-- Configuration JS -->
    <script src="assets/js/config.js?v=<?php echo filemtime('assets/js/config.js'); ?>"></script>
    
    <script>
        $(document).ready(function() {
            // Flash messages
            <?php if (!empty($flash_messages)): ?>
                <?php foreach ($flash_messages as $message): ?>
                    showAlert('<?php echo addslashes($message['message']); ?>', '<?php echo $message['type']; ?>');
                <?php endforeach; ?>
            <?php endif; ?>
            
            // ===== DROPDOWN ACTIONS ===== //
            
            // Xử lý click "Đăng xuất"
            $(document).on('click', '[data-action="logout"]', function(e) {
                e.preventDefault();
                showInfo('Đang đăng xuất...');
                setTimeout(function() {
                    window.location.href = 'auth/logout.php';
                }, 1000);
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
    </script>
</body>
</html> 