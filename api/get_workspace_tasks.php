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
$user_role = getCurrentUserRole();
$status_filter = $_GET['status'] ?? 'processing';

file_put_contents(__DIR__ . '/debug_workspace.txt', "Current user_id: $user_id, username: " . getCurrentUsername() . ", role: " . $user_role . ", status_filter: $status_filter\n", FILE_APPEND);

try {
    $data = [];
    
    // Kiểm tra quyền admin - admin có thể thấy tất cả, IT chỉ thấy của mình
    $is_admin = ($user_role === 'admin');
    
    // Xử lý deployment_cases
    $case_where_conditions = [];
    $case_params = [];
    
    if ($status_filter === 'processing') {
        // Hiển thị các case có trạng thái không phải "Hoàn thành"
        if ($is_admin) {
            $case_where_conditions[] = "status != 'Hoàn thành'";
        } else {
            $case_where_conditions[] = "assigned_to = ? AND status != 'Hoàn thành'";
            $case_params[] = $user_id;
        }
    } elseif ($status_filter === 'done_this_month') {
        // Hiển thị các case hoàn thành trong tháng hiện tại
        if ($is_admin) {
            $case_where_conditions[] = "status = 'Hoàn thành' AND MONTH(end_date) = MONTH(CURRENT_DATE()) AND YEAR(end_date) = YEAR(CURRENT_DATE())";
        } else {
            $case_where_conditions[] = "assigned_to = ? AND status = 'Hoàn thành' AND MONTH(end_date) = MONTH(CURRENT_DATE()) AND YEAR(end_date) = YEAR(CURRENT_DATE())";
            $case_params[] = $user_id;
        }
    } elseif ($status_filter === 'done_last_month') {
        // Hiển thị các case hoàn thành trong các tháng trước
        if ($is_admin) {
            $case_where_conditions[] = "status = 'Hoàn thành' AND (MONTH(end_date) != MONTH(CURRENT_DATE()) OR YEAR(end_date) != YEAR(CURRENT_DATE()))";
        } else {
            $case_where_conditions[] = "assigned_to = ? AND status = 'Hoàn thành' AND (MONTH(end_date) != MONTH(CURRENT_DATE()) OR YEAR(end_date) != YEAR(CURRENT_DATE()))";
            $case_params[] = $user_id;
        }
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
            
            // Lấy tên người được gán
            $assigned_name = '';
            if (!empty($row['assigned_to'])) {
                $stmt4 = $pdo->prepare('SELECT fullname FROM staffs WHERE id = ? LIMIT 1');
                $stmt4->execute([$row['assigned_to']]);
                $staff = $stmt4->fetch(PDO::FETCH_ASSOC);
                if ($staff && !empty($staff['fullname'])) {
                    $assigned_name = $staff['fullname'];
                }
            }
            
            $data[] = [
                'id'            => $row['id'] ?? '',
                'case_code'     => $row['case_code'] ?? '',
                'level'         => 'Case',
                'case_type'     => $row['request_type'] ?? '',
                'service_type'  => $row['case_description'] ?? '',
                'customer_name' => $customer_name,
                'start_date'    => $row['start_date'] ?? '',
                'end_date'      => $row['end_date'] ?? '',
                'status'        => $row['status'] ?? '',
                'assigned_to'   => $row['assigned_to'] ?? '',
                'assigned_name' => $assigned_name,
            ];
        }
    }

    // Xử lý deployment_tasks
    $task_where_conditions = [];
    $task_params = [];
    
    if ($status_filter === 'processing') {
        // Hiển thị các task có trạng thái không phải "Hoàn thành"
        if ($is_admin) {
            $task_where_conditions[] = "status != 'Hoàn thành'";
        } else {
            $task_where_conditions[] = "assignee_id = ? AND status != 'Hoàn thành'";
            $task_params[] = $user_id;
        }
    } elseif ($status_filter === 'done_this_month') {
        // Hiển thị các task hoàn thành trong tháng hiện tại
        if ($is_admin) {
            $task_where_conditions[] = "status = 'Hoàn thành' AND MONTH(end_date) = MONTH(CURRENT_DATE()) AND YEAR(end_date) = YEAR(CURRENT_DATE())";
        } else {
            $task_where_conditions[] = "assignee_id = ? AND status = 'Hoàn thành' AND MONTH(end_date) = MONTH(CURRENT_DATE()) AND YEAR(end_date) = YEAR(CURRENT_DATE())";
            $task_params[] = $user_id;
        }
    } elseif ($status_filter === 'done_last_month') {
        // Hiển thị các task hoàn thành trong các tháng trước
        if ($is_admin) {
            $task_where_conditions[] = "status = 'Hoàn thành' AND (MONTH(end_date) != MONTH(CURRENT_DATE()) OR YEAR(end_date) != YEAR(CURRENT_DATE()))";
        } else {
            $task_where_conditions[] = "assignee_id = ? AND status = 'Hoàn thành' AND (MONTH(end_date) != MONTH(CURRENT_DATE()) OR YEAR(end_date) != YEAR(CURRENT_DATE()))";
            $task_params[] = $user_id;
        }
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
            
            // Lấy tên người được gán
            $assigned_name = '';
            if (!empty($row['assignee_id'])) {
                $stmt4 = $pdo->prepare('SELECT fullname FROM staffs WHERE id = ? LIMIT 1');
                $stmt4->execute([$row['assignee_id']]);
                $staff = $stmt4->fetch(PDO::FETCH_ASSOC);
                if ($staff && !empty($staff['fullname'])) {
                    $assigned_name = $staff['fullname'];
                }
            }
            
            $data[] = [
                'id'            => $row['id'] ?? '',
                'case_code'     => $row['task_number'] ?? '',
                'level'         => 'Task',
                'case_type'     => $row['template_name'] ?? '',
                'service_type'  => $row['task_description'] ?? '',
                'customer_name' => $customer_name,
                'start_date'    => $row['start_date'] ?? '',
                'end_date'      => $row['end_date'] ?? '',
                'status'        => $row['status'] ?? '',
                'assigned_to'   => $row['assignee_id'] ?? '',
                'assigned_name' => $assigned_name,
            ];
        }
    }

    // Xử lý maintenance_cases
    $maintenance_case_where_conditions = [];
    $maintenance_case_params = [];
    
    if ($status_filter === 'processing') {
        if ($is_admin) {
            $maintenance_case_where_conditions[] = "status != 'Hoàn thành'";
        } else {
            $maintenance_case_where_conditions[] = "assigned_to = ? AND status != 'Hoàn thành'";
            $maintenance_case_params[] = $user_id;
        }
    } elseif ($status_filter === 'done_this_month') {
        if ($is_admin) {
            $maintenance_case_where_conditions[] = "status = 'Hoàn thành' AND MONTH(end_date) = MONTH(CURRENT_DATE()) AND YEAR(end_date) = YEAR(CURRENT_DATE())";
        } else {
            $maintenance_case_where_conditions[] = "assigned_to = ? AND status = 'Hoàn thành' AND MONTH(end_date) = MONTH(CURRENT_DATE()) AND YEAR(end_date) = YEAR(CURRENT_DATE())";
            $maintenance_case_params[] = $user_id;
        }
    } elseif ($status_filter === 'done_last_month') {
        if ($is_admin) {
            $maintenance_case_where_conditions[] = "status = 'Hoàn thành' AND (MONTH(end_date) != MONTH(CURRENT_DATE()) OR YEAR(end_date) != YEAR(CURRENT_DATE()))";
        } else {
            $maintenance_case_where_conditions[] = "assigned_to = ? AND status = 'Hoàn thành' AND (MONTH(end_date) != MONTH(CURRENT_DATE()) OR YEAR(end_date) != YEAR(CURRENT_DATE()))";
            $maintenance_case_params[] = $user_id;
        }
    }
    
    if (!empty($maintenance_case_where_conditions)) {
        $maintenance_case_sql = 'SELECT * FROM maintenance_cases WHERE ' . implode(' AND ', $maintenance_case_where_conditions) . ' ORDER BY start_date DESC';
        $stmt = $pdo->prepare($maintenance_case_sql);
        $stmt->execute($maintenance_case_params);
        $maintenance_cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($maintenance_cases as $row) {
            // Lấy customer_id từ maintenance_requests, rồi lấy tên từ partner_companies
            $customer_name = '';
            if (!empty($row['maintenance_request_id'])) {
                $stmt2 = $pdo->prepare('SELECT customer_id FROM maintenance_requests WHERE id = ? LIMIT 1');
                $stmt2->execute([$row['maintenance_request_id']]);
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
            
            // Lấy tên người được gán
            $assigned_name = '';
            if (!empty($row['assigned_to'])) {
                $stmt4 = $pdo->prepare('SELECT fullname FROM staffs WHERE id = ? LIMIT 1');
                $stmt4->execute([$row['assigned_to']]);
                $staff = $stmt4->fetch(PDO::FETCH_ASSOC);
                if ($staff && !empty($staff['fullname'])) {
                    $assigned_name = $staff['fullname'];
                }
            }
            
            $data[] = [
                'id'            => $row['id'] ?? '',
                'case_code'     => $row['case_code'] ?? '',
                'level'         => 'Case Bảo trì',
                'case_type'     => $row['request_type'] ?? '',
                'service_type'  => $row['case_description'] ?? '',
                'customer_name' => $customer_name,
                'start_date'    => $row['start_date'] ?? '',
                'end_date'      => $row['end_date'] ?? '',
                'status'        => $row['status'] ?? '',
                'assigned_to'   => $row['assigned_to'] ?? '',
                'assigned_name' => $assigned_name,
            ];
        }
    }

    // Xử lý maintenance_tasks
    $maintenance_task_where_conditions = [];
    $maintenance_task_params = [];
    
    if ($status_filter === 'processing') {
        if ($is_admin) {
            $maintenance_task_where_conditions[] = "status != 'Hoàn thành'";
        } else {
            $maintenance_task_where_conditions[] = "assigned_to = ? AND status != 'Hoàn thành'";
            $maintenance_task_params[] = $user_id;
        }
    } elseif ($status_filter === 'done_this_month') {
        if ($is_admin) {
            $maintenance_task_where_conditions[] = "status = 'Hoàn thành' AND MONTH(end_date) = MONTH(CURRENT_DATE()) AND YEAR(end_date) = YEAR(CURRENT_DATE())";
        } else {
            $maintenance_task_where_conditions[] = "assigned_to = ? AND status = 'Hoàn thành' AND MONTH(end_date) = MONTH(CURRENT_DATE()) AND YEAR(end_date) = YEAR(CURRENT_DATE())";
            $maintenance_task_params[] = $user_id;
        }
    } elseif ($status_filter === 'done_last_month') {
        if ($is_admin) {
            $maintenance_task_where_conditions[] = "status = 'Hoàn thành' AND (MONTH(end_date) != MONTH(CURRENT_DATE()) OR YEAR(end_date) != YEAR(CURRENT_DATE()))";
        } else {
            $maintenance_task_where_conditions[] = "assigned_to = ? AND status = 'Hoàn thành' AND (MONTH(end_date) != MONTH(CURRENT_DATE()) OR YEAR(end_date) != YEAR(CURRENT_DATE()))";
            $maintenance_task_params[] = $user_id;
        }
    }
    
    if (!empty($maintenance_task_where_conditions)) {
        $maintenance_task_sql = 'SELECT * FROM maintenance_tasks WHERE ' . implode(' AND ', $maintenance_task_where_conditions) . ' ORDER BY start_date DESC';
        $stmt = $pdo->prepare($maintenance_task_sql);
        $stmt->execute($maintenance_task_params);
        $maintenance_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($maintenance_tasks as $row) {
            // Lấy customer_id từ maintenance_requests qua maintenance_cases
            $customer_name = '';
            $maintenance_case_id = $row['maintenance_case_id'] ?? null;
            if ($maintenance_case_id) {
                $stmt_case = $pdo->prepare('SELECT maintenance_request_id FROM maintenance_cases WHERE id = ? LIMIT 1');
                $stmt_case->execute([$maintenance_case_id]);
                $case = $stmt_case->fetch(PDO::FETCH_ASSOC);
                if ($case && !empty($case['maintenance_request_id'])) {
                    $stmt2 = $pdo->prepare('SELECT customer_id FROM maintenance_requests WHERE id = ? LIMIT 1');
                    $stmt2->execute([$case['maintenance_request_id']]);
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
            
            // Lấy tên người được gán
            $assigned_name = '';
            if (!empty($row['assigned_to'])) {
                $stmt4 = $pdo->prepare('SELECT fullname FROM staffs WHERE id = ? LIMIT 1');
                $stmt4->execute([$row['assigned_to']]);
                $staff = $stmt4->fetch(PDO::FETCH_ASSOC);
                if ($staff && !empty($staff['fullname'])) {
                    $assigned_name = $staff['fullname'];
                }
            }
            
            $data[] = [
                'id'            => $row['id'] ?? '',
                'case_code'     => $row['task_number'] ?? '',
                'level'         => 'Task Bảo trì',
                'case_type'     => $row['template_name'] ?? '',
                'service_type'  => $row['task_description'] ?? '',
                'customer_name' => $customer_name,
                'start_date'    => $row['start_date'] ?? '',
                'end_date'      => $row['end_date'] ?? '',
                'status'        => $row['status'] ?? '',
                'assigned_to'   => $row['assigned_to'] ?? '',
                'assigned_name' => $assigned_name,
            ];
        }
    }

    // Xử lý internal_cases
    $internal_case_where_conditions = [];
    $internal_case_params = [];
    
    if ($status_filter === 'processing') {
        if ($is_admin) {
            $internal_case_where_conditions[] = "status != 'completed'";
        } else {
            $internal_case_where_conditions[] = "handler_id = ? AND status != 'completed'";
            $internal_case_params[] = $user_id;
        }
    } elseif ($status_filter === 'done_this_month') {
        if ($is_admin) {
            $internal_case_where_conditions[] = "status = 'completed' AND MONTH(completed_at) = MONTH(CURRENT_DATE()) AND YEAR(completed_at) = YEAR(CURRENT_DATE())";
        } else {
            $internal_case_where_conditions[] = "handler_id = ? AND status = 'completed' AND MONTH(completed_at) = MONTH(CURRENT_DATE()) AND YEAR(completed_at) = YEAR(CURRENT_DATE())";
            $internal_case_params[] = $user_id;
        }
    } elseif ($status_filter === 'done_last_month') {
        if ($is_admin) {
            $internal_case_where_conditions[] = "status = 'completed' AND (MONTH(completed_at) != MONTH(CURRENT_DATE()) OR YEAR(completed_at) != YEAR(CURRENT_DATE()))";
        } else {
            $internal_case_where_conditions[] = "handler_id = ? AND status = 'completed' AND (MONTH(completed_at) != MONTH(CURRENT_DATE()) OR YEAR(completed_at) != YEAR(CURRENT_DATE()))";
            $internal_case_params[] = $user_id;
        }
    }
    
    if (!empty($internal_case_where_conditions)) {
        $internal_case_sql = 'SELECT * FROM internal_cases WHERE ' . implode(' AND ', $internal_case_where_conditions) . ' ORDER BY start_date DESC';
        $stmt = $pdo->prepare($internal_case_sql);
        $stmt->execute($internal_case_params);
        $internal_cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($internal_cases as $row) {
            // Map status từ enum sang tiếng Việt
            $status_map = [
                'pending' => 'Tiếp nhận',
                'in_progress' => 'Đang xử lý',
                'completed' => 'Hoàn thành',
                'cancelled' => 'Huỷ'
            ];
            
            // Lấy tên người được gán
            $assigned_name = '';
            if (!empty($row['handler_id'])) {
                $stmt4 = $pdo->prepare('SELECT fullname FROM staffs WHERE id = ? LIMIT 1');
                $stmt4->execute([$row['handler_id']]);
                $staff = $stmt4->fetch(PDO::FETCH_ASSOC);
                if ($staff && !empty($staff['fullname'])) {
                    $assigned_name = $staff['fullname'];
                }
            }
            
            $data[] = [
                'id'            => $row['id'] ?? '',
                'case_code'     => $row['case_number'] ?? '',
                'level'         => 'Case Nội bộ',
                'case_type'     => $row['case_type'] ?? '',
                'service_type'  => $row['issue_description'] ?? '',
                'customer_name' => 'Nội bộ',
                'start_date'    => $row['start_date'] ?? '',
                'end_date'      => $row['due_date'] ?? '',
                'status'        => $status_map[$row['status']] ?? $row['status'],
                'assigned_to'   => $row['handler_id'] ?? '',
                'assigned_name' => $assigned_name,
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