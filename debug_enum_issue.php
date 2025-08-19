<?php
/**
 * Script debug để tìm hiểu tại sao ENUM không cập nhật được
 */

require_once 'config/db.php';

echo "=== DEBUG ENUM ISSUE ===\n";

try {
    // 1. Kiểm tra quyền database
    echo "1. Checking database permissions...\n";
    $user = $pdo->query("SELECT USER() as current_user")->fetch(PDO::FETCH_ASSOC);
    echo "Current user: " . $user['current_user'] . "\n";
    
    // 2. Kiểm tra cấu trúc bảng notifications
    echo "\n2. Checking notifications table structure...\n";
    $structure = $pdo->query("DESCRIBE notifications")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($structure as $col) {
        if ($col['Field'] == 'type') {
            echo "Type column: " . $col['Type'] . "\n";
            echo "Null: " . $col['Null'] . "\n";
            echo "Key: " . $col['Key'] . "\n";
            echo "Default: " . $col['Default'] . "\n";
            echo "Extra: " . $col['Extra'] . "\n";
        }
    }
    
    // 3. Kiểm tra dữ liệu hiện tại
    echo "\n3. Checking current data...\n";
    $data = $pdo->query("SELECT type, COUNT(*) as count FROM notifications GROUP BY type")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($data as $row) {
        echo "Type: " . $row['type'] . " - Count: " . $row['count'] . "\n";
    }
    
    // 4. Thử cách khác để cập nhật ENUM
    echo "\n4. Trying alternative ENUM update method...\n";
    
    // Thử tạo bảng tạm
    echo "Creating temporary table...\n";
    $pdo->exec("CREATE TABLE notifications_temp LIKE notifications");
    
    // Cập nhật cấu trúc bảng tạm
    echo "Updating temp table structure...\n";
    $pdo->exec("ALTER TABLE notifications_temp MODIFY COLUMN type ENUM('leave_request', 'leave_approved', 'leave_rejected', 'internal_case', 'system') DEFAULT 'system'");
    
    // Kiểm tra bảng tạm
    $temp_check = $pdo->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'notifications_temp' AND COLUMN_NAME = 'type'");
    $temp_type = $temp_check->fetch(PDO::FETCH_ASSOC);
    echo "Temp table ENUM: " . $temp_type['COLUMN_TYPE'] . "\n";
    
    if (strpos($temp_type['COLUMN_TYPE'], 'internal_case') !== false) {
        echo "SUCCESS: Temp table has internal_case!\n";
        
        // Copy dữ liệu
        echo "Copying data to temp table...\n";
        $pdo->exec("INSERT INTO notifications_temp SELECT * FROM notifications");
        
        // Drop bảng cũ và rename bảng mới
        echo "Replacing original table...\n";
        $pdo->exec("DROP TABLE notifications");
        $pdo->exec("RENAME TABLE notifications_temp TO notifications");
        
        echo "SUCCESS: Table replaced with new ENUM!\n";
        
        // Test tạo thông báo
        $staff = $pdo->query("SELECT id, fullname FROM staffs LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($staff) {
            $test = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, 'internal_case', ?)");
            $result = $test->execute([$staff['id'], 'Test', 'Test message', 999]);
            
            if ($result) {
                echo "SUCCESS: Test notification created!\n";
                $pdo->exec("DELETE FROM notifications WHERE related_id = 999");
                echo "Test notification cleaned up.\n";
            } else {
                echo "ERROR: Failed to create test notification\n";
            }
        }
        
    } else {
        echo "ERROR: Temp table also failed to get internal_case\n";
        $pdo->exec("DROP TABLE notifications_temp");
    }
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "=== END DEBUG ===\n";
?>
