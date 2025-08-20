<?php
/**
 * Fix Auto Increment cho tất cả các bảng maintenance
 * File: fix_all_maintenance_auto_increment.php
 * Mục đích: Sửa lỗi auto-increment bị reset về 0 cho tất cả bảng maintenance
 * Tác giả: IT Support Team
 * Ngày tạo: 2024-12-19
 */

require_once 'config/db.php';

echo "<h2>Fixing Auto Increment cho tất cả các bảng Maintenance</h2>";

$tables = [
    'maintenance_requests' => 'Yêu cầu bảo trì',
    'maintenance_cases' => 'Case bảo trì',
    'maintenance_tasks' => 'Task bảo trì'
];

$results = [];

foreach ($tables as $table => $tableName) {
    echo "<h3>🔧 Đang xử lý bảng: {$tableName} ({$table})</h3>";
    
    try {
        // Bước 1: Kiểm tra cấu trúc bảng
        echo "<p>1. Kiểm tra cấu trúc bảng {$table}...</p>";
        
        $stmt = $pdo->query("DESCRIBE {$table}");
        $columns = $stmt->fetchAll();
        
        $idColumn = null;
        foreach ($columns as $column) {
            if ($column['Field'] === 'id') {
                $idColumn = $column;
                break;
            }
        }
        
        if (!$idColumn) {
            throw new Exception("Không tìm thấy cột 'id' trong bảng {$table}");
        }
        
        echo "<p>Cột 'id' hiện tại: " . $idColumn['Type'] . " | Extra: " . $idColumn['Extra'] . "</p>";
        
        // Bước 2: Kiểm tra giá trị auto_increment hiện tại
        echo "<p>2. Kiểm tra giá trị AUTO_INCREMENT hiện tại...</p>";
        
        $stmt = $pdo->query("SHOW TABLE STATUS LIKE '{$table}'");
        $tableStatus = $stmt->fetch();
        
        $currentAutoIncrement = $tableStatus['Auto_increment'];
        echo "<p>Giá trị AUTO_INCREMENT hiện tại: " . ($currentAutoIncrement ?: 'NULL') . "</p>";
        
        // Bước 3: Tìm ID cao nhất trong bảng
        echo "<p>3. Tìm ID cao nhất trong bảng...</p>";
        
        $stmt = $pdo->query("SELECT MAX(id) as max_id FROM {$table}");
        $result = $stmt->fetch();
        $maxId = $result['max_id'];
        
        echo "<p>ID cao nhất hiện tại: " . ($maxId ?: 'NULL (bảng trống)') . "</p>";
        
        // Bước 4: Tính toán giá trị AUTO_INCREMENT mới
        $nextId = $maxId ? $maxId + 1 : 1;
        echo "<p>4. Giá trị AUTO_INCREMENT mới sẽ là: " . $nextId . "</p>";
        
        // Bước 5: Sửa cấu trúc cột id nếu cần
        echo "<p>5. Sửa cấu trúc cột 'id'...</p>";
        
        if (strpos($idColumn['Extra'], 'auto_increment') === false) {
            echo "<p>Cột 'id' không có auto_increment, đang sửa...</p>";
            $alterSql = "ALTER TABLE {$table} MODIFY COLUMN id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
            $pdo->exec($alterSql);
            echo "<p>✓ Đã sửa cấu trúc cột 'id'</p>";
        } else {
            echo "<p>✓ Cột 'id' đã có auto_increment</p>";
        }
        
        // Bước 6: Reset AUTO_INCREMENT về giá trị đúng
        echo "<p>6. Reset AUTO_INCREMENT về giá trị đúng...</p>";
        
        $sql5 = "ALTER TABLE {$table} AUTO_INCREMENT = {$nextId}";
        $pdo->exec($sql5);
        echo "<p>✓ Đã reset AUTO_INCREMENT về " . $nextId . "</p>";
        
        // Bước 7: Kiểm tra lại
        echo "<p>7. Kiểm tra lại sau khi sửa...</p>";
        
        $stmt = $pdo->query("SHOW TABLE STATUS LIKE '{$table}'");
        $tableStatusAfter = $stmt->fetch();
        $newAutoIncrement = $tableStatusAfter['Auto_increment'];
        
        echo "<p>Giá trị AUTO_INCREMENT sau khi sửa: " . $newAutoIncrement . "</p>";
        
        // Bước 8: Test insert để kiểm tra
        echo "<p>8. Test insert để kiểm tra...</p>";
        
        // Tạo một record test tạm thời
        $testCode = 'TEST_' . date('YmdHis') . '_' . rand(1000, 9999);
        
        if ($table === 'maintenance_requests') {
            $stmt = $pdo->prepare("INSERT INTO {$table} (request_code, customer_id, sale_id, maintenance_status, created_by) VALUES (?, 1, 1, 'Tiếp nhận', 1)");
            $stmt->execute([$testCode]);
        } elseif ($table === 'maintenance_cases') {
            $stmt = $pdo->prepare("INSERT INTO {$table} (case_code, maintenance_request_id, request_type, assigned_to, status, created_by) VALUES (?, 1, 'Test Case', 1, 'Tiếp nhận', 1)");
            $stmt->execute([$testCode]);
        } elseif ($table === 'maintenance_tasks') {
            $stmt = $pdo->prepare("INSERT INTO {$table} (task_number, maintenance_case_id, task_name, status, created_by) VALUES (?, 1, 'Test Task', 'Chờ xử lý', 1)");
            $stmt->execute([$testCode]);
        }
        
        $testId = $pdo->lastInsertId();
        echo "<p>✓ Test insert thành công với ID: " . $testId . "</p>";
        
        // Xóa record test
        if ($table === 'maintenance_requests') {
            $stmt = $pdo->prepare("DELETE FROM {$table} WHERE request_code = ?");
        } elseif ($table === 'maintenance_cases') {
            $stmt = $pdo->prepare("DELETE FROM {$table} WHERE case_code = ?");
        } elseif ($table === 'maintenance_tasks') {
            $stmt = $pdo->prepare("DELETE FROM {$table} WHERE task_number = ?");
        }
        $stmt->execute([$testCode]);
        echo "<p>✓ Đã xóa record test</p>";
        
        $results[$table] = [
            'status' => 'success',
            'old_auto_increment' => $currentAutoIncrement,
            'new_auto_increment' => $newAutoIncrement,
            'next_id' => $nextId,
            'test_id' => $testId
        ];
        
        echo "<p style='color: green;'><strong>✓ Hoàn thành! Auto increment đã được sửa thành công cho bảng {$tableName}.</strong></p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'><strong>✗ Lỗi khi xử lý bảng {$tableName}: " . $e->getMessage() . "</strong></p>";
        error_log("Error in fix_all_maintenance_auto_increment.php for table {$table}: " . $e->getMessage());
        
        $results[$table] = [
            'status' => 'error',
            'error' => $e->getMessage()
        ];
    }
    
    echo "<hr>";
}

