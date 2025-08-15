<?php
// Trang quản lý Yêu cầu triển khai (Deployment Requests)
require_once 'includes/session.php';
requireLogin();
require_once 'config/db.php';

// Xử lý AJAX request để trả về modal content
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    define('AJAX_REQUEST', true);
    
    // Set headers cho AJAX response
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    $modal = $_GET['modal'] ?? '';
    $id = $_GET['id'] ?? '';
    
    // Debug: Log thông tin request
    error_log("AJAX Request - Modal: $modal, ID: $id");
    
    // Debug: Log đường dẫn file
    $modalFile = '';
    if ($modal === 'editDeploymentCase') {
        $modalFile = 'includes/modals/edit_deployment_case_modal.php';
    } elseif ($modal === 'editDeploymentTask') {
        $modalFile = 'includes/modals/edit_deployment_task_modal.php';
    } elseif ($modal === 'editMaintenanceCase') {
        $modalFile = 'includes/modals/edit_maintenance_case_modal.php';
    } elseif ($modal === 'editMaintenanceTask') {
        $modalFile = 'includes/modals/edit_maintenance_task_modal.php';
    } elseif ($modal === 'editInternalCase') {
        $modalFile = 'includes/modals/edit_internal_case_modal.php';
    }
    error_log("Modal type: $modal, Modal file path: $modalFile, exists: " . (file_exists($modalFile) ? 'YES' : 'NO'));
    
    // Chỉ trả về modal content, không phải toàn bộ trang
    if ($modal === 'editDeploymentCase') {
        // Trả về modal edit deployment case
        if (file_exists('includes/modals/edit_deployment_case_modal.php')) {
            error_log("Including edit_deployment_case_modal.php");
            ob_start();
            include 'includes/modals/edit_deployment_case_modal.php';
            $output = ob_get_clean();
            error_log("Output length: " . strlen($output));
            echo $output;
        } else {
            echo '<div class="alert alert-danger">File modal không tồn tại</div>';
        }
        exit;
    } elseif ($modal === 'editDeploymentTask') {
        // Trả về modal edit deployment task
        if (file_exists('includes/modals/edit_deployment_task_modal.php')) {
            error_log("Including edit_deployment_task_modal.php");
            ob_start();
            include 'includes/modals/edit_deployment_task_modal.php';
            $output = ob_get_clean();
            error_log("Output length: " . strlen($output));
            echo $output;
        } else {
            echo '<div class="alert alert-danger">File modal không tồn tại</div>';
        }
        exit;
    } elseif ($modal === 'editMaintenanceCase') {
        // Trả về modal edit maintenance case
        if (file_exists('includes/modals/edit_maintenance_case_modal.php')) {
            error_log("Including edit_maintenance_case_modal.php");
            ob_start();
            include 'includes/modals/edit_maintenance_case_modal.php';
            $output = ob_get_clean();
            error_log("Output length: " . strlen($output));
            echo $output;
        } else {
            echo '<div class="alert alert-danger">File modal không tồn tại</div>';
        }
        exit;
    } elseif ($modal === 'editMaintenanceTask') {
        // Trả về modal edit maintenance task
        if (file_exists('includes/modals/edit_maintenance_task_modal.php')) {
            error_log("Including edit_maintenance_task_modal.php");
            ob_start();
            include 'includes/modals/edit_maintenance_task_modal.php';
            $output = ob_get_clean();
            error_log("Output length: " . strlen($output));
            echo $output;
        } else {
            echo '<div class="alert alert-danger">File modal không tồn tại</div>';
        }
        exit;
    } elseif ($modal === 'editInternalCase') {
        // Trả về modal edit internal case
        if (file_exists('includes/modals/edit_internal_case_modal.php')) {
            error_log("Including edit_internal_case_modal.php");
            ob_start();
            include 'includes/modals/edit_internal_case_modal.php';
            $output = ob_get_clean();
            error_log("Output length: " . strlen($output));
            echo $output;
        } else {
            echo '<div class="alert alert-danger">File modal không tồn tại</div>';
        }
        exit;
    }
    
    // Nếu không tìm thấy modal, trả về lỗi
    http_response_code(404);
    echo '<div class="alert alert-danger">Modal không tìm thấy</div>';
    exit;
}

