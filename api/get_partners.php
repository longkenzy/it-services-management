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
    // Get partners from database
    $stmt = $pdo->prepare("SELECT id, name, contact_person, contact_phone FROM partner_companies ORDER BY name ASC");
    $stmt->execute();
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $partners
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
