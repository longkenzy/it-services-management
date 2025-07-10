/**
 * EU Companies Management JavaScript
 * File: assets/js/eu-companies.js
 * Mục đích: Quản lý CRUD operations cho công ty EU
 */

// Global variables
let isAddingNewEUCompany = false;

// Initialize when document is ready
$(document).ready(function() {
    
});

// Add new EU company row
window.addNewEUCompanyRow = function() {
    
    
    if (isAddingNewEUCompany) {
        showError('Vui lòng hoàn tất thao tác thêm công ty EU hiện tại trước khi tạo mới!');
        return;
    }
    
    const tbody = $('#eu-companies-tbody');
    
    // Hide "no data" message if it exists
    tbody.find('tr td[colspan="8"]').parent().hide();
    
    var newRow = `
        <tr class="new-eu-company-row">
            <td class="text-center"><i class="fas fa-plus text-primary"></i></td>
            <td>
                <input type="text" class="form-control" id="new-eu-company-name" placeholder="Tên công ty EU" required>
            </td>
            <td>
                <input type="text" class="form-control" id="new-eu-company-short-name" placeholder="Tên viết tắt">
            </td>
            <td>
                <textarea class="form-control" id="new-eu-company-address" placeholder="Địa chỉ" rows="2"></textarea>
            </td>
            <td>
                <input type="text" class="form-control" id="new-eu-company-contact-person" placeholder="Người liên hệ">
            </td>
            <td>
                <input type="text" class="form-control" id="new-eu-company-contact-phone" placeholder="SĐT liên hệ">
            </td>
            <td>
                <select class="form-select" id="new-eu-company-status">
                    <option value="active">Hoạt động</option>
                    <option value="inactive">Ngừng hoạt động</option>
                </select>
            </td>
            <td>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-success" onclick="saveEUCompany()" title="Lưu">
                        <i class="fas fa-save"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="cancelAddEUCompany()" title="Hủy">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </td>
        </tr>
    `;
    
    tbody.prepend(newRow);
    isAddingNewEUCompany = true;
    
    // Focus on name input
    $('#new-eu-company-name').focus();
    
    // Add enter key handler for quick save
    $('#new-eu-company-name, #new-eu-company-short-name, #new-eu-company-address, #new-eu-company-contact-person, #new-eu-company-contact-phone').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            saveEUCompany();
        } else if (e.key === 'Escape') {
            e.preventDefault();
            cancelAddEUCompany();
        }
    });
};

