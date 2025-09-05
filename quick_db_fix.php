<?php
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h2>Quick Database Fix</h2>";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    echo "<p>✅ Database connected successfully!</p>";
    
    // Add the most essential columns one by one
    $columns_to_add = [
        'confidence_score' => 'DECIMAL(5,2) DEFAULT NULL',
        'accuracy_meters' => 'INT DEFAULT NULL',
        'network_type' => 'VARCHAR(50) DEFAULT NULL',
        'vpn_detected' => 'BOOLEAN DEFAULT FALSE',
        'browser' => 'VARCHAR(50) DEFAULT NULL',
        'os' => 'VARCHAR(50) DEFAULT NULL',
        'refined_latitude' => 'DECIMAL(10, 8) DEFAULT NULL',
        'refined_longitude' => 'DECIMAL(11, 8) DEFAULT NULL'
    ];
    
    foreach ($columns_to_add as $column => $definition) {
        // Check if column exists
        $stmt = $conn->query("SHOW COLUMNS FROM targets LIKE '$column'");
        $column_exists = $stmt->fetch();
        
        if (!$column_exists) {
            echo "<p>🔄 Adding $column column...</p>";
            $sql = "ALTER TABLE targets ADD COLUMN $column $definition";
            $conn->exec($sql);
            echo "<p>✅ $column column added successfully!</p>";
        } else {
            echo "<p>✅ $column column already exists!</p>";
        }
    }
    
    echo "<h3>🎉 Quick Database Fix Complete!</h3>";
    echo "<p>Essential enhanced geolocation columns have been added.</p>";
    echo "<p><a href='test_enhanced_geolocation.php' class='btn btn-primary'>Test Enhanced Geolocation</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Database Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; padding: 2rem; background: #f8f9fa; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-tools"></i> Quick Database Fix</h1>
        <!-- PHP output will be displayed here -->
    </div>
</body>
</html>