// Lấy role user hiện tại
$current_role = isset($_SESSION['role']) ? $_SESSION['role'] : (function_exists('getCurrentUserRole') ? getCurrentUserRole() : null);

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
    // error_log("Found " . count($requests) . " deployment requests");
    // Debug: Hiển thị thông tin nếu có debug parameter
    // if (isset($_GET['debug'])) {
    //     echo "<!-- Debug: Found " . count($requests) . " records -->\n";
    //     foreach ($requests as $req) {
    //         echo "<!-- Record: " . $req['request_code'] . " -->\n";
    //     }
    // }
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
        
        /* Consistent badge styling */
        .badge {
            border-radius: 0.375rem !important;
            font-size: 0.75rem !important;
            font-weight: 500 !important;
            padding: 0.35rem 0.65rem !important;
        }
        
        /* Consistent button styling */
        .btn {
            border-radius: 4px !important;
        }
        
        .btn-sm {
            border-radius: 4px !important;
        }
        
        .btn-outline-warning,
        .btn-outline-danger,
        .btn-outline-primary {
            border-radius: 4px !important;
        }
        
        .badge.bg-success {
            background-color: #28a745 !important;
        }
        
        .badge.bg-warning {
            background-color: #ffc107 !important;
            color: #212529 !important;
        }
        
        .badge.bg-danger {
            background-color: #dc3545 !important;
        }
        
        .badge.bg-secondary {
            background-color: #6c757d !important;
        }
        
        /* Căn trái nội dung cột khách hàng */
        .customer-info {
            text-align: left;
        }
    </style>
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo filemtime('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="assets/css/alert.css?v=<?php echo filemtime('assets/css/alert.css'); ?>">
    <link rel="stylesheet" href="assets/css/deployment_requests.css?v=<?php echo filemtime('assets/css/deployment_requests.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    

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
                    <button class="btn btn-success me-2" id="exportExcelBtn" title="Xuất Excel">
                        <i class="fas fa-file-excel me-2"></i>
                        Xuất Excel
                    </button>
                    <?php if ($current_role !== 'it' && $current_role !== 'user'): ?>
                    <button class="button" id="createRequestBtn" data-bs-toggle="modal" data-bs-target="#addDeploymentRequestModal">
                        <span class="button_lg">
                            <span class="button_sl"></span>
                            <span class="button_text">
                                <i class="fas fa-plus me-2"></i>
                                Tạo yêu cầu triển khai
                            </span>
                        </span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php if (!empty($requests)): ?>
        <!-- Table hiển thị danh sách yêu cầu triển khai -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>STT</th>
                                <th>Mã YC</th>
                                <th>Loại HĐ</th>
                                <th>Khách hàng</th>
                                <th>Phụ trách</th>
                                <th>Thời hạn triển khai</th>
                                <th>Ghi chú</th>
                                <th>Tổng số case</th>
                                <th>Tổng số task</th>
                                <th>Tiến độ (%)</th>
                                <th>Trạng thái triển khai</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody id="deployment-requests-table">
                                <?php foreach ($requests as $index => $request): ?>
                                <tr>
                                    <td class="text-center">
                                        <?php echo $index + 1; ?>
                                    </td>
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
                                        <span class="text-dark"><?php echo $request['total_cases'] ?? 0; ?></span>
                                    </td>
                                    <td>
                                        <span class="text-dark"><?php echo $request['total_tasks'] ?? 0; ?></span>
                                    </td>
                                    <td>
                                        <div class="progress" style="width: 80px; height: 20px;"><div class="progress-bar bg-warning" style="width: <?php echo $request['progress_percentage'] ?? 0; ?>%" title="<?php echo $request['progress_percentage'] ?? 0; ?>%"><small><?php echo $request['progress_percentage'] ?? 0; ?>%</small></div></div>
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
                                            <?php if ($current_role === 'admin'): ?>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteRequest(<?php echo $request['id']; ?>)" title="Xóa">
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
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Hiển thị thông báo khi chưa có yêu cầu triển khai nào -->
        <div class="card">
            <div class="card-body">
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">Chưa có yêu cầu triển khai nào</h5>
                    <p class="text-muted">Bấm nút "Tạo yêu cầu triển khai" để bắt đầu</p>
                    <?php if (isset($_GET['debug'])): ?>
                        <div class="mt-3">
                            <small class="text-muted">Debug: <?php echo count($requests); ?> records found</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
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
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Mã yêu cầu:</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="request_code" id="request_code" readonly value="YC<?php echo date('y').date('m'); ?>001">
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Số hợp đồng PO:</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="po_number" id="po_number" placeholder="Nhập số hợp đồng PO" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                  <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox" value="1" id="no_contract_po" name="no_contract_po" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                    <label class="form-check-label" for="no_contract_po">Không có HĐ/PO</label>
                  </div>
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Loại hợp đồng:</label>
                <div class="col-md-9">
                  <select class="form-select" name="contract_type" id="contract_type" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
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
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Loại yêu cầu chi tiết:</label>
                <div class="col-md-9">
                  <select class="form-select" name="request_detail_type" id="request_detail_type" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
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
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Email subject (KH):</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="email_subject_customer" id="email_subject_customer" placeholder="Nhập email subject cho khách hàng" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Email subject (NB):</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="email_subject_internal" id="email_subject_internal" placeholder="Nhập email subject cho nội bộ" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Bắt đầu dự kiến:</label>
                <div class="col-md-9">
                  <input type="date" class="form-control" name="expected_start" id="expected_start" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Kết thúc dự kiến:</label>
                <div class="col-md-9">
                  <input type="date" class="form-control" name="expected_end" id="expected_end" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                </div>
              </div>
            </div>
            
            <!-- Cột phải: Khách hàng & Xử lý -->
            <div class="col-md-6">
              <h6 class="text-primary mb-3"><i class="fas fa-users me-2"></i>KHÁCH HÀNG</h6>
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Khách hàng:</label>
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
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Người liên hệ:</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="contact_person" id="contact_person" placeholder="Nhập tên người liên hệ">
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Điện thoại:</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="contact_phone" id="contact_phone" placeholder="Nhập số điện thoại">
                </div>
              </div>
              
              <h6 class="text-primary mb-3 mt-4"><i class="fas fa-cogs me-2"></i>XỬ LÝ</h6>
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Sale phụ trách:</label>
                <div class="col-md-9">
                  <select class="form-select" name="sale_id" id="sale_id">
                    <option value="">-- Chọn sale phụ trách --</option>
                    <?php
                    $sales = $pdo->query("SELECT id, fullname FROM staffs WHERE (department != 'IT Dept.' OR department IS NULL) AND (resigned != 1 OR resigned IS NULL) ORDER BY fullname ASC")->fetchAll();
                    
                    if (empty($sales)) {
                      echo '<option value="">-- Không có nhân viên phù hợp --</option>';
                    } else {
                      foreach ($sales as $sale) {
                        echo '<option value="'.$sale['id'].'">'.htmlspecialchars($sale['fullname']).'</option>';
                      }
                    }
                    ?>
                  </select>
                </div>
              </div>
              
              <div class="mb-3 row align-items-start">
                <label class="col-md-3 form-label mb-0">Ghi chú người yêu cầu:</label>
                <div class="col-md-9">
                  <textarea class="form-control" name="requester_notes" id="requester_notes" rows="2" placeholder="Nhập ghi chú"></textarea>
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Quản lý triển khai:</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="deployment_manager" id="deployment_manager" value="Trần Nguyễn Anh Khoa" readonly>
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Trạng thái triển khai:</label>
                <div class="col-md-9">
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
<script src="assets/js/deployment_requests.js?v=<?php echo filemtime('assets/js/deployment_requests.js'); ?>"></script>

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
                // Reload ngay lập tức sau khi tạo thành công
                reloadDeploymentRequestsTable();
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
                const currentRole = '<?php echo $current_role; ?>';
                const currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
                
                // Kiểm tra quyền chỉnh sửa - sale phụ trách hoặc admin
                const canEdit = currentRole === 'admin' || (currentRole !== 'it' && currentRole !== 'user' && request.sale_id == currentUserId);
                
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
                    // Set readonly cho các trường nếu không có quyền chỉnh sửa
                    if (!canEdit) {
                        const readonlyFields = [
                            'edit_po_number', 'edit_no_contract_po', 'edit_contract_type', 'edit_request_detail_type',
                            'edit_email_subject_customer', 'edit_email_subject_internal', 'edit_expected_start', 'edit_expected_end',
                            'edit_customer_id', 'edit_contact_person', 'edit_contact_phone', 'edit_sale_id', 
                            'edit_requester_notes', 'edit_deployment_status'
                        ];
                        
                        readonlyFields.forEach(fieldId => {
                            const field = document.getElementById(fieldId);
                            if (field) {
                                field.setAttribute('readonly', true);
                                field.style.backgroundColor = '#f8f9fa';
                                field.style.cursor = 'not-allowed';
                            }
                        });
                        
                        // Disable select elements
                        const selectFields = ['edit_contract_type', 'edit_request_detail_type', 'edit_customer_id', 'edit_sale_id', 'edit_deployment_status'];
                        selectFields.forEach(fieldId => {
                            const field = document.getElementById(fieldId);
                            if (field) {
                                field.disabled = true;
                                field.style.backgroundColor = '#f8f9fa';
                                field.style.cursor = 'not-allowed';
                            }
                        });
                        
                        // Disable checkbox
                        const checkbox = document.getElementById('edit_no_contract_po');
                        if (checkbox) {
                            checkbox.disabled = true;
                        }
                    }
            
                    
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
        e.preventDefault();
        
        // Kiểm tra xem form có bị disable tạm thời không
        if (this.getAttribute('data-submit-disabled') === 'true') {
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
                    reloadDeploymentRequestsTable();
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

    // Fetch cases filtered by deployment_request_id
    fetch('api/get_deployment_cases.php?deployment_request_id=' + requestId)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('deployment-cases-table');
            if (!tbody) {
                return;
            }
            
            tbody.innerHTML = '';

            if (!data.success || !Array.isArray(data.data) || data.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="14" class="text-center text-muted py-3">
                  <i class='fas fa-inbox fa-2x mb-2'></i><br>Chưa có case triển khai nào
                </td></tr>`;
                return;
            }

            // Populate table with filtered cases
            data.data.forEach((item, idx) => {
                tbody.innerHTML += `
                  <tr>
                    <td class='text-center'>${idx + 1}</td>
                    <td class='text-center'>${item.case_code || ''}</td>
                    <td class='text-center'>${item.case_description || ''}</td>
                    <td class='text-center'>${item.notes || ''}</td>
                    <td class='text-center'>${item.assigned_to_name || ''}</td>
                    <td class='text-center'>${formatDateForDisplay(item.start_date)}</td>
                    <td class='text-center'>${formatDateForDisplay(item.end_date)}</td>
                    <td class='text-center'>
                      <span class="badge bg-${item.status === 'Hoàn thành' ? 'success' : (item.status === 'Đang xử lý' ? 'warning' : (item.status === 'Huỷ' ? 'danger' : 'secondary'))}">
                        ${item.status || 'Tiếp nhận'}
                      </span>
                    </td>
                    <td class='text-center'>${item.total_tasks || 0}</td>
                    <td class='text-center'>${item.completed_tasks || 0}</td>
                    <td class='text-center'>${item.work_type || ''}</td>
                    <td class='text-center'>
                      <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="editDeploymentCase(${item.id}); return false;" title="Chỉnh sửa">
                          <i class="fas fa-edit"></i>
                        </button>
                        <?php if ($current_role === 'admin'): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteDeploymentCase(${item.id}, ${requestId}); return false;" title="Xóa">
                          <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                `;
            });
        })
        .catch(error => {
            const tbody = document.getElementById('deployment-cases-table');
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="14" class="text-center text-danger py-3">
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
            e.preventDefault();
            
            // Kiểm tra xem có phải là submit thực sự không
            const submitButton = e.submitter;
            const form = e.target;
            
            // Kiểm tra xem form có bị disable tạm thời không
            if (form.getAttribute('data-submit-disabled') === 'true') {
                return;
            }
            
            if (!submitButton || submitButton.textContent.trim() !== 'Cập nhật case' || isSubmitting) {
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
            
            // Kiểm tra quyền trước khi submit
            const currentRole = '<?php echo $current_role; ?>';
            const currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
            const originalAssignedTo = document.getElementById('edit_case_id').getAttribute('data-original-assigned-to');
            
            // Kiểm tra quyền chỉnh sửa case - chỉ role 'it' và người phụ trách case hoặc admin
            const canEditCase = currentRole === 'admin' || (currentRole === 'it' && originalAssignedTo == currentUserId);
            
            if (!canEditCase) {
                if (typeof showAlert === 'function') {
                    showAlert('Bạn không có quyền chỉnh sửa case này!', 'error');
                } else {
                    alert('Bạn không có quyền chỉnh sửa case này!');
                }
                isSubmitting = false;
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
                    
                    // Hiển thị thông báo auto update nếu có
                    if (data.auto_updated_items && data.auto_updated_items.length > 0) {
                        let autoUpdateMessage = 'Hệ thống đã tự động cập nhật: ';
                        data.auto_updated_items.forEach(item => {
                            if (item.type === 'deployment_request') {
                                autoUpdateMessage += `Yêu cầu triển khai #${item.id} → Hoàn thành. `;
                            }
                        });
                        setTimeout(() => {
                            if (typeof showAlert === 'function') {
                                showAlert(autoUpdateMessage, 'info');
                            } else {
                                alert(autoUpdateMessage);
                            }
                        }, 1000);
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
    // Ngăn chặn event bubbling
    event.preventDefault();
    event.stopPropagation();
    
    // Lấy thông tin case
    fetch(`api/get_case_details.php?id=${caseId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const caseData = data.data;
                const currentRole = '<?php echo $current_role; ?>';
                const currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
                
                // Kiểm tra quyền chỉnh sửa case - chỉ role 'it' và người phụ trách case hoặc admin
                const canEditCase = currentRole === 'admin' || (currentRole === 'it' && caseData.assigned_to == currentUserId);
                
                // Lưu trữ giá trị assigned_to gốc để kiểm tra quyền khi submit
                document.getElementById('edit_case_id').setAttribute('data-original-assigned-to', caseData.assigned_to);
                
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
                    
                    // Set readonly cho các trường dựa trên quyền
                    if (!canEditCase) {
                        // Disable tất cả các trường nếu không có quyền
                        const readonlyFields = [
                            'edit_case_code', 'edit_request_type', 'edit_progress', 'edit_case_description', 
                            'edit_notes', 'edit_assigned_to', 'edit_work_type', 'edit_start_date', 'edit_end_date', 'edit_status'
                        ];
                        
                        readonlyFields.forEach(fieldId => {
                            const field = document.getElementById(fieldId);
                            if (field) {
                                field.setAttribute('readonly', true);
                                field.style.backgroundColor = '#f8f9fa';
                                field.style.cursor = 'not-allowed';
                            }
                        });
                        
                        // Disable select elements (bao gồm cả trạng thái)
                        const selectFields = ['edit_request_type', 'edit_progress', 'edit_assigned_to', 'edit_work_type', 'edit_status'];
                        selectFields.forEach(fieldId => {
                            const field = document.getElementById(fieldId);
                            if (field) {
                                field.disabled = true;
                                field.style.backgroundColor = '#f8f9fa';
                                field.style.cursor = 'not-allowed';
                            }
                        });
                    } else if (currentRole === 'admin') {
                        // Admin: có thể chỉnh sửa tất cả các trường
                        // Không disable trường nào
                    } else {
                        // User có quyền (role it + người phụ trách): chỉ cho phép sửa ngày kết thúc và trạng thái
                        const readonlyFields = [
                            'edit_case_code', 'edit_request_type', 'edit_progress', 'edit_case_description', 
                            'edit_notes', 'edit_assigned_to', 'edit_work_type', 'edit_start_date'
                        ];
                        
                        readonlyFields.forEach(fieldId => {
                            const field = document.getElementById(fieldId);
                            if (field) {
                                field.setAttribute('readonly', true);
                                field.style.backgroundColor = '#f8f9fa';
                                field.style.cursor = 'not-allowed';
                            }
                        });
                        
                        // Disable select elements (trừ trạng thái)
                        const selectFields = ['edit_request_type', 'edit_progress', 'edit_assigned_to', 'edit_work_type'];
                        selectFields.forEach(fieldId => {
                            const field = document.getElementById(fieldId);
                            if (field) {
                                field.disabled = true;
                                field.style.backgroundColor = '#f8f9fa';
                                field.style.cursor = 'not-allowed';
                            }
                        });
                    }
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
        return;
    }
    
    fetch('api/get_task_templates.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
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
                // Task templates loaded successfully
            }
        })
        .catch(error => {
            // Error handling
        });
}

// Function to load IT staffs
function loadITStaffs() {
    const select = document.getElementById('task_assignee_id');
    if (!select) {
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
                // Error handling
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
    if (!caseId) {
        return;
    }
    // Fetch tasks filtered by deployment_case_id
    fetch('api/get_deployment_tasks.php?deployment_case_id=' + caseId, {
        credentials: 'same-origin', // Include cookies/session
        headers: {
            'Accept': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('deployment-tasks-table');
            if (!tbody) {
                return;
            }
            
            tbody.innerHTML = '';
            
            if (!data.success || !Array.isArray(data.data) || data.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="10" class="text-center text-muted py-3">
                  <i class='fas fa-inbox fa-2x mb-2'></i><br>Chưa có task triển khai nào
                </td></tr>`;
                return;
            }
            
            // Populate table with filtered tasks
            data.data.forEach((item, idx) => {
                tbody.innerHTML += `
                  <tr>
                    <td class='text-center'>${idx + 1}</td>
                    <td class='text-center'>${item.task_number || ''}</td>
                    <td class='text-center'>${item.task_type || ''}</td>
                    <td class='text-center'>${item.template_name || '-'}</td>
                    <td class='text-center'>${item.task_description || ''}</td>
                    <td class='text-center'>${formatDateForDisplay(item.start_date)}</td>
                    <td class='text-center'>${formatDateForDisplay(item.end_date)}</td>
                    <td class='text-center'>${item.assignee_name || ''}</td>
                    <td class='text-center'>
                      <span class="badge bg-${(item.status === 'Hoàn thành' ? 'success' : (item.status === 'Đang xử lý' ? 'warning' : (item.status === 'Huỷ' ? 'danger' : 'secondary')))}">
                        ${item.status || 'Tiếp nhận'}
                      </span>
                    </td>
                    <td class='text-center'>
                      <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="editDeploymentTask(${item.id}); return false;" title="Chỉnh sửa">
                          <i class="fas fa-edit"></i>
                        </button>
                        <?php if ($current_role === 'admin'): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteDeploymentTask(${item.id}, ${caseId}); return false;" title="Xóa">
                          <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                `;
            });
        })
        .catch(error => {
            const tbody = document.getElementById('deployment-tasks-table');
            if (tbody) {
                tbody.innerHTML = `<tr><td colspan="10" class="text-center text-danger py-3">
                  <i class='fas fa-exclamation-triangle fa-2x mb-2'></i><br>Lỗi khi tải dữ liệu
                </td></tr>`;
            }
        });
}

