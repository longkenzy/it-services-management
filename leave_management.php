<?php
/**
 * IT Services Management - Leave Management
 * Trang quản lý đơn nghỉ phép
 */

require_once 'includes/session.php';
require_once 'config/db.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    header('Location: auth/login.php');
    exit;
}

$current_user = getCurrentUser();
$page_title = "Quản lý Nghỉ phép";

// Kiểm tra quyền phê duyệt
$can_approve = in_array($current_user['role'], ['admin', 'hr']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - IT Services Management</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo filemtime('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="assets/css/table-improvements.css?v=<?php echo filemtime('assets/css/table-improvements.css'); ?>">
    <link rel="stylesheet" href="assets/css/alert.css?v=<?php echo filemtime('assets/css/alert.css'); ?>">
    <link rel="stylesheet" href="assets/css/leave_management.css?v=<?php echo filemtime('assets/css/leave_management.css'); ?>">
    
    <!-- No Border Radius Override -->
    <link rel="stylesheet" href="assets/css/no-border-radius.css?v=<?php echo filemtime('assets/css/no-border-radius.css'); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/logo.png">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="page-title">
                            <i class="fas fa-calendar-alt text-primary me-3"></i>
                            <?php echo $page_title; ?>
                        </h1>
                        <p class="text-muted mb-0">Quản lý và theo dõi các đơn nghỉ phép của nhân viên</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createLeaveRequestModal">
                            <i class="fas fa-plus me-2"></i>
                            Tạo đơn nghỉ phép
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Filters and Search -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="d-flex gap-2">
                        <select class="form-select" style="width: auto;" id="statusFilter">
                            <option value="">Tất cả trạng thái</option>
                            <option value="Chờ phê duyệt">Chờ phê duyệt</option>
                            <option value="Đã phê duyệt">Đã phê duyệt</option>
                            <option value="Từ chối">Từ chối</option>
                        </select>
                        <select class="form-select" style="width: auto;" id="typeFilter">
                            <option value="">Tất cả loại nghỉ</option>
                            <option value="Nghỉ phép năm">Nghỉ phép năm</option>
                            <option value="Nghỉ ốm">Nghỉ ốm</option>
                            <option value="Nghỉ việc riêng">Nghỉ việc riêng</option>
                            <option value="Nghỉ không lương">Nghỉ không lương</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Tìm kiếm đơn nghỉ phép..." id="searchInput">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Leave Requests Table -->
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 leave-requests-table">
                            <thead>
                                <tr>
                                    <th style="width: 8%;">Mã đơn</th>
                                    <th style="width: 20%;">Người yêu cầu</th>
                                    <th style="width: 15%;">Thời gian nghỉ</th>
                                    <th style="width: 18%;">Lý do nghỉ</th>
                                    <th style="width: 10%;">Đính kèm</th>
                                    <th style="width: 12%;">Ngày gửi</th>
                                    <th style="width: 10%;">Trạng thái</th>
                                    <th style="width: 7%;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="leaveRequestsTableBody">
                                <!-- Data will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Loading State -->
            <div id="loadingState" class="text-center py-4" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
                <p class="mt-2 text-muted">Đang tải danh sách đơn nghỉ phép...</p>
            </div>
            
            <!-- Empty State -->
            <div id="emptyState" class="text-center py-5" style="display: none;">
                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Chưa có đơn nghỉ phép nào</h5>
                <p class="text-muted">Bắt đầu tạo đơn nghỉ phép đầu tiên của bạn</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createLeaveRequestModal">
                    <i class="fas fa-plus me-2"></i>
                    Tạo đơn nghỉ phép
                </button>
            </div>
        </div>
    </div>
    
    <!-- Modal Tạo đơn nghỉ phép -->
    <div class="modal fade" id="createLeaveRequestModal" tabindex="-1" aria-labelledby="createLeaveRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createLeaveRequestModalLabel">
                        <i class="fas fa-plus text-primary me-2"></i>
                        Tạo đơn nghỉ phép
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="createLeaveRequestForm">
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Cột trái -->
                            <div class="col-md-6">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
                                </h6>
                                
                                <!-- Thông tin người yêu cầu (readonly) -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">
                                            <i class="fas fa-user me-2 text-primary"></i>Người yêu cầu
                                        </label>
                                    </div>
                                    <div class="col-md-8">
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['fullname']); ?>" readonly>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">
                                            <i class="fas fa-id-badge me-2 text-info"></i>Chức vụ
                                        </label>
                                    </div>
                                    <div class="col-md-8">
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['position'] ?? ''); ?>" readonly>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">
                                            <i class="fas fa-building me-2 text-success"></i>Phòng ban
                                        </label>
                                    </div>
                                    <div class="col-md-8">
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['department'] ?? ''); ?>" readonly>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">
                                            <i class="fas fa-map-marker-alt me-2 text-warning"></i>Văn phòng
                                        </label>
                                    </div>
                                    <div class="col-md-8">
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($current_user['office'] ?? ''); ?>" readonly>
                                    </div>
                                </div>
                                
                                <!-- Thông tin ngày tháng -->
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="start_date" class="form-label">
                                            <i class="fas fa-calendar-plus me-2 text-info"></i>Ngày bắt đầu <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="time" class="form-control" id="start_time" name="start_time" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="end_date" class="form-label">
                                            <i class="fas fa-calendar-minus me-2 text-warning"></i>Ngày kết thúc <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="time" class="form-control" id="end_time" name="end_time" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="return_date" class="form-label">
                                            <i class="fas fa-calendar-check me-2 text-success"></i>Ngày đi làm lại <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <input type="date" class="form-control" id="return_date" name="return_date" required>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="time" class="form-control" id="return_time" name="return_time" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="leave_days" class="form-label">
                                            <i class="fas fa-calendar-day me-2 text-primary"></i>Số ngày nghỉ <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                    <div class="col-md-8">
                                        <input type="number" class="form-control" id="leave_days" name="leave_days" min="0.5" max="30" step="0.5" required>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Cột phải -->
                            <div class="col-md-6">
                                <h6 class="text-success mb-3">
                                    <i class="fas fa-edit me-2"></i>Thông tin nghỉ phép
                                </h6>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="leave_type" class="form-label">
                                            <i class="fas fa-tag me-2 text-secondary"></i>Loại nghỉ phép <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                    <div class="col-md-8">
                                        <select class="form-select" id="leave_type" name="leave_type" required>
                                            <option value="">-- Chọn loại nghỉ phép --</option>
                                            <option value="Nghỉ phép năm">Nghỉ phép năm</option>
                                            <option value="Nghỉ ốm">Nghỉ ốm</option>
                                            <option value="Nghỉ việc riêng">Nghỉ việc riêng</option>
                                            <option value="Nghỉ không lương">Nghỉ không lương</option>
                                            <option value="Nghỉ thai sản">Nghỉ thai sản</option>
                                            <option value="Nghỉ khác">Nghỉ khác</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="reason" class="form-label">
                                            <i class="fas fa-comment me-2 text-info"></i>Lý do nghỉ phép <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                    <div class="col-md-8">
                                        <textarea class="form-control" id="reason" name="reason" rows="4" placeholder="Nhập lý do nghỉ phép chi tiết..." required></textarea>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="handover_to" class="form-label">
                                            <i class="fas fa-handshake me-2 text-warning"></i>Đã bàn giao việc cho <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                    <div class="col-md-8">
                                        <select class="form-select" id="handover_to" name="handover_to" required>
                                            <option value="">-- Chọn người được bàn giao --</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="attachment" class="form-label">
                                            <i class="fas fa-paperclip me-2 text-muted"></i>Đính kèm tài liệu (nếu có)
                                        </label>
                                    </div>
                                    <div class="col-md-8">
                                        <input type="file" class="form-control" id="attachment" name="attachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                        <small class="text-muted">Hỗ trợ: PDF, DOC, DOCX, JPG, PNG (tối đa 5MB)</small>
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
                            <i class="fas fa-paper-plane me-2"></i>Gửi đơn
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Xem chi tiết đơn nghỉ phép -->
    <div class="modal fade" id="viewLeaveRequestModal" tabindex="-1" aria-labelledby="viewLeaveRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewLeaveRequestModalLabel">
                        <i class="fas fa-eye text-info me-2"></i>
                        Chi tiết đơn nghỉ phép
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewLeaveRequestModalBody">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/alert.js"></script>
    <script>
        // Đảm bảo jQuery đã được load và $ alias có sẵn
        if (typeof jQuery !== 'undefined') {
            console.log('jQuery loaded successfully:', jQuery.fn.jquery);
            // Đảm bảo $ alias có sẵn
            if (typeof $ === 'undefined') {
                $ = jQuery;
            }
        } else {
            console.error('jQuery is not loaded!');
        }
    </script>
    
    <script>
    // Truyền thông tin quyền phê duyệt cho JavaScript
    window.canApprove = <?php echo json_encode($can_approve); ?>;
    window.currentUserRole = "<?php echo addslashes($current_user['role']); ?>";
    console.log('PHP canApprove:', <?php echo json_encode($can_approve); ?>);
    console.log('PHP currentUserRole:', "<?php echo addslashes($current_user['role']); ?>");
    </script>
    
    <script src="assets/js/leave_management.js"></script>
</body>
</html> 