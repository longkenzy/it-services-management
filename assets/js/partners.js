/**
 * IT CRM - Partners Management JavaScript
 * File: assets/js/partners.js
 * Mục đích: Xử lý inline editing cho công ty đối tác
 */

// Đảm bảo jQuery đã load
if (typeof jQuery === 'undefined') {
    throw new Error('Partners JavaScript requires jQuery');
}

// Định nghĩa functions trong global scope
window.addNewPartnerRow = function() {
    var tbody = $('#partners-tbody');
    
    if (tbody.length === 0) {
        
        return;
    }
    
    // Đếm số row thật sự chứa dữ liệu (có button actions), không tính row thông báo trống
    var dataRows = tbody.find('tr').filter(function() {
        return $(this).find('button').length > 0; // Chỉ đếm row có button
    });
    var newRowNumber = dataRows.length + 1;
    
    // Check if there's already a new row being added
    if (tbody.find('.new-partner-row').length > 0) {
        showWarning('Vui lòng hoàn tất việc thêm công ty đối tác hiện tại trước khi thêm mới!');
        return;
    }
    
    // Hide "no data" message if it exists
    tbody.find('tr td[colspan="8"]').parent().hide();
    
    var newRow = `
        <tr class="new-partner-row">
            <td>${newRowNumber}</td>
            <td>
                <input type="text" class="form-control" id="new-partner-name" placeholder="Tên công ty" required>
            </td>
            <td>
                <input type="text" class="form-control" id="new-partner-short-name" placeholder="Tên viết tắt">
            </td>
            <td>
                <textarea class="form-control" id="new-partner-address" placeholder="Địa chỉ" rows="2"></textarea>
            </td>
            <td>
                <input type="text" class="form-control" id="new-partner-contact-person" placeholder="Người liên hệ">
            </td>
            <td>
                <input type="text" class="form-control" id="new-partner-contact-phone" placeholder="SĐT liên hệ">
            </td>
            <td>
                <select class="form-select" id="new-partner-status">
                    <option value="active">Hoạt động</option>
                    <option value="inactive">Ngừng hoạt động</option>
                </select>
            </td>
            <td>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-success" onclick="savePartner()" title="Lưu">
                        <i class="fas fa-save"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="cancelAddPartner()" title="Hủy">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </td>
        </tr>
    `;
    
    tbody.append(newRow);
    
    // Focus on the name input
    $('#new-partner-name').focus();
    
    // Add keyboard event handlers
    $('#new-partner-name, #new-partner-short-name, #new-partner-address, #new-partner-contact-person, #new-partner-contact-phone').on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            savePartner();
        } else if (e.key === 'Escape') {
            e.preventDefault();
            cancelAddPartner();
        }
    });
};

window.savePartner = function() {
    var name = $('#new-partner-name').val().trim();
    var short_name = $('#new-partner-short-name').val().trim();
    var address = $('#new-partner-address').val().trim();
    var contact_person = $('#new-partner-contact-person').val().trim();
    var contact_phone = $('#new-partner-contact-phone').val().trim();
    var status = $('#new-partner-status').val();
    
    if (!name) {
        showError('Tên công ty không được để trống!');
        $('#new-partner-name').focus();
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
        url: 'api/partner_companies.php',
        method: 'POST',
        dataType: 'json',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.message);
                
                // Reload trang để sắp xếp lại theo alphabet
                setTimeout(function() {
                    location.reload();
                }, 1000); // Delay 1s để user thấy thông báo success
                
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            var message = 'Có lỗi xảy ra khi thêm công ty đối tác!';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            showError(message);
        }
    });
};

window.cancelAddPartner = function() {
    $('#partners-tbody').find('.new-partner-row').remove();
};

