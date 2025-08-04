<?php
/**
 * Tạo bảng don_nghi_phep
 */

echo "=== TẠO BẢNG DON_NGHI_PHEP ===\n\n";

// Kết nối database
$host = 'localhost';
$dbname = 'thichho1_it_crm_db';
$username = 'thichho1_root';
$password = 'Longkenzy@7525';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Kết nối database thành công\n";
} catch(PDOException $e) {
    echo "❌ Lỗi kết nối database: " . $e->getMessage() . "\n";
    exit;
}

// Kiểm tra bảng đã tồn tại chưa
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'don_nghi_phep'");
    if ($stmt->rowCount() > 0) {
        echo "⚠️ Bảng don_nghi_phep đã tồn tại\n";
        
        // Hiển thị cấu trúc hiện tại
        $stmt = $conn->query("DESCRIBE don_nghi_phep");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "📋 Cấu trúc hiện tại:\n";
        foreach ($columns as $column) {
            echo "   - {$column['Field']}: {$column['Type']}\n";
        }
        
        // Kiểm tra xem có cần thêm cột không
        $has_ngay_duyet = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'ngay_duyet') {
                $has_ngay_duyet = true;
                break;
            }
        }
        
        if (!$has_ngay_duyet) {
            echo "\n📝 Thêm cột ngay_duyet...\n";
            $conn->exec("ALTER TABLE don_nghi_phep ADD COLUMN ngay_duyet DATETIME NULL AFTER ngay_gui");
            echo "✅ Đã thêm cột ngay_duyet\n";
        }
        
    } else {
        echo "📝 Tạo bảng don_nghi_phep...\n";
        
        $sql = "CREATE TABLE don_nghi_phep (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ma_don VARCHAR(50) UNIQUE NOT NULL,
            trang_thai ENUM('cho_duyet', 'da_duyet', 'tu_choi') DEFAULT 'cho_duyet',
            noi_dung TEXT,
            ngay_gui DATETIME DEFAULT CURRENT_TIMESTAMP,
            ngay_duyet DATETIME NULL,
            INDEX idx_ma_don (ma_don),
            INDEX idx_trang_thai (trang_thai),
            INDEX idx_ngay_gui (ngay_gui)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $conn->exec($sql);
        echo "✅ Đã tạo bảng don_nghi_phep thành công\n";
        
        // Hiển thị cấu trúc
        $stmt = $conn->query("DESCRIBE don_nghi_phep");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "📋 Cấu trúc bảng:\n";
        foreach ($columns as $column) {
            echo "   - {$column['Field']}: {$column['Type']}\n";
        }
    }
    
    // Tạo dữ liệu mẫu
    echo "\n📝 Tạo dữ liệu mẫu...\n";
    $ma_don = 'SAMPLE_' . date('YmdHis');
    $stmt = $conn->prepare("INSERT INTO don_nghi_phep (ma_don, trang_thai, noi_dung) VALUES (?, 'cho_duyet', ?)");
    $stmt->execute([$ma_don, 'Đơn nghỉ phép mẫu để test']);
    echo "✅ Đã tạo đơn mẫu: $ma_don\n";
    
    // Hiển thị dữ liệu
    echo "\n📊 Dữ liệu trong bảng:\n";
    $stmt = $conn->query("SELECT * FROM don_nghi_phep ORDER BY ngay_gui DESC LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rows) > 0) {
        foreach ($rows as $row) {
            echo "   - {$row['ma_don']}: {$row['trang_thai']} (Ngày: {$row['ngay_gui']})\n";
        }
    } else {
        echo "   ⚠️ Chưa có dữ liệu\n";
    }
    
} catch(PDOException $e) {
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}

echo "\n=== HOÀN THÀNH ===\n";
?> 