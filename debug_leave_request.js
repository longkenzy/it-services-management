/**
 * Script debug để kiểm tra lỗi 500 khi tạo đơn nghỉ phép
 * Copy và paste script này vào console của browser
 */

console.log('=== DEBUG LEAVE REQUEST API ===');

// Hàm test API
async function testCreateLeaveRequest() {
    try {
        console.log('1. Bắt đầu test API...');
        
        // Tạo dữ liệu test với ngày trong tương lai
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const dayAfterTomorrow = new Date();
        dayAfterTomorrow.setDate(dayAfterTomorrow.getDate() + 2);
        
        const testData = {
            start_date: tomorrow.toISOString().split('T')[0], // Ngày mai
            start_time: '08:00',
            end_date: tomorrow.toISOString().split('T')[0], // Ngày mai
            end_time: '17:00',
            return_date: dayAfterTomorrow.toISOString().split('T')[0], // Ngày kia
            return_time: '08:00',
            leave_days: '1.0',
            leave_type: 'Nghỉ phép năm',
            reason: 'Test debug script',
            handover_to: '1'
        };
        
        console.log('2. Dữ liệu test:', testData);
        
        // Tạo FormData
        const formData = new FormData();
        for (let key in testData) {
            formData.append(key, testData[key]);
        }
        
        console.log('3. Gửi request đến API...');
        
        // Gửi request
        const response = await fetch('/api/create_leave_request.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin' // Bao gồm cookies/session
        });
        
        console.log('4. Response status:', response.status);
        console.log('5. Response headers:', Object.fromEntries(response.headers.entries()));
        
        // Đọc response text
        const responseText = await response.text();
        console.log('6. Response text:', responseText);
        
        // Thử parse JSON
        try {
            const responseJson = JSON.parse(responseText);
            console.log('7. Response JSON:', responseJson);
        } catch (e) {
            console.log('7. Không thể parse JSON:', e.message);
        }
        
        if (response.ok) {
            console.log('✅ API hoạt động bình thường');
        } else {
            console.log('❌ API trả về lỗi:', response.status, response.statusText);
        }
        
    } catch (error) {
        console.error('❌ Lỗi khi test API:', error);
        console.error('Error details:', {
            message: error.message,
            stack: error.stack
        });
    }
}

// Hàm kiểm tra session
async function checkSession() {
    try {
        console.log('=== KIỂM TRA SESSION ===');
        
        const response = await fetch('/api/check_session.php', {
            method: 'GET',
            credentials: 'same-origin'
        });
        
        const responseText = await response.text();
        console.log('Session check response:', responseText);
        
    } catch (error) {
        console.error('Lỗi kiểm tra session:', error);
    }
}

// Hàm kiểm tra database connection
async function checkDatabase() {
    try {
        console.log('=== KIỂM TRA DATABASE ===');
        
        const response = await fetch('/api/check_database.php', {
            method: 'GET',
            credentials: 'same-origin'
        });
        
        const responseText = await response.text();
        console.log('Database check response:', responseText);
        
    } catch (error) {
        console.error('Lỗi kiểm tra database:', error);
    }
}

// Hàm kiểm tra file permissions
async function checkFilePermissions() {
    try {
        console.log('=== KIỂM TRA FILE PERMISSIONS ===');
        
        const response = await fetch('/api/check_permissions.php', {
            method: 'GET',
            credentials: 'same-origin'
        });
        
        const responseText = await response.text();
        console.log('Permissions check response:', responseText);
        
    } catch (error) {
        console.error('Lỗi kiểm tra permissions:', error);
    }
}

// Hàm test với dữ liệu thực từ form
function testWithRealFormData() {
    try {
        console.log('=== TEST VỚI DỮ LIỆU THỰC TỪ FORM ===');
        
        // Lấy dữ liệu từ form hiện tại
        const form = document.getElementById('createLeaveRequestForm');
        if (!form) {
            console.error('Không tìm thấy form createLeaveRequestForm');
            return;
        }
        
        const formData = new FormData(form);
        
        // Log dữ liệu form
        console.log('Form data:');
        for (let [key, value] of formData.entries()) {
            console.log(`${key}: ${value}`);
        }
        
        // Gửi request với dữ liệu thực
        fetch('/api/create_leave_request.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', Object.fromEntries(response.headers.entries()));
            return response.text();
        })
        .then(responseText => {
            console.log('Response text:', responseText);
            try {
                const responseJson = JSON.parse(responseText);
                console.log('Response JSON:', responseJson);
            } catch (e) {
                console.log('Không thể parse JSON:', e.message);
            }
        })
        .catch(error => {
            console.error('Lỗi khi gửi request:', error);
        });
        
    } catch (error) {
        console.error('Lỗi khi test với form data:', error);
    }
}

// Hàm kiểm tra network
function checkNetwork() {
    console.log('=== KIỂM TRA NETWORK ===');
    console.log('Current URL:', window.location.href);
    console.log('API URL:', '/api/create_leave_request.php');
    console.log('Full API URL:', window.location.origin + '/api/create_leave_request.php');
    
    // Test connection
    fetch('/api/create_leave_request.php', {
        method: 'OPTIONS',
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('OPTIONS request status:', response.status);
    })
    .catch(error => {
        console.error('Lỗi kết nối:', error);
    });
}

// Hàm chạy tất cả tests
async function runAllTests() {
    console.log('🚀 BẮT ĐẦU DEBUG LEAVE REQUEST API');
    console.log('=====================================');
    
    await checkNetwork();
    await checkSession();
    await checkDatabase();
    await checkFilePermissions();
    await testCreateLeaveRequest();
    
    console.log('=====================================');
    console.log('🏁 HOÀN THÀNH DEBUG');
}

// Export các hàm để có thể gọi riêng lẻ
window.debugLeaveRequest = {
    testCreateLeaveRequest,
    checkSession,
    checkDatabase,
    checkFilePermissions,
    testWithRealFormData,
    checkNetwork,
    runAllTests
};

console.log('📋 Các hàm debug có sẵn:');
console.log('- debugLeaveRequest.runAllTests() - Chạy tất cả tests');
console.log('- debugLeaveRequest.testCreateLeaveRequest() - Test API với dữ liệu mẫu');
console.log('- debugLeaveRequest.testWithRealFormData() - Test với dữ liệu từ form');
console.log('- debugLeaveRequest.checkSession() - Kiểm tra session');
console.log('- debugLeaveRequest.checkDatabase() - Kiểm tra database');
console.log('- debugLeaveRequest.checkFilePermissions() - Kiểm tra permissions');
console.log('- debugLeaveRequest.checkNetwork() - Kiểm tra network');

// Tự động chạy test cơ bản
console.log('🔄 Tự động chạy test cơ bản...');
testCreateLeaveRequest(); 