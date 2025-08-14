<?php
require_once '../config/db.php';
require_once '../includes/session.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get the current year and month
    $currentYear = date('Y');
    $currentMonth = date('m');
    
    // Get the last task number for current year/month
    $sql = "SELECT task_number FROM maintenance_tasks 
            WHERE task_number LIKE ? 
            ORDER BY task_number DESC 
            LIMIT 1";
    
    $pattern = "TASK{$currentYear}{$currentMonth}%";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$pattern]);
    $lastTask = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($lastTask) {
        // Extract the number part and increment
        $lastNumber = intval(substr($lastTask['task_number'], -3));
        $nextNumber = $lastNumber + 1;
    } else {
        // First task for this year/month
        $nextNumber = 1;
    }
    
    // Format the new task number
    $taskCode = sprintf("TASK%s%s%03d", $currentYear, $currentMonth, $nextNumber);
    
    echo json_encode([
        'success' => true,
        'task_number' => $taskCode
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_next_maintenance_task_number_simple.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu']);
} catch (Exception $e) {
    error_log("Error in get_next_maintenance_task_number_simple.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
?>
