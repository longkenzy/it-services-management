<?php
// Trang quản lý Yêu cầu bảo trì (Maintenance Requests)
require_once 'includes/session.php';
requireLogin();
require_once 'config/db.php';

// Lấy role user hiện tại
$current_role = isset($_SESSION['role']) ? $_SESSION['role'] : (function_exists('getCurrentUserRole') ? getCurrentUserRole() : null);

// Lấy danh sách yêu cầu bảo trì từ database với thông tin chi tiết
$requests = [];
try {
    $sql = "SELECT 
                mr.*,
                pc.name as customer_name,
                pc.contact_person,
                pc.contact_phone,
                sale.fullname as sale_name,
                creator.fullname as created_by_name,
                (
                    SELECT COUNT(*) FROM maintenance_cases mc WHERE mc.maintenance_request_id = mr.id
                ) as total_cases,
                0 as total_tasks,
                0 as progress_percentage
            FROM maintenance_requests mr
            LEFT JOIN partner_companies pc ON mr.customer_id = pc.id
            LEFT JOIN staffs sale ON mr.sale_id = sale.id
            LEFT JOIN staffs creator ON mr.created_by = creator.id
            ORDER BY mr.created_at ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $requests = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Database error in maintenance_requests.php: " . $e->getMessage());
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
        'maintenance_manager', 'maintenance_status'
    ];
    $data = [];
    foreach ($fields as $f) {
        $data[$f] = trim($_POST[$f] ?? '');
    }
    if ($data['request_code'] === '' || $data['customer_id'] === '' || $data['sale_id'] === '' || $data['maintenance_status'] === '') {
        $errors[] = 'Vui lòng nhập đầy đủ các trường bắt buộc.';
    }
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO maintenance_requests (
            request_code, po_number, no_contract_po, contract_type, request_detail_type,
            email_subject_customer, email_subject_internal, expected_start, expected_end,
            customer_id, contact_person, contact_phone, sale_id, requester_notes, maintenance_manager, maintenance_status, created_by
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
            $data['maintenance_manager'],
            $data['maintenance_status'],
            $_SESSION['user_id'] ?? null
        ]);
        $success_message = 'Tạo yêu cầu bảo trì thành công!';
        header('Location: maintenance_requests.php?success=1');
        exit;
    }
}

