<?php
// Test file đơn giản để kiểm tra
echo "Testing basic PHP setup...\n";

// Test require files
try {
    require_once 'config/db.php';
    echo "✓ config/db.php loaded successfully\n";
    
    require_once 'includes/session.php';
    echo "✓ includes/session.php loaded successfully\n";
    
    // Test database connection
    if (isset($pdo) && $pdo instanceof PDO) {
        echo "✓ Database connection successful\n";
        
        // Test simple query
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        if ($result && $result['test'] == 1) {
            echo "✓ Database query successful\n";
        } else {
            echo "✗ Database query failed\n";
        }
    } else {
        echo "✗ Database connection failed\n";
    }
    
    // Test session functions
    if (function_exists('getCurrentUserId')) {
        echo "✓ getCurrentUserId function exists\n";
        $user_id = getCurrentUserId();
        echo "Current user ID: " . ($user_id ?? 'null') . "\n";
    } else {
        echo "✗ getCurrentUserId function not found\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?> 