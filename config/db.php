<?php
/**
 * IT CRM Database Connection
 * File: config/db.php
 * Mục đích: Kết nối MySQL database sử dụng PDO
 * Tác giả: IT Support Team
 * Ngày tạo: 2024-01-01
 */

// Ngăn chặn truy cập trực tiếp vào file này
if (!defined('INCLUDED')) {
    define('INCLUDED', true);
}

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Access denied.');
}

// ===== CẤU HÌNH DATABASE ===== //
$db_config = [
    'host'     => 'localhost',          // Địa chỉ server MySQL
    'database' => 'thichho1_it_crm_db',          // Tên database
    'username' => 'thichho1_root',               // Tên đăng nhập MySQL
    'password' => 'Longkenzy@7525',                   // Mật khẩu MySQL (để trống với XAMPP/WAMP)
    'charset'  => 'utf8mb4',            // Bộ mã ký tự
    'port'     => 3306                  // Cổng MySQL (mặc định 3306)
];

// ===== TÙY CHỌN PDO ===== //
$pdo_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,    // Báo lỗi dạng Exception
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // Trả về dạng mảng kết hợp
    PDO::ATTR_EMULATE_PREPARES   => true,                      // Bật giả lập prepared statements cho hosting
    PDO::ATTR_PERSISTENT         => false                      // Tắt kết nối liên tục
];

// Thêm MySQL-specific options nếu có sẵn
if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
    $pdo_options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4";
}

// ===== TẠO CHUỖI DSN (Data Source Name) ===== //
$dsn = "mysql:host={$db_config['host']};dbname={$db_config['database']};charset={$db_config['charset']};port={$db_config['port']}";

// ===== BIẾN TOÀN CỤC LUU KẾT NỐI ===== //
$pdo = null;

try {
    // Tạo kết nối PDO
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $pdo_options);
    
    // Thiết lập timezone cho MySQL
    $pdo->exec("SET time_zone = '+07:00'");
    
    // Log kết nối thành công (chỉ trong môi trường development)
    
} catch (PDOException $e) {
    // Xử lý lỗi kết nối
    $error_message = "Lỗi kết nối database: " . $e->getMessage();
    
    // Log lỗi vào file
    error_log($error_message);
    
    // Trong môi trường production, không hiển thị chi tiết lỗi
    if (defined('PRODUCTION') && PRODUCTION === true) {
        die("Không thể kết nối đến cơ sở dữ liệu. Vui lòng thử lại sau.");
    } else {
        die($error_message);
    }
}

/**
 * Hàm thực thi câu lệnh SQL an toàn
 * @param string $sql Câu lệnh SQL
 * @param array $params Tham số cho prepared statement
 * @return PDOStatement|false
 */
function executeQuery($sql, $params = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("SQL Error: " . $e->getMessage() . " | Query: " . $sql);
        return false;
    }
}

/**
 * Hàm lấy một dòng dữ liệu
 * @param string $sql Câu lệnh SQL
 * @param array $params Tham số cho prepared statement
 * @return array|false
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetch() : false;
}

/**
 * Hàm lấy nhiều dòng dữ liệu
 * @param string $sql Câu lệnh SQL
 * @param array $params Tham số cho prepared statement
 * @return array|false
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt ? $stmt->fetchAll() : false;
}

function getConnection() {
    global $pdo;
    return $pdo;
}

// ===== THÔNG BÁO KẾT NỐI THÀNH CÔNG ===== //

?> 