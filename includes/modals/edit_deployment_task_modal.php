<?php
// Modal chỉnh sửa deployment task
// File: includes/modals/edit_deployment_task_modal.php

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

<!-- Modal chỉnh sửa deployment task -->
<div class="modal fade" id="editDeploymentTaskModal" tabindex="-1" aria-labelledby="editDeploymentTaskModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width: calc(100vw - 40px); max-width: none; margin: 20px auto;">
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
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">X Hủy</button>
        <button type="submit" form="editDeploymentTaskForm" class="btn btn-warning">
          <i class="fas fa-save"></i> Cập nhật Task
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Xử lý form submit
document.getElementById('editDeploymentTaskForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('api/update_deployment_task.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Cập nhật task thành công!', 'success');
            // Đóng modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editDeploymentTaskModal'));
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
