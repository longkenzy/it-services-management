<?php
require_once '../config/db.php';
require_once '../includes/session.php';

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Query để lấy danh sách phòng ban
    $sql = "SELECT DISTINCT department FROM staffs WHERE department IS NOT NULL AND department != '' ORDER BY department";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $departments
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_departments.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error in get_departments.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'General error: ' . $e->getMessage()
    ]);
}
?>
