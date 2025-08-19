<?php
/**
 * Script đơn giản để sửa ENUM
 */

require_once 'config/db.php';

echo "=== FIX ENUM SCRIPT ===\n";

try {
    // Kiểm tra hiện tại
    $check = $pdo->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'notifications' AND COLUMN_NAME = 'type'");
    $current = $check->fetch(PDO::FETCH_ASSOC);
    
    echo "Current ENUM: " . $current['COLUMN_TYPE'] . "\n";
    
    if (strpos($current['COLUMN_TYPE'], 'internal_case') === false) {
        echo "Updating ENUM...\n";
        
        // Thử cập nhật ENUM
        $sql = "ALTER TABLE notifications MODIFY COLUMN type ENUM('leave_request', 'leave_approved', 'leave_rejected', 'internal_case', 'system') DEFAULT 'system'";
        $result = $pdo->exec($sql);
        
        if ($result !== false) {
            echo "SUCCESS: ENUM updated!\n";
            
            // Kiểm tra lại
            $check = $pdo->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'notifications' AND COLUMN_NAME = 'type'");
            $new = $check->fetch(PDO::FETCH_ASSOC);
            echo "New ENUM: " . $new['COLUMN_TYPE'] . "\n";
            
            if (strpos($new['COLUMN_TYPE'], 'internal_case') !== false) {
                echo "SUCCESS: internal_case added to ENUM!\n";
                
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
                echo "ERROR: internal_case not found in new ENUM\n";
            }
        } else {
            echo "ERROR: Failed to update ENUM\n";
            $error = $pdo->errorInfo();
            echo "Error: " . $error[2] . "\n";
        }
    } else {
        echo "ENUM already has internal_case\n";
    }
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}

echo "=== END ===\n";
?>
