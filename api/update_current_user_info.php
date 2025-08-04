<?php
/**
 * Script cập nhật thông tin user hiện tại
 */

header('Content-Type: application/json');

require_once '../includes/session.php';
require_once '../config/db.php';

try {
    if (!isLoggedIn()) {
        echo json_encode([
            'success' => false,
            'message' => 'User chưa đăng nhập'
        ]);
        exit;
    }
    
    $current_user = getCurrentUser();
    
    // Kiểm tra xem user đã có trong bảng staffs chưa
    $stmt = $pdo->prepare("SELECT id FROM staffs WHERE id = ?");
    $stmt->execute([$current_user['id']]);
    $exists = $stmt->fetch();
    
    if ($exists) {
        // Cập nhật thông tin nếu thiếu
        $update_fields = [];
        $params = [];
        
        if (empty($current_user['position'])) {
            $update_fields[] = "position = ?";
            $params[] = 'Nhân viên';
        }
        
        if (empty($current_user['department'])) {
            $update_fields[] = "department = ?";
            $params[] = 'IT';
        }
        
        if (empty($current_user['office'])) {
            $update_fields[] = "office = ?";
            $params[] = 'Hà Nội';
        }
        
        if (!empty($update_fields)) {
            $params[] = $current_user['id'];
            $sql = "UPDATE staffs SET " . implode(', ', $update_fields) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật thông tin user',
                'updated_fields' => $update_fields
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Thông tin user đã đầy đủ'
            ]);
        }
    } else {
        // Tạo mới record trong bảng staffs
        $sql = "INSERT INTO staffs (id, staff_code, fullname, position, department, office, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $current_user['id'],
            'NV' . str_pad($current_user['id'], 3, '0', STR_PAD_LEFT),
            $current_user['fullname'],
            'Nhân viên',
            'IT',
            'Hà Nội',
            'Đang làm việc'
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã tạo thông tin staff cho user'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?> 