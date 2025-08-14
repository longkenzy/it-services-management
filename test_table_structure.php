<?php
require_once 'config/db.php';

try {
    // Check if user_activity_logs table exists and has correct structure
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_activity_logs'");
    if ($stmt->rowCount() == 0) {
        echo "Table user_activity_logs does not exist!\n";
        exit;
    }
    
    // Check table structure
    $stmt = $pdo->query("DESCRIBE user_activity_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "user_activity_logs table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']}\n";
    }
    
    // Check if internal_cases table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'internal_cases'");
    if ($stmt->rowCount() == 0) {
        echo "\nTable internal_cases does not exist!\n";
        exit;
    }
    
    // Check internal_cases table structure
    $stmt = $pdo->query("DESCRIBE internal_cases");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\ninternal_cases table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