window.editPartner = function(id) {
    // Find the row
    var row = $('#partners-tbody').find(`button[onclick*="editPartner(${id}"]`).closest('tr');
    
    if (row.length === 0) {
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
    
    // Replace cells with inputs
    nameCell.html(`
        <input type="text" class="form-control" id="edit-partner-name-${id}" value="${currentName}" required>
    `);
    
    row.find('td:eq(2)').html(`
        <input type="text" class="form-control" id="edit-partner-short-name-${id}" value="${currentShortName}">
    `);
    
    row.find('td:eq(3)').html(`
        <textarea class="form-control" id="edit-partner-address-${id}" rows="2">${currentAddress}</textarea>
    `);
    
    row.find('td:eq(4)').html(`
        <input type="text" class="form-control" id="edit-partner-contact-person-${id}" value="${currentContactPerson}">
    `);
    
    row.find('td:eq(5)').html(`
        <input type="text" class="form-control" id="edit-partner-contact-phone-${id}" value="${currentContactPhone}">
    `);
    
    row.find('td:eq(6)').html(`
        <select class="form-select" id="edit-partner-status-${id}">
            <option value="active" ${currentStatus === 'active' ? 'selected' : ''}>Hoạt động</option>
            <option value="inactive" ${currentStatus === 'inactive' ? 'selected' : ''}>Ngừng hoạt động</option>
        </select>
    `);
    
    // Replace action buttons
    var actionCell = row.find('td:eq(7)');
    actionCell.html(`
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-success" onclick="updatePartner(${id})" title="Lưu">
                <i class="fas fa-save"></i>
            </button>
            <button type="button" class="btn btn-sm btn-secondary" onclick="cancelEditPartner(${id}, '${currentName}', '${currentShortName}', '${currentAddress}', '${currentContactPerson}', '${currentContactPhone}', '${currentStatus}')" title="Hủy">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `);
    
    // Focus on name input
    $(`#edit-partner-name-${id}`).focus().select();
    
    // Add keyboard event handlers
    $(`#edit-partner-name-${id}`).on('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            updatePartner(id);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            cancelEditPartner(id, currentName, currentShortName, currentAddress, currentContactPerson, currentContactPhone, currentStatus);
        }
    });
};

window.updatePartner = function(id) {
    var name = $(`#edit-partner-name-${id}`).val().trim();
    var short_name = $(`#edit-partner-short-name-${id}`).val().trim();
    var address = $(`#edit-partner-address-${id}`).val().trim();
    var contact_person = $(`#edit-partner-contact-person-${id}`).val().trim();
    var contact_phone = $(`#edit-partner-contact-phone-${id}`).val().trim();
    var status = $(`#edit-partner-status-${id}`).val();
    
    if (!name) {
        showError('Tên công ty không được để trống!');
        $(`#edit-partner-name-${id}`).focus();
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
        url: 'api/partner_companies.php?id=' + id,
        method: 'PUT',
        dataType: 'json',
        data: JSON.stringify(formData),
        contentType: 'application/json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.message);
                
                // Cập nhật row trực tiếp thay vì reload trang
                var row = $(`#partners-tbody`).find(`button[onclick*="updatePartner(${id}"]`).closest('tr');
                row.removeClass('editing-row');
                
                // Restore row với dữ liệu mới
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
                                onclick="editPartner(${id})"
                                title="Chỉnh sửa">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="deletePartner(${id}, '${name}')"
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
            var message = 'Có lỗi xảy ra khi cập nhật công ty đối tác!';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            showError(message);
        }
    });
};

window.cancelEditPartner = function(id, originalName, originalShortName, originalAddress, originalContactPerson, originalContactPhone, originalStatus) {
    var row = $(`#partners-tbody`).find(`button[onclick*="updatePartner(${id}"]`).closest('tr');
    
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
                    onclick="editPartner(${id})"
                    title="Chỉnh sửa">
                <i class="fas fa-edit"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" 
                    onclick="deletePartner(${id}, '${originalName}')"
                    title="Xóa">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `);
};

window.deletePartner = function(id, name) {
    if (!confirm(`Bạn có chắc chắn muốn xóa công ty đối tác "${name}"?`)) {
        return;
    }
    
    $.ajax({
        url: 'api/partner_companies.php?id=' + id,
        method: 'DELETE',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccess(response.message);
                
                // Xóa row khỏi table thay vì reload trang
                var row = $(`#partners-tbody`).find(`button[onclick*="deletePartner(${id}"]`).closest('tr');
                row.fadeOut(300, function() {
                    row.remove();
                    updatePartnerRowNumbers();
                });
                
            } else {
                showError(response.message);
            }
        },
        error: function(xhr) {
            var message = 'Có lỗi xảy ra khi xóa công ty đối tác!';
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
function updatePartnerRowNumbers() {
    $('#partners-tbody tr').each(function(index) {
        $(this).find('td:first').text(index + 1);
    });
}

// Helper function to validate email
function isValidEmail(email) {
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Initialize when document ready
$(document).ready(function() {
    // Partners inline editing initialized
}); 