// Lấy tên người dùng đang đăng nhập
$fullname = 'Không xác định';
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT fullname FROM staffs WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    if ($row && !empty($row['fullname'])) {
        $fullname = $row['fullname'];
    } else {
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
    <title>Yêu cầu bảo trì - IT Services Management</title>
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo filemtime('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="assets/css/alert.css?v=<?php echo filemtime('assets/css/alert.css'); ?>">
    <link rel="stylesheet" href="assets/css/no-border-radius.css?v=<?php echo filemtime('assets/css/no-border-radius.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
            <style>
            /* Progress bar animation */
            @keyframes progress {
                from { width: 100%; }
                to { width: 0%; }
            }
            
            /* Fix accessibility issues - sử dụng inert attribute */
            .modal[inert] {
                pointer-events: none;
            }
            
            .modal[inert] * {
                pointer-events: none;
            }
            
            /* Fallback cho browsers không hỗ trợ inert */
            .modal[aria-hidden="true"] {
                pointer-events: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
            }
            
            .modal[aria-hidden="true"] * {
                pointer-events: none !important;
            }
            
            /* Đảm bảo modal không thể focus khi ẩn */
            .modal[aria-hidden="true"]:focus,
            .modal[aria-hidden="true"] *:focus {
                outline: none !important;
                box-shadow: none !important;
            }
            
            /* ===== MODAL STYLES ===== */
        /* Override Bootstrap modal-fullscreen */
        #addMaintenanceRequestModal .modal-dialog,
        #editMaintenanceRequestModal .modal-dialog,
        #createMaintenanceCaseModal .modal-dialog,
        #createMaintenanceTaskModal .modal-dialog {
            max-width: none;
            width: calc(100vw - 40px);
            margin: 80px auto 20px auto;
            height: calc(100vh - 120px);
        }

        /* Modal chỉnh sửa case bảo trì và tạo task bảo trì - kích thước giống với modal request */
        #editMaintenanceCaseModal .modal-dialog,
        #createMaintenanceTaskModal .modal-dialog {
            max-width: none;
            width: calc(100vw - 40px);
            margin: 80px auto 20px auto;
            height: calc(100vh - 120px);
        }
        
        /* Modal tạo task bảo trì - tăng z-index để nằm trên các modal khác */
        #createMaintenanceTaskModal {
            z-index: 1060 !important;
        }
        
        #createMaintenanceTaskModal .modal-backdrop {
            z-index: 1055 !important;
        }
        
        .maintenance-request-modal {
            border-radius: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        
        #editMaintenanceTaskModal {
            z-index: 1060 !important;
        }
        
        #editMaintenanceTaskModal .modal-backdrop {
            z-index: 1055 !important;
        }
        
        #editMaintenanceTaskModal .modal-dialog {
            max-width: none;
            width: calc(100vw - 40px);
            margin: 80px auto 20px auto;
            height: calc(100vh - 120px);
        }
        
        .maintenance-request-modal .modal-header {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: white;
            border-bottom: 2px solid #dee2e6;
            padding: 0.75rem 1.5rem;
        }
        
        .maintenance-request-modal .modal-title {
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .maintenance-request-modal .modal-body {
            flex: 1;
            padding: 1.5rem;
            background-color: #f8f9fa;
            max-height: 70vh;
            overflow-y: auto;
        }

        /* Modal chỉnh sửa case - body cao hơn để chứa task */
        #editMaintenanceCaseModal .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
        
        /* Modal chỉnh sửa task - body cao hơn để chứa nội dung */
        #editMaintenanceTaskModal .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .maintenance-request-modal .modal-footer {
            background-color: #f8f9fa;
            border-top: 2px solid #dee2e6;
            padding: 0.75rem 1.5rem;
        }
        
        /* ===== FORM STYLES ===== */
        .maintenance-request-modal .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.25rem;
            font-size: 1rem;
        }
        
        .maintenance-request-modal .form-control,
        .maintenance-request-modal .form-select {
            border: 2px solid #e9ecef;
            padding: 0.6rem 0.8rem;
            font-size: 1rem;
            height: 48px;
            transition: all 0.3s ease;
        }
        
        .maintenance-request-modal .form-control:focus,
        .maintenance-request-modal .form-select:focus {
            border-color: #ffc107;
            box-shadow: 0 0.2rem rgba(255, 193, 7, 0.25);
        }
        
        .maintenance-request-modal .form-control[readonly] {
            background-color: #f8f9fa;
            color: #6c757d;
        }
        
        .maintenance-request-modal .form-control:disabled {
            background-color: #e9ecef;
            color: #6c757d;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        /* ===== CHECKBOX STYLES ===== */
        .maintenance-request-modal .form-check {
            margin-top: 0.25rem;
        }
        
        .maintenance-request-modal .form-check-input {
            border: 2px solid #e9ecef;
        }
        
        .maintenance-request-modal .form-check-input:checked {
            background-color: #ffc107;
            border-color: #ffc107;
        }

        /* ===== BUTTON STYLES ===== */
        /* Đảm bảo nút vuông */
        .btn-group .btn {
            width: 32px !important;
            height: 32px !important;
            padding: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            line-height: 1 !important;
            border-radius: 4px !important;
        }

        .btn-group .btn i {
            font-size: 14px !important;
            line-height: 1 !important;
            margin: 0 !important;
        }

        /* Override Bootstrap button styles */
        .btn-group .btn.btn-sm {
            width: 32px !important;
            height: 32px !important;
            padding: 0 !important;
        }
        
        .maintenance-request-modal .form-check-label {
            font-size: 0.95rem;
            color: #6c757d;
            margin-left: 0.25rem;
        }
        
        /* ===== TEXTAREA STYLES ===== */
        .maintenance-request-modal textarea.form-control {
            resize: vertical;
            min-height: 100px;
            height: 100px;
        }
        
        /* ===== BUTTON STYLES ===== */
        .maintenance-request-modal .btn {
            padding: 0.5rem 1rem;
            font-weight: 600;
            border-radius: 0;
            font-size: 1rem;
        }
        
        .maintenance-request-modal .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        }
        
        .maintenance-request-modal .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 193, 7, 0.4);
        }
        
        .maintenance-request-modal .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
            border: none;
        }
        
        /* ===== FORM GROUP SPACING ===== */
        .maintenance-request-modal .mb-3 {
            margin-bottom: 0.75rem;
        }
        
        .maintenance-request-modal .row.g-4 {
            --bs-gutter-x: 1em;
            --bs-gutter-y: 0.5rem;
        }
        
        /* ===== RESPONSIVE STYLES ===== */
        @media (max-width: 768px) {
            #addMaintenanceRequestModal .modal-dialog,
            #editMaintenanceRequestModal .modal-dialog,
            #createMaintenanceCaseModal .modal-dialog,
            #createMaintenanceTaskModal .modal-dialog {
                width: calc(100vw - 20px);
                margin: 70px auto 10px auto;
                height: calc(100vh - 100px);
            }
            
            .maintenance-request-modal {
                height: 100%;
            }
            
            .maintenance-request-modal .modal-body {
                padding: 0.75em;
            }
            
            .maintenance-request-modal .row {
                margin: 0;
            }
            
            .maintenance-request-modal .col-md-6 {
                padding: 0;
                margin-bottom: 0.5em;
            }
        }
        
        /* ===== SCROLLBAR STYLES ===== */
        .maintenance-request-modal .modal-body::-webkit-scrollbar {
            width: 8px;
        }
        
        .maintenance-request-modal .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        .maintenance-request-modal .modal-body::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        .maintenance-request-modal .modal-body::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* ===== MODAL SPECIFIC STYLES ===== */
        /* Modal tạo case bảo trì */
        #createMaintenanceCaseModal .maintenance-request-modal .modal-header {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
        }
        
        #createMaintenanceCaseModal .maintenance-request-modal .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
        }
        
        /* Modal tạo task bảo trì */
        #createMaintenanceTaskModal .maintenance-request-modal .modal-header {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
        }
        
        #createMaintenanceTaskModal .maintenance-request-modal .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
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
            color: #ffc107;
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
        
        .badge {
            font-size: 0.7rem;
            padding: 0.4rem 0.6rem;
        }
        
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
        
        .btn-group .btn {
            margin: 0 6px;
        }
        
        .btn-group .btn i {
            font-size: 0.75rem;
            padding: 4px;
        }
        
        .btn-outline-warning {
            padding: 4px !important;
        }
        
        .btn-outline-danger {
            padding: 4px !important;
        }
        
        @media (max-width: 1200px) {
            .table-responsive {
                font-size: 0.75rem;
            }
            
            .table th,
            .table td {
                padding: 0.5rem;
            }
        }
        
        /* ===== MODAL TABLE STYLES ===== */
        .maintenance-request-modal .table {
            font-size: 0.875rem;
        }
        
        .maintenance-request-modal .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            padding: 0.5rem;
            font-size: 0.8rem;
        }
        
        .maintenance-request-modal .table td {
            padding: 0.5rem;
            vertical-align: middle;
        }
        
        .maintenance-request-modal .table-sm th,
        .maintenance-request-modal .table-sm td {
            padding: 0.25rem 0.5rem;
        }
        
        .maintenance-request-modal .table-bordered {
            border: 1px solid #dee2e6;
        }
        
        .maintenance-request-modal .table-bordered th,
        .maintenance-request-modal .table-bordered td {
            border: 1px solid #dee2e6;
        }

        /* ===== TRẠNG THÁI BẢO TRÌ STYLES ===== */
        /* Trạng thái hoàn thành */
        .badge.bg-success {
            background-color: #5cb85c !important;
            color: #fff !important;
        }
        
        /* Trạng thái tiếp nhận */
        .badge.bg-secondary {
            background-color: #f0ad4e !important;
            color: #fff !important;
        }
        
        /* Trạng thái đang xử lý */
        .badge.bg-warning {
            background-color: #5bc0de !important;
            color: #fff !important;
        }
        
        /* Trạng thái huỷ */
        .badge.bg-danger {
            background-color: #d9534f !important;
            color: #fff !important;
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
                        <i class="fas fa-tools me-3 text-warning"></i>
                        Yêu cầu bảo trì
                    </h1>
                    <p class="text-muted mb-0">Quản lý các yêu cầu bảo trì hệ thống và thiết bị</p>
                </div>
                <div class="col-auto">
                    <?php if ($current_role !== 'user'): ?>
                    <button class="btn btn-warning" id="createRequestBtn" data-bs-toggle="modal" data-bs-target="#addMaintenanceRequestModal">
                        <i class="fas fa-plus me-2"></i>
                        Tạo yêu cầu bảo trì
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Alert Container -->
        <div id="alert-container" class="alert-container"></div>
        
        <!-- Table hiển thị danh sách yêu cầu bảo trì -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Mã YC</th>
                                <th>Loại HĐ</th>
                                <th>Khách hàng</th>
                                <th>Phụ trách</th>
                                <th>Thời hạn bảo trì</th>
                                <th>Ghi chú</th>
                                <th>Trạng thái YC</th>
                                <th>Tổng số case</th>
                                <th>Tổng số task</th>
                                <th>Tiến độ (%)</th>
                                <th>Trạng thái bảo trì</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="maintenance-requests-table">
                            <?php if (empty($requests)): ?>
                                <tr>
                                    <td colspan="11" class="text-center py-5">
                                        <i class="fas fa-tools fa-3x text-muted mb-3 d-block"></i>
                                        <h5 class="text-muted">Chưa có yêu cầu bảo trì nào</h5>
                                        <p class="text-muted">Bấm nút "Tạo yêu cầu bảo trì" để bắt đầu</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($requests as $request): ?>
                                <tr>
                                    <td>
                                        <strong class="text-warning"><?php echo htmlspecialchars($request['request_code']); ?></strong>
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
                                        <?php if ($request['expected_start']): ?>
                                            <div class="text-wrap" style="white-space: pre-line;">
                                                <strong>Từ</strong><br>
                                                <?php echo date('d/m/Y', strtotime($request['expected_start'])); ?><br>
                                                <strong>Đến</strong><br>
                                                <?php echo $request['expected_end'] ? date('d/m/Y', strtotime($request['expected_end'])) : '(Chưa xác định)'; ?>
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
                                        <span class="text-dark"><?php echo htmlspecialchars($request['maintenance_status']); ?></span>
                                    </td>
                                    <td>
                                        <span class="text-dark"><?php echo $request['total_cases'] ?? 0; ?></span>
                                    </td>
                                    <td>
                                        <span class="text-dark"><?php echo $request['total_tasks'] ?? 0; ?></span>
                                    </td>
                                    <td>
                                        <div class="progress" style="width: 80px; height: 20px;"><div class="progress-bar bg-warning" style="width: <?php echo $request['progress_percentage'] ?? 0; ?>%" title="<?php echo $request['progress_percentage'] ?? 0; ?>%"><small><?php echo $request['progress_percentage'] ?? 0; ?>%</small></div></div>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $statusClass = '';
                                        switch ($request['maintenance_status']) {
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
                                            <?php echo htmlspecialchars($request['maintenance_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-warning" onclick="editMaintenanceRequest(<?php echo $request['id']; ?>)" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if ($current_role !== 'user'): ?>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteMaintenanceRequest(<?php echo $request['id']; ?>)" title="Xóa">
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
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<!-- Modal tạo yêu cầu bảo trì -->
<div class="modal fade" id="addMaintenanceRequestModal" tabindex="-1" aria-labelledby="addMaintenanceRequestModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content maintenance-request-modal">
      <div class="modal-header">
        <h5 class="modal-title" id="addMaintenanceRequestModalLabel">Tạo yêu cầu bảo trì</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="addMaintenanceRequestForm" method="POST" action="#">
        <div class="modal-body">
          <div class="row g-4">
            <!-- Cột trái: Hợp đồng -->
            <div class="col-md-6">
              <h6 class="text-warning mb-3"><i class="fas fa-file-contract me-2"></i>HỢP ĐỒNG</h6>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Mã yêu cầu:</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="request_code" id="request_code" readonly value="YC<?php echo date('y').date('m'); ?>001">
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Số hợp đồng PO:</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="po_number" id="po_number" placeholder="Nhập số hợp đồng PO" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                  <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox" value="1" id="no_contract_po" name="no_contract_po" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                    <label class="form-check-label" for="no_contract_po">Không có HĐ/PO</label>
                  </div>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Loại hợp đồng:</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="contract_type" id="contract_type" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                    <option value="">-- Chọn loại hợp đồng --</option>
                    <option value="Hợp đồng bảo trì hệ thống">Hợp đồng bảo trì hệ thống</option>
                    <option value="Hợp đồng bảo trì thiết bị">Hợp đồng bảo trì thiết bị</option>
                    <option value="Hợp đồng bảo trì phần mềm">Hợp đồng bảo trì phần mềm</option>
                    <option value="Hợp đồng bảo trì mạng">Hợp đồng bảo trì mạng</option>
                    <option value="Hợp đồng bảo trì bảo mật">Hợp đồng bảo trì bảo mật</option>
                    <option value="Hợp đồng bảo trì định kỳ">Hợp đồng bảo trì định kỳ</option>
                  </select>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Loại yêu cầu chi tiết:</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="request_detail_type" id="request_detail_type" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                    <option value="">-- Chọn loại yêu cầu chi tiết --</option>
                    <option value="Bảo trì hệ thống định kỳ">Bảo trì hệ thống định kỳ</option>
                    <option value="Bảo trì thiết bị mạng">Bảo trì thiết bị mạng</option>
                    <option value="Bảo trì máy chủ">Bảo trì máy chủ</option>
                    <option value="Bảo trì phần mềm">Bảo trì phần mềm</option>
                    <option value="Bảo trì bảo mật">Bảo trì bảo mật</option>
                    <option value="Khắc phục sự cố">Khắc phục sự cố</option>
                    <option value="Nâng cấp hệ thống">Nâng cấp hệ thống</option>
                    <option value="Kiểm tra hiệu suất">Kiểm tra hiệu suất</option>
                    <option value="Sao lưu dữ liệu">Sao lưu dữ liệu</option>
                    <option value="Cập nhật bảo mật">Cập nhật bảo mật</option>
                  </select>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Email subject (Khách hàng):</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="email_subject_customer" id="email_subject_customer" placeholder="Nhập email subject cho khách hàng" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Email subject (Nội bộ):</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="email_subject_internal" id="email_subject_internal" placeholder="Nhập email subject cho nội bộ" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Bắt đầu dự kiến:</label>
                </div>
                <div class="col-md-9">
                  <input type="date" class="form-control" name="expected_start" id="expected_start" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Kết thúc dự kiến:</label>
                </div>
                <div class="col-md-9">
                  <input type="date" class="form-control" name="expected_end" id="expected_end" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                </div>
              </div>
            </div>
            
            <!-- Cột phải: Khách hàng & Xử lý -->
            <div class="col-md-6">
              <h6 class="text-success mb-3"><i class="fas fa-users me-2"></i>KHÁCH HÀNG</h6>
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Khách hàng:</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="customer_id" id="customer_id" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                    <option value="">-- Chọn khách hàng --</option>
                    <?php
                    $partners = $pdo->query("SELECT id, name, contact_person, contact_phone FROM partner_companies ORDER BY name ASC")->fetchAll();
                    foreach ($partners as $partner) {
                      echo '<option value="'.$partner['id'].'">'.htmlspecialchars($partner['name']).'</option>';
                    }
                    ?>
                  </select>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Người liên hệ:</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="contact_person" id="contact_person" readonly placeholder="Sẽ tự động điền theo khách hàng">
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Điện thoại:</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="contact_phone" id="contact_phone" readonly placeholder="Sẽ tự động điền theo khách hàng">
                </div>
              </div>
              
              <h6 class="text-warning mb-3 mt-4"><i class="fas fa-cogs me-2"></i>XỬ LÝ</h6>
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Sale phụ trách:</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="sale_id" id="sale_id">
                    <option value="">-- Chọn sale phụ trách --</option>
                    <?php
                    $sales = $pdo->query("SELECT id, fullname FROM staffs WHERE department = 'SALE Dept.' AND status = 'active' ORDER BY fullname ASC")->fetchAll();
                    
                    if (empty($sales)) {
                      echo '<option value="">-- Không có nhân viên SALE Dept --</option>';
                    } else {
                      foreach ($sales as $sale) {
                        echo '<option value="'.$sale['id'].'">'.htmlspecialchars($sale['fullname']).'</option>';
                      }
                    }
                    ?>
                  </select>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Ghi chú người yêu cầu:</label>
                </div>
                <div class="col-md-9">
                  <textarea class="form-control" name="requester_notes" id="requester_notes" rows="2" placeholder="Nhập ghi chú"></textarea>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Quản lý bảo trì:</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="maintenance_manager" id="maintenance_manager" value="Trần Nguyễn Anh Khoa" readonly>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Trạng thái bảo trì:</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="maintenance_status" id="maintenance_status">
                    <option value="Tiếp nhận">Tiếp nhận</option>
                    <option value="Đang xử lý">Đang xử lý</option>
                    <option value="Hoàn thành">Hoàn thành</option>
                    <option value="Huỷ">Huỷ</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-warning">Lưu yêu cầu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal chỉnh sửa yêu cầu bảo trì - Chỉ hiển thị khi có yêu cầu bảo trì -->
<?php if (!empty($requests)): ?>
<div class="modal fade" id="editMaintenanceRequestModal" tabindex="-1" aria-labelledby="editMaintenanceRequestModalLabel">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content maintenance-request-modal">
      <div class="modal-header">
        <h5 class="modal-title" id="editMaintenanceRequestModalLabel">Chỉnh sửa yêu cầu bảo trì</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="editMaintenanceRequestForm" method="POST" action="#">
        <div class="modal-body">
          <div class="row g-4">
            <!-- Cột trái: Hợp đồng -->
            <div class="col-md-6">
              <h6 class="text-warning mb-3"><i class="fas fa-file-contract me-2"></i>HỢP ĐỒNG</h6>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Mã yêu cầu:</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="edit_request_code" id="edit_request_code" readonly>
                  <input type="hidden" name="edit_request_id" id="edit_request_id">
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Số hợp đồng PO:</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="edit_po_number" id="edit_po_number" placeholder="Nhập số hợp đồng PO">
                  <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox" value="1" id="edit_no_contract_po" name="edit_no_contract_po">
                    <label class="form-check-label" for="edit_no_contract_po">Không có HĐ/PO</label>
                  </div>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Loại hợp đồng:</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="edit_contract_type" id="edit_contract_type">
                    <option value="">-- Chọn loại hợp đồng --</option>
                    <option value="Hợp đồng bảo trì hệ thống">Hợp đồng bảo trì hệ thống</option>
                    <option value="Hợp đồng bảo trì thiết bị">Hợp đồng bảo trì thiết bị</option>
                    <option value="Hợp đồng bảo trì phần mềm">Hợp đồng bảo trì phần mềm</option>
                    <option value="Hợp đồng bảo trì mạng">Hợp đồng bảo trì mạng</option>
                    <option value="Hợp đồng bảo trì bảo mật">Hợp đồng bảo trì bảo mật</option>
                    <option value="Hợp đồng bảo trì định kỳ">Hợp đồng bảo trì định kỳ</option>
                  </select>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Loại yêu cầu chi tiết:</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="edit_request_detail_type" id="edit_request_detail_type">
                    <option value="">-- Chọn loại yêu cầu chi tiết --</option>
                    <option value="Bảo trì hệ thống định kỳ">Bảo trì hệ thống định kỳ</option>
                    <option value="Bảo trì thiết bị mạng">Bảo trì thiết bị mạng</option>
                    <option value="Bảo trì máy chủ">Bảo trì máy chủ</option>
                    <option value="Bảo trì phần mềm">Bảo trì phần mềm</option>
                    <option value="Bảo trì bảo mật">Bảo trì bảo mật</option>
                    <option value="Khắc phục sự cố">Khắc phục sự cố</option>
                    <option value="Nâng cấp hệ thống">Nâng cấp hệ thống</option>
                    <option value="Kiểm tra hiệu suất">Kiểm tra hiệu suất</option>
                    <option value="Sao lưu dữ liệu">Sao lưu dữ liệu</option>
                    <option value="Cập nhật bảo mật">Cập nhật bảo mật</option>
                  </select>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Email subject (Khách hàng):</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="edit_email_subject_customer" id="edit_email_subject_customer" placeholder="Nhập email subject cho khách hàng">
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Email subject (Nội bộ):</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="edit_email_subject_internal" id="edit_email_subject_internal" placeholder="Nhập email subject cho nội bộ">
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Bắt đầu dự kiến:</label>
                </div>
                <div class="col-md-9">
                  <input type="date" class="form-control" name="edit_expected_start" id="edit_expected_start">
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Kết thúc dự kiến:</label>
                </div>
                <div class="col-md-9">
                  <input type="date" class="form-control" name="edit_expected_end" id="edit_expected_end">
                </div>
              </div>
            </div>
            
            <!-- Cột phải: Khách hàng & Xử lý -->
            <div class="col-md-6">
              <h6 class="text-success mb-3"><i class="fas fa-users me-2"></i>KHÁCH HÀNG</h6>
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Khách hàng:</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select select2" name="edit_customer_id" id="edit_customer_id">
                    <option value="">-- Chọn khách hàng --</option>
                    <?php
                    foreach ($partners as $partner) {
                      echo '<option value="'.$partner['id'].'">'.htmlspecialchars($partner['name']).'</option>';
                    }
                    ?>
                  </select>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Người liên hệ:</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="edit_contact_person" id="edit_contact_person" readonly placeholder="Sẽ tự động điền theo khách hàng">
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Số điện thoại:</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="edit_contact_phone" id="edit_contact_phone" readonly placeholder="Sẽ tự động điền theo khách hàng">
                </div>
              </div>
              
              <h6 class="text-info mb-3"><i class="fas fa-cogs me-2"></i>XỬ LÝ</h6>
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Sale phụ trách:</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select select2" name="edit_sale_id" id="edit_sale_id">
                    <option value="">-- Chọn sale phụ trách --</option>
                    <?php
                    foreach ($sales as $sale) {
                      echo '<option value="'.$sale['id'].'">'.htmlspecialchars($sale['fullname']).'</option>';
                    }
                    ?>
                  </select>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Ghi chú người yêu cầu:</label>
                </div>
                <div class="col-md-9">
                  <textarea class="form-control" name="edit_requester_notes" id="edit_requester_notes" rows="2" placeholder="Nhập ghi chú"></textarea>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Quản lý bảo trì:</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="edit_maintenance_manager" id="edit_maintenance_manager" value="Trần Nguyễn Anh Khoa" readonly>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Trạng thái bảo trì:</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="edit_maintenance_status" id="edit_maintenance_status">
                    <option value="Tiếp nhận">Tiếp nhận</option>
                    <option value="Đang xử lý">Đang xử lý</option>
                    <option value="Hoàn thành">Hoàn thành</option>
                    <option value="Huỷ">Huỷ</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Phần thứ 3: Quản lý Case bảo trì -->
          <div class="border-top pt-4 mt-4 bg-light" id="maintenance-cases-section" style="display: none;">
            <div class="row">
              <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="text-warning mb-0"><i class="fas fa-tasks me-2"></i>QUẢN LÝ CASE BẢO TRÌ</h6>
                  <button type="button" class="btn btn-warning btn-sm" onclick="if(prepareCreateMaintenanceCase()) { $('#createMaintenanceCaseModal').modal('show'); }">
                    <i class="fas fa-plus me-1"></i>Tạo case bảo trì
                  </button>
                </div>
                
                <!-- Bảng danh sách case bảo trì -->
                <div class="table-responsive">
                  <table class="table table-sm table-hover table-bordered">
                    <thead class="table-light">
                      <tr>
                        <th class="text-center">STT</th>
                        <th class="text-center">Số case</th>
                        <th class="text-center">Mô tả case</th>
                        <th class="text-center">Ghi chú</th>
                        <th class="text-center">Người phụ trách</th>
                        <th class="text-center">Ngày bắt đầu</th>
                        <th class="text-center">Ngày kết thúc</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-center">Tổng số task</th>
                        <th class="text-center">Task hoàn thành</th>
                        <th class="text-center">Hình thức</th>
                        <th class="text-center">Thao tác</th>
                      </tr>
                    </thead>
                    <tbody id="maintenance-cases-table">
                      <tr>
                        <td colspan="12" class="text-center text-muted py-3">
                          <i class="fas fa-inbox fa-2x mb-2"></i><br>
                          Chưa có case bảo trì nào
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
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
          <button type="button" class="btn btn-warning" onclick="updateMaintenanceRequest()">Cập nhật yêu cầu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal tạo case bảo trì -->
<div class="modal fade" id="createMaintenanceCaseModal" tabindex="-1" aria-labelledby="createMaintenanceCaseModalLabel">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content maintenance-request-modal">
      <div class="modal-header">
        <h5 class="modal-title" id="createMaintenanceCaseModalLabel">Tạo case bảo trì</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="createMaintenanceCaseForm" method="POST" action="#">
        <input type="hidden" id="maintenance_request_id" name="maintenance_request_id">
        <div class="modal-body">
          <div class="row g-4">
            <!-- Cột trái -->
            <div class="col-md-6">
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Số case:</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="case_code" id="case_code" readonly>
                </div>
              </div>
              <div class="row mb-3 d-none">
                <div class="col-md-3">
                  <label class="form-label">Mã yêu cầu:</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="request_code" id="case_request_code" readonly>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Loại yêu cầu:</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="request_type" id="case_request_type" required>
                    <option value="">-- Chọn loại yêu cầu --</option>
                    <option value="Bảo trì hệ thống định kỳ">Bảo trì hệ thống định kỳ</option>
                    <option value="Bảo trì thiết bị mạng">Bảo trì thiết bị mạng</option>
                    <option value="Bảo trì máy chủ">Bảo trì máy chủ</option>
                    <option value="Bảo trì phần mềm">Bảo trì phần mềm</option>
                    <option value="Bảo trì bảo mật">Bảo trì bảo mật</option>
                    <option value="Khắc phục sự cố">Khắc phục sự cố</option>
                    <option value="Nâng cấp hệ thống">Nâng cấp hệ thống</option>
                    <option value="Kiểm tra hiệu suất">Kiểm tra hiệu suất</option>
                    <option value="Sao lưu dữ liệu">Sao lưu dữ liệu</option>
                    <option value="Cập nhật bảo mật">Cập nhật bảo mật</option>
                  </select>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Loại yêu cầu chi tiết:</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="request_detail_type" id="case_request_detail_type">
                    <option value="">-- Chọn loại yêu cầu chi tiết --</option>
                    <option value="Bảo trì định kỳ">Bảo trì định kỳ</option>
                    <option value="Bảo trì theo yêu cầu">Bảo trì theo yêu cầu</option>
                    <option value="Bảo trì khẩn cấp">Bảo trì khẩn cấp</option>
                    <option value="Bảo trì phòng ngừa">Bảo trì phòng ngừa</option>
                    <option value="Bảo trì sửa chữa">Bảo trì sửa chữa</option>
                    <option value="Bảo trì nâng cấp">Bảo trì nâng cấp</option>
                    <option value="Bảo trì thay thế">Bảo trì thay thế</option>
                    <option value="Bảo trì kiểm tra">Bảo trì kiểm tra</option>
                    <option value="Bảo trì vệ sinh">Bảo trì vệ sinh</option>
                    <option value="Bảo trì hiệu chỉnh">Bảo trì hiệu chỉnh</option>
                  </select>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Mô tả case:</label>
                </div>
                <div class="col-md-9">
                  <textarea class="form-control" name="case_description" id="case_description" rows="3" placeholder="Nhập mô tả chi tiết về case"></textarea>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Ghi chú:</label>
                </div>
                <div class="col-md-9">
                  <textarea class="form-control" name="notes" id="case_notes" rows="2" placeholder="Nhập ghi chú"></textarea>
                </div>
              </div>
            </div>
            <!-- Cột phải -->
            <div class="col-md-6">
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Người phụ trách:</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="assigned_to" id="case_assigned_to" required>
                    <option value="">-- Chọn người phụ trách --</option>
                    <?php
                    $staffs = $pdo->query("SELECT id, fullname FROM staffs WHERE status = 'active' AND department = 'IT Dept.' AND resigned = 0 ORDER BY fullname ASC")->fetchAll();
                    foreach ($staffs as $staff) {
                      echo '<option value="'.$staff['id'].'">'.htmlspecialchars($staff['fullname']).'</option>';
                    }
                    ?>
                  </select>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Hình thức:</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="work_type" id="case_work_type">
                    <option value="">-- Chọn hình thức --</option>
                    <option value="onsite">Onsite</option>
                    <option value="offsite">Offsite</option>
                    <option value="remote">Remote</option>
                  </select>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Ngày bắt đầu:</label>
                </div>
                <div class="col-md-9">
                  <input type="date" class="form-control" name="start_date" id="case_start_date">
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Ngày kết thúc:</label>
                </div>
                <div class="col-md-9">
                  <input type="date" class="form-control" name="end_date" id="case_end_date">
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Trạng thái:</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="status" id="case_status" required>
                    <option value="Tiếp nhận">Tiếp nhận</option>
                    <option value="Đang xử lý">Đang xử lý</option>
                    <option value="Hoàn thành">Hoàn thành</option>
                    <option value="Huỷ">Huỷ</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-warning">Tạo case</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal tạo task bảo trì -->
<div class="modal fade" id="createMaintenanceTaskModal" tabindex="-1" aria-labelledby="createMaintenanceTaskModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content maintenance-request-modal">
      <div class="modal-header">
        <h5 class="modal-title" id="createMaintenanceTaskModalLabel">
          <i class="fas fa-plus-circle text-warning"></i> Tạo Task Bảo Trì
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="createMaintenanceTaskForm">
          <div class="row g-4">
            <!-- Cột trái: Thông tin cơ bản -->
            <div class="col-md-6">
              <h6 class="text-warning mb-3"><i class="fas fa-info-circle me-2"></i>THÔNG TIN CƠ BẢN</h6>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Số task:</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="task_number" id="task_number" readonly>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Số case:</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="case_code" id="task_case_code" readonly>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Mã yêu cầu:</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="request_code" id="task_request_code" readonly>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label for="task_type" class="form-label">Loại Task <span class="text-danger">*</span></label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="task_type" id="task_type" required>
                    <option value="">-- Chọn loại task --</option>
                    <option value="onsite">Onsite</option>
                    <option value="offsite">Offsite</option>
                    <option value="remote">Remote</option>
                  </select>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label for="task_template" class="form-label">Task mẫu</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="task_template" id="task_template">
                    <option value="">-- Chọn task mẫu --</option>
                    <option value="Kiểm tra thiết bị">Kiểm tra thiết bị</option>
                    <option value="Bảo trì định kỳ">Bảo trì định kỳ</option>
                    <option value="Khắc phục sự cố">Khắc phục sự cố</option>
                    <option value="Cập nhật phần mềm">Cập nhật phần mềm</option>
                    <option value="Sao lưu dữ liệu">Sao lưu dữ liệu</option>
                    <option value="Kiểm tra bảo mật">Kiểm tra bảo mật</option>
                    <option value="Nghiệm thu bảo trì">Nghiệm thu bảo trì</option>
                    <option value="Vệ sinh thiết bị">Vệ sinh thiết bị</option>
                    <option value="Thay thế linh kiện">Thay thế linh kiện</option>
                    <option value="Hiệu chỉnh hệ thống">Hiệu chỉnh hệ thống</option>
                  </select>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label for="task_name" class="form-label">Task <span class="text-danger">*</span></label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="task_name" id="task_name" required placeholder="Nhập tên task cụ thể">
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label for="task_note" class="form-label">Ghi chú</label>
                </div>
                <div class="col-md-9">
                  <textarea class="form-control" name="task_note" id="task_note" rows="2" placeholder="Nhập ghi chú bổ sung"></textarea>
                </div>
              </div>
            </div>
            
            <!-- Cột phải: Thông tin thực hiện -->
            <div class="col-md-6">
              <h6 class="text-success mb-3"><i class="fas fa-cogs me-2"></i>THÔNG TIN THỰC HIỆN</h6>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label for="task_assignee_id" class="form-label">Người thực hiện</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="assignee_id" id="task_assignee_id">
                    <option value="">-- Chọn người thực hiện --</option>
                    <?php
                    $it_staffs = $pdo->query("SELECT id, fullname FROM staffs WHERE department = 'IT Dept.' AND status = 'active' AND resigned = 0 ORDER BY fullname ASC")->fetchAll();
                    
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
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label for="task_start_date" class="form-label">Thời gian bắt đầu:</label>
                </div>
                <div class="col-md-9">
                  <input type="datetime-local" class="form-control" name="start_date" id="task_start_date">
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label for="task_end_date" class="form-label">Thời gian kết thúc:</label>
                </div>
                <div class="col-md-9">
                  <input type="datetime-local" class="form-control" name="end_date" id="task_end_date">
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label for="task_status" class="form-label">Trạng thái</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="status" id="task_status">
                    <option value="Tiếp nhận">Tiếp nhận</option>
                    <option value="Đang xử lý">Đang xử lý</option>
                    <option value="Hoàn thành">Hoàn thành</option>
                    <option value="Huỷ">Huỷ</option>
                  </select>
                </div>
              </div>
              
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Người nhập:</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="created_by_name" id="task_created_by_name" value="<?php echo htmlspecialchars($_SESSION['fullname'] ?? ''); ?>" readonly>
                </div>
              </div>
            </div>
          </div>
          <input type="hidden" name="maintenance_case_id" id="task_maintenance_case_id">
          <input type="hidden" name="maintenance_request_id" id="task_maintenance_request_id">
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times"></i> Hủy
        </button>
        <button type="submit" form="createMaintenanceTaskForm" class="btn btn-warning">
          <i class="fas fa-save"></i> Tạo Task
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal chỉnh sửa task bảo trì -->
<div class="modal fade" id="editMaintenanceTaskModal" tabindex="-1" aria-labelledby="editMaintenanceTaskModalLabel" aria-hidden="true" style="z-index: 1060;">
  <div class="modal-dialog">
    <div class="modal-content maintenance-request-modal">
      <div class="modal-header">
        <h5 class="modal-title" id="editMaintenanceTaskModalLabel">
          <i class="fas fa-edit text-warning"></i> Chỉnh sửa Task Bảo Trì
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editMaintenanceTaskForm">
          <div class="row g-4">
            <!-- Cột trái -->
            <div class="col-md-6">
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Số task:</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="edit_task_code" id="edit_task_code" readonly>
                </div>
              </div>
              <div class="mb-3 row align-items-center">
                <label for="edit_task_type" class="col-md-3 form-label mb-0">Loại Task <span class="text-danger">*</span></label>
                <div class="col-md-9">
                  <select class="form-select" name="edit_task_type" id="edit_task_type" required>
                    <option value="">-- Chọn loại task --</option>
                    <option value="onsite">Onsite</option>
                    <option value="offsite">Offsite</option>
                    <option value="remote">Remote</option>
                  </select>
                </div>
              </div>
              <div class="mb-3 row align-items-center">
                <label for="edit_task_template" class="col-md-3 form-label mb-0">Task mẫu</label>
                <div class="col-md-9">
                  <select class="form-select" name="edit_task_template" id="edit_task_template">
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
              </div>
              <div class="mb-3 row align-items-center">
                <label for="edit_task_name" class="col-md-3 form-label mb-0">Task <span class="text-danger">*</span></label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="edit_task_name" id="edit_task_name" required placeholder="Nhập tên task cụ thể">
                </div>
              </div>
              <div class="mb-3 row align-items-start">
                <label for="edit_task_note" class="col-md-3 form-label mb-0">Ghi chú</label>
                <div class="col-md-9">
                  <textarea class="form-control" name="edit_task_note" id="edit_task_note" rows="2" placeholder="Nhập ghi chú"></textarea>
                </div>
              </div>
            </div>
            <!-- Cột phải -->
            <div class="col-md-6">
              <div class="mb-3 row align-items-center">
                <label for="edit_task_assigned_to" class="col-md-3 form-label mb-0">Người thực hiện</label>
                <div class="col-md-9">
                  <select class="form-select" name="edit_task_assigned_to" id="edit_task_assigned_to">
                    <option value="">-- Chọn người thực hiện --</option>
                  </select>
                </div>
              </div>
              <div class="mb-3 row align-items-center">
                <label for="edit_task_start_date" class="col-md-3 form-label mb-0">Thời gian bắt đầu:</label>
                <div class="col-md-9">
                  <input type="datetime-local" class="form-control" name="edit_task_start_date" id="edit_task_start_date">
                </div>
              </div>
              <div class="mb-3 row align-items-center">
                <label for="edit_task_end_date" class="col-md-3 form-label mb-0">Thời gian kết thúc:</label>
                <div class="col-md-9">
                  <input type="datetime-local" class="form-control" name="edit_task_end_date" id="edit_task_end_date">
                </div>
              </div>
              <div class="mb-3 row align-items-center">
                <label for="edit_task_status" class="col-md-3 form-label mb-0">Trạng thái</label>
                <div class="col-md-9">
                  <select class="form-select" name="edit_task_status" id="edit_task_status">
                    <option value="Tiếp nhận">Tiếp nhận</option>
                    <option value="Đang xử lý">Đang xử lý</option>
                    <option value="Hoàn thành">Hoàn thành</option>
                    <option value="Huỷ">Huỷ</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <input type="hidden" name="edit_task_id" id="edit_task_id">
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times"></i> Hủy
        </button>
        <button type="submit" form="editMaintenanceTaskForm" class="btn btn-warning">
          <i class="fas fa-save"></i> Cập nhật Task
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal chỉnh sửa case bảo trì -->
<div class="modal fade" id="editMaintenanceCaseModal" tabindex="-1" aria-labelledby="editMaintenanceCaseModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content maintenance-request-modal">
      <div class="modal-header">
        <h5 class="modal-title" id="editMaintenanceCaseModalLabel">Chỉnh sửa case bảo trì</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="editMaintenanceCaseForm" method="POST" action="#">
        <input type="hidden" id="edit_maintenance_request_id" name="edit_maintenance_request_id">
        <input type="hidden" id="edit_case_id" name="edit_case_id">
        <div class="modal-body">
          <div class="row g-4">
            <!-- Cột trái -->
            <div class="col-md-6">
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Số case:</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="edit_case_code" id="edit_case_code" readonly>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Mã yêu cầu:</label>
                </div>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="edit_case_request_code" id="edit_case_request_code" readonly>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Loại yêu cầu:</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="edit_request_type" id="edit_request_type" required>
                    <option value="">-- Chọn loại yêu cầu --</option>
                    <option value="Bảo trì hệ thống định kỳ">Bảo trì hệ thống định kỳ</option>
                    <option value="Bảo trì thiết bị mạng">Bảo trì thiết bị mạng</option>
                    <option value="Bảo trì máy chủ">Bảo trì máy chủ</option>
                    <option value="Bảo trì phần mềm">Bảo trì phần mềm</option>
                    <option value="Bảo trì bảo mật">Bảo trì bảo mật</option>
                    <option value="Khắc phục sự cố">Khắc phục sự cố</option>
                    <option value="Nâng cấp hệ thống">Nâng cấp hệ thống</option>
                    <option value="Kiểm tra hiệu suất">Kiểm tra hiệu suất</option>
                    <option value="Sao lưu dữ liệu">Sao lưu dữ liệu</option>
                    <option value="Cập nhật bảo mật">Cập nhật bảo mật</option>
                  </select>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Loại yêu cầu chi tiết:</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="edit_request_detail_type" id="edit_case_request_detail_type">
                    <option value="">-- Chọn loại yêu cầu chi tiết --</option>
                    <option value="Bảo trì định kỳ">Bảo trì định kỳ</option>
                    <option value="Bảo trì theo yêu cầu">Bảo trì theo yêu cầu</option>
                    <option value="Bảo trì khẩn cấp">Bảo trì khẩn cấp</option>
                    <option value="Bảo trì phòng ngừa">Bảo trì phòng ngừa</option>
                    <option value="Bảo trì sửa chữa">Bảo trì sửa chữa</option>
                    <option value="Bảo trì nâng cấp">Bảo trì nâng cấp</option>
                    <option value="Bảo trì thay thế">Bảo trì thay thế</option>
                    <option value="Bảo trì kiểm tra">Bảo trì kiểm tra</option>
                    <option value="Bảo trì vệ sinh">Bảo trì vệ sinh</option>
                    <option value="Bảo trì hiệu chỉnh">Bảo trì hiệu chỉnh</option>
                  </select>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Mô tả case:</label>
                </div>
                <div class="col-md-9">
                  <textarea class="form-control" name="edit_case_description" id="edit_case_description" rows="3" placeholder="Nhập mô tả chi tiết về case"></textarea>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Ghi chú:</label>
                </div>
                <div class="col-md-9">
                  <textarea class="form-control" name="edit_notes" id="edit_notes" rows="2" placeholder="Nhập ghi chú"></textarea>
                </div>
              </div>
            </div>
            <!-- Cột phải -->
            <div class="col-md-6">
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Người phụ trách:</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="edit_assigned_to" id="edit_assigned_to" required>
                    <option value="">-- Chọn người phụ trách --</option>
                    <?php
                    $staffs = $pdo->query("SELECT id, fullname FROM staffs WHERE status = 'active' AND department = 'IT Dept.' AND resigned = 0 ORDER BY fullname ASC")->fetchAll();
                    foreach ($staffs as $staff) {
                      echo '<option value="'.$staff['id'].'">'.htmlspecialchars($staff['fullname']).'</option>';
                    }
                    ?>
                  </select>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Hình thức:</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="edit_work_type" id="edit_work_type">
                    <option value="">-- Chọn hình thức --</option>
                    <option value="onsite">Onsite</option>
                    <option value="offsite">Offsite</option>
                    <option value="remote">Remote</option>
                  </select>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Ngày bắt đầu:</label>
                </div>
                <div class="col-md-9">
                  <input type="date" class="form-control" name="edit_start_date" id="edit_start_date">
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Ngày kết thúc:</label>
                </div>
                <div class="col-md-9">
                  <input type="date" class="form-control" name="edit_end_date" id="edit_end_date">
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-3">
                  <label class="form-label">Trạng thái:</label>
                </div>
                <div class="col-md-9">
                  <select class="form-select" name="edit_status" id="edit_status" required>
                    <option value="Tiếp nhận">Tiếp nhận</option>
                    <option value="Đang xử lý">Đang xử lý</option>
                    <option value="Hoàn thành">Hoàn thành</option>
                    <option value="Huỷ">Huỷ</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Phần thứ 3: Quản lý Task bảo trì -->
          <div class="border-top pt-4 mt-4 bg-light">
            <div class="row">
              <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="text-warning mb-0"><i class="fas fa-tasks me-2"></i>QUẢN LÝ TASK BẢO TRÌ</h6>
                  <button type="button" class="btn btn-warning btn-sm" onclick="prepareCreateMaintenanceTask()">
                    <i class="fas fa-plus me-1"></i>Tạo task bảo trì
                  </button>
                </div>
                
                <!-- Bảng danh sách task bảo trì -->
                <div class="table-responsive">
                  <table class="table table-sm table-hover table-bordered">
                    <thead class="table-light">
                      <tr>
                        <th class="text-center">STT</th>
                        <th class="text-center">Số task</th>
                        <th class="text-center">Loại task</th>
                        <th class="text-center">Task mẫu</th>
                        <th class="text-center">Task</th>
                        <th class="text-center">Thời gian bắt đầu</th>
                        <th class="text-center">Thời gian kết thúc</th>
                        <th class="text-center">Người thực hiện</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-center">Thao tác</th>
                      </tr>
                    </thead>
                    <tbody id="edit-maintenance-tasks-table">
                      <tr>
                        <td colspan="10" class="text-center text-muted py-3">
                          <i class="fas fa-inbox fa-2x mb-2"></i><br>
                          Chưa có task bảo trì nào
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
          <button type="submit" class="btn btn-warning">Cập nhật case</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="assets/js/alert.js?v=<?php echo filemtime('assets/js/alert.js'); ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Lưu trữ dữ liệu khách hàng và sale
    let partnerData = <?php echo json_encode($partners); ?>;
    let salesData = <?php echo json_encode($sales); ?>;
    
    // Khởi tạo Select2 cho trường khách hàng (JS chung)
    function initializeSelect2() {
        $('#customer_id').select2({
            theme: 'bootstrap-5',
            placeholder: '-- Chọn khách hàng --',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#addMaintenanceRequestModal'),
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
            dropdownParent: $('#addMaintenanceRequestModal'),
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
    
    // Khởi tạo Select2 cho modal chỉnh sửa (chỉ khi có yêu cầu bảo trì)
    <?php if (!empty($requests)): ?>
    function initializeEditSelect2() {
        $('#edit_customer_id').select2({
            theme: 'bootstrap-5',
            placeholder: '-- Chọn khách hàng --',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#editMaintenanceRequestModal'),
            language: {
                noResults: function() {
                    return "Không tìm thấy khách hàng";
                },
                searching: function() {
                    return "Đang tìm kiếm...";
                }
            }
        });
        
        $('#edit_sale_id').select2({
            theme: 'bootstrap-5',
            placeholder: '-- Chọn sale phụ trách --',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#editMaintenanceRequestModal'),
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
    <?php endif; ?>
    
    // Khởi tạo Select2 khi modal hiển thị
    $('#addMaintenanceRequestModal').on('shown.bs.modal', function() {
        initializeSelect2();
    });
    
    // Fix accessibility issue - sử dụng inert attribute
    $('#addMaintenanceRequestModal').on('hidden.bs.modal', function() {
        // Sử dụng inert thay vì aria-hidden
        this.setAttribute('inert', '');
        this.removeAttribute('aria-hidden');
    });
    
    $('#addMaintenanceRequestModal').on('show.bs.modal', function() {
        // Xóa inert khi modal hiển thị
        this.removeAttribute('inert');
    });
    
    // Khởi tạo Select2 khi modal chỉnh sửa hiển thị (chỉ khi modal tồn tại)
    <?php if (!empty($requests)): ?>
    $('#editMaintenanceRequestModal').on('shown.bs.modal', function() {
        initializeEditSelect2();
        setupEditCheckboxHandler();
        // Load danh sách cases bảo trì
        loadMaintenanceCases();
    });
    
    // Fix accessibility issue - sử dụng inert attribute
    $('#editMaintenanceRequestModal').on('hidden.bs.modal', function() {
        // Sử dụng inert thay vì aria-hidden
        this.setAttribute('inert', '');
        this.removeAttribute('aria-hidden');
    });
    
    $('#editMaintenanceRequestModal').on('show.bs.modal', function() {
        // Xóa inert khi modal hiển thị
        this.removeAttribute('inert');
    });
    
    // Khởi tạo khi modal tạo case hiển thị
    $('#createMaintenanceCaseModal').on('shown.bs.modal', function() {
        // Load mã case tiếp theo
        loadNextCaseNumber();
        // Load danh sách tasks (nếu có case ID)
        loadMaintenanceTasks();
    });
    
    // Fix accessibility issue - sử dụng inert attribute
    $('#createMaintenanceCaseModal').on('hidden.bs.modal', function() {
        // Sử dụng inert thay vì aria-hidden
        this.setAttribute('inert', '');
        this.removeAttribute('aria-hidden');
    });
    
    $('#createMaintenanceCaseModal').on('show.bs.modal', function() {
        // Xóa inert khi modal hiển thị
        this.removeAttribute('inert');
    });
    

    
    // Khởi tạo Select2 ngay lập tức nếu cần
    initializeSelect2();
    
    // Fix accessibility issues cho tất cả modals
    $('#createMaintenanceTaskModal').on('hidden.bs.modal', function() {
        this.setAttribute('inert', '');
        this.removeAttribute('aria-hidden');
    });
    
    $('#createMaintenanceTaskModal').on('show.bs.modal', function() {
        this.removeAttribute('inert');
        // Load mã task tiếp theo
        loadNextTaskNumber();
    });
    
    // Modal edit task accessibility
    $('#editMaintenanceTaskModal').on('show.bs.modal', function() {
        this.removeAttribute('inert');
    });
    
    $('#editMaintenanceTaskModal').on('hidden.bs.modal', function() {
        this.setAttribute('inert', '');
        // Cleanup
        window.currentEditTaskId = null;
    });
    

    
    $('#editMaintenanceCaseModal').on('hidden.bs.modal', function() {
        this.setAttribute('inert', '');
        this.removeAttribute('aria-hidden');
    });
    
    $('#editMaintenanceCaseModal').on('show.bs.modal', function() {
        this.removeAttribute('inert');
    });
    
    // Load danh sách tasks khi modal edit case được mở
    $('#editMaintenanceCaseModal').on('shown.bs.modal', function() {
        // Load danh sách tasks cho case hiện tại
        if (typeof window.loadMaintenanceTasks === 'function') {
            // Đợi một chút để đảm bảo các trường đã được set giá trị
            setTimeout(() => {
                window.loadMaintenanceTasks();
            }, 200);
        }
    });
    
    // Thêm event handler cho khi modal edit case được mở để đảm bảo load tasks
    $('#editMaintenanceCaseModal').on('show.bs.modal', function() {
        // Đảm bảo load tasks sau khi modal đã hiển thị hoàn toàn
        setTimeout(() => {
            if (typeof window.loadMaintenanceTasks === 'function') {
                window.loadMaintenanceTasks();
            }
        }, 300);
    });
    
    // Override Bootstrap modal behavior để tránh aria-hidden
    $(document).on('hidden.bs.modal', '.modal', function() {
        // Đảm bảo tất cả modals đều sử dụng inert thay vì aria-hidden
        if (!this.hasAttribute('inert')) {
            this.setAttribute('inert', '');
        }
        if (this.hasAttribute('aria-hidden')) {
            this.removeAttribute('aria-hidden');
        }
    });
    
    $(document).on('show.bs.modal', '.modal', function() {
        // Xóa inert khi modal hiển thị
        this.removeAttribute('inert');
    });
    
    // Override Bootstrap modal methods để ngăn aria-hidden
    const originalModalHide = $.fn.modal.Constructor.prototype.hide;
    $.fn.modal.Constructor.prototype.hide = function() {
        // Gọi method gốc
        originalModalHide.call(this);
        
        // Sau khi hide, đảm bảo sử dụng inert thay vì aria-hidden
        const modalElement = this._element;
        if (modalElement) {
            setTimeout(() => {
                modalElement.setAttribute('inert', '');
                modalElement.removeAttribute('aria-hidden');
            }, 0);
        }
    };
    
    const originalModalShow = $.fn.modal.Constructor.prototype.show;
    $.fn.modal.Constructor.prototype.show = function() {
        // Xóa inert trước khi show
        const modalElement = this._element;
        if (modalElement) {
            modalElement.removeAttribute('inert');
        }
        
        // Gọi method gốc
        originalModalShow.call(this);
    };
    
    // Sử dụng MutationObserver để theo dõi thay đổi aria-hidden
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'aria-hidden') {
                const modal = mutation.target;
                if (modal.classList.contains('modal') && modal.getAttribute('aria-hidden') === 'true') {
                    // Ngay lập tức thay thế aria-hidden bằng inert
                    modal.setAttribute('inert', '');
                    modal.removeAttribute('aria-hidden');
                }
            }
        });
    });
    
    // Bắt đầu theo dõi tất cả modals
    document.addEventListener('DOMContentLoaded', function() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(function(modal) {
            observer.observe(modal, {
                attributes: true,
                attributeFilter: ['aria-hidden']
            });
        });
        
        // Backup: Kiểm tra định kỳ để đảm bảo không có aria-hidden
        setInterval(function() {
            const hiddenModals = document.querySelectorAll('.modal[aria-hidden="true"]');
            hiddenModals.forEach(function(modal) {
                modal.setAttribute('inert', '');
                modal.removeAttribute('aria-hidden');
            });
        }, 100);
    });
    
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
    
    // Xử lý khi chọn khách hàng trong modal chỉnh sửa (Select2)
    $('#edit_customer_id').on('select2:select', function(e) {
        const customerId = e.params.data.id;
        const contactPersonInput = document.getElementById('edit_contact_person');
        const contactPhoneInput = document.getElementById('edit_contact_phone');
        
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
    
    // Xử lý khi xóa lựa chọn khách hàng trong modal chỉnh sửa
    $('#edit_customer_id').on('select2:clear', function(e) {
        const contactPersonInput = document.getElementById('edit_contact_person');
        const contactPhoneInput = document.getElementById('edit_contact_phone');
        
        // Reset người liên hệ và điện thoại
        contactPersonInput.value = '';
        contactPhoneInput.value = '';
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
    
    // Xử lý checkbox "Không có HĐ/PO" cho modal chỉnh sửa
    function setupEditCheckboxHandler() {
        const checkbox = document.getElementById('edit_no_contract_po');
        const poInput = document.getElementById('edit_po_number');
        
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
        const requestCodeElement = document.getElementById('request_code');
        if (!requestCodeElement) {
            return; // Element không tồn tại, thoát khỏi function
        }
        
        fetch('api/get_next_maintenance_request_number.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    requestCodeElement.value = data.request_code;
                } else {
                    // Fallback nếu API lỗi
                    const year = new Date().getFullYear().toString().slice(-2);
                    const month = (new Date().getMonth() + 1).toString().padStart(2, '0');
                    requestCodeElement.value = `YC${year}${month}001`;
                }
            })
            .catch(error => {
                // Fallback nếu API lỗi
                const year = new Date().getFullYear().toString().slice(-2);
                const month = (new Date().getMonth() + 1).toString().padStart(2, '0');
                requestCodeElement.value = `YC${year}${month}001`;
            });
    }
    
    // Cập nhật mã yêu cầu khi mở modal
    const modal = document.getElementById('addMaintenanceRequestModal');
    if (modal) {
        function modalShowHandler() {
            generateRequestCode();
            setupCheckboxHandler();
        }
        
        modal.addEventListener('shown.bs.modal', modalShowHandler);
    }
    

    
    // Xử lý submit form tạo case bảo trì (chỉ khi form tồn tại)
    const createMaintenanceCaseForm = document.getElementById('createMaintenanceCaseForm');
    if (createMaintenanceCaseForm) {
        createMaintenanceCaseForm.addEventListener('submit', function(e) {
            e.preventDefault();
            // Gọi function createMaintenanceCase từ file JS
            createMaintenanceCase();
        });
    }
    
    // Xử lý submit form tạo task bảo trì (chỉ khi form tồn tại)
    const createMaintenanceTaskForm = document.getElementById('createMaintenanceTaskForm');
    if (createMaintenanceTaskForm) {
        createMaintenanceTaskForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validation
        const requiredFields = ['task_type', 'task_name'];
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
        
        fetch('api/create_maintenance_task.php', {
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
                    showAlert('Tạo task bảo trì thành công!', 'success');
                } else {
                    alert('Tạo task bảo trì thành công!');
                }
                
                // Lấy case ID và request ID từ form data
                const caseId = formData.maintenance_case_id;
                const requestId = formData.maintenance_request_id;
                
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('createMaintenanceTaskModal'));
                if (modal) {
                    modal.hide();
                }
                
                // Reload tasks table bằng cách gọi function JS
                if (typeof window.loadMaintenanceTasks === 'function') {
                    window.loadMaintenanceTasks();
                }
                
                // Reload danh sách cases để cập nhật số lượng tasks
                if (typeof window.loadMaintenanceCases === 'function') {
                    window.loadMaintenanceCases();
                }
                
                // Reset form
                e.target.reset();
            } else {
                if (typeof showAlert === 'function') {
                    showAlert(data.error || 'Có lỗi xảy ra khi tạo task', 'error');
                } else {
                    alert(data.error || 'Có lỗi xảy ra khi tạo task');
                }
            }
        })
        .catch(error => {
            if (typeof showAlert === 'function') {
                showAlert('Có lỗi xảy ra khi tạo task', 'error');
            } else {
                alert('Có lỗi xảy ra khi tạo task');
            }
        });
    });
    
    // Xử lý submit form chỉnh sửa yêu cầu bảo trì - Đã chuyển sang file JavaScript
    // document.getElementById('editMaintenanceRequestForm').addEventListener('submit', function(e) {
    //     e.preventDefault();
    //     // ... code đã chuyển sang maintenance_requests.js
    // });

});

