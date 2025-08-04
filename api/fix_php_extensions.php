<?php
/**
 * Tự động bật các extension PHP cần thiết
 */

echo "=== TỰ ĐỘNG BẬT PHP EXTENSIONS ===\n\n";

// Tìm file php.ini
$php_ini_path = php_ini_loaded_file();
if (empty($php_ini_path)) {
    echo "❌ Không tìm thấy file php.ini\n";
    echo "Thử tìm trong các vị trí thông thường:\n";
    
    $possible_paths = [
        'C:/laragon/bin/php/php-8.4.10-Win32-vs16-x64/php.ini',
        'C:/laragon/bin/php/php-8.4.10-Win32-vs16-x64/php.ini-development',
        'C:/laragon/bin/php/php-8.4.10-Win32-vs16-x64/php.ini-production',
        'C:/laragon/etc/php/php.ini',
        'C:/laragon/etc/php/php.ini-development',
        'C:/laragon/etc/php/php.ini-production'
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            echo "✅ Tìm thấy: " . $path . "\n";
            $php_ini_path = $path;
            break;
        }
    }
}

if (empty($php_ini_path)) {
    echo "❌ Không thể tìm thấy file php.ini\n";
    echo "Vui lòng bật thủ công các extension sau:\n";
    echo "- extension=pdo_mysql\n";
    echo "- extension=curl\n";
    echo "- extension=openssl\n";
    exit;
}

echo "📁 File php.ini: " . $php_ini_path . "\n\n";

// Đọc nội dung file php.ini
$content = file_get_contents($php_ini_path);
if ($content === false) {
    echo "❌ Không thể đọc file php.ini\n";
    exit;
}

// Các extension cần bật
$extensions_to_enable = [
    'pdo_mysql' => 'extension=pdo_mysql',
    'curl' => 'extension=curl',
    'openssl' => 'extension=openssl'
];

$modified = false;

foreach ($extensions_to_enable as $ext_name => $ext_line) {
    // Kiểm tra xem extension đã được bật chưa
    if (extension_loaded($ext_name)) {
        echo "✅ $ext_name: Đã bật\n";
        continue;
    }
    
    // Tìm dòng extension trong file
    if (strpos($content, $ext_line) !== false) {
        echo "✅ $ext_name: Đã có trong file php.ini\n";
        continue;
    }
    
    // Tìm dòng bị comment
    $commented_line = ';' . $ext_line;
    if (strpos($content, $commented_line) !== false) {
        // Bỏ comment
        $content = str_replace($commented_line, $ext_line, $content);
        echo "✅ $ext_name: Đã bỏ comment\n";
        $modified = true;
    } else {
        // Thêm extension mới
        $content .= "\n" . $ext_line . "\n";
        echo "✅ $ext_name: Đã thêm mới\n";
        $modified = true;
    }
}

if ($modified) {
    // Backup file cũ
    $backup_path = $php_ini_path . '.backup.' . date('Y-m-d-H-i-s');
    if (copy($php_ini_path, $backup_path)) {
        echo "📋 Backup file: " . $backup_path . "\n";
    }
    
    // Ghi file mới
    if (file_put_contents($php_ini_path, $content)) {
        echo "✅ Đã cập nhật file php.ini\n";
        echo "🔄 Vui lòng restart Laragon để áp dụng thay đổi\n";
    } else {
        echo "❌ Không thể ghi file php.ini (cần quyền admin)\n";
        echo "Vui lòng bật thủ công các extension sau:\n";
        foreach ($extensions_to_enable as $ext_name => $ext_line) {
            if (!extension_loaded($ext_name)) {
                echo "- $ext_line\n";
            }
        }
    }
} else {
    echo "ℹ️ Không cần thay đổi gì\n";
}

echo "\n=== KIỂM TRA SAU KHI SỬA ===\n";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? "✅ Đã bật" : "❌ Chưa bật") . "\n";
echo "cURL: " . (extension_loaded('curl') ? "✅ Đã bật" : "❌ Chưa bật") . "\n";
echo "OpenSSL: " . (extension_loaded('openssl') ? "✅ Đã bật" : "❌ Chưa bật") . "\n";
?> 