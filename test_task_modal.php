<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Task Modal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Test Task Modal</h2>
        <button type="button" class="btn btn-primary" onclick="testCreateTask()">
            Test Tạo Task
        </button>
        
        <div id="debug-info" class="mt-3"></div>
    </div>

    <!-- Modal tạo task triển khai -->
    <div class="modal fade" id="createDeploymentTaskModal" tabindex="-1" aria-labelledby="createDeploymentTaskModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createDeploymentTaskModalLabel">
                        <i class="fas fa-plus-circle text-primary"></i> Tạo Task Triển Khai
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="createDeploymentTaskForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="task_type" class="form-label">Loại Task <span class="text-danger">*</span></label>
                                    <select class="form-select" name="task_type" id="task_type" required>
                                        <option value="">-- Chọn loại task --</option>
                                        <option value="onsite">Onsite</option>
                                        <option value="offsite">Offsite</option>
                                        <option value="remote">Remote</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="template_id" class="form-label">Task Mẫu</label>
                                    <select class="form-select" name="template_id" id="template_id">
                                        <option value="">-- Chọn task mẫu --</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="task_description" class="form-label">Task <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="task_description" id="task_description" rows="3" required placeholder="Mô tả chi tiết task..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="task_start_date" class="form-label">Thời gian bắt đầu <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" name="start_date" id="task_start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="task_end_date" class="form-label">Thời gian kết thúc <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control" name="end_date" id="task_end_date" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="task_assignee_id" class="form-label">Người thực hiện</label>
                            <select class="form-select" name="assignee_id" id="task_assignee_id">
                                <option value="">-- Chọn người thực hiện --</option>
                            </select>
                        </div>
                        
                        <input type="hidden" name="deployment_case_id" id="task_deployment_case_id" value="1">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function testCreateTask() {
            console.log('Testing create task modal...');
            
            // Set the case ID in the hidden field
            document.getElementById('task_deployment_case_id').value = '1';
            
            // Load task templates
            loadTaskTemplates();
            
            // Load IT staffs
            loadITStaffs();
            
            // Reset form
            document.getElementById('createDeploymentTaskForm').reset();
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('createDeploymentTaskModal'));
            modal.show();
        }

        // Function to load task templates
        function loadTaskTemplates() {
            console.log('Loading task templates...');
            fetch('api/get_task_templates.php')
                .then(response => response.json())
                .then(data => {
                    console.log('Task templates response:', data);
                    if (data.success) {
                        const select = document.getElementById('template_id');
                        select.innerHTML = '<option value="">-- Chọn task mẫu --</option>';
                        
                        data.data.forEach(template => {
                            const option = document.createElement('option');
                            option.value = template.id;
                            option.textContent = template.template_name;
                            option.dataset.description = template.task_description;
                            option.dataset.type = template.task_type;
                            select.appendChild(option);
                        });
                        
                        document.getElementById('debug-info').innerHTML += '<div class="alert alert-success">Task templates loaded successfully</div>';
                    } else {
                        console.error('Error loading task templates:', data.message);
                        document.getElementById('debug-info').innerHTML += '<div class="alert alert-danger">Error loading task templates: ' + data.message + '</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading task templates:', error);
                    document.getElementById('debug-info').innerHTML += '<div class="alert alert-danger">Error loading task templates: ' + error.message + '</div>';
                });
        }

        // Function to load IT staffs
        function loadITStaffs() {
            console.log('Loading IT staffs...');
            fetch('api/get_it_staffs.php')
                .then(response => response.json())
                .then(data => {
                    console.log('IT staffs response:', data);
                    if (data.success) {
                        const select = document.getElementById('task_assignee_id');
                        select.innerHTML = '<option value="">-- Chọn người thực hiện --</option>';
                        
                        data.data.forEach(staff => {
                            const option = document.createElement('option');
                            option.value = staff.id;
                            option.textContent = `${staff.fullname} (${staff.staff_code})`;
                            select.appendChild(option);
                        });
                        
                        document.getElementById('debug-info').innerHTML += '<div class="alert alert-success">IT staffs loaded successfully</div>';
                    } else {
                        console.error('Error loading IT staffs:', data.message);
                        document.getElementById('debug-info').innerHTML += '<div class="alert alert-danger">Error loading IT staffs: ' + data.message + '</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading IT staffs:', error);
                    document.getElementById('debug-info').innerHTML += '<div class="alert alert-danger">Error loading IT staffs: ' + error.message + '</div>';
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
    </script>
</body>
</html> 