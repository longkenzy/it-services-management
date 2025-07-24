<?php
// Trang quản lý Yêu cầu triển khai (Deployment Requests)
require_once 'includes/session.php';
requireLogin();
require_once 'config/db.php';

// Lấy danh sách yêu cầu triển khai từ database với thông tin chi tiết
$requests = [];
try {
    $sql = "SELECT 
                dr.*,
                pc.name as customer_name,
                pc.contact_person,
                pc.contact_phone,
                sale.fullname as sale_name,
                creator.fullname as created_by_name,
                (
                    SELECT COUNT(*) FROM deployment_cases dc WHERE dc.deployment_request_id = dr.id
                ) as total_cases,
                0 as total_tasks,
                0 as progress_percentage
            FROM deployment_requests dr
            LEFT JOIN partner_companies pc ON dr.customer_id = pc.id
            LEFT JOIN staffs sale ON dr.sale_id = sale.id
            LEFT JOIN staffs creator ON dr.created_by = creator.id
            ORDER BY dr.created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $requests = $stmt->fetchAll();
    
    // Debug: Log số lượng records tìm thấy
    error_log("Found " . count($requests) . " deployment requests");
    
    // Debug: Hiển thị thông tin nếu có debug parameter
    if (isset($_GET['debug'])) {
        echo "<!-- Debug: Found " . count($requests) . " records -->\n";
        foreach ($requests as $req) {
            echo "<!-- Record: " . $req['request_code'] . " -->\n";
        }
    }
} catch (PDOException $e) {
    error_log("Database error in deployment_requests.php: " . $e->getMessage());
    $requests = [];
}

// Lấy flash messages nếu có
$flash_messages = getFlashMessages();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_code'])) {
    $errors = [];
    $fields = [
        'request_code', 'po_number', 'no_contract_po', 'contract_type', 'request_detail_type',
        'email_subject_customer', 'email_subject_internal', 'expected_start', 'expected_end',
        'customer_id', 'contact_person', 'contact_phone', 'sale_id', 'requester_notes',
        'deployment_manager', 'deployment_status'
    ];
    $data = [];
    foreach ($fields as $f) {
        $data[$f] = trim($_POST[$f] ?? '');
    }
    if ($data['request_code'] === '' || $data['customer_id'] === '' || $data['sale_id'] === '' || $data['deployment_status'] === '') {
        $errors[] = 'Vui lòng nhập đầy đủ các trường bắt buộc.';
    }
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO deployment_requests (
            request_code, po_number, no_contract_po, contract_type, request_detail_type,
            email_subject_customer, email_subject_internal, expected_start, expected_end,
            customer_id, contact_person, contact_phone, sale_id, requester_notes, deployment_manager, deployment_status, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['request_code'],
            $data['po_number'],
            !empty($data['no_contract_po']) ? 1 : 0,
            $data['contract_type'],
            $data['request_detail_type'],
            $data['email_subject_customer'],
            $data['email_subject_internal'],
            $data['expected_start'],
            $data['expected_end'],
            $data['customer_id'],
            $data['contact_person'],
            $data['contact_phone'],
            $data['sale_id'],
            $data['requester_notes'],
            $data['deployment_manager'],
            $data['deployment_status'],
            $_SESSION['user_id'] ?? null
        ]);
        $success_message = 'Tạo yêu cầu triển khai thành công!';
        header('Location: deployment_requests.php?success=1');
        exit;
    }
}

