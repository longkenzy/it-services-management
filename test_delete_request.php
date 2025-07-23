<?php
require_once 'config/db.php';

echo "<h2>Test Tính năng Xóa Deployment Request</h2>";

// Hiển thị danh sách deployment requests hiện tại
echo "<h3>Danh sách Deployment Requests hiện tại:</h3>";
$stmt = $pdo->query("SELECT id, request_code, deployment_status FROM deployment_requests ORDER BY id");
$requests = $stmt->fetchAll();

if (empty($requests)) {
    echo "<p>Không có deployment requests nào.</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Mã yêu cầu</th><th>Trạng thái</th></tr>";
    foreach ($requests as $request) {
        echo "<tr>";
        echo "<td>" . $request['id'] . "</td>";
        echo "<td>" . $request['request_code'] . "</td>";
        echo "<td>" . $request['deployment_status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Hiển thị danh sách deployment cases hiện tại
echo "<h3>Danh sách Deployment Cases hiện tại:</h3>";
$stmt = $pdo->query("SELECT id, case_code, deployment_request_id FROM deployment_cases ORDER BY deployment_request_id, id");
$cases = $stmt->fetchAll();

if (empty($cases)) {
    echo "<p>Không có deployment cases nào.</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Mã case</th><th>Deployment Request ID</th></tr>";
    foreach ($cases as $case) {
        echo "<tr>";
        echo "<td>" . $case['id'] . "</td>";
        echo "<td>" . $case['case_code'] . "</td>";
        echo "<td>" . $case['deployment_request_id'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<p><strong>Lưu ý:</strong> Để test tính năng xóa, hãy vào trang deployment_requests.php và nhấn nút 'Xóa' trên một yêu cầu triển khai.</p>";
echo "<p>Tính năng sẽ xóa yêu cầu triển khai và tất cả các case triển khai liên quan.</p>";
?> 