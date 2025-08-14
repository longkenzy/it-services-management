<?php
/**
 * API: Phê duyệt/từ chối đơn nghỉ phép (theo phòng ban)
 * Method: POST
 * Parameters: request_id, action (approve/reject), comment (optional)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/session.php';
require_once '../config/db.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

try {
    $current_user = getCurrentUser();
    
    // Lấy dữ liệu từ request
    $request_id = $_POST['request_id'] ?? null;
    $action = $_POST['action'] ?? null; // approve hoặc reject
    $comment = $_POST['comment'] ?? '';
    
    if (!$request_id || !$action) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin cần thiết']);
        exit;
    }
    
    // Lấy thông tin đơn nghỉ phép
    $stmt = $pdo->prepare("SELECT lr.*, s.department as requester_department FROM leave_requests lr 
                           LEFT JOIN staffs s ON lr.requester_id = s.id 
                           WHERE lr.id = ?");
    $stmt->execute([$request_id]);
    $leave_request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$leave_request) {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn nghỉ phép']);
        exit;
    }
    
    // Xử lý theo phòng ban và role
    $current_status = $leave_request['status'];
    $user_role = $current_user['role'];
    $requester_department = $leave_request['requester_department'];
    $result = false;
    
    // Logic phê duyệt theo phòng ban
    if ($requester_department && strpos($requester_department, 'HR') !== false) {
        // HR: chỉ hr_leader duyệt
        if ($user_role !== 'hr_leader') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Chỉ HR Leader mới có quyền duyệt đơn của phòng HR']);
            exit;
        }
        
        if ($current_status !== 'Chờ phê duyệt') {
            echo json_encode(['success' => false, 'message' => 'Đơn nghỉ phép không ở trạng thái chờ phê duyệt']);
            exit;
        }
        
        if ($action === 'approve') {
            $new_status = 'Đã phê duyệt';
            $approval_message = 'Đơn nghỉ phép đã được HR Leader phê duyệt';
            
            $stmt = $pdo->prepare("UPDATE leave_requests SET 
                status = ?, 
                approved_by = ?, 
                approved_at = NOW(), 
                approval_comment = ? 
                WHERE id = ?");
            $result = $stmt->execute([$new_status, $current_user['id'], $comment, $request_id]);
            
        } elseif ($action === 'reject') {
            $new_status = 'Từ chối';
            $approval_message = 'Đơn nghỉ phép đã bị HR Leader từ chối';
            
            $stmt = $pdo->prepare("UPDATE leave_requests SET 
                status = ?, 
                approved_by = ?, 
                approved_at = NOW(), 
                approval_comment = ? 
                WHERE id = ?");
            $result = $stmt->execute([$new_status, $current_user['id'], $comment, $request_id]);
        }
        
    } elseif ($requester_department && strpos($requester_department, 'IT') !== false) {
        // IT: cấp 1 it_leader, cấp 2 hr
        if ($user_role === 'it_leader') {
            // Cấp 1: IT Leader
            if ($current_status !== 'Chờ phê duyệt') {
                echo json_encode(['success' => false, 'message' => 'Đơn nghỉ phép không ở trạng thái chờ phê duyệt']);
                exit;
            }
            
            if ($action === 'approve') {
                $new_status = 'IT Leader đã phê duyệt';
                $approval_message = 'Đơn nghỉ phép đã được IT Leader phê duyệt (chờ HR phê duyệt cuối)';
                
                $stmt = $pdo->prepare("UPDATE leave_requests SET 
                    status = ?, 
                    admin_approved_by = ?, 
                    admin_approved_at = NOW(), 
                    admin_approval_comment = ? 
                    WHERE id = ?");
                $result = $stmt->execute([$new_status, $current_user['id'], $comment, $request_id]);
                
                if ($result) {
                    // Gửi thông báo cho HR
                    try {
                        $hr_ids = [];
                        $stmt = $pdo->prepare("SELECT id FROM staffs WHERE role = 'hr'");
                        $stmt->execute();
                        while ($hr = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $hr_ids[] = $hr['id'];
                        }
                        
                        foreach ($hr_ids as $hr_id) {
                            $notification_sql = "INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, ?, ?)";
                            $notification_stmt = $pdo->prepare($notification_sql);
                            $notification_stmt->execute([
                                $hr_id,
                                'Đơn nghỉ phép IT cần phê duyệt (Cấp 2)',
                                "Đơn nghỉ phép {$leave_request['request_code']} đã được IT Leader phê duyệt, cần HR phê duyệt cuối (Cấp 2)",
                                'leave_request',
                                $request_id
                            ]);
                        }
                    } catch (Exception $e) {
                        // Bỏ qua lỗi notification
                    }
                }
                
            } elseif ($action === 'reject') {
                $new_status = 'Từ chối bởi IT Leader';
                $approval_message = 'Đơn nghỉ phép đã bị IT Leader từ chối';
                
                $stmt = $pdo->prepare("UPDATE leave_requests SET 
                    status = ?, 
                    admin_approved_by = ?, 
                    admin_approved_at = NOW(), 
                    admin_approval_comment = ? 
                    WHERE id = ?");
                $result = $stmt->execute([$new_status, $current_user['id'], $comment, $request_id]);
            }
            
        } elseif ($user_role === 'hr') {
            // Cấp 2: HR
            if ($current_status !== 'IT Leader đã phê duyệt') {
                echo json_encode(['success' => false, 'message' => 'Đơn nghỉ phép chưa được IT Leader phê duyệt hoặc không ở trạng thái phù hợp']);
                exit;
            }
            
            if ($action === 'approve') {
                $new_status = 'Đã phê duyệt';
                $approval_message = 'Đơn nghỉ phép đã được HR phê duyệt cuối cùng';
                
                $stmt = $pdo->prepare("UPDATE leave_requests SET 
                    status = ?, 
                    hr_approved_by = ?, 
                    hr_approved_at = NOW(), 
                    hr_approval_comment = ? 
                    WHERE id = ?");
                $result = $stmt->execute([$new_status, $current_user['id'], $comment, $request_id]);
                
            } elseif ($action === 'reject') {
                $new_status = 'Từ chối bởi HR';
                $approval_message = 'Đơn nghỉ phép đã bị HR từ chối';
                
                $stmt = $pdo->prepare("UPDATE leave_requests SET 
                    status = ?, 
                    hr_approved_by = ?, 
                    hr_approved_at = NOW(), 
                    hr_approval_comment = ? 
                    WHERE id = ?");
                $result = $stmt->execute([$new_status, $current_user['id'], $comment, $request_id]);
            }
        } else {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền phê duyệt đơn của phòng IT']);
            exit;
        }
        
    } elseif ($requester_department && strpos($requester_department, 'SALE') !== false) {
        // SALE: cấp 1 sale_leader, cấp 2 hr
        if ($user_role === 'sale_leader') {
            // Cấp 1: Sale Leader
            if ($current_status !== 'Chờ phê duyệt') {
                echo json_encode(['success' => false, 'message' => 'Đơn nghỉ phép không ở trạng thái chờ phê duyệt']);
                exit;
            }
            
            if ($action === 'approve') {
                $new_status = 'Sale Leader đã phê duyệt';
                $approval_message = 'Đơn nghỉ phép đã được Sale Leader phê duyệt (chờ HR phê duyệt cuối)';
                
                $stmt = $pdo->prepare("UPDATE leave_requests SET 
                    status = ?, 
                    admin_approved_by = ?, 
                    admin_approved_at = NOW(), 
                    admin_approval_comment = ? 
                    WHERE id = ?");
                $result = $stmt->execute([$new_status, $current_user['id'], $comment, $request_id]);
                
                if ($result) {
                    // Gửi thông báo cho HR
                    try {
                        $hr_ids = [];
                        $stmt = $pdo->prepare("SELECT id FROM staffs WHERE role = 'hr'");
                        $stmt->execute();
                        while ($hr = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            $hr_ids[] = $hr['id'];
                        }
                        
                        foreach ($hr_ids as $hr_id) {
                            $notification_sql = "INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, ?, ?)";
                            $notification_stmt = $pdo->prepare($notification_sql);
                            $notification_stmt->execute([
                                $hr_id,
                                'Đơn nghỉ phép SALE cần phê duyệt (Cấp 2)',
                                "Đơn nghỉ phép {$leave_request['request_code']} đã được Sale Leader phê duyệt, cần HR phê duyệt cuối (Cấp 2)",
                                'leave_request',
                                $request_id
                            ]);
                        }
                    } catch (Exception $e) {
                        // Bỏ qua lỗi notification
                    }
                }
                
            } elseif ($action === 'reject') {
                $new_status = 'Từ chối bởi Sale Leader';
                $approval_message = 'Đơn nghỉ phép đã bị Sale Leader từ chối';
                
                $stmt = $pdo->prepare("UPDATE leave_requests SET 
                    status = ?, 
                    admin_approved_by = ?, 
                    admin_approved_at = NOW(), 
                    admin_approval_comment = ? 
                    WHERE id = ?");
                $result = $stmt->execute([$new_status, $current_user['id'], $comment, $request_id]);
            }
            
        } elseif ($user_role === 'hr') {
            // Cấp 2: HR
            if ($current_status !== 'Sale Leader đã phê duyệt') {
                echo json_encode(['success' => false, 'message' => 'Đơn nghỉ phép chưa được Sale Leader phê duyệt hoặc không ở trạng thái phù hợp']);
                exit;
            }
            
            if ($action === 'approve') {
                $new_status = 'Đã phê duyệt';
                $approval_message = 'Đơn nghỉ phép đã được HR phê duyệt cuối cùng';
                
                $stmt = $pdo->prepare("UPDATE leave_requests SET 
                    status = ?, 
                    hr_approved_by = ?, 
                    hr_approved_at = NOW(), 
                    hr_approval_comment = ? 
                    WHERE id = ?");
                $result = $stmt->execute([$new_status, $current_user['id'], $comment, $request_id]);
                
            } elseif ($action === 'reject') {
                $new_status = 'Từ chối bởi HR';
                $approval_message = 'Đơn nghỉ phép đã bị HR từ chối';
                
                $stmt = $pdo->prepare("UPDATE leave_requests SET 
                    status = ?, 
                    hr_approved_by = ?, 
                    hr_approved_at = NOW(), 
                    hr_approval_comment = ? 
                    WHERE id = ?");
                $result = $stmt->execute([$new_status, $current_user['id'], $comment, $request_id]);
            }
        } else {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền phê duyệt đơn của phòng SALE']);
            exit;
        }
        
    } else {
        // Các phòng ban khác: giữ nguyên logic cũ (admin -> hr)
        if ($user_role === 'admin') {
        if ($current_status !== 'Chờ phê duyệt') {
            echo json_encode(['success' => false, 'message' => 'Đơn nghỉ phép không ở trạng thái chờ phê duyệt']);
            exit;
        }
        
        if ($action === 'approve') {
            $new_status = 'Admin đã phê duyệt';
                $approval_message = 'Đơn nghỉ phép đã được admin phê duyệt (chờ HR phê duyệt cuối)';
            
            $stmt = $pdo->prepare("UPDATE leave_requests SET 
                status = ?, 
                admin_approved_by = ?, 
                admin_approved_at = NOW(), 
                admin_approval_comment = ? 
                WHERE id = ?");
            $result = $stmt->execute([$new_status, $current_user['id'], $comment, $request_id]);
            
            if ($result) {
                // Gửi thông báo cho HR
                try {
                    $hr_ids = [];
                    $stmt = $pdo->prepare("SELECT id FROM staffs WHERE role = 'hr'");
                    $stmt->execute();
                    while ($hr = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $hr_ids[] = $hr['id'];
                    }
                    
                    foreach ($hr_ids as $hr_id) {
                        $notification_sql = "INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, ?, ?)";
                        $notification_stmt = $pdo->prepare($notification_sql);
                        $notification_stmt->execute([
                            $hr_id,
                            'Đơn nghỉ phép cần phê duyệt (Cấp 2)',
                                "Đơn nghỉ phép {$leave_request['request_code']} đã được admin phê duyệt, cần HR phê duyệt cuối (Cấp 2)",
                            'leave_request',
                            $request_id
                        ]);
                    }
                } catch (Exception $e) {
                    // Bỏ qua lỗi notification
                }
            }
            
        } elseif ($action === 'reject') {
            $new_status = 'Từ chối bởi Admin';
                $approval_message = 'Đơn nghỉ phép đã bị admin từ chối';
            
            $stmt = $pdo->prepare("UPDATE leave_requests SET 
                status = ?, 
                admin_approved_by = ?, 
                admin_approved_at = NOW(), 
                admin_approval_comment = ? 
                WHERE id = ?");
            $result = $stmt->execute([$new_status, $current_user['id'], $comment, $request_id]);
        }
        
    } elseif ($user_role === 'hr') {
        if ($current_status !== 'Admin đã phê duyệt') {
            echo json_encode(['success' => false, 'message' => 'Đơn nghỉ phép chưa được admin phê duyệt hoặc không ở trạng thái phù hợp']);
            exit;
        }
        
        if ($action === 'approve') {
                $new_status = 'Đã phê duyệt';
            $approval_message = 'Đơn nghỉ phép đã được HR phê duyệt cuối cùng';
            
            $stmt = $pdo->prepare("UPDATE leave_requests SET 
                status = ?, 
                hr_approved_by = ?, 
                hr_approved_at = NOW(), 
                hr_approval_comment = ? 
                WHERE id = ?");
            $result = $stmt->execute([$new_status, $current_user['id'], $comment, $request_id]);
            
        } elseif ($action === 'reject') {
            $new_status = 'Từ chối bởi HR';
            $approval_message = 'Đơn nghỉ phép đã bị HR từ chối';
            
            $stmt = $pdo->prepare("UPDATE leave_requests SET 
                status = ?, 
                hr_approved_by = ?, 
                hr_approved_at = NOW(), 
                hr_approval_comment = ? 
                WHERE id = ?");
            $result = $stmt->execute([$new_status, $current_user['id'], $comment, $request_id]);
            }
        } else {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền phê duyệt đơn nghỉ phép']);
            exit;
        }
    }
    
    if ($result) {
        // Gửi thông báo cho người tạo đơn
        try {
            $notification_sql = "INSERT INTO notifications (user_id, title, message, type, related_id) VALUES (?, ?, ?, ?, ?)";
            $notification_stmt = $pdo->prepare($notification_sql);
            $notification_stmt->execute([
                $leave_request['requester_id'],
                'Kết quả phê duyệt đơn nghỉ phép',
                "$approval_message. Mã đơn: {$leave_request['request_code']}. " . ($comment ? "Ghi chú: $comment" : ''),
                'leave_request',
                $request_id
            ]);
        } catch (Exception $e) {
            // Bỏ qua lỗi notification
        }
        
        // Log hoạt động
        try {
            $log_sql = "INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())";
            $log_stmt = $pdo->prepare($log_sql);
            $log_stmt->execute([
                $current_user['id'],
                $action === 'approve' ? 'approve_leave_request' : 'reject_leave_request',
                "{$action} đơn nghỉ phép: {$leave_request['request_code']} (Role: {$user_role}, Department: {$requester_department})"
            ]);
        } catch (Exception $e) {
            // Bỏ qua lỗi log
        }
        
        echo json_encode([
            'success' => true,
            'message' => $approval_message
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi cập nhật đơn nghỉ phép']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
    ]);
}
?> 