<?php
/**
 * Script thêm cột gender vào bảng staffs
 */

header('Content-Type: application/json');

require_once '../config/db.php';

try {
    // Kiểm tra xem có cột gender không
    $stmt = $pdo->query("SHOW COLUMNS FROM staffs LIKE 'gender'");
    $hasGender = $stmt->rowCount() > 0;
    
    if (!$hasGender) {
        // Thêm cột gender
        $pdo->exec("ALTER TABLE staffs ADD COLUMN gender ENUM('Nam', 'Nữ') DEFAULT 'Nam'");
        
        // Cập nhật dữ liệu mẫu (có thể thay đổi theo logic nghiệp vụ)
        $pdo->exec("UPDATE staffs SET gender = 'Nam' WHERE gender IS NULL OR gender = ''");
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm cột gender thành công',
            'action' => 'added'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Cột gender đã tồn tại',
            'action' => 'exists'
        ]);
    }
    
    // Kiểm tra cột resigned
    $stmt = $pdo->query("SHOW COLUMNS FROM staffs LIKE 'resigned'");
    $hasResigned = $stmt->rowCount() > 0;
    
    if (!$hasResigned) {
        // Thêm cột resigned
        $pdo->exec("ALTER TABLE staffs ADD COLUMN resigned TINYINT(1) DEFAULT 0");
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm cột resigned thành công',
            'action' => 'added_resigned'
        ]);
    }
    
    // Lấy thống kê sau khi thêm cột
    $totalStmt = $pdo->query("SELECT COUNT(*) as total FROM staffs");
    $total = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $maleStmt = $pdo->query("SELECT COUNT(*) as male FROM staffs WHERE gender = 'Nam'");
    $male = $maleStmt->fetch(PDO::FETCH_ASSOC)['male'];
    
    $femaleStmt = $pdo->query("SELECT COUNT(*) as female FROM staffs WHERE gender = 'Nữ'");
    $female = $femaleStmt->fetch(PDO::FETCH_ASSOC)['female'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Cập nhật thành công',
        'statistics' => [
            'total' => $total,
            'male' => $male,
            'female' => $female
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?> 