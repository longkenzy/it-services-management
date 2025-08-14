<?php
/**
 * Check work_type column in internal_cases table
 */

require_once 'config/db.php';

try {
    // Kiểm tra cấu trúc bảng internal_cases
    $result = $pdo->query("SHOW COLUMNS FROM internal_cases");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Cấu trúc bảng internal_cases:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']}\n";
    }
    
    // Kiểm tra xem có cột work_type không
    $workTypeExists = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'work_type') {
            $workTypeExists = true;
            break;
        }
    }
    
    if ($workTypeExists) {
        echo "\n✅ Cột work_type đã tồn tại!\n";
    } else {
        echo "\n❌ Cột work_type chưa tồn tại!\n";
    }
    
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
}
?>
