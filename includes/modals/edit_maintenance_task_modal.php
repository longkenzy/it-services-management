<?php
// Modal chỉnh sửa maintenance task
// File: includes/modals/edit_maintenance_task_modal.php

// Bảo vệ file
if (!defined('AJAX_REQUEST')) {
    define('AJAX_REQUEST', true);
}

// Include các file cần thiết nếu chưa có
if (!isset($pdo)) {
    require_once '../../config/db.php';
}

// Lấy role user hiện tại
$current_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
?>

<!-- Modal chỉnh sửa maintenance task -->
<div class="modal fade" id="editMaintenanceTaskModal" tabindex="-1" aria-labelledby="editMaintenanceTaskModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editMaintenanceTaskModalLabel">Chỉnh sửa task bảo trì</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="editMaintenanceTaskForm" method="POST">
        <div class="modal-body">
          <input type="hidden" id="edit_maintenance_task_id" name="id">
          
          <div class="mb-3">
            <label for="edit_maintenance_task_description" class="form-label">Mô tả task:</label>
            <textarea class="form-control" id="edit_maintenance_task_description" name="task_description" rows="3" <?php echo ($current_role === 'user') ? 'readonly' : ''; ?>></textarea>
          </div>
          
          <div class="mb-3">
            <label for="edit_maintenance_task_notes" class="form-label">Ghi chú:</label>
            <textarea class="form-control" id="edit_maintenance_task_notes" name="notes" rows="2" <?php echo ($current_role === 'user') ? 'readonly' : ''; ?>></textarea>
          </div>
          
          <div class="row">
            <div class="col-md-6">
              <div class="mb-3">
                <label for="edit_maintenance_task_start_date" class="form-label">Ngày bắt đầu:</label>
                <input type="date" class="form-control" id="edit_maintenance_task_start_date" name="start_date" <?php echo ($current_role === 'user') ? 'readonly' : ''; ?>>
              </div>
            </div>
            <div class="col-md-6">
              <div class="mb-3">
                <label for="edit_maintenance_task_end_date" class="form-label">Ngày kết thúc:</label>
                <input type="date" class="form-control" id="edit_maintenance_task_end_date" name="end_date" <?php echo ($current_role === 'user') ? 'readonly' : ''; ?>>
              </div>
            </div>
          </div>
          
          <div class="mb-3">
            <label for="edit_maintenance_task_status" class="form-label">Trạng thái:</label>
            <select class="form-select" id="edit_maintenance_task_status" name="status" <?php echo ($current_role === 'user') ? 'disabled' : ''; ?>>
              <option value="Tiếp nhận">Tiếp nhận</option>
              <option value="Đang xử lý">Đang xử lý</option>
              <option value="Hoàn thành">Hoàn thành</option>
              <option value="Huỷ">Huỷ</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Xử lý form submit
document.getElementById('editMaintenanceTaskForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('api/update_maintenance_task.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Cập nhật task thành công!', 'success');
            // Đóng modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editMaintenanceTaskModal'));
            modal.hide();
            // Reload workspace tasks
            if (typeof loadWorkspaceTasks === 'function') {
                loadWorkspaceTasks();
            }
        } else {
            showAlert(data.message || 'Có lỗi xảy ra!', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Có lỗi xảy ra khi cập nhật!', 'error');
    });
});
</script>
