<?php
// Bảo vệ file khỏi truy cập trực tiếp (chỉ cho phép từ cùng domain)
if (!isset($_SERVER['HTTP_REFERER']) || !str_contains($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST'])) {
    // Cho phép truy cập từ AJAX requests
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
        http_response_code(403);
        exit('Access denied.');
    }
}
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/session.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Please login first']);
    exit;
}

require_once '../config/db.php';

// Debug: Kiểm tra database connection và auto-increment
try {
    $test_stmt = $pdo->prepare("SELECT NOW() as current_time, @@time_zone as timezone, @@sql_mode as sql_mode, @@auto_increment_increment as auto_inc_inc, @@auto_increment_offset as auto_inc_offset");
    $test_stmt->execute();
    $test_result = $test_stmt->fetch(PDO::FETCH_ASSOC);
    error_log("DEBUG: Database connection test - Current time: {$test_result['current_time']}, Timezone: {$test_result['timezone']}, SQL mode: {$test_result['sql_mode']}, Auto increment: {$test_result['auto_inc_inc']}, Auto offset: {$test_result['auto_inc_offset']}");
    
    // Kiểm tra auto-increment của bảng internal_cases
    $auto_stmt = $pdo->prepare("SHOW TABLE STATUS LIKE 'internal_cases'");
    $auto_stmt->execute();
    $auto_result = $auto_stmt->fetch(PDO::FETCH_ASSOC);
    error_log("DEBUG: Table status - Auto_increment: {$auto_result['Auto_increment']}, Engine: {$auto_result['Engine']}");
    
} catch (Exception $e) {
    error_log("DEBUG: Database connection test failed - " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
        exit;
    }
    
    // Validate required fields
    $required_fields = ['requester_id', 'handler_id', 'case_type', 'issue_title', 'issue_description', 'status'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            echo json_encode(['success' => false, 'error' => "Trường '$field' là bắt buộc"]);
            exit;
        }
    }
    
    // Tạo số case tự động
    $case_number = generateCaseNumber($pdo);
    
    // Chuẩn bị dữ liệu
    $current_user_id = getCurrentUserId();
    $requester_id = $input['requester_id'];
    $handler_id = $input['handler_id'];
    $case_type = trim($input['case_type']);
    $priority = $input['priority'] ?? 'onsite';
    $issue_title = trim($input['issue_title']);
    $issue_description = trim($input['issue_description']);
    $status = $input['status'];
    $notes = trim($input['notes'] ?? '');
    $start_date = !empty($input['start_date']) ? $input['start_date'] : null;
    $due_date = !empty($input['due_date']) ? $input['due_date'] : null;
    $transferred_by = 'Trần Nguyễn Anh Khoa';
    
    // Validate date range
    if (!empty($start_date) && !empty($due_date)) {
        $start = new DateTime($start_date);
        $end = new DateTime($due_date);
        
        if ($end <= $start) {
            echo json_encode(['success' => false, 'error' => 'Ngày kết thúc phải lớn hơn ngày bắt đầu']);
            exit;
        }
    }
    
    // Tự động set completed_at nếu status là completed
    $completed_at = null;
    if ($status === 'completed') {
        $completed_at = date('Y-m-d H:i:s');
    }
    
    // Insert case vào database với explicit timestamps
    $current_timestamp = date('Y-m-d H:i:s');
    
    // Thử cách 1: Insert với explicit timestamps
    $stmt = $pdo->prepare("
        INSERT INTO internal_cases (
            case_number, requester_id, handler_id, transferred_by, case_type, priority,
            issue_title, issue_description, status, notes, start_date, due_date, completed_at, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $case_number,
        $requester_id,
        $handler_id,
        $transferred_by,
        $case_type,
        $priority,
        $issue_title,
        $issue_description,
        $status,
        $notes,
        $start_date,
        $due_date,
        $completed_at,
        $current_timestamp,
        $current_timestamp
    ]);
    
    // Nếu cách 1 thất bại, thử cách 2: Insert không có timestamps
    if (!$result) {
        error_log("DEBUG: First insert failed, trying without timestamps");
        
        $stmt = $pdo->prepare("
            INSERT INTO internal_cases (
                case_number, requester_id, handler_id, transferred_by, case_type, priority,
                issue_title, issue_description, status, notes, start_date, due_date, completed_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $case_number,
            $requester_id,
            $handler_id,
            $transferred_by,
            $case_type,
            $priority,
            $issue_title,
            $issue_description,
            $status,
            $notes,
            $start_date,
            $due_date,
            $completed_at
        ]);
    }
    
    // Nếu vẫn thất bại, thử sửa auto-increment
    if (!$result) {
        error_log("DEBUG: Both inserts failed, trying to fix auto-increment");
        
        try {
            // Lấy ID cao nhất hiện có
            $max_stmt = $pdo->prepare("SELECT MAX(id) as max_id FROM internal_cases");
            $max_stmt->execute();
            $max_result = $max_stmt->fetch(PDO::FETCH_ASSOC);
            $max_id = $max_result['max_id'] ?? 0;
            $next_id = $max_id + 1;
            
            error_log("DEBUG: Max ID: $max_id, Next ID: $next_id");
            
            // Reset auto-increment
            $reset_stmt = $pdo->prepare("ALTER TABLE internal_cases AUTO_INCREMENT = ?");
            $reset_stmt->execute([$next_id]);
            
            // Thử insert lại
            $stmt = $pdo->prepare("
                INSERT INTO internal_cases (
                    case_number, requester_id, handler_id, transferred_by, case_type, priority,
                    issue_title, issue_description, status, notes, start_date, due_date, completed_at, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $result = $stmt->execute([
                $case_number,
                $requester_id,
                $handler_id,
                $transferred_by,
                $case_type,
                $priority,
                $issue_title,
                $issue_description,
                $status,
                $notes,
                $start_date,
                $due_date,
                $completed_at,
                $current_timestamp,
                $current_timestamp
            ]);
            
            error_log("DEBUG: After auto-increment fix - Insert result: " . ($result ? 'success' : 'failed'));
            
        } catch (Exception $e) {
            error_log("DEBUG: Auto-increment fix failed - " . $e->getMessage());
        }
    }
    
    if (!$result) {
        $error_info = $stmt->errorInfo();
        error_log("DEBUG: Insert failed - Error info: " . print_r($error_info, true));
        echo json_encode(['success' => false, 'error' => 'Database insert failed: ' . $error_info[2]]);
        exit;
    }
    
    // Debug: Log thông tin insert
    error_log("DEBUG: Insert successful - Rows affected: " . $stmt->rowCount());
    
    $case_id = $pdo->lastInsertId();
    
    // Debug: Log thông tin database
    error_log("DEBUG: Database info - lastInsertId: $case_id, current_timestamp: $current_timestamp");
    
    // Debug: Kiểm tra case vừa tạo
    if ($case_id && $case_id > 0) {
        $check_stmt = $pdo->prepare("SELECT id, case_number, created_at, updated_at FROM internal_cases WHERE id = ?");
        $check_stmt->execute([$case_id]);
        $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($check_result) {
            error_log("DEBUG: Case created successfully - ID: {$check_result['id']}, Number: {$check_result['case_number']}, Created: {$check_result['created_at']}, Updated: {$check_result['updated_at']}");
        } else {
            error_log("DEBUG: Case not found after insert - ID: $case_id");
        }
    } else {
        error_log("DEBUG: lastInsertId returned 0 or null: $case_id");
        
        // Thử lấy case vừa tạo bằng case_number
        $check_stmt = $pdo->prepare("SELECT id, case_number, created_at, updated_at FROM internal_cases WHERE case_number = ? ORDER BY id DESC LIMIT 1");
        $check_stmt->execute([$case_number]);
        $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($check_result) {
            $case_id = $check_result['id'];
            error_log("DEBUG: Found case by case_number - ID: {$check_result['id']}, Number: {$check_result['case_number']}, Created: {$check_result['created_at']}, Updated: {$check_result['updated_at']}");
            
            // Nếu ID = 0, thử update ID
            if ($case_id == 0) {
                error_log("DEBUG: Case has ID = 0, trying to fix");
                
                // Lấy ID cao nhất + 1
                $max_stmt = $pdo->prepare("SELECT MAX(id) as max_id FROM internal_cases WHERE id > 0");
                $max_stmt->execute();
                $max_result = $max_stmt->fetch(PDO::FETCH_ASSOC);
                $new_id = ($max_result['max_id'] ?? 0) + 1;
                
                // Update ID
                $update_stmt = $pdo->prepare("UPDATE internal_cases SET id = ? WHERE case_number = ? AND id = 0");
                $update_result = $update_stmt->execute([$new_id, $case_number]);
                
                if ($update_result) {
                    $case_id = $new_id;
                    error_log("DEBUG: Fixed ID from 0 to $new_id");
                } else {
                    error_log("DEBUG: Failed to fix ID");
                }
            }
        } else {
            error_log("DEBUG: No case found with case_number: $case_number");
        }
    }
    
    // Ghi log
    try {
        $log_message = "Tạo case nội bộ mới: $case_number";
        $log_stmt = $pdo->prepare("
            INSERT INTO user_activity_logs (user_id, activity, details, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $log_stmt->execute([$current_user_id, 'create_case', $log_message]);
    } catch (Exception $e) {
        // Ignore logging errors - don't fail the case creation
    }
    
    // Gửi thông báo cho người xử lý (handler)
    try {
        // Lấy thông tin người yêu cầu
        $requester_stmt = $pdo->prepare("SELECT fullname FROM staffs WHERE id = ?");
        $requester_stmt->execute([$requester_id]);
        $requester = $requester_stmt->fetch(PDO::FETCH_ASSOC);
        $requester_name = $requester ? $requester['fullname'] : '';
        
        // Chuẩn bị dữ liệu cho thông báo
        $notification_data = [
            'case_id' => $case_id,
            'case_number' => $case_number,
            'handler_id' => $handler_id,
            'issue_title' => $issue_title,
            'requester_name' => $requester_name
        ];
        
        // Gọi API tạo thông báo
        $notification_url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/create_internal_case_notification.php';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $notification_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notification_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Requested-With: XMLHttpRequest'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $notification_response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $notification_result = json_decode($notification_response, true);
            if ($notification_result && $notification_result['success']) {
                error_log("DEBUG: Notification sent successfully to handler ID: $handler_id");
            } else {
                error_log("DEBUG: Notification failed: " . ($notification_result['message'] ?? 'Unknown error'));
            }
        } else {
            error_log("DEBUG: Notification HTTP error: $http_code");
        }
        
    } catch (Exception $e) {
        // Ignore notification errors - don't fail the case creation
        error_log("DEBUG: Notification error: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Tạo case thành công',
        'case_id' => $case_id,
        'case_number' => $case_number,
        'debug_info' => [
            'inserted_id' => $case_id,
            'case_number' => $case_number,
            'current_user_id' => $current_user_id
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

function generateCaseNumber($pdo) {
    $year = date('y'); // 2 số cuối của năm (25)
    $month = date('m'); // tháng với 2 chữ số (07)
    
    // Lấy số thứ tự cao nhất hiện có trong database
    try {
        $stmt = $pdo->prepare("
            SELECT MAX(CAST(SUBSTRING(case_number, -3) AS UNSIGNED)) as max_sequence 
            FROM internal_cases 
            WHERE case_number LIKE 'CNB.{$year}{$month}%'
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        $max_sequence = $result['max_sequence'] ?? 0;
        $count = $max_sequence + 1;
        
        // Debug log
        error_log("DEBUG: generateCaseNumber - year: $year, month: $month, max_sequence: $max_sequence, count: $count");
    } catch (Exception $e) {
        // Nếu lỗi, sử dụng số sequence mặc định
        $count = 1;
        error_log("DEBUG: generateCaseNumber error - " . $e->getMessage());
    }
    
    // Format số thứ tự thành 3 chữ số (001, 002, ...)
    $sequence = str_pad($count, 3, '0', STR_PAD_LEFT);
    $case_number = "CNB.{$year}{$month}{$sequence}";
    
    error_log("DEBUG: Generated case number: $case_number");
    return $case_number;
}
?> 