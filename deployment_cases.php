<?php
/**
 * IT CRM - Deployment Cases Management
 * File: deployment_cases.php
 * Mục đích: Quản lý các case triển khai dự án
 * Tác giả: IT Support Team
 */

require_once 'includes/session.php';
requireLogin();

require_once 'config/db.php';

// Tạo bảng deployment_cases nếu chưa tồn tại
try {
    $checkTable = $pdo->query("SHOW TABLES LIKE 'deployment_cases'");
    if ($checkTable->rowCount() == 0) {
        $createTableSQL = "CREATE TABLE deployment_cases (
            id INT AUTO_INCREMENT PRIMARY KEY,
            case_number VARCHAR(50) NOT NULL UNIQUE,
            progress VARCHAR(20) DEFAULT 'CS',
            case_description TEXT,
            notes TEXT,
            created_by INT,
            assigned_to INT,
            priority VARCHAR(20) DEFAULT 'onsite',
            start_date DATE,
            due_date DATE,
            status VARCHAR(20) DEFAULT 'pending',
            progress_status VARCHAR(50) DEFAULT 'Phát triển sau',
            total_tasks INT DEFAULT 0,
            completed_tasks INT DEFAULT 0,
            progress_percentage INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES staffs(id),
            FOREIGN KEY (assigned_to) REFERENCES staffs(id)
        )";
        $pdo->exec($createTableSQL);
    }
} catch (PDOException $e) {
    // Ignore table creation errors
}

