<?php
/**
 * Kiểm tra các extension PHP cần thiết
 */

echo "=== KIỂM TRA PHP EXTENSIONS ===\n\n";

// Kiểm tra PDO
echo "PDO: " . (extension_loaded('pdo') ? "✅ Đã bật" : "❌ Chưa bật") . "\n";

// Kiểm tra PDO MySQL
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? "✅ Đã bật" : "❌ Chưa bật") . "\n";

// Kiểm tra cURL
echo "cURL: " . (extension_loaded('curl') ? "✅ Đã bật" : "❌ Chưa bật") . "\n";

// Kiểm tra JSON
echo "JSON: " . (extension_loaded('json') ? "✅ Đã bật" : "❌ Chưa bật") . "\n";

// Kiểm tra OpenSSL
echo "OpenSSL: " . (extension_loaded('openssl') ? "✅ Đã bật" : "❌ Chưa bật") . "\n";

echo "\n=== THÔNG TIN PHP ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "PHP ini file: " . php_ini_loaded_file() . "\n";

// Kiểm tra các extension đã load
echo "\n=== TẤT CẢ EXTENSIONS ĐÃ LOAD ===\n";
$extensions = get_loaded_extensions();
sort($extensions);
foreach ($extensions as $ext) {
    if (strpos($ext, 'pdo') !== false || strpos($ext, 'curl') !== false || strpos($ext, 'json') !== false || strpos($ext, 'openssl') !== false) {
        echo "- " . $ext . "\n";
    }
}

echo "\n=== HƯỚNG DẪN BẬT PDO MYSQL ===\n";
echo "1. Mở file php.ini: " . php_ini_loaded_file() . "\n";
echo "2. Tìm dòng: ;extension=pdo_mysql\n";
echo "3. Bỏ dấu ; ở đầu dòng: extension=pdo_mysql\n";
echo "4. Restart Laragon\n";
?> 