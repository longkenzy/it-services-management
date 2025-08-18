<?php
require_once '../config/db.php';
require_once '../includes/session.php';

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

try {
    // Query để lấy danh sách khách hàng từ bảng partner_companies
    $sql = "SELECT DISTINCT name FROM partner_companies WHERE name IS NOT NULL AND name != '' ORDER BY name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $customers
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_customers.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("General error in get_customers.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'General error: ' . $e->getMessage()
    ]);
}
?>