// Lấy danh sách case triển khai từ database
$cases = [];
try {
    $sql = "SELECT 
                dc.id,
                dc.case_number,
                dc.progress,
                dc.case_description,
                dc.notes,
                dc.status,
                dc.created_at,
                dc.start_date,
                dc.due_date,
                dc.progress_status,
                dc.total_tasks,
                dc.completed_tasks,
                dc.progress_percentage,
                dc.priority,
                creator.fullname as created_by_name,
                assignee.fullname as assigned_to_name
            FROM deployment_cases dc
            LEFT JOIN staffs creator ON dc.created_by = creator.id
            LEFT JOIN staffs assignee ON dc.assigned_to = assignee.id
            ORDER BY dc.created_at DESC";
    
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
    <title>Case Triển Khai - IT Services Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo filemtime('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="assets/css/alert.css?v=<?php echo filemtime('assets/css/alert.css'); ?>">
    
    <!-- No Border Radius Override -->
    <link rel="stylesheet" href="assets/css/no-border-radius.css?v=<?php echo filemtime('assets/css/no-border-radius.css'); ?>">
    
    <style>
        /* Tooltip CSS */
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
            border: 6px solid transparent;
            border-right-color: #d1ecf1;
        }
        
        /* ===== DEPLOYMENT CASES MODAL STYLES ===== */
        #addDeploymentCaseModal .modal-dialog {
            max-width: 95vw;
            width: 95vw;
            margin: 0.5rem auto;
        }

        #addDeploymentCaseModal .modal-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 1rem 1.5rem;
        }

        #addDeploymentCaseModal .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        #addDeploymentCaseModal .modal-body {
            padding: 1.5rem;
            max-height: 70vh;
            overflow-y: auto;
        }

        #addDeploymentCaseModal .form-label {
            color: #495057;
            font-weight: 600;
        }

        #addDeploymentCaseModal .form-control,
        #addDeploymentCaseModal .form-select {
            border: 1px solid #dee2e6;
            padding: 0.5rem 0.75rem;
            font-size: 0.9rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        #addDeploymentCaseModal .form-control:focus,
        #addDeploymentCaseModal .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }

        #addDeploymentCaseModal .form-control.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        #addDeploymentCaseModal .form-control.is-valid {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }

        #addDeploymentCaseModal .form-control[readonly] {
            background-color: #f8f9fa;
            opacity: 1;
        }

        #addDeploymentCaseModal .text-danger {
            color: #dc3545 !important;
        }

        #addDeploymentCaseModal .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #dee2e6;
        }

        #addDeploymentCaseModal .btn {
            padding: 0.5rem 1.5rem;
            font-weight: 500;
        }

        #addDeploymentCaseModal .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }

        #addDeploymentCaseModal .btn-success:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        #addDeploymentCaseModal .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }

        #addDeploymentCaseModal .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }

        #addDeploymentCaseModal .alert {
            margin-bottom: 0;
        }

        #addDeploymentCaseModal .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        #addDeploymentCaseModal .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        /* Row spacing for form fields */
        #addDeploymentCaseModal .mb-3 {
            margin-bottom: 1rem;
        }

        #addDeploymentCaseModal .row.align-items-center {
            min-height: 40px;
        }

        #addDeploymentCaseModal .row.align-items-top {
            align-items: flex-start;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            #addDeploymentCaseModal .modal-dialog {
                margin: 0.25rem;
                max-width: 98vw;
                width: 98vw;
            }
            
            #addDeploymentCaseModal .modal-body {
                padding: 1rem;
            }
            
            #addDeploymentCaseModal .col-md-6 {
                margin-bottom: 0.75rem;
            }
            
            #addDeploymentCaseModal .row.align-items-center .col-4 {
                margin-bottom: 0.25rem;
            }
            
            #addDeploymentCaseModal .mb-3 {
                margin-bottom: 0.75rem;
            }
        }

        /* Table alignment styles */
        .deployment-cases-table thead th {
            text-align: center;
            vertical-align: middle;
        }
        
        .deployment-cases-table tbody td {
            text-align: center;
            vertical-align: middle;
        }
        
        .progress-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 15px;
            font-size: 0.6rem; /* Giảm font chữ nhỏ hơn nữa */
            font-weight: 600;
        }
        
        .progress-CS { background: #007bff; color: white; }
        .progress-SH { background: #28a745; color: white; }
        .progress-GH { background: #ffc107; color: black; }
        .progress-TK { background: #17a2b8; color: white; }
        .progress-NT { background: #6f42c1; color: white; }
        
        .priority-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 15px;
            font-size: 0.6rem; /* Giảm font chữ nhỏ hơn nữa */
            font-weight: 600;
        }
        
        .priority-onsite { background: #dc3545; color: white; }
        .priority-offsite { background: #fd7e14; color: white; }
        .priority-remote { background: #20c997; color: white; }
        
        /* Case status styles */
        .case-status {
            padding: 0.4rem 0.8rem;
            border-radius: 15px;
            font-size: 0.6rem; /* Giảm font chữ nhỏ hơn nữa */
            font-weight: 600;
        }
        
        .status-pending { background: #ffc107; color: black; }
        .status-in_progress { background: #17a2b8; color: white; }
        .status-completed { background: #28a745; color: white; }
        .status-cancelled { background: #6c757d; color: white; }
        
        /* Progress bar styles */
        .progress-bar-custom {
            height: 8px;
            border-radius: 4px;
            background-color: #e9ecef;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #007bff, #0056b3);
            transition: width 0.3s ease;
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
        
        /* Text truncation */
        .text-truncate-custom {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .deployment-cases-table {
            font-size: 0.85rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .deployment-cases-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            font-size: 0.8rem;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding: 0.75rem 0.5rem;
            vertical-align: middle;
        }
        .deployment-cases-table td {
            padding: 0.75rem 0.5rem;
            vertical-align: middle;
            font-size: 0.85rem;
            border-bottom: 1px solid #e9ecef;
        }
        .deployment-cases-table tbody tr:hover {
            background-color: #f8f9fa;
            transition: background-color 0.2s ease;
        }
        .deployment-cases-table .case-number {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: #007bff;
            font-size: 0.9rem;
        }
        .deployment-cases-table th,
        .deployment-cases-table td {
            padding: 0.5rem 0.25rem;
        }
        .deployment-cases-table .case-status {
            font-size: 0.6rem;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }
        .deployment-cases-table .case-date {
            font-size: 0.8rem;
            color: #6c757d;
            white-space: nowrap;
        }
        .deployment-cases-table .action-buttons .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            margin: 0 0.125rem;
        }
        @media (max-width: 768px) {
            .deployment-cases-table {
                font-size: 0.75rem;
            }
            .deployment-cases-table th,
            .deployment-cases-table td {
                padding: 0.5rem 0.25rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- ===== MAIN CONTENT ===== -->
    <main class="main-content">
        <div class="container-fluid px-4 py-4">
            
            <!-- Page Header -->
            <div class="page-header mb-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h1 class="page-title mb-0">
                            <i class="fas fa-rocket me-3 text-success"></i>
                            Case Triển Khai
                        </h1>
                        <p class="text-muted mb-0">Quản lý các case triển khai dự án và hệ thống</p>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary" id="createCaseBtn" data-bs-toggle="modal" data-bs-target="#addDeploymentCaseModal">
                            <i class="fas fa-plus me-2"></i>
                            Tạo Case triển khai
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
                                        Danh sách Case triển khai
                                    </h5>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex gap-2">
                                        <div class="input-group input-group-sm" style="width: 300px;">
                                            <span class="input-group-text">
                                                <i class="fas fa-search"></i>
                                            </span>
                                            <input type="text" class="form-control" id="caseSearchInput" 
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
                                        <i class="fas fa-rocket fa-5x text-muted opacity-50"></i>
                                    </div>
                                    <h4 class="text-muted mb-3">Chưa có case triển khai nào</h4>
                                    <p class="text-muted mb-4">Bắt đầu bằng cách tạo case triển khai đầu tiên của bạn</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDeploymentCaseModal">
                                        <i class="fas fa-plus me-2"></i>
                                        Tạo Case đầu tiên
                                    </button>
                                </div>
                            <?php else: ?>
                                <!-- Cases Table -->
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 deployment-cases-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                                    </div>
                                                </th>
                                                <th scope="col">Số case</th>
                                                <th scope="col">Tiến trình</th>
                                                <th scope="col">Mô tả case</th>
                                                <th scope="col">Ghi chú</th>
                                                <th scope="col">Người phụ trách</th>
                                                <th scope="col">Ngày bắt đầu</th>
                                                <th scope="col">Ngày kết thúc</th>
                                                <th scope="col">Trạng thái</th>
                                                <th scope="col">Trạng thái tiến độ</th>
                                                <th scope="col">Tổng số task</th>
                                                <th scope="col">Task hoàn thành</th>
                                                <th scope="col">Tiến độ</th>
                                                <th scope="col">Hình thức</th>
                                                <th scope="col">Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($cases as $case): ?>
                                                <tr data-status="<?php echo htmlspecialchars($case['status']); ?>">
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" 
                                                                   value="<?php echo $case['id']; ?>">
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="case-number fw-bold text-primary">
                                                            <?php echo htmlspecialchars($case['case_number']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="progress-badge progress-<?php echo htmlspecialchars($case['progress']); ?>">
                                                            <?php 
                                                            $progressLabels = [
                                                                'CS' => 'CS - Chốt SOW',
                                                                'SH' => 'SH - Soạn hàng',
                                                                'GH' => 'GH - Giao hàng',
                                                                'TK' => 'TK - Triển khai',
                                                                'NT' => 'NT - Nghiệm thu'
                                                            ];
                                                            echo $progressLabels[$case['progress']] ?? $case['progress'];
                                                            ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="text-truncate-custom" 
                                                             title="<?php echo htmlspecialchars($case['case_description']); ?>">
                                                            <?php echo htmlspecialchars($case['case_description']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="text-truncate-custom" 
                                                             title="<?php echo htmlspecialchars($case['notes']); ?>">
                                                            <?php echo htmlspecialchars($case['notes'] ?: '-'); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="text-truncate-custom" 
                                                             title="<?php echo htmlspecialchars($case['assigned_to_name'] ?? 'Chưa phân công'); ?>">
                                                            <?php echo htmlspecialchars($case['assigned_to_name'] ?? 'Chưa phân công'); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php if ($case['start_date']): ?>
                                                            <span class="case-date">
                                                                <?php echo date('d/m/Y', strtotime($case['start_date'])); ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($case['due_date']): ?>
                                                            <span class="case-date">
                                                                <?php echo date('d/m/Y', strtotime($case['due_date'])); ?>
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
                                                        <span class="badge bg-secondary">
                                                            <?php echo htmlspecialchars($case['progress_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold">
                                                            <?php echo $case['total_tasks']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-bold text-success">
                                                            <?php echo $case['completed_tasks']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="progress-bar-custom me-2" style="width: 60px;">
                                                                <div class="progress-fill" style="width: <?php echo $case['progress_percentage']; ?>%;"></div>
                                                            </div>
                                                            <small class="fw-bold"><?php echo $case['progress_percentage']; ?>%</small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="priority-badge priority-<?php echo htmlspecialchars($case['priority']); ?>">
                                                            <?php echo ucfirst(htmlspecialchars($case['priority'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="task_deployment_cases.php?id=<?php echo $case['id']; ?>" class="btn btn-sm btn-outline-primary" title="Xem chi tiết">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                                    title="Chỉnh sửa" onclick="editCase(<?php echo $case['id']; ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <?php if ($case['status'] !== 'completed'): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                                    title="Đánh dấu hoàn thành" onclick="markAsCompleted(<?php echo $case['id']; ?>)">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <?php endif; ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                    title="Xóa" onclick="deleteCase(<?php echo $case['id']; ?>)">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- ===== MODAL THÊM CASE TRIỂN KHAI ===== -->
    <div class="modal fade" id="addDeploymentCaseModal" tabindex="-1" aria-labelledby="addDeploymentCaseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <form id="addDeploymentCaseForm" autocomplete="off">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addDeploymentCaseModalLabel">
                            <i class="fas fa-plus-circle me-2"></i>Tạo Case triển khai mới
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" tabindex="-1"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Cột trái -->
                            <div class="col-md-6">
                                <!-- Số case -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Số case</label>
                                        </div>
                                        <div class="col-8">
                                            <input type="text" class="form-control" id="caseNumber" name="case_number" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Tiến trình -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Tiến trình <span class="text-danger">*</span></label>
                                        </div>
                                        <div class="col-8">
                                            <select class="form-select" id="progress" name="progress" required>
                                                <option value="" class="placeholder-option">Chọn tiến trình</option>
                                                <option value="CS">CS - Chốt SOW</option>
                                                <option value="SH">SH - Soạn hàng</option>
                                                <option value="GH">GH - Giao hàng</option>
                                                <option value="TK">TK - Triển khai</option>
                                                <option value="NT">NT - Nghiệm thu</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Người nhập -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Người nhập</label>
                                        </div>
                                        <div class="col-8">
                                            <input type="text" class="form-control" id="createdBy" name="created_by" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Người phụ trách -->
                                <div class="mb-3">
                                    <div class="row align-items-center">
                                        <div class="col-4">
                                            <label class="form-label mb-0 fw-semibold">Người phụ trách <span class="text-danger">*</span></label>
                                        </div>
                                        <div class="col-8">
                                            <select class="form-select" id="assignedTo" name="assigned_to" required>
                                                <option value="" class="placeholder-option">Chọn nhân viên</option>
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

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Alert JS -->
    <script src="assets/js/alert.js?v=<?php echo filemtime('assets/js/alert.js'); ?>"></script>
    
    <script>
        $(document).ready(function() {
            // Load danh sách nhân viên IT Dept
            loadITStaff();
            
            // Generate case number
            generateCaseNumber();
            
            // Set current user as created by
            setCurrentUser();
            
            // Form submission
            $('#addDeploymentCaseForm').on('submit', function(e) {
                e.preventDefault();
                submitForm();
            });
            
            // Tooltip functionality
            $('.tooltip-icon').on('mouseenter', function() {
                const tooltipText = $(this).data('tooltip');
                if (tooltipText) {
                    const tooltip = $('<div class="custom-tooltip"></div>').html(tooltipText);
                    $('body').append(tooltip);
                    
                    const iconPos = $(this).offset();
                    const iconWidth = $(this).outerWidth();
                    
                    tooltip.css({
                        left: iconPos.left + iconWidth + 10,
                        top: iconPos.top + $(this).outerHeight() / 2
                    });
                    
                    if (tooltipText.includes('<br>')) {
                        tooltip.addClass('multiline');
                    }
                    
                    setTimeout(() => tooltip.addClass('show'), 10);
                }
            });
            
            $('.tooltip-icon').on('mouseleave', function() {
                $('.custom-tooltip').remove();
            });
            
            // Search functionality
            $('#caseSearchInput').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                $('.deployment-cases-table tbody tr').each(function() {
                    const text = $(this).text().toLowerCase();
                    $(this).toggle(text.includes(searchTerm));
                });
            });
            
            // Status filter
            $('#statusFilter').on('change', function() {
                const status = $(this).val();
                if (status) {
                    $('.deployment-cases-table tbody tr').each(function() {
                        const rowStatus = $(this).data('status');
                        $(this).toggle(rowStatus === status);
                    });
                } else {
                    $('.deployment-cases-table tbody tr').show();
                }
            });
            
            // Select all functionality
            $('#selectAll').on('change', function() {
                $('.deployment-cases-table tbody input[type="checkbox"]').prop('checked', this.checked);
            });
        });
        
        function showAddCaseModal() {
            $('#addDeploymentCaseModal').modal('show');
        }
        
        function loadITStaff() {
            $.ajax({
                url: 'api/get_staffs.php',
                method: 'GET',
                success: function(response) {
                    if (response.success && response.data && response.data.staffs) {
                        const itStaff = response.data.staffs.filter(staff => staff.department === 'IT Dept.');
                        const select = $('#assignedTo');
                        select.find('option:not(:first)').remove();
                        
                        itStaff.forEach(staff => {
                            select.append(`<option value="${staff.id}">${staff.fullname}</option>`);
                        });
                    } else {
                        console.error('Invalid response format:', response);
                        showAlert('Dữ liệu nhân viên không đúng định dạng', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    showAlert('Không thể tải danh sách nhân viên', 'error');
                }
            });
        }
        
        function generateCaseNumber() {
            const now = new Date();
            const year = now.getFullYear().toString().slice(-2);
            const month = (now.getMonth() + 1).toString().padStart(2, '0');
            
            // Get next sequence number
            $.ajax({
                url: 'api/get_next_case_number.php',
                method: 'GET',
                data: { type: 'deployment' },
                success: function(response) {
                    if (response.success) {
                        const sequence = response.sequence.toString().padStart(3, '0');
                        const caseNumber = `CTK.${year}${month}${sequence}`;
                        $('#caseNumber').val(caseNumber);
                    }
                },
                error: function() {
                    // Fallback
                    const caseNumber = `CTK.${year}${month}001`;
                    $('#caseNumber').val(caseNumber);
                }
            });
        }
        
        function setCurrentUser() {
            // This should be set from session data
            $('#createdBy').val('<?php echo getCurrentUserFullname(); ?>');
        }
        
        function submitForm() {
            const formData = new FormData($('#addDeploymentCaseForm')[0]);
            
            // Map field names to match API expectations
            const caseDescription = $('#issueTitle').val() + '\n\n' + $('#issueDescription').val();
            formData.set('case_description', caseDescription);
            
            $.ajax({
                url: 'api/create_deployment_case.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showAlert('Tạo case triển khai thành công!', 'success');
                        $('#addDeploymentCaseModal').modal('hide');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showAlert(response.error || 'Có lỗi xảy ra khi tạo case', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    showAlert('Có lỗi xảy ra khi tạo case', 'error');
                }
            });
        }
        
        function viewCase(id) {
            // TODO: Implement view case functionality
            showAlert('Tính năng xem chi tiết đang phát triển', 'info');
        }
        
        function editCase(id) {
            // TODO: Implement edit case functionality
            showAlert('Tính năng chỉnh sửa đang phát triển', 'info');
        }
        
        function markAsCompleted(id) {
            // TODO: Implement mark as completed functionality
            showAlert('Tính năng đánh dấu hoàn thành đang phát triển', 'info');
        }
        
        function deleteCase(id) {
            // TODO: Implement delete case functionality
            showAlert('Tính năng xóa đang phát triển', 'info');
        }
    </script>
</body>
</html> 