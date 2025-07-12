<?php
// Test database connection
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Database Connection</h2>";

try {
    // Test connection
    $pdo = new PDO('mysql:host=localhost;dbname=thichho1_it_crm_db;charset=utf8mb4', 'thichho1_root', 'Longkenzy@7525');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Database connection successful</p>";
    
    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM staffs");
    $result = $stmt->fetch();
    echo "<p>✅ Staffs table has {$result['count']} records</p>";
    
    // Test admin user
    $stmt = $pdo->prepare("SELECT * FROM staffs WHERE username = ?");
    $stmt->execute(['admin']);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<p>✅ Found admin user:</p>";
        echo "<pre>";
        print_r($admin);
        echo "</pre>";
    } else {
        echo "<p>❌ Admin user not found</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?> 