// Tóm tắt kết quả
echo "<h3>📊 Tóm tắt kết quả</h3>";
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; width: 100%;'>";
echo "<tr style='background-color: #f8f9fa;'>";
echo "<th>Bảng</th>";
echo "<th>Trạng thái</th>";
echo "<th>AUTO_INCREMENT cũ</th>";
echo "<th>AUTO_INCREMENT mới</th>";
echo "<th>ID tiếp theo</th>";
echo "<th>Test ID</th>";
echo "</tr>";

foreach ($results as $table => $result) {
    $tableName = $tables[$table];
    $statusColor = $result['status'] === 'success' ? 'green' : 'red';
    $statusText = $result['status'] === 'success' ? '✅ Thành công' : '❌ Lỗi';
    
    echo "<tr>";
    echo "<td><strong>{$tableName}</strong><br><small>{$table}</small></td>";
    echo "<td style='color: {$statusColor};'>{$statusText}</td>";
    
    if ($result['status'] === 'success') {
        echo "<td>" . ($result['old_auto_increment'] ?: 'NULL') . "</td>";
        echo "<td>" . $result['new_auto_increment'] . "</td>";
        echo "<td>" . $result['next_id'] . "</td>";
        echo "<td>" . $result['test_id'] . "</td>";
    } else {
        echo "<td colspan='4' style='color: red;'>" . $result['error'] . "</td>";
    }
    
    echo "</tr>";
}
echo "</table>";

// Đếm số bảng thành công
$successCount = count(array_filter($results, function($result) {
    return $result['status'] === 'success';
}));

$totalCount = count($tables);

echo "<h3 style='color: " . ($successCount === $totalCount ? 'green' : 'orange') . ";'>";
echo "🎯 Kết quả: {$successCount}/{$totalCount} bảng đã được sửa thành công";
echo "</h3>";

if ($successCount === $totalCount) {
    echo "<p style='color: green;'><strong>🎉 Tất cả các bảng maintenance đã được sửa auto-increment thành công!</strong></p>";
} else {
    echo "<p style='color: orange;'><strong>⚠️ Có một số bảng chưa được sửa thành công. Vui lòng kiểm tra lại.</strong></p>";
}

echo "<hr>";
echo "<p><a href='maintenance_requests.php'>← Quay lại trang Yêu cầu bảo trì</a></p>";
echo "<p><a href='test_maintenance_auto_increment.php'>🔍 Test bảng maintenance_requests</a></p>";
echo "<p><a href='test_maintenance_cases_auto_increment.php'>🔍 Test bảng maintenance_cases</a></p>";
echo "<p><a href='test_maintenance_tasks_auto_increment.php'>🔍 Test bảng maintenance_tasks</a></p>";
?>
