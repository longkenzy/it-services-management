/**
 * IT CRM - Case Types Management JavaScript
 * File: assets/js/case-types.js
 * Mục đích: Xử lý inline editing cho các loại case
 */

// Global case type functions
window.addNewCaseTypeRow = function(type) {
    
    
    var tbody = $('#' + type + '-case-types-tbody');
    
    // Check if there's already a new row being added
    if (tbody.find('.new-case-type-row').length > 0) {
        showWarning('Vui lòng hoàn tất việc thêm loại case hiện tại trước khi thêm mới!');
        return;
    }
    
    // Hide "no data" message if it exists
    var noDataRow = tbody.find('tr td[colspan="3"]').parent();
    var hasNoDataRow = noDataRow.length > 0;
    noDataRow.hide();
    
    // Calculate next row number
    var actualRows = tbody.find('tr:visible').length;
    var nextRowNumber = hasNoDataRow ? 1 : actualRows + 1;
    
    // Create new row
    var newRow = `
        <tr class="new-case-type-row">
            <td>${nextRowNumber}</td>
            <td>
                <input type="text" class="form-control" id="new-case-type-name-${type}" placeholder="Tên loại case" required>
            </td>
            <td>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-success" onclick="saveCaseType('${type}')" title="Lưu">
                        <i class="fas fa-save"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="cancelAddCaseType('${type}')" title="Hủy">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </td>
        </tr>
    `;
    
    // Add new row to table
    tbody.append(newRow);
    
    // Focus on name input
    $('#new-case-type-name-' + type).focus();
    
    // Add keyboard event handlers
    $('#new-case-type-name-' + type).on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            saveCaseType(type);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            cancelAddCaseType(type);
        }
    });
};

window.saveCaseType = function(type) {
    
    
    var name = $('#new-case-type-name-' + type).val().trim();
    
    if (!name) {
        showError('Tên loại case không được để trống!');
        $('#new-case-type-name-' + type).focus();
        return;
    }
    
    var formData = {
        name: name,
        type: type
    };
    
    $.ajax({
        url: 'api/case_types.php?type=' + type,
        method: 'POST',
        dataType: 'json',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.message);
                
                // Thay thế row mới bằng row thực thay vì reload trang
                var tbody = $('#' + type + '-case-types-tbody');
                var newRow = tbody.find('.new-case-type-row');
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
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="editCaseType(${response.data ? response.data.id : 'null'}, '${type}')"
                                        title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteCaseType(${response.data ? response.data.id : 'null'}, '${name}', '${type}')"
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
            var message = 'Có lỗi xảy ra khi thêm loại case!';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            showError(message);
        }
    });
};

window.cancelAddCaseType = function(type) {
    $('#' + type + '-case-types-tbody').find('.new-case-type-row').remove();
};

window.editCaseType = function(id, type) {
    
    
    // Find the row
    var row = $('#' + type + '-case-types-tbody').find(`button[onclick*="editCaseType(${id}"]`).closest('tr');
    
    if (row.length === 0) {
        
        return;
    }
    
    // Get current values
    var nameCell = row.find('td:eq(1)');
    var currentName = nameCell.find('.fw-semibold').text().trim();
    
    // Add editing class
    row.addClass('editing-row');
    
    // Replace cells with inputs
    nameCell.html(`
        <input type="text" class="form-control" id="edit-case-type-name-${id}" value="${currentName}" required>
    `);
    
    // Replace action buttons
    var actionCell = row.find('td:eq(2)');
    actionCell.html(`
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-success" onclick="updateCaseType(${id}, '${type}')" title="Lưu">
                <i class="fas fa-save"></i>
            </button>
            <button type="button" class="btn btn-sm btn-secondary" onclick="cancelEditCaseType(${id}, '${currentName}', '${type}')" title="Hủy">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `);
    
    // Focus on name input
    $(`#edit-case-type-name-${id}`).focus().select();
    
    // Add keyboard event handlers
    $(`#edit-case-type-name-${id}`).on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            updateCaseType(id, type);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            cancelEditCaseType(id, currentName, type);
        }
    });
};

window.updateCaseType = function(id, type) {
    
    
    var name = $(`#edit-case-type-name-${id}`).val().trim();
    
    if (!name) {
        showError('Tên loại case không được để trống!');
        $(`#edit-case-type-name-${id}`).focus();
        return;
    }
    
    var formData = {
        name: name,
        type: type
    };
    
    $.ajax({
        url: 'api/case_types.php?id=' + id + '&type=' + type,
        method: 'PUT',
        dataType: 'json',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.message);
                
                // Cập nhật row trực tiếp thay vì reload trang
                var row = $('#' + type + '-case-types-tbody').find(`button[onclick*="updateCaseType(${id}"]`).closest('tr');
                row.removeClass('editing-row');
                
                // Restore row với dữ liệu mới
                row.find('td:eq(1)').html(`
                    <div class="fw-semibold">
                        ${name}
                    </div>
                `);
                
                // Restore action buttons
                row.find('td:eq(2)').html(`
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                onclick="editCaseType(${id}, '${type}')"
                                title="Chỉnh sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="deleteCaseType(${id}, '${name}', '${type}')"
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
            var message = 'Có lỗi xảy ra khi cập nhật loại case!';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            showError(message);
        }
    });
};

window.cancelEditCaseType = function(id, originalName, type) {
    
    
    var row = $('#' + type + '-case-types-tbody').find(`button[onclick*="updateCaseType(${id}"]`).closest('tr');
    
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
    
    // Restore action buttons
    row.find('td:eq(2)').html(`
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-outline-primary" 
                    onclick="editCaseType(${id}, '${type}')"
                    title="Chỉnh sửa">
                <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" 
                    onclick="deleteCaseType(${id}, '${originalName}', '${type}')"
                    title="Xóa">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `);
};

window.deleteCaseType = function(id, name, type) {
    
    
    if (!confirm(`Bạn có chắc chắn muốn xóa loại case "${name}"?`)) {
        return;
    }
    
    $.ajax({
        url: 'api/case_types.php?id=' + id + '&type=' + type,
        method: 'DELETE',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.message);
                
                // Xóa row khỏi table thay vì reload trang
                var row = $('#' + type + '-case-types-tbody').find(`button[onclick*="deleteCaseType(${id}"]`).closest('tr');
                row.fadeOut(300, function() {
                    row.remove();
                    updateCaseTypeRowNumbers(type);
                });
                
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            var message = 'Có lỗi xảy ra khi xóa loại case!';
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
function updateCaseTypeRowNumbers(type) {
    $('#' + type + '-case-types-tbody tr').each(function(index) {
        $(this).find('td:first').text(index + 1);
    });
}

// Initialize when document ready
$(document).ready(function() {
    
}); 