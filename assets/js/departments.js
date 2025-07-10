/**
 * IT CRM - Departments Management JavaScript
 * File: assets/js/departments.js
 * Mục đích: Xử lý inline editing cho phòng ban
 */

// Đảm bảo jQuery đã load
if (typeof jQuery === 'undefined') {
    throw new Error('Departments JavaScript requires jQuery');
}

// Định nghĩa functions trong global scope
window.addNewDepartmentRow = function() {
    
    
    var tbody = $('#departments-tbody');
    
    
    if (tbody.length === 0) {
        
        return;
    }
    
    // Đếm số row thật sự chứa dữ liệu (có button actions), không tính row thông báo trống
    var dataRows = tbody.find('tr').filter(function() {
        return $(this).find('button').length > 0; // Chỉ đếm row có button
    });
    var newRowNumber = dataRows.length + 1;
    
    // Check if there's already a new row being added
    if (tbody.find('.new-department-row').length > 0) {
        showWarning('Vui lòng hoàn tất việc thêm phòng ban hiện tại trước khi thêm mới!');
        return;
    }
    
    // Hide "no data" message if it exists
    tbody.find('tr td[colspan="5"]').parent().hide();
    
    var newRow = `
        <tr class="new-department-row">
            <td>${newRowNumber}</td>
            <td>
                <input type="text" class="form-control" id="new-dept-name" placeholder="Tên phòng ban" required>
            </td>
            <td>
                <input type="text" class="form-control" id="new-dept-office" placeholder="Văn phòng">
            </td>
            <td>
                <textarea class="form-control" id="new-dept-address" rows="2" placeholder="Địa chỉ"></textarea>
            </td>
            <td>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-success" onclick="saveDepartment()" title="Lưu">
                        <i class="fas fa-save"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="cancelAddDepartment()" title="Hủy">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </td>
        </tr>
    `;
    
    tbody.append(newRow);
    
    
    // Focus on the name input
    $('#new-dept-name').focus();
    
    // Add keyboard event handlers
    $('#new-dept-name').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            saveDepartment();
        } else if (e.key === 'Escape') {
            e.preventDefault();
            cancelAddDepartment();
        }
    });
};

window.saveDepartment = function() {
    
    
    var name = $('#new-dept-name').val().trim();
    var office = $('#new-dept-office').val().trim();
    var address = $('#new-dept-address').val().trim();
    
    if (!name) {
        showError('Tên phòng ban không được để trống!');
        $('#new-dept-name').focus();
        return;
    }
    
    var formData = {
        name: name,
        office: office,
        address: address
    };
    
    $.ajax({
        url: 'api/departments.php',
        method: 'POST',
        dataType: 'json',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.message);
                
                // Thay thế row mới bằng row thực thay vì reload trang
                var tbody = $('#departments-tbody');
                var newRow = tbody.find('.new-department-row');
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
                        <td>${office || 'N/A'}</td>
                        <td>
                            <div class="text-truncate" style="max-width: 200px;" title="${address || ''}">
                                ${address || 'N/A'}
                            </div>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="editDepartment(${response.data ? response.data.id : 'null'})"
                                        title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteDepartment(${response.data ? response.data.id : 'null'}, '${name}')"
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
            var message = 'Có lỗi xảy ra khi thêm phòng ban!';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            showError(message);
        }
    });
};

window.cancelAddDepartment = function() {
    $('#departments-tbody').find('.new-department-row').remove();
};

window.editDepartment = function(id) {
    
    
    // Find the row
    var row = $('#departments-tbody').find(`button[onclick*="editDepartment(${id}"]`).closest('tr');
    
    if (row.length === 0) {
        
        return;
    }
    
    // Get current values
    var nameCell = row.find('td:eq(1)');
    var currentName = nameCell.find('.fw-semibold').text().trim();
    var currentOffice = row.find('td:eq(2)').text().trim();
    var currentAddress = row.find('td:eq(3)').text().trim();
    
    // Handle N/A values
    if (currentOffice === 'N/A') currentOffice = '';
    if (currentAddress === 'N/A') currentAddress = '';
    
    // Add editing class
    row.addClass('editing-row');
    
    // Replace cells with inputs
    nameCell.html(`
        <input type="text" class="form-control" id="edit-dept-name-${id}" value="${currentName}" required>
    `);
    
    row.find('td:eq(2)').html(`
        <input type="text" class="form-control" id="edit-dept-office-${id}" value="${currentOffice}">
    `);
    
    row.find('td:eq(3)').html(`
        <textarea class="form-control" id="edit-dept-address-${id}" rows="2">${currentAddress}</textarea>
    `);
    
    // Replace action buttons
    var actionCell = row.find('td:eq(4)');
    actionCell.html(`
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-success" onclick="updateDepartment(${id})" title="Lưu">
                <i class="fas fa-save"></i>
            </button>
            <button type="button" class="btn btn-sm btn-secondary" onclick="cancelEditDepartment(${id}, '${currentName}', '${currentOffice}', '${currentAddress}')" title="Hủy">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `);
    
    // Focus on name input
    $(`#edit-dept-name-${id}`).focus().select();
    
    // Add keyboard event handlers
    $(`#edit-dept-name-${id}`).on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            updateDepartment(id);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            cancelEditDepartment(id, currentName, currentOffice, currentAddress);
        }
    });
};

