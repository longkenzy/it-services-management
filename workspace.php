<?php
session_start();
require_once 'includes/session.php';
$user_fullname = $_SESSION['fullname'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? 0;
file_put_contents(__DIR__ . '/debug_workspace_php.txt', 'workspace.php user_id: ' . $user_id . ', username: ' . ($_SESSION['username'] ?? 'null') . ', role: ' . ($_SESSION['role'] ?? 'null') . PHP_EOL, FILE_APPEND);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <title>Workspace - IT Services Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?php echo filemtime('assets/css/dashboard.css'); ?>">
    <link rel="stylesheet" href="assets/css/alert.css?v=<?php echo filemtime('assets/css/alert.css'); ?>">
    <link rel="stylesheet" href="assets/css/no-border-radius.css?v=<?php echo filemtime('assets/css/no-border-radius.css'); ?>">
    <link rel="stylesheet" href="assets/css/workspace.css?v=<?php echo filemtime('assets/css/workspace.css'); ?>">
</head>
<body>
<?php require_once 'includes/header.php'; ?>
<main class="main-content">
  <div class="container-fluid px-4 py-4">
    <div class="page-header mb-4">
      <div class="row align-items-center">
        <div class="col">
          <h1 class="page-title mb-0">Workspace</h1>
          <p class="text-muted mb-0">Các task bạn cần làm hoặc đang làm</p>
        </div>
      </div>
    </div>
    <div class="row mb-4">
      <div class="col-12">
        <div class="card">
          <div class="card-body">
            <div class="d-flex flex-wrap align-items-center mb-3 gap-2">
              <button class="btn btn-outline-info" id="btn-filter-processing">Đang xử lý</button>
              <button class="btn btn-outline-success" id="btn-filter-done-last-month">Hoàn thành tháng trước</button>
              <button class="btn btn-outline-success" id="btn-filter-done-this-month">Hoàn thành tháng này</button>
            </div>
            <div class="table-responsive">
              <table id="workspace-table" class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>LINK</th>
                    <th>STT</th>
                    <th>MÃ ITSM</th>
                    <th>LEVEL</th>
                    <th>LOẠI CASE</th>
                    <th>LOẠI DV</th>
                    <th>KHÁCH HÀNG</th>
                    <th>BẮT ĐẦU</th>
                    <th>KẾT THÚC</th>
                    <th>TRẠNG THÁI</th>
                  </tr>
                </thead>
                <tbody id="workspace-tasks-table">
                  <tr><td colspan="10" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
<!-- jQuery (load trước) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Alert System -->
<script src="assets/js/alert.js?v=<?php echo filemtime('assets/js/alert.js'); ?>"></script>
<!-- Custom JavaScript -->
<script src="assets/js/dashboard.js?v=<?php echo filemtime('assets/js/dashboard.js'); ?>"></script>
<script>
function formatDateForDisplay(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    return d.toLocaleDateString('vi-VN') + ' ' + d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
}
function loadWorkspaceTasks(statusFilter = 'processing') {
    const tbody = document.getElementById('workspace-tasks-table');
    tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted py-4"><i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...</td></tr>';
    fetch('api/get_workspace_tasks.php?status=' + encodeURIComponent(statusFilter))
        .then(res => res.json())
        .then(data => {
            if (!data.success || !Array.isArray(data.data) || data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center text-muted py-4">Không có task nào</td></tr>';
                return;
            }
            tbody.innerHTML = '';
            data.data.forEach((item, idx) => {
                tbody.innerHTML += `
                    <tr>
                        <td></td>
                        <td>${idx + 1}</td>
                        <td>${item.case_code || ''}</td>
                        <td>${item.level || ''}</td>
                        <td>${item.case_type || ''}</td>
                        <td>${item.service_type || ''}</td>
                        <td>${item.customer_name || ''}</td>
                        <td>${item.start_date ? formatDateForDisplay(item.start_date) : ''}</td>
                        <td>${item.end_date ? formatDateForDisplay(item.end_date) : ''}</td>
                        <td><span class="badge bg-${item.status === 'Hoàn thành' ? 'success' : (item.status === 'Đang xử lý' ? 'warning' : 'secondary')}">${item.status}</span></td>
                    </tr>
                `;
            });
        });
}
document.getElementById('btn-filter-processing').onclick = function() { loadWorkspaceTasks('processing'); };
document.getElementById('btn-filter-done-last-month').onclick = function() { loadWorkspaceTasks('done_last_month'); };
document.getElementById('btn-filter-done-this-month').onclick = function() { loadWorkspaceTasks('done_this_month'); };
document.addEventListener('DOMContentLoaded', function() { loadWorkspaceTasks('processing'); });
</script>
</body>
</html> 