// Lấy tên người dùng đang đăng nhập
// Lấy tên người dùng đang đăng nhập (ưu tiên bảng staffs, fallback sang users)
$fullname = 'Không xác định';
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT fullname FROM staffs WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    if ($row && !empty($row['fullname'])) {
        $fullname = $row['fullname'];
    } else {
        // Nếu không có trong staffs, thử lấy từ users
        $stmt2 = $pdo->prepare('SELECT fullname FROM users WHERE id = ?');
        $stmt2->execute([$_SESSION['user_id']]);
        $row2 = $stmt2->fetch();
        if ($row2 && !empty($row2['fullname'])) {
            $fullname = $row2['fullname'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <title>Yêu cầu triển khai - IT Services Management</title>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Custom Select2 styling */
        .select2-container--bootstrap-5 .select2-selection {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            min-height: 38px;
        }
        .select2-container--bootstrap-5 .select2-selection--single {
            padding: 0.375rem 0.75rem;
        }
        .select2-container--bootstrap-5 .select2-selection__rendered {
            color: #212529;
            line-height: 1.5;
        }
        .select2-container--bootstrap-5 .select2-selection__placeholder {
            color: #6c757d;
        }
        .select2-container--bootstrap-5 .select2-dropdown {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected] {
            background-color: #0d6efd;
            color: white;
        }
        
        /* Ensure Select2 search input is clickable and editable */
        .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field {
            width: 100% !important;
            padding: 0.375rem 0.75rem !important;
            border: 1px solid #dee2e6 !important;
            border-radius: 0.375rem !important;
            font-size: 1rem !important;
            line-height: 1.5 !important;
            color: #212529 !important;
            background-color: #fff !important;
            pointer-events: auto !important;
            cursor: text !important;
        }
        
        .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field:focus {
            outline: none !important;
            border-color: #86b7fe !important;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25) !important;
        }
        
        /* Fix z-index for dropdown */
        .select2-container--bootstrap-5.select2-container--open {
            z-index: 9999 !important;
        }
        
        .select2-dropdown {
            z-index: 9999 !important;
        }
        
        /* Additional fixes for Select2 */
        .select2-container {
            z-index: 9999 !important;
        }
        
        .select2-container--bootstrap-5 .select2-selection {
            z-index: 1 !important;
        }
        
        /* Ensure search input is not disabled */
        .select2-search__field {
            pointer-events: auto !important;
            user-select: text !important;
            -webkit-user-select: text !important;
            -moz-user-select: text !important;
            -ms-user-select: text !important;
        }
        
        /* Fix for modal z-index conflicts */
        .modal {
            z-index: 1050 !important;
        }
        
        .modal-backdrop {
            z-index: 1040 !important;
        }
    </style>
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo filemtime('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="assets/css/alert.css?v=<?php echo filemtime('assets/css/alert.css'); ?>">
    <link rel="stylesheet" href="assets/css/no-border-radius.css?v=<?php echo filemtime('assets/css/no-border-radius.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ===== MODAL STYLES ===== */
        /* Override Bootstrap modal-fullscreen */
        #addDeploymentRequestModal .modal-dialog,
        #editDeploymentRequestModal .modal-dialog,
        #createDeploymentCaseModal .modal-dialog,
        #editDeploymentCaseModal .modal-dialog,
        #createDeploymentTaskModal .modal-dialog {
            max-width: none;
            width: calc(100vw - 40px);
            margin: 80px auto 20px auto;
            height: calc(100vh - 120px);
        }
        
        .deployment-request-modal {
            border-radius: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        

        
        .deployment-request-modal .modal-header {
            background: linear-gradient(135deg, #07ff 0%, #0056b3 100);
            color: white;
            border-bottom:2px solid #dee2e6;
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
            border-top:2px solid #dee2e6;
            padding: 0.75rem 1.5rem;
        }
        
        /* ===== FORM STYLES ===== */
        .deployment-request-modal .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.25rem;
            font-size: 1rem;
        }
        
        .deployment-request-modal .form-control,
        .deployment-request-modal .form-select {
            border:2px solid #e9ecef;
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
        
        /* ===== CHECKBOX STYLES ===== */
        .deployment-request-modal .form-check {
            margin-top: 0.25rem;
        }
        
        .deployment-request-modal .form-check-input {
            border:2px solid #e9ecef;
        }
        
        .deployment-request-modal .form-check-input:checked {
            background-color: #07f;
            border-color: #7c7c7c;
        }
        
        .deployment-request-modal .form-check-label {
            font-size: 0.95rem;
            color: #6c757d;
            margin-left: 0.25rem;
        }
        
        /* ===== TEXTAREA STYLES ===== */
        .deployment-request-modal textarea.form-control {
            resize: vertical;
            min-height: 100px;
            height: 100px;
        }
        
        /* ===== BUTTON STYLES ===== */
        .deployment-request-modal .btn {
            padding: 0.5rem 1rem;
            font-weight: 600;
            border-radius: 0;
            font-size: 1rem;
        }
        
        .deployment-request-modal .btn-primary {
            background: linear-gradient(135deg, #07ff 0%, #0056b3 100);
            border: none;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
        }
        
        .deployment-request-modal .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        }
        
        .deployment-request-modal .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #545b62 100);
            border: none;
        }
        
        /* ===== FORM GROUP SPACING ===== */
        .deployment-request-modal .mb-3 {
            margin-bottom: 0.75rem;
        }
        
        .deployment-request-modal .row.g-4 {
            --bs-gutter-x: 1em;
            --bs-gutter-y: 0.5rem;
        }
        
        /* ===== RESPONSIVE STYLES ===== */
        @media (max-width: 768px) {
            #addDeploymentRequestModal .modal-dialog,
            #editDeploymentRequestModal .modal-dialog {
                width: calc(100vw - 20px);
                margin: 70px auto 10px auto; /* Top margin 70px để tránh header trên mobile */
                height: calc(100vh - 100px); /* Trừ đi chiều cao header và margin trên mobile */
            }
            
            .deployment-request-modal {
                height: 100%;
            }
            
            #editDeploymentRequestModal .deployment-request-modal {
                height: 100%;
            }
            
            .deployment-request-modal .modal-body {
                padding: 0.75em;
            }
            
            #editDeploymentRequestModal .deployment-request-modal .modal-body {
                padding: 0.75em;
            }
            
            .deployment-request-modal .row {
                margin: 0;
            }
            
            #editDeploymentRequestModal .deployment-request-modal .row {
                margin: 0;
            }
            
            .deployment-request-modal .col-md-6 {
                padding: 0;
                margin-bottom: 0.5em;
            }
            
            #editDeploymentRequestModal .deployment-request-modal .col-md-6 {
                padding: 0;
                margin-bottom: 0.5em;
            }
        }
        
        /* ===== SCROLLBAR STYLES ===== */
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
        
        /* ===== CARD STYLES ===== */
        .card-body {
            padding: 0 !important;
        }
        
        /* ===== PAGE HEADER STYLES ===== */
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .page-title i {
            color: #28a745;
        }
        
        /* ===== TABLE STYLES ===== */
        .table th {
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding: 0.75rem;
            font-size: 0.8rem;
        }
        
        .table td {
            padding: 0.5rem;
            vertical-align: middle;
            font-size: 0.8rem;
        }
        
        .table-light th,
        .table-light td {
            text-align: center;
            vertical-align: middle;
        }
        
        /* Căn giữa các cột cụ thể */
        .table td:nth-child(4),  /* Phụ trách */
        .table td:nth-child(7),  /* Trạng thái YC */
        .table td:nth-child(8),  /* Tổng số case */
        .table td:nth-child(9),  /* Tổng số task */
        .table td:nth-child(11) { /* Trạng thái triển khai */
            text-align: center;
            vertical-align: middle;
        }
        
        .badge {
            font-size: 0.7rem;
            padding: 0.4rem 0.6rem;
        }
        
        /* ===== ACTION BUTTONS ===== */
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
            margin: 0 0.25rem;
        }
        
        .btn-outline-primary:hover,
        .btn-outline-warning:hover,
        .btn-outline-danger:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }
        
        /* ===== TABLE STYLES ===== */
        .table th {
            font-weight: 600;
            color: #495057;
            background-color: #9ECAD6;
            border-bottom: 2px solid #dee2e6;
            border-right: 1px solid #dee2e6;
            padding: 0.5rem;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        
        .table th:last-child {
            border-right: none;
        }
        
        .table td {
            padding: 0.5rem;
            vertical-align: middle;
            font-size: 0.8rem;
            border-right: 1px solid #dee2e6;
        }
        
        .table td:last-child {
            border-right: none;
        }
        
        .table-light th,
        .table-light td {
            text-align: center;
            vertical-align: middle;
        }
        
        /* Căn giữa các cột cụ thể */
        .table td:nth-child(4),  /* Phụ trách */
        .table td:nth-child(7),  /* Trạng thái YC */
        .table td:nth-child(8),  /* Tổng số case */
        .table td:nth-child(9),  /* Tổng số task */
        .table td:nth-child(11) { /* Trạng thái triển khai */
            text-align: center;
            vertical-align: middle;
        }
        
        .customer-info {
            line-height: 1.4;
        }
        
        .contract-info {
            line-height: 1.3;
        }
        
        .progress {
            border-radius: 10px;
            overflow: hidden;
        }
        
        .progress-bar {
            font-size: 0.65rem;
            line-height: 18px;
            font-weight: 600;
        }
        
        .badge {
            font-size: 0.7rem;
            padding: 0.4rem 0.6rem;
        }
        
        .btn-group .btn {
            margin: 0 6px;
        }
        
        /* Giảm kích thước icon trong cột thao tác */
        .btn-group .btn i {
            font-size: 0.75rem;
            padding: 4px;
        }
        
        /* Override padding cho button outline warning */
        .btn-outline-warning {
            padding: 4px !important;
        }
        
        /* Override padding cho button outline danger */
        .btn-outline-danger {
            padding: 4px !important;
        }
        
        /* Responsive table */
        @media (max-width: 1200px) {
            .table-responsive {
                font-size: 0.75rem;
            }
            
            .table th,
            .table td {
                padding: 0.5rem;
            }
        }
        
        /* Đảm bảo modal tạo task hiển thị trên cùng */
        #createDeploymentTaskModal {
            z-index: 1060 !important;
        }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<main class="main-content">
    <div class="container-fluid px-4 py-4">
        <div class="page-header mb-4">
            <div class="row align-items-center">
                <div class="col">
                    <h1 class="page-title mb-0">
                        <i class="fas fa-rocket me-3 text-success"></i>
                        Yêu cầu triển khai
                    </h1>
                    <p class="text-muted mb-0">Quản lý các yêu cầu triển khai dự án và hệ thống</p>
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" id="createRequestBtn" data-bs-toggle="modal" data-bs-target="#addDeploymentRequestModal">
                        <i class="fas fa-plus me-2"></i>
                        Tạo yêu cầu triển khai
                    </button>
                </div>
            </div>
        </div>
        <!-- Table hiển thị danh sách yêu cầu triển khai -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($requests)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Chưa có yêu cầu triển khai nào</h5>
                        <p class="text-muted">Bấm nút "Tạo yêu cầu triển khai" để bắt đầu</p>
                        <?php if (isset($_GET['debug'])): ?>
                            <div class="mt-3">
                                <small class="text-muted">Debug: <?php echo count($requests); ?> records found</small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Mã YC</th>
                                    <th>Loại HĐ</th>
                                    <th>Khách hàng</th>
                                    <th>Phụ trách</th>
                                    <th>Thời hạn triển khai</th>
                                    <th>Ghi chú</th>
                                    <th>Trạng thái YC</th>
                                    <th>Tổng số case</th>
                                    <th>Tổng số task</th>
                                    <th>Tiến độ (%)</th>
                                    <th>Trạng thái triển khai</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="deployment-requests-table">
                                <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td>
                                        <strong class="text-primary"><?php echo htmlspecialchars($request['request_code']); ?></strong>
                                    </td>
                                    <td>
                                        <div class="contract-info">
                                            <div class="fw-bold"><?php echo htmlspecialchars($request['contract_type'] ?? 'N/A'); ?></div>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($request['request_detail_type'] ?? 'N/A'); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="customer-info">
                                            <div class="fw-bold"><?php echo htmlspecialchars($request['customer_name'] ?? 'N/A'); ?></div>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($request['contact_person'] ?? 'N/A'); ?>
                                            </small><br>
                                            <small class="text-muted">
                                                <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($request['contact_phone'] ?? 'N/A'); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-dark"><?php echo htmlspecialchars($request['sale_name'] ?? 'N/A');?></span>
                                    </td>
                                    <td>
                                        <?php if ($request['expected_start'] && $request['expected_end']): ?>
                                            <div class="text-wrap" style="white-space: pre-line;">
                                                <strong>Từ</strong><br>
                                                <?php echo date('d/m/Y', strtotime($request['expected_start'])); ?><br>
                                                <strong>Đến</strong><br>
                                                <?php echo date('d/m/Y', strtotime($request['expected_end'])); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Chưa có</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($request['requester_notes'])): ?>
                                            <div class="text-wrap" style="max-width: 200px; white-space: pre-wrap; word-wrap: break-word;">
                                                <?php echo htmlspecialchars($request['requester_notes']); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-dark"><?php echo htmlspecialchars($request['deployment_status']); ?></span>
                                    </td>
                                    <td>
                                        <span class="text-dark"><?php echo $request['total_cases'] ?? 0; ?></span>
                                    </td>
                                    <td>
                                        <span class="text-dark"><?php echo $request['total_tasks'] ?? 0; ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        $progress = $request['progress_percentage'] ?? 0;
                                        $progressClass = $progress >= 80 ? 'success' : ($progress >= 50 ? 'warning' : 'danger');
                                        ?>
                                        <div class="progress" style="width: 80px; height: 20px;">
                                            <div class="progress-bar bg-<?php echo $progressClass; ?>" 
                                                 style="width: <?php echo $progress; ?>%" 
                                                 title="<?php echo $progress; ?>%">
                                                <small><?php echo $progress; ?>%</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        switch ($request['deployment_status']) {
                                            case 'Hoàn thành':
                                                $statusClass = 'success';
                                                break;
                                            case 'Đang xử lý':
                                                $statusClass = 'warning';
                                                break;
                                            case 'Huỷ':
                                                $statusClass = 'danger';
                                                break;
                                            default:
                                                $statusClass = 'secondary';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($request['deployment_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-warning" onclick="editRequest(<?php echo $request['id']; ?>)" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteRequest(<?php echo $request['id']; ?>)" title="Xóa">
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
</main>
<!-- Modal tạo yêu cầu triển khai -->
<div class="modal fade" id="addDeploymentRequestModal" tabindex="-1" aria-labelledby="addDeploymentRequestModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content deployment-request-modal">
      <div class="modal-header">
        <h5 class="modal-title" id="addDeploymentRequestModalLabel">Tạo yêu cầu triển khai</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="addDeploymentRequestForm" method="POST" action="#">
        <div class="modal-body">
          <div class="row g-4">
            <!-- Cột trái: Hợp đồng -->
            <div class="col-md-6">
              <h6 class="text-primary mb-3"><i class="fas fa-file-contract me-2"></i>HỢP ĐỒNG</h6>
              
              <div class="mb-3">
                <label class="form-label">Mã yêu cầu:</label>
                <input type="text" class="form-control" name="request_code" id="request_code" readonly value="YC<?php echo date('y').date('m'); ?>001">
              </div>
              
              <div class="mb-3">
                <label class="form-label">Số hợp đồng PO:</label>
                <input type="text" class="form-control" name="po_number" id="po_number" placeholder="Nhập số hợp đồng PO">
                <div class="form-check mt-1">
                  <input class="form-check-input" type="checkbox" value="1" id="no_contract_po" name="no_contract_po">
                  <label class="form-check-label" for="no_contract_po">Không có HĐ/PO</label>
                </div>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Loại hợp đồng:</label>
                <select class="form-select" name="contract_type" id="contract_type">
                  <option value="">-- Chọn loại hợp đồng --</option>
                  <option value="Hợp đồng cung cấp dịch vụ">Hợp đồng cung cấp dịch vụ</option>
                  <option value="Hợp đồng bảo trì hệ thống">Hợp đồng bảo trì hệ thống</option>
                  <option value="Hợp đồng phát triển phần mềm">Hợp đồng phát triển phần mềm</option>
                  <option value="Hợp đồng tư vấn công nghệ">Hợp đồng tư vấn công nghệ</option>
                  <option value="Hợp đồng triển khai dự án">Hợp đồng triển khai dự án</option>
                  <option value="Hợp đồng hỗ trợ kỹ thuật">Hợp đồng hỗ trợ kỹ thuật</option>
                  <option value="Hợp đồng đào tạo">Hợp đồng đào tạo</option>
                  <option value="Hợp đồng gia hạn dịch vụ">Hợp đồng gia hạn dịch vụ</option>
                  <option value="Hợp đồng nâng cấp hệ thống">Hợp đồng nâng cấp hệ thống</option>
                  <option value="Hợp đồng tích hợp hệ thống">Hợp đồng tích hợp hệ thống</option>
                </select>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Loại yêu cầu chi tiết:</label>
                <select class="form-select" name="request_detail_type" id="request_detail_type">
                  <option value="">-- Chọn loại yêu cầu chi tiết --</option>
                  <option value="Triển khai hệ thống mới">Triển khai hệ thống mới</option>
                  <option value="Nâng cấp hệ thống hiện có">Nâng cấp hệ thống hiện có</option>
                  <option value="Tích hợp hệ thống bên thứ 3">Tích hợp hệ thống bên thứ 3</option>
                  <option value="Cấu hình và tối ưu hóa">Cấu hình và tối ưu hóa</option>
                  <option value="Di chuyển dữ liệu">Di chuyển dữ liệu</option>
                  <option value="Sao lưu và khôi phục">Sao lưu và khôi phục</option>
                  <option value="Bảo mật và phân quyền">Bảo mật và phân quyền</option>
                  <option value="Đào tạo người dùng">Đào tạo người dùng</option>
                  <option value="Hỗ trợ kỹ thuật">Hỗ trợ kỹ thuật</option>
                  <option value="Bảo trì định kỳ">Bảo trì định kỳ</option>
                  <option value="Khắc phục sự cố">Khắc phục sự cố</option>
                  <option value="Tư vấn và đánh giá">Tư vấn và đánh giá</option>
                </select>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Email subject (Khách hàng):</label>
                <input type="text" class="form-control" name="email_subject_customer" id="email_subject_customer" placeholder="Nhập email subject cho khách hàng">
              </div>
              
              <div class="mb-3">
                <label class="form-label">Email subject (Nội bộ):</label>
                <input type="text" class="form-control" name="email_subject_internal" id="email_subject_internal" placeholder="Nhập email subject cho nội bộ">
              </div>
              
              <div class="mb-3">
                <label class="form-label">Bắt đầu dự kiến:</label>
                <input type="date" class="form-control" name="expected_start" id="expected_start">
              </div>
              
              <div class="mb-3">
                <label class="form-label">Kết thúc dự kiến:</label>
                <input type="date" class="form-control" name="expected_end" id="expected_end">
              </div>
            </div>
            
            <!-- Cột phải: Khách hàng & Xử lý -->
            <div class="col-md-6">
              <h6 class="text-success mb-3"><i class="fas fa-users me-2"></i>KHÁCH HÀNG</h6>
              <div class="mb-3">
                <label class="form-label">Khách hàng:</label>
                <select class="form-select" name="customer_id" id="customer_id">
                  <option value="">-- Chọn khách hàng --</option>
                  <?php
                  $partners = $pdo->query("SELECT id, name, contact_person, contact_phone FROM partner_companies ORDER BY name ASC")->fetchAll();
                  foreach ($partners as $partner) {
                    echo '<option value="'.$partner['id'].'">'.htmlspecialchars($partner['name']).'</option>';
                  }
                  ?>
                </select>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Người liên hệ:</label>
                <input type="text" class="form-control" name="contact_person" id="contact_person" readonly placeholder="Sẽ tự động điền theo khách hàng">
              </div>
              
              <div class="mb-3">
                <label class="form-label">Điện thoại:</label>
                <input type="text" class="form-control" name="contact_phone" id="contact_phone" readonly placeholder="Sẽ tự động điền theo khách hàng">
              </div>
              
              <h6 class="text-warning mb-3 mt-4"><i class="fas fa-cogs me-2"></i>XỬ LÝ</h6>
              <div class="mb-3">
                <label class="form-label">Sale phụ trách:</label>
                <select class="form-select" name="sale_id" id="sale_id">
                  <option value="">-- Chọn sale phụ trách --</option>
                  <?php
                  $sales = $pdo->query("SELECT id, fullname FROM staffs WHERE department = 'SALE Dept.' AND status = 'active' ORDER BY fullname ASC")->fetchAll();
                  
                  if (empty($sales)) {
                    echo '<option value="">-- Không có nhân viên SALE Dept --</option>';
                  } else {
                    foreach ($sales as $sale) {
                      echo '<option value="'.$sale['id'].'">'.htmlspecialchars($sale['fullname']).' ID: '.$sale['id'].'</option>';
                    }
                  }
                  ?>
                </select>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Ghi chú người yêu cầu:</label>
                <textarea class="form-control" name="requester_notes" id="requester_notes" rows="2" placeholder="Nhập ghi chú"></textarea>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Quản lý triển khai:</label>
                <input type="text" class="form-control" name="deployment_manager" id="deployment_manager" value="Trần Nguyễn Anh Khoa" readonly>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Trạng thái triển khai:</label>
                <select class="form-select" name="deployment_status" id="deployment_status">
                  <option value="Tiếp nhận">Tiếp nhận</option>
                  <option value="Đang xử lý">Đang xử lý</option>
                  <option value="Hoàn thành">Hoàn thành</option>
                  <option value="Huỷ">Huỷ</option>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary">Lưu yêu cầu</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="assets/js/alert.js?v=<?php echo filemtime('assets/js/alert.js'); ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Lưu trữ dữ liệu khách hàng và sale
    let partnerData = <?php echo json_encode($partners); ?>;
    let salesData = <?php echo json_encode($sales); ?>;
    
    // Khởi tạo Select2 cho trường khách hàng
    function initializeSelect2() {
        $('#customer_id').select2({
            theme: 'bootstrap-5',
            placeholder: '-- Chọn khách hàng --',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#addDeploymentRequestModal'),
            language: {
                noResults: function() {
                    return "Không tìm thấy khách hàng";
                },
                searching: function() {
                    return "Đang tìm kiếm...";
                }
            }
        });
        
        $('#sale_id').select2({
            theme: 'bootstrap-5',
            placeholder: '-- Chọn sale phụ trách --',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#addDeploymentRequestModal'),
            language: {
                noResults: function() {
                    return "Không tìm thấy sale phụ trách";
                },
                searching: function() {
                    return "Đang tìm kiếm...";
                }
            }
        });
    }
    
    // Khởi tạo Select2 khi modal hiển thị
    $('#addDeploymentRequestModal').on('shown.bs.modal', function() {
        initializeSelect2();
    });
    
    // Khởi tạo Select2 ngay lập tức nếu cần
    initializeSelect2();
    

    
    // Xử lý khi chọn khách hàng (Select2)
    $('#customer_id').on('select2:select', function(e) {
        const customerId = e.params.data.id;
        const contactPersonInput = document.getElementById('contact_person');
        const contactPhoneInput = document.getElementById('contact_phone');
        
        // Reset người liên hệ và điện thoại
        contactPersonInput.value = '';
        contactPhoneInput.value = '';
        
        if (customerId) {
            // Tìm thông tin khách hàng từ dữ liệu đã có
            const selectedPartner = partnerData.find(partner => partner.id == customerId);
            if (selectedPartner) {
                contactPersonInput.value = selectedPartner.contact_person || 'Chưa có thông tin';
                contactPhoneInput.value = selectedPartner.contact_phone || 'Chưa có thông tin';
            }
        }
    });
    
    // Xử lý khi xóa lựa chọn khách hàng
    $('#customer_id').on('select2:clear', function(e) {
        const contactPersonInput = document.getElementById('contact_person');
        const contactPhoneInput = document.getElementById('contact_phone');
        
        // Reset người liên hệ và điện thoại
        contactPersonInput.value = '';
        contactPhoneInput.value = '';
    });
    

    
    // Xử lý khi chọn sale phụ trách (Select2)
    $('#sale_id').on('select2:select', function(e) {
        const saleId = e.params.data.id;
        if (saleId) {
            const selectedSale = salesData.find(sale => sale.id == saleId);
            if (selectedSale) {
        
            }
        }
    });
    
    // Xử lý checkbox "Không có HĐ/PO"
    function setupCheckboxHandler() {
        const checkbox = document.getElementById('no_contract_po');
        const poInput = document.getElementById('po_number');
        
        if (checkbox && poInput) {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    poInput.value = '';
                    poInput.disabled = true;
                } else {
                    poInput.disabled = false;
                }
            });
        }
    }
    
    // Tạo mã yêu cầu tự động
    function generateRequestCode() {
        fetch('api/get_next_request_number.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('request_code').value = data.request_code;
                } else {
                    // Fallback nếu API lỗi
                    const year = new Date().getFullYear().toString().slice(-2);
                    const month = (new Date().getMonth() + 1).toString().padStart(2, '0');
                    document.getElementById('request_code').value = `YC${year}${month}001`;
                }
            })
            .catch(error => {
                // Fallback nếu API lỗi
                const year = new Date().getFullYear().toString().slice(-2);
                const month = (new Date().getMonth() + 1).toString().padStart(2, '0');
                document.getElementById('request_code').value = `YC${year}${month}001`;
            });
    }
    
    // Cập nhật mã yêu cầu khi mở modal
    const modal = document.getElementById('addDeploymentRequestModal');
    if (modal) {
        // Xóa event listener cũ nếu có
        modal.removeEventListener('shown.bs.modal', modalShowHandler);
        
        function modalShowHandler() {
            generateRequestCode();
            setupCheckboxHandler();
        }
        
        modal.addEventListener('shown.bs.modal', modalShowHandler);
    }
    
    // Xử lý submit form
    document.getElementById('addDeploymentRequestForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validation
        const requiredFields = ['customer_id', 'sale_id', 'deployment_status'];
        let isValid = true;
        
        requiredFields.forEach(function(fieldId) {
            const field = document.getElementById(fieldId);
            const value = field.value;
            if (!value) {
                isValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            if (typeof showAlert === 'function') {
                showAlert('Vui lòng điền đầy đủ các trường bắt buộc', 'error');
            } else {
                alert('Vui lòng điền đầy đủ các trường bắt buộc');
            }
            return;
        }
        
        // Submit form
        const formData = new FormData(this);
        
        console.log(formData);
        
        fetch('api/create_deployment_request.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof showAlert === 'function') {
                    showAlert('Tạo yêu cầu triển khai thành công!', 'success');
                } else {
                    alert('Tạo yêu cầu triển khai thành công!');
                }
                const modal = bootstrap.Modal.getInstance(document.getElementById('addDeploymentRequestModal'));
                if (modal) {
                    modal.hide();
                }
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                if (typeof showAlert === 'function') {
                    showAlert(data.error || 'Có lỗi xảy ra khi tạo yêu cầu', 'error');
                } else {
                    alert(data.error || 'Có lỗi xảy ra khi tạo yêu cầu');
                }
            }
        })
        .catch(error => {
            if (typeof showAlert === 'function') {
                showAlert('Có lỗi xảy ra khi tạo yêu cầu', 'error');
            } else {
                alert('Có lỗi xảy ra khi tạo yêu cầu');
            }
        });
    });
});