window.updateDepartment = function(id) {
    
    
    var name = $(`#edit-dept-name-${id}`).val().trim();
    var office = $(`#edit-dept-office-${id}`).val().trim();
    var address = $(`#edit-dept-address-${id}`).val().trim();
    
    if (!name) {
        showError('Tên phòng ban không được để trống!');
        $(`#edit-dept-name-${id}`).focus();
        return;
    }
    
    var formData = {
        name: name,
        office: office,
        address: address
    };
    
    $.ajax({
        url: 'api/departments.php?id=' + id,
        method: 'PUT',
        dataType: 'json',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.message);
                
                // Cập nhật row trực tiếp thay vì reload trang
                var row = $(`#departments-tbody`).find(`button[onclick*="updateDepartment(${id}"]`).closest('tr');
                row.removeClass('editing-row');
                
                // Restore row với dữ liệu mới
                row.find('td:eq(1)').html(`
                    <div class="fw-semibold">
                        ${name}
                    </div>
                `);
                
                row.find('td:eq(2)').text(office || 'N/A');
                
                row.find('td:eq(3)').html(`
                    <div class="text-truncate" style="max-width: 200px;" title="${address || ''}">
                        ${address || 'N/A'}
                    </div>
                `);
                
                // Restore action buttons
                row.find('td:eq(4)').html(`
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                onclick="editDepartment(${id})"
                                title="Chỉnh sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="deleteDepartment(${id}, '${name}')"
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
            var message = 'Có lỗi xảy ra khi cập nhật phòng ban!';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            showError(message);
        }
    });
};

window.cancelEditDepartment = function(id, originalName, originalOffice, originalAddress) {
    
    
    var row = $(`#departments-tbody`).find(`button[onclick*="updateDepartment(${id}"]`).closest('tr');
    
    if (row.length === 0) {
        
        return;
    }
    
    row.removeClass('editing-row');
    
    // Restore original values
    row.find('td:eq(1)').html(`
        <div class="fw-semibold">
            ${originalName}
        </div>
    `);
    
    row.find('td:eq(2)').text(originalOffice || 'N/A');
    
    row.find('td:eq(3)').html(`
        <div class="text-truncate" style="max-width: 200px;" title="${originalAddress || ''}">
            ${originalAddress || 'N/A'}
        </div>
    `);
    
    // Restore action buttons
    row.find('td:eq(4)').html(`
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-outline-primary" 
                    onclick="editDepartment(${id})"
                    title="Chỉnh sửa">
                <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" 
                    onclick="deleteDepartment(${id}, '${originalName}')"
                    title="Xóa">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `);
};

window.deleteDepartment = function(id, name) {
    
    
    if (!confirm(`Bạn có chắc chắn muốn xóa phòng ban "${name}"?`)) {
        return;
    }
    
    $.ajax({
        url: 'api/departments.php?id=' + id,
        method: 'DELETE',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.message);
                
                // Xóa row khỏi table thay vì reload trang
                var row = $(`#departments-tbody`).find(`button[onclick*="deleteDepartment(${id}"]`).closest('tr');
                row.fadeOut(300, function() {
                    row.remove();
                    updateDepartmentRowNumbers();
                });
                
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            var message = 'Có lỗi xảy ra khi xóa phòng ban!';
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
function updateDepartmentRowNumbers() {
    $('#departments-tbody tr').each(function(index) {
        $(this).find('td:first').text(index + 1);
    });
}

// Debug: Log that functions are loaded



 

// Initialize when document ready
$(document).ready(function() {
    
}); 