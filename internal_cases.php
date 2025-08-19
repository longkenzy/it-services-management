<?php
/**
 * IT CRM - Internal Cases Page
 * File: internal_cases.php
 * Mục đích: Quản lý case nội bộ
 */

// Include các file cần thiết
require_once 'includes/session.php';

// Bảo vệ trang - yêu cầu đăng nhập
requireLogin();

// Lấy thông tin user hiện tại
$current_user = getCurrentUser();

// Include database connection
require_once 'config/db.php';

// Tạo bảng internal_cases nếu chưa tồn tại
try {
    $checkTable = $pdo->query("SHOW TABLES LIKE 'internal_cases'");
    if ($checkTable->rowCount() == 0) {
        $createTableSQL = "CREATE TABLE internal_cases (
            id INT AUTO_INCREMENT PRIMARY KEY,
            case_number VARCHAR(50) NOT NULL UNIQUE,
            requester_id INT,
            handler_id INT,
            transferred_by INT,
            case_type VARCHAR(100),
            priority VARCHAR(20) DEFAULT 'normal',
            issue_title VARCHAR(255),
            issue_description TEXT,
            status VARCHAR(20) DEFAULT 'pending',
            notes TEXT,
            start_date DATE,
            due_date DATE,
            completed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($createTableSQL);
    }
} catch (PDOException $e) {
    // Ignore table creation errors
}

// Tạo bảng internal_case_types nếu chưa tồn tại
try {
    $checkTable = $pdo->query("SHOW TABLES LIKE 'internal_case_types'");
    if ($checkTable->rowCount() == 0) {
        $createTableSQL = "CREATE TABLE internal_case_types (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $pdo->exec($createTableSQL);
        
        // Thêm dữ liệu mẫu
        $insertSampleData = "INSERT INTO internal_case_types (name, description) VALUES
            ('Hỗ trợ kỹ thuật', 'Các vấn đề về kỹ thuật, phần mềm, phần cứng'),
            ('Yêu cầu tài khoản', 'Tạo, sửa, khóa tài khoản người dùng'),
            ('Cấp quyền truy cập', 'Phân quyền truy cập hệ thống, dữ liệu'),
            ('Bảo trì hệ thống', 'Bảo trì, cập nhật, sửa chữa hệ thống'),
            ('Đào tạo', 'Đào tạo sử dụng hệ thống, công cụ')";
        $pdo->exec($insertSampleData);
    }
} catch (PDOException $e) {
    // Ignore table creation errors
}

