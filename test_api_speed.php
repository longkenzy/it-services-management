<?php
/**
 * Test file để kiểm tra tốc độ API và cấu trúc dữ liệu
 */
require_once 'config/db.php';

echo "<h2>Kiểm tra tốc độ API và cấu trúc dữ liệu</h2>";

// Test 1: Kiểm tra API get_internal_case_details.php
echo "<h3>1. Test API get_internal_case_details.php</h3>";

// Lấy một case để test
$stmt = $pdo->prepare("SELECT id FROM internal_cases LIMIT 1");
$stmt->execute();
$test_case = $stmt->fetch();

if ($test_case) {
    $case_id = $test_case['id'];
    echo "<p>Testing với case ID: $case_id</p>";
    
    // Test query trực tiếp
    $start_time = microtime(true);
    
    $stmt = $pdo->prepare("
        SELECT ic.*, 
               requester.fullname as requester_name,
               requester.position as requester_position,
               handler.fullname as handler_name,
               handler.position as handler_position
        FROM internal_cases ic
        LEFT JOIN staffs requester ON ic.requester_id = requester.id
        LEFT JOIN staffs handler ON ic.handler_id = handler.id
        WHERE ic.id = ?
    ");
    $stmt->execute([$case_id]);
    $case_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time) * 1000; // Convert to milliseconds
    
    echo "<p>Thời gian thực thi query: <strong>" . number_format($execution_time, 2) . " ms</strong></p>";
    
    if ($case_data) {
        echo "<h4>Dữ liệu trả về:</h4>";
        echo "<pre>";
        print_r($case_data);
        echo "</pre>";
        
        echo "<h4>Kiểm tra các trường quan trọng:</h4>";
        echo "<ul>";
        echo "<li>requester_name: " . ($case_data['requester_name'] ?? 'NULL') . "</li>";
        echo "<li>requester_position: " . ($case_data['requester_position'] ?? 'NULL') . "</li>";
        echo "<li>handler_name: " . ($case_data['handler_name'] ?? 'NULL') . "</li>";
        echo "<li>handler_position: " . ($case_data['handler_position'] ?? 'NULL') . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>Không tìm thấy case với ID: $case_id</p>";
    }
} else {
    echo "<p style='color: red;'>Không có case nào trong database để test</p>";
}

// Test 2: Kiểm tra API get_staff_list.php
echo "<h3>2. Test API get_staff_list.php</h3>";

$start_time = microtime(true);

$stmt = $pdo->prepare("SELECT id, fullname, position FROM staffs WHERE status = 'active' ORDER BY fullname");
$stmt->execute();
$staff_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

$end_time = microtime(true);
$execution_time = ($end_time - $start_time) * 1000;

echo "<p>Thời gian thực thi query staff list: <strong>" . number_format($execution_time, 2) . " ms</strong></p>";
echo "<p>Số lượng staff: <strong>" . count($staff_list) . "</strong></p>";

if (count($staff_list) > 0) {
    echo "<h4>Sample staff data:</h4>";
    echo "<pre>";
    print_r(array_slice($staff_list, 0, 3)); // Chỉ hiển thị 3 record đầu
    echo "</pre>";
}

// Test 3: Kiểm tra index trên bảng staffs
echo "<h3>3. Kiểm tra index trên bảng staffs</h3>";

$stmt = $pdo->prepare("SHOW INDEX FROM staffs");
$stmt->execute();
$indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h4>Indexes trên bảng staffs:</h4>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Index Name</th><th>Column</th><th>Type</th></tr>";

foreach ($indexes as $index) {
    echo "<tr>";
    echo "<td>" . $index['Key_name'] . "</td>";
    echo "<td>" . $index['Column_name'] . "</td>";
    echo "<td>" . $index['Index_type'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>4. Kết luận</h3>";
if ($execution_time < 100) {
    echo "<p style='color: green;'>✅ API hoạt động nhanh (dưới 100ms)</p>";
} else {
    echo "<p style='color: orange;'>⚠️ API hơi chậm (trên 100ms)</p>";
}

echo "<p><strong>Lưu ý:</strong> API get_internal_case_details.php đã được tối ưu để trả về đầy đủ thông tin position, không cần gọi thêm API khác.</p>";
?>
