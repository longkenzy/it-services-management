<?php
/**
 * Script kiểm tra và cập nhật thông tin user cho leave management
 */

header('Content-Type: application/json');

require_once '../includes/session.php';
require_once '../config/db.php';

try {
    // Kiểm tra đăng nhập
    if (!isLoggedIn()) {
        echo json_encode([
            'success' => false,
            'message' => 'User chưa đăng nhập. Vui lòng đăng nhập trước.'
        ]);
        exit;
    }
    
    $current_user = getCurrentUser();
    
    // Kiểm tra thông tin từ database
    $stmt = $pdo->prepare("SELECT id, fullname, position, department, office, staff_code FROM staffs WHERE id = ?");
    $stmt->execute([$current_user['id']]);
    $staff_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$staff_info) {
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
            'message' => 'Đã tạo thông tin staff cho user',
            'user_info' => [
                'id' => $current_user['id'],
                'fullname' => $current_user['fullname'],
                'position' => 'Nhân viên',
                'department' => 'IT',
                'office' => 'Hà Nội'
            ]
        ]);
    } else {
        // Kiểm tra và cập nhật thông tin nếu thiếu
        $update_fields = [];
        $params = [];
        
        if (empty($staff_info['position'])) {
            $update_fields[] = "position = ?";
            $params[] = 'Nhân viên';
        }
        
        if (empty($staff_info['department'])) {
            $update_fields[] = "department = ?";
            $params[] = 'IT';
        }
        
        if (empty($staff_info['office'])) {
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
                'updated_fields' => $update_fields,
                'user_info' => [
                    'id' => $current_user['id'],
                    'fullname' => $current_user['fullname'],
                    'position' => $staff_info['position'] ?: 'Nhân viên',
                    'department' => $staff_info['department'] ?: 'IT',
                    'office' => $staff_info['office'] ?: 'Hà Nội'
                ]
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Thông tin user đã đầy đủ',
                'user_info' => [
                    'id' => $current_user['id'],
                    'fullname' => $current_user['fullname'],
                    'position' => $staff_info['position'],
                    'department' => $staff_info['department'],
                    'office' => $staff_info['office']
                ]
            ]);
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?> 