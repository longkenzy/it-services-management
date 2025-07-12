<?php
require_once 'includes/session.php';
requireLogin();
require_once 'config/db.php';

// Lấy user hiện tại từ session
$currentUser = getCurrentUser();
$username = $currentUser ? $currentUser['username'] : null;

// Lấy thông tin user từ database theo username
$stmt = $pdo->prepare('SELECT * FROM staffs WHERE username = ?');
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('Không tìm thấy thông tin người dùng!');
}

// Xác định avatar
$avatar_url = !empty($user['avatar']) && file_exists($user['avatar']) ? $user['avatar'] : 'assets/images/default-avatar.svg';

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân</title>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo filemtime('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="assets/css/alert.css?v=<?php echo filemtime('assets/css/alert.css'); ?>">
    <!-- No Border Radius Override -->
    <link rel="stylesheet" href="assets/css/no-border-radius.css?v=<?php echo filemtime('assets/css/no-border-radius.css'); ?>">
</head>
<body>
<?php include 'includes/header.php'; ?>
<main class="main-content">
    <div class="container-fluid px-4 py-4">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <img src="<?php echo htmlspecialchars($avatar_url); ?>" alt="Avatar" class="rounded-circle me-4" style="width: 90px; height: 90px; object-fit: cover; border: 2px solid #dee2e6;">
                            <div>
                                <h3 class="mb-1 fw-bold"><?php echo htmlspecialchars($user['fullname']); ?></h3>
                                <div class="text-muted mb-1">Tài khoản: <strong><?php echo htmlspecialchars($user['username']); ?></strong></div>
                                <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo $user['status'] === 'active' ? 'Đang làm việc' : 'Ngừng hoạt động'; ?>
                                </span>
                            </div>
                        </div>
                        <hr>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Email:</div>
                            <div class="col-sm-8 fw-semibold"><?php echo htmlspecialchars($user['email']); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Phòng ban:</div>
                            <div class="col-sm-8 fw-semibold"><?php echo htmlspecialchars($user['department'] ?? ''); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Chức vụ:</div>
                            <div class="col-sm-8 fw-semibold"><?php echo htmlspecialchars($user['position'] ?? ''); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Số điện thoại:</div>
                            <div class="col-sm-8 fw-semibold"><?php echo htmlspecialchars($user['phone'] ?? ''); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Ngày sinh:</div>
                            <div class="col-sm-8 fw-semibold"><?php echo htmlspecialchars($user['dob'] ?? ''); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Ngày vào làm:</div>
                            <div class="col-sm-8 fw-semibold"><?php echo htmlspecialchars($user['start_date'] ?? ''); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Giới tính:</div>
                            <div class="col-sm-8 fw-semibold"><?php echo htmlspecialchars($user['gender'] ?? ''); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Địa chỉ:</div>
                            <div class="col-sm-8 fw-semibold"><?php echo htmlspecialchars($user['address'] ?? ''); ?></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-4 text-muted">Ghi chú:</div>
                            <div class="col-sm-8 fw-semibold"><?php echo htmlspecialchars($user['notes'] ?? ''); ?></div>
                        </div>
                        <div class="mt-4">
                            <a href="dashboard.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-2"></i>Quay lại Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/alert.js?v=<?php echo filemtime('assets/js/alert.js'); ?>"></script>
</body>
</html> 