// Lấy danh sách case nội bộ từ database theo phân quyền
$cases = [];
try {
    // Nếu có quyền xem tất cả cases (admin, it, it_leader)
    if (canViewAllInternalCases()) {
        $sql = "SELECT 
                    ic.id,
                    ic.case_number,
                    ic.case_type,
                    ic.priority,
                    ic.issue_title,
                    ic.issue_description,
                    ic.status,
                    ic.created_at,
                    ic.start_date,
                    ic.due_date,
                    requester.fullname as requester_name,
                    handler.fullname as handler_name
                FROM internal_cases ic
                LEFT JOIN staffs requester ON ic.requester_id = requester.id
                LEFT JOIN staffs handler ON ic.handler_id = handler.id
                ORDER BY ic.created_at ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    } else {
        // Chỉ xem cases của chính mình
        $sql = "SELECT 
                    ic.id,
                    ic.case_number,
                    ic.case_type,
                    ic.priority,
                    ic.issue_title,
                    ic.issue_description,
                    ic.status,
                    ic.created_at,
                    ic.start_date,
                    ic.due_date,
                    requester.fullname as requester_name,
                    handler.fullname as handler_name
                FROM internal_cases ic
                LEFT JOIN staffs requester ON ic.requester_id = requester.id
                LEFT JOIN staffs handler ON ic.handler_id = handler.id
                WHERE ic.requester_id = ?
                ORDER BY ic.created_at ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$current_user['id']]);
    }
    $cases = $stmt->fetchAll();
} catch (PDOException $e) {
    // Bảng có thể chưa tồn tại, sẽ tạo sau
    $cases = [];
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
    <title>Case Nội Bộ - IT Services Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo filemtime('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="assets/css/alert.css?v=<?php echo filemtime('assets/css/alert.css'); ?>">
    
    <!-- SheetJS for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    
    <!-- Tooltip CSS -->
    <style>
        .tooltip-icon {
            display: inline-block;
            width: 18px;
            height: 18px;
            background: #17a2b8;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 18px;
            font-size: 10px;
            margin-left: 5px;
            cursor: help;
            position: relative;
            vertical-align: top;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .tooltip-icon:hover {
            background: #138496;
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .custom-tooltip {
            position: absolute;
            z-index: 1000;
            background: #d1ecf1;
            color: #0c5460;
            padding: 10px 12px;
            border-radius: 8px;
            font-size: 12px;
            line-height: 1.4;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            border: 1px solid #bee5eb;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
            pointer-events: none;
            white-space: nowrap;
            left: 25px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: 500;
            max-width: 250px;
        }
        
        .custom-tooltip.multiline {
            white-space: normal;
            width: 280px;
            max-width: 280px;
            min-width: 280px;
        }
        
        .tooltip-content {
            text-align: left;
            line-height: 1.6;
        }
        
        .tooltip-content strong {
            color: #0c5460;
            font-weight: 600;
        }
        
        .custom-tooltip.show {
            opacity: 1;
            visibility: visible;
        }
        
        .custom-tooltip::after {
            content: '';
            position: absolute;
            left: -6px;
            top: 50%;
            transform: translateY(-50%);
            width: 0;
            height: 0;
            border-top: 6px solid transparent;
            border-bottom: 6px solid transparent;
            border-right: 6px solid #d1ecf1;
        }
        
        /* CSS cho đường kẽ bảng case nội bộ */
        .internal-cases-table {
            border-collapse: collapse;
        }
        
        .internal-cases-table th,
        .internal-cases-table td {
            border: 1px solid #dee2e6;
            vertical-align: middle;
            padding: 12px 8px;
        }
        
        .internal-cases-table th {
            background-color: #9ecad6;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495068;
        }
        
        .internal-cases-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .internal-cases-table tbody tr:hover td {
            border-color: #adb5bd;
        }
        
        /* Table alignment styles */
        .internal-cases-table thead th {
            text-align: center;
            vertical-align: middle;
            border-right: 2px solid #dee2e6;
        }
        
        .internal-cases-table tbody td {
            text-align: center;
            vertical-align: middle;
            border-right: 1px solid #dee2e6;
        }
        
        /* Tăng độ dày đường kẽ cho cột đầu tiên và cuối cùng */
        .internal-cases-table th:first-child,
        .internal-cases-table td:first-child {
            border-left: 2px solid #dee2e6;
        }
        
        .internal-cases-table th:last-child,
        .internal-cases-table td:last-child {
            border-right: 2px solid #dee2e6;
        }
        
        /* Date validation styles */
        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
        }
        
        .form-control.is-invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        /* Delete confirmation modal styles */
        .modal-header.bg-danger {
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        
        /* Action buttons hover effects */
        .action-buttons .btn {
            transition: all 0.2s ease;
        }
        
        .action-buttons .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        
        .action-buttons .btn-outline-danger:hover {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
        }
        
        /* Spinner animation */
        .fa-spin {
            animation: fa-spin 1s infinite linear;
        }
        
        @keyframes fa-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Placeholder option styling - chỉ cho text placeholder như "Chọn nhân sự", "Chọn loại case" */
        .placeholder-option {
            color: #6c757d !important;
            font-style: italic !important;
            opacity: 0.7 !important;
        }
        
        /* Special styling for empty value options */
        select.form-select option[value=""] {
            color: #6c757d;
            font-style: italic;
            opacity: 0.8;
        }
        
        /* Responsive filter styling */
        @media (max-width: 1200px) {
            .page-header .d-flex.gap-2 {
                flex-wrap: wrap;
            }
            .page-header .d-flex.gap-2 .form-select,
            .page-header .d-flex.gap-2 .btn {
                margin-bottom: 0.5rem;
            }
        }
        
        @media (max-width: 768px) {
            .page-header .d-flex.gap-2 {
                flex-direction: column;
            }
            .page-header .d-flex.gap-2 .form-select,
            .page-header .d-flex.gap-2 .btn {
                width: 100% !important;
                margin-bottom: 0.5rem;
            }
        }
        
        /* Active filter styling */
        .form-select:not([value=""]) {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        
        /* Filter button hover effect */
        #clearFiltersBtn:hover {
            background-color: #6c757d;
            border-color: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <?php 
    // Include header chung
    include 'includes/header.php'; 
    ?>
    
    <!-- Flash messages will be shown via JavaScript alert system -->
    
    <!-- ===== MAIN CONTENT ===== -->
    <main class="main-content">
        <div class="container-fluid px-4 py-4">
            
            <!-- Page Header -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h1 class="page-title mb-0">
                            <i class="fas fa-building me-3 text-primary"></i>
                            Case Nội Bộ
                        </h1>
                        <p class="text-muted mb-0">Quản lý các vấn đề và yêu cầu hỗ trợ nội bộ</p>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary" id="createCaseBtn" data-bs-toggle="modal" data-bs-target="#createCaseModal">
                            <i class="fas fa-plus me-2"></i>
                            Tạo Case nội bộ
                        </button>
                    </div>
                </div>
                <!-- Filter Row -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <select class="form-select form-select-sm" id="requesterFilter" style="width: 180px;">
                                <option value="">Tất cả người yêu cầu</option>
                            </select>
                            <select class="form-select form-select-sm" id="handlerFilter" style="width: 180px;">
                                <option value="">Tất cả người xử lý</option>
                            </select>
                            <select class="form-select form-select-sm" id="caseTypeFilter" style="width: 180px;">
                                <option value="">Tất cả loại case</option>
                            </select>
                            <select class="form-select form-select-sm" id="statusFilter" style="width: 150px;">
                                <option value="">Tất cả trạng thái</option>
                                <option value="pending">Tiếp nhận</option>
                                <option value="in_progress">Đang xử lý</option>
                                <option value="completed">Hoàn thành</option>
                                <option value="cancelled">Huỷ</option>
                            </select>
                            <div class="d-flex gap-1 align-items-center">
                                <label class="form-label mb-0 me-1" style="font-size: 0.875rem; color: #6c757d;">Từ:</label>
                                <input type="date" class="form-control form-control-sm" id="dateFromFilter" style="width: 140px;">
                            </div>
                            <div class="d-flex gap-1 align-items-center">
                                <label class="form-label mb-0 me-1" style="font-size: 0.875rem; color: #6c757d;">Đến:</label>
                                <input type="date" class="form-control form-control-sm" id="dateToFilter" style="width: 140px;">
                            </div>
                            <button class="btn btn-outline-secondary btn-sm" id="clearFiltersBtn" title="Xóa bộ lọc">
                                <i class="fas fa-times me-1"></i>
                                Xóa lọc
                            </button>
                            <button class="btn btn-success btn-sm" id="exportExcelBtn" title="Xuất Excel">
                                <i class="fas fa-file-excel me-1"></i>
                                Xuất Excel
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Cases Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-list me-2"></i>
                                        Danh sách Case nội bộ
                                    </h5>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <!-- Filter results info -->
                            <div class="px-3 py-2 border-bottom bg-light">
                                <small class="text-muted">
                                    <span id="filterResultsInfo">
                                        Hiển thị tất cả <?php echo count($cases); ?> case
                                    </span>
                                </small>
                            </div>
                            <?php if (empty($cases)): ?>
                                <!-- Empty State -->
                                <div class="text-center py-5">
                                    <div class="mb-4">
                                        <i class="fas fa-inbox fa-5x text-muted opacity-50"></i>
                                    </div>
                                    <h4 class="text-muted mb-3">Chưa có case nội bộ nào</h4>
                                    <p class="text-muted mb-4">Bắt đầu bằng cách tạo case nội bộ đầu tiên của bạn</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCaseModal">
                                        <i class="fas fa-plus me-2"></i>
                                        Tạo Case đầu tiên
                                    </button>
                                </div>
                            <?php else: ?>
                                <!-- Cases Table -->
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 internal-cases-table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col">STT</th>
                                                <th scope="col">Số case</th>
                                                <th scope="col">Người yêu cầu</th>
                                                <th scope="col">Người xử lý</th>
                                                <th scope="col">Loại case</th>
                                                <th scope="col">Vụ việc hỗ trợ</th>
                                                <th scope="col">Ngày tiếp nhận</th>
                                                <th scope="col">Ngày hoàn thành</th>
                                                <th scope="col">Trạng thái</th>
                                                <th scope="col">Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($cases as $index => $case): ?>
                                                <tr data-case-id="<?php echo $case['id']; ?>" 
                                                    data-status="<?php echo htmlspecialchars($case['status']); ?>" 
                                                    data-start-date="<?php echo htmlspecialchars($case['start_date']); ?>"
                                                    data-requester="<?php echo htmlspecialchars($case['requester_name'] ?? ''); ?>"
                                                    data-handler="<?php echo htmlspecialchars($case['handler_name'] ?? ''); ?>"
                                                    data-case-type="<?php echo htmlspecialchars($case['case_type'] ?? ''); ?>">
                                                    <td class="text-center">
                                                        <span class="text-muted"><?php echo $index + 1; ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="case-number">
                                                            <?php echo htmlspecialchars($case['case_number']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="text-truncate" 
                                                             title="<?php echo htmlspecialchars($case['requester_name'] ?? 'N/A'); ?>">
                                                            <?php echo htmlspecialchars($case['requester_name'] ?? 'N/A'); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="text-truncate" 
                                                             title="<?php echo htmlspecialchars($case['handler_name'] ?? 'Chưa phân công'); ?>">
                                                            <?php echo htmlspecialchars($case['handler_name'] ?? 'Chưa phân công'); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="case-priority priority-<?php echo strtolower($case['priority'] ?? 'normal'); ?>">
                                                            <?php echo htmlspecialchars($case['case_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="case-title" 
                                                             title="<?php echo htmlspecialchars($case['issue_title']); ?>">
                                                            <?php echo htmlspecialchars($case['issue_title']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if ($case['start_date']): ?>
                                                            <span class="case-date">
                                                                <?php echo date('d/m/Y', strtotime($case['start_date'])); ?><br>
                                                                <small><?php echo date('H:i', strtotime($case['start_date'])); ?></small>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($case['due_date']): ?>
                                                            <span class="case-date">
                                                                <?php echo date('d/m/Y', strtotime($case['due_date'])); ?><br>
                                                                <small><?php echo date('H:i', strtotime($case['due_date'])); ?></small>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $statusText = '';
                                                        switch ($case['status']) {
                                                            case 'pending':
                                                                $statusText = 'Tiếp nhận';
                                                                break;
                                                            case 'in_progress':
                                                                $statusText = 'Đang xử lý';
                                                                break;
                                                            case 'completed':
                                                                $statusText = 'Hoàn thành';
                                                                break;
                                                            case 'cancelled':
                                                                $statusText = 'Huỷ';
                                                                break;
                                                            default:
                                                                $statusText = 'Không xác định';
                                                        }
                                                        ?>
                                                        <span class="case-status status-<?php echo $case['status']; ?>">
                                                            <?php echo $statusText; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <button type="button" class="btn btn-sm btn-outline-primary btn-view-case" 
                                                                    title="Xem chi tiết" data-id="<?php echo $case['id']; ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <?php if (canEditInternalCase()): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                                    title="Chỉnh sửa"
                                                                    onclick="editCase(<?php echo $case['id']; ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>

                                                            <?php endif; ?>
                                                            <?php if (canDeleteInternalCase()): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                    title="Xóa"
                                                                    onclick="deleteCase(<?php echo $case['id']; ?>)">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <div class="card-footer">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <small class="text-muted">
                                                Hiển thị <?php echo count($cases); ?> kết quả
                                            </small>
                                        </div>
                                        <div class="col-auto">
                                            <nav aria-label="Cases pagination">
                                                <ul class="pagination pagination-sm mb-0">
                                                    <li class="page-item disabled">
                                                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Trước</a>
                                                    </li>
                                                    <li class="page-item active">
                                                        <a class="page-link" href="#">1</a>
                                                    </li>
                                                    <li class="page-item disabled">
                                                        <a class="page-link" href="#">Sau</a>
                                                    </li>
                                                </ul>
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </main>
    
    <!-- Modal Tạo Case Nội Bộ -->
    <div class="modal fade" id="createCaseModal" tabindex="-1" aria-labelledby="createCaseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <form id="createCaseForm" autocomplete="off">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createCaseModalLabel">
                            <i class="fas fa-plus-circle me-2"></i>Tạo Case nội bộ mới
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" tabindex="-1"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Cột trái -->
                            <div class="col-md-6">
                                <!-- ID -->
                                <!-- Trường ID đã bị ẩn theo yêu cầu -->
                                
                                <!-- Số case -->
                                <!-- Trường Số case đã bị ẩn theo yêu cầu -->
                                
                                <!-- Người yêu cầu -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Người yêu cầu <span class="text-danger">*</span></label>
                                        </div>
                                        <div class="col-8">
                                            <select class="form-select" id="requesterId" name="requester_id" required>
                                                <option value="" class="placeholder-option">Chọn nhân sự</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Chức danh -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Chức danh</label>
                                        </div>
                                        <div class="col-8">
                                            <input type="text" class="form-control" id="requesterPosition" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Người chuyển case -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Người chuyển case</label>
                                        </div>
                                        <div class="col-8">
                                            <input type="text" class="form-control" id="transferredBy" name="transferred_by" value="Trần Nguyễn Anh Khoa" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Người xử lý -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Người xử lý <span class="text-danger">*</span></label>
                                        </div>
                                        <div class="col-8">
                                            <select class="form-select" id="handlerId" name="handler_id" required>
                                                <option value="" class="placeholder-option">Chọn nhân sự</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Loại case -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Loại case <span class="text-danger">*</span></label>
                                        </div>
                                        <div class="col-8">
                                            <select class="form-select" id="caseType" name="case_type" required>
                                                <option value="" class="placeholder-option">Chọn loại case</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Hình thức -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">
                                                Hình thức
                                                <span class="tooltip-icon" data-tooltip="<div class='tooltip-content'>• <strong>Onsite:</strong> Bạn di chuyển đến site khách hàng để hỗ trợ<br>• <strong>Offsite:</strong> Bạn hỗ trợ khách hàng tại văn phòng, và khách hàng cũng đang ở đó<br>• <strong>Remote:</strong> Hỗ trợ khách hàng từ xa</div>">
                                                    <i class="fas fa-info"></i>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="col-8">
                                            <select class="form-select" id="priority" name="priority">
                                                <option value="onsite" selected>Onsite</option>
                                                <option value="offsite">Offsite</option>
                                                <option value="remote">Remote</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                

                            </div>
                            
                            <!-- Cột phải -->
                            <div class="col-md-6">
                                <!-- Vụ việc -->
                                <div class="mb-3">
                                    <div class="row align-items-top">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">
                                                Vụ việc <span class="text-danger">*</span>
                                                <span class="tooltip-icon" data-tooltip="Mô tả ngắn gọn">
                                                    <i class="fas fa-info"></i>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="col-8">
                                            <input type="text" class="form-control" id="issueTitle" name="issue_title" 
                                                   placeholder="Nhập tiêu đề vụ việc" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Mô tả chi tiết -->
                                <div class="mb-3">
                                    <div class="row align-items-top">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">
                                                Mô tả chi tiết <span class="text-danger">*</span>
                                                <span class="tooltip-icon" data-tooltip="Mô tả chi tiết">
                                                    <i class="fas fa-info"></i>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="col-8">
                                            <textarea class="form-control" id="issueDescription" name="issue_description" 
                                                      rows="3" placeholder="Mô tả chi tiết vấn đề..." required></textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Bắt đầu -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Bắt đầu <span class="text-danger">*</span></label>
                                        </div>
                                        <div class="col-8">
                                            <input type="datetime-local" class="form-control" id="startDate" name="start_date" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Kết thúc -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Kết thúc</label>
                                        </div>
                                        <div class="col-8">
                                            <input type="datetime-local" class="form-control" id="dueDate" name="due_date">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Trạng thái -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Trạng thái <span class="text-danger">*</span></label>
                                        </div>
                                        <div class="col-8">
                                            <select class="form-select" id="status" name="status" required>
                                                <option value="pending" selected>Tiếp nhận</option>
                                                <option value="in_progress">Đang xử lý</option>
                                                <option value="completed">Hoàn thành</option>
                                                <option value="cancelled">Huỷ</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Ghi chú -->
                                <div class="mb-3">
                                    <div class="row align-items-top">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Ghi chú</label>
                                        </div>
                                        <div class="col-8">
                                            <textarea class="form-control" id="notes" name="notes" 
                                                      rows="4" placeholder="Ghi chú thêm (nếu có)..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Alerts moved to toast system -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-arrow-left me-2"></i>Trở về
                        </button>
                        <button type="submit" class="btn" style="background-color: #0d6efd; border-color: #0d6efd; color: white;">
                            <i class="fas fa-plus me-2"></i>Tạo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Chỉnh sửa Case Nội Bộ -->
            <div class="modal fade" id="editCaseModal" tabindex="-1" aria-labelledby="editCaseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <form id="editCaseForm" autocomplete="off">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editCaseModalLabel">
                            <i class="fas fa-edit me-2"></i>Chỉnh sửa Case nội bộ
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" tabindex="-1"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Cột trái -->
                            <div class="col-md-6">
                                <!-- Số case (read-only) -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Số case</label>
                                        </div>
                                        <div class="col-8">
                                            <input type="text" class="form-control" id="editCaseNumber" readonly>
                                            <input type="hidden" id="editCaseId">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Người yêu cầu -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Người yêu cầu <span class="text-danger">*</span></label>
                                        </div>
                                        <div class="col-8">
                                            <?php if (isAdmin()): ?>
                                            <select class="form-select" id="editRequesterId" name="requester_id" required>
                                                <option value="" class="placeholder-option">Chọn nhân sự</option>
                                            </select>
                                            <?php else: ?>
                                            <input type="text" class="form-control" id="editRequesterName" readonly>
                                            <input type="hidden" id="editRequesterId" name="requester_id">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Chức danh -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Chức danh</label>
                                        </div>
                                        <div class="col-8">
                                            <input type="text" class="form-control" id="editRequesterPosition" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Người chuyển case -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Người chuyển case</label>
                                        </div>
                                        <div class="col-8">
                                            <input type="text" class="form-control" id="editTransferredBy" 
                                                   value="Trần Nguyễn Anh Khoa" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Người xử lý -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Người xử lý <span class="text-danger">*</span></label>
                                        </div>
                                        <div class="col-8">
                                            <?php if (isAdmin()): ?>
                                            <select class="form-select" id="editHandlerId" name="handler_id" required>
                                                <option value="" class="placeholder-option">Chọn nhân sự</option>
                                            </select>
                                            <?php else: ?>
                                            <input type="text" class="form-control" id="editHandlerName" readonly>
                                            <input type="hidden" id="editHandlerId" name="handler_id">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Loại case -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Loại case <span class="text-danger">*</span></label>
                                        </div>
                                        <div class="col-8">
                                            <?php if (isAdmin()): ?>
                                            <select class="form-select" id="editCaseType" name="case_type" required>
                                                <option value="" class="placeholder-option">Chọn loại case</option>
                                            </select>
                                            <?php else: ?>
                                            <input type="text" class="form-control" id="editCaseTypeName" readonly>
                                            <input type="hidden" id="editCaseType" name="case_type">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Hình thức -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">
                                                Hình thức
                                                <span class="tooltip-icon" data-tooltip="<div class='tooltip-content'>• <strong>Onsite:</strong> Bạn di chuyển đến site khách hàng để hỗ trợ<br>• <strong>Offsite:</strong> Bạn hỗ trợ khách hàng tại văn phòng, và khách hàng cũng đang ở đó<br>• <strong>Remote:</strong> Hỗ trợ khách hàng từ xa</div>">
                                                    <i class="fas fa-info"></i>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="col-8">
                                            <?php if (isAdmin()): ?>
                                            <select class="form-select" id="editPriority" name="priority">
                                                <option value="onsite">Onsite</option>
                                                <option value="offsite">Offsite</option>
                                                <option value="remote">Remote</option>
                                            </select>
                                            <?php else: ?>
                                            <input type="text" class="form-control" id="editPriorityName" readonly>
                                            <input type="hidden" id="editPriority" name="priority">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Cột phải -->
                            <div class="col-md-6">
                                <!-- Vụ việc -->
                                <div class="mb-3">
                                    <div class="row align-items-top">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">
                                                Vụ việc <span class="text-danger">*</span>
                                                <span class="tooltip-icon" data-tooltip="Mô tả ngắn gọn">
                                                    <i class="fas fa-info"></i>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="col-8">
                                            <?php if (isAdmin()): ?>
                                            <input type="text" class="form-control" id="editIssueTitle" name="issue_title" 
                                                   placeholder="Nhập tiêu đề vụ việc" required>
                                            <?php else: ?>
                                            <input type="text" class="form-control" id="editIssueTitle" readonly>
                                            <input type="hidden" name="issue_title">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Mô tả chi tiết -->
                                <div class="mb-3">
                                    <div class="row align-items-top">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">
                                                Mô tả chi tiết <span class="text-danger">*</span>
                                                <span class="tooltip-icon" data-tooltip="Mô tả chi tiết">
                                                    <i class="fas fa-info"></i>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="col-8">
                                            <?php if (isAdmin()): ?>
                                            <textarea class="form-control" id="editIssueDescription" name="issue_description" 
                                                      rows="3" placeholder="Mô tả chi tiết vấn đề..." required></textarea>
                                            <?php else: ?>
                                            <textarea class="form-control" id="editIssueDescription" rows="3" readonly></textarea>
                                            <input type="hidden" name="issue_description">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Bắt đầu -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Bắt đầu</label>
                                        </div>
                                        <div class="col-8">
                                            <?php if (isAdmin()): ?>
                                            <input type="datetime-local" class="form-control" id="editStartDate" name="start_date">
                                            <?php else: ?>
                                            <input type="text" class="form-control" id="editStartDateDisplay" readonly>
                                            <input type="hidden" id="editStartDate" name="start_date">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Kết thúc -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Kết thúc</label>
                                        </div>
                                        <div class="col-8">
                                            <input type="datetime-local" class="form-control" id="editDueDate" name="due_date">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Trạng thái -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Trạng thái <span class="text-danger">*</span></label>
                                        </div>
                                        <div class="col-8">
                                            <select class="form-select" id="editStatus" name="status" required>
                                                <option value="pending">Tiếp nhận</option>
                                                <option value="in_progress">Đang xử lý</option>
                                                <option value="completed">Hoàn thành</option>
                                                <option value="cancelled">Huỷ</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Ghi chú -->
                                <div class="mb-3">
                                    <div class="row align-items-top">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Ghi chú</label>
                                        </div>
                                        <div class="col-8">
                                            <textarea class="form-control" id="editNotes" name="notes" 
                                                      rows="4" placeholder="Ghi chú thêm (nếu có)..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Hủy
                        </button>
                        <button type="submit" class="btn" style="background-color: #198754; border-color: #198754; color: white;">
                            <i class="fas fa-save me-2"></i>Cập nhật
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- ===== MODAL XEM CHI TIẾT CASE ===== -->
            <div class="modal fade" id="viewCaseModal" tabindex="-1" aria-labelledby="viewCaseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <form id="viewCaseForm" autocomplete="off">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewCaseModalLabel">
                            <i class="fas fa-eye me-2"></i>Xem chi tiết case
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" tabindex="-1"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Copy nội dung form tạo case, tất cả trường readonly/disabled, id có tiền tố view -->
                        <div class="row">
                            <!-- Cột trái -->
                            <div class="col-md-6">
                            <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Số case</label>
                                        </div>
                                        <div class="col-8">
                                            <input type="text" class="form-control" id="viewCaseNumber" readonly>
                                        </div>
                                    </div>
                                </div>
                                <!-- Người yêu cầu -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Người yêu cầu <span class="text-danger">*</span></label>
                                        </div>
                                        <div class="col-8">
                                            <input type="text" class="form-control" id="viewRequesterName" readonly>
                                        </div>
                                    </div>
                                </div>
                                <!-- Chức danh -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Chức danh</label>
                                        </div>
                                        <div class="col-8">
                                            <input type="text" class="form-control" id="viewRequesterPosition" readonly>
                                        </div>
                                    </div>
                                </div>
                                <!-- Người chuyển case -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Người chuyển case</label>
                                        </div>
                                        <div class="col-8">
                                            <input type="text" class="form-control" id="viewTransferredBy" readonly>
                                        </div>
                                    </div>
                                </div>
                                <!-- Người xử lý -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Người xử lý <span class="text-danger">*</span></label>
                                        </div>
                                        <div class="col-8">
                                            <input type="text" class="form-control" id="viewHandlerName" readonly>
                                        </div>
                                    </div>
                                </div>
                                <!-- Loại case -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Loại case <span class="text-danger">*</span></label>
                                        </div>
                                        <div class="col-8">
                                            <input type="text" class="form-control" id="viewCaseTypeName" readonly>
                                        </div>
                                    </div>
                                </div>
                                <!-- Hình thức -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">
                                                Hình thức
                                                <span class="tooltip-icon" data-tooltip="<div class='tooltip-content'>• <strong>Onsite:</strong> Bạn di chuyển đến site khách hàng để hỗ trợ<br>• <strong>Offsite:</strong> Bạn hỗ trợ khách hàng tại văn phòng, và khách hàng cũng đang ở đó<br>• <strong>Remote:</strong> Hỗ trợ khách hàng từ xa</div>">
                                                    <i class="fas fa-info"></i>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="col-8">
                                            <input type="text" class="form-control" id="viewPriorityName" readonly>
                                        </div>
                                    </div>
                                </div>
                                <!-- Thêm trường Số case vào viewCaseModal -->
                                
                            </div>
                            <!-- Cột phải -->
                            <div class="col-md-6">
                                <!-- Vụ việc -->
                                <div class="mb-3">
                                    <div class="row align-items-top">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">
                                                Vụ việc <span class="text-danger">*</span>
                                                <span class="tooltip-icon" data-tooltip="Mô tả ngắn gọn">
                                                    <i class="fas fa-info"></i>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="col-8">
                                            <input type="text" class="form-control" id="viewIssueTitle" name="issue_title" readonly>
                                        </div>
                                    </div>
                                </div>
                                <!-- Mô tả chi tiết -->
                                <div class="mb-3">
                                    <div class="row align-items-top">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">
                                                Mô tả chi tiết <span class="text-danger">*</span>
                                                <span class="tooltip-icon" data-tooltip="Mô tả chi tiết">
                                                    <i class="fas fa-info"></i>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="col-8">
                                            <textarea class="form-control" id="viewIssueDescription" name="issue_description" rows="3" readonly></textarea>
                                        </div>
                                    </div>
                                </div>
                                <!-- Bắt đầu -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Bắt đầu</label>
                                        </div>
                                        <div class="col-8">
                                            <input type="text" class="form-control" id="viewStartDate" name="start_date" readonly>
                                        </div>
                                    </div>
                                </div>
                                <!-- Kết thúc -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Kết thúc</label>
                                        </div>
                                        <div class="col-8">
                                            <input type="text" class="form-control" id="viewDueDate" name="due_date" readonly>
                                        </div>
                                    </div>
                                </div>
                                <!-- Trạng thái -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Trạng thái <span class="text-danger">*</span></label>
                                        </div>
                                        <div class="col-8">
                                            <input type="text" class="form-control" id="viewStatusName" readonly>
                                        </div>
                                    </div>
                                </div>
                                <!-- Ghi chú -->
                                <div class="mb-3">
                                    <div class="row align-items-top">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Ghi chú</label>
                                        </div>
                                        <div class="col-8">
                                            <textarea class="form-control" id="viewNotes" name="notes" rows="4" readonly></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        <?php if (canEditInternalCase()): ?>
                        <button type="button" class="btn btn-primary" id="btnViewToEditCase"><i class="fas fa-edit me-2"></i>Chỉnh sửa</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- jQuery (load trước) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Alert System -->
    <script src="assets/js/alert.js?v=<?php echo filemtime('assets/js/alert.js'); ?>"></script>
    
    <script>
    // Global function to re-index STT column for visible rows
    window.reindexSTT = function() {
        var visibleIndex = 1;
        $('tbody tr:visible').each(function() {
            var firstCell = $(this).find('td:first');
            firstCell.text(visibleIndex);
            visibleIndex++;
        });
    }
    
    // Global function to show empty state when table is empty
    window.showEmptyState = function() {
        // Hide the table container
        $('.table-responsive').hide();
        
        // Remove any existing empty state messages first
        $('#emptyStateMessage').remove();
        
        // Check if this is due to filtering
        var hasActiveFilters = $('#requesterFilter').val() || $('#handlerFilter').val() || 
                              $('#caseTypeFilter').val() || $('#statusFilter').val() || 
                              ($('#monthFilter').val() && $('#monthFilter').val() !== 'current_month');
        
        var emptyStateHtml;
        
        if (hasActiveFilters) {
            // Show filter-specific empty state
            emptyStateHtml = '<div class="text-center py-5" id="emptyStateMessage">' +
                '<div class="mb-4">' +
                    '<i class="fas fa-search fa-5x text-muted opacity-50"></i>' +
                '</div>' +
                '<h4 class="text-muted mb-3">Không tìm thấy case nào</h4>' +
                '<p class="text-muted mb-4">Thử thay đổi bộ lọc hoặc tạo case mới</p>' +
                '<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCaseModal">' +
                    '<i class="fas fa-plus me-2"></i>' +
                    'Tạo Case mới' +
                '</button>' +
            '</div>';
        } else {
            // Show general empty state
            emptyStateHtml = '<div class="text-center py-5" id="emptyStateMessage">' +
                '<div class="mb-4">' +
                    '<i class="fas fa-inbox fa-5x text-muted opacity-50"></i>' +
                '</div>' +
                '<h4 class="text-muted mb-3">Chưa có case nội bộ nào</h4>' +
                '<p class="text-muted mb-4">Bắt đầu bằng cách tạo case nội bộ đầu tiên của bạn</p>' +
                '<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCaseModal">' +
                    '<i class="fas fa-plus me-2"></i>' +
                    'Tạo Case đầu tiên' +
                '</button>' +
            '</div>';
        }
        
        // Insert empty state after the table container
        $('.table-responsive').after(emptyStateHtml);
    }
    
    // Global function to hide empty state when table has data
    window.hideEmptyState = function() {
        // Show the table container
        $('.table-responsive').show();
        
        // Remove empty state message if it exists
        $('#emptyStateMessage').remove();
    }
    
    $(document).ready(function() {
        // Flash messages
        <?php if (!empty($flash_messages)): ?>
            <?php foreach ($flash_messages as $message): ?>
                showAlert('<?php echo addslashes($message['message']); ?>', '<?php echo $message['type']; ?>');
            <?php endforeach; ?>
        <?php endif; ?>
        
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
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
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
        
        // ===== TOOLTIP FUNCTIONALITY ===== //
        
        // Tooltip for form labels
        $(document).on('mouseenter', '.tooltip-icon', function() {
            const tooltip = $(this).data('tooltip');
            if (tooltip) {
                // Remove existing tooltips
                $('.custom-tooltip').remove();
                
                // Create new tooltip
                const tooltipEl = $('<div class="custom-tooltip">' + tooltip + '</div>');
                
                // Check if tooltip contains HTML (multiline)
                if (tooltip.includes('<br>') || tooltip.includes('<div')) {
                    tooltipEl.addClass('multiline');
                }
                
                $(this).append(tooltipEl);
                
                // Show tooltip
                setTimeout(function() {
                    tooltipEl.addClass('show');
                }, 10);
            }
        });
        
        $(document).on('mouseleave', '.tooltip-icon', function() {
            const tooltipEl = $(this).find('.custom-tooltip');
            tooltipEl.removeClass('show');
            setTimeout(function() {
                tooltipEl.remove();
            }, 300);
        });
        
        // ===== INTERNAL CASES FUNCTIONALITY ===== //
        
        // Load staff list khi modal được mở
        $('#createCaseModal').on('show.bs.modal', function() {
            loadStaffList();
            loadCaseTypes();
            generateCaseNumber();
            
            // Tự động điền ngày giờ hiện tại vào trường ngày bắt đầu
            var now = new Date();
            var year = now.getFullYear();
            var month = String(now.getMonth() + 1).padStart(2, '0');
            var day = String(now.getDate()).padStart(2, '0');
            var hours = String(now.getHours()).padStart(2, '0');
            var minutes = String(now.getMinutes()).padStart(2, '0');
            var currentDateTime = year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
            $('#startDate').val(currentDateTime);
        });
        
        // Handle form submission
        $('#createCaseForm').on('submit', function(e) {
            e.preventDefault();
            createCase();
        });
        
        // ===== EDIT CASE MODAL HANDLERS ===== //
        
        // Handle edit form submission
        $('#editCaseForm').on('submit', function(e) {
            e.preventDefault();
            updateCase();
        });
        
        // Auto-fill chức danh khi chọn người yêu cầu (edit modal)
        $('#editRequesterId').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            var position = selectedOption.data('position');
            $('#editRequesterPosition').val(position || '');
        });
        
        // Date validation for edit modal
        $('#editStartDate, #editDueDate').on('change blur', function() {
            validateEditDateRange();
        });
        

        
        // Auto-fill chức danh khi chọn người yêu cầu
        $('#requesterId').on('change', function() {
            var selectedOption = $(this).find('option:selected');
            var position = selectedOption.data('position');
            $('#requesterPosition').val(position || '');
        });
        
        // Validate date range
        function validateDateRange() {
            var startDate = $('#startDate').val();
            var dueDate = $('#dueDate').val();
            
            if (startDate && dueDate) {
                var start = new Date(startDate);
                var end = new Date(dueDate);
                
                if (end <= start) {
                    $('#dueDate').addClass('is-invalid');
                    if (!$('#dateError').length) {
                        $('#dueDate').after('<div id="dateError" class="invalid-feedback">Ngày kết thúc phải lớn hơn ngày bắt đầu</div>');
                    }
                    return false;
                } else {
                    $('#dueDate').removeClass('is-invalid');
                    $('#dateError').remove();
                    return true;
                }
            }
            $('#dueDate').removeClass('is-invalid');
            $('#dateError').remove();
            return true;
        }
        
        // Date validation events
        $('#startDate, #dueDate').on('change blur', function() {
            validateDateRange();
        });
        
        // Load filter data on page load
        loadFilterData();
        
        // Debounce function for better performance
        function debounce(func, wait) {
            var timeout;
            return function executedFunction() {
                var context = this;
                var args = arguments;
                var later = function() {
                    timeout = null;
                    func.apply(context, args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
        // Debounced filter function
        var debouncedApplyFilters = debounce(applyFilters, 300);
        
        // Status filter
        $('#statusFilter').on('change', function() {
            debouncedApplyFilters();
        });
        
        // Date filters
        $('#dateFromFilter, #dateToFilter').on('change', function() {
            debouncedApplyFilters();
        });
        
        // Requester filter
        $('#requesterFilter').on('change', function() {
            debouncedApplyFilters();
        });
        
        // Handler filter
        $('#handlerFilter').on('change', function() {
            debouncedApplyFilters();
        });
        
        // Case type filter
        $('#caseTypeFilter').on('change', function() {
            debouncedApplyFilters();
        });
        
        // Clear filters button
        $('#clearFiltersBtn').on('click', function() {
            $('#requesterFilter').val('');
            $('#handlerFilter').val('');
            $('#caseTypeFilter').val('');
            $('#statusFilter').val('');
            $('#dateFromFilter').val('');
            $('#dateToFilter').val('');
            applyFilters(); // Use immediate filter for clear button
        });
        
        // Apply default filter on page load
        applyFilters();
        
        // Check if table is empty on initial load
        if ($('tbody tr').length === 0) {
            showEmptyState();
        }
        
        // Load filter data function
        function loadFilterData() {
            // Load requester filter data
            $.ajax({
                url: 'api/get_staff_list.php?all=1',
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        var options = '<option value="">Tất cả người yêu cầu</option>';
                        
                        // Add all staff members to the dropdown
                        response.data.forEach(function(staff) {
                            options += '<option value="' + staff.fullname + '">' + staff.fullname + '</option>';
                        });
                        
                        $('#requesterFilter').html(options);
                    }
                }
            });
            
            // Load handler filter data
            $.ajax({
                url: 'api/get_staff_list.php',
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        var options = '<option value="">Tất cả người xử lý</option>';
                        
                        // Add all IT staff members to the dropdown
                        response.data.forEach(function(staff) {
                            options += '<option value="' + staff.fullname + '">' + staff.fullname + '</option>';
                        });
                        
                        $('#handlerFilter').html(options);
                    }
                }
            });
            
            // Load case type filter data
            $.ajax({
                url: 'api/case_types.php?type=internal&action=list',
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        var options = '<option value="">Tất cả loại case</option>';
                        
                        // Add all case types to the dropdown
                        response.data.forEach(function(caseType) {
                            if (caseType.status === 'active') {
                                options += '<option value="' + caseType.name + '">' + caseType.name + '</option>';
                            }
                        });
                        
                        $('#caseTypeFilter').html(options);
                    }
                }
            });
        }
        
        // Combined filter function
        function applyFilters() {
            var selectedStatus = $('#statusFilter').val();
            var selectedDateFrom = $('#dateFromFilter').val();
            var selectedDateTo = $('#dateToFilter').val();
            var selectedRequester = $('#requesterFilter').val();
            var selectedHandler = $('#handlerFilter').val();
            var selectedCaseType = $('#caseTypeFilter').val();
            var visibleRows = 0;
            
            $('tbody tr').each(function() {
                var row = $(this);
                var rowStatus = row.data('status');
                var rowDate = row.data('start-date');
                var rowRequester = row.data('requester');
                var rowHandler = row.data('handler');
                var rowCaseType = row.data('case-type');
                
                var statusMatch = !selectedStatus || selectedStatus === "" || rowStatus === selectedStatus;
                var dateMatch = isDateInRange(rowDate, selectedDateFrom, selectedDateTo);
                var requesterMatch = !selectedRequester || selectedRequester === "" || rowRequester === selectedRequester;
                var handlerMatch = !selectedHandler || selectedHandler === "" || rowHandler === selectedHandler;
                var caseTypeMatch = !selectedCaseType || selectedCaseType === "" || rowCaseType === selectedCaseType;
                
                if (statusMatch && dateMatch && requesterMatch && handlerMatch && caseTypeMatch) {
                    row.show();
                    visibleRows++;
                } else {
                    row.hide();
                }
            });
            
            // Re-index STT column for visible rows
            reindexSTT();
            
            // Check if no rows are visible after filtering
            if (visibleRows === 0) {
                showEmptyState();
            } else {
                hideEmptyState();
            }
        }
        

        
        // Helper function to check if date is in selected range
        function isDateInRange(dateString, dateFrom, dateTo) {
            if (!dateString) return true;
            
            var date = new Date(dateString);
            var fromDate = dateFrom ? new Date(dateFrom) : null;
            var toDate = dateTo ? new Date(dateTo) : null;
            
            // If no date filters are set, show all
            if (!fromDate && !toDate) return true;
            
            // If only from date is set
            if (fromDate && !toDate) {
                return date >= fromDate;
            }
            
            // If only to date is set
            if (!fromDate && toDate) {
                return date <= toDate;
            }
            
            // If both dates are set
            if (fromDate && toDate) {
                return date >= fromDate && date <= toDate;
            }
            
            return true;
        }
        

    });
    
    // Cache cho staff list và case types để tránh gọi API nhiều lần
    var cachedStaffList = null;
    var cachedCaseTypes = null;
    
    // Case management functions
    function editCase(id) {
        $.ajax({
            url: 'api/get_internal_case_details.php?id=' + id,
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    loadEditCaseData(response.data);
                } else {
                    showError(response.error || 'Không thể tải thông tin case');
                }
            },
            error: function(xhr, status, error) {
                showError('Có lỗi xảy ra khi tải thông tin case');
            }
        });
    }
    
    function loadEditCaseData(caseData) {
        // Chỉ load staff list và case types nếu chưa có cache
        var promises = [];
        
        if (!cachedStaffList) {
            promises.push(loadStaffListForEdit());
        }
        
        if (!cachedCaseTypes) {
            promises.push(loadCaseTypesForEdit());
        }
        
        if (promises.length > 0) {
            Promise.all(promises).then(function() {
                populateEditForm(caseData);
                $('#editCaseModal').modal('show');
            }).catch(function(error) {
                showError('Không thể tải dữ liệu cần thiết');
                throw error;
            });
        } else {
            // Nếu đã có cache, populate ngay lập tức
            populateEditForm(caseData);
            $('#editCaseModal').modal('show');
        }
    }
    
    function loadStaffListForEdit() {
        return new Promise(function(resolve, reject) {
            // Load tất cả staff cho requester
            $.ajax({
                url: 'api/get_staff_list.php?all=1',
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        cachedStaffList = response.data;
                        var options = '<option value="">Chọn nhân sự</option>';
                        response.data.forEach(function(staff) {
                            options += '<option value="' + staff.id + '" data-position="' + staff.position + '">' + 
                                      staff.fullname + '</option>';
                        });
                        $('#editRequesterId').html(options);
                        
                        // Load IT staff cho handler
                        $.ajax({
                            url: 'api/get_staff_list.php',
                            type: 'GET',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            success: function(response2) {
                                if (response2.success) {
                                    var options2 = '<option value="">Chọn nhân sự</option>';
                                    response2.data.forEach(function(staff) {
                                        options2 += '<option value="' + staff.id + '" data-position="' + staff.position + '">' + 
                                                  staff.fullname + '</option>';
                                    });
                                    $('#editHandlerId').html(options2);
                                    resolve();
                                } else {
                                    reject(response2.error);
                                }
                            },
                            error: function() {
                                reject('Lỗi khi tải danh sách nhân sự IT');
                            }
                        });
                    } else {
                        reject(response.error);
                    }
                },
                error: function() {
                    reject('Lỗi khi tải danh sách nhân sự');
                }
            });
        });
    }
    
    function loadCaseTypesForEdit() {
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: 'api/case_types.php?type=internal&action=list',
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    if (response.success) {
                        cachedCaseTypes = response.data;
                        var options = '<option value="">Chọn loại case</option>';
                        response.data.forEach(function(caseType) {
                            if (caseType.status === 'active') {
                                options += '<option value="' + caseType.name + '">' + caseType.name + '</option>';
                            }
                        });
                        $('#editCaseType').html(options);
                        resolve();
                    } else {
                        reject(response.message);
                    }
                },
                error: function() {
                    reject('Lỗi khi tải danh sách loại case');
                }
            });
        });
    }
    
    function populateEditForm(caseData) {
        // Basic info
        $('#editCaseId').val(caseData.id);
        $('#editCaseNumber').val(caseData.case_number);
        
        // Check user role to determine what fields can be edited
        var isAdmin = <?php echo isAdmin() ? 'true' : 'false'; ?>;
        
        if (isAdmin) {
            // Admin can edit all fields
            $('#editRequesterId').val(caseData.requester_id);
            $('#editHandlerId').val(caseData.handler_id);
            $('#editCaseType').val(caseData.case_type);
            $('#editPriority').val(caseData.priority || 'onsite');
            $('#editIssueTitle').val(caseData.issue_title);
            $('#editIssueDescription').val(caseData.issue_description);
            
            // Date fields (format cho datetime-local input)
            if (caseData.start_date) {
                var startDate = new Date(caseData.start_date);
                $('#editStartDate').val(formatDateTimeLocal(startDate));
            }
            
            if (caseData.due_date) {
                var dueDate = new Date(caseData.due_date);
                $('#editDueDate').val(formatDateTimeLocal(dueDate));
            }
            
            // Update requester position
            var selectedRequester = $('#editRequesterId option:selected');
            var requesterPosition = selectedRequester.data('position') || '';
            $('#editRequesterPosition').val(requesterPosition);
        } else {
            // Non-admin users can only edit status, due_date, and notes
            // Set readonly fields with display values
            $('#editRequesterName').val(caseData.requester_name || 'N/A');
            $('#editRequesterId').val(caseData.requester_id);
            $('#editHandlerName').val(caseData.handler_name || 'Chưa phân công');
            $('#editHandlerId').val(caseData.handler_id);
            $('#editCaseTypeName').val(caseData.case_type || 'N/A');
            $('#editCaseType').val(caseData.case_type);
            $('#editPriorityName').val(caseData.priority || 'onsite');
            $('#editPriority').val(caseData.priority || 'onsite');
            $('#editIssueTitle').val(caseData.issue_title || 'N/A');
            $('#editIssueDescription').val(caseData.issue_description || 'N/A');
            
            // Display start date as text
            if (caseData.start_date) {
                var startDate = new Date(caseData.start_date);
                $('#editStartDateDisplay').val(formatDateTimeDisplay(startDate));
                $('#editStartDate').val(formatDateTimeLocal(startDate));
            }
        }
        
        // All users can edit these fields
        $('#editStatus').val(caseData.status);
        $('#editNotes').val(caseData.notes || '');
        
        // Due date - all users can edit
        if (caseData.due_date) {
            var dueDate = new Date(caseData.due_date);
            $('#editDueDate').val(formatDateTimeLocal(dueDate));
        }
    }
    
    function formatDateTimeDisplay(date) {
        // Format date for display (DD/MM/YYYY HH:MM)
        var day = String(date.getDate()).padStart(2, '0');
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var year = date.getFullYear();
        var hours = String(date.getHours()).padStart(2, '0');
        var minutes = String(date.getMinutes()).padStart(2, '0');
        
        return day + '/' + month + '/' + year + ' ' + hours + ':' + minutes;
    }
    
    function formatDateTimeLocal(date) {
        // Format date cho datetime-local input (YYYY-MM-DDTHH:MM)
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        var hours = String(date.getHours()).padStart(2, '0');
        var minutes = String(date.getMinutes()).padStart(2, '0');
        
        return year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
    }
    
    function deleteCase(id) {
        // Tạo modal confirm nâng cao
        var modal = $('<div class="modal fade" tabindex="-1">' +
            '<div class="modal-dialog modal-dialog-centered">' +
                '<div class="modal-content">' +
                    '<div class="modal-header bg-danger text-white">' +
                        '<h5 class="modal-title">' +
                            '<i class="fas fa-exclamation-triangle me-2"></i>Xác nhận xóa case' +
                        '</h5>' +
                        '<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>' +
                    '</div>' +
                    '<div class="modal-body">' +
                        '<div class="text-center">' +
                            '<i class="fas fa-trash-alt text-danger mb-3" style="font-size: 3rem;"></i>' +
                            '<h5>Bạn có chắc chắn muốn xóa case này?</h5>' +
                            '<p class="text-muted">Hành động này không thể hoàn tác!</p>' +
                        '</div>' +
                    '</div>' +
                    '<div class="modal-footer">' +
                        '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' +
                            '<i class="fas fa-times me-2"></i>Hủy' +
                        '</button>' +
                        '<button type="button" class="btn btn-danger" id="confirmDeleteBtn">' +
                            '<i class="fas fa-trash me-2"></i>Xóa' +
                        '</button>' +
                    '</div>' +
                '</div>' +
            '</div>' +
        '</div>');
        
        // Thêm modal vào body
        $('body').append(modal);
        modal.modal('show');
        
        // Xử lý khi nhấn nút xóa
        modal.find('#confirmDeleteBtn').on('click', function() {
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Đang xóa...');
            
            $.ajax({
                url: 'api/delete_case.php',
                type: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                contentType: 'application/json',
                data: JSON.stringify({
                    case_id: id
                }),
                success: function(response) {
                    modal.modal('hide');
                    if (response.success) {
                        showSuccess('Xóa case thành công: ' + response.deleted_case.case_number);
                        // Xóa row khỏi bảng thay vì reload trang
                        $('tr[data-case-id="' + id + '"]').fadeOut(300, function() {
                            $(this).remove();
                            // Re-index STT column sau khi xóa
                            reindexSTT();
                            
                            // Kiểm tra nếu bảng trống thì hiển thị empty state
                            if ($('tbody tr').length === 0) {
                                showEmptyState();
                            }
                        });
                    } else {
                        showError(response.error || 'Có lỗi xảy ra khi xóa case');
                    }
                },
                error: function(xhr, status, error) {
                    modal.modal('hide');
                    console.error('Error deleting case:', error);
                    showError('Có lỗi xảy ra khi kết nối server');
                }
            });
        });
        
        // Xóa modal khi đóng
        modal.on('hidden.bs.modal', function() {
            modal.remove();
        });
    }
    

    
    // Helper functions for create case modal
    function loadStaffList() {
        // Lấy toàn bộ nhân sự cho Người yêu cầu
        $.ajax({
            url: 'api/get_staff_list.php?all=1',
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    var options = '<option value="">Chọn nhân sự</option>';
                    response.data.forEach(function(staff) {
                        options += '<option value="' + staff.id + '" data-position="' + staff.position + '">' + 
                                  staff.fullname + '</option>';
                    });
                    $('#requesterId').html(options);
                }
            }
        });
        // Lấy nhân sự IT Dept. cho Người xử lý
        $.ajax({
            url: 'api/get_staff_list.php',
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    var options = '<option value="">Chọn nhân sự</option>';
                    response.data.forEach(function(staff) {
                        options += '<option value="' + staff.id + '" data-position="' + staff.position + '">' + 
                                  staff.fullname + '</option>';
                    });
                    $('#handlerId').html(options);
                }
            }
        });
    }
    
    function loadCaseTypes() {
        $.ajax({
            url: 'api/case_types.php?type=internal&action=list',
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                if (response.success) {
                    var options = '<option value="">Chọn loại case</option>';
                    response.data.forEach(function(caseType) {
                        if (caseType.status === 'active') {
                            options += '<option value="' + caseType.name + '">' + caseType.name + '</option>';
                        }
                    });
                    $('#caseType').html(options);
                } else {
                    console.error('Lỗi khi tải danh sách loại case:', response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Lỗi AJAX khi tải case types:', error);
            }
        });
    }
    
    function generateCaseNumber() {
        var today = new Date();
        var year = today.getFullYear().toString().slice(-2); // 2 số cuối của năm (25)
        var month = String(today.getMonth() + 1).padStart(2, '0'); // tháng với 2 chữ số (07)
        
        // Tạo số preview theo format CNB.YYMMNNN
        var sequence = '001'; // Placeholder, server sẽ tính số thực tế
        var caseNumber = 'CNB.' + year + month + sequence;
        $('#caseNumber').val(caseNumber + ' (Tự động tạo)');
    }
    
    function createCase() {
        // Validate form
        var requiredFields = ['requesterId', 'handlerId', 'caseType', 'issueTitle', 'issueDescription', 'status', 'startDate'];
        var isValid = true;
        var firstInvalidField = null;
        
        requiredFields.forEach(function(fieldId) {
            var element = $('#' + fieldId);
            if (!element.val() || element.val().trim() === '') {
                element.removeClass('is-valid').addClass('is-invalid');
                isValid = false;
                if (!firstInvalidField) {
                    firstInvalidField = element;
                }
            } else {
                element.removeClass('is-invalid').addClass('is-valid');
            }
        });
        
        if (!isValid) {
            showError('Vui lòng điền đầy đủ các trường bắt buộc');
            if (firstInvalidField) {
                firstInvalidField.focus();
            }
            return;
        }
        
        // Validate date range only if both dates are provided
        var startDate = $('#startDate').val();
        var dueDate = $('#dueDate').val();
        
        if (startDate && dueDate) {
            var start = new Date(startDate);
            var end = new Date(dueDate);
            
            if (end <= start) {
                showError('Ngày kết thúc phải lớn hơn ngày bắt đầu');
                $('#dueDate').focus();
                return;
            }
        }
        
        // Prepare data
        var formData = {
            requester_id: $('#requesterId').val(),
            handler_id: $('#handlerId').val(),
            transferred_by: $('#transferredBy').val(),
            case_type: $('#caseType').val(),
            priority: $('#priority').val(),
            issue_title: $('#issueTitle').val(),
            issue_description: $('#issueDescription').val(),
            status: $('#status').val(),
            notes: $('#notes').val(),
            start_date: $('#startDate').val(),
            due_date: $('#dueDate').val()
        };
        
        // Submit via AJAX
        $.ajax({
            url: 'api/create_case.php',
            type: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function(response) {
                if (response.success) {
                    // Lấy tên người xử lý từ form để hiển thị trong thông báo
                    var handlerSelect = $('#handlerId');
                    var handlerName = handlerSelect.find('option:selected').text();
                    
                    showSuccess('Tạo case thành công! Mã case: ' + response.case_number + '. Đã gửi thông báo cho người xử lý: ' + handlerName);
                    
                    // Reset form
                    $('#createCaseForm')[0].reset();
                    $('.form-control, .form-select').removeClass('is-valid is-invalid');
                    
                    // Close modal and reload page after 3 seconds (tăng thời gian để user đọc thông báo)
                    setTimeout(function() {
                        $('#createCaseModal').modal('hide');
                        // Hide empty state if it exists (in case we're not reloading)
                        hideEmptyState();
                        location.reload();
                    }, 3000);
                } else {
                    showError(response.error || 'Có lỗi xảy ra khi tạo case');
                }
            },
            error: function(xhr, status, error) {
                console.error('Lỗi AJAX:', error);
                console.error('Response text:', xhr.responseText);
                showError('Có lỗi xảy ra khi kết nối server. Vui lòng thử lại.');
            }
        });
    }
    
    // ===== EDIT CASE FUNCTIONS ===== //
    
    function validateEditDateRange() {
        var startDate = $('#editStartDate').val();
        var dueDate = $('#editDueDate').val();
        
        if (startDate && dueDate) {
            var start = new Date(startDate);
            var end = new Date(dueDate);
            
            if (end <= start) {
                $('#editDueDate').addClass('is-invalid');
                if (!$('#editDateError').length) {
                    $('#editDueDate').after('<div id="editDateError" class="invalid-feedback">Ngày kết thúc phải lớn hơn ngày bắt đầu</div>');
                }
                return false;
            } else {
                $('#editDueDate').removeClass('is-invalid');
                $('#editDateError').remove();
                return true;
            }
        }
        $('#editDueDate').removeClass('is-invalid');
        $('#editDateError').remove();
        return true;
    }
    
    function updateCase() {
        // Validate form
        var requiredFields = ['editRequesterId', 'editHandlerId', 'editCaseType', 'editIssueTitle', 'editIssueDescription', 'editStatus'];
        var isValid = true;
        var firstInvalidField = null;
        
        requiredFields.forEach(function(fieldId) {
            var element = $('#' + fieldId);
            if (!element.val() || element.val().trim() === '') {
                element.removeClass('is-valid').addClass('is-invalid');
                isValid = false;
                if (!firstInvalidField) {
                    firstInvalidField = element;
                }
            } else {
                element.removeClass('is-invalid').addClass('is-valid');
            }
        });
        
        if (!isValid) {
            showError('Vui lòng điền đầy đủ các trường bắt buộc');
            if (firstInvalidField) {
                firstInvalidField.focus();
            }
            return;
        }
        
        // Validate date range only if both dates are provided
        var startDate = $('#editStartDate').val();
        var dueDate = $('#editDueDate').val();
        
        if (startDate && dueDate) {
            var start = new Date(startDate);
            var end = new Date(dueDate);
            
            if (end <= start) {
                showError('Ngày kết thúc phải lớn hơn ngày bắt đầu');
                $('#editDueDate').focus();
                return;
            }
        }
        
        // Prepare data
        var formData = {
            case_id: $('#editCaseId').val(),
            requester_id: $('#editRequesterId').val(),
            handler_id: $('#editHandlerId').val(),
            case_type: $('#editCaseType').val(),
            priority: $('#editPriority').val(),
            issue_title: $('#editIssueTitle').val(),
            issue_description: $('#editIssueDescription').val(),
            status: $('#editStatus').val(),
            notes: $('#editNotes').val(),
            start_date: $('#editStartDate').val(),
            due_date: $('#editDueDate').val()
        };
        
        // Disable submit button
        var submitBtn = $('#editCaseForm button[type="submit"]');
        var originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Đang cập nhật...');
        
        // Submit via AJAX
        $.ajax({
            url: 'api/update_case_simple.php',
            type: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function(response) {
                console.log('API Response:', response);
                if (response.success) {
                    showSuccess('Cập nhật case thành công!');
                    
                    // Close modal after 1 second
                    setTimeout(function() {
                        $('#editCaseModal').modal('hide');
                        
                        // Update the table row with new data
                        updateTableRow(formData.case_id, formData);
                    }, 1000);
                } else {
                    showError(response.error || 'Có lỗi xảy ra khi cập nhật case');
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', xhr.responseText);
                console.log('Status:', status);
                console.log('Error:', error);
                showError('Có lỗi xảy ra khi kết nối server. Vui lòng thử lại.');
            },
            complete: function() {
                // Re-enable submit button
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    }
    
    // ===== JS: UPDATE TABLE ROW ===== //
    function updateTableRow(caseId, formData) {
        // Find the table row for this case
        var row = $('tr[data-case-id="' + caseId + '"]');
        if (row.length === 0) {
            // If row not found, reload the page as fallback
            location.reload();
            return;
        }
        
        // Update status column
        if (formData.status) {
            var statusText = '';
            
            switch(formData.status) {
                case 'pending':
                    statusText = 'Tiếp nhận';
                    break;
                case 'in_progress':
                    statusText = 'Đang xử lý';
                    break;
                case 'completed':
                    statusText = 'Hoàn thành';
                    break;
                case 'cancelled':
                    statusText = 'Huỷ';
                    break;
                default:
                    statusText = 'Không xác định';
            }
            
            // Update status text and class
            var statusSpan = row.find('.case-status');
            statusSpan.removeClass('status-pending status-in_progress status-completed status-cancelled')
                     .addClass('status-' + formData.status)
                     .text(statusText);
        }
        
        // Update notes column if provided (if it exists in the table)
        if (formData.notes !== undefined) {
            var notesCell = row.find('.case-notes');
            if (notesCell.length > 0) {
                notesCell.text(formData.notes || '');
            }
        }
        
        // Update due_date column (Ngày hoàn thành)
        if (formData.due_date !== undefined) {
            var dueDateCell = row.find('td:nth-child(8)'); // Cột thứ 8 là Ngày hoàn thành
            if (dueDateCell.length > 0) {
                if (formData.due_date) {
                    var dueDate = new Date(formData.due_date);
                    var formattedDate = dueDate.getDate().toString().padStart(2, '0') + '/' + 
                                       (dueDate.getMonth() + 1).toString().padStart(2, '0') + '/' + 
                                       dueDate.getFullYear() + '<br>' +
                                       '<small>' + dueDate.getHours().toString().padStart(2, '0') + ':' + 
                                       dueDate.getMinutes().toString().padStart(2, '0') + '</small>';
                    dueDateCell.html('<span class="case-date">' + formattedDate + '</span>');
                } else {
                    dueDateCell.html('<span class="text-muted">-</span>');
                }
            }
        }
        
        // Update start_date column if provided
        if (formData.start_date !== undefined) {
            var startDateCell = row.find('td:nth-child(7)'); // Cột thứ 7 là Ngày tiếp nhận
            if (startDateCell.length > 0) {
                if (formData.start_date) {
                    var startDate = new Date(formData.start_date);
                    var formattedDate = startDate.getDate().toString().padStart(2, '0') + '/' + 
                                       (startDate.getMonth() + 1).toString().padStart(2, '0') + '/' + 
                                       startDate.getFullYear() + '<br>' +
                                       '<small>' + startDate.getHours().toString().padStart(2, '0') + ':' + 
                                       startDate.getMinutes().toString().padStart(2, '0') + '</small>';
                    startDateCell.html('<span class="case-date">' + formattedDate + '</span>');
                } else {
                    startDateCell.html('<span class="text-muted">-</span>');
                }
            }
        }
        
        // Update the updated_at column if it exists
        var updatedAtCell = row.find('.updated-at');
        if (updatedAtCell.length > 0) {
            var now = new Date();
            var formattedDate = now.getDate().toString().padStart(2, '0') + '/' + 
                               (now.getMonth() + 1).toString().padStart(2, '0') + '/' + 
                               now.getFullYear() + ' ' +
                               now.getHours().toString().padStart(2, '0') + ':' + 
                               now.getMinutes().toString().padStart(2, '0');
            updatedAtCell.text(formattedDate);
        }
        
        console.log('Table row update completed');
    }
    
    // ===== JS: XEM CHI TIẾT CASE ===== //
    $(document).on('click', '.btn-view-case', function() {
        var caseId = $(this).data('id');
        // Gọi API lấy chi tiết case
        $.ajax({
            url: 'api/get_internal_case_details.php?id=' + caseId,
            type: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(response) {
                if (response.success) {
                    fillViewCaseForm(response.data);
                    $('#viewCaseModal').modal('show');
                    $('#btnViewToEditCase').data('case', response.data);
                } else {
                    showError('Không thể tải chi tiết case');
                }
            },
            error: function() {
                showError('Có lỗi khi tải chi tiết case');
            }
        });
    });

    $(document).on('click', '#btnViewToEditCase', function() {
        var caseData = $(this).data('case');
        // Blur focus trước khi đóng modal để tránh lỗi ARIA
        $(this).blur();
        $('#viewCaseModal').modal('hide');
        // Sau khi modal ẩn xong thì mở modal chỉnh sửa
        setTimeout(function() {
            loadEditCaseData(caseData).catch(function(error) {
                showError('Lỗi khi load dữ liệu edit');
            });
        }, 400);
    });

    function fillViewCaseForm(caseData) {
        
        // Populate các trường readonly với dữ liệu từ caseData
        $('#viewCaseNumber').val(caseData.case_number || '');
        $('#viewRequesterName').val(caseData.requester_name || 'N/A');
        $('#viewRequesterPosition').val(caseData.requester_position || 'N/A');
        $('#viewTransferredBy').val('Trần Nguyễn Anh Khoa'); // Giá trị cố định
        $('#viewHandlerName').val(caseData.handler_name || 'Chưa phân công');
        $('#viewCaseTypeName').val(caseData.case_type || 'N/A');
        
        // Map priority values
        var priorityMap = {
            'onsite': 'Onsite',
            'offsite': 'Offsite', 
            'remote': 'Remote'
        };
        $('#viewPriorityName').val(priorityMap[caseData.priority] || 'Onsite');
        
        $('#viewIssueTitle').val(caseData.issue_title || '');
        $('#viewIssueDescription').val(caseData.issue_description || '');
        
        // Format dates for display
        if (caseData.start_date) {
            try {
                var startDate = new Date(caseData.start_date);
                if (!isNaN(startDate.getTime())) {
                    var formattedStartDate = formatDateTimeDisplay(startDate);
                    $('#viewStartDate').val(formattedStartDate);
                } else {
                    $('#viewStartDate').val('Chưa có');
                }
            } catch (error) {
                $('#viewStartDate').val('Chưa có');
            }
        } else {
            $('#viewStartDate').val('Chưa có');
        }
        
        if (caseData.due_date) {
            try {
                var dueDate = new Date(caseData.due_date);
                if (!isNaN(dueDate.getTime())) {
                    var formattedDueDate = formatDateTimeDisplay(dueDate);
                    $('#viewDueDate').val(formattedDueDate);
                } else {
                    $('#viewDueDate').val('Chưa có');
                }
            } catch (error) {
                $('#viewDueDate').val('Chưa có');
            }
        } else {
            $('#viewDueDate').val('Chưa có');
        }
        
        // Map status values
        var statusMap = {
            'pending': 'Tiếp nhận',
            'in_progress': 'Đang xử lý',
            'completed': 'Hoàn thành',
            'cancelled': 'Huỷ'
        };
        $('#viewStatusName').val(statusMap[caseData.status] || 'Tiếp nhận');
        
        $('#viewNotes').val(caseData.notes || '');
    }
    
    // ===== FOCUS MANAGEMENT FOR ACCESSIBILITY ===== //
    // Xử lý focus khi đóng modal để tránh lỗi ARIA
    $(document).on('click', '.btn-close', function() {
        // Blur focus trước khi đóng modal
        $(this).blur();
    });
    
    // Xử lý focus khi đóng modal bằng ESC key
    $(document).on('keydown', '.modal', function(e) {
        if (e.key === 'Escape') {
            // Blur tất cả focusable elements trong modal
            $(this).find(':focus').blur();
        }
    });
    
    // Xử lý focus khi click outside modal
    $(document).on('click', '.modal', function(e) {
        if (e.target === this) {
            // Blur tất cả focusable elements trong modal
            $(this).find(':focus').blur();
        }
    });
    
    // Xử lý focus khi modal bắt đầu ẩn
    $(document).on('hide.bs.modal', function(e) {
        var modal = $(e.target);
        // Blur tất cả focusable elements trong modal
        modal.find(':focus').blur();
    });
    
    // ===== EXCEL EXPORT FUNCTIONALITY ===== //
    
    // Xử lý click nút xuất Excel
    $(document).on('click', '#exportExcelBtn', function(e) {
        e.preventDefault();
        exportInternalCasesToExcel();
    });
    
    function exportInternalCasesToExcel() {
        // Lấy dữ liệu từ các row đang hiển thị
        var visibleRows = [];
        $('tbody tr:visible').each(function() {
            var row = $(this);
            var caseData = {
                case_number: row.find('td:eq(1) .case-number').text().trim(),
                requester_name: row.find('td:eq(2) div').text().trim(),
                handler_name: row.find('td:eq(3) div').text().trim(),
                case_type: row.find('td:eq(4) span').text().trim(),
                issue_title: row.find('td:eq(5) .case-title').text().trim(),
                start_date: row.find('td:eq(6) .case-date').text().trim() || '-',
                due_date: row.find('td:eq(7) .case-date').text().trim() || '-',
                status: row.find('td:eq(8) .case-status').text().trim()
            };
            visibleRows.push(caseData);
        });
        
        if (visibleRows.length === 0) {
            showError('Không có dữ liệu để xuất!');
            return;
        }
        
        // Hiển thị loading
        var originalText = $('#exportExcelBtn').html();
        $('#exportExcelBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Đang xuất...');
        
        // Tạo workbook và worksheet
        var wb = XLSX.utils.book_new();
        
        // Tạo dữ liệu cho worksheet
        var wsData = [
            ['STT', 'Số case', 'Người yêu cầu', 'Người xử lý', 'Loại case', 'Vụ việc hỗ trợ', 'Ngày tiếp nhận', 'Ngày hoàn thành', 'Trạng thái']
        ];
        
        // Thêm dữ liệu từ các row
        visibleRows.forEach(function(row, index) {
            wsData.push([
                index + 1,
                row.case_number,
                row.requester_name,
                row.handler_name,
                row.case_type,
                row.issue_title,
                row.start_date,
                row.due_date,
                row.status
            ]);
        });
        
        // Tạo worksheet
        var ws = XLSX.utils.aoa_to_sheet(wsData);
        
        // Định dạng header (dòng đầu tiên)
        var headerRange = XLSX.utils.decode_range(ws['!ref']);
        for (var col = headerRange.s.c; col <= headerRange.e.c; col++) {
            var cellAddress = XLSX.utils.encode_cell({r: 0, c: col});
            if (!ws[cellAddress]) ws[cellAddress] = {};
            ws[cellAddress].s = {
                font: { bold: true, color: { rgb: "FFFFFF" } },
                fill: { fgColor: { rgb: "4472C4" } },
                alignment: { horizontal: "center", vertical: "center" },
                border: {
                    top: { style: "thin", color: { rgb: "000000" } },
                    bottom: { style: "thin", color: { rgb: "000000" } },
                    left: { style: "thin", color: { rgb: "000000" } },
                    right: { style: "thin", color: { rgb: "000000" } }
                }
            };
        }
        
        // Định dạng dữ liệu (các dòng còn lại)
        for (var row = 1; row <= visibleRows.length; row++) {
            for (var col = 0; col <= 8; col++) {
                var cellAddress = XLSX.utils.encode_cell({r: row, c: col});
                if (!ws[cellAddress]) ws[cellAddress] = {};
                ws[cellAddress].s = {
                    alignment: { 
                        horizontal: col === 0 ? "center" : "left", 
                        vertical: "center" 
                    },
                    border: {
                        top: { style: "thin", color: { rgb: "000000" } },
                        bottom: { style: "thin", color: { rgb: "000000" } },
                        left: { style: "thin", color: { rgb: "000000" } },
                        right: { style: "thin", color: { rgb: "000000" } }
                    }
                };
            }
        }
        
        // Thiết lập độ rộng cột
        ws['!cols'] = [
            { width: 8 },   // STT
            { width: 15 },  // Số case
            { width: 20 },  // Người yêu cầu
            { width: 20 },  // Người xử lý
            { width: 18 },  // Loại case
            { width: 35 },  // Vụ việc hỗ trợ
            { width: 15 },  // Ngày tiếp nhận
            { width: 15 },  // Ngày hoàn thành
            { width: 15 }   // Trạng thái
        ];
        
        // Thiết lập chiều cao dòng header
        ws['!rows'] = [{ hpt: 25 }]; // Chiều cao 25 points cho dòng đầu
        
        // Thêm worksheet vào workbook
        XLSX.utils.book_append_sheet(wb, ws, "Danh sách Case nội bộ");
        
        // Tạo tên file
        var fileName = "Danh_sach_Case_noi_bo_" + new Date().toISOString().slice(0,10) + ".xlsx";
        
        // Xuất file
        XLSX.writeFile(wb, fileName);
        
        // Reset button
        setTimeout(function() {
            $('#exportExcelBtn').prop('disabled', false).html(originalText);
            showSuccess('Đã xuất file Excel thành công!');
        }, 1000);
    }
    
    // Auto-open modal khi có parameter từ workspace
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const openEditModal = urlParams.get('open_edit_modal');
        const caseId = urlParams.get('case_id');
        
        if (openEditModal === '1' && caseId) {
            // Tìm và mở modal edit case
            setTimeout(() => {
                const editButtons = document.querySelectorAll('[onclick*="editInternalCase"]');
                for (let button of editButtons) {
                    const onclick = button.getAttribute('onclick');
                    if (onclick && onclick.includes(caseId)) {
                        button.click();
                        break;
                    }
                }
            }, 1000);
        }
    });
    </script>
</body>
</html> 