// Save new EU company
window.saveEUCompany = function() {
    
    
    var name = $('#new-eu-company-name').val().trim();
    var short_name = $('#new-eu-company-short-name').val().trim();
    var address = $('#new-eu-company-address').val().trim();
    var contact_person = $('#new-eu-company-contact-person').val().trim();
    var contact_phone = $('#new-eu-company-contact-phone').val().trim();
    var status = $('#new-eu-company-status').val();
    
    if (!name) {
        showError('Tên công ty EU không được để trống!');
        $('#new-eu-company-name').focus();
        return;
    }
    
    var formData = {
        name: name,
        short_name: short_name,
        address: address,
        contact_person: contact_person,
        contact_phone: contact_phone,
        status: status
    };
    
    $.ajax({
        url: 'api/eu_companies.php',
        method: 'POST',
        dataType: 'json',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.message);
                
                // Get the new row element
                var newRow = $('.new-eu-company-row');
                
                // Create the actual row HTML
                var realRow = `
                    <tr>
                        <td>${$('#eu-companies-tbody tr').length}</td>
                        <td>
                            <div class="fw-semibold">
                                ${name}
                            </div>
                        </td>
                        <td>${short_name || 'N/A'}</td>
                        <td>
                            <div style="word-wrap: break-word; white-space: normal; line-height: 1.4;">
                                ${address || 'N/A'}
                            </div>
                        </td>
                        <td>
                            <div class="text-truncate" style="max-width: 150px;" title="${contact_person || ''}">
                                ${contact_person || 'N/A'}
                            </div>
                        </td>
                        <td>
                            <div class="text-truncate" style="max-width: 150px;" title="${contact_phone || ''}">
                                ${contact_phone || 'N/A'}
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-${status === 'active' ? 'success' : 'secondary'}">
                                ${status === 'active' ? 'Hoạt động' : 'Ngừng hoạt động'}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="editEUCompany(${response.data ? response.data.id : 'null'})"
                                        title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteEUCompany(${response.data ? response.data.id : 'null'}, '${name}')"
                                        title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                
                // Replace the new row with the actual row
                newRow.replaceWith(realRow);
                
                // Reset the flag
                isAddingNewEUCompany = false;
                
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            var message = 'Có lỗi xảy ra khi thêm công ty EU!';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            showError(message);
        }
    });
};

// Cancel adding new EU company
window.cancelAddEUCompany = function() {
    $('#eu-companies-tbody').find('.new-eu-company-row').remove();
    isAddingNewEUCompany = false;
};

// Edit EU company
window.editEUCompany = function(id) {
    
    
    if (isAddingNewEUCompany) {
        showError('Vui lòng hoàn tất thao tác thêm công ty EU hiện tại trước khi chỉnh sửa!');
        return;
    }
    
    var row = $(`#eu-companies-tbody`).find(`button[onclick*="editEUCompany(${id}"]`).closest('tr');
    
    if (row.length === 0) {
        showError('Không tìm thấy công ty EU để chỉnh sửa!');
        return;
    }
    
    // Get current values
    var nameCell = row.find('td:eq(1)');
    var currentName = nameCell.find('.fw-semibold').text().trim();
    var currentShortName = row.find('td:eq(2)').text().trim();
    var currentAddress = row.find('td:eq(3)').find('div').text().trim();
    var currentContactPerson = row.find('td:eq(4)').find('div').text().trim();
    var currentContactPhone = row.find('td:eq(5)').find('div').text().trim();
    var currentStatus = row.find('td:eq(6)').find('.badge').hasClass('bg-success') ? 'active' : 'inactive';
    
    // Handle N/A values
    if (currentShortName === 'N/A') currentShortName = '';
    if (currentAddress === 'N/A') currentAddress = '';
    if (currentContactPerson === 'N/A') currentContactPerson = '';
    if (currentContactPhone === 'N/A') currentContactPhone = '';
    
    // Add editing class
    row.addClass('editing-row');
    
    // Replace cells with input fields
    row.find('td:eq(1)').html(`
        <input type="text" class="form-control" id="edit-eu-company-name-${id}" value="${currentName}" required>
    `);
    
    row.find('td:eq(2)').html(`
        <input type="text" class="form-control" id="edit-eu-company-short-name-${id}" value="${currentShortName}">
    `);
    
    row.find('td:eq(3)').html(`
        <textarea class="form-control" id="edit-eu-company-address-${id}" rows="2">${currentAddress}</textarea>
    `);
    
    row.find('td:eq(4)').html(`
        <input type="text" class="form-control" id="edit-eu-company-contact-person-${id}" value="${currentContactPerson}">
    `);
    
    row.find('td:eq(5)').html(`
        <input type="text" class="form-control" id="edit-eu-company-contact-phone-${id}" value="${currentContactPhone}">
    `);
    
    row.find('td:eq(6)').html(`
        <select class="form-select" id="edit-eu-company-status-${id}">
            <option value="active" ${currentStatus === 'active' ? 'selected' : ''}>Hoạt động</option>
            <option value="inactive" ${currentStatus === 'inactive' ? 'selected' : ''}>Ngừng hoạt động</option>
        </select>
    `);
    
    // Replace action buttons
    var actionCell = row.find('td:eq(7)');
    actionCell.html(`
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-success" onclick="updateEUCompany(${id})" title="Lưu">
                <i class="fas fa-save"></i>
            </button>
            <button type="button" class="btn btn-sm btn-secondary" onclick="cancelEditEUCompany(${id}, '${currentName}', '${currentShortName}', '${currentAddress}', '${currentContactPerson}', '${currentContactPhone}', '${currentStatus}')" title="Hủy">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `);
    
    // Focus on name input
    $(`#edit-eu-company-name-${id}`).focus().select();
    
    // Add keyboard shortcuts
    $(`#edit-eu-company-name-${id}, #edit-eu-company-short-name-${id}, #edit-eu-company-address-${id}, #edit-eu-company-contact-person-${id}, #edit-eu-company-contact-phone-${id}`).on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            updateEUCompany(id);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            cancelEditEUCompany(id, currentName, currentShortName, currentAddress, currentContactPerson, currentContactPhone, currentStatus);
        }
    });
};

