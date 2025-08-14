<?php
/**
 * Debug Export Internal Cases to Excel
 * File: api/export_internal_cases_debug.php
 * Purpose: Debug version that saves to temp file first
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

// Clear any output buffer
ob_clean();

// Bảo vệ API - yêu cầu đăng nhập
requireLogin();

try {
    // Simple query to test
    $sql = "SELECT 
                ic.id,
                ic.case_number,
                ic.case_type,
                ic.status,
                ic.created_at
            FROM internal_cases ic
            LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tạo file Excel đơn giản
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Đặt tiêu đề cho sheet
    $sheet->setTitle('Debug Export');
    
    // Headers đơn giản
    $headers = ['ID', 'Số case', 'Loại case', 'Trạng thái', 'Ngày tạo'];
    
    // Ghi headers
    foreach ($headers as $colIndex => $header) {
        $column = chr(65 + $colIndex);
        $sheet->setCellValue($column . '1', $header);
    }
    
    // Ghi dữ liệu đơn giản
    foreach ($cases as $index => $case) {
        $row = $index + 2;
        
        $data = [
            $case['id'],
            $case['case_number'],
            $case['case_type'],
            $case['status'],
            $case['created_at'] ? date('d/m/Y', strtotime($case['created_at'])) : '-'
        ];
        
        foreach ($data as $colIndex => $value) {
            $column = chr(65 + $colIndex);
            $sheet->setCellValue($column . $row, $value);
        }
    }
    
    // Tạo tên file
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "Debug_Export_{$timestamp}.xlsx";
    
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
    error_log("Error in debug export: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Debug export error: ' . $e->getMessage()
    ]);
    exit;
}
?>