// ===== EDIT REQUEST FUNCTIONS =====
function editRequest(requestId) {
    // Lấy thông tin request
    fetch(`api/get_deployment_request.php?id=${requestId}`)
        .then(response => response.json())
        .then(data => {
                            if (data.success) {
                    const request = data.data;
                    // Debug: Log toàn bộ dữ liệu request
            
                
                                    // Điền dữ liệu vào form edit
                    document.getElementById('edit_request_id').value = request.id;
                    document.getElementById('edit_request_code').value = request.request_code;
                    document.getElementById('edit_po_number').value = request.po_number || '';
                    document.getElementById('edit_no_contract_po').checked = request.no_contract_po == 1;
                    
                    // Xử lý disable/enable trường PO number dựa trên checkbox
                    const editPoInput = document.getElementById('edit_po_number');
                    if (request.no_contract_po == 1) {
                        editPoInput.disabled = true;
                    } else {
                        editPoInput.disabled = false;
                    }
                    document.getElementById('edit_contract_type').value = request.contract_type || '';
                    

                    
                    document.getElementById('edit_email_subject_customer').value = request.email_subject_customer || '';
                    document.getElementById('edit_email_subject_internal').value = request.email_subject_internal || '';
                    document.getElementById('edit_expected_start').value = request.expected_start || '';
                    document.getElementById('edit_expected_end').value = request.expected_end || '';
                    

                    
                    document.getElementById('edit_contact_person').value = request.contact_person || '';
                    document.getElementById('edit_contact_phone').value = request.contact_phone || '';
                    

                    
                    document.getElementById('edit_requester_notes').value = request.requester_notes || '';
                    document.getElementById('edit_deployment_manager').value = request.deployment_manager || '';
                    

                
                // Hiển thị modal edit
                const editModal = new bootstrap.Modal(document.getElementById('editDeploymentRequestModal'));
                
                // Lưu dữ liệu để set sau khi modal hiển thị
                const requestData = request;
                
                // Đợi modal hiển thị xong rồi set values
                document.getElementById('editDeploymentRequestModal').addEventListener('shown.bs.modal', function() {
            
                    
                    // Set values sau khi modal đã hiển thị
                    const detailTypeSelect = document.getElementById('edit_request_detail_type');
                    const customerSelect = document.getElementById('edit_customer_id');
                    const saleSelect = document.getElementById('edit_sale_id');
                    const statusSelect = document.getElementById('edit_deployment_status');
                    

                    
                    if (detailTypeSelect) {
                        detailTypeSelect.value = requestData.request_detail_type || '';

                    }
                    
                    if (customerSelect) {
                        customerSelect.value = requestData.customer_id || '';

                    }
                    
                    if (saleSelect) {
                        saleSelect.value = requestData.sale_id || '';

                    }
                    
                    if (statusSelect) {
                        statusSelect.value = requestData.deployment_status || '';

                    }
                    
                    // Force update UI
                    setTimeout(() => {
                        if (detailTypeSelect) detailTypeSelect.dispatchEvent(new Event('change'));
                        if (customerSelect) customerSelect.dispatchEvent(new Event('change'));
                        if (saleSelect) saleSelect.dispatchEvent(new Event('change'));
                        if (statusSelect) statusSelect.dispatchEvent(new Event('change'));

                    }, 100);
                    
                    // Load dữ liệu case triển khai ngay khi modal edit hiển thị
                    setTimeout(() => {
                        loadDeploymentCases(requestData.id);
                    }, 200);
                }, { once: true });
                
                editModal.show();
                
                // Thêm event listener cho checkbox "Không có HĐ/PO" trong form edit
                const editCheckbox = document.getElementById('edit_no_contract_po');
                
                if (editCheckbox && editPoInput) {
                    editCheckbox.addEventListener('change', function() {
                        if (this.checked) {
                            editPoInput.value = '';
                            editPoInput.disabled = true;
                        } else {
                            editPoInput.disabled = false;
                        }
                    });
                }

            } else {
                if (typeof showAlert === 'function') {
                    showAlert(data.error || 'Không thể lấy thông tin yêu cầu', 'error');
                } else {
                    alert(data.error || 'Không thể lấy thông tin yêu cầu');
                }
            }
        })
        .catch(error => {
            if (typeof showAlert === 'function') {
                showAlert('Có lỗi xảy ra khi lấy thông tin yêu cầu', 'error');
            } else {
                alert('Có lỗi xảy ra khi lấy thông tin yêu cầu');
            }
        });
}