// Update EU company
window.updateEUCompany = function(id) {
    
    
    var name = $(`#edit-eu-company-name-${id}`).val().trim();
    var short_name = $(`#edit-eu-company-short-name-${id}`).val().trim();
    var address = $(`#edit-eu-company-address-${id}`).val().trim();
    var contact_person = $(`#edit-eu-company-contact-person-${id}`).val().trim();
    var contact_phone = $(`#edit-eu-company-contact-phone-${id}`).val().trim();
    var status = $(`#edit-eu-company-status-${id}`).val();
    
    if (!name) {
        showError('Tên công ty EU không được để trống!');
        $(`#edit-eu-company-name-${id}`).focus();
        return;
    }
    
    var formData = {
        name: name,
        short_name: short_name,
        address: address,
        contact_person: contact_person,
        contact_phone: contact_phone,
        status: status
    };
    
    $.ajax({
        url: 'api/eu_companies.php?id=' + id,
        method: 'PUT',
        dataType: 'json',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.message);
                
                // Update row directly instead of page reload
                var row = $(`#eu-companies-tbody`).find(`button[onclick*="updateEUCompany(${id}"]`).closest('tr');
                row.removeClass('editing-row');
                
                // Restore row with new data
                row.find('td:eq(1)').html(`
                    <div class="fw-semibold">
                        ${name}
                    </div>
                `);
                
                row.find('td:eq(2)').text(short_name || 'N/A');
                
                row.find('td:eq(3)').html(`
                    <div style="word-wrap: break-word; white-space: normal; line-height: 1.4;">
                        ${address || 'N/A'}
                    </div>
                `);
                
                row.find('td:eq(4)').html(`
                    <div class="text-truncate" style="max-width: 150px;" title="${contact_person || ''}">
                        ${contact_person || 'N/A'}
                    </div>
                `);
                
                row.find('td:eq(5)').html(`
                    <div class="text-truncate" style="max-width: 150px;" title="${contact_phone || ''}">
                        ${contact_phone || 'N/A'}
                    </div>
                `);
                
                row.find('td:eq(6)').html(`
                    <span class="badge bg-${status === 'active' ? 'success' : 'secondary'}">
                        ${status === 'active' ? 'Hoạt động' : 'Ngừng hoạt động'}
                    </span>
                `);
                
                // Restore action buttons
                row.find('td:eq(7)').html(`
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                onclick="editEUCompany(${id})"
                                title="Chỉnh sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="deleteEUCompany(${id}, '${name}')"
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
            var message = 'Có lỗi xảy ra khi cập nhật công ty EU!';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            showError(message);
        }
    });
};

// Cancel edit EU company
window.cancelEditEUCompany = function(id, originalName, originalShortName, originalAddress, originalContactPerson, originalContactPhone, originalStatus) {
    
    
    var row = $(`#eu-companies-tbody`).find(`button[onclick*="updateEUCompany(${id}"]`).closest('tr');
    
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
    
    row.find('td:eq(2)').text(originalShortName || 'N/A');
    
    row.find('td:eq(3)').html(`
        <div style="word-wrap: break-word; white-space: normal; line-height: 1.4;">
            ${originalAddress || 'N/A'}
        </div>
    `);
    
    row.find('td:eq(4)').html(`
        <div class="text-truncate" style="max-width: 150px;" title="${originalContactPerson || ''}">
            ${originalContactPerson || 'N/A'}
        </div>
    `);
    
    row.find('td:eq(5)').html(`
        <div class="text-truncate" style="max-width: 150px;" title="${originalContactPhone || ''}">
            ${originalContactPhone || 'N/A'}
        </div>
    `);
    
    row.find('td:eq(6)').html(`
        <span class="badge bg-${originalStatus === 'active' ? 'success' : 'secondary'}">
            ${originalStatus === 'active' ? 'Hoạt động' : 'Ngừng hoạt động'}
        </span>
    `);
    
    // Restore action buttons
    row.find('td:eq(7)').html(`
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-outline-primary" 
                    onclick="editEUCompany(${id})"
                    title="Chỉnh sửa">
                <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" 
                    onclick="deleteEUCompany(${id}, '${originalName}')"
                    title="Xóa">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `);
};

// Delete EU company
window.deleteEUCompany = function(id, name) {
    
    
    if (!confirm(`Bạn có chắc chắn muốn xóa công ty EU "${name}"?`)) {
        return;
    }
    
    $.ajax({
        url: 'api/eu_companies.php?id=' + id,
        method: 'DELETE',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.message);
                
                // Remove the row from table
                var row = $(`#eu-companies-tbody`).find(`button[onclick*="deleteEUCompany(${id}"]`).closest('tr');
                row.fadeOut(300, function() {
                    $(this).remove();
                    
                    // Update row numbers
                    $('#eu-companies-tbody tr').each(function(index) {
                        $(this).find('td:first').text(index + 1);
                    });
                    
                    // Show "no data" message if no rows left
                    if ($('#eu-companies-tbody tr').length === 0) {
                        $('#eu-companies-tbody').html(`
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-globe fa-2x mb-2"></i>
                                    <br>
                                    Chưa có công ty EU nào
                                </td>
                            </tr>
                        `);
                    }
                });
                
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            var message = 'Có lỗi xảy ra khi xóa công ty EU!';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            showError(message);
        }
    });
}; 