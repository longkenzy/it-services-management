/**
 * IT CRM - Positions Management JavaScript
 * File: assets/js/positions.js
 * Mục đích: Xử lý inline editing cho bảng chức vụ
 */

// Global positions functions
window.addNewPositionRow = function() {
    var tbody = $('#positions-tbody');
    
    // Check if there's already a new row being added
    if (tbody.find('.new-position-row').length > 0) {
        showWarning('Vui lòng hoàn tất việc thêm chức vụ hiện tại trước khi thêm mới!');
        return;
    }
    
    // Hide "no data" message if it exists
    var noDataRow = tbody.find('tr td[colspan="4"]').parent();
    var hasNoDataRow = noDataRow.length > 0;
    noDataRow.hide();
    
    // Calculate next row number
    var actualRows = tbody.find('tr:visible').length;
    var nextRowNumber = hasNoDataRow ? 1 : actualRows + 1;
    
    // Get departments for select options
    $.ajax({
        url: 'api/departments.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var departmentOptions = '<option value="">Chọn phòng ban</option>';
                response.data.forEach(function(dept) {
                    departmentOptions += `<option value="${dept.id}">${dept.name}</option>`;
                });
                
                // Create new row
                var newRow = `
                    <tr class="new-position-row">
                        <td>${nextRowNumber}</td>
                        <td>
                            <input type="text" class="form-control" id="new-position-name" placeholder="Tên chức vụ" required>
                        </td>
                        <td>
                            <select class="form-select" id="new-position-department" required>
                                ${departmentOptions}
                            </select>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-success" onclick="savePosition()" title="Lưu">
                                    <i class="fas fa-save"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="cancelAddPosition()" title="Hủy">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                
                // Add new row to table
                tbody.append(newRow);
                
                // Focus on name input
                $('#new-position-name').focus();
                
                // Add keyboard event handlers
                $('#new-position-name, #new-position-department').on('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        savePosition();
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        cancelAddPosition();
                    }
                });
            } else {
                showError('Không thể tải danh sách phòng ban!');
            }
        },
        error: function() {
            showError('Có lỗi xảy ra khi tải danh sách phòng ban!');
        }
    });
};

window.savePosition = function() {
    var name = $('#new-position-name').val().trim();
    var department_id = $('#new-position-department').val();
    
    if (!name) {
        showError('Tên chức vụ không được để trống!');
        $('#new-position-name').focus();
        return;
    }
    
    if (!department_id) {
        showError('Vui lòng chọn phòng ban!');
        $('#new-position-department').focus();
        return;
    }
    
    var formData = {
        name: name,
        department_id: department_id
    };
    
    $.ajax({
        url: 'api/positions.php',
        method: 'POST',
        dataType: 'json',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.message);
                
                // Thay thế row mới bằng row thực thay vì reload trang
                var tbody = $('#positions-tbody');
                var newRow = tbody.find('.new-position-row');
                var newRowNumber = newRow.find('td:first').text();
                
                // Tạo row mới với dữ liệu thực
                var realRow = `
                    <tr>
                        <td>${newRowNumber}</td>
                        <td>
                            <div class="fw-semibold">
                                ${name}
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-info">
                                ${response.data.department_name}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="editPosition(${response.data.id})"
                                        title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="deletePosition(${response.data.id}, '${name}')"
                                        title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                
                // Thay thế row mới bằng row thực
                newRow.replaceWith(realRow);
                
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            var message = 'Có lỗi xảy ra khi thêm chức vụ!';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            showError(message);
        }
    });
};

window.cancelAddPosition = function() {
    $('#positions-tbody').find('.new-position-row').remove();
};