// Function chỉnh sửa task triển khai
function editDeploymentTask(taskId) {
    // Ngăn chặn event bubbling
    event.preventDefault();
    event.stopPropagation();
    
    // Lấy thông tin task
    fetch('api/get_task_details.php?id=' + taskId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const taskData = data.data;
                const currentRole = '<?php echo $current_role; ?>';
                const currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
                
                // Kiểm tra quyền chỉnh sửa task - chỉ role 'it' và người thực hiện task hoặc admin
                const canEditTask = currentRole === 'admin' || (currentRole === 'it' && taskData.assignee_id == currentUserId);
                
                // Lưu trữ giá trị assignee_id gốc để kiểm tra quyền khi submit
                document.getElementById('edit_task_id').setAttribute('data-original-assignee-id', taskData.assignee_id);
                
                // Điền dữ liệu vào form edit task
                document.getElementById('edit_task_id').value = taskData.id;
                document.getElementById('edit_task_number').value = taskData.task_number || '';
                document.getElementById('edit_task_type').value = taskData.task_type || '';
                document.getElementById('edit_task_template').value = taskData.template_name || '';
                document.getElementById('edit_task_name').value = taskData.task_description || '';
                document.getElementById('edit_task_note').value = taskData.notes || taskData.task_note || '';
                document.getElementById('edit_task_assignee_id').value = taskData.assignee_id || '';
                
                // Lưu case ID và request ID để reload sau khi update
                document.getElementById('edit_task_case_id').value = taskData.deployment_case_id || '';
                document.getElementById('edit_task_request_id').value = taskData.deployment_request_id || '';
                
                // Load danh sách nhân viên IT Dept trước
                fetch('api/get_it_staffs.php')
                    .then(response => response.json())
                    .then(staffData => {
                        const select = document.getElementById('edit_task_assignee_id');
                        select.innerHTML = '<option value="">-- Chọn người thực hiện --</option>';
                        if (staffData.success && Array.isArray(staffData.data)) {
                            staffData.data.forEach(staff => {
                                const option = document.createElement('option');
                                option.value = staff.id;
                                option.textContent = staff.fullname;
                                select.appendChild(option);
                            });
                        }
                        
                        // Sau đó mới set giá trị cho assignee_id
                        document.getElementById('edit_task_assignee_id').value = taskData.assignee_id || '';
                        
                        // Format datetime cho input datetime-local
                        document.getElementById('edit_task_start_date').value = taskData.start_date ? formatDateTimeForInput(taskData.start_date) : '';
                        document.getElementById('edit_task_end_date').value = taskData.end_date ? formatDateTimeForInput(taskData.end_date) : '';
                        
                        document.getElementById('edit_task_status').value = taskData.status || '';
                        
                        // Hiển thị modal edit task
                        const editTaskModal = new bootstrap.Modal(document.getElementById('editDeploymentTaskModal'));
                        
                        // Set readonly cho các trường dựa trên quyền
                        if (!canEditTask) {
                            // Disable tất cả các trường nếu không có quyền
                            const readonlyFields = [
                                'edit_task_number', 'edit_task_type', 'edit_task_template', 'edit_task_name', 
                                'edit_task_note', 'edit_task_assignee_id', 'edit_task_start_date', 'edit_task_end_date', 'edit_task_status'
                            ];
                            
                            readonlyFields.forEach(fieldId => {
                                const field = document.getElementById(fieldId);
                                if (field) {
                                    field.setAttribute('readonly', true);
                                    field.style.backgroundColor = '#f8f9fa';
                                    field.style.cursor = 'not-allowed';
                                }
                            });
                            
                            // Disable select elements (bao gồm cả trạng thái)
                            const selectFields = ['edit_task_type', 'edit_task_template', 'edit_task_assignee_id', 'edit_task_status'];
                            selectFields.forEach(fieldId => {
                                const field = document.getElementById(fieldId);
                                if (field) {
                                    field.disabled = true;
                                    field.style.backgroundColor = '#f8f9fa';
                                    field.style.cursor = 'not-allowed';
                                }
                            });
                        } else if (currentRole === 'admin') {
                            // Admin: có thể chỉnh sửa tất cả các trường
                            // Không disable trường nào
                        } else {
                            // User có quyền (role it + người thực hiện): chỉ cho phép sửa ngày kết thúc và trạng thái
                            const readonlyFields = [
                                'edit_task_number', 'edit_task_type', 'edit_task_template', 'edit_task_name', 
                                'edit_task_note', 'edit_task_assignee_id', 'edit_task_start_date'
                            ];
                            
                            readonlyFields.forEach(fieldId => {
                                const field = document.getElementById(fieldId);
                                if (field) {
                                    field.setAttribute('readonly', true);
                                    field.style.backgroundColor = '#f8f9fa';
                                    field.style.cursor = 'not-allowed';
                                }
                            });
                            
                            // Disable select elements (trừ trạng thái)
                            const selectFields = ['edit_task_type', 'edit_task_template', 'edit_task_assignee_id'];
                            selectFields.forEach(fieldId => {
                                const field = document.getElementById(fieldId);
                                if (field) {
                                    field.disabled = true;
                                    field.style.backgroundColor = '#f8f9fa';
                                    field.style.cursor = 'not-allowed';
                                }
                            });
                        }
                        
                        editTaskModal.show();
                    });
                
            } else {
                if (typeof showAlert === 'function') {
                    showAlert(data.message || 'Không thể lấy thông tin task', 'error');
                } else {
                    alert(data.message || 'Không thể lấy thông tin task');
                }
            }
        })
        .catch(error => {
            if (typeof showAlert === 'function') {
                showAlert('Có lỗi xảy ra khi lấy thông tin task', 'error');
            } else {
                alert('Có lỗi xảy ra khi lấy thông tin task');
            }
        });
}