// Xử lý submit form edit
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('editDeploymentRequestForm').addEventListener('submit', function(e) {
        console.log('=== Edit deployment request form submit triggered ===');
        e.preventDefault();
        
        // Kiểm tra xem form có bị disable tạm thời không
        if (this.getAttribute('data-submit-disabled') === 'true') {
            console.log('Edit deployment request form temporarily disabled, ignoring submit');
            return;
        }
        
        // Validation
        const requiredFields = ['edit_customer_id', 'edit_sale_id', 'edit_deployment_status'];
        let isValid = true;
        
        requiredFields.forEach(function(fieldId) {
            const field = document.getElementById(fieldId);
            const value = field.value;
            if (!value) {
                isValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            if (typeof showAlert === 'function') {
                showAlert('Vui lòng điền đầy đủ các trường bắt buộc', 'error');
            } else {
                alert('Vui lòng điền đầy đủ các trường bắt buộc');
            }
            return;
        }
        
        // Chuẩn bị dữ liệu
        const formData = {
            id: document.getElementById('edit_request_id').value,
            request_code: document.getElementById('edit_request_code').value,
            po_number: document.getElementById('edit_po_number').value,
            no_contract_po: document.getElementById('edit_no_contract_po').checked ? 1 : 0,
            contract_type: document.getElementById('edit_contract_type').value,
            request_detail_type: document.getElementById('edit_request_detail_type').value,
            email_subject_customer: document.getElementById('edit_email_subject_customer').value,
            email_subject_internal: document.getElementById('edit_email_subject_internal').value,
            expected_start: document.getElementById('edit_expected_start').value,
            expected_end: document.getElementById('edit_expected_end').value,
            customer_id: document.getElementById('edit_customer_id').value,
            contact_person: document.getElementById('edit_contact_person').value,
            contact_phone: document.getElementById('edit_contact_phone').value,
            sale_id: document.getElementById('edit_sale_id').value,
            requester_notes: document.getElementById('edit_requester_notes').value,
            deployment_manager: document.getElementById('edit_deployment_manager').value,
            deployment_status: document.getElementById('edit_deployment_status').value
        };
        
        // Debug: Log dữ liệu gửi đi

        
        // Submit form
        fetch('api/update_deployment_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(formData)
        })
        .then(response => {

            return response.json();
        })
        .then(data => {

            if (data.success) {
                if (typeof showAlert === 'function') {
                    showAlert('Cập nhật yêu cầu triển khai thành công!', 'success');
                } else {
                    alert('Cập nhật yêu cầu triển khai thành công!');
                }
                const modal = bootstrap.Modal.getInstance(document.getElementById('editDeploymentRequestModal'));
                if (modal) {
                    modal.hide();
                }
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                if (typeof showAlert === 'function') {
                    showAlert(data.error || 'Có lỗi xảy ra khi cập nhật yêu cầu', 'error');
                } else {
                    alert(data.error || 'Có lỗi xảy ra khi cập nhật yêu cầu');
                }
            }
        })
        .catch(error => {
            if (typeof showAlert === 'function') {
                showAlert('Có lỗi xảy ra khi cập nhật yêu cầu', 'error');
            } else {
                alert('Có lỗi xảy ra khi cập nhật yêu cầu');
            }
        });
    });
});

// Function tạo case triển khai
function createDeploymentCase() {
    const requestId = document.getElementById('edit_request_id').value;
    document.getElementById('deployment_request_id').value = requestId;
    // Clear table before showing modal to avoid showing old cases
    const tbody = document.getElementById('deployment-cases-table');
    if (tbody) tbody.innerHTML = '';
    const createCaseModal = new bootstrap.Modal(document.getElementById('createDeploymentCaseModal'));
    createCaseModal.show();
    generateCaseCode();
    // Always load cases for the correct requestId
    loadDeploymentCases(requestId);
}

// Function tạo mã case tự động
function generateCaseCode() {
    const currentDate = new Date();
    const year = currentDate.getFullYear().toString().slice(-2); // 2 số cuối năm
    const month = (currentDate.getMonth() + 1).toString().padStart(2, '0'); // Tháng với 2 chữ số
    
    // Gọi API để lấy số thứ tự case
    fetch('api/get_next_case_number.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            year: year,
            month: month
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('case_code').value = data.case_code;
        } else {
            // Fallback nếu API lỗi
            const caseCode = `CTK${year}${month}001`;
            document.getElementById('case_code').value = caseCode;
        }
    })
    .catch(error => {
        // Fallback nếu network lỗi
        const caseCode = `CTK${year}${month}001`;
        document.getElementById('case_code').value = caseCode;
    });
}

