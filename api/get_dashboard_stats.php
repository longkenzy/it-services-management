<?php
/**
 * IT CRM - Dashboard Statistics API
 * File: api/get_dashboard_stats.php
 * Mục đích: API lấy thống kê tổng quan cho dashboard
 */

// Sử dụng đường dẫn tuyệt đối
$base_path = dirname(__DIR__);
require_once $base_path . '/config/db.php';
require_once $base_path . '/includes/session.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

try {
    $stats = [];
    
    // ===== THỐNG KÊ NHÂN SỰ ===== //
    try {
        $staff_sql = "SELECT 
            COUNT(*) as total_staff,
            COUNT(CASE WHEN resigned = 0 THEN 1 END) as active_staff,
            COUNT(CASE WHEN resigned = 1 THEN 1 END) as resigned_staff,
            COUNT(CASE WHEN gender = 'Nam' THEN 1 END) as male_staff,
            COUNT(CASE WHEN gender = 'Nữ' THEN 1 END) as female_staff,
            COUNT(CASE WHEN job_type = 'Chính thức' THEN 1 END) as fulltime_staff,
            COUNT(CASE WHEN job_type = 'Thử việc' THEN 1 END) as probation_staff,
            COUNT(CASE WHEN job_type = 'Cộng tác viên' THEN 1 END) as contractor_staff
        FROM staffs";
        
        $stmt = $pdo->prepare($staff_sql);
        $stmt->execute();
        $staff_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $staff_stats = [
            'total_staff' => 0,
            'active_staff' => 0,
            'resigned_staff' => 0,
            'male_staff' => 0,
            'female_staff' => 0,
            'fulltime_staff' => 0,
            'probation_staff' => 0,
            'contractor_staff' => 0
        ];
    }
    
    // ===== THỐNG KÊ DEPLOYMENT REQUESTS ===== //
    try {
        $deployment_sql = "SELECT 
            COUNT(*) as total_requests,
            COUNT(CASE WHEN status = 'Chờ xử lý' THEN 1 END) as pending_requests,
            COUNT(CASE WHEN status = 'Đang xử lý' THEN 1 END) as processing_requests,
            COUNT(CASE WHEN status = 'Hoàn thành' THEN 1 END) as completed_requests,
            COUNT(CASE WHEN status = 'Tạm dừng' THEN 1 END) as paused_requests,
            COUNT(CASE WHEN status = 'Hủy bỏ' THEN 1 END) as cancelled_requests
        FROM deployment_requests";
        
        $stmt = $pdo->prepare($deployment_sql);
        $stmt->execute();
        $deployment_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $deployment_stats = [
            'total_requests' => 0,
            'pending_requests' => 0,
            'processing_requests' => 0,
            'completed_requests' => 0,
            'paused_requests' => 0,
            'cancelled_requests' => 0
        ];
    }
    
    // ===== THỐNG KÊ DEPLOYMENT CASES ===== //
    try {
        $deployment_cases_sql = "SELECT 
            COUNT(*) as total_cases,
            COUNT(CASE WHEN status = 'Chờ xử lý' THEN 1 END) as pending_cases,
            COUNT(CASE WHEN status = 'Đang xử lý' THEN 1 END) as processing_cases,
            COUNT(CASE WHEN status = 'Hoàn thành' THEN 1 END) as completed_cases,
            COUNT(CASE WHEN status = 'Tạm dừng' THEN 1 END) as paused_cases
        FROM deployment_cases";
        
        $stmt = $pdo->prepare($deployment_cases_sql);
        $stmt->execute();
        $deployment_cases_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $deployment_cases_stats = [
            'total_cases' => 0,
            'pending_cases' => 0,
            'processing_cases' => 0,
            'completed_cases' => 0,
            'paused_cases' => 0
        ];
    }
    
    // ===== THỐNG KÊ MAINTENANCE REQUESTS ===== //
    try {
        $maintenance_sql = "SELECT 
            COUNT(*) as total_requests,
            COUNT(CASE WHEN status = 'Chờ xử lý' THEN 1 END) as pending_requests,
            COUNT(CASE WHEN status = 'Đang xử lý' THEN 1 END) as processing_requests,
            COUNT(CASE WHEN status = 'Hoàn thành' THEN 1 END) as completed_requests,
            COUNT(CASE WHEN status = 'Tạm dừng' THEN 1 END) as paused_requests,
            COUNT(CASE WHEN status = 'Hủy bỏ' THEN 1 END) as cancelled_requests
        FROM maintenance_requests";
        
        $stmt = $pdo->prepare($maintenance_sql);
        $stmt->execute();
        $maintenance_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $maintenance_stats = [
            'total_requests' => 0,
            'pending_requests' => 0,
            'processing_requests' => 0,
            'completed_requests' => 0,
            'paused_requests' => 0,
            'cancelled_requests' => 0
        ];
    }
    
    // ===== THỐNG KÊ MAINTENANCE CASES ===== //
    try {
        $maintenance_cases_sql = "SELECT 
            COUNT(*) as total_cases,
            COUNT(CASE WHEN status = 'Chờ xử lý' THEN 1 END) as pending_cases,
            COUNT(CASE WHEN status = 'Đang xử lý' THEN 1 END) as processing_cases,
            COUNT(CASE WHEN status = 'Hoàn thành' THEN 1 END) as completed_cases,
            COUNT(CASE WHEN status = 'Tạm dừng' THEN 1 END) as paused_cases
        FROM maintenance_cases";
        
        $stmt = $pdo->prepare($maintenance_cases_sql);
        $stmt->execute();
        $maintenance_cases_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $maintenance_cases_stats = [
            'total_cases' => 0,
            'pending_cases' => 0,
            'processing_cases' => 0,
            'completed_cases' => 0,
            'paused_cases' => 0
        ];
    }
    
    // ===== THỐNG KÊ TASKS ===== //
    try {
        $deployment_tasks_sql = "SELECT 
            COUNT(*) as total_tasks,
            COUNT(CASE WHEN status = 'Chờ xử lý' THEN 1 END) as pending_tasks,
            COUNT(CASE WHEN status = 'Đang xử lý' THEN 1 END) as processing_tasks,
            COUNT(CASE WHEN status = 'Hoàn thành' THEN 1 END) as completed_tasks
        FROM deployment_tasks";
        
        $stmt = $pdo->prepare($deployment_tasks_sql);
        $stmt->execute();
        $deployment_tasks_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $deployment_tasks_stats = [
            'total_tasks' => 0,
            'pending_tasks' => 0,
            'processing_tasks' => 0,
            'completed_tasks' => 0
        ];
    }
    
    try {
        $maintenance_tasks_sql = "SELECT 
            COUNT(*) as total_tasks,
            COUNT(CASE WHEN status = 'Chờ xử lý' THEN 1 END) as pending_tasks,
            COUNT(CASE WHEN status = 'Đang xử lý' THEN 1 END) as processing_tasks,
            COUNT(CASE WHEN status = 'Hoàn thành' THEN 1 END) as completed_tasks
        FROM maintenance_tasks";
        
        $stmt = $pdo->prepare($maintenance_tasks_sql);
        $stmt->execute();
        $maintenance_tasks_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $maintenance_tasks_stats = [
            'total_tasks' => 0,
            'pending_tasks' => 0,
            'processing_tasks' => 0,
            'completed_tasks' => 0
        ];
    }
    
    // ===== THỐNG KÊ PARTNER COMPANIES ===== //
    try {
        $partners_sql = "SELECT COUNT(*) as total_partners FROM partner_companies";
        $stmt = $pdo->prepare($partners_sql);
        $stmt->execute();
        $partners_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $partners_stats = ['total_partners' => 0];
    }
    
    // ===== THỐNG KÊ RECENT ACTIVITIES (7 ngày gần nhất) ===== //
    try {
        $recent_activities_sql = "SELECT 
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_deployment_requests_7d
        FROM deployment_requests";
        
        $stmt = $pdo->prepare($recent_activities_sql);
        $stmt->execute();
        $recent_deployment = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $recent_deployment = ['new_deployment_requests_7d' => 0];
    }
    
    try {
        $recent_maintenance_sql = "SELECT 
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_maintenance_requests_7d
        FROM maintenance_requests";
        
        $stmt = $pdo->prepare($recent_maintenance_sql);
        $stmt->execute();
        $recent_maintenance = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $recent_maintenance = ['new_maintenance_requests_7d' => 0];
    }
    
    // ===== TÍNH TOÁN TỔNG HỢP ===== //
    $total_requests = $deployment_stats['total_requests'] + $maintenance_stats['total_requests'];
    $total_cases = $deployment_cases_stats['total_cases'] + $maintenance_cases_stats['total_cases'];
    $total_tasks = $deployment_tasks_stats['total_tasks'] + $maintenance_tasks_stats['total_tasks'];
    
    $pending_requests = $deployment_stats['pending_requests'] + $maintenance_stats['pending_requests'];
    $processing_requests = $deployment_stats['processing_requests'] + $maintenance_stats['processing_requests'];
    $completed_requests = $deployment_stats['completed_requests'] + $maintenance_stats['completed_requests'];
    
    // Tính tỷ lệ hoàn thành
    $completion_rate = $total_requests > 0 ? round(($completed_requests / $total_requests) * 100, 1) : 0;
    
    // Tổng hợp dữ liệu
    $stats = [
        'staff' => $staff_stats,
        'deployment_requests' => $deployment_stats,
        'deployment_cases' => $deployment_cases_stats,
        'maintenance_requests' => $maintenance_stats,
        'maintenance_cases' => $maintenance_cases_stats,
        'deployment_tasks' => $deployment_tasks_stats,
        'maintenance_tasks' => $maintenance_tasks_stats,
        'partners' => $partners_stats,
        'recent_activities' => [
            'new_deployment_requests_7d' => $recent_deployment['new_deployment_requests_7d'],
            'new_maintenance_requests_7d' => $recent_maintenance['new_maintenance_requests_7d']
        ],
        'summary' => [
            'total_requests' => $total_requests,
            'total_cases' => $total_cases,
            'total_tasks' => $total_tasks,
            'pending_requests' => $pending_requests,
            'processing_requests' => $processing_requests,
            'completed_requests' => $completed_requests,
            'completion_rate' => $completion_rate
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_dashboard_stats.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi lấy thống kê dashboard: ' . $e->getMessage()
    ]);
}
?> 