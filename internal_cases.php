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

// Lấy danh sách case nội bộ từ database
$cases = [];
try {
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
            ORDER BY ic.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
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
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/alert.css">
    
    <!-- No Border Radius Override -->
    <link rel="stylesheet" href="assets/css/no-border-radius.css">
    
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
            top: 50%;
            left: -6px;
            transform: translateY(-50%);
            border: 6px solid transparent;
            border-right-color: #d1ecf1;
        }
        
        /* Table alignment styles */
        .internal-cases-table thead th {
            text-align: center;
            vertical-align: middle;
        }
        
        .internal-cases-table tbody td {
            text-align: center;
            vertical-align: middle;
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
            
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="dashboard.php" class="text-decoration-none">
                            <i class="fas fa-home me-1"></i>
                            Trang chủ
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="#" class="text-decoration-none">Công việc</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Case nội bộ</li>
                </ol>
            </nav>
            
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
                                <div class="col-auto">
                                    <div class="d-flex gap-2">
                                        <div class="input-group input-group-sm" style="width: 300px;">
                                            <span class="input-group-text">
                                                <i class="fas fa-search"></i>
                                            </span>
                                            <input type="text" class="form-control" id="searchInput" 
                                                   placeholder="Tìm kiếm case...">
                                        </div>
                                        <select class="form-select form-select-sm" id="statusFilter" style="width: 150px;">
                                            <option value="">Tất cả trạng thái</option>
                                            <option value="pending">Tiếp nhận</option>
                                            <option value="in_progress">Đang xử lý</option>
                                            <option value="completed">Hoàn thành</option>
                                            <option value="cancelled">Huỷ</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
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
                                    <table class="table table-hover mb-0 internal-cases-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                                    </div>
                                                </th>
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
                                            <?php foreach ($cases as $case): ?>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" 
                                                                   value="<?php echo $case['id']; ?>">
                                                        </div>
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
                                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                    title="Xem chi tiết"
                                                                    onclick="viewCase(<?php echo $case['id']; ?>)">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                                    title="Chỉnh sửa"
                                                                    onclick="editCase(<?php echo $case['id']; ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <?php if ($case['status'] !== 'completed'): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                                    title="Đánh dấu hoàn thành"
                                                                    onclick="markAsCompleted(<?php echo $case['id']; ?>)">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                    title="Xóa"
                                                                    onclick="deleteCase(<?php echo $case['id']; ?>)">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
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
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                                            <input type="text" class="form-control" id="transferredBy" 
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
                                            <label class="form-label mb-0 fw-semibold">Bắt đầu</label>
                                        </div>
                                        <div class="col-8">
                                            <input type="datetime-local" class="form-control" id="startDate" name="start_date">
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
                        <button type="submit" class="btn btn-success">
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
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                                            <select class="form-select" id="editRequesterId" name="requester_id" required>
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
                                            <select class="form-select" id="editHandlerId" name="handler_id" required>
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
                                            <select class="form-select" id="editCaseType" name="case_type" required>
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
                                            <select class="form-select" id="editPriority" name="priority">
                                                <option value="onsite">Onsite</option>
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
                                            <input type="text" class="form-control" id="editIssueTitle" name="issue_title" 
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
                                            <textarea class="form-control" id="editIssueDescription" name="issue_description" 
                                                      rows="3" placeholder="Mô tả chi tiết vấn đề..." required></textarea>
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
                                            <input type="datetime-local" class="form-control" id="editStartDate" name="start_date">
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
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Cập nhật
                        </button>
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
    <script src="assets/js/alert.js"></script>
    
    <script>
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
        
        // Search functionality
        $('#searchInput').on('input', function() {
            // TODO: Implement search functionality
        });
        
        // Status filter
        $('#statusFilter').on('change', function() {
            // TODO: Implement filter functionality
        });
        
        // Select all checkbox
        $('#selectAll').on('change', function() {
            $('tbody input[type="checkbox"]').prop('checked', this.checked);
        });
        
        // Bulk delete functionality
        function bulkDeleteCases() {
            var selectedIds = [];
            $('tbody input[type="checkbox"]:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            if (selectedIds.length === 0) {
                showError('Vui lòng chọn ít nhất một case để xóa');
                return;
            }
            
            if (confirm('Bạn có chắc chắn muốn xóa ' + selectedIds.length + ' case đã chọn?')) {
                var promises = selectedIds.map(function(id) {
                    return $.ajax({
                        url: 'api/delete_case.php',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({ case_id: id })
                    });
                });
                
                Promise.all(promises)
                    .then(function(responses) {
                        var successCount = responses.filter(r => r.success).length;
                        showSuccess('Đã xóa thành công ' + successCount + ' case');
                        setTimeout(() => location.reload(), 1500);
                    })
                    .catch(function(error) {
                        showError('Có lỗi xảy ra khi xóa case');
                        console.error('Bulk delete error:', error);
                    });
            }
        }
        
        // Add bulk delete button if cases are selected
        $('tbody input[type="checkbox"]').on('change', function() {
            var checkedCount = $('tbody input[type="checkbox"]:checked').length;
            var existingBtn = $('#bulkDeleteBtn');
            
            if (checkedCount > 0) {
                if (existingBtn.length === 0) {
                    var bulkBtn = $('<button type="button" class="btn btn-outline-danger btn-sm me-2" id="bulkDeleteBtn">' +
                        '<i class="fas fa-trash me-2"></i>Xóa đã chọn (' + checkedCount + ')' +
                        '</button>');
                    bulkBtn.on('click', bulkDeleteCases);
                    $('.page-header .col-auto').prepend(bulkBtn);
                } else {
                    existingBtn.html('<i class="fas fa-trash me-2"></i>Xóa đã chọn (' + checkedCount + ')');
                }
            } else {
                existingBtn.remove();
            }
        });
    });
    
    // Case management functions
    function viewCase(id) {
        showInfo('Chức năng xem chi tiết case sẽ được phát triển sau...');
    }
    
    function editCase(id) {
        // Load case data và hiển thị modal edit
        $.ajax({
            url: 'api/get_case_details.php?id=' + id,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    // Load staff list và case types trước khi populate form
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
        // Load staff list và case types trước
        Promise.all([
            loadStaffListForEdit(),
            loadCaseTypesForEdit()
        ]).then(function() {
            // Sau khi load xong, populate form với dữ liệu case
            populateEditForm(caseData);
            // Hiển thị modal
            $('#editCaseModal').modal('show');
        }).catch(function(error) {
            showError('Không thể tải dữ liệu cần thiết');
        });
    }
    
    function loadStaffListForEdit() {
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: 'api/get_staff_list.php',
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        var options = '<option value="">Chọn nhân sự</option>';
                        response.data.forEach(function(staff) {
                            options += '<option value="' + staff.id + '" data-position="' + staff.position + '">' + 
                                      staff.fullname + '</option>';
                        });
                        $('#editRequesterId').html(options);
                        $('#editHandlerId').html(options);
                        resolve();
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
                success: function(response) {
                    if (response.success) {
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
        
        // Select fields
        $('#editRequesterId').val(caseData.requester_id);
        $('#editHandlerId').val(caseData.handler_id);
        $('#editCaseType').val(caseData.case_type);
        $('#editPriority').val(caseData.priority || 'onsite');
        $('#editStatus').val(caseData.status);
        
        // Text fields
        $('#editIssueTitle').val(caseData.issue_title);
        $('#editIssueDescription').val(caseData.issue_description);
        $('#editNotes').val(caseData.notes || '');
        
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
                contentType: 'application/json',
                data: JSON.stringify({
                    case_id: id
                }),
                success: function(response) {
                    modal.modal('hide');
                    if (response.success) {
                        showSuccess('Xóa case thành công: ' + response.deleted_case.case_number);
                        // Reload page sau 1.5 giây để user thấy message
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
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
    
    function markAsCompleted(id) {
        if (confirm('Bạn có chắc chắn muốn đánh dấu case này là hoàn thành?')) {
            $.ajax({
                url: 'api/update_case.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    case_id: id,
                    status: 'completed'
                }),
                success: function(response) {
                    if (response.success) {
                        showSuccess('Đánh dấu hoàn thành thành công!');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showError(response.error || 'Có lỗi xảy ra khi cập nhật trạng thái');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error updating case:', error);
                    showError('Có lỗi xảy ra khi cập nhật trạng thái');
                }
            });
        }
    }
    
    // Helper functions for create case modal
    function loadStaffList() {
        $.ajax({
            url: 'api/get_staff_list.php',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    var options = '<option value="">Chọn nhân sự</option>';
                    response.data.forEach(function(staff) {
                        options += '<option value="' + staff.id + '" data-position="' + staff.position + '">' + 
                                  staff.fullname + '</option>';
                    });
                    $('#requesterId').html(options);
                    $('#handlerId').html(options);
                } else {
                    console.error('Lỗi khi tải danh sách nhân sự:', response.error);
                }
            },
            error: function(xhr, status, error) {
                console.error('Lỗi AJAX:', error);
            }
        });
    }
    
    function loadCaseTypes() {
        $.ajax({
            url: 'api/case_types.php?type=internal&action=list',
            type: 'GET',
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
        var requiredFields = ['requesterId', 'handlerId', 'caseType', 'issueTitle', 'issueDescription', 'status'];
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
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function(response) {
                if (response.success) {
                    showSuccess('Tạo case thành công! Mã case: ' + response.case_number);
                    
                    // Reset form
                    $('#createCaseForm')[0].reset();
                    $('.form-control, .form-select').removeClass('is-valid is-invalid');
                    
                    // Close modal and reload page after 2 seconds
                    setTimeout(function() {
                        $('#createCaseModal').modal('hide');
                        location.reload();
                    }, 2000);
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
            url: 'api/update_case.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(formData),
            success: function(response) {
                if (response.success) {
                    showSuccess('Cập nhật case thành công!');
                    
                    // Close modal and reload page after 2 seconds
                    setTimeout(function() {
                        $('#editCaseModal').modal('hide');
                        location.reload();
                    }, 2000);
                } else {
                    showError(response.error || 'Có lỗi xảy ra khi cập nhật case');
                }
            },
            error: function(xhr, status, error) {
                showError('Có lỗi xảy ra khi kết nối server. Vui lòng thử lại.');
            },
            complete: function() {
                // Re-enable submit button
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    }
    </script>
</body>
</html> 