// Function xóa task triển khai
function deleteDeploymentTask(taskId, caseId) {
    // Ngăn chặn event bubbling
    event.preventDefault();
    event.stopPropagation();
    
    if (!confirm('Bạn có chắc chắn muốn xóa task triển khai này?')) {
        return;
    }
    
    fetch('api/delete_deployment_task.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id: taskId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (typeof showAlert === 'function') {
                showAlert('Xóa task triển khai thành công!', 'success');
            } else {
                alert('Xóa task triển khai thành công!');
            }
            // Reload danh sách task
            loadDeploymentTasks(caseId);
            reloadDeploymentRequestsTable();
        } else {
            if (typeof showAlert === 'function') {
                showAlert(data.message || 'Có lỗi xảy ra khi xóa task', 'error');
            } else {
                alert(data.message || 'Có lỗi xảy ra khi xóa task');
            }
        }
    })
            .catch(error => {
            if (typeof showAlert === 'function') {
                showAlert('Có lỗi xảy ra khi xóa task', 'error');
            } else {
                alert('Có lỗi xảy ra khi xóa task');
            }
        });
}

// Function xóa yêu cầu triển khai
function deleteRequest(requestId) {
    if (!confirm('Bạn có chắc chắn muốn xóa yêu cầu triển khai này?\n\nLưu ý: Tất cả các case triển khai liên quan cũng sẽ bị xóa!')) {
        return;
    }
    
    // Disable button để tránh click nhiều lần
    const deleteButton = event.target.closest('button');
    if (deleteButton) {
        deleteButton.disabled = true;
        deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }
    
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
            
            // Reload bảng ngay lập tức
            reloadDeploymentRequestsTable();
            
        } else {
            if (typeof showAlert === 'function') {
                showAlert('Lỗi: ' + (data.message || 'Không thể xóa yêu cầu triển khai'), 'error');
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể xóa yêu cầu triển khai'));
            }
            
            // Re-enable button nếu có lỗi
            if (deleteButton) {
                deleteButton.disabled = false;
                deleteButton.innerHTML = '<i class="fas fa-trash"></i>';
            }
        }
    })
    .catch(error => {
        if (typeof showAlert === 'function') {
            showAlert('Lỗi kết nối: ' + error.message, 'error');
        } else {
            alert('Lỗi kết nối: ' + error.message);
        }
        
        // Re-enable button nếu có lỗi
        if (deleteButton) {
            deleteButton.disabled = false;
            deleteButton.innerHTML = '<i class="fas fa-trash"></i>';
        }
    });
}

