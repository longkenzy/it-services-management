<?php
require_once 'config/db.php';

echo "=== Kiểm tra bảng staffs ===\n";
$stmt = $pdo->query('SELECT id, fullname, username FROM staffs LIMIT 5');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id'] . ", Name: " . $row['fullname'] . ", Username: " . $row['username'] . "\n";
}

echo "\n=== Thử tạo đơn nghỉ phép test với user_id thực tế ===\n";
try {
    // Lấy user_id đầu tiên
    $stmt = $pdo->query('SELECT id FROM staffs LIMIT 1');
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "Không có user nào trong bảng staffs!\n";
        exit;
    }
    
    $user_id = $user['id'];
    echo "Sử dụng user_id: " . $user_id . "\n";
    
    $sql = "INSERT INTO leave_requests (
        request_code, requester_id, requester_position, requester_department, requester_office,
        start_date, end_date, return_date, leave_days, leave_type, reason, handover_to,
        attachment, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        'TEST001',
        $user_id,
        'Test Position',
        'Test Department',
        'Test Office',
        '2025-01-01 08:00:00',
        '2025-01-02 17:00:00',
        '2025-01-03 08:00:00',
        1.0,
        'Test Leave',
        'Test Reason',
        $user_id, // handover_to cũng dùng user_id
        null,
        'Chờ phê duyệt'
    ]);
    
    if ($result) {
        $request_id = $pdo->lastInsertId();
        echo "Tạo thành công! ID: " . $request_id . "\n";
        
        // Kiểm tra record vừa tạo
        $stmt = $pdo->prepare("SELECT * FROM leave_requests WHERE id = ?");
        $stmt->execute([$request_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Record vừa tạo:\n";
        print_r($record);
        
        // Xóa record test
        $stmt = $pdo->prepare("DELETE FROM leave_requests WHERE id = ?");
        $stmt->execute([$request_id]);
        echo "Đã xóa record test\n";
    } else {
        echo "Lỗi khi tạo record test\n";
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
?> 