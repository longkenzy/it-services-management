<?php
header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../config/db.php';

if (!isset($_SESSION[user_id'])) {
    http_response_code(41  echo json_encode([success => false, 'error =>Unauthorized]);  exit;
}

require_once../config/db.php;

if($_SERVER['REQUEST_METHOD'] !==GET
    http_response_code(45  echo json_encode([success => false, error' => 'Method not allowed]);
    exit;
}

try[object Object]
    $partner_id = $_GET['partner_id'] ?? null;
    
    if (!$partner_id) {
        echo json_encode([success => false, 'error' => 'Partner ID is required']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT contact_person, contact_phone FROM partner_companies WHERE id = ?");
    $stmt->execute([$partner_id]);
    $contact = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($contact) {
        echo json_encode([
           success' => true,
          contacts' => [$contact]
        ]);
    } else {
        echo json_encode([
           success' => true,
           contacts' =>         ]);
    }
    
} catch (Exception $e) {
    http_response_code(50  echo json_encode(
        success' => false,
        error => Server error:  . $e->getMessage()
    ]);
}
?> 