// Hàm reload bảng danh sách yêu cầu triển khai
function reloadDeploymentRequestsTable() {
    console.log('Reloading deployment requests table...');
    
    // Helper function để format date
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
    
    fetch('api/get_deployment_requests.php')
        .then(response => response.json())
        .then(data => {
            console.log('API response:', data);
            
            if (!data.success || !Array.isArray(data.data)) {
                console.error('Invalid API response:', data);
                return;
            }
            
            // Tìm container chính chứa bảng hoặc thông báo
            const mainContainer = document.querySelector('.container-fluid');
            if (!mainContainer) {
                console.error('Main container not found');
                return;
            }
            
            // Tìm tất cả cards trong toàn bộ document (không chỉ trong mainContainer)
            const allCards = document.querySelectorAll('.card');
            const cards = Array.from(allCards).filter(card => {
                // Chỉ lấy cards trong main container hoặc có liên quan đến deployment
                return mainContainer.contains(card) || 
                       card.querySelector('table') || 
                       card.textContent.includes('triển khai') ||
                       card.textContent.includes('deployment');
            });
            console.log('Found', cards.length, 'cards in main container');
            console.log('Total cards in document:', allCards.length);
            
            // Tìm card chứa bảng deployment requests hoặc thông báo
            let cardContainer = null;
            
            // Cách 1: Tìm card có bảng với id deployment-requests-table
            const tableWithId = mainContainer.querySelector('#deployment-requests-table');
            if (tableWithId) {
                cardContainer = tableWithId.closest('.card');
                console.log('Found table with ID, card container:', cardContainer);
            }
            
            // Cách 2: Tìm card có chứa bảng deployment requests
            if (!cardContainer) {
                for (let card of cards) {
                    const table = card.querySelector('table');
                    if (table && (table.querySelector('th') && 
                        (table.querySelector('th').textContent.includes('Mã YC') || 
                         table.querySelector('th').textContent.includes('Loại HĐ')))) {
                        cardContainer = card;
                        console.log('Found card with deployment table:', card);
                        break;
                    }
                }
            }
            
            // Cách 3: Tìm card có chứa thông báo "Chưa có yêu cầu triển khai nào"
            if (!cardContainer) {
                for (let card of cards) {
                    if (card.textContent.includes('Chưa có yêu cầu triển khai nào')) {
                        cardContainer = card;
                        console.log('Found card with empty message:', card);
                        break;
                    }
                }
            }
            
            // Cách 4: Tìm card đầu tiên sau page-header (thường là card chứa nội dung chính)
            if (!cardContainer && cards.length > 0) {
                const pageHeader = mainContainer.querySelector('.page-header');
                if (pageHeader) {
                    // Tìm card đầu tiên sau page-header
                    let nextElement = pageHeader.nextElementSibling;
                    while (nextElement && !nextElement.classList.contains('card')) {
                        nextElement = nextElement.nextElementSibling;
                    }
                    if (nextElement && nextElement.classList.contains('card')) {
                        cardContainer = nextElement;
                        console.log('Found card after page-header:', cardContainer);
                    } else {
                        // Tìm card đầu tiên trong danh sách cards đã lọc
                        cardContainer = cards[0];
                        console.log('Using first filtered card as fallback:', cardContainer);
                    }
                } else {
                    // Tìm card đầu tiên trong danh sách cards đã lọc
                    cardContainer = cards[0];
                    console.log('Using first filtered card as fallback:', cardContainer);
                }
            }
            
            // Cách 5: Nếu vẫn không tìm thấy, tìm bất kỳ card nào có table
            if (!cardContainer) {
                for (let card of allCards) {
                    if (card.querySelector('table')) {
                        cardContainer = card;
                        console.log('Found any card with table:', cardContainer);
                        break;
                    }
                }
            }
            
            if (!cardContainer) {
                console.error('Card container not found, creating new card');
                // Tạo card mới nếu không tìm thấy và thêm vào đúng vị trí
                cardContainer = document.createElement('div');
                cardContainer.className = 'card';
                cardContainer.style.marginTop = '20px'; // Thêm margin để tránh nhảy lên header
                
                // Tìm vị trí đúng để thêm card mới (sau page-header)
                const pageHeader = mainContainer.querySelector('.page-header');
                if (pageHeader) {
                    // Tìm vị trí sau page-header, trước các element khác
                    let insertPosition = pageHeader.nextElementSibling;
                    while (insertPosition && !insertPosition.classList.contains('card') && 
                           !insertPosition.classList.contains('row') && 
                           !insertPosition.classList.contains('col')) {
                        insertPosition = insertPosition.nextElementSibling;
                    }
                    
                    if (insertPosition) {
                        mainContainer.insertBefore(cardContainer, insertPosition);
                    } else {
                        // Thêm vào sau page-header
                        mainContainer.insertBefore(cardContainer, pageHeader.nextElementSibling);
                    }
                } else {
                    // Fallback: thêm vào cuối container thay vì đầu
                    mainContainer.appendChild(cardContainer);
                }
            }
            
            console.log('Found card container:', cardContainer);
            if (cardContainer) {
                console.log('Card container position:', cardContainer.offsetTop);
                console.log('Card container classes:', cardContainer.className);
                console.log('Card container parent:', cardContainer.parentElement);
            } else {
                console.log('Card container position: N/A');
            }
            const currentRole = '<?php echo $current_role; ?>';
            const currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
            
            if (data.data.length === 0) {
                console.log('No data, showing empty message');
                // Hiển thị thông báo khi không có dữ liệu
                cardContainer.innerHTML = `
                <div class="card-body">
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                        <h5 class="text-muted">Chưa có yêu cầu triển khai nào</h5>
                        <p class="text-muted">Bấm nút "Tạo yêu cầu triển khai" để bắt đầu</p>
                    </div>
                </div>
                `;
                
                // Đảm bảo card có margin-top
                if (!cardContainer.style.marginTop) {
                    cardContainer.style.marginTop = '20px';
                }
            } else {
                console.log('Has data, showing table with', data.data.length, 'records');
                // Hiển thị bảng khi có dữ liệu
                cardContainer.innerHTML = `
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>STT</th>
                                    <th>Mã YC</th>
                                    <th>Loại HĐ</th>
                                    <th>Khách hàng</th>
                                    <th>Phụ trách</th>
                                    <th>Thời hạn triển khai</th>
                                    <th>Ghi chú</th>
                                    <th>Tổng số case</th>
                                    <th>Tổng số task</th>
                                    <th>Tiến độ (%)</th>
                                    <th>Trạng thái triển khai</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="deployment-requests-table">
                                ${data.data.map((request, index) => {
                                    const deleteButton = currentRole === 'admin' ? `<button class="btn btn-sm btn-outline-danger" onclick="deleteRequest(${request.id})" title="Xóa"><i class="fas fa-trash"></i></button>` : '';
                                    return `
                                    <tr>
                                        <td class="text-center">${index + 1}</td>
                                        <td><strong class="text-primary">${request.request_code || ''}</strong></td>
                                        <td><div class="contract-info"><div class="fw-bold">${request.contract_type || 'N/A'}</div><small class="text-muted">${request.request_detail_type || 'N/A'}</small></div></td>
                                        <td><div class="customer-info"><div class="fw-bold">${request.customer_name || 'N/A'}</div><small class="text-muted"><i class='fas fa-user me-1'></i>${request.contact_person || 'N/A'}</small><br><small class="text-muted"><i class='fas fa-phone me-1'></i>${request.contact_phone || 'N/A'}</small></div></td>
                                        <td><span class="text-dark">${request.sale_name || 'N/A'}</span></td>
                                        <td>${request.expected_start ? `<div class='text-wrap' style='white-space: pre-line;'><strong>Từ</strong><br>${formatDateForDisplay(request.expected_start)}<br><strong>Đến</strong><br>${request.expected_end ? formatDateForDisplay(request.expected_end) : '(Chưa xác định)'}</div>` : '<span class="text-muted">Chưa có</span>'}</td>
                                        <td>${request.requester_notes ? `<div class='text-wrap' style='max-width: 200px; white-space: pre-wrap; word-wrap: break-word;'>${request.requester_notes}</div>` : '<span class="text-muted">-</span>'}</td>
                                        <td><span class="text-dark">${request.total_cases || 0}</span></td>
                                        <td><span class="text-dark">${request.total_tasks || 0}</span></td>
                                        <td><div class="progress" style="width: 80px; height: 20px;"><div class="progress-bar bg-warning" style="width: ${request.progress_percentage || 0}%" title="${request.progress_percentage || 0}%"><small>${request.progress_percentage || 0}%</small></div></div></td>
                                        <td><span class="badge bg-${(request.deployment_status === 'Hoàn thành' ? 'success' : (request.deployment_status === 'Đang xử lý' ? 'warning' : (request.deployment_status === 'Huỷ' ? 'danger' : 'secondary')))}">${request.deployment_status || ''}</span></td>
                                        <td><div class="btn-group" role="group"><button class="btn btn-sm btn-outline-warning" onclick="editRequest(${request.id})" title="Chỉnh sửa"><i class="fas fa-edit"></i></button>${deleteButton}</div></td>
                                    </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
                `;
                
                // Đảm bảo tbody được tạo đúng cách
                const tbody = document.getElementById('deployment-requests-table');
                if (!tbody) {
                    const table = cardContainer.querySelector('table');
                    if (table) {
                        const newTbody = document.createElement('tbody');
                        newTbody.id = 'deployment-requests-table';
                        table.appendChild(newTbody);
                    }
                }
                
                // Đảm bảo card có margin-top
                if (!cardContainer.style.marginTop) {
                    cardContainer.style.marginTop = '20px';
                }
            }
            console.log('Table reloaded successfully');
            
            // Đảm bảo card không bị "nhảy" lên header
            if (cardContainer) {
                const cardTop = cardContainer.offsetTop;
                console.log('Card container position:', cardTop);
                
                if (cardTop < 150) {
                    console.warn('Card container position too high, adjusting...');
                    // Thêm margin-top để đẩy xuống
                    const currentMargin = parseInt(cardContainer.style.marginTop) || 0;
                    cardContainer.style.marginTop = (currentMargin + 30) + 'px';
                    
                    // Đảm bảo card không bị che bởi header
                    cardContainer.style.zIndex = '1';
                }
                
                // Đảm bảo card có khoảng cách với header
                if (!cardContainer.style.marginTop) {
                    cardContainer.style.marginTop = '20px';
                }
            }
        })
        .catch(error => {
            console.error('Error reloading deployment requests table:', error);
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
                request_id: formData.get('request_id') || document.getElementById('task_request_id')?.value || '',
                task_type: formData.get('task_type'),
                template_name: formData.get('task_template') || null,
                task_description: formData.get('task_name'),
                start_date: formData.get('start_date'),
                end_date: formData.get('end_date'),
                assignee_id: formData.get('assignee_id') || null,
                status: formData.get('status')
            };
            
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
                    
                    // Lưu caseId trước khi đóng modal
                    const caseId = data.deployment_case_id || document.getElementById('task_deployment_case_id').value;
                    const requestId = data.request_id || document.getElementById('task_request_id').value;
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('createDeploymentTaskModal'));
                    modal.hide();
                    
                    // Reload sau khi modal đã đóng
                    setTimeout(() => {
                        // Reload tasks table
                        if (caseId) {
                            loadDeploymentTasks(caseId);
                        }
                        
                        // Reload deployment cases table để cập nhật cột tổng số task
                        if (requestId) {
                            loadDeploymentCases(requestId);
                        }
                        
                        // Reload deployment requests table để cập nhật cột tổng số task
                        reloadDeploymentRequestsTable();
                    }, 500);
                    
                    // Reset form
                    e.target.reset();
                } else {
                    showAlert(result.message, 'error');
                }
            })
            .catch(error => {
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
    
    // Event listener cho form chỉnh sửa task
    const editTaskForm = document.getElementById('editDeploymentTaskForm');
    if (editTaskForm) {
        editTaskForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Kiểm tra quyền trước khi submit
            const currentRole = '<?php echo $current_role; ?>';
            const currentUserId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
            const originalAssigneeId = document.getElementById('edit_task_id').getAttribute('data-original-assignee-id');
            
            // Kiểm tra quyền chỉnh sửa task - chỉ role 'it' và người thực hiện task hoặc admin
            const canEditTask = currentRole === 'admin' || (currentRole === 'it' && originalAssigneeId == currentUserId);
            
            if (!canEditTask) {
                if (typeof showAlert === 'function') {
                    showAlert('Bạn không có quyền chỉnh sửa task này!', 'error');
                } else {
                    alert('Bạn không có quyền chỉnh sửa task này!');
                }
                return;
            }
            
            const formData = new FormData(e.target);
            const data = {
                id: formData.get('id'),
                task_type: formData.get('task_type'),
                template_name: formData.get('task_template') || null,
                task_name: formData.get('task_name'),
                task_note: formData.get('task_note'),
                start_date: formData.get('start_date'),
                end_date: formData.get('end_date'),
                assignee_id: formData.get('assignee_id') || null,
                status: formData.get('status')
            };
            
            console.log('Debug - Update task data:', data);
            
            fetch('api/update_deployment_task.php', {
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
                    
                    // Hiển thị thông báo auto update nếu có
                    if (result.auto_updated_items && result.auto_updated_items.length > 0) {
                        let autoUpdateMessage = 'Hệ thống đã tự động cập nhật: ';
                        result.auto_updated_items.forEach(item => {
                            if (item.type === 'case') {
                                autoUpdateMessage += `Case #${item.id} → Hoàn thành. `;
                            } else if (item.type === 'deployment_request') {
                                autoUpdateMessage += `Yêu cầu triển khai #${item.id} → Hoàn thành. `;
                            }
                        });
                        setTimeout(() => {
                            showAlert(autoUpdateMessage, 'info');
                        }, 1000);
                    }
                    
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editDeploymentTaskModal'));
                    modal.hide();
                    
                    // Reload tasks table
                    const caseId = document.getElementById('edit_task_case_id').value;
                    if (caseId) {
                        loadDeploymentTasks(caseId);
                    }
                    
                    // Reload deployment cases table để cập nhật cột tổng số task
                    const requestId = document.getElementById('edit_task_request_id').value;
                    if (requestId) {
                        loadDeploymentCases(requestId);
                    }
                    
                    // Reload deployment requests table để cập nhật cột tổng số task
                    reloadDeploymentRequestsTable();
                    
                    // Reset form
                    e.target.reset();
                } else {
                    showAlert(result.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Lỗi khi cập nhật task triển khai', 'error');
            });
        });
    }
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
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Mã yêu cầu:</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="request_code" id="edit_request_code" readonly>
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Số hợp đồng PO:</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="po_number" id="edit_po_number" placeholder="Nhập số hợp đồng PO" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                  <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox" value="1" id="edit_no_contract_po" name="no_contract_po" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                    <label class="form-check-label" for="edit_no_contract_po">Không có HĐ/PO</label>
                  </div>
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Loại hợp đồng:</label>
                <div class="col-md-9">
                  <select class="form-select" name="contract_type" id="edit_contract_type" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
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
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Loại yêu cầu chi tiết:</label>
                <div class="col-md-9">
                  <select class="form-select" name="request_detail_type" id="edit_request_detail_type" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
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
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Email subject (KH):</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="email_subject_customer" id="edit_email_subject_customer" placeholder="Nhập tiêu đề email" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Email subject (NB):</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="email_subject_internal" id="edit_email_subject_internal" placeholder="Nhập tiêu đề email" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                </div>
              </div>
            </div>
            
            <!-- Cột phải: Thông tin triển khai -->
            <div class="col-md-6">
              <h6 class="text-primary mb-3"><i class="fas fa-calendar-alt me-2"></i>THÔNG TIN TRIỂN KHAI</h6>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Ngày bắt đầu dự kiến:</label>
                <div class="col-md-9">
                  <input type="date" class="form-control" name="expected_start" id="edit_expected_start" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Ngày kết thúc dự kiến:</label>
                <div class="col-md-9">
                  <input type="date" class="form-control" name="expected_end" id="edit_expected_end" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Khách hàng: <span class="text-danger">*</span></label>
                <div class="col-md-9">
                  <select class="form-select" name="customer_id" id="edit_customer_id" required <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
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
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Người liên hệ:</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="contact_person" id="edit_contact_person" placeholder="Nhập tên người liên hệ" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Số điện thoại:</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="contact_phone" id="edit_contact_phone" placeholder="Nhập số điện thoại" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Sale phụ trách: <span class="text-danger">*</span></label>
                <div class="col-md-9">
                  <select class="form-select" name="sale_id" id="edit_sale_id" required <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                    <option value="">-- Chọn sale phụ trách --</option>
                    <?php
                    $sales = $pdo->query("SELECT id, fullname FROM staffs WHERE (department != 'IT Dept.' OR department IS NULL) AND (resigned != 1 OR resigned IS NULL) ORDER BY fullname ASC")->fetchAll();
                    
                    if (empty($sales)) {
                      echo '<option value="">-- Không có nhân viên phù hợp --</option>';
                    } else {
                      foreach ($sales as $sale) {
                        echo '<option value="'.$sale['id'].'">'.htmlspecialchars($sale['fullname']).'</option>';
                      }
                    }
                    ?>
                  </select>
                </div>
              </div>
              
              <div class="mb-3 row align-items-start">
                <label class="col-md-3 form-label mb-0">Ghi chú yêu cầu:</label>
                <div class="col-md-9">
                  <textarea class="form-control" name="requester_notes" id="edit_requester_notes" rows="3" placeholder="Nhập ghi chú yêu cầu" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>></textarea>
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Quản lý triển khai:</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="deployment_manager" id="edit_deployment_manager" value="Trần Nguyễn Anh Khoa" readonly>
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Trạng thái triển khai: <span class="text-danger">*</span></label>
                <div class="col-md-9">
                  <select class="form-select" name="deployment_status" id="edit_deployment_status" required <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
                    <option value="">-- Chọn trạng thái --</option>
                    <option value="Tiếp nhận">Tiếp nhận</option>
                    <option value="Đang xử lý">Đang xử lý</option>
                    <option value="Hoàn thành">Hoàn thành</option>
                    <option value="Huỷ">Huỷ</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Phần thứ 3: Quản lý Case triển khai -->
          <div class="border-top pt-4 mt-4 bg-light">
            <div class="row">
              <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="text-success mb-0"><i class="fas fa-tasks me-2"></i>QUẢN LÝ CASE TRIỂN KHAI</h6>
                  <?php if ($current_role === 'it'): ?>
                  <button type="button" class="btn btn-success btn-sm" onclick="createDeploymentCase()">
                    <i class="fas fa-plus me-1"></i>Tạo case triển khai
                  </button>
                  <?php endif; ?>
                </div>
                
                <!-- Bảng danh sách case triển khai -->
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
                    <tbody id="deployment-cases-table">
                      <tr>
                        <td colspan="12" class="text-center text-muted py-3">
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
          <?php if ($current_role === 'admin' || ($current_role !== 'it' && $current_role !== 'user')): ?>
          <button type="submit" class="btn btn-primary">Cập nhật yêu cầu</button>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal tạo task triển khai -->
