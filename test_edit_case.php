<?php
require_once 'config/db.php';

echo "<h2>Test Tính năng Edit Case Triển Khai</h2>";

// Hiển thị danh sách deployment cases hiện tại
echo "<h3>Danh sách Deployment Cases hiện tại:</h3>";
$stmt = $pdo->query("SELECT id, case_code, request_type, status, deployment_request_id FROM deployment_cases ORDER BY id DESC LIMIT 10");
$cases = $stmt->fetchAll();

if (empty($cases)) {
    echo "<p>Không có deployment cases nào.</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Mã case</th><th>Loại yêu cầu</th><th>Trạng thái</th><th>Deployment Request ID</th></tr>";
    foreach ($cases as $case) {
        echo "<tr>";
        echo "<td>" . $case['id'] . "</td>";
        echo "<td>" . $case['case_code'] . "</td>";
        echo "<td>" . $case['request_type'] . "</td>";
        echo "<td>" . $case['status'] . "</td>";
        echo "<td>" . $case['deployment_request_id'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<p><strong>Hướng dẫn test:</strong></p>";
echo "<ol>";
echo "<li>Vào trang <code>deployment_requests.php</code></li>";
echo "<li>Nhấn nút 'Chỉnh sửa' trên một yêu cầu triển khai</li>";
echo "<li>Trong modal edit, nhấn nút 'Tạo case triển khai' để tạo case mới</li>";
echo "<li>Hoặc nếu đã có case, nhấn nút 'Sửa' (biểu tượng bút chì) trên case đó</li>";
echo "<li>Modal edit case sẽ hiện ra với form giống hệt form tạo case</li>";
echo "<li>Chỉnh sửa thông tin và nhấn 'Cập nhật case'</li>";
echo "</ol>";

echo "<p><strong>Lưu ý:</strong> Tính năng edit case đã được hoàn thiện với:</p>";
echo "<ul>";
echo "<li>Form edit giống hệt form tạo case</li>";
echo "<li>Tự động điền dữ liệu hiện tại vào form</li>";
echo "<li>Validation đầy đủ</li>";
echo "<li>Cập nhật realtime bảng danh sách</li>";
echo "<li>Cập nhật cột 'Tổng số case'</li>";
echo "</ul>";
?> 