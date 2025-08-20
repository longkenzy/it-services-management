<?php
/**
 * Export Internal Cases to Excel
 * File: api/export_internal_cases.php
 * Purpose: Export internal cases data to Excel format
 */

// Prevent any output before headers
ob_start();

// Include necessary files
require_once '../includes/session.php';
require_once '../config/db.php';
require_once '../vendor/autoload.php';

// Use PhpSpreadsheet for Excel generation
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Clear any output buffer
ob_clean();

// Bảo vệ API - yêu cầu đăng nhập
requireLogin();

// Lấy thông tin user hiện tại
$current_user = getCurrentUser();

// Lấy các tham số filter từ request
$status_filter = $_GET['status'] ?? '';
$date_from_filter = $_GET['date_from'] ?? '';
$date_to_filter = $_GET['date_to'] ?? '';
$requester_filter = $_GET['requester'] ?? '';
$handler_filter = $_GET['handler'] ?? '';
$case_type_filter = $_GET['case_type'] ?? '';

try {
    // Xây dựng câu query với filter
    $sql = "SELECT 
                ic.id,
                ic.case_number,
                ic.case_type,
                ic.priority,
                ic.issue_title,
                ic.issue_description,
                ic.notes,
                ic.status,
                ic.created_at,
                ic.start_date,
                ic.due_date,
                ic.completed_at,
                requester.fullname as requester_name,
                handler.fullname as handler_name
            FROM internal_cases ic
            LEFT JOIN staffs requester ON ic.requester_id = requester.id
            LEFT JOIN staffs handler ON ic.handler_id = handler.id
            WHERE 1=1";
    
    $params = [];
    
    // Filter theo trạng thái
    if (!empty($status_filter)) {
        $sql .= " AND ic.status = ?";
        $params[] = $status_filter;
    }
    
    // Filter theo ngày từ
    if (!empty($date_from_filter)) {
        $sql .= " AND ic.start_date >= ?";
        $params[] = $date_from_filter;
    }
    
    // Filter theo ngày đến
    if (!empty($date_to_filter)) {
        $sql .= " AND ic.start_date <= ?";
        $params[] = $date_to_filter;
    }
    
    // Filter theo người yêu cầu
    if (!empty($requester_filter)) {
        $sql .= " AND requester.fullname = ?";
        $params[] = $requester_filter;
    }
    
    // Filter theo người xử lý
    if (!empty($handler_filter)) {
        $sql .= " AND handler.fullname = ?";
        $params[] = $handler_filter;
    }
    
    // Filter theo loại case
    if (!empty($case_type_filter)) {
        $sql .= " AND ic.case_type = ?";
        $params[] = $case_type_filter;
    }
    
    $sql .= " ORDER BY ic.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tạo file Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Đặt tiêu đề cho sheet
    $sheet->setTitle('Danh sách Case nội bộ');
    
    // Định nghĩa headers
    $headers = [
        'STT',
        'Số case',
        'Người yêu cầu',
        'Người xử lý',
        'Loại case',
        'Hình thức',
        'Vụ việc hỗ trợ',
        'Ghi chú',
        'Mô tả chi tiết',
        'Ngày tiếp nhận',
        'Ngày bắt đầu',
        'Ngày hoàn thành',
        'Trạng thái'
    ];
    
    // Ghi headers
    foreach ($headers as $colIndex => $header) {
        $column = chr(65 + $colIndex); // A, B, C, ...
        $sheet->setCellValue($column . '1', $header);
        
        // Style cho header
        $sheet->getStyle($column . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);
    }
    
    // Ghi dữ liệu
    foreach ($cases as $index => $case) {
        $row = $index + 2;
        
        // Chuyển đổi trạng thái sang tiếng Việt
        $status_vn = '';
        switch ($case['status']) {
            case 'pending':
                $status_vn = 'Tiếp nhận';
                break;
            case 'in_progress':
                $status_vn = 'Đang xử lý';
                break;
            case 'completed':
                $status_vn = 'Hoàn thành';
                break;
            case 'cancelled':
                $status_vn = 'Huỷ';
                break;
            default:
                $status_vn = $case['status'];
        }
        
        // Chuyển đổi hình thức (priority field chứa work_type values)
        $work_type_vn = '';
        switch ($case['priority']) {
            case 'remote':
                $work_type_vn = 'Remote';
                break;
            case 'onsite':
                $work_type_vn = 'Onsite';
                break;
            case 'offsite':
                $work_type_vn = 'Offsite';
                break;
            default:
                $work_type_vn = $case['priority'] ?? 'Onsite';
        }
        
        $data = [
            $index + 1,
            $case['case_number'],
            $case['requester_name'] ?? 'N/A',
            $case['handler_name'] ?? 'Chưa phân công',
            $case['case_type'],
            $work_type_vn,
            $case['issue_title'],
            $case['notes'] ?? '-',
            $case['issue_description'],
            $case['created_at'] ? date('d/m/Y H:i', strtotime($case['created_at'])) : '-',
            $case['start_date'] ? date('d/m/Y', strtotime($case['start_date'])) : '-',
            $case['completed_at'] ? date('d/m/Y', strtotime($case['completed_at'])) : '-',
            $status_vn
        ];
        
        foreach ($data as $colIndex => $value) {
            $column = chr(65 + $colIndex);
            $sheet->setCellValue($column . $row, $value);
            
            // Style cho dữ liệu
            $sheet->getStyle($column . $row)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);
        }
    }
    
    // Tự động điều chỉnh chiều rộng cột
    foreach (range('A', 'N') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }
    
    // Tạo tên file
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "Danh_sach_Case_noi_bo_{$timestamp}.xlsx";
    
    // Tạo thư mục temp nếu chưa có
    $temp_dir = '../temp';
    if (!is_dir($temp_dir)) {
        mkdir($temp_dir, 0755, true);
    }
    
    // Lưu file vào temp trước
    $temp_file = $temp_dir . '/' . $filename;
    $writer = new Xlsx($spreadsheet);
    $writer->save($temp_file);
    
    // Kiểm tra file có tồn tại không
    if (!file_exists($temp_file)) {
        throw new Exception('Không thể tạo file Excel');
    }
    
    // Kiểm tra kích thước file
    $file_size = filesize($temp_file);
    if ($file_size === 0) {
        throw new Exception('File Excel trống');
    }
    
    // Clear any output buffer before sending headers
    ob_clean();
    
    // Set headers để download file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: max-age=0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Đọc và gửi file
    readfile($temp_file);
    
    // Xóa file temp
    unlink($temp_file);
    
    // End output buffer and flush
    ob_end_flush();
    exit;
    
} catch (Exception $e) {
    // Clear any output buffer
    ob_clean();
    
    // Log error
    error_log("Error exporting internal cases: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Có lỗi xảy ra khi xuất file Excel: ' . $e->getMessage()
    ]);
    exit;
}
?>