// Function load danh sách case triển khai
function loadDeploymentCases(requestId) {
    if (!requestId) return;

    console.log('Loading deployment cases for request ID:', requestId);

    // Fetch cases filtered by deployment_request_id
    fetch('api/get_deployment_cases.php?deployment_request_id=' + requestId)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('deployment-cases-table');
            if (!tbody) {
                console.error('Table body not found');
                return;
            }
            
            tbody.innerHTML = '';

            if (!data.success || !Array.isArray(data.data) || data.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="15" class="text-center text-muted py-3">
                  <i class='fas fa-inbox fa-2x mb-2'></i><br>Chưa có case triển khai nào
                </td></tr>`;
                return;
            }

            console.log('Found', data.data.length, 'deployment cases');

            // Populate table with filtered cases
            data.data.forEach((item, idx) => {
                tbody.innerHTML += `
                  <tr>
                    <td class='text-center'>${idx + 1}</td>
                    <td>${item.case_code || ''}</td>
                    <td>${item.progress || ''}</td>
                    <td>${item.case_description || ''}</td>
                    <td>${item.notes || ''}</td>
                    <td>${item.assigned_to_name || ''}</td>
                    <td>${formatDateForDisplay(item.start_date)}</td>
                    <td>${formatDateForDisplay(item.end_date)}</td>
                    <td>${item.status || ''}</td>
                    <td>${item.progress || ''}</td>
                    <td>${item.total_tasks || 0}</td>
                    <td>${item.completed_tasks || 0}</td>
                    <td>${item.progress_percentage || 0}%</td>
                    <td>${item.work_type || ''}</td>
                    <td>
                      <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="editDeploymentCase(${item.id}); return false;" title="Chỉnh sửa">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteDeploymentCase(${item.id}, ${requestId}); return false;" title="Xóa">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                `;
            });
        })
        .catch(error => {
            console.error('Error loading deployment cases:', error);
            const tbody = document.getElementById('deployment-cases-table');
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="15" class="text-center text-danger py-3">
                  <i class='fas fa-exclamation-triangle fa-2x mb-2'></i><br>Lỗi khi tải dữ liệu
                </td></tr>`;
            }
        });
}

// Xử lý form tạo case triển khai
document.addEventListener('DOMContentLoaded', function() {
    
    // Xử lý submit form tạo case
    document.getElementById('createDeploymentCaseForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validation
        const requiredFields = ['request_type', 'assigned_to', 'status'];
        let isValid = true;
        
        requiredFields.forEach(function(fieldId) {
            const field = document.getElementById(fieldId);
            const value = field.value;
            if (!value) {
                isValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        if (!isValid) {
            if (typeof showAlert === 'function') {
                showAlert('Vui lòng điền đầy đủ các trường bắt buộc', 'error');
            } else {
                alert('Vui lòng điền đầy đủ các trường bắt buộc');
            }
            return;
        }
        
        // Chuẩn bị dữ liệu
        const formData = {
            case_code: document.getElementById('case_code').value,
            request_type: document.getElementById('request_type').value,
            progress: document.getElementById('progress').value,
            case_description: document.getElementById('case_description').value,
            notes: document.getElementById('notes').value,
            assigned_to: document.getElementById('assigned_to').value,
            work_type: document.getElementById('work_type').value,
            start_date: document.getElementById('start_date').value,
            end_date: document.getElementById('end_date').value,
            status: document.getElementById('status').value,
            deployment_request_id: document.getElementById('deployment_request_id').value
        };
        formData.assigned_to = parseInt(formData.assigned_to, 10);
        formData.deployment_request_id = parseInt(formData.deployment_request_id, 10);
        
        // Submit form
        fetch('api/create_deployment_case.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (typeof showAlert === 'function') {
                    showAlert('Tạo case triển khai thành công!', 'success');
                } else {
                    alert('Tạo case triển khai thành công!');
                }
                const modal = bootstrap.Modal.getInstance(document.getElementById('createDeploymentCaseModal'));
                if (modal) modal.hide();
                loadDeploymentCases(formData.deployment_request_id);
                reloadDeploymentRequestsTable(); // cập nhật lại tổng số case
            } else {
                if (typeof showAlert === 'function') {
                    showAlert(data.error || 'Có lỗi xảy ra khi tạo case', 'error');
                } else {
                    alert(data.error || 'Có lỗi xảy ra khi tạo case');
                }
            }
        })
        .catch(error => {
            if (typeof showAlert === 'function') {
                showAlert('Có lỗi xảy ra khi tạo case', 'error');
            } else {
                alert('Có lỗi xảy ra khi tạo case');
            }
        });
    });
});



// Add event listener to load cases when the modal is shown
const createCaseModalElement = document.getElementById('createDeploymentCaseModal');
if (createCaseModalElement) {
    createCaseModalElement.addEventListener('shown.bs.modal', function () {
        const requestId = document.getElementById('edit_request_id').value;
        if (requestId) {
            loadDeploymentCases(requestId);
        }
    });
}

// Add event listener to load cases when edit modal is shown
const editModalElement = document.getElementById('editDeploymentRequestModal');
if (editModalElement) {
    editModalElement.addEventListener('shown.bs.modal', function () {
        const requestId = document.getElementById('edit_request_id').value;
        if (requestId) {
            // Load dữ liệu case triển khai ngay khi modal edit hiển thị
            setTimeout(() => {
                loadDeploymentCases(requestId);
            }, 300);
        }
    });
}

// Xử lý form edit case triển khai
document.addEventListener('DOMContentLoaded', function() {
    const editCaseForm = document.getElementById('editDeploymentCaseForm');
    if (editCaseForm) {
        let isSubmitting = false; // Flag để tránh submit nhầm
        
        editCaseForm.addEventListener('submit', function(e) {
            console.log('Edit case form submit event triggered');
            e.preventDefault();
            
            // Kiểm tra xem có phải là submit thực sự không
            const submitButton = e.submitter;
            const form = e.target;
            
            // Kiểm tra xem form có bị disable tạm thời không
            if (form.getAttribute('data-submit-disabled') === 'true') {
                console.log('Form submit temporarily disabled, ignoring');
                return;
            }
            
            if (!submitButton || submitButton.textContent.trim() !== 'Cập nhật case' || isSubmitting) {
                console.log('Not a real submit or already submitting, ignoring');
                return;
            }
            
            isSubmitting = true; // Set flag để tránh submit nhầm
            
            // Validation
            const requiredFields = ['edit_request_type', 'edit_assigned_to', 'edit_status'];
            let isValid = true;
            
            requiredFields.forEach(function(fieldId) {
                const field = document.getElementById(fieldId);
                const value = field.value;
                if (!value) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                if (typeof showAlert === 'function') {
                    showAlert('Vui lòng điền đầy đủ các trường bắt buộc', 'error');
                } else {
                    alert('Vui lòng điền đầy đủ các trường bắt buộc');
                }
                return;
            }
            
            // Chuẩn bị dữ liệu
            const formData = {
                id: document.getElementById('edit_case_id').value,
                case_code: document.getElementById('edit_case_code').value,
                request_type: document.getElementById('edit_request_type').value,
                progress: document.getElementById('edit_progress').value,
                case_description: document.getElementById('edit_case_description').value,
                notes: document.getElementById('edit_notes').value,
                assigned_to: document.getElementById('edit_assigned_to').value,
                work_type: document.getElementById('edit_work_type').value,
                start_date: document.getElementById('edit_start_date').value,
                end_date: document.getElementById('edit_end_date').value,
                status: document.getElementById('edit_status').value
            };
            
            console.log('Submitting edit case form with data:', formData);
            
            // Submit form
            fetch('api/update_deployment_case.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof showAlert === 'function') {
                        showAlert('Cập nhật case triển khai thành công!', 'success');
                    } else {
                        alert('Cập nhật case triển khai thành công!');
                    }
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editDeploymentCaseModal'));
                    if (modal) modal.hide();
                    // Reload danh sách case
                    const requestId = document.getElementById('edit_request_id').value;
                    if (requestId) {
                        loadDeploymentCases(requestId);
                        reloadDeploymentRequestsTable(); // cập nhật lại tổng số case
                    }
                } else {
                    if (typeof showAlert === 'function') {
                        showAlert(data.error || 'Có lỗi xảy ra khi cập nhật case', 'error');
                    } else {
                        alert(data.error || 'Có lỗi xảy ra khi cập nhật case');
                    }
                }
                isSubmitting = false; // Reset flag
            })
            .catch(error => {
                if (typeof showAlert === 'function') {
                    showAlert('Có lỗi xảy ra khi cập nhật case', 'error');
                } else {
                    alert('Có lỗi xảy ra khi cập nhật case');
                }
                isSubmitting = false; // Reset flag
            });
        });
    }
    

});

// ===== DEPLOYMENT CASE FUNCTIONS =====

// Function format datetime cho input datetime-local
function formatDateTimeForInput(dateTimeString) {
    if (!dateTimeString) return '';
    
    const date = new Date(dateTimeString);
    if (isNaN(date.getTime())) return '';
    
    // Format: YYYY-MM-DDTHH:MM
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:${minutes}`;
}

// Function format date theo định dạng dd/MM/yyyy
function formatDateForDisplay(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '';
    
    // Format: dd/MM/yyyy
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    
    return `${day}/${month}/${year}`;
}

// Function chỉnh sửa case triển khai
function editDeploymentCase(caseId) {
    console.log('=== editDeploymentCase called with ID:', caseId, '===');
    
    // Ngăn chặn event bubbling
    event.preventDefault();
    event.stopPropagation();
    
    // Lấy thông tin case
    fetch(`api/get_case_details.php?id=${caseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const caseData = data.data;
                
                // Điền dữ liệu vào form edit case
                document.getElementById('edit_case_id').value = caseData.id;
                document.getElementById('edit_case_code').value = caseData.case_code || '';
                document.getElementById('edit_request_type').value = caseData.request_type || '';
                document.getElementById('edit_progress').value = caseData.progress || '';
                document.getElementById('edit_case_description').value = caseData.case_description || '';
                document.getElementById('edit_notes').value = caseData.notes || '';
                document.getElementById('edit_assigned_to').value = caseData.assigned_to || '';
                document.getElementById('edit_work_type').value = caseData.work_type || '';
                
                // Format datetime cho input datetime-local
                document.getElementById('edit_start_date').value = caseData.start_date ? formatDateTimeForInput(caseData.start_date) : '';
                document.getElementById('edit_end_date').value = caseData.end_date ? formatDateTimeForInput(caseData.end_date) : '';
                
                document.getElementById('edit_status').value = caseData.status || '';
                
                            // Hiển thị modal edit case
            const editCaseModal = new bootstrap.Modal(document.getElementById('editDeploymentCaseModal'));
            
            // Tạm thời disable form edit deployment request để tránh submit nhầm
            const editRequestForm = document.getElementById('editDeploymentRequestForm');
            if (editRequestForm) {
                editRequestForm.setAttribute('data-submit-disabled', 'true');
                setTimeout(() => {
                    editRequestForm.removeAttribute('data-submit-disabled');
                }, 2000);
            }
            
            // Tạm thời disable form submit khi modal mở
            const form = document.getElementById('editDeploymentCaseForm');
            if (form) {
                form.setAttribute('data-submit-disabled', 'true');
                setTimeout(() => {
                    form.removeAttribute('data-submit-disabled');
                }, 1000);
            }
            
            editCaseModal.show();
            
            // Đảm bảo form không submit tự động
            setTimeout(() => {
                const form = document.getElementById('editDeploymentCaseForm');
                if (form) {
                    form.reset();
                    // Điền lại dữ liệu
                    document.getElementById('edit_case_id').value = caseData.id;
                    document.getElementById('edit_case_code').value = caseData.case_code || '';
                    document.getElementById('edit_request_type').value = caseData.request_type || '';
                    document.getElementById('edit_progress').value = caseData.progress || '';
                    document.getElementById('edit_case_description').value = caseData.case_description || '';
                    document.getElementById('edit_notes').value = caseData.notes || '';
                    document.getElementById('edit_assigned_to').value = caseData.assigned_to || '';
                    document.getElementById('edit_work_type').value = caseData.work_type || '';
                    document.getElementById('edit_start_date').value = caseData.start_date ? formatDateTimeForInput(caseData.start_date) : '';
                    document.getElementById('edit_end_date').value = caseData.end_date ? formatDateTimeForInput(caseData.end_date) : '';
                    document.getElementById('edit_status').value = caseData.status || '';
                }
                
                // Load danh sách task triển khai
                loadDeploymentTasks(caseData.id);
            }, 100);
                
            } else {
                if (typeof showAlert === 'function') {
                    showAlert(data.error || 'Không thể lấy thông tin case', 'error');
                } else {
                    alert(data.error || 'Không thể lấy thông tin case');
                }
            }
        })
        .catch(error => {
            console.error('Error fetching case details:', error);
            if (typeof showAlert === 'function') {
                showAlert('Có lỗi xảy ra khi lấy thông tin case', 'error');
            } else {
                alert('Có lỗi xảy ra khi lấy thông tin case');
            }
        });
}

// Function xóa case triển khai
function deleteDeploymentCase(caseId, requestId) {
    // Ngăn chặn event bubbling
    event.preventDefault();
    event.stopPropagation();
    
    if (!confirm('Bạn có chắc chắn muốn xóa case triển khai này?')) {
        return;
    }
    
    console.log('Deleting deployment case ID:', caseId);
    
    fetch('api/delete_deployment_case.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: caseId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof showAlert === 'function') {
                showAlert('Xóa case triển khai thành công!', 'success');
            } else {
                alert('Xóa case triển khai thành công!');
            }
            loadDeploymentCases(requestId);
            reloadDeploymentRequestsTable(); // cập nhật lại tổng số case
        } else {
            if (typeof showAlert === 'function') {
                showAlert(data.error || 'Có lỗi xảy ra khi xóa case', 'error');
            } else {
                alert(data.error || 'Có lỗi xảy ra khi xóa case');
            }
        }
    })
    .catch(error => {
        console.error('Error deleting deployment case:', error);
        if (typeof showAlert === 'function') {
            showAlert('Có lỗi xảy ra khi xóa case', 'error');
        } else {
            alert('Có lỗi xảy ra khi xóa case');
        }
    });
}

// Function to load task templates
function loadTaskTemplates() {
    const select = document.getElementById('template_id');
    if (!select) {
        console.error('template_id element not found');
        return;
    }
    
    console.log('Loading task templates...');
    fetch('api/get_task_templates.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Task templates response:', data);
            if (data.success) {
                select.innerHTML = '<option value="">-- Chọn task mẫu --</option>';
                
                data.data.forEach(template => {
                    const option = document.createElement('option');
                    option.value = template.id;
                    option.textContent = template.template_name;
                    option.dataset.description = template.task_description;
                    option.dataset.type = template.task_type;
                    select.appendChild(option);
                });
                console.log('Task templates loaded successfully');
            } else {
                console.error('Error loading task templates:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading task templates:', error);
        });
}

// Function to load IT staffs
function loadITStaffs() {
    const select = document.getElementById('task_assignee_id');
    if (!select) {
        console.error('task_assignee_id element not found');
        return;
    }
    select.innerHTML = '<option value="">-- Chọn người thực hiện --</option>';
    fetch('api/get_staffs.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                // Nếu API trả về dạng { staffs: [...] }
                const staffs = Array.isArray(data.data.staffs) ? data.data.staffs : data.data;
                const itStaffs = staffs.filter(staff => staff.department === 'IT Dept.');
                if (itStaffs.length > 0) {
                    itStaffs.forEach(staff => {
                        const option = document.createElement('option');
                        option.value = staff.id;
                        option.textContent = staff.fullname + ' (' + (staff.employee_code || staff.staff_code || '') + ')';
                        select.appendChild(option);
                    });
                } else {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = '-- Không có nhân viên IT Dept --';
                    select.appendChild(option);
                }
            } else {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = '-- Không có nhân viên IT Dept --';
                select.appendChild(option);
            }
        })
        .catch(error => {
            console.error('Error loading IT staffs:', error);
            const option = document.createElement('option');
            option.value = '';
            option.textContent = '-- Lỗi khi tải danh sách nhân sự IT --';
            select.appendChild(option);
        });
}

// Function tạo task triển khai
function createDeploymentTask() {
    const modalElement = document.getElementById('createDeploymentTaskModal');
    if (!modalElement) {
        alert('Lỗi: Không tìm thấy modal tạo task');
        return;
    }

    // Lấy caseId từ form edit case hiện tại
    const caseId = document.getElementById('edit_case_id').value;
    if (!caseId) {
        alert('Lỗi: Không tìm thấy thông tin case');
        return;
    }

    // Handler khi modal hiển thị
    const handler = function() {
        // Lấy thông tin case và request
        fetch('api/get_case_details.php?id=' + caseId)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const caseData = data.data;
                    document.getElementById('task_case_code').value = caseData.case_code || '';
                    document.getElementById('task_request_code').value = caseData.request_code || '';
                    document.getElementById('task_deployment_case_id').value = caseId;
                    document.getElementById('task_request_id').value = caseData.deployment_request_id || '';
                }
            })
            .catch(error => {
                console.error('Error loading case details:', error);
            });

        // Lấy số task tự động
        fetch('api/get_next_task_number.php')
            .then(response => response.json())
            .then(data => {
                document.getElementById('task_number').value = data.success ? data.task_number : '';
            })
            .catch(() => {
                document.getElementById('task_number').value = '';
            });

        // Lấy danh sách user IT Dept
        fetch('api/get_it_staffs.php')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('task_assignee_id');
                select.innerHTML = '<option value="">-- Chọn người thực hiện --</option>';
                if (data.success && Array.isArray(data.data)) {
                    data.data.forEach(staff => {
                        const option = document.createElement('option');
                        option.value = staff.id;
                        option.textContent = staff.fullname;
                        select.appendChild(option);
                    });
                }
            });

        modalElement.removeEventListener('shown.bs.modal', handler);
    };
    modalElement.addEventListener('shown.bs.modal', handler);

    const modal = new bootstrap.Modal(modalElement);
    modal.show();
}

// Function load danh sách task triển khai
function loadDeploymentTasks(caseId) {
    if (!caseId) return;
    
    console.log('Loading deployment tasks for case ID:', caseId);
    
    // Fetch tasks filtered by deployment_case_id
    fetch('api/get_deployment_tasks.php?deployment_case_id=' + caseId)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('deployment-tasks-table');
            if (!tbody) {
                console.error('Tasks table body not found');
                return;
            }
            
            tbody.innerHTML = '';
            
            if (!data.success || !Array.isArray(data.data) || data.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="11" class="text-center text-muted py-3">
                  <i class='fas fa-inbox fa-2x mb-2'></i><br>Chưa có task triển khai nào
                </td></tr>`;
                return;
            }
            
            console.log('Found', data.data.length, 'deployment tasks');
            
            // Populate table with filtered tasks
            data.data.forEach((item, idx) => {
                tbody.innerHTML += `
                  <tr>
                    <td class='text-center'>${idx + 1}</td>
                    <td>${item.task_number || ''}</td>
                    <td>${item.task_type || ''}</td>
                    <td>${item.template_name || '-'}</td>
                    <td>${item.task_description || ''}</td>
                    <td>${formatDateForDisplay(item.start_date)}</td>
                    <td>${formatDateForDisplay(item.end_date)}</td>
                    <td>${item.assignee_name || ''}</td>
                    <td>
                      <span class="badge bg-${(item.status === 'Hoàn thành' ? 'success' : (item.status === 'Đang xử lý' ? 'warning' : (item.status === 'Huỷ' ? 'danger' : 'secondary')))}">
                        ${item.status || 'Tiếp nhận'}
                      </span>
                    </td>
                    <td>
                      <div class="progress" style="width: 80px; height: 20px;">
                        <div class="progress-bar bg-${(item.progress_percentage >= 80 ? 'success' : (item.progress_percentage >= 50 ? 'warning' : 'danger'))}" 
                             style="width: ${item.progress_percentage || 0}%" 
                             title="${item.progress_percentage || 0}%">
                          <small>${item.progress_percentage || 0}%</small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="editDeploymentTask(${item.id}); return false;" title="Chỉnh sửa">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteDeploymentTask(${item.id}, ${caseId}); return false;" title="Xóa">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                `;
            });
        })
        .catch(error => {
            console.error('Error loading deployment tasks:', error);
            const tbody = document.getElementById('deployment-tasks-table');
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="11" class="text-center text-danger py-3">
                  <i class='fas fa-exclamation-triangle fa-2x mb-2'></i><br>Lỗi khi tải dữ liệu
                </td></tr>`;
            }
        });
}

// Function chỉnh sửa task triển khai
function editDeploymentTask(taskId) {
    console.log('=== editDeploymentTask called with ID:', taskId, '===');
    
    // Ngăn chặn event bubbling
    event.preventDefault();
    event.stopPropagation();
    
    if (typeof showAlert === 'function') {
        showAlert('Tính năng chỉnh sửa task đang được phát triển', 'info');
    } else {
        alert('Tính năng chỉnh sửa task đang được phát triển');
    }
}

// Function xóa task triển khai
function deleteDeploymentTask(taskId, caseId) {
    // Ngăn chặn event bubbling
    event.preventDefault();
    event.stopPropagation();
    
    if (!confirm('Bạn có chắc chắn muốn xóa task triển khai này?')) {
        return;
    }
    
    console.log('Deleting deployment task ID:', taskId);
    
    if (typeof showAlert === 'function') {
        showAlert('Tính năng xóa task đang được phát triển', 'info');
    } else {
        alert('Tính năng xóa task đang được phát triển');
    }
}

// Function xóa yêu cầu triển khai
function deleteRequest(requestId) {
    if (!confirm('Bạn có chắc chắn muốn xóa yêu cầu triển khai này?\n\nLưu ý: Tất cả các case triển khai liên quan cũng sẽ bị xóa!')) {
        return;
    }
    
    console.log('Deleting deployment request ID:', requestId);
    
    fetch('api/delete_deployment_request.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: requestId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof showAlert === 'function') {
                showAlert('Xóa yêu cầu triển khai thành công!', 'success');
            } else {
                alert('Xóa yêu cầu triển khai thành công!');
            }
            // Reload trang để cập nhật danh sách
            location.reload();
        } else {
            if (typeof showAlert === 'function') {
                showAlert('Lỗi: ' + (data.message || 'Không thể xóa yêu cầu triển khai'), 'error');
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể xóa yêu cầu triển khai'));
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showAlert === 'function') {
            showAlert('Lỗi kết nối: ' + error.message, 'error');
        } else {
            alert('Lỗi kết nối: ' + error.message);
        }
    });
}

// Hàm reload bảng danh sách yêu cầu triển khai
function reloadDeploymentRequestsTable() {
    fetch('api/get_deployment_requests.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success || !Array.isArray(data.data)) return;
            const tbody = document.getElementById('deployment-requests-table');
            if (!tbody) return;
            tbody.innerHTML = '';
            data.data.forEach(request => {
                tbody.innerHTML += `
                <tr>
                    <td><strong class="text-primary">${request.request_code || ''}</strong></td>
                    <td><div class="contract-info"><div class="fw-bold">${request.contract_type || 'N/A'}</div><small class="text-muted">${request.request_detail_type || 'N/A'}</small></div></td>
                    <td><div class="customer-info"><div class="fw-bold">${request.customer_name || 'N/A'}</div><small class="text-muted"><i class='fas fa-user me-1'></i>${request.contact_person || 'N/A'}</small><br><small class="text-muted"><i class='fas fa-phone me-1'></i>${request.contact_phone || 'N/A'}</small></div></td>
                    <td><span class="text-dark">${request.sale_name || 'N/A'}</span></td>
                    <td>${request.expected_start && request.expected_end ? `<div class='text-wrap' style='white-space: pre-line;'><strong>Từ</strong><br>${formatDateForDisplay(request.expected_start)}<br><strong>Đến</strong><br>${formatDateForDisplay(request.expected_end)}</div>` : '<span class="text-muted">Chưa có</span>'}</td>
                    <td>${request.requester_notes ? `<div class='text-wrap' style='max-width: 200px; white-space: pre-wrap; word-wrap: break-word;'>${request.requester_notes}</div>` : '<span class="text-muted">-</span>'}</td>
                    <td><span class="text-dark">${request.deployment_status || ''}</span></td>
                    <td><span class="text-dark">${request.total_cases || 0}</span></td>
                    <td><span class="text-dark">${request.total_tasks || 0}</span></td>
                    <td><div class="progress" style="width: 80px; height: 20px;"><div class="progress-bar bg-${(request.progress_percentage >= 80 ? 'success' : (request.progress_percentage >= 50 ? 'warning' : 'danger'))}" style="width: ${request.progress_percentage || 0}%" title="${request.progress_percentage || 0}%"><small>${request.progress_percentage || 0}%</small></div></div></td>
                    <td><span class="badge bg-${(request.deployment_status === 'Hoàn thành' ? 'success' : (request.deployment_status === 'Đang xử lý' ? 'warning' : (request.deployment_status === 'Huỷ' ? 'danger' : 'secondary')))}">${request.deployment_status || ''}</span></td>
                    <td><div class="btn-group" role="group"><button class="btn btn-sm btn-outline-warning" onclick="editRequest(${request.id})" title="Chỉnh sửa"><i class="fas fa-edit"></i></button><button class="btn btn-sm btn-outline-danger" onclick="deleteRequest(${request.id})" title="Xóa"><i class="fas fa-trash"></i></button></div></td>
                </tr>
                `;
            });
        });
}

// Event listener cho form tạo deployment task
document.addEventListener('DOMContentLoaded', function() {
    const createTaskForm = document.getElementById('createDeploymentTaskForm');
    if (createTaskForm) {
        createTaskForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const data = {
                deployment_case_id: formData.get('deployment_case_id'),
                task_type: formData.get('task_type'),
                template_name: formData.get('task_template') || null,
                task_description: formData.get('task_name'),
                start_date: formData.get('start_date'),
                end_date: formData.get('end_date'),
                assignee_id: formData.get('assignee_id') || null,
                status: formData.get('status')
            };
            
            console.log('Creating deployment task with data:', data);
            
            fetch('api/create_deployment_task.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showAlert(result.message, 'success');
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('createDeploymentTaskModal'));
                    modal.hide();
                    
                    // Reload tasks table
                    loadDeploymentTasks(data.deployment_case_id);
                    
                    // Reset form
                    e.target.reset();
                } else {
                    showAlert(result.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error creating deployment task:', error);
                showAlert('Lỗi khi tạo task triển khai', 'error');
            });
        });
    }
    
    // Event listener cho template selection
    document.addEventListener('change', function(e) {
        if (e.target.id === 'template_id') {
            const selectedOption = e.target.options[e.target.selectedIndex];
            if (selectedOption && selectedOption.dataset.description) {
                document.getElementById('task_description').value = selectedOption.dataset.description;
                document.getElementById('task_type').value = selectedOption.dataset.type;
            }
        }
    });
});
</script>

<!-- Modal chỉnh sửa yêu cầu triển khai -->
<div class="modal fade" id="editDeploymentRequestModal" tabindex="-1" aria-labelledby="editDeploymentRequestModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content deployment-request-modal">
      <div class="modal-header">
        <h5 class="modal-title" id="editDeploymentRequestModalLabel">Chỉnh sửa yêu cầu triển khai</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="editDeploymentRequestForm" method="POST" action="#">
        <div class="modal-body">
          <input type="hidden" id="edit_request_id" name="id">
          <div class="row g-4">
            <!-- Cột trái: Hợp đồng -->
            <div class="col-md-6">
              <h6 class="text-primary mb-3"><i class="fas fa-file-contract me-2"></i>HỢP ĐỒNG</h6>
              
              <div class="mb-3">
                <label class="form-label">Mã yêu cầu:</label>
                <input type="text" class="form-control" name="request_code" id="edit_request_code" readonly>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Số hợp đồng PO:</label>
                <input type="text" class="form-control" name="po_number" id="edit_po_number" placeholder="Nhập số hợp đồng PO">
                <div class="form-check mt-1">
                  <input class="form-check-input" type="checkbox" value="1" id="edit_no_contract_po" name="no_contract_po">
                  <label class="form-check-label" for="edit_no_contract_po">Không có HĐ/PO</label>
                </div>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Loại hợp đồng:</label>
                <select class="form-select" name="contract_type" id="edit_contract_type">
                  <option value="">-- Chọn loại hợp đồng --</option>
                  <option value="Hợp đồng cung cấp dịch vụ">Hợp đồng cung cấp dịch vụ</option>
                  <option value="Hợp đồng bảo trì hệ thống">Hợp đồng bảo trì hệ thống</option>
                  <option value="Hợp đồng phát triển phần mềm">Hợp đồng phát triển phần mềm</option>
                  <option value="Hợp đồng tư vấn công nghệ">Hợp đồng tư vấn công nghệ</option>
                  <option value="Hợp đồng triển khai dự án">Hợp đồng triển khai dự án</option>
                  <option value="Hợp đồng hỗ trợ kỹ thuật">Hợp đồng hỗ trợ kỹ thuật</option>
                  <option value="Hợp đồng đào tạo">Hợp đồng đào tạo</option>
                  <option value="Hợp đồng gia hạn dịch vụ">Hợp đồng gia hạn dịch vụ</option>
                  <option value="Hợp đồng nâng cấp hệ thống">Hợp đồng nâng cấp hệ thống</option>
                  <option value="Hợp đồng tích hợp hệ thống">Hợp đồng tích hợp hệ thống</option>
                </select>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Loại yêu cầu chi tiết:</label>
                <select class="form-select" name="request_detail_type" id="edit_request_detail_type">
                  <option value="">-- Chọn loại yêu cầu chi tiết --</option>
                  <option value="Triển khai mới">Triển khai mới</option>
                  <option value="Nâng cấp hệ thống">Nâng cấp hệ thống</option>
                  <option value="Bảo trì hệ thống">Bảo trì hệ thống</option>
                  <option value="Tư vấn kỹ thuật">Tư vấn kỹ thuật</option>
                  <option value="Đào tạo người dùng">Đào tạo người dùng</option>
                  <option value="Hỗ trợ kỹ thuật">Hỗ trợ kỹ thuật</option>
                  <option value="Tích hợp hệ thống">Tích hợp hệ thống</option>
                  <option value="Tích hợp hệ thống bên thứ 3">Tích hợp hệ thống bên thứ 3</option>
                  <option value="Khắc phục sự cố">Khắc phục sự cố</option>
                  <option value="Tối ưu hóa hiệu suất">Tối ưu hóa hiệu suất</option>
                  <option value="Di chuyển dữ liệu">Di chuyển dữ liệu</option>
                </select>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Tiêu đề email gửi khách hàng:</label>
                <input type="text" class="form-control" name="email_subject_customer" id="edit_email_subject_customer" placeholder="Nhập tiêu đề email">
              </div>
              
              <div class="mb-3">
                <label class="form-label">Tiêu đề email nội bộ:</label>
                <input type="text" class="form-control" name="email_subject_internal" id="edit_email_subject_internal" placeholder="Nhập tiêu đề email">
              </div>
            </div>
            
            <!-- Cột phải: Thông tin triển khai -->
            <div class="col-md-6">
              <h6 class="text-primary mb-3"><i class="fas fa-calendar-alt me-2"></i>THÔNG TIN TRIỂN KHAI</h6>
              
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Ngày bắt đầu dự kiến:</label>
                    <input type="date" class="form-control" name="expected_start" id="edit_expected_start">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Ngày kết thúc dự kiến:</label>
                    <input type="date" class="form-control" name="expected_end" id="edit_expected_end">
                  </div>
                </div>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Khách hàng: <span class="text-danger">*</span></label>
                <select class="form-select" name="customer_id" id="edit_customer_id" required>
                  <option value="">-- Chọn khách hàng --</option>
                  <?php
                  $partners = $pdo->query("SELECT id, name, contact_person, contact_phone FROM partner_companies ORDER BY name ASC")->fetchAll();
                  foreach ($partners as $partner) {
                    echo '<option value="'.$partner['id'].'">'.htmlspecialchars($partner['name']).'</option>';
                  }
                  ?>
                </select>
              </div>
              
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Người liên hệ:</label>
                    <input type="text" class="form-control" name="contact_person" id="edit_contact_person" placeholder="Nhập tên người liên hệ">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Số điện thoại:</label>
                    <input type="text" class="form-control" name="contact_phone" id="edit_contact_phone" placeholder="Nhập số điện thoại">
                  </div>
                </div>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Sale phụ trách: <span class="text-danger">*</span></label>
                <select class="form-select" name="sale_id" id="edit_sale_id" required>
                  <option value="">-- Chọn sale phụ trách --</option>
                  <?php
                  $sales = $pdo->query("SELECT id, fullname FROM staffs WHERE department = 'SALE Dept.' AND status = 'active' ORDER BY fullname ASC")->fetchAll();
                  
                  if (empty($sales)) {
                    echo '<option value="">-- Không có nhân viên SALE Dept --</option>';
                  } else {
                    foreach ($sales as $sale) {
                      echo '<option value="'.$sale['id'].'">'.htmlspecialchars($sale['fullname']).' ID: '.$sale['id'].'</option>';
                    }
                  }
                  ?>
                </select>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Ghi chú yêu cầu:</label>
                <textarea class="form-control" name="requester_notes" id="edit_requester_notes" rows="3" placeholder="Nhập ghi chú yêu cầu"></textarea>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Quản lý triển khai:</label>
                <input type="text" class="form-control" name="deployment_manager" id="edit_deployment_manager" value="Trần Nguyễn Anh Khoa" readonly>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Trạng thái triển khai: <span class="text-danger">*</span></label>
                <select class="form-select" name="deployment_status" id="edit_deployment_status" required>
                  <option value="">-- Chọn trạng thái --</option>
                  <option value="Tiếp nhận">Tiếp nhận</option>
                  <option value="Đang xử lý">Đang xử lý</option>
                  <option value="Hoàn thành">Hoàn thành</option>
                  <option value="Huỷ">Huỷ</option>
                </select>
              </div>
            </div>
          </div>
          
          <!-- Phần thứ 3: Quản lý Case triển khai -->
          <div class="border-top pt-4 mt-4 bg-light">
            <div class="row">
              <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="text-success mb-0"><i class="fas fa-tasks me-2"></i>QUẢN LÝ CASE TRIỂN KHAI</h6>
                  <button type="button" class="btn btn-success btn-sm" onclick="createDeploymentCase()">
                    <i class="fas fa-plus me-1"></i>Tạo case triển khai
                  </button>
                </div>
                
                <!-- Bảng danh sách case triển khai -->
                <div class="table-responsive">
                  <table class="table table-sm table-hover table-bordered">
                    <thead class="table-light">
                      <tr>
                        <th class="text-center">STT</th>
                        <th>Số case</th>
                        <th>Tiến trình</th>
                        <th>Mô tả case</th>
                        <th>Ghi chú</th>
                        <th>Người phụ trách</th>
                        <th>Ngày bắt đầu</th>
                        <th>Ngày kết thúc</th>
                        <th>Trạng thái</th>
                        <th>Trạng thái tiến độ</th>
                        <th>Tổng số task</th>
                        <th>Task hoàn thành</th>
                        <th>Tiến độ (%)</th>
                        <th>Hình thức</th>
                        <th>Thao tác</th>
                      </tr>
                    </thead>
                    <tbody id="deployment-cases-table">
                      <tr>
                        <td colspan="15" class="text-center text-muted py-3">
                          <i class="fas fa-inbox fa-2x mb-2"></i><br>
                          Chưa có case triển khai nào
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary">Cập nhật yêu cầu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal tạo task triển khai -->
<div class="modal fade" id="createDeploymentTaskModal" tabindex="-1" aria-labelledby="createDeploymentTaskModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content deployment-request-modal">
      <div class="modal-header">
        <h5 class="modal-title" id="createDeploymentTaskModalLabel">
          <i class="fas fa-plus-circle text-primary"></i> Tạo Task Triển Khai
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="createDeploymentTaskForm">
          <div class="row g-4">
            <!-- Cột trái -->
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label">Số task:</label>
                <input type="text" class="form-control" name="task_number" id="task_number" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label">Số case:</label>
                <input type="text" class="form-control" name="case_code" id="task_case_code" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label">Mã yêu cầu:</label>
                <input type="text" class="form-control" name="request_code" id="task_request_code" readonly>
              </div>
              <div class="mb-3">
                <label for="task_type" class="form-label">Loại Task <span class="text-danger">*</span></label>
                <select class="form-select" name="task_type" id="task_type" required>
                  <option value="">-- Chọn loại task --</option>
                  <option value="onsite">Onsite</option>
                  <option value="offsite">Offsite</option>
                  <option value="remote">Remote</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="task_template" class="form-label">Task mẫu</label>
                <select class="form-select" name="task_template" id="task_template">
                  <option value="">-- Chọn task mẫu --</option>
                  <option value="Cài đặt thiết bị">Cài đặt thiết bị</option>
                  <option value="Cấu hình phần mềm">Cấu hình phần mềm</option>
                  <option value="Kiểm tra hệ thống">Kiểm tra hệ thống</option>
                  <option value="Đào tạo người dùng">Đào tạo người dùng</option>
                  <option value="Bảo trì định kỳ">Bảo trì định kỳ</option>
                  <option value="Khắc phục sự cố">Khắc phục sự cố</option>
                  <option value="Nghiệm thu dự án">Nghiệm thu dự án</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="task_name" class="form-label">Task <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="task_name" id="task_name" required placeholder="Nhập tên task cụ thể">
              </div>
              <div class="mb-3">
                <label for="task_note" class="form-label">Ghi chú</label>
                <textarea class="form-control" name="task_note" id="task_note" rows="2" placeholder="Nhập ghi chú"></textarea>
              </div>
            </div>
            <!-- Cột phải -->
            <div class="col-md-6">
              <div class="mb-3">
                <label for="task_assignee_id" class="form-label">Người thực hiện</label>
                <select class="form-select" name="assignee_id" id="task_assignee_id">
                  <option value="">-- Chọn người thực hiện --</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="task_start_date" class="form-label">Thời gian bắt đầu <span class="text-danger">*</span></label>
                <input type="datetime-local" class="form-control" name="start_date" id="task_start_date" required>
              </div>
              <div class="mb-3">
                <label for="task_end_date" class="form-label">Thời gian kết thúc <span class="text-danger">*</span></label>
                <input type="datetime-local" class="form-control" name="end_date" id="task_end_date" required>
              </div>
              <div class="mb-3">
                <label for="task_status" class="form-label">Trạng thái</label>
                <select class="form-select" name="status" id="task_status">
                  <option value="Tiếp nhận">Tiếp nhận</option>
                  <option value="Đang xử lý">Đang xử lý</option>
                  <option value="Hoàn thành">Hoàn thành</option>
                  <option value="Huỷ">Huỷ</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Người nhập:</label>
                <input type="text" class="form-control" name="created_by_name" id="task_created_by_name" value="<?php echo htmlspecialchars($_SESSION['fullname'] ?? ''); ?>" readonly>
              </div>
            </div>
          </div>
          <input type="hidden" name="deployment_case_id" id="task_deployment_case_id">
          <input type="hidden" name="request_id" id="task_request_id">
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times"></i> Hủy
        </button>
        <button type="submit" form="createDeploymentTaskForm" class="btn btn-primary">
          <i class="fas fa-save"></i> Tạo Task
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal tạo case triển khai -->
<div class="modal fade" id="createDeploymentCaseModal" tabindex="-1" aria-labelledby="createDeploymentCaseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content deployment-request-modal">
      <div class="modal-header">
        <h5 class="modal-title" id="createDeploymentCaseModalLabel">Tạo case triển khai</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="createDeploymentCaseForm" method="POST" action="#">
        <input type="hidden" id="deployment_request_id" name="deployment_request_id">
        <div class="modal-body">
          <div class="row g-4">
            <!-- Cột trái: Thông tin cơ bản -->
            <div class="col-md-6">
              <h6 class="text-primary mb-3"><i class="fas fa-info-circle me-2"></i>THÔNG TIN CƠ BẢN</h6>
              
              <div class="mb-3">
                <label class="form-label">Số case:</label>
                <input type="text" class="form-control" name="case_code" id="case_code" readonly>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Loại yêu cầu:</label>
                <select class="form-select" name="request_type" id="request_type">
                  <option value="">-- Chọn loại yêu cầu --</option>
                  <option value="Triển khai mới">Triển khai mới</option>
                  <option value="Nâng cấp hệ thống">Nâng cấp hệ thống</option>
                  <option value="Bảo trì hệ thống">Bảo trì hệ thống</option>
                  <option value="Tư vấn kỹ thuật">Tư vấn kỹ thuật</option>
                  <option value="Đào tạo người dùng">Đào tạo người dùng</option>
                  <option value="Hỗ trợ kỹ thuật">Hỗ trợ kỹ thuật</option>
                  <option value="Tích hợp hệ thống">Tích hợp hệ thống</option>
                  <option value="Tích hợp hệ thống bên thứ 3">Tích hợp hệ thống bên thứ 3</option>
                  <option value="Khắc phục sự cố">Khắc phục sự cố</option>
                  <option value="Tối ưu hóa hiệu suất">Tối ưu hóa hiệu suất</option>
                  <option value="Di chuyển dữ liệu">Di chuyển dữ liệu</option>
                </select>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Tiến trình:</label>
                <select class="form-select" name="progress" id="progress">
                  <option value="">-- Chọn tiến trình --</option>
                  <option value="CS - Chốt SOW">CS - Chốt SOW</option>
                  <option value="SH - Soạn hàng">SH - Soạn hàng</option>
                  <option value="GH - Giao hàng">GH - Giao hàng</option>
                  <option value="TK - Triển khai">TK - Triển khai</option>
                  <option value="NT - Nghiệm thu">NT - Nghiệm thu</option>
                </select>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Mô tả case:</label>
                <textarea class="form-control" name="case_description" id="case_description" rows="4" placeholder="Nhập mô tả chi tiết về case"></textarea>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Ghi chú:</label>
                <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="Nhập ghi chú bổ sung"></textarea>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Người nhập:</label>
                <input type="text" class="form-control" name="created_by_name" id="created_by_name" value="<?php echo htmlspecialchars($fullname); ?>" readonly>
              </div>
            </div>
            
            <!-- Cột phải: Thông tin triển khai -->
            <div class="col-md-6">
              <h6 class="text-success mb-3"><i class="fas fa-cogs me-2"></i>THÔNG TIN TRIỂN KHAI</h6>
              
              <div class="mb-3">
                <label class="form-label">Người phụ trách:</label>
                <select class="form-select" name="assigned_to" id="assigned_to">
                  <option value="">-- Chọn người phụ trách --</option>
                  <?php
                  $it_staffs = $pdo->query("SELECT id, fullname FROM staffs WHERE department = 'IT Dept.' AND status = 'active' ORDER BY fullname ASC")->fetchAll();
                  
                  if (empty($it_staffs)) {
                    echo '<option value="">-- Không có nhân viên IT Dept --</option>';
                  } else {
                    foreach ($it_staffs as $staff) {
                      echo '<option value="'.$staff['id'].'">'.htmlspecialchars($staff['fullname']).'</option>';
                    }
                  }
                  ?>
                </select>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Hình thức:</label>
                <select class="form-select" name="work_type" id="work_type">
                  <option value="">-- Chọn hình thức --</option>
                  <option value="Onsite">Onsite</option>
                  <option value="Offsite">Offsite</option>
                  <option value="Remote">Remote</option>
                </select>
              </div>
              
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Ngày giờ bắt đầu:</label>
                    <input type="datetime-local" class="form-control" name="start_date" id="start_date">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Ngày giờ kết thúc:</label>
                    <input type="datetime-local" class="form-control" name="end_date" id="end_date">
                  </div>
                </div>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Trạng thái:</label>
                <select class="form-select" name="status" id="status">
                  <option value="Tiếp nhận">Tiếp nhận</option>
                  <option value="Đang xử lý">Đang xử lý</option>
                  <option value="Hoàn thành">Hoàn thành</option>
                  <option value="Huỷ">Huỷ</option>
                </select>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary">Tạo case</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal chỉnh sửa case triển khai -->
<div class="modal fade" id="editDeploymentCaseModal" tabindex="-1" aria-labelledby="editDeploymentCaseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content deployment-request-modal">
      <div class="modal-header">
        <h5 class="modal-title" id="editDeploymentCaseModalLabel">Chỉnh sửa case triển khai</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="editDeploymentCaseForm" method="POST" action="#">
        <input type="hidden" id="edit_case_id" name="id">
        <div class="modal-body">
          <div class="row g-4">
            <!-- Cột trái: Thông tin cơ bản -->
            <div class="col-md-6">
              <h6 class="text-primary mb-3"><i class="fas fa-info-circle me-2"></i>THÔNG TIN CƠ BẢN</h6>
              
              <div class="mb-3">
                <label class="form-label">Số case:</label>
                <input type="text" class="form-control" name="case_code" id="edit_case_code" readonly>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Loại yêu cầu:</label>
                <select class="form-select" name="request_type" id="edit_request_type">
                  <option value="">-- Chọn loại yêu cầu --</option>
                  <option value="Triển khai mới">Triển khai mới</option>
                  <option value="Nâng cấp hệ thống">Nâng cấp hệ thống</option>
                  <option value="Bảo trì hệ thống">Bảo trì hệ thống</option>
                  <option value="Tư vấn kỹ thuật">Tư vấn kỹ thuật</option>
                  <option value="Đào tạo người dùng">Đào tạo người dùng</option>
                  <option value="Hỗ trợ kỹ thuật">Hỗ trợ kỹ thuật</option>
                  <option value="Tích hợp hệ thống">Tích hợp hệ thống</option>
                  <option value="Tích hợp hệ thống bên thứ 3">Tích hợp hệ thống bên thứ 3</option>
                  <option value="Khắc phục sự cố">Khắc phục sự cố</option>
                  <option value="Tối ưu hóa hiệu suất">Tối ưu hóa hiệu suất</option>
                  <option value="Di chuyển dữ liệu">Di chuyển dữ liệu</option>
                </select>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Tiến trình:</label>
                <select class="form-select" name="progress" id="edit_progress">
                  <option value="">-- Chọn tiến trình --</option>
                  <option value="CS - Chốt SOW">CS - Chốt SOW</option>
                  <option value="SH - Soạn hàng">SH - Soạn hàng</option>
                  <option value="GH - Giao hàng">GH - Giao hàng</option>
                  <option value="TK - Triển khai">TK - Triển khai</option>
                  <option value="NT - Nghiệm thu">NT - Nghiệm thu</option>
                </select>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Mô tả case:</label>
                <textarea class="form-control" name="case_description" id="edit_case_description" rows="4" placeholder="Nhập mô tả chi tiết về case"></textarea>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Ghi chú:</label>
                <textarea class="form-control" name="notes" id="edit_notes" rows="3" placeholder="Nhập ghi chú bổ sung"></textarea>
              </div>
            </div>
            
            <!-- Cột phải: Thông tin triển khai -->
            <div class="col-md-6">
              <h6 class="text-success mb-3"><i class="fas fa-cogs me-2"></i>THÔNG TIN TRIỂN KHAI</h6>
              
              <div class="mb-3">
                <label class="form-label">Người phụ trách:</label>
                <select class="form-select" name="assigned_to" id="edit_assigned_to">
                  <option value="">-- Chọn người phụ trách --</option>
                  <?php
                  $it_staffs = $pdo->query("SELECT id, fullname FROM staffs WHERE department = 'IT Dept.' AND status = 'active' ORDER BY fullname ASC")->fetchAll();
                  
                  if (empty($it_staffs)) {
                    echo '<option value="">-- Không có nhân viên IT Dept --</option>';
                  } else {
                    foreach ($it_staffs as $staff) {
                      echo '<option value="'.$staff['id'].'">'.htmlspecialchars($staff['fullname']).'</option>';
                    }
                  }
                  ?>
                </select>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Hình thức:</label>
                <select class="form-select" name="work_type" id="edit_work_type">
                  <option value="">-- Chọn hình thức --</option>
                  <option value="Onsite">Onsite</option>
                  <option value="Offsite">Offsite</option>
                  <option value="Remote">Remote</option>
                </select>
              </div>
              
              <div class="row">
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Ngày giờ bắt đầu:</label>
                    <input type="datetime-local" class="form-control" name="start_date" id="edit_start_date">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="mb-3">
                    <label class="form-label">Ngày giờ kết thúc:</label>
                    <input type="datetime-local" class="form-control" name="end_date" id="edit_end_date">
                  </div>
                </div>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Trạng thái:</label>
                <select class="form-select" name="status" id="edit_status">
                  <option value="Tiếp nhận">Tiếp nhận</option>
                  <option value="Đang xử lý">Đang xử lý</option>
                  <option value="Hoàn thành">Hoàn thành</option>
                  <option value="Huỷ">Huỷ</option>
                </select>
              </div>
            </div>
          </div>
          
          <!-- Phần thứ 3: Quản lý Task triển khai -->
          <div class="border-top pt-4 mt-4 bg-light">
            <div class="row">
              <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="text-info mb-0"><i class="fas fa-tasks me-2"></i>QUẢN LÝ TASK TRIỂN KHAI</h6>
                  <button type="button" class="btn btn-info btn-sm" onclick="createDeploymentTask()">
                    <i class="fas fa-plus me-1"></i>Tạo task triển khai
                  </button>
                </div>
                
                <!-- Bảng danh sách task triển khai -->
                <div class="table-responsive">
                  <table class="table table-sm table-hover table-bordered">
                    <thead class="table-light">
                      <tr>
                        <th class="text-center">STT</th>
                        <th>Số Task</th>
                        <th>Loại Task</th>
                        <th>Task mẫu</th>
                        <th>Task</th>
                        <th>Thời gian bắt đầu</th>
                        <th>Thời gian kết thúc</th>
                        <th>Người thực hiện</th>
                        <th>Trạng thái</th>
                        <th>Trạng thái tiến độ</th>
                        <th>Thao tác</th>
                      </tr>
                    </thead>
                    <tbody id="deployment-tasks-table">
                      <tr>
                        <td colspan="11" class="text-center text-muted py-3">
                          <i class="fas fa-inbox fa-2x mb-2"></i><br>
                          Chưa có task triển khai nào
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary">Cập nhật case</button>
        </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>