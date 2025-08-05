<?php
/**
 * Debug script để kiểm tra lỗi 500 từ API create_leave_request.php
 */

// Bật hiển thị lỗi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Debug API Error - create_leave_request.php</h2>";

try {
    // 1. Kiểm tra kết nối database
    echo "<h3>1. Kiểm tra kết nối database</h3>";
    
    require_once 'config/db.php';
    echo "✓ Kết nối database thành công<br>";
    
    // 2. Kiểm tra session
    echo "<h3>2. Kiểm tra session</h3>";
    
    require_once 'includes/session.php';
    if (isLoggedIn()) {
        $current_user = getCurrentUser();
        echo "✓ Đã đăng nhập: " . $current_user['fullname'] . " (ID: " . $current_user['id'] . ")<br>";
    } else {
        echo "✗ Chưa đăng nhập<br>";
    }
    
    // 3. Kiểm tra bảng leave_requests
    echo "<h3>3. Kiểm tra bảng leave_requests</h3>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'leave_requests'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Bảng leave_requests tồn tại<br>";
        
        // Kiểm tra cấu trúc
        $stmt = $pdo->query("DESCRIBE leave_requests");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Số cột: " . count($columns) . "<br>";
        
        // Kiểm tra quyền INSERT
        try {
            $test_sql = "INSERT INTO leave_requests (request_code, requester_id, start_date, end_date, return_date, leave_days, leave_type, reason, handover_to, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $test_stmt = $pdo->prepare($test_sql);
            echo "✓ Có thể prepare câu INSERT<br>";
        } catch (Exception $e) {
            echo "✗ Lỗi prepare INSERT: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "✗ Bảng leave_requests không tồn tại<br>";
    }
    
    // 4. Kiểm tra bảng staffs
    echo "<h3>4. Kiểm tra bảng staffs</h3>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'staffs'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Bảng staffs tồn tại<br>";
        
        // Kiểm tra dữ liệu staff
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM staffs");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Số nhân viên: " . $result['count'] . "<br>";
    } else {
        echo "✗ Bảng staffs không tồn tại<br>";
    }
    
    // 5. Kiểm tra thư mục upload
    echo "<h3>5. Kiểm tra thư mục upload</h3>";
    
    $upload_dir = 'assets/uploads/leave_attachments/';
    if (is_dir($upload_dir)) {
        echo "✓ Thư mục upload tồn tại<br>";
        if (is_writable($upload_dir)) {
            echo "✓ Có quyền ghi vào thư mục upload<br>";
        } else {
            echo "✗ Không có quyền ghi vào thư mục upload<br>";
        }
    } else {
        echo "✗ Thư mục upload không tồn tại<br>";
        if (mkdir($upload_dir, 0777, true)) {
            echo "✓ Đã tạo thư mục upload<br>";
        } else {
            echo "✗ Không thể tạo thư mục upload<br>";
        }
    }
    
    // 6. Kiểm tra cài đặt PHP
    echo "<h3>6. Kiểm tra cài đặt PHP</h3>";
    
    echo "PHP Version: " . phpversion() . "<br>";
    echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "<br>";
    echo "Post Max Size: " . ini_get('post_max_size') . "<br>";
    echo "Max Execution Time: " . ini_get('max_execution_time') . "<br>";
    echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
    
    // 7. Kiểm tra các extension cần thiết
    echo "<h3>7. Kiểm tra PHP extensions</h3>";
    
    $required_extensions = ['pdo', 'pdo_mysql', 'json', 'fileinfo'];
    foreach ($required_extensions as $ext) {
        if (extension_loaded($ext)) {
            echo "✓ Extension $ext đã được load<br>";
        } else {
            echo "✗ Extension $ext chưa được load<br>";
        }
    }
    
    // 8. Thử tạo đơn nghỉ phép test
    echo "<h3>8. Thử tạo đơn nghỉ phép test</h3>";
    
    if (isLoggedIn()) {
        try {
            // Tạo request code
            $current_year = date('y');
            $current_month = date('m');
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM leave_requests WHERE request_code LIKE ?");
            $month_pattern = "NP{$current_year}{$current_month}%";
            $stmt->execute([$month_pattern]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $next_number = $result['count'] + 1;
            $request_code = "NP{$current_year}{$current_month}" . str_pad($next_number, 3, '0', STR_PAD_LEFT);
            
            echo "Generated request code: " . $request_code . "<br>";
            
            // Thử insert
            $sql = "INSERT INTO leave_requests (
                request_code, requester_id, requester_position, requester_department, requester_office,
                start_date, end_date, return_date, leave_days, leave_type, reason, handover_to,
                attachment, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $request_code,
                $current_user['id'],
                $current_user['position'] ?? 'Test Position',
                $current_user['department'] ?? 'Test Department',
                $current_user['office'] ?? 'Test Office',
                '2024-12-20 08:00:00',
                '2024-12-20 17:00:00',
                '2024-12-21 08:00:00',
                1.0,
                'Nghỉ phép năm',
                'Test reason for debugging',
                1,
                null,
                'Chờ phê duyệt'
            ]);
            
            if ($result) {
                $inserted_id = $pdo->lastInsertId();
                echo "✓ Insert thành công! ID: " . $inserted_id . "<br>";
                
                // Xóa record test
                $stmt = $pdo->prepare("DELETE FROM leave_requests WHERE id = ?");
                $stmt->execute([$inserted_id]);
                echo "✓ Đã xóa record test<br>";
            } else {
                echo "✗ Insert thất bại<br>";
            }
        } catch (Exception $e) {
            echo "✗ Lỗi khi test insert: " . $e->getMessage() . "<br>";
            echo "File: " . $e->getFile() . "<br>";
            echo "Line: " . $e->getLine() . "<br>";
        }
    } else {
        echo "✗ Chưa đăng nhập nên không thể test<br>";
    }
    
    // 9. Kiểm tra error logs
    echo "<h3>9. Kiểm tra error logs</h3>";
    
    $log_files = [
        'error_log' => ini_get('error_log'),
        'php_errors' => 'logs/php_errors.log',
        'custom_log' => 'logs/api_errors.log'
    ];
    
    foreach ($log_files as $name => $log_file) {
        if (file_exists($log_file)) {
            echo "✓ Log file $name tồn tại: $log_file<br>";
            $size = filesize($log_file);
            echo "  Kích thước: " . number_format($size) . " bytes<br>";
            
            if ($size > 0) {
                $content = file_get_contents($log_file);
                $lines = explode("\n", $content);
                $recent_lines = array_slice($lines, -5);
                echo "  5 dòng cuối:<br>";
                foreach ($recent_lines as $line) {
                    if (trim($line)) {
                        echo "  " . htmlspecialchars($line) . "<br>";
                    }
                }
            }
        } else {
            echo "ℹ Log file $name không tồn tại: $log_file<br>";
        }
    }
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>✗ Lỗi chính:</h3>";
    echo "Message: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?> 