<?php
/**
 * IT CRM - Common Header
 * File: includes/header.php
 * Mục đích: Header chung cho tất cả các trang
 */

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Access denied.');
}

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && strpos($haystack, $needle) !== false;
    }
}

// Kiểm tra đã có session và current_user chưa
if (!isset($current_user) || !$current_user) {
    $current_user = getCurrentUser();
}
// Lấy avatar từ bảng staffs dựa trên username
require_once 'config/db.php';
$avatar_url = null;
$stmt = $pdo->prepare('SELECT avatar FROM staffs WHERE username = ? LIMIT 1');
$stmt->execute([$current_user['username']]);
$row = $stmt->fetch();
if ($row && !empty($row['avatar'])) {
    $avatar_path = $row['avatar'];
    // Nếu đường dẫn không có thư mục, thêm vào
    if (!str_contains($avatar_path, '/')) {
        $avatar_url = 'assets/uploads/avatars/' . $avatar_path;
    } else {
        $avatar_url = $avatar_path;
    }
}
// Tạo avatar từ chữ cái đầu của tên
$initials = '';
$name_parts = explode(' ', $current_user['fullname']);
foreach ($name_parts as $part) {
    $initials .= strtoupper(substr($part, 0, 1));
}
$initials = substr($initials, 0, 2); // Chỉ lấy 2 ký tự đầu
// Tạo màu background dựa trên user ID
$colors = ['#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6f42c1'];
$color = $colors[$current_user['id'] % count($colors)];

// Xác định trang hiện tại để highlight menu
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Header -->
<header class="main-header">
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid px-4">
            
            <!-- Logo và Brand -->
            <div class="navbar-brand-wrapper d-flex align-items-center">
                <div class="brand-logo me-3">
                    <img src="assets/images/logo.png" alt="Logo công ty" class="logo-img" />
                </div>
                <div class="brand-text">
                    <h5 class="mb-0 fw-bold text-primary">IT Services Management</h5>
                    <small class="text-muted">ITSM - Quản lý Dịch vụ CNTT</small>
                </div>
            </div>
            
            <!-- Mobile Toggle Button -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#navbarContent" aria-controls="navbarContent" 
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navbar Content -->
            <div class="collapse navbar-collapse" id="navbarContent">
                
                <!-- Main Navigation -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    
                    <!-- Trang chủ -->
                    <li class="nav-item">
                        <a class="nav-link fw-semibold <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" 
                           href="dashboard.php" id="homeLink">
                            <i class="fas fa-home me-2"></i>
                            Trang chủ
                        </a>
                    </li>

                    <!-- Workspace -->
                    <li class="nav-item">
                        <a class="nav-link fw-semibold <?php echo $current_page === 'workspace.php' ? 'active' : ''; ?>" 
                           href="workspace.php" id="workspaceLink">
                            <i class="fas fa-bolt me-2"></i>
                            Workspace
                        </a>
                    </li>
                    
                    <!-- Dropdown Công việc -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle fw-semibold" href="#" 
                           id="workDropdown" role="button" data-bs-toggle="dropdown" 
                           aria-expanded="false">
                            <i class="fas fa-tasks me-2"></i>
                            Công việc
                        </a>
                        <ul class="dropdown-menu dropdown-menu-custom" aria-labelledby="workDropdown">
                            <li>
                                <a class="dropdown-item" href="internal_cases.php">
                                    <i class="fas fa-ticket-alt me-2 text-primary"></i>
                                    IT TICKET
                                </a>
                            </li>
                            <li class="dropdown-submenu">
                                <a class="dropdown-item dropdown-toggle d-flex justify-content-between align-items-center" href="#" id="projectDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span><i class="fas fa-project-diagram me-2 text-success"></i>DỰ ÁN</span>
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="projectDropdown">
                                    <li>
                                        <a class="dropdown-item" href="deployment_requests.php">
                                            <i class="fas fa-rocket me-2 text-success"></i>
                                            TRIỂN KHAI
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="maintenance_requests.php">
                                            <i class="fas fa-wrench me-2 text-warning"></i>
                                            BẢO TRÌ
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Menu Nhân sự -->
                    <li class="nav-item">
                        <a class="nav-link fw-semibold <?php echo $current_page === 'staff.php' ? 'active' : ''; ?>" 
                           href="staff.php" id="staffLink">
                            <i class="fas fa-users me-2"></i>
                            Nhân sự
                        </a>
                    </li>
                    
                    <!-- Cấu hình - Chỉ hiển thị cho admin và leader -->
                    <?php if (hasRole(['admin', 'leader'])): ?>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold <?php echo $current_page === 'config.php' ? 'active' : ''; ?>" 
                           href="config.php" id="configLink">
                            <i class="fas fa-cogs me-2"></i>
                            Cấu hình
                        </a>
                    </li>
                    <?php endif; ?>
                    
                </ul>
                
                <!-- Search Bar -->
                <div class="search-wrapper mx-lg-4 my-3 my-lg-0">
                    <form class="search-form">
                        <div class="input-group">
                            <input type="text" class="form-control border-start-0" 
                                   placeholder="Tìm kiếm case, nhân viên..." id="globalSearchInput">
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Thông báo ngoài header -->
                <div class="header-notification ms-auto me-3 d-flex align-items-center">
                    <a class="nav-link position-relative" href="#" data-action="notifications" title="Thông báo">
                        <i class="fas fa-bell fa-lg"></i>
                        <span class="badge bg-danger position-absolute top-0 start-100 translate-middle ms-2">3</span>
                    </a>
                </div>
                
                <!-- User Profile -->
                <div class="user-profile-wrapper">
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle user-profile-link" href="#" 
                           id="userDropdown" role="button" data-bs-toggle="dropdown" 
                           aria-expanded="false">
                            <div class="user-avatar me-2">
                                <?php
                                if ($avatar_url && file_exists($avatar_url)) {
                                    // Hiển thị avatar thật
                                    echo '<img src="' . htmlspecialchars($avatar_url) . '" alt="Avatar" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">';
                                } else {
                                    // Hiển thị avatar chữ cái
                                    echo '<div style="width: 40px; height: 40px; background-color: ' . $color . '; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px;">' . $initials . '</div>';
                                }
                                ?>
                            </div>
                            <div class="user-info d-none d-md-block">
                                <div class="user-name fw-semibold"><?php echo htmlspecialchars($current_user['fullname']); ?></div>
                                <small class="user-role text-muted"><?php echo ucfirst($current_user['role']); ?></small>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom" 
                            aria-labelledby="userDropdown">
                            <li class="dropdown-header">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <?php
                                        if ($avatar_url && file_exists($avatar_url)) {
                                            echo '<img src="' . htmlspecialchars($avatar_url) . '" alt="Avatar" class="rounded-circle" style="width: 50px; height: 50px; object-fit: cover;">';
                                        } else {
                                            echo '<div style="width: 50px; height: 50px; background-color: ' . $color . '; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px;">' . $initials . '</div>';
                                        }
                                        ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($current_user['fullname']); ?></div>
                                        <small class="text-muted"><?php echo htmlspecialchars($current_user['username']); ?></small>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="profile.php">
                                    <i class="fas fa-user me-2"></i>
                                    Thông tin cá nhân
                                </a>
                            </li>

                            <li>
                                <a class="dropdown-item" href="#" data-action="change-password">
                                    <i class="fas fa-key me-2"></i>
                                    Đổi mật khẩu
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="#" data-action="logout">
                                    <i class="fas fa-sign-out-alt me-2"></i>
                                    Đăng xuất
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                
            </div>
        </div>
    </nav>
