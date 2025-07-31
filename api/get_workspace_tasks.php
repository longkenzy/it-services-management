<?php
header('Content-Type: application/json');
require_once '../includes/session.php';
require_once '../config/db.php';

if (!isLoggedIn()) {
    file_put_contents(__DIR__ . '/debug_workspace.txt', "No user_id in session\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$user_id = getCurrentUserId();
$status_filter = $_GET['status'] ?? 'processing';

file_put_contents(__DIR__ . '/debug_workspace.txt', "Current user_id: $user_id, username: " . getCurrentUsername() . ", role: " . getCurrentUserRole() . ", status_filter: $status_filter\n", FILE_APPEND);

try {
    $data = [];
    
    // Xử lý deployment_cases
    $case_where_conditions = [];
    $case_params = [$user_id];
    
    if ($status_filter === 'processing') {
        // Hiển thị các case có trạng thái không phải "Hoàn thành"
        $case_where_conditions[] = "assigned_to = ? AND status != 'Hoàn thành'";
    } elseif ($status_filter === 'done_this_month') {
        // Hiển thị các case hoàn thành trong tháng hiện tại
        $case_where_conditions[] = "assigned_to = ? AND status = 'Hoàn thành' AND MONTH(end_date) = MONTH(CURRENT_DATE()) AND YEAR(end_date) = YEAR(CURRENT_DATE())";
    } elseif ($status_filter === 'done_last_month') {
        // Hiển thị các case hoàn thành trong các tháng trước
        $case_where_conditions[] = "assigned_to = ? AND status = 'Hoàn thành' AND (MONTH(end_date) != MONTH(CURRENT_DATE()) OR YEAR(end_date) != YEAR(CURRENT_DATE()))";
    }
    
    if (!empty($case_where_conditions)) {
        $case_sql = 'SELECT * FROM deployment_cases WHERE ' . implode(' AND ', $case_where_conditions) . ' ORDER BY start_date DESC';
        $stmt = $pdo->prepare($case_sql);
        $stmt->execute($case_params);
        $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($cases as $row) {
            // Lấy customer_id từ deployment_requests, rồi lấy tên từ partner_companies
            $customer_name = '';
            if (!empty($row['deployment_request_id'])) {
                $stmt2 = $pdo->prepare('SELECT customer_id FROM deployment_requests WHERE id = ? LIMIT 1');
                $stmt2->execute([$row['deployment_request_id']]);
                $req = $stmt2->fetch(PDO::FETCH_ASSOC);
                if ($req && !empty($req['customer_id'])) {
                    $stmt3 = $pdo->prepare('SELECT name FROM partner_companies WHERE id = ? LIMIT 1');
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
                'assigned_to'   => $row['assigned_to'] ?? '',
            ];
        }
    }

    // Xử lý deployment_tasks
    $task_where_conditions = [];
    $task_params = [$user_id];
    
    if ($status_filter === 'processing') {
        // Hiển thị các task có trạng thái không phải "Hoàn thành"
        $task_where_conditions[] = "assignee_id = ? AND status != 'Hoàn thành'";
    } elseif ($status_filter === 'done_this_month') {
        // Hiển thị các task hoàn thành trong tháng hiện tại
        $task_where_conditions[] = "assignee_id = ? AND status = 'Hoàn thành' AND MONTH(end_date) = MONTH(CURRENT_DATE()) AND YEAR(end_date) = YEAR(CURRENT_DATE())";
    } elseif ($status_filter === 'done_last_month') {
        // Hiển thị các task hoàn thành trong các tháng trước
        $task_where_conditions[] = "assignee_id = ? AND status = 'Hoàn thành' AND (MONTH(end_date) != MONTH(CURRENT_DATE()) OR YEAR(end_date) != YEAR(CURRENT_DATE()))";
    }
    
    if (!empty($task_where_conditions)) {
        $task_sql = 'SELECT * FROM deployment_tasks WHERE ' . implode(' AND ', $task_where_conditions) . ' ORDER BY start_date DESC';
        $stmt = $pdo->prepare($task_sql);
        $stmt->execute($task_params);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($tasks as $row) {
            // Lấy customer_id từ deployment_requests qua deployment_cases
            $customer_name = '';
            $deployment_case_id = $row['deployment_case_id'] ?? null;
            if ($deployment_case_id) {
                $stmt_case = $pdo->prepare('SELECT deployment_request_id FROM deployment_cases WHERE id = ? LIMIT 1');
                $stmt_case->execute([$deployment_case_id]);
                $case = $stmt_case->fetch(PDO::FETCH_ASSOC);
                if ($case && !empty($case['deployment_request_id'])) {
                    $stmt2 = $pdo->prepare('SELECT customer_id FROM deployment_requests WHERE id = ? LIMIT 1');
                    $stmt2->execute([$case['deployment_request_id']]);
                    $req = $stmt2->fetch(PDO::FETCH_ASSOC);
                    if ($req && !empty($req['customer_id'])) {
                        $stmt3 = $pdo->prepare('SELECT name FROM partner_companies WHERE id = ? LIMIT 1');
                        $stmt3->execute([$req['customer_id']]);
                        $company = $stmt3->fetch(PDO::FETCH_ASSOC);
                        if ($company && !empty($company['name'])) {
                            $customer_name = $company['name'];
                        }
                    }
                }
            }
            $data[] = [
                'case_code'     => $row['task_number'] ?? '',
                'level'         => 'Task',
                'case_type'     => $row['template_name'] ?? '',
                'service_type'  => $row['task_description'] ?? '',
                'customer_name' => $customer_name,
                'start_date'    => $row['start_date'] ?? '',
                'end_date'      => $row['end_date'] ?? '',
                'status'        => $row['status'] ?? '',
                'assigned_to'   => $row['assignee_id'] ?? '',
            ];
        }
    }

    // Sắp xếp tổng hợp theo ngày bắt đầu (mới nhất trước)
    usort($data, function($a, $b) {
        $dateA = !empty($a['start_date']) ? strtotime($a['start_date']) : 0;
        $dateB = !empty($b['start_date']) ? strtotime($b['start_date']) : 0;
        return $dateB - $dateA; // Sắp xếp giảm dần (mới nhất trước)
    });

    file_put_contents(__DIR__ . '/debug_workspace.txt', "Data returned: " . json_encode($data) . "\n", FILE_APPEND);
    echo json_encode(['success' => true, 'data' => $data]);
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/debug_workspace.txt', "Error: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 