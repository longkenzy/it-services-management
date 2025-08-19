<?php
/**
 * Script kiểm tra trạng thái auto increment của các bảng
 * Chạy file này để xem tình trạng auto increment hiện tại
 */

require_once 'config/db.php';

echo "<h2>Kiểm tra trạng thái Auto Increment</h2>";

try {
    // Danh sách các bảng cần kiểm tra
    $tables = [
        'deployment_cases',
        'deployment_requests', 
        'deployment_tasks',
        'maintenance_cases',
        'maintenance_requests',
        'maintenance_tasks',
        'internal_cases'
    ];
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
    echo "<tr><th>Bảng</th><th>Auto Increment</th><th>ID cao nhất</th><th>Trạng thái</th><th>Ghi chú</th></tr>";
    
    foreach ($tables as $table) {
        // Kiểm tra bảng có tồn tại không
        $checkTable = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($checkTable->rowCount() == 0) {
            echo "<tr>";
            echo "<td>$table</td>";
            echo "<td colspan='3' style='color: red;'>Bảng không tồn tại</td>";
            echo "<td>-</td>";
            echo "</tr>";
            continue;
        }
        
        // Lấy thông tin auto increment
        $statusSql = "SHOW TABLE STATUS LIKE '$table'";
        $statusStmt = $pdo->prepare($statusSql);
        $statusStmt->execute();
        $tableStatus = $statusStmt->fetch(PDO::FETCH_ASSOC);
        
        $autoIncrement = $tableStatus['Auto_increment'] ?? 0;
        
        // Lấy ID cao nhất
        $maxIdSql = "SELECT MAX(id) as max_id FROM $table";
        $maxIdStmt = $pdo->prepare($maxIdSql);
        $maxIdStmt->execute();
        $maxResult = $maxIdStmt->fetch(PDO::FETCH_ASSOC);
        $maxId = $maxResult['max_id'] ?? 0;
        
        // Kiểm tra trạng thái
        $status = 'OK';
        $note = '';
        
        if ($autoIncrement == 0) {
            $status = 'LỖI';
            $note = 'Auto increment = 0';
        } elseif ($autoIncrement <= $maxId) {
            $status = 'CẦN SỬA';
            $note = 'Auto increment <= max ID';
        } elseif ($maxId == 0) {
            $status = 'OK';
            $note = 'Bảng trống';
        }
        
        $statusColor = $status === 'OK' ? 'green' : ($status === 'CẦN SỬA' ? 'orange' : 'red');
        
        echo "<tr>";
        echo "<td><strong>$table</strong></td>";
        echo "<td>$autoIncrement</td>";
        echo "<td>$maxId</td>";
        echo "<td style='color: $statusColor;'><strong>$status</strong></td>";
        echo "<td>$note</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Kiểm tra cấu trúc cột id của deployment_cases
    echo "<h3>Kiểm tra cấu trúc cột ID - deployment_cases</h3>";
    $describeSql = "DESCRIBE deployment_cases";
    $describeStmt = $pdo->prepare($describeSql);
    $describeStmt->execute();
    $columns = $describeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'id') {
            $extraColor = strpos($column['Extra'], 'auto_increment') !== false ? 'green' : 'red';
            echo "<tr>";
            echo "<td><strong>{$column['Field']}</strong></td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td style='color: $extraColor;'><strong>{$column['Extra']}</strong></td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    // Hiển thị dữ liệu mẫu
    echo "<h3>Dữ liệu mẫu - deployment_cases</h3>";
    $sampleSql = "SELECT id, case_code, created_at FROM deployment_cases ORDER BY id ASC LIMIT 5";
    $sampleStmt = $pdo->prepare($sampleSql);
    $sampleStmt->execute();
    $samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($samples)) {
        echo "<p style='color: blue;'>Bảng deployment_cases chưa có dữ liệu</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Case Code</th><th>Created At</th></tr>";
        foreach ($samples as $sample) {
            $idColor = $sample['id'] == 0 ? 'red' : 'black';
            echo "<tr>";
            echo "<td style='color: $idColor;'><strong>{$sample['id']}</strong></td>";
            echo "<td>{$sample['case_code']}</td>";
            echo "<td>{$sample['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3 style='color: green;'>Hoàn thành kiểm tra!</h3>";
    echo "<p>Nếu có bảng nào hiển thị trạng thái 'LỖI' hoặc 'CẦN SỬA', hãy sử dụng script sửa lỗi tương ứng.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
}
?>
