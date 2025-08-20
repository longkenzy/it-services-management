<?php
/**
 * Script dọn dẹp dữ liệu khoảng trắng thừa trong bảng internal_cases
 * File: clean_internal_cases_data.php
 */

require_once 'config/db.php';

echo "<h2>Dọn dẹp dữ liệu khoảng trắng thừa trong bảng internal_cases</h2>";

try {
    // Kiểm tra kết nối database
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✓ Kết nối database thành công</p>";
    
    // Đếm số record trước khi dọn dẹp
    $count_stmt = $pdo->query("SELECT COUNT(*) as total FROM internal_cases");
    $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p>Tổng số record: $total_records</p>";
    
    // Lấy tất cả records có khoảng trắng thừa
    $stmt = $pdo->query("
        SELECT id, case_type, issue_title, issue_description, notes 
        FROM internal_cases 
        WHERE 
            case_type != TRIM(case_type) OR
            issue_title != TRIM(issue_title) OR
            issue_description != TRIM(issue_description) OR
            notes != TRIM(notes)
    ");
    
    $records_to_clean = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count_to_clean = count($records_to_clean);
    
    echo "<p>Số record cần dọn dẹp: $count_to_clean</p>";
    
    if ($count_to_clean > 0) {
        echo "<h3>Chi tiết các record cần dọn dẹp:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Loại case</th><th>Tiêu đề</th><th>Mô tả</th><th>Ghi chú</th>";
        echo "</tr>";
        
        foreach ($records_to_clean as $record) {
            echo "<tr>";
            echo "<td>" . $record['id'] . "</td>";
            echo "<td>'" . htmlspecialchars($record['case_type']) . "'</td>";
            echo "<td>'" . htmlspecialchars($record['issue_title']) . "'</td>";
            echo "<td>'" . htmlspecialchars($record['issue_description']) . "'</td>";
            echo "<td>'" . htmlspecialchars($record['notes']) . "'</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Thực hiện dọn dẹp
        echo "<h3>Đang thực hiện dọn dẹp...</h3>";
        
        $update_stmt = $pdo->prepare("
            UPDATE internal_cases 
            SET 
                case_type = TRIM(case_type),
                issue_title = TRIM(issue_title),
                issue_description = TRIM(issue_description),
                notes = TRIM(notes),
                updated_at = NOW()
            WHERE id = ?
        ");
        
        $updated_count = 0;
        foreach ($records_to_clean as $record) {
            $result = $update_stmt->execute([$record['id']]);
            if ($result) {
                $updated_count++;
                echo "<p>✓ Đã dọn dẹp record ID: " . $record['id'] . "</p>";
            } else {
                echo "<p style='color: red;'>✗ Lỗi khi dọn dẹp record ID: " . $record['id'] . "</p>";
            }
        }
        
        echo "<h3>Kết quả dọn dẹp:</h3>";
        echo "<p>✓ Đã dọn dẹp thành công: $updated_count record</p>";
        
    } else {
        echo "<p>✓ Không có record nào cần dọn dẹp</p>";
    }
    
    // Kiểm tra lại sau khi dọn dẹp
    $check_stmt = $pdo->query("
        SELECT COUNT(*) as remaining 
        FROM internal_cases 
        WHERE 
            case_type != TRIM(case_type) OR
            issue_title != TRIM(issue_title) OR
            issue_description != TRIM(issue_description) OR
            notes != TRIM(notes)
    ");
    
    $remaining = $check_stmt->fetch(PDO::FETCH_ASSOC)['remaining'];
    
    if ($remaining == 0) {
        echo "<p style='color: green; font-weight: bold;'>✓ Dọn dẹp hoàn tất! Không còn khoảng trắng thừa nào.</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>✗ Vẫn còn $remaining record có khoảng trắng thừa</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Lỗi database: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='internal_cases.php'>← Quay lại trang Case nội bộ</a></p>";
?>