// Function xóa yêu cầu bảo trì
function deleteRequest(requestId) {
    if (!confirm('Bạn có chắc chắn muốn xóa yêu cầu bảo trì này?\n\nLưu ý: Tất cả các case bảo trì liên quan cũng sẽ bị xóa!')) {
        return;
    }
    
    fetch('api/delete_maintenance_request.php', {
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
                showAlert('Xóa yêu cầu bảo trì thành công!', 'success');
            } else {
                alert('Xóa yêu cầu bảo trì thành công!');
            }
            location.reload();
        } else {
            if (typeof showAlert === 'function') {
                showAlert('Lỗi: ' + (data.message || 'Không thể xóa yêu cầu bảo trì'), 'error');
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể xóa yêu cầu bảo trì'));
            }
        }
    })
    .catch(error => {
        if (typeof showAlert === 'function') {
            showAlert('Lỗi kết nối: ' + error.message, 'error');
        } else {
            alert('Lỗi kết nối: ' + error.message);
        }
    });
}

// Function chỉnh sửa yêu cầu bảo trì
function editRequest(requestId) {
    // Gọi function từ file JavaScript
    editMaintenanceRequest(requestId);
}

// Function chuẩn bị tạo case bảo trì
function prepareCreateMaintenanceCase() {
    // Lấy request ID từ modal chỉnh sửa
    const requestId = document.getElementById('edit_request_id');
    const requestCode = document.getElementById('edit_request_code');
    
    if (!requestId || !requestId.value) {
        if (typeof showAlert === 'function') {
            showAlert('Vui lòng chọn một yêu cầu bảo trì trước khi tạo case', 'warning');
        } else {
            alert('Vui lòng chọn một yêu cầu bảo trì trước khi tạo case');
        }
        return false; // Ngăn modal hiển thị
    }
    
    // Set giá trị cho modal tạo case
    const maintenanceRequestIdField = document.getElementById('maintenance_request_id');
    const caseRequestCodeField = document.getElementById('case_request_code');
    
    if (maintenanceRequestIdField) {
        maintenanceRequestIdField.value = requestId.value;
    }
    
    if (caseRequestCodeField && requestCode) {
        caseRequestCodeField.value = requestCode.value;
    }
    
    // Lưu request_id vào sessionStorage để sử dụng sau này
    if (typeof sessionStorage !== 'undefined') {
        sessionStorage.setItem('current_maintenance_request_id', requestId.value);
    }
    
    return true; // Cho phép modal hiển thị
}

