<?php
/**
 * Kiểm tra các đơn nghỉ phép đang chờ duyệt
 */

define('INCLUDED', true);
require_once __DIR__ . '/../config/db.php';

echo "=== KIỂM TRA ĐƠN NGHỈ PHÉP CHỜ DUYỆT ===\n\n";

// Lấy danh sách đơn nghỉ phép chờ duyệt
$stmt = $pdo->prepare("
    SELECT lr.*, s.fullname as requester_name
    FROM leave_requests lr
    LEFT JOIN staffs s ON lr.requester_id = s.id
    WHERE lr.status = 'Chờ phê duyệt'
    ORDER BY lr.created_at DESC
");
$stmt->execute();
$pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($pending_requests) > 0) {
    echo "Có " . count($pending_requests) . " đơn nghỉ phép đang chờ duyệt:\n\n";
    
    foreach ($pending_requests as $request) {
        echo "ID: " . $request['id'] . "\n";
        echo "Mã đơn: " . $request['request_code'] . "\n";
        echo "Người yêu cầu: " . $request['requester_name'] . "\n";
        echo "Loại nghỉ: " . $request['leave_type'] . "\n";
        echo "Thời gian: " . date('d/m/Y H:i', strtotime($request['start_date'])) . " - " . date('d/m/Y H:i', strtotime($request['end_date'])) . "\n";
        echo "Lý do: " . $request['reason'] . "\n";
        echo "Trạng thái: " . $request['status'] . "\n";
        echo "Thời gian tạo: " . date('d/m/Y H:i', strtotime($request['created_at'])) . "\n";
        echo "---\n";
    }
} else {
    echo "Không có đơn nghỉ phép nào đang chờ duyệt.\n";
}

// Kiểm tra tất cả đơn nghỉ phép gần đây
echo "\n=== TẤT CẢ ĐƠN NGHỈ PHÉP GẦN ĐÂY ===\n\n";

$stmt = $pdo->prepare("
    SELECT lr.*, s.fullname as requester_name
    FROM leave_requests lr
    LEFT JOIN staffs s ON lr.requester_id = s.id
    ORDER BY lr.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($recent_requests as $request) {
    echo "ID: " . $request['id'] . " | ";
    echo "Mã: " . $request['request_code'] . " | ";
    echo "Người yêu cầu: " . $request['requester_name'] . " | ";
    echo "Trạng thái: " . $request['status'] . " | ";
    echo "Tạo: " . date('d/m/Y H:i', strtotime($request['created_at'])) . "\n";
}
?> 