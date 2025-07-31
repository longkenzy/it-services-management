<?php
/**
 * IT Services Management - Staff XLSX Export API
 * File: api/export_staff_xlsx.php
 * Mục đích: Xuất dữ liệu nhân sự ra file Excel có thể mở được
 */

// Include session management
require_once '../includes/session.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Include database configuration
require_once '../config/db.php';

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="staff_list_' . date('Y-m-d_H-i-s') . '.xls"');
header('Cache-Control: max-age=0');

// Get filter parameters
$search = $_GET['search'] ?? '';
$department = $_GET['department'] ?? '';
$position = $_GET['position'] ?? '';
$gender = $_GET['gender'] ?? '';
$export_type = $_GET['export_type'] ?? 'all'; // 'all' or 'selected'
$selected_ids = $_GET['selected_ids'] ?? '';

try {
    $pdo = getConnection();
    
    // Build query
    $sql = "SELECT 
                staff_code, fullname, birth_date, gender, position, department, 
                phone_main, email_work, job_type, start_date, seniority, resigned,
                address_perm, address_temp
            FROM staffs 
            WHERE 1=1";
    
    $params = [];
    
    // Add search filter
    if (!empty($search)) {
        $sql .= " AND (staff_code LIKE ? OR fullname LIKE ? OR email_work LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    // Add department filter
    if (!empty($department)) {
        $sql .= " AND department = ?";
        $params[] = $department;
    }
    
    // Add position filter
    if (!empty($position)) {
        $sql .= " AND position = ?";
        $params[] = $position;
    }
    
    // Add gender filter
    if (!empty($gender)) {
        $sql .= " AND gender = ?";
        $params[] = $gender;
    }
    
    // Add selected IDs filter
    if ($export_type === 'selected' && !empty($selected_ids)) {
        $ids = explode(',', $selected_ids);
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql .= " AND id IN ($placeholders)";
        $params = array_merge($params, $ids);
    }
    
    // Add sorting
    $sql .= " ORDER BY start_date ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $staffs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Start Excel output with proper Excel headers
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<style>';
    echo 'table { border-collapse: collapse; width: 100%; }';
    echo 'th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }';
    echo 'th { background-color: #f2f2f2; font-weight: bold; }';
    echo '.status-active { background-color: #d4edda; color: #155724; }';
    echo '.status-resigned { background-color: #f8d7da; color: #721c24; }';
    echo '.text-cell { mso-number-format: "@"; }';
    echo '.phone-cell { mso-number-format: "\\@"; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Excel table
    echo '<table>';
    
    // Header row
    echo '<tr>';
    echo '<th>Mã số</th>';
    echo '<th>Họ và tên</th>';
    echo '<th>Năm sinh</th>';
    echo '<th>Giới tính</th>';
    echo '<th>Chức vụ</th>';
    echo '<th>Phòng ban</th>';
    echo '<th>Số điện thoại</th>';
    echo '<th>Email công việc</th>';
    echo '<th>Loại hợp đồng</th>';
    echo '<th>Ngày vào làm</th>';
    echo '<th>Thâm niên</th>';
    echo '<th>Địa chỉ thường trú</th>';
    echo '<th>Địa chỉ tạm trú</th>';
    echo '<th>Trạng thái</th>';
    echo '</tr>';
    
    // Data rows
    foreach ($staffs as $staff) {
        echo '<tr>';
        echo '<td class="text-cell">' . htmlspecialchars($staff['staff_code']) . '</td>';
        echo '<td>' . htmlspecialchars($staff['fullname']) . '</td>';
        echo '<td>' . ($staff['birth_date'] ? date('Y', strtotime($staff['birth_date'])) : '') . '</td>';
        echo '<td>' . htmlspecialchars($staff['gender']) . '</td>';
        echo '<td>' . htmlspecialchars($staff['position']) . '</td>';
        echo '<td>' . htmlspecialchars($staff['department']) . '</td>';
        echo '<td class="phone-cell">' . htmlspecialchars($staff['phone_main']) . '</td>';
        echo '<td>' . htmlspecialchars($staff['email_work']) . '</td>';
        echo '<td>' . htmlspecialchars($staff['job_type']) . '</td>';
        echo '<td>' . ($staff['start_date'] ? date('d/m/Y', strtotime($staff['start_date'])) : '') . '</td>';
        echo '<td class="text-cell">' . htmlspecialchars($staff['seniority']) . ' tháng</td>';
        echo '<td>' . htmlspecialchars($staff['address_perm']) . '</td>';
        echo '<td>' . htmlspecialchars($staff['address_temp']) . '</td>';
        
        // Status with color
        $statusClass = $staff['resigned'] == 1 ? 'status-resigned' : 'status-active';
        $statusText = $staff['resigned'] == 1 ? 'Đã nghỉ' : 'Hoạt động';
        echo '<td class="' . $statusClass . '">' . $statusText . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body>';
    echo '</html>';
    
} catch (PDOException $e) {
    // Log error
    error_log("Excel export error: " . $e->getMessage());
    
    // Return error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi xuất dữ liệu: ' . $e->getMessage()
    ]);
}
?> 