// Function chuẩn bị tạo task bảo trì
function prepareCreateMaintenanceTask() {
    // Lấy case ID từ modal edit case (khi đang chỉnh sửa case)
    let caseId = document.getElementById('edit_case_id')?.value;
    let requestId = document.getElementById('edit_maintenance_request_id')?.value;
    let caseCode = document.getElementById('edit_case_code')?.value;
    let requestCode = document.getElementById('edit_case_request_code')?.value;
    

    
    // Nếu không có từ modal edit, thử lấy từ modal tạo case mới
    if (!caseId) {
        caseId = document.getElementById('case_code')?.value;
        requestId = document.getElementById('maintenance_request_id')?.value;
        caseCode = document.getElementById('case_code')?.value;
        requestCode = document.getElementById('case_request_code')?.value;
        

    }
    
    // Kiểm tra xem có thông tin case không
    if (!caseId) {
        if (typeof showAlert === 'function') {
            showAlert('Vui lòng chọn một case trước khi tạo task', 'warning');
        } else {
            alert('Vui lòng chọn một case trước khi tạo task');
        }
        return false;
    }
    
    // Set giá trị cho modal tạo task
    const taskCaseIdField = document.getElementById('task_maintenance_case_id');
    const taskRequestIdField = document.getElementById('task_maintenance_request_id');
    const taskCaseCodeField = document.getElementById('task_case_code');
    const taskRequestCodeField = document.getElementById('task_request_code');
    
    if (taskCaseIdField) {
        taskCaseIdField.value = caseId;
    }
    
    if (taskRequestIdField) {
        taskRequestIdField.value = requestId;
    }
    
    if (taskCaseCodeField) {
        taskCaseCodeField.value = caseCode;
    }
    
    if (taskRequestCodeField) {
        taskRequestCodeField.value = requestCode;
    }
    
    // Load số task tiếp theo trước khi mở modal
    $.ajax({
        url: 'api/get_next_maintenance_task_number_simple.php',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                $('#task_number').val(response.task_code);
            }
        }
    });
    
    // Load danh sách staff IT Dept trước khi mở modal
    $.ajax({
        url: 'api/get_it_staffs.php',
        type: 'GET',
        success: function(response) {
            const select = document.getElementById('task_assignee_id');
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
            }
        }
    });
    
    // Mở modal tạo task
    try {
        $('#createMaintenanceTaskModal').modal('show');
    } catch (e) {
        // Nếu jQuery không hoạt động, thử Bootstrap
        const modalElement = document.getElementById('createMaintenanceTaskModal');
        if (modalElement) {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        } else {
            if (typeof showAlert === 'function') {
                showAlert('Lỗi: Không tìm thấy modal tạo task bảo trì', 'error');
            } else {
                alert('Lỗi: Không tìm thấy modal tạo task bảo trì');
            }
        }
    }
    
    return true; // Cho phép modal hiển thị
}

