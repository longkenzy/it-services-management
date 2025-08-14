/**
 * Deployment Requests JavaScript
 * File: assets/js/deployment_requests.js
 * Purpose: JavaScript functionality for deployment requests page
 */

// ===== EXCEL EXPORT FUNCTIONALITY ===== //
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý click nút xuất Excel
    const exportExcelBtn = document.getElementById('exportExcelBtn');
    if (exportExcelBtn) {
        exportExcelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            exportDeploymentRequestsToExcel();
        });
    }
    
    function exportDeploymentRequestsToExcel() {
        // Hiển thị loading
        const originalText = exportExcelBtn.innerHTML;
        exportExcelBtn.disabled = true;
        exportExcelBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang xuất...';
        
        // Tạo URL export
        const exportUrl = 'api/export_deployment_requests.php';
        
        // Tạo một iframe ẩn để download file
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.src = exportUrl;
        document.body.appendChild(iframe);
        
        // Xóa iframe sau khi download
        setTimeout(function() {
            document.body.removeChild(iframe);
            exportExcelBtn.disabled = false;
            exportExcelBtn.innerHTML = originalText;
            
            // Hiển thị thông báo thành công
            if (typeof showAlert === 'function') {
                showAlert('Đã xuất file Excel thành công!', 'success');
            } else {
                alert('Đã xuất file Excel thành công!');
            }
        }, 2000);
    }
});

// ===== UTILITY FUNCTIONS ===== //
function formatDateForDisplay(dateString) {
    if (!dateString) return '';
    
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '';
    
    // Format: dd/MM/yyyy
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    
    return `${day}/${month}/${year}`;
}

function formatDateTimeForInput(dateTimeString) {
    if (!dateTimeString) return '';
    
    const date = new Date(dateTimeString);
    if (isNaN(date.getTime())) return '';
    
    // Format: YYYY-MM-DDTHH:MM
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return `${year}-${month}-${day}T${hours}:${minutes}`;
} 