<div class="modal fade" id="createDeploymentTaskModal" tabindex="-1" aria-labelledby="createDeploymentTaskModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content deployment-request-modal">
      <div class="modal-header">
        <h5 class="modal-title" id="createDeploymentTaskModalLabel">
          <i class="fas fa-plus-circle text-primary"></i> Tạo Task Triển Khai
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="createDeploymentTaskForm">
          <!-- Hidden fields for IDs -->
          <input type="hidden" name="deployment_case_id" id="task_deployment_case_id">
          <input type="hidden" name="request_id" id="task_request_id">
          <div class="row g-4">
            <!-- Cột trái -->
            <div class="col-md-6">
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Số task:</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="task_number" id="task_number" readonly>
                </div>
              </div>
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Số case:</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="case_code" id="task_case_code" readonly>
                </div>
              </div>
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Mã yêu cầu:</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="request_code" id="task_request_code" readonly>
                </div>
              </div>
              <div class="mb-3 row align-items-center">
                <label for="task_type" class="col-md-3 form-label mb-0">Loại Task <span class="text-danger">*</span></label>
                <div class="col-md-9">
                  <select class="form-select" name="task_type" id="task_type" required>
                    <option value="">-- Chọn loại task --</option>
                    <option value="onsite">Onsite</option>
                    <option value="offsite">Offsite</option>
                    <option value="remote">Remote</option>
                  </select>
                </div>
              </div>
              <div class="mb-3 row align-items-center">
                <label for="task_template" class="col-md-3 form-label mb-0">Task mẫu</label>
                <div class="col-md-9">
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
              </div>
              <div class="mb-3 row align-items-center">
                <label for="task_name" class="col-md-3 form-label mb-0">Task <span class="text-danger">*</span></label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="task_name" id="task_name" required placeholder="Nhập tên task cụ thể">
                </div>
              </div>
              <div class="mb-3 row align-items-start">
                <label for="task_note" class="col-md-3 form-label mb-0">Ghi chú</label>
                <div class="col-md-9">
                  <textarea class="form-control" name="task_note" id="task_note" rows="2" placeholder="Nhập ghi chú"></textarea>
                </div>
              </div>
            </div>
            <!-- Cột phải -->
            <div class="col-md-6">
              <div class="mb-3 row align-items-center">
                <label for="task_assignee_id" class="col-md-3 form-label mb-0">Người thực hiện</label>
                <div class="col-md-9">
                  <select class="form-select" name="assignee_id" id="task_assignee_id">
                    <option value="">-- Chọn người thực hiện --</option>
                  </select>
                </div>
              </div>
              <div class="mb-3 row align-items-center">
                <label for="task_start_date" class="col-md-3 form-label mb-0">Thời gian bắt đầu:</label>
                <div class="col-md-9">
                  <input type="datetime-local" class="form-control" name="start_date" id="task_start_date">
                </div>
              </div>
              <div class="mb-3 row align-items-center">
                <label for="task_end_date" class="col-md-3 form-label mb-0">Thời gian kết thúc:</label>
                <div class="col-md-9">
                  <input type="datetime-local" class="form-control" name="end_date" id="task_end_date">
                </div>
              </div>
              <div class="mb-3 row align-items-center">
                <label for="task_status" class="col-md-3 form-label mb-0">Trạng thái</label>
                <div class="col-md-9">
                  <select class="form-select" name="status" id="task_status">
                    <option value="Tiếp nhận">Tiếp nhận</option>
                    <option value="Đang xử lý">Đang xử lý</option>
                    <option value="Hoàn thành">Hoàn thành</option>
                    <option value="Huỷ">Huỷ</option>
                  </select>
                </div>
              </div>
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Người nhập:</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="created_by_name" id="task_created_by_name" value="<?php echo htmlspecialchars($_SESSION['fullname'] ?? ''); ?>" readonly>
                </div>
              </div>
            </div>
          </div>
          <input type="hidden" name="deployment_case_id" id="task_deployment_case_id_duplicate">
          <input type="hidden" name="request_id" id="task_request_id_duplicate">
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
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Số case:</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="case_code" id="case_code" readonly>
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Loại yêu cầu:</label>
                <div class="col-md-9">
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
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Tiến trình:</label>
                <div class="col-md-9">
                  <select class="form-select" name="progress" id="progress">
                    <option value="">-- Chọn tiến trình --</option>
                    <option value="CS - Chốt SOW">CS - Chốt SOW</option>
                    <option value="SH - Soạn hàng">SH - Soạn hàng</option>
                    <option value="GH - Giao hàng">GH - Giao hàng</option>
                    <option value="TK - Triển khai">TK - Triển khai</option>
                    <option value="NT - Nghiệm thu">NT - Nghiệm thu</option>
                  </select>
                </div>
              </div>
              
              <div class="mb-3 row align-items-start">
                <label class="col-md-3 form-label mb-0">Mô tả case:</label>
                <div class="col-md-9">
                  <textarea class="form-control" name="case_description" id="case_description" rows="4" placeholder="Nhập mô tả chi tiết về case"></textarea>
                </div>
              </div>
              
              <div class="mb-3 row align-items-start">
                <label class="col-md-3 form-label mb-0">Ghi chú:</label>
                <div class="col-md-9">
                  <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="Nhập ghi chú bổ sung"></textarea>
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Người nhập:</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="created_by_name" id="created_by_name" value="<?php echo htmlspecialchars($fullname); ?>" readonly>
                </div>
              </div>
            </div>
            
            <!-- Cột phải: Thông tin triển khai -->
            <div class="col-md-6">
              <h6 class="text-primary mb-3"><i class="fas fa-cogs me-2"></i>THÔNG TIN TRIỂN KHAI</h6>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Người phụ trách:</label>
                <div class="col-md-9">
                  <select class="form-select" name="assigned_to" id="assigned_to">
                    <option value="">-- Chọn người phụ trách --</option>
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
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Hình thức:</label>
                <div class="col-md-9">
                  <select class="form-select" name="work_type" id="work_type">
                    <option value="">-- Chọn hình thức --</option>
                    <option value="Onsite">Onsite</option>
                    <option value="Offsite">Offsite</option>
                    <option value="Remote">Remote</option>
                  </select>
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Ngày giờ bắt đầu:</label>
                <div class="col-md-9">
                  <input type="datetime-local" class="form-control" name="start_date" id="start_date">
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Ngày giờ kết thúc:</label>
                <div class="col-md-9">
                  <input type="datetime-local" class="form-control" name="end_date" id="end_date">
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Trạng thái:</label>
                <div class="col-md-9">
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
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Số case:</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="case_code" id="edit_case_code" readonly>
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Loại yêu cầu:</label>
                <div class="col-md-9">
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
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Tiến trình:</label>
                <div class="col-md-9">
                  <select class="form-select" name="progress" id="edit_progress">
                    <option value="">-- Chọn tiến trình --</option>
                    <option value="CS - Chốt SOW">CS - Chốt SOW</option>
                    <option value="SH - Soạn hàng">SH - Soạn hàng</option>
                    <option value="GH - Giao hàng">GH - Giao hàng</option>
                    <option value="TK - Triển khai">TK - Triển khai</option>
                    <option value="NT - Nghiệm thu">NT - Nghiệm thu</option>
                  </select>
                </div>
              </div>
              
              <div class="mb-3 row align-items-start">
                <label class="col-md-3 form-label mb-0">Mô tả case:</label>
                <div class="col-md-9">
                  <textarea class="form-control" name="case_description" id="edit_case_description" rows="4" placeholder="Nhập mô tả chi tiết về case"></textarea>
                </div>
              </div>
              
              <div class="mb-3 row align-items-start">
                <label class="col-md-3 form-label mb-0">Ghi chú:</label>
                <div class="col-md-9">
                  <textarea class="form-control" name="notes" id="edit_notes" rows="3" placeholder="Nhập ghi chú bổ sung"></textarea>
                </div>
              </div>
            </div>
            
            <!-- Cột phải: Thông tin triển khai -->
            <div class="col-md-6">
              <h6 class="text-primary mb-3"><i class="fas fa-cogs me-2"></i>THÔNG TIN TRIỂN KHAI</h6>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Người phụ trách:</label>
                <div class="col-md-9">
                  <select class="form-select" name="assigned_to" id="edit_assigned_to">
                    <option value="">-- Chọn người phụ trách --</option>
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
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Hình thức:</label>
                <div class="col-md-9">
                  <select class="form-select" name="work_type" id="edit_work_type">
                    <option value="">-- Chọn hình thức --</option>
                    <option value="Onsite">Onsite</option>
                    <option value="Offsite">Offsite</option>
                    <option value="Remote">Remote</option>
                  </select>
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Ngày giờ bắt đầu:</label>
                <div class="col-md-9">
                  <input type="datetime-local" class="form-control" name="start_date" id="edit_start_date">
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Ngày giờ kết thúc:</label>
                <div class="col-md-9">
                  <input type="datetime-local" class="form-control" name="end_date" id="edit_end_date">
                </div>
              </div>
              
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Trạng thái:</label>
                <div class="col-md-9">
                  <select class="form-select" name="status" id="edit_status">
                    <option value="Tiếp nhận">Tiếp nhận</option>
                    <option value="Đang xử lý">Đang xử lý</option>
                    <option value="Hoàn thành">Hoàn thành</option>
                    <option value="Huỷ">Huỷ</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Phần thứ 3: Quản lý Task triển khai -->
          <div class="border-top pt-4 mt-4 bg-light">
            <div class="row">
              <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="text-info mb-0"><i class="fas fa-tasks me-2"></i>QUẢN LÝ TASK TRIỂN KHAI</h6>
                  <?php if ($current_role === 'it' || $current_role === 'admin'): ?>
                  <button type="button" class="btn btn-info btn-sm" onclick="createDeploymentTask()">
                    <i class="fas fa-plus me-1"></i>Tạo task triển khai
                  </button>
                  <?php endif; ?>
                </div>
                
                <!-- Bảng danh sách task triển khai -->
                <div class="table-responsive">
                  <table class="table table-sm table-hover table-bordered">
                    <thead class="table-light">
                      <tr>
                        <th class="text-center">STT</th>
                        <th class="text-center">Số Task</th>
                        <th class="text-center">Loại Task</th>
                        <th class="text-center">Task mẫu</th>
                        <th class="text-center">Task</th>
                        <th class="text-center">Thời gian bắt đầu</th>
                        <th class="text-center">Thời gian kết thúc</th>
                        <th class="text-center">Người thực hiện</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-center">Thao tác</th>
                      </tr>
                    </thead>
                    <tbody id="deployment-tasks-table">
                      <tr>
                        <td colspan="10" class="text-center text-muted py-3">
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
    <!-- Modal chỉnh sửa task triển khai -->
    <div class="modal fade" id="editDeploymentTaskModal" tabindex="-1" aria-labelledby="editDeploymentTaskModalLabel" aria-hidden="true">
      <div class="modal-dialog">
    <div class="modal-content deployment-request-modal">
      <div class="modal-header">
        <h5 class="modal-title" id="editDeploymentTaskModalLabel">
          <i class="fas fa-edit text-warning"></i> Chỉnh sửa Task Triển Khai
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editDeploymentTaskForm">
          <div class="row g-4">
            <!-- Cột trái -->
            <div class="col-md-6">
              <div class="mb-3 row align-items-center">
                <label class="col-md-3 form-label mb-0">Số task:</label>
                <div class="col-md-9">
                  <input type="text" class="form-control" name="task_number" id="edit_task_number" readonly>
                </div>
              </div>
              <div class="mb-3 row align-items-center">
                <label for="edit_task_type" class="col-md-3 form-label mb-0">Loại Task <span class="text-danger">*</span></label>
                <div class="col-md-9">
                  <select class="form-select" name="task_type" id="edit_task_type" required <?php echo ($current_role === 'user') ? 'readonly' : ''; ?>>
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
                  <select class="form-select" name="task_template" id="edit_task_template" <?php echo ($current_role === 'user') ? 'readonly' : ''; ?>>
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
                  <input type="text" class="form-control" name="task_name" id="edit_task_name" required placeholder="Nhập tên task cụ thể" <?php echo ($current_role === 'user') ? 'readonly' : ''; ?>>
                </div>
              </div>
              <div class="mb-3 row align-items-start">
                <label for="edit_task_note" class="col-md-3 form-label mb-0">Ghi chú</label>
                <div class="col-md-9">
                  <textarea class="form-control" name="task_note" id="edit_task_note" rows="2" placeholder="Nhập ghi chú" <?php echo ($current_role === 'user') ? 'readonly' : ''; ?>></textarea>
                </div>
              </div>
            </div>
            <!-- Cột phải -->
            <div class="col-md-6">
              <div class="mb-3 row align-items-center">
                <label for="edit_task_assignee_id" class="col-md-3 form-label mb-0">Người thực hiện</label>
                <div class="col-md-9">
                  <select class="form-select" name="assignee_id" id="edit_task_assignee_id" <?php echo ($current_role === 'user') ? 'readonly' : ''; ?>>
                    <option value="">-- Chọn người thực hiện --</option>
                  </select>
                </div>
              </div>
              <div class="mb-3 row align-items-center">
                <label for="edit_task_start_date" class="col-md-3 form-label mb-0">Thời gian bắt đầu:</label>
                <div class="col-md-9">
                  <input type="datetime-local" class="form-control" name="start_date" id="edit_task_start_date" <?php echo ($current_role === 'user') ? 'readonly' : ''; ?>>
                </div>
              </div>
              <div class="mb-3 row align-items-center">
                <label for="edit_task_end_date" class="col-md-3 form-label mb-0">Thời gian kết thúc:</label>
                <div class="col-md-9">
                  <input type="datetime-local" class="form-control" name="end_date" id="edit_task_end_date">
                </div>
              </div>
              <div class="mb-3 row align-items-center">
                <label for="edit_task_status" class="col-md-3 form-label mb-0">Trạng thái</label>
                <div class="col-md-9">
                  <select class="form-select" name="status" id="edit_task_status">
                    <option value="Tiếp nhận">Tiếp nhận</option>
                    <option value="Đang xử lý">Đang xử lý</option>
                    <option value="Hoàn thành">Hoàn thành</option>
                    <option value="Huỷ">Huỷ</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <input type="hidden" name="id" id="edit_task_id">
          <input type="hidden" id="edit_task_case_id">
          <input type="hidden" id="edit_task_request_id">
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times"></i> Hủy
        </button>
        <button type="submit" form="editDeploymentTaskForm" class="btn btn-warning">
          <i class="fas fa-save"></i> Cập nhật Task
        </button>
      </div>
    </div>
  </div>
