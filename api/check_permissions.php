<?php
/**
 * API kiểm tra file permissions
 */

header('Content-Type: application/json');

try {
    $response = [
        'success' => true,
        'permissions' => [],
        'directories' => [],
        'php_info' => []
    ];
    
    // Kiểm tra thư mục upload
    $upload_dirs = [
        '../assets/uploads/',
        '../assets/uploads/leave_attachments/',
        '../assets/uploads/avatars/',
        '../logs/'
    ];
    
    foreach ($upload_dirs as $dir) {
        $full_path = __DIR__ . '/' . $dir;
        $exists = is_dir($full_path);
        $writable = is_writable($full_path);
        $readable = is_readable($full_path);
        
        $response['directories'][$dir] = [
            'exists' => $exists,
            'writable' => $writable,
            'readable' => $readable,
            'permissions' => $exists ? substr(sprintf('%o', fileperms($full_path)), -4) : 'N/A',
            'full_path' => $full_path
        ];
        
        // Tạo thư mục nếu không tồn tại
        if (!$exists) {
            $created = mkdir($full_path, 0777, true);
            $response['directories'][$dir]['created'] = $created;
        }
    }
    
    // Kiểm tra file permissions
    $important_files = [
        '../config/db.php',
        '../includes/session.php',
        '../api/create_leave_request.php',
        '../logs/api_errors.log'
    ];
    
    foreach ($important_files as $file) {
        $full_path = __DIR__ . '/' . $file;
        $exists = file_exists($full_path);
        $readable = is_readable($full_path);
        $writable = is_writable($full_path);
        
        $response['permissions'][$file] = [
            'exists' => $exists,
            'readable' => $readable,
            'writable' => $writable,
            'permissions' => $exists ? substr(sprintf('%o', fileperms($full_path)), -4) : 'N/A',
            'size' => $exists ? filesize($full_path) : 0,
            'full_path' => $full_path
        ];
    }
    
    // Thông tin PHP
    $response['php_info'] = [
        'version' => phpversion(),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'max_execution_time' => ini_get('max_execution_time'),
        'memory_limit' => ini_get('memory_limit'),
        'display_errors' => ini_get('display_errors'),
        'error_reporting' => ini_get('error_reporting'),
        'session_save_path' => ini_get('session.save_path'),
        'session_gc_maxlifetime' => ini_get('session.gc_maxlifetime')
    ];
    
    // Kiểm tra extensions
    $required_extensions = ['pdo', 'pdo_mysql', 'json', 'fileinfo', 'session'];
    $response['extensions'] = [];
    
    foreach ($required_extensions as $ext) {
        $response['extensions'][$ext] = extension_loaded($ext);
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?> 