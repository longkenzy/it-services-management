<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$case_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($case_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid case ID']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM internal_cases WHERE id = ?");
$stmt->execute([$case_id]);
$case = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$case) {
    echo json_encode(['success' => false, 'error' => 'Case not found']);
    exit;
}

echo json_encode(['success' => true, 'data' => $case]); 