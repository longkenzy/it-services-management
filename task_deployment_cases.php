<?php
// task_deployment_cases.php
require_once 'includes/session.php';
requireLogin();
require_once 'config/db.php';

// Lấy ID case từ query string
$case_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$case = null;
$error = null;

if ($case_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM deployment_cases WHERE id = ?");
        $stmt->execute([$case_id]);
        $case = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$case) {
            $error = 'Không tìm thấy case triển khai.';
        }
    } catch (PDOException $e) {
        $error = 'Lỗi truy vấn dữ liệu.';
    }
} else {
    $error = 'Thiếu ID case.';
}

// Lấy danh sách task triển khai cho case này
$tasks = [];
if ($case && $case_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT t.*, s.fullname as assignee_name FROM deployment_case_tasks t LEFT JOIN staffs s ON t.assignee_id = s.id WHERE t.deployment_case_id = ? ORDER BY t.id ASC");
        $stmt->execute([$case_id]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $tasks = [];
    }
}

function h($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <title>Chi tiết Case Triển Khai</title>
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
        .case-detail-card { width: 100%; max-width: none; margin: 40px 0; box-shadow: 0 2px 16px rgba(0,0,0,0.08); border-radius: 16px; }
        .case-detail-header { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: #fff; border-radius: 16px 16px 0 0; padding: 2rem 2.5rem; }
        .case-detail-body { padding: 2rem 2.5rem; background: #fff; border-radius: 0 0 16px 16px; }
        .case-label { font-weight: 600; color: #0056b3; min-width: 140px; display: inline-block; }
        .case-value { color: #222; }
        .case-row { margin-bottom: 1.25rem; }
        @media (max-width: 600px) {
            .case-detail-card, .case-detail-header, .case-detail-body { padding: 1rem !important; }
        }
        /* CSS cho bảng task triển khai */
        .deployment-tasks-table th, 
        .deployment-tasks-table td { 
            text-align: center; 
            vertical-align: middle; 
            padding: 0.75rem;
            white-space: nowrap;
        }
        
        /* Điều chỉnh width cho từng cột */
        .deployment-tasks-table th:nth-child(1),
        .deployment-tasks-table td:nth-child(1) {
            width: 100px;
        }
        .deployment-tasks-table th:nth-child(2),
        .deployment-tasks-table td:nth-child(2) {
            width: 120px;
        }
        .deployment-tasks-table th:nth-child(3),
        .deployment-tasks-table td:nth-child(3) {
            width: 150px;
        }
        .deployment-tasks-table th:nth-child(4),
        .deployment-tasks-table td:nth-child(4) {
            width: 120px;
        }
        .deployment-tasks-table th:nth-child(5),
        .deployment-tasks-table td:nth-child(5) {
            width: 120px;
        }
        .deployment-tasks-table th:nth-child(6),
        .deployment-tasks-table td:nth-child(6) {
            width: 120px;
        }
        .deployment-tasks-table th:nth-child(7),
        .deployment-tasks-table td:nth-child(7) {
            width: 150px;
        }
        .deployment-tasks-table th:nth-child(8),
        .deployment-tasks-table td:nth-child(8) {
            width: 120px;
        }
        /* Xử lý text overflow */
        .deployment-tasks-table td {
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 0;
        }
        .deployment-tasks-table td:nth-child(3),
        .deployment-tasks-table td:nth-child(7) {
            white-space: normal;
            word-wrap: break-word;
        }
        .deployment-tasks-table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
        }
        .deployment-tasks-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .deployment-tasks-table .case-status {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .deployment-tasks-table .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .deployment-tasks-table .status-in_progress {
            background-color: #cce5ff;
            color: #004085;
        }
        .deployment-tasks-table .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        .deployment-tasks-table .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        @media (max-width: 768px) {
            .deployment-tasks-table {
                font-size: 0.875rem;
            }
            .deployment-tasks-table th, 
            .deployment-tasks-table td { 
                padding: 0.5rem 0.25rem;
                width: auto !important;
                min-width: 80px;
            }
            .deployment-tasks-table .case-status {
                padding: 0.125rem 0.25rem;
                font-size: 0.75rem;
            }
        }
        @media (max-width: 576px) {
            .deployment-tasks-table {
                font-size: 0.75rem;
            }
            .deployment-tasks-table th, 
            .deployment-tasks-table td { 
                padding: 0.25rem 0.125rem;
            }
        }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>
    <main class="main-content">
        <div class="container-fluid px-4 py-4">
            <!-- Card chi tiết case (giữ nguyên) -->
            <div class="case-detail-card bg-white">
                <?php if (
                    isset($error) && $error): ?>
                    <div class="alert alert-danger m-4"><?php echo h($error); ?></div>
                <?php elseif ($case): ?>
                    <div class="case-detail-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="display-5 text-primary"><i class="fas fa-rocket"></i></div>
                            <div>
                                <h2 class="mb-1">Case Triển Khai: <span class="fw-bold text-primary"><?php echo h($case['case_number']); ?></span></h2>
                                <div class="fs-5 mt-1 text-white-50"><?php echo h($case['project_name'] ?? ''); ?></div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-3 mt-md-0">
                            <span class="case-status status-<?php echo h($case['status']); ?> text-uppercase px-3 py-2 rounded fw-bold">
                                <i class="fas fa-info-circle me-1"></i>
                                <?php
                                $statusText = [
                                    'pending' => 'Tiếp nhận',
                                    'in_progress' => 'Đang xử lý',
                                    'completed' => 'Hoàn thành',
                                    'cancelled' => 'Huỷ'
                                ];
                                echo $statusText[$case['status']] ?? h($case['status']);
                                ?>
                            </span>
                            <span class="progress-badge progress-<?php echo h($case['progress']); ?> px-3 py-2 rounded fw-bold">
                                <i class="fas fa-tasks me-1"></i>
                                <?php
                                $progressLabels = [
                                    'CS' => 'CS - Chốt SOW',
                                    'SH' => 'SH - Soạn hàng',
                                    'GH' => 'GH - Giao hàng',
                                    'TK' => 'TK - Triển khai',
                                    'NT' => 'NT - Nghiệm thu'
                                ];
                                echo $progressLabels[$case['progress']] ?? h($case['progress']);
                                ?>
                            </span>
                            <span class="priority-badge priority-<?php echo h($case['job_type'] ?? $case['priority'] ?? ''); ?> px-3 py-2 rounded fw-bold">
                                <i class="fas fa-briefcase me-1"></i>
                                <?php echo h($case['job_type'] ?? $case['priority']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="case-detail-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="case-row mb-3">
                                    <span class="case-label"><i class="fas fa-hashtag me-2"></i>Mã Case:</span>
                                    <span class="case-value ms-1"><?php echo h($case['case_number']); ?></span>
                                </div>
                                <div class="case-row mb-3">
                                    <span class="case-label"><i class="fas fa-project-diagram me-2"></i>Tên dự án:</span>
                                    <span class="case-value ms-1"><?php echo h($case['project_name']); ?></span>
                                </div>
                                <div class="case-row mb-3">
                                    <span class="case-label"><i class="fas fa-user-tie me-2"></i>Khách hàng:</span>
                                    <span class="case-value ms-1"><?php echo h($case['customer_name']); ?></span>
                                </div>
                                <div class="case-row mb-3">
                                    <span class="case-label"><i class="fas fa-user-cog me-2"></i>Người phụ trách:</span>
                                    <span class="case-value ms-1"><?php echo h($case['handler_name']); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="case-row mb-3">
                                    <span class="case-label"><i class="fas fa-calendar-alt me-2"></i>Ngày bắt đầu:</span>
                                    <span class="case-value ms-1"><?php echo h($case['start_date']); ?></span>
                                </div>
                                <div class="case-row mb-3">
                                    <span class="case-label"><i class="fas fa-calendar-check me-2"></i>Ngày kết thúc:</span>
                                    <span class="case-value ms-1"><?php echo h($case['end_date']); ?></span>
                                </div>
                                <div class="case-row mb-3">
                                    <span class="case-label"><i class="fas fa-sticky-note me-2"></i>Ghi chú:</span>
                                    <span class="case-value ms-1"><?php echo nl2br(h($case['notes'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Card bảng danh sách task triển khai -->
            <div class="card mt-4">
                <div class="card-header bg-light d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="fas fa-tasks me-2 text-primary"></i>Danh sách Task Triển Khai</h5>
                    <a href="#" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> Tạo Task Triển Khai
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 deployment-tasks-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Số task</th>
                                    <th>Loại task</th>
                                    <th>Task mẫu</th>
                                    <th>Loại task</th>
                                    <th>Ngày bắt đầu</th>
                                    <th>Ngày kết thúc</th>
                                    <th>Người thực hiện</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tasks)): ?>
                                    <tr><td colspan="8" class="text-muted py-4">Chưa có task triển khai nào cho case này.</td></tr>
                                <?php else: foreach ($tasks as $task): ?>
                                    <tr>
                                        <td><?php echo h($task['task_number']); ?></td>
                                        <td><?php echo h($task['task_type']); ?></td>
                                        <td><?php echo $task['is_template'] ? h($task['template_name']) : '-'; ?></td>
                                        <td><?php echo h($task['task_type']); ?></td>
                                        <td><?php echo $task['start_date'] ? date('d/m/Y', strtotime($task['start_date'])) : '-'; ?></td>
                                        <td><?php echo $task['end_date'] ? date('d/m/Y', strtotime($task['end_date'])) : '-'; ?></td>
                                        <td><?php echo h($task['assignee_name'] ?: 'Chưa phân công'); ?></td>
                                        <td>
                                            <?php
                                            $statusText = [
                                                'pending' => 'Tiếp nhận',
                                                'in_progress' => 'Đang xử lý',
                                                'completed' => 'Hoàn thành',
                                                'cancelled' => 'Huỷ'
                                            ];
                                            $statusClass = 'status-' . h($task['status']);
                                            ?>
                                            <span class="case-status <?php echo $statusClass; ?>">
                                                <?php echo $statusText[$task['status']] ?? h($task['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- ===== JAVASCRIPT LIBS ===== -->
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom Alert System -->
    <script src="assets/js/alert.js"></script>
    
    <!-- ===== MAIN JAVASCRIPT ===== -->
    <script>
    $(document).ready(function() {
        // ===== USER PROFILE ACTIONS =====
        
        // Xử lý click "Thông tin cá nhân"
        $(document).on('click', '[data-action="profile"]', function(e) {
            e.preventDefault();
            window.location.href = 'profile.php';
        });
        
        // Xử lý click "Cài đặt"
        $(document).on('click', '[data-action="settings"]', function(e) {
            e.preventDefault();
            showInfo('Tính năng cài đặt đang được phát triển.');
        });
        
        // Xử lý click "Thông báo"
        $(document).on('click', '[data-action="notifications"]', function(e) {
            e.preventDefault();
            showInfo('Tính năng thông báo đang được phát triển.');
        });
        
        // Xử lý click "Đổi mật khẩu"
        $(document).on('click', '[data-action="change-password"]', function(e) {
            e.preventDefault();
            $('#changePasswordModal').modal('show');
        });
        
        // Xử lý click "Đăng xuất"
        $(document).on('click', '[data-action="logout"]', function(e) {
            e.preventDefault();
            if (confirm('Bạn có chắc chắn muốn đăng xuất?')) {
                window.location.href = 'auth/logout.php';
            }
        });
        
        // ===== CHANGE PASSWORD MODAL =====
        
        // Xử lý submit form đổi mật khẩu
        $('#changePasswordForm').on('submit', function(e) {
            e.preventDefault();
            
            const oldPassword = $('#old_password').val().trim();
            const newPassword = $('#new_password').val().trim();
            const confirmPassword = $('#confirm_password').val().trim();
            
            // Validation
            if (!oldPassword || !newPassword || !confirmPassword) {
                showError('Vui lòng điền đầy đủ thông tin');
                return;
            }
            
            if (newPassword.length < 6) {
                showError('Mật khẩu mới phải có ít nhất 6 ký tự');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                showError('Mật khẩu mới và xác nhận mật khẩu không khớp');
                return;
            }
            
            if (oldPassword === newPassword) {
                showError('Mật khẩu mới phải khác mật khẩu cũ');
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
                        showSuccess(response.message);
                        // Đóng modal sau 2 giây
                        setTimeout(function() {
                            $('#changePasswordModal').modal('hide');
                        }, 2000);
                    } else {
                        showError(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Change password error:', error);
                    showError('Có lỗi xảy ra. Vui lòng thử lại sau.');
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
        
        // Reset modal khi đóng
        $('#changePasswordModal').on('hidden.bs.modal', function() {
            $('#changePasswordForm')[0].reset();
            $('#changePasswordForm button[type="submit"]').prop('disabled', false).text('Đổi mật khẩu');
        });
        
        // ===== SEARCH FUNCTIONALITY =====
        
        // Global search
        $('#globalSearchInput').on('input', function() {
            const query = $(this).val().trim();
            if (query.length > 2) {
                // Implement search functionality here
                console.log('Searching for:', query);
            }
        });
        
        // ===== NAVIGATION HIGHLIGHTING =====
        
        // Highlight current page in navigation
        const currentPage = window.location.pathname.split('/').pop();
        $('.nav-link').removeClass('active');
        $(`.nav-link[href="${currentPage}"]`).addClass('active');
        
        // ===== RESPONSIVE BEHAVIOR =====
        
        // Close mobile menu when clicking on a link
        $('.navbar-nav .nav-link').on('click', function() {
            if ($(window).width() < 992) {
                $('.navbar-collapse').collapse('hide');
            }
        });
        
        // ===== INITIALIZATION =====
        
        // Show welcome message if this is the first visit
        if (!localStorage.getItem('welcomeShown')) {
            showSuccess('Chào mừng bạn đến với IT Services Management!');
            localStorage.setItem('welcomeShown', 'true');
        }
    });
    </script>

</body>
</html> 