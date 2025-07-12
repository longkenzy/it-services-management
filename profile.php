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
        if (!str_contains($avatar_path, '/')) {
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
    $colors = ['#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6f42c1'];
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
    <!-- No Border Radius Override -->
    <link rel="stylesheet" href="assets/css/no-border-radius.css?v=<?php echo filemtime('assets/css/no-border-radius.css'); ?>">
    
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .avatar-container {
            position: relative;
            display: inline-block;
        }
        
        .avatar-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .avatar-initials {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 2.5rem;
            color: white;
            border: 4px solid white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .avatar-upload-btn {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .avatar-upload-btn:hover {
            background: #0056b3;
            transform: scale(1.1);
        }
        
        .info-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            border: none;
            margin-bottom: 1.5rem;
        }
        
        .info-card .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            border-radius: 12px 12px 0 0 !important;
            padding: 1rem 1.5rem;
        }
        
        .info-card .card-body {
            padding: 1.5rem;
        }
        
        .info-row {
            display: flex;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            flex: 0 0 140px;
            font-weight: 600;
            color: #495057;
            font-size: 0.9rem;
        }
        
        .info-value {
            flex: 1;
            color: #212529;
            font-weight: 500;
        }
        
        .info-value.empty {
            color: #6c757d;
            font-style: italic;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .role-badge {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .action-buttons {
            margin-top: 2rem;
        }
        
        .btn-custom {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        @media (max-width: 768px) {
            .info-label {
                flex: 0 0 120px;
                font-size: 0.85rem;
            }
            
            .avatar-image, .avatar-initials {
                width: 100px;
                height: 100px;
            }
            
            .avatar-upload-btn {
                width: 35px;
                height: 35px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container-fluid px-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h2 mb-2">
                        <i class="fas fa-user-circle me-3"></i>
                        Thông tin cá nhân
                    </h1>
                    <p class="mb-0 opacity-75">Quản lý thông tin cá nhân và cài đặt tài khoản</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex align-items-center justify-content-md-end">
                        <span class="role-badge me-3">
                            <i class="fas fa-crown me-1"></i>
                            <?php echo ucfirst($user['role'] ?? 'User'); ?>
                        </span>
                        <span class="status-badge bg-<?php echo (strtolower($user['status'] ?? '') === 'active' || ($user['status'] ?? '') === 'Đang làm việc') ? 'success' : 'secondary'; ?>">
                            <?php echo htmlspecialchars($user['status'] ?? 'Unknown'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <main class="main-content">
        <div class="container-fluid px-4">
            <div class="row">
                <!-- Left Column - Avatar & Basic Info -->
                <div class="col-lg-4">
                    <div class="info-card">
                        <div class="card-body text-center">
                            <div class="avatar-container mb-4">
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
                            
                            <h4 class="fw-bold mb-2"><?php echo htmlspecialchars($user['fullname'] ?? 'Unknown'); ?></h4>
                            <p class="text-muted mb-3">@<?php echo htmlspecialchars($user['username'] ?? 'unknown'); ?></p>
                            
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="stats-card">
                                        <div class="stats-number"><?php echo htmlspecialchars($user['staff_code'] ?? $user['id'] ?? 'N/A'); ?></div>
                                        <div class="stats-label">Mã nhân viên</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stats-card">
                                        <div class="stats-number">
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
                                        </div>
                                        <div class="stats-label">Năm kinh nghiệm</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column - Detailed Info -->
                <div class="col-lg-8">
                    <!-- Personal Information -->
                    <div class="info-card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user me-2 text-primary"></i>
                                Thông tin cá nhân
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="info-row">
                                <div class="info-label">Họ và tên:</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['fullname'] ?? 'Chưa cập nhật'); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Tên đăng nhập:</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['username'] ?? 'Chưa cập nhật'); ?></div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Email:</div>
                                <div class="info-value <?php echo empty($user['email_work']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($user['email_work'] ?? 'Chưa cập nhật'); ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Số điện thoại:</div>
                                <div class="info-value <?php echo empty($user['phone_main']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($user['phone_main'] ?? 'Chưa cập nhật'); ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Sinh nhật:</div>
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
                                <div class="info-label">Giới tính:</div>
                                <div class="info-value <?php echo empty($user['gender']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($user['gender'] ?? 'Chưa cập nhật'); ?>
                                </div>
                            </div>

                        </div>
                    </div>
                    
                    <!-- Work Information -->
                    <div class="info-card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-briefcase me-2 text-success"></i>
                                Thông tin công việc
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="info-row">
                                <div class="info-label">Phòng ban:</div>
                                <div class="info-value <?php echo empty($user['department']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($user['department'] ?? 'Chưa cập nhật'); ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Chức vụ:</div>
                                <div class="info-value <?php echo empty($user['position']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($user['position'] ?? 'Chưa cập nhật'); ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Văn phòng:</div>
                                <div class="info-value <?php echo empty($user['office']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($user['office'] ?? 'Chưa cập nhật'); ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Loại hợp đồng:</div>
                                <div class="info-value <?php echo empty($user['job_type']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($user['job_type'] ?? 'Chưa cập nhật'); ?>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Thâm niên:</div>
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
                                <div class="info-label">Trạng thái:</div>
                                <div class="info-value">
                                    <span class="status-badge bg-<?php echo (strtolower($user['status'] ?? '') === 'active' || ($user['status'] ?? '') === 'Đang làm việc') ? 'success' : 'secondary'; ?>">
                                        <?php echo htmlspecialchars($user['status'] ?? 'Unknown'); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="dashboard.php" class="btn btn-outline-primary btn-custom me-3">
                            <i class="fas fa-arrow-left me-2"></i>
                            Quay lại Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

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