</header>

<!-- Modal Đổi Mật Khẩu -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="changePasswordForm" autocomplete="off">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">
                        <i class="fas fa-key me-2"></i>Đổi mật khẩu
                    </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" tabindex="-1"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <div class="row align-items-center">
                            <div class="col-4">
                                <label for="old_password" class="form-label mb-0 fw-semibold">
                                    <i class="fas fa-lock me-2 text-muted"></i>Mật khẩu cũ
                                </label>
                            </div>
                            <div class="col-8">
                                <input type="password" class="form-control" id="old_password" name="old_password" 
                                       placeholder="Nhập mật khẩu hiện tại" required autocomplete="current-password">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="row align-items-center">
                            <div class="col-4">
                                <label for="new_password" class="form-label mb-0 fw-semibold">
                                    <i class="fas fa-key me-2 text-primary"></i>Mật khẩu mới
                                </label>
                            </div>
                            <div class="col-8">
                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                       placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)" required autocomplete="new-password">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="row align-items-center">
                            <div class="col-4">
                                <label for="confirm_password" class="form-label mb-0 fw-semibold">
                                    <i class="fas fa-check-circle me-2 text-success"></i>Xác nhận
                                </label>
                            </div>
                            <div class="col-8">
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                       placeholder="Nhập lại mật khẩu mới" required autocomplete="new-password">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Password Strength Indicator -->
                    <div class="mb-3">
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <small class="text-muted" id="strengthText">Độ mạnh mật khẩu</small>
                        </div>
                    </div>
                    
                    <!-- Alerts have been moved to toast system -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Đổi mật khẩu</button>
                </div>
            </form>
        </div>
    </div>
</div> 