window.editPosition = function(id) {
    // Find the row
    var row = $('#positions-tbody').find(`button[onclick*="editPosition(${id}"]`).closest('tr');
    
    if (row.length === 0) {
        return;
    }
    
    // Get current values
    var nameCell = row.find('td:eq(1)');
    var departmentCell = row.find('td:eq(2)');
    var currentName = nameCell.find('.fw-semibold').text().trim();
    var currentDepartmentName = departmentCell.find('.badge').text().trim();
    
    // Add editing class
    row.addClass('editing-row');
    
    // Get departments for select options
    $.ajax({
        url: 'api/departments.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var departmentOptions = '';
                var currentDepartmentId = '';
                
                response.data.forEach(function(dept) {
                    var selected = dept.name === currentDepartmentName ? ' selected' : '';
                    if (selected) currentDepartmentId = dept.id;
                    departmentOptions += `<option value="${dept.id}"${selected}>${dept.name}</option>`;
                });
                
                // Replace cells with inputs
                nameCell.html(`
                    <input type="text" class="form-control" id="edit-position-name-${id}" value="${currentName}" required>
                `);
                
                departmentCell.html(`
                    <select class="form-select" id="edit-position-department-${id}" required>
                        ${departmentOptions}
                    </select>
                `);
                
                // Replace action buttons
                var actionCell = row.find('td:eq(3)');
                actionCell.html(`
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-success" onclick="updatePosition(${id})" title="Lưu">
                            <i class="fas fa-save"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="cancelEditPosition(${id}, '${currentName}', '${currentDepartmentName}')" title="Hủy">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `);
                
                // Focus on name input
                $(`#edit-position-name-${id}`).focus().select();
                
                // Add keyboard event handlers
                $(`#edit-position-name-${id}, #edit-position-department-${id}`).on('keydown', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        updatePosition(id);
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        cancelEditPosition(id, currentName, currentDepartmentName);
                    }
                });
            } else {
                showError('Không thể tải danh sách phòng ban!');
            }
        },
        error: function() {
            showError('Có lỗi xảy ra khi tải danh sách phòng ban!');
        }
    });
};

window.updatePosition = function(id) {
    var name = $(`#edit-position-name-${id}`).val().trim();
    var department_id = $(`#edit-position-department-${id}`).val();
    
    if (!name) {
        showError('Tên chức vụ không được để trống!');
        $(`#edit-position-name-${id}`).focus();
        return;
    }
    
    if (!department_id) {
        showError('Vui lòng chọn phòng ban!');
        $(`#edit-position-department-${id}`).focus();
        return;
    }
    
    var formData = {
        name: name,
        department_id: department_id
    };
    
    $.ajax({
        url: 'api/positions.php?id=' + id,
        method: 'PUT',
        dataType: 'json',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.message);
                
                // Cập nhật row trực tiếp thay vì reload trang
                var row = $('#positions-tbody').find(`button[onclick*="updatePosition(${id}"]`).closest('tr');
                row.removeClass('editing-row');
                
                // Restore row với dữ liệu mới
                row.find('td:eq(1)').html(`
                    <div class="fw-semibold">
                        ${name}
                    </div>
                `);
                
                row.find('td:eq(2)').html(`
                    <span class="badge bg-info">
                        ${response.data.department_name}
                    </span>
                `);
                
                row.find('td:eq(3)').html(`
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                onclick="editPosition(${id})"
                                title="Chỉnh sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="deletePosition(${id}, '${name}')"
                                title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `);
                
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            var message = 'Có lỗi xảy ra khi cập nhật chức vụ!';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            showError(message);
        }
    });
};

window.cancelEditPosition = function(id, originalName, originalDepartmentName) {
    var row = $('#positions-tbody').find(`button[onclick*="updatePosition(${id}"]`).closest('tr');
    row.removeClass('editing-row');
    
    // Restore original content
    row.find('td:eq(1)').html(`
        <div class="fw-semibold">
            ${originalName}
        </div>
    `);
    
    row.find('td:eq(2)').html(`
        <span class="badge bg-info">
            ${originalDepartmentName}
        </span>
    `);
    
    row.find('td:eq(3)').html(`
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-outline-primary" 
                    onclick="editPosition(${id})"
                    title="Chỉnh sửa">
                <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" 
                    onclick="deletePosition(${id}, '${originalName}')"
                    title="Xóa">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `);
};

window.deletePosition = function(id, name) {
    if (!confirm(`Bạn có chắc chắn muốn xóa chức vụ "${name}"?`)) {
        return;
    }
    
    $.ajax({
        url: 'api/positions.php?id=' + id,
        method: 'DELETE',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.message);
                
                // Xóa row khỏi table thay vì reload trang
                var row = $('#positions-tbody').find(`button[onclick*="deletePosition(${id}"]`).closest('tr');
                row.fadeOut(300, function() {
                    row.remove();
                    updatePositionRowNumbers();
                });
                
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            var message = 'Có lỗi xảy ra khi xóa chức vụ!';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            showError(message);
        }
    });
};

/**
 * Update row numbers after deletion
 */
function updatePositionRowNumbers() {
    $('#positions-tbody tr').each(function(index) {
        $(this).find('td:first').text(index + 1);
    });
} 