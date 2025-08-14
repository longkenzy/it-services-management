<?php
/**
 * Export Deployment Requests to Excel
 * File: api/export_deployment_requests.php
 * Purpose: Export deployment requests data to Excel format
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

try {
    // Xây dựng câu query để lấy dữ liệu deployment requests
    $sql = "SELECT 
                dr.id,
                dr.request_code,
                dr.po_number,
                dr.contract_type,
                dr.request_detail_type,
                dr.email_subject_customer,
                dr.email_subject_internal,
                dr.expected_start,
                dr.expected_end,
                dr.contact_person,
                dr.contact_phone,
                dr.requester_notes,
                dr.deployment_manager,
                dr.deployment_status,
                dr.created_at,
                pc.name as customer_name,
                sale.fullname as sale_name,
                creator.fullname as created_by_name,
                (
                    SELECT COUNT(*) FROM deployment_cases dc WHERE dc.deployment_request_id = dr.id
                ) as total_cases,
                (
                    SELECT COUNT(*) FROM deployment_tasks dt 
                    INNER JOIN deployment_cases dc ON dt.deployment_case_id = dc.id 
                    WHERE dc.deployment_request_id = dr.id
                ) as total_tasks
            FROM deployment_requests dr
            LEFT JOIN partner_companies pc ON dr.customer_id = pc.id
            LEFT JOIN staffs sale ON dr.sale_id = sale.id
            LEFT JOIN staffs creator ON dr.created_by = creator.id
            ORDER BY dr.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tạo file Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Đặt tiêu đề cho sheet
    $sheet->setTitle('Danh sách Yêu cầu triển khai');
    
    // Định nghĩa headers
    $headers = [
        'STT',
        'Mã YC',
        'Số HĐ PO',
        'Loại HĐ',
        'Loại yêu cầu chi tiết',
        'Khách hàng',
        'Người liên hệ',
        'Điện thoại',
        'Sale phụ trách',
        'Email subject (KH)',
        'Email subject (NB)',
        'Bắt đầu dự kiến',
        'Kết thúc dự kiến',
        'Ghi chú',
        'Quản lý triển khai',
        'Trạng thái triển khai',
        'Tổng số case',
        'Tổng số task',
        'Người tạo',
        'Ngày tạo'
    ];
    
    // Style cho header
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF']
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '5BC0DE']
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
    ];
    
    // Ghi headers
    foreach ($headers as $colIndex => $header) {
        $column = chr(65 + $colIndex);
        $sheet->setCellValue($column . '1', $header);
        $sheet->getStyle($column . '1')->applyFromArray($headerStyle);
    }
    
    // Ghi dữ liệu
    foreach ($requests as $index => $request) {
        $row = $index + 2;
        
        // Format dates
        $expected_start = $request['expected_start'] ? date('d/m/Y', strtotime($request['expected_start'])) : '';
        $expected_end = $request['expected_end'] ? date('d/m/Y', strtotime($request['expected_end'])) : '';
        $created_at = $request['created_at'] ? date('d/m/Y H:i', strtotime($request['created_at'])) : '';
        
        // Status color mapping
        $statusColor = '';
        switch ($request['deployment_status']) {
            case 'Hoàn thành':
                $statusColor = '28A745';
                break;
            case 'Đang xử lý':
                $statusColor = 'FFC107';
                break;
            case 'Huỷ':
                $statusColor = 'DC3545';
                break;
            default:
                $statusColor = '6C757D';
        }
        
        $data = [
            $index + 1,
            $request['request_code'],
            $request['po_number'] ?: 'Không có HĐ/PO',
            $request['contract_type'] ?: 'N/A',
            $request['request_detail_type'] ?: 'N/A',
            $request['customer_name'] ?: 'N/A',
            $request['contact_person'] ?: 'N/A',
            $request['contact_phone'] ?: 'N/A',
            $request['sale_name'] ?: 'N/A',
            $request['email_subject_customer'] ?: 'N/A',
            $request['email_subject_internal'] ?: 'N/A',
            $expected_start,
            $expected_end,
            $request['requester_notes'] ?: 'N/A',
            $request['deployment_manager'] ?: 'N/A',
            $request['deployment_status'] ?: 'N/A',
            $request['total_cases'] ?: 0,
            $request['total_tasks'] ?: 0,
            $request['created_by_name'] ?: 'N/A',
            $created_at
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
            
            // Style đặc biệt cho cột trạng thái
            if ($colIndex == 15) { // Cột trạng thái triển khai
                $sheet->getStyle($column . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $statusColor]
                    ],
                    'font' => [
                        'color' => ['rgb' => 'FFFFFF'],
                        'bold' => true
                    ]
                ]);
            }
        }
    }
    
    // Tự động điều chỉnh chiều rộng cột
    foreach (range('A', 'T') as $column) {
        $sheet->getColumnDimension($column)->setAutoSize(true);
    }
    
    // Tạo tên file
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "Danh_sach_Yeu_cau_trien_khai_{$timestamp}.xlsx";
    
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
    error_log("Error exporting deployment requests: " . $e->getMessage());
    
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