// Function tạo case bảo trì (giữ lại để tương thích)
function createMaintenanceCase() {
    // Lấy request ID từ modal chỉnh sửa
    const requestId = document.getElementById('edit_request_id');
    const requestCode = document.getElementById('edit_request_code');
    
    if (!requestId || !requestId.value) {
        if (typeof showAlert === 'function') {
            showAlert('Lỗi: Không tìm thấy thông tin yêu cầu bảo trì', 'error');
        } else {
            alert('Lỗi: Không tìm thấy thông tin yêu cầu bảo trì');
        }
        return;
    }
    
    // Set giá trị cho modal tạo case
    const maintenanceRequestIdField = document.getElementById('maintenance_request_id');
    const caseRequestCodeField = document.getElementById('case_request_code');
    
    if (maintenanceRequestIdField) {
        maintenanceRequestIdField.value = requestId.value;
    }
    
    if (caseRequestCodeField && requestCode) {
        caseRequestCodeField.value = requestCode.value;
    }
    
    // Hiển thị modal tạo case
    const modalElement = document.getElementById('createMaintenanceCaseModal');
    
    if (modalElement) {
        // Thử sử dụng jQuery trước
        try {
            $('#createMaintenanceCaseModal').modal('show');
        } catch (e) {
            // Nếu jQuery không hoạt động, thử Bootstrap
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    } else {
        if (typeof showAlert === 'function') {
            showAlert('Lỗi: Không tìm thấy modal tạo case bảo trì', 'error');
        } else {
            alert('Lỗi: Không tìm thấy modal tạo case bảo trì');
        }
    }
}

// Function load mã case tiếp theo
function loadNextCaseNumber() {
    fetch('api/get_next_maintenance_case_number.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const caseCodeField = document.getElementById('case_code');
                if (caseCodeField) {
                    caseCodeField.value = data.case_code;
                } else {
                    console.error('case_code field not found');
                }
            } else {
                console.error('API error:', data.message);
                if (typeof showAlert === 'function') {
                    showAlert('Lỗi: ' + (data.message || 'Không thể tạo mã case'), 'error');
                } else {
                    alert('Lỗi: ' + (data.message || 'Không thể tạo mã case'));
                }
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            if (typeof showAlert === 'function') {
                showAlert('Lỗi kết nối: ' + error.message, 'error');
            } else {
                alert('Lỗi kết nối: ' + error.message);
            }
        });
}

