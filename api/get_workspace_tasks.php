<?php
header('Content-Type: application/json');
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    file_put_contents(__DIR__ . '/debug_workspace.txt', "No user_id in session\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$user_id = $_SESSION['user_id'];
file_put_contents(__DIR__ . '/debug_workspace.txt', "Current user_id: $user_id\n", FILE_APPEND);

try {
    $stmt = $pdo->prepare('SELECT * FROM deployment_cases WHERE assigned_to = ? ORDER BY created_at DESC');
    $stmt->execute([$user_id]);
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    file_put_contents(__DIR__ . '/debug_workspace.txt', "Cases found: " . count($cases) . "\n", FILE_APPEND);
    $data = [];
    foreach ($cases as $row) {
        // Lấy customer_id từ deployment_requests
        $customer_name = '';
        if (!empty($row['deployment_request_id'])) {
            $stmt2 = $pdo->prepare('SELECT customer_id FROM deployment_requests WHERE id = ? LIMIT 1');
            $stmt2->execute([$row['deployment_request_id']]);
            $req = $stmt2->fetch(PDO::FETCH_ASSOC);
            if ($req && !empty($req['customer_id'])) {
                $stmt3 = $pdo->prepare('SELECT name FROM eu_companies WHERE id = ? LIMIT 1');
                $stmt3->execute([$req['customer_id']]);
                $company = $stmt3->fetch(PDO::FETCH_ASSOC);
                if ($company && !empty($company['name'])) {
                    $customer_name = $company['name'];
                }
            }
        }
        $data[] = [
            'case_code'     => $row['case_code'] ?? '',
            'level'         => 'Case',
            'case_type'     => $row['request_type'] ?? '',
            'service_type'  => $row['case_description'] ?? '',
            'customer_name' => $customer_name,
            'start_date'    => $row['start_date'] ?? '',
            'end_date'      => $row['end_date'] ?? '',
            'status'        => $row['status'] ?? '',
            'assigned_to'   => $row['assigned_to'] ?? '', // debug
        ];
    }
    file_put_contents(__DIR__ . '/debug_workspace.txt', "Data returned: " . json_encode($data) . "\n", FILE_APPEND);
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/debug_workspace.txt', "Error: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 