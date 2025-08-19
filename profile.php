<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'includes/session.php';
    requireLogin();
    require_once 'config/db.php';

    // Lấy user hiện tại từ session
    $currentUser = getCurrentUser();
    $username = $currentUser ? $currentUser['username'] : null;

    if (!$username) {
        throw new Exception('Không tìm thấy thông tin người dùng trong session');
    }

    // Lấy thông tin user từ database theo username
    $stmt = $pdo->prepare('
        SELECT s.* 
        FROM staffs s 
        WHERE s.username = ?
    ');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Không tìm thấy thông tin người dùng trong database');
    }

    // Xác định avatar
    $avatar_url = null;
    if (!empty($user['avatar'])) {
        $avatar_path = $user['avatar'];
        if (strpos($avatar_path, '/') === false) {
            $avatar_url = 'assets/uploads/avatars/' . $avatar_path;
        } else {
            $avatar_url = $avatar_path;
        }
    }

    // Kiểm tra file avatar có tồn tại không
    if (!$avatar_url || !file_exists($avatar_url)) {
        $avatar_url = 'assets/images/default-avatar.svg';
    }

    // Tạo avatar từ chữ cái đầu của tên
    $initials = '';
    $name_parts = explode(' ', $user['fullname'] ?? '');
    foreach ($name_parts as $part) {
        if (!empty($part)) {
            $initials .= strtoupper(substr($part, 0, 1));
        }
    }
    $initials = substr($initials, 0, 2);

    // Tạo màu background dựa trên user ID
    $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];
    $color = $colors[($user['id'] ?? 0) % count($colors)];

} catch (Exception $e) {
    // Log error
    error_log("Profile Error: " . $e->getMessage());
    
    // Show user-friendly error
    http_response_code(500);
    echo '<!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Lỗi - IT Services Management</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-body text-center">
                            <h3 class="text-danger mb-3">
                                <i class="fas fa-exclamation-triangle"></i>
                                Đã xảy ra lỗi
                            </h3>
                            <p class="text-muted">Không thể tải thông tin cá nhân. Vui lòng thử lại sau.</p>
                            <a href="dashboard.php" class="btn btn-primary">Quay lại Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân - IT Services Management</title>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo filemtime('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="assets/css/alert.css?v=<?php echo filemtime('assets/css/alert.css'); ?>">
    <style>
        :root {
            --primary-color: #3b82f6;
            --primary-dark: #2563eb;
            --primary-light: #60a5fa;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }

        body {
            background: var(--gray-50);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        /* Đảm bảo navbar có màu sắc đúng */
        .main-header .user-name,
        .navbar .user-name {
            color: var(--gray-900) !important;
        }
        
        .main-header .user-role,
        .navbar .user-role {
            color: var(--gray-600) !important;
        }

        .profile-container {
            min-height: 100vh;
            background: var(--primary-color);
            padding: 2rem 0;
        }

        .profile-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            border: none;
        }

        .profile-header {
            background: var(--primary-color);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .avatar-container {
            position: relative;
            display: inline-block;
            margin-bottom: 1.5rem;
        }

        .avatar-image {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            object-fit: cover;
            border: 6px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }

        .avatar-initials {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 3rem;
            color: white;
            border: 6px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transition: all 0.3s ease;
        }

        .avatar-upload-btn {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            font-size: 1.2rem;
        }

        .avatar-upload-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.1);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .profile-header .user-name {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: white;
        }

        .profile-header .user-username {
            font-size: 1.1rem;
            opacity: 0.9;
            color: rgba(255, 255, 255, 0.9);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            background: rgba(255, 255, 255, 0.15);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
            display: block;
        }

        .stat-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
        }

        .profile-content {
            padding: 3rem 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: var(--primary-color);
            font-size: 1.25rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .info-section {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid var(--gray-200);
        }

        .info-row {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--gray-100);
            transition: all 0.3s ease;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-row:hover {
            background: var(--gray-50);
            margin: 0 -1rem;
            padding: 1rem;
            border-radius: 8px;
        }

        .info-label {
            flex: 0 0 140px;
            font-weight: 600;
            color: var(--gray-600);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .info-label i {
            color: var(--primary-color);
            width: 16px;
        }

        .info-value {
            flex: 1;
            color: var(--gray-900);
            font-weight: 500;
        }

        .info-value.empty {
            color: var(--gray-400);
            font-style: italic;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .role-badge {
            background: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-buttons {
            margin-top: 3rem;
            text-align: center;
        }

        .btn-modern {
            padding: 1rem 2rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }

        .btn-primary-modern {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary-modern:hover {
            background: var(--primary-dark);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.4);
        }

        .btn-outline-modern {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-outline-modern:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.4);
        }

        @media (max-width: 768px) {
            .profile-header {
                padding: 2rem 1rem;
            }

            .avatar-image, .avatar-initials {
                width: 120px;
                height: 120px;
            }

            .avatar-upload-btn {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }

            .profile-header .user-name {
                font-size: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .profile-content {
                padding: 2rem 1rem;
            }

            .info-label {
                flex: 0 0 120px;
                font-size: 0.85rem;
            }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .info-section {
            animation: fadeInUp 0.6s ease-out 0.2s both;
        }

        .stat-card {
            animation: fadeInUp 0.6s ease-out 0.4s both;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="profile-container">
        <div class="container">
            <div class="profile-card">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="avatar-container">
                        <?php if ($avatar_url && $avatar_url !== 'assets/images/default-avatar.svg'): ?>
                            <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="Avatar" class="avatar-image" id="profileAvatar">
                        <?php else: ?>
                            <div class="avatar-initials" style="background-color: <?php echo $color; ?>;" id="profileAvatarInitials">
                                <?php echo $initials; ?>
                            </div>
                        <?php endif; ?>
                        <button class="avatar-upload-btn" onclick="document.getElementById('avatarInput').click()" title="Đổi ảnh đại diện">
                            <i class="fas fa-camera"></i>
                        </button>
                        <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                    </div>
                    
                    <h1 class="user-name"><?php echo htmlspecialchars($user['fullname'] ?? 'Unknown'); ?></h1>
                    <p class="user-username">@<?php echo htmlspecialchars($user['username'] ?? 'unknown'); ?></p>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <span class="stat-number"><?php echo htmlspecialchars($user['staff_code'] ?? $user['id'] ?? 'N/A'); ?></span>
                            <div class="stat-label">Mã nhân viên</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">
                                <?php 
                                if (!empty($user['start_date'])) {
                                    try {
                                        $start_date = new DateTime($user['start_date']);
                                        $current_date = new DateTime();
                                        $interval = $current_date->diff($start_date);
                                        echo $interval->y;
                                    } catch (Exception $e) {
                                        echo '0';
                                    }
                                } else {
                                    echo '0';
                                }
                                ?>
                            </span>
                            <div class="stat-label">Năm kinh nghiệm</div>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number">
                                <span class="role-badge">
                                    <i class="fas fa-crown"></i>
                                    <?php echo ucfirst($user['role'] ?? 'User'); ?>
                                </span>
                            </span>
                            <div class="stat-label">Vai trò</div>
                        </div>
                    </div>
                </div>

                <!-- Profile Content -->
                <div class="profile-content">
                    <div class="info-grid">
                        <!-- Personal Information -->
                        <div class="info-section">
                            <h2 class="section-title">
                                <i class="fas fa-user"></i>
                                Thông tin cá nhân
                            </h2>
                            <div class="info-row">
                                <div class="info-label">
                                    <i class="fas fa-id-card"></i>
                                    Họ và tên
                                </div>
                                <div class="info-value"><?php echo htmlspecialchars($user['fullname'] ?? 'Chưa cập nhật'); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">
                                    <i class="fas fa-user"></i>
                                    Tên đăng nhập
                                </div>
                                <div class="info-value"><?php echo htmlspecialchars($user['username'] ?? 'Chưa cập nhật'); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">
                                    <i class="fas fa-envelope"></i>
                                    Email
                                </div>
                                <div class="info-value <?php echo empty($user['email_work']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($user['email_work'] ?? 'Chưa cập nhật'); ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">
                                    <i class="fas fa-phone"></i>
                                    Số điện thoại
                                </div>
                                <div class="info-value <?php echo empty($user['phone_main']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($user['phone_main'] ?? 'Chưa cập nhật'); ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">
                                    <i class="fas fa-birthday-cake"></i>
                                    Sinh nhật
                                </div>
                                <div class="info-value <?php echo empty($user['birth_date']) ? 'empty' : ''; ?>">
                                    <?php 
                                    if (!empty($user['birth_date'])) {
                                        try {
                                            $birth_date = new DateTime($user['birth_date']);
                                            echo htmlspecialchars($birth_date->format('d/m/Y'));
                                        } catch (Exception $e) {
                                            echo htmlspecialchars($user['birth_date']);
                                        }
                                    } else {
                                        echo 'Chưa cập nhật';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">
                                    <i class="fas fa-venus-mars"></i>
                                    Giới tính
                                </div>
                                <div class="info-value <?php echo empty($user['gender']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($user['gender'] ?? 'Chưa cập nhật'); ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Work Information -->
                        <div class="info-section">
                            <h2 class="section-title">
                                <i class="fas fa-briefcase"></i>
                                Thông tin công việc
                            </h2>
                            <div class="info-row">
                                <div class="info-label">
                                    <i class="fas fa-building"></i>
                                    Phòng ban
                                </div>
                                <div class="info-value <?php echo empty($user['department']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($user['department'] ?? 'Chưa cập nhật'); ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">
                                    <i class="fas fa-user-tie"></i>
                                    Chức vụ
                                </div>
                                <div class="info-value <?php echo empty($user['position']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($user['position'] ?? 'Chưa cập nhật'); ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">
                                    <i class="fas fa-map-marker-alt"></i>
                                    Văn phòng
                                </div>
                                <div class="info-value <?php echo empty($user['office']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($user['office'] ?? 'Chưa cập nhật'); ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">
                                    <i class="fas fa-file-contract"></i>
                                    Loại hợp đồng
                                </div>
                                <div class="info-value <?php echo empty($user['job_type']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($user['job_type'] ?? 'Chưa cập nhật'); ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">
                                    <i class="fas fa-clock"></i>
                                    Thâm niên
                                </div>
                                <div class="info-value <?php echo empty($user['start_date']) ? 'empty' : ''; ?>">
                                    <?php 
                                    if (!empty($user['start_date'])) {
                                        try {
                                            $start_date = new DateTime($user['start_date']);
                                            $current_date = new DateTime();
                                            $interval = $current_date->diff($start_date);
                                            
                                            $years = $interval->y;
                                            $months = $interval->m;
                                            $days = $interval->d;
                                            
                                            $seniority_text = '';
                                            if ($years > 0) {
                                                $seniority_text .= $years . ' năm ';
                                            }
                                            if ($months > 0) {
                                                $seniority_text .= $months . ' tháng ';
                                            }
                                            if ($days > 0) {
                                                $seniority_text .= $days . ' ngày';
                                            }
                                            
                                            if (empty($seniority_text)) {
                                                echo 'Chưa đủ 1 ngày';
                                            } else {
                                                echo htmlspecialchars(trim($seniority_text));
                                            }
                                        } catch (Exception $e) {
                                            echo 'Chưa cập nhật';
                                        }
                                    } else {
                                        echo 'Chưa cập nhật';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">
                                    <i class="fas fa-circle"></i>
                                    Trạng thái
                                </div>
                                <div class="info-value">
                                    <span class="status-badge bg-<?php echo (strtolower($user['status'] ?? '') === 'active' || ($user['status'] ?? '') === 'Đang làm việc') ? 'success' : 'secondary'; ?>">
                                        <i class="fas fa-<?php echo (strtolower($user['status'] ?? '') === 'active' || ($user['status'] ?? '') === 'Đang làm việc') ? 'check-circle' : 'pause-circle'; ?>"></i>
                                        <?php echo htmlspecialchars($user['status'] ?? 'Unknown'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="dashboard.php" class="btn-modern btn-outline-modern">
                            <i class="fas fa-arrow-left"></i>
                            Quay lại Dashboard
                        </a>
                        <button type="button" class="btn-modern btn-primary-modern ms-3" data-action="change-password">
                            <i class="fas fa-key"></i>
                            Đổi mật khẩu
                        </button>
                    </div>
                </div>
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
                showInfo('Bạn đang ở trang thông tin cá nhân');
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
            
            // Mở modal đổi mật khẩu khi click dropdown
            $(document).on('click', '[data-action="change-password"]', function(e) {
                e.preventDefault();
                
                // Reset form nếu có
                if ($('#changePasswordForm').length) {
                    $('#changePasswordForm')[0].reset();
                }
                
                if ($('#changePasswordModal').length) {
                    $('#changePasswordModal').modal('show');
                }
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
                                showAlert('Đổi mật khẩu thành công!', 'success');
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
                
                if (strength < 25) {
                    feedback = 'Rất yếu';
                } else if (strength < 50) {
                    feedback = 'Yếu';
                } else if (strength < 75) {
                    feedback = 'Trung bình';
                } else {
                    feedback = 'Mạnh';
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
                showAlert(message, 'error');
            }
            
            function showChangePasswordSuccess(message) {
                showAlert(message, 'success');
            }
            
            // Reset modal khi đóng
            $('#changePasswordModal').on('hidden.bs.modal', function() {
                $('#changePasswordForm')[0].reset();
                $('#changePasswordForm button[type="submit"]').prop('disabled', false).text('Đổi mật khẩu');
            });
            
            // ===== AVATAR UPLOAD ===== //
            
            // Xử lý upload avatar
            $('#avatarInput').on('change', function() {
                const file = this.files[0];
                if (file) {
                    // Kiểm tra kích thước file (max 5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        showAlert('File quá lớn. Vui lòng chọn file nhỏ hơn 5MB.', 'error');
                        return;
                    }
                    
                    // Kiểm tra định dạng file
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!allowedTypes.includes(file.type)) {
                        showAlert('Chỉ chấp nhận file ảnh (JPG, PNG, GIF).', 'error');
                        return;
                    }
                    
                    // Hiển thị preview
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // Ẩn avatar initials nếu có
                        $('#profileAvatarInitials').hide();
                        
                        // Hiển thị hoặc cập nhật avatar image
                        if ($('#profileAvatar').length) {
                            $('#profileAvatar').attr('src', e.target.result);
                        } else {
                            $('.avatar-container').prepend('<img src="' + e.target.result + '" alt="Avatar" class="avatar-image" id="profileAvatar">');
                        }
                    };
                    reader.readAsDataURL(file);
                    
                    // Upload file
                    uploadAvatar(file);
                }
            });
        });
        
        // ===== FUNCTIONS ===== //
        
        function uploadAvatar(file) {
            const formData = new FormData();
            formData.append('avatar', file);
            
            $.ajax({
                url: 'upload_avatar.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showAlert('Cập nhật ảnh đại diện thành công!', 'success');
                    } else {
                        showAlert(response.message || 'Có lỗi xảy ra khi cập nhật ảnh đại diện.', 'error');
                    }
                },
                error: function() {
                    showAlert('Có lỗi xảy ra khi cập nhật ảnh đại diện.', 'error');
                }
            });
        }
    </script>
</body>
</html> 