</div>

</body>
</html>

<script>
document.addEventListener('DOMContentLoaded', function() {
    reloadDeploymentRequestsTable();
    
    // Disable form fields for user role
    const currentRole = '<?php echo $current_role; ?>';
    if (currentRole === 'user') {
        // Disable all form fields in create modal
        const createModalFields = [
            'po_number', 'no_contract_po', 'contract_type', 'request_detail_type',
            'email_subject_customer', 'email_subject_internal', 'expected_start', 'expected_end',
            'customer_id', 'sale_id', 'requester_notes', 'deployment_status'
        ];
        
        createModalFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.disabled = true;
                field.setAttribute('readonly', true);
            }
        });
        
        // Disable all form fields in edit modal
        const editModalFields = [
            'edit_po_number', 'edit_no_contract_po', 'edit_contract_type', 'edit_request_detail_type',
            'edit_email_subject_customer', 'edit_email_subject_internal', 'edit_expected_start', 'edit_expected_end',
            'edit_customer_id', 'edit_contact_person', 'edit_contact_phone', 'edit_sale_id', 
            'edit_requester_notes', 'edit_deployment_status'
        ];
        
        editModalFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.disabled = true;
                field.setAttribute('readonly', true);
            }
        });
    }
});

// Disable fields when modals are shown
document.addEventListener('DOMContentLoaded', function() {
    const currentRole = '<?php echo $current_role; ?>';
    if (currentRole === 'user') {
        // Disable fields when create modal is shown
        const createModal = document.getElementById('addDeploymentRequestModal');
        if (createModal) {
            createModal.addEventListener('shown.bs.modal', function() {
                const fields = this.querySelectorAll('input, select, textarea');
                fields.forEach(field => {
                    if (field.id !== 'request_code') { // Keep request_code readonly but not disabled
                        field.disabled = true;
                        field.setAttribute('readonly', true);
                    }
                });
            });
        }
        
        // Disable fields when edit modal is shown
        const editModal = document.getElementById('editDeploymentRequestModal');
        if (editModal) {
            editModal.addEventListener('shown.bs.modal', function() {
                const fields = this.querySelectorAll('input, select, textarea');
                fields.forEach(field => {
                    if (field.id !== 'edit_request_code') { // Keep edit_request_code readonly but not disabled
                        field.disabled = true;
                        field.setAttribute('readonly', true);
                    }
                });
            });
        }
    }
});