// Function load danh sách cases bảo trì
function loadMaintenanceCases() {
    const requestId = document.getElementById('edit_request_id').value;
    if (!requestId) return;
    
    fetch(`api/get_maintenance_cases.php?maintenance_request_id=${requestId}`)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('maintenance-cases-table');
            if (!tbody) return;
            
            tbody.innerHTML = '';
            
            if (!data.success || !Array.isArray(data.data) || data.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="12" class="text-center text-muted py-3">
                  <i class='fas fa-inbox fa-2x mb-2'></i><br>Chưa có case bảo trì nào
                </td></tr>`;
                return;
            }
            
            // Populate table with cases
            data.data.forEach((item, idx) => {
                tbody.innerHTML += `
                  <tr>
                    <td class='text-center'>${idx + 1}</td>
                    <td class='text-center'><strong class="text-primary">${item.case_code || ''}</strong></td>
                    <td class='text-center'>${item.case_description || ''}</td>
                    <td class='text-center'>${item.notes || ''}</td>
                    <td class='text-center'>${item.assigned_to_name || ''}</td>
                    <td class='text-center'>${formatDateForDisplay(item.start_date)}</td>
                    <td class='text-center'>${formatDateForDisplay(item.end_date)}</td>
                    <td class='text-center'>
                      <span class="badge bg-${(item.status === 'Hoàn thành' ? 'success' : (item.status === 'Đang xử lý' ? 'warning' : (item.status === 'Huỷ' ? 'danger' : 'secondary')))}">
                        ${item.status || 'Tiếp nhận'}
                      </span>
                    </td>
                    <td class='text-center'>${item.total_tasks || 0}</td>
                    <td class='text-center'>${item.completed_tasks || 0}</td>
                    <td class='text-center'>${item.work_type || ''}</td>
                    <td class='text-center'>
                      <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-warning" onclick="createMaintenanceTask(${item.id})" title="Tạo task">
                          <i class="fas fa-plus"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-info" onclick="editMaintenanceCase(${item.id})" title="Chỉnh sửa">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteMaintenanceCase(${item.id})" title="Xóa">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                `;
            });
        })
        .catch(error => {
            console.error('Error loading maintenance cases:', error);
        });
}

// Function load danh sách tasks bảo trì - Sử dụng function JS thay vì PHP
function loadMaintenanceTasks() {
    // Gọi function JS để load tasks
    if (typeof window.loadMaintenanceTasks === 'function') {
        window.loadMaintenanceTasks();
    }
}

// Function tạo task bảo trì
function createMaintenanceTask(caseId) {
    if (!caseId) {
        if (typeof showAlert === 'function') {
            showAlert('Lỗi: Không tìm thấy thông tin case', 'error');
        } else {
            alert('Lỗi: Không tìm thấy thông tin case');
        }
        return;
    }

    // Handler khi modal hiển thị
    const handler = function() {
        const modalElement = document.getElementById('createMaintenanceTaskModal'); // Thêm dòng này để đảm bảo biến tồn tại
        // Lấy thông tin case và request
        fetch('api/get_maintenance_case_details.php?id=' + caseId)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const caseData = data.data;
                    document.getElementById('task_case_code').value = caseData.case_code || '';
                    document.getElementById('task_request_code').value = caseData.request_code || '';
                    document.getElementById('task_maintenance_case_id').value = caseId;
                    document.getElementById('task_maintenance_request_id').value = caseData.maintenance_request_id || '';
                }
            })
            .catch(error => {
                console.error('Error loading case details:', error);
            });

        // Lấy số task tự động
        fetch('api/get_next_maintenance_task_number_simple.php')
            .then(response => response.json())
            .then(data => {
                document.getElementById('task_number').value = data.success ? data.task_code : '';
            })
            .catch((error) => {
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

        if (modalElement) {
            modalElement.removeEventListener('shown.bs.modal', handler);
        }
    };
    
    const modalElement = document.getElementById('createMaintenanceTaskModal');
    if (modalElement) {
        modalElement.addEventListener('shown.bs.modal', handler);

        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    }
}

// Function format date cho hiển thị
function formatDateForDisplay(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN');
}

// Function format datetime cho hiển thị
function formatDateTimeForDisplay(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('vi-VN');
}

// Function xóa case bảo trì
function deleteMaintenanceCase(caseId) {
    if (!confirm('Bạn có chắc chắn muốn xóa case bảo trì này?\n\nLưu ý: Tất cả các task liên quan cũng sẽ bị xóa!')) {
        return;
    }
    
    fetch('api/delete_maintenance_case.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: caseId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof showAlert === 'function') {
                showAlert('Xóa case bảo trì thành công!', 'success');
            } else {
                alert('Xóa case bảo trì thành công!');
            }
            // Reload danh sách cases
            loadMaintenanceCases();
        } else {
            if (typeof showAlert === 'function') {
                showAlert('Lỗi: ' + (data.message || 'Không thể xóa case bảo trì'), 'error');
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể xóa case bảo trì'));
            }
        }
    })
    .catch(error => {
        if (typeof showAlert === 'function') {
            showAlert('Lỗi kết nối: ' + error.message, 'error');
        } else {
            alert('Lỗi kết nối: ' + error.message);
        }
    });
}



// Function xóa task bảo trì
function deleteMaintenanceTask(taskId) {
    if (!confirm('Bạn có chắc chắn muốn xóa task bảo trì này?')) {
        return;
    }
    
    fetch(`api/delete_maintenance_task.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: taskId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof showAlert === 'function') {
                showAlert('Xóa task bảo trì thành công!', 'success');
            } else {
                alert('Xóa task bảo trì thành công!');
            }
            loadMaintenanceTasks(); // Reload danh sách
        } else {
            if (typeof showAlert === 'function') {
                showAlert(data.message || 'Có lỗi xảy ra khi xóa task bảo trì', 'error');
            } else {
                alert(data.message || 'Có lỗi xảy ra khi xóa task bảo trì');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showAlert === 'function') {
            showAlert('Có lỗi xảy ra khi xóa task bảo trì', 'error');
        } else {
            alert('Có lỗi xảy ra khi xóa task bảo trì');
        }
    });
}

