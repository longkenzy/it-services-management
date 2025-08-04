<?php
/**
 * Script cập nhật bảng leave_requests với các cột còn thiếu
 */

header('Content-Type: application/json');

require_once '../config/db.php';

try {
    // Kiểm tra xem bảng có tồn tại không
    $stmt = $pdo->query("SHOW TABLES LIKE 'leave_requests'");
    if ($stmt->rowCount() == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Bảng leave_requests không tồn tại. Vui lòng tạo bảng trước.'
        ]);
        exit;
    }
    
    $updates = [];
    
    // Kiểm tra và thêm các cột còn thiếu
    $stmt = $pdo->query("DESCRIBE leave_requests");
    $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Thêm cột requester_position nếu chưa có
    if (!in_array('requester_position', $existing_columns)) {
        $pdo->exec("ALTER TABLE leave_requests ADD COLUMN requester_position varchar(100) DEFAULT NULL COMMENT 'Chức vụ người yêu cầu' AFTER requester_id");
        $updates[] = "Thêm cột requester_position";
    }
    
    // Thêm cột requester_department nếu chưa có
    if (!in_array('requester_department', $existing_columns)) {
        $pdo->exec("ALTER TABLE leave_requests ADD COLUMN requester_department varchar(100) DEFAULT NULL COMMENT 'Phòng ban người yêu cầu' AFTER requester_position");
        $updates[] = "Thêm cột requester_department";
    }
    
    // Thêm cột requester_office nếu chưa có
    if (!in_array('requester_office', $existing_columns)) {
        $pdo->exec("ALTER TABLE leave_requests ADD COLUMN requester_office varchar(100) DEFAULT NULL COMMENT 'Văn phòng người yêu cầu' AFTER requester_department");
        $updates[] = "Thêm cột requester_office";
    }
    
    // Thêm cột return_date nếu chưa có
    if (!in_array('return_date', $existing_columns)) {
        $pdo->exec("ALTER TABLE leave_requests ADD COLUMN return_date datetime NOT NULL COMMENT 'Ngày và giờ đi làm lại' AFTER end_date");
        $updates[] = "Thêm cột return_date";
    }
    
    // Thêm cột handover_to nếu chưa có
    if (!in_array('handover_to', $existing_columns)) {
        $pdo->exec("ALTER TABLE leave_requests ADD COLUMN handover_to int(11) DEFAULT NULL COMMENT 'ID người được bàn giao việc' AFTER reason");
        $updates[] = "Thêm cột handover_to";
    }
    
    // Cập nhật các trường date thành datetime
    $stmt = $pdo->query("DESCRIBE leave_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        if (in_array($column['Field'], ['start_date', 'end_date', 'return_date']) && strpos($column['Type'], 'date') !== false && strpos($column['Type'], 'datetime') === false) {
            // Tạo cột tạm thời
            $temp_column = $column['Field'] . '_temp';
            $pdo->exec("ALTER TABLE leave_requests ADD COLUMN {$temp_column} datetime NOT NULL COMMENT 'Tạm thời' AFTER {$column['Field']}");
            
            // Cập nhật dữ liệu
            $pdo->exec("UPDATE leave_requests SET {$temp_column} = CONCAT({$column['Field']}, ' 08:00:00') WHERE {$column['Field']} IS NOT NULL");
            
            // Xóa cột cũ và đổi tên cột mới
            $pdo->exec("ALTER TABLE leave_requests DROP COLUMN {$column['Field']}");
            $pdo->exec("ALTER TABLE leave_requests CHANGE {$temp_column} {$column['Field']} datetime NOT NULL COMMENT 'Ngày và giờ'");
            
            $updates[] = "Cập nhật {$column['Field']} từ DATE sang DATETIME";
        }
    }
    
    // Kiểm tra dữ liệu staffs có sẵn
    $stmt = $pdo->query("SELECT id, fullname, position, department, office FROM staffs WHERE resigned = 0 OR resigned IS NULL ORDER BY id LIMIT 5");
    $available_staffs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($available_staffs) == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Không có nhân viên nào trong bảng staffs. Vui lòng thêm nhân viên trước.'
        ]);
        exit;
    }
    
    // Thêm dữ liệu mẫu nếu bảng trống
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM leave_requests");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($count['count'] == 0) {
        // Sử dụng staff_id thực tế từ database
        $staff1 = $available_staffs[0];
        $staff2 = count($available_staffs) > 1 ? $available_staffs[1] : $staff1;
        $staff3 = count($available_staffs) > 2 ? $available_staffs[2] : $staff1;
        
        $sample_data = [
            [
                'request_code' => 'NP2508001',
                'requester_id' => $staff1['id'],
                'requester_position' => $staff1['position'] ?? 'Nhân viên',
                'requester_department' => $staff1['department'] ?? 'IT',
                'requester_office' => $staff1['office'] ?? 'Hà Nội',
                'start_date' => '2024-01-15 08:00:00',
                'end_date' => '2024-01-17 17:00:00',
                'return_date' => '2024-01-18 08:00:00',
                'leave_days' => 3.0,
                'leave_type' => 'Nghỉ phép năm',
                'reason' => 'Nghỉ phép năm để đi du lịch cùng gia đình',
                'handover_to' => $staff2['id'],
                'status' => 'Chờ phê duyệt'
            ],
            [
                'request_code' => 'NP2508002',
                'requester_id' => $staff2['id'],
                'requester_position' => $staff2['position'] ?? 'Trưởng nhóm',
                'requester_department' => $staff2['department'] ?? 'HR',
                'requester_office' => $staff2['office'] ?? 'TP.HCM',
                'start_date' => '2024-01-20 08:00:00',
                'end_date' => '2024-01-20 17:00:00',
                'return_date' => '2024-01-21 08:00:00',
                'leave_days' => 1.0,
                'leave_type' => 'Nghỉ ốm',
                'reason' => 'Bị cảm cúm, cần nghỉ để điều trị',
                'handover_to' => $staff3['id'],
                'status' => 'Đã phê duyệt'
            ]
        ];
        
        $insert_sql = "INSERT INTO leave_requests (
            request_code, requester_id, requester_position, requester_department, requester_office,
            start_date, end_date, return_date, leave_days, leave_type, reason, handover_to, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($insert_sql);
        
        foreach ($sample_data as $data) {
            $stmt->execute([
                $data['request_code'],
                $data['requester_id'],
                $data['requester_position'],
                $data['requester_department'],
                $data['requester_office'],
                $data['start_date'],
                $data['end_date'],
                $data['return_date'],
                $data['leave_days'],
                $data['leave_type'],
                $data['reason'],
                $data['handover_to'],
                $data['status']
            ]);
        }
        
        $updates[] = "Thêm dữ liệu mẫu với staff_id thực tế";
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật bảng leave_requests thành công',
        'updates' => $updates,
        'available_staffs' => count($available_staffs)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?> 