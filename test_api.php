<?php
// Test API endpoints
echo "<h2>Testing API Endpoints</h2>";

// Test database connection
echo "<h3>1. Testing Database Connection</h3>";
try {
    require_once 'config/db.php';
    $pdo = getConnection();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

// Test task templates table
echo "<h3>2. Testing Task Templates Table</h3>";
try {
    $sql = "SELECT COUNT(*) as count FROM deployment_task_templates";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p style='color: green;'>✓ Task templates table exists. Count: " . $result['count'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Task templates table error: " . $e->getMessage() . "</p>";
}

// Test deployment_tasks table
echo "<h3>3. Testing Deployment Tasks Table</h3>";
try {
    $sql = "SELECT COUNT(*) as count FROM deployment_tasks";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p style='color: green;'>✓ Deployment tasks table exists. Count: " . $result['count'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Deployment tasks table error: " . $e->getMessage() . "</p>";
}

// Test IT staffs query
echo "<h3>4. Testing IT Staffs Query</h3>";
try {
    $sql = "SELECT COUNT(*) as count FROM staffs s LEFT JOIN departments d ON s.department_id = d.id WHERE d.name = 'IT Dept.' OR d.name LIKE '%IT%'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p style='color: green;'>✓ IT staffs query successful. Count: " . $result['count'] . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ IT staffs query error: " . $e->getMessage() . "</p>";
}

echo "<h3>5. Testing API Endpoints</h3>";
echo "<p><a href='api/get_task_templates.php' target='_blank'>Test get_task_templates.php</a></p>";
echo "<p><a href='api/get_it_staffs.php' target='_blank'>Test get_it_staffs.php</a></p>";
?> 