// Function chỉnh sửa task bảo trì (placeholder)
function editMaintenanceTask(taskId) {
    if (typeof showAlert === 'function') {
        showAlert('Chức năng chỉnh sửa task bảo trì đang được phát triển', 'info');
    } else {
        alert('Chức năng chỉnh sửa task bảo trì đang được phát triển');
    }
}

// Function hiển thị alert - giống như deployment_requests.php
function showAlert(message, type = 'info') {
    // Sử dụng Bootstrap alert nếu có container
    const alertContainer = document.getElementById('alert-container');
    if (alertContainer) {
        let alertClass = 'alert-info';
        let icon = 'fas fa-info-circle';
        
        switch (type) {
            case 'success':
                alertClass = 'alert-success';
                icon = 'fas fa-check-circle';
                break;
            case 'error':
                alertClass = 'alert-danger';
                icon = 'fas fa-exclamation-circle';
                break;
            case 'warning':
                alertClass = 'alert-warning';
                icon = 'fas fa-exclamation-triangle';
                break;
            case 'info':
            default:
                alertClass = 'alert-info';
                icon = 'fas fa-info-circle';
                break;
        }
        
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="${icon} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        alertContainer.innerHTML = alertHtml;
        
        // Tự động ẩn alert sau 5 giây
        setTimeout(() => {
            const alert = alertContainer.querySelector('.alert');
            if (alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    } else {
        // Fallback về alert() nếu không có container
        alert(message);
    }
}

</script>

<!-- Load dữ liệu khách hàng cho JavaScript -->
<script>
    // Load dữ liệu khách hàng cho JavaScript
    partnerData = <?php echo json_encode($partners); ?>;
</script>

<!-- Include maintenance requests JavaScript -->
<script src="assets/js/maintenance_requests.js?v=<?php echo filemtime('assets/js/maintenance_requests.js'); ?>"></script>
<?php if (!empty($requests)): ?>
<script>
function initializeEditSelect2() {
    $('#edit_customer_id').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Chọn khách hàng --',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#editMaintenanceRequestModal'),
        language: {
            noResults: function() {
                return "Không tìm thấy khách hàng";
            },
            searching: function() {
                return "Đang tìm kiếm...";
            }
        }
    });
    $('#edit_sale_id').select2({
        theme: 'bootstrap-5',
        placeholder: '-- Chọn sale phụ trách --',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#editMaintenanceRequestModal'),
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
// Khởi tạo Select2 khi modal chỉnh sửa hiển thị (chỉ khi modal tồn tại)
$('#editMaintenanceRequestModal').on('shown.bs.modal', function() {
    initializeEditSelect2();
    setupEditCheckboxHandler();
    // Load danh sách cases bảo trì
    loadMaintenanceCases();
});
// Fix accessibility issue - sử dụng inert attribute
$('#editMaintenanceRequestModal').on('hidden.bs.modal', function() {
    // Sử dụng inert thay vì aria-hidden
    this.setAttribute('inert', '');
    this.removeAttribute('aria-hidden');
});
$('#editMaintenanceRequestModal').on('show.bs.modal', function() {
    // Xóa inert khi modal hiển thị
    this.removeAttribute('inert');
});
</script>
<?php endif; ?>
<?php endif; ?>
</body>
</html> 