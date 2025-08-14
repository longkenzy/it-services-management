<?php
/**
 * Test Export Internal Cases to Excel
 * File: api/export_internal_cases_test.php
 * Purpose: Simple test version to isolate Excel corruption issue
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
            LIMIT 10";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Tạo file Excel đơn giản
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Đặt tiêu đề cho sheet
    $sheet->setTitle('Test Export');
    
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
    $filename = "Test_Export_{$timestamp}.xlsx";
    
    // Clear any output buffer before sending headers
    ob_clean();
    
    // Set headers để download file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Tạo file Excel
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    
    // End output buffer and flush
    ob_end_flush();
    exit;
    
} catch (Exception $e) {
    // Clear any output buffer
    ob_clean();
    
    // Log error
    error_log("Error in test export: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Test export error: ' . $e->getMessage()
    ]);
    exit;
}
?>