// Disable fields when edit task modal is shown
document.addEventListener('DOMContentLoaded', function() {
    const currentRole = '<?php echo $current_role; ?>';
    const editTaskModal = document.getElementById('editDeploymentTaskModal');
    if (editTaskModal) {
        editTaskModal.addEventListener('shown.bs.modal', function() {
            const fields = this.querySelectorAll('input, select, textarea');
            fields.forEach(field => {
                // Keep end_date and status enabled for user role
                if (currentRole === 'user' && field.id !== 'edit_task_end_date' && field.id !== 'edit_task_status' && field.id !== 'edit_task_number') {
                    // Use readonly instead of disabled to allow form submission
                    field.setAttribute('readonly', true);
                    field.style.backgroundColor = '#f8f9fa';
                    field.style.cursor = 'not-allowed';
                }
            });
        });
    }
});

// Reset form khi mở modal tạo case triển khai
$('#createDeploymentCaseModal').on('show.bs.modal', function () {
    // Reset toàn bộ form
    $('#createDeploymentCaseForm')[0].reset();
    // Reset select2 khách hàng nếu có
    if ($('#customer_id').data('select2')) {
        $('#customer_id').val('').trigger('change');
    }
    // Reset select2 sale nếu có
    if ($('#sale_id').data('select2')) {
        $('#sale_id').val('').trigger('change');
    }
    // Clear các trường custom nếu cần
    $('#contact_person').val('');
    $('#contact_phone').val('');
});

// Auto-open modal khi có parameter từ workspace
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const openEditModal = urlParams.get('open_edit_modal');
    const caseId = urlParams.get('case_id');
    const taskId = urlParams.get('task_id');
    
    if (openEditModal === '1') {
        if (caseId) {
            // Tìm và mở modal edit case
            setTimeout(() => {
                const editButtons = document.querySelectorAll('[onclick*="editDeploymentCase"]');
                for (let button of editButtons) {
                    const onclick = button.getAttribute('onclick');
                    if (onclick && onclick.includes(caseId)) {
                        button.click();
                        break;
                    }
                }
            }, 1000);
        } else if (taskId) {
            // Tìm và mở modal edit task
            setTimeout(() => {
                const editButtons = document.querySelectorAll('[onclick*="editDeploymentTask"]');
                for (let button of editButtons) {
                    const onclick = button.getAttribute('onclick');
                    if (onclick && onclick.includes(taskId)) {
                        button.click();
                        break;
                    }
                }
            }, 1000);
        }
    }
});


</script>
<script src="assets/js/dashboard.js?v=<?php echo filemtime('assets/js/dashboard.js'); ?>"></script>
</body>
</html>