<?php
/**
 * Test script để kiểm tra việc sửa lỗi khoảng trắng thừa
 * File: test_whitespace_fix.php
 */

require_once 'config/db.php';

echo "<h2>Kiểm tra dữ liệu khoảng trắng thừa trong bảng internal_cases</h2>";

try {
    // Lấy tất cả records từ bảng internal_cases
    $stmt = $pdo->query("SELECT id, case_type, issue_title, issue_description, notes FROM internal_cases ORDER BY id");
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Tổng số records: " . count($cases) . "</p>";
    
    if (count($cases) > 0) {
        echo "<h3>Chi tiết từng record:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Loại case</th><th>Tiêu đề</th><th>Mô tả (Raw)</th><th>Ghi chú (Raw)</th>";
        echo "<th>Mô tả (Trimmed)</th><th>Ghi chú (Trimmed)</th>";
        echo "</tr>";
        
        foreach ($cases as $case) {
            echo "<tr>";
            echo "<td>" . $case['id'] . "</td>";
            echo "<td>'" . htmlspecialchars($case['case_type']) . "'</td>";
            echo "<td>'" . htmlspecialchars($case['issue_title']) . "'</td>";
            echo "<td>'" . htmlspecialchars($case['issue_description']) . "'</td>";
            echo "<td>'" . htmlspecialchars($case['notes']) . "'</td>";
            echo "<td>'" . htmlspecialchars(trim($case['issue_description'])) . "'</td>";
            echo "<td>'" . htmlspecialchars(trim($case['notes'])) . "'</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Kiểm tra xem có khoảng trắng thừa không
        echo "<h3>Kiểm tra khoảng trắng thừa:</h3>";
        $hasWhitespace = false;
        
        foreach ($cases as $case) {
            if (trim($case['issue_description']) !== $case['issue_description'] || 
                trim($case['notes']) !== $case['notes'] ||
                trim($case['case_type']) !== $case['case_type'] ||
                trim($case['issue_title']) !== $case['issue_title']) {
                
                $hasWhitespace = true;
                echo "<p style='color: red;'>❌ Record ID {$case['id']} có khoảng trắng thừa!</p>";
                
                if (trim($case['issue_description']) !== $case['issue_description']) {
                    echo "<p>  - Mô tả: '" . htmlspecialchars($case['issue_description']) . "'</p>";
                }
                if (trim($case['notes']) !== $case['notes']) {
                    echo "<p>  - Ghi chú: '" . htmlspecialchars($case['notes']) . "'</p>";
                }
                if (trim($case['case_type']) !== $case['case_type']) {
                    echo "<p>  - Loại case: '" . htmlspecialchars($case['case_type']) . "'</p>";
                }
                if (trim($case['issue_title']) !== $case['issue_title']) {
                    echo "<p>  - Tiêu đề: '" . htmlspecialchars($case['issue_title']) . "'</p>";
                }
            }
        }
        
        if (!$hasWhitespace) {
            echo "<p style='color: green;'>✅ Không có khoảng trắng thừa nào!</p>";
        }
        
    } else {
        echo "<p>Không có records nào trong bảng internal_cases</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Lỗi database: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Lỗi: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='internal_cases.php'>← Quay lại trang Case nội bộ</a></p>";
?>
