<?php
// Script kiểm tra bảng deployment_cases
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Checking deployment_cases table...\n";

// Include database
define('INCLUDED', true);
require_once 'config/db.php';

try {
    // Kiểm tra bảng có tồn tại không
    $stmt = $pdo->query("SHOW TABLES LIKE 'deployment_cases'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Table deployment_cases exists\n";
        
        // Kiểm tra cấu trúc bảng
        $stmt = $pdo->query("DESCRIBE deployment_cases");
        $columns = $stmt->fetchAll();
        
        echo "Table structure:\n";
        foreach ($columns as $column) {
            echo "- {$column['Field']}: {$column['Type']} " . 
                 ($column['Null'] == 'NO' ? 'NOT NULL' : 'NULL') . 
                 ($column['Key'] ? " ({$column['Key']})" : "") . "\n";
        }
        
        // Kiểm tra dữ liệu
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM deployment_cases");
        $count = $stmt->fetch()['count'];
        echo "Total records: $count\n";
        
    } else {
        echo "✗ Table deployment_cases does not exist\n";
        
        // Tạo bảng
        echo "Creating table...\n";
        $createTableSQL = "CREATE TABLE deployment_cases (
            id INT AUTO_INCREMENT PRIMARY KEY,
            case_number VARCHAR(50) NOT NULL UNIQUE,
            progress VARCHAR(20) DEFAULT 'CS',
            case_description TEXT,
            notes TEXT,
            created_by INT,
            assigned_to INT,
            priority VARCHAR(20) DEFAULT 'onsite',
            start_date DATE,
            due_date DATE,
            status VARCHAR(20) DEFAULT 'pending',
            progress_status VARCHAR(50) DEFAULT 'Phát triển sau',
            total_tasks INT DEFAULT 0,
            completed_tasks INT DEFAULT 0,
            progress_percentage INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES staffs(id),
            FOREIGN KEY (assigned_to) REFERENCES staffs(id)
        )";
        
        $pdo->exec($createTableSQL);
        echo "✓ Table created successfully\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "Check completed.\n";
?> 