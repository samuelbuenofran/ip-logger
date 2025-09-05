<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

echo "<h2>Enhancing Geolocation Database Schema</h2>";

try {
    // Check if enhanced columns already exist
    $stmt = $conn->query("SHOW COLUMNS FROM targets LIKE 'confidence_score'");
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        echo "<p>🔄 Adding enhanced geolocation columns...</p>";
        
        // Add enhanced geolocation columns
        $enhanced_columns = [
            "ALTER TABLE targets ADD COLUMN confidence_score DECIMAL(5,2) DEFAULT NULL",
            "ALTER TABLE targets ADD COLUMN accuracy_meters INT DEFAULT NULL",
            "ALTER TABLE targets ADD COLUMN location_method VARCHAR(50) DEFAULT NULL",
            "ALTER TABLE targets ADD COLUMN precision_level VARCHAR(50) DEFAULT NULL",
            "ALTER TABLE targets ADD COLUMN data_sources JSON DEFAULT NULL",
            
            // Network analysis columns
            "ALTER TABLE targets ADD COLUMN network_type VARCHAR(50) DEFAULT NULL",
            "ALTER TABLE targets ADD COLUMN carrier_info VARCHAR(100) DEFAULT NULL",
            "ALTER TABLE targets ADD COLUMN vpn_detected BOOLEAN DEFAULT FALSE",
            "ALTER TABLE targets ADD COLUMN proxy_detected BOOLEAN DEFAULT FALSE",
            "ALTER TABLE targets ADD COLUMN tor_exit_node BOOLEAN DEFAULT FALSE",
            "ALTER TABLE targets ADD COLUMN data_center BOOLEAN DEFAULT FALSE",
            "ALTER TABLE targets ADD COLUMN mobile_network BOOLEAN DEFAULT FALSE",
            "ALTER TABLE targets ADD COLUMN isp_network BOOLEAN DEFAULT FALSE",
            
            // Device fingerprinting columns
            "ALTER TABLE targets ADD COLUMN browser VARCHAR(50) DEFAULT NULL",
            "ALTER TABLE targets ADD COLUMN browser_version VARCHAR(20) DEFAULT NULL",
            "ALTER TABLE targets ADD COLUMN os VARCHAR(50) DEFAULT NULL",
            "ALTER TABLE targets ADD COLUMN os_version VARCHAR(20) DEFAULT NULL",
            "ALTER TABLE targets ADD COLUMN platform VARCHAR(20) DEFAULT NULL",
            "ALTER TABLE targets ADD COLUMN screen_resolution VARCHAR(20) DEFAULT NULL",
            "ALTER TABLE targets ADD COLUMN timezone_offset INT DEFAULT NULL",
            "ALTER TABLE targets ADD COLUMN language VARCHAR(10) DEFAULT NULL",
            "ALTER TABLE targets ADD COLUMN touch_support BOOLEAN DEFAULT FALSE",
            
            // Historical analysis columns
            "ALTER TABLE targets ADD COLUMN historical_consistency_score DECIMAL(5,2) DEFAULT NULL",
            "ALTER TABLE targets ADD COLUMN location_variance_km DECIMAL(10,2) DEFAULT NULL",
            "ALTER TABLE targets ADD COLUMN most_frequent_location JSON DEFAULT NULL",
            
            // Refined coordinates columns
            "ALTER TABLE targets ADD COLUMN refined_latitude DECIMAL(10, 8) DEFAULT NULL",
            "ALTER TABLE targets ADD COLUMN refined_longitude DECIMAL(11, 8) DEFAULT NULL",
            "ALTER TABLE targets ADD COLUMN accuracy_improvement_meters INT DEFAULT NULL",
            "ALTER TABLE targets ADD COLUMN confidence_boost INT DEFAULT NULL"
        ];
        
        foreach ($enhanced_columns as $sql) {
            $conn->exec($sql);
        }
        
        echo "<p>✅ Enhanced geolocation columns added successfully!</p>";
        
        // Add indexes for better performance
        echo "<p>🔄 Adding performance indexes...</p>";
        
        $indexes = [
            "ALTER TABLE targets ADD INDEX idx_confidence_score (confidence_score)",
            "ALTER TABLE targets ADD INDEX idx_network_type (network_type)",
            "ALTER TABLE targets ADD INDEX idx_device_type (device_type)",
            "ALTER TABLE targets ADD INDEX idx_vpn_detected (vpn_detected)",
            "ALTER TABLE targets ADD INDEX idx_data_center (data_center)"
        ];
        
        foreach ($indexes as $sql) {
            $conn->exec($sql);
        }
        
        echo "<p>✅ Performance indexes added successfully!</p>";
        
        // Update device_type enum to include 'tablet'
        echo "<p>🔄 Updating device_type enum...</p>";
        $conn->exec("ALTER TABLE targets MODIFY COLUMN device_type ENUM('desktop', 'mobile', 'tablet') DEFAULT 'desktop'");
        echo "<p>✅ Device type enum updated successfully!</p>";
        
    } else {
        echo "<p>✅ Enhanced geolocation columns already exist!</p>";
    }
    
    // Check if links table has password_recovery_code column
    $stmt = $conn->query("SHOW COLUMNS FROM links LIKE 'password_recovery_code'");
    $recovery_column_exists = $stmt->fetch();
    
    if (!$recovery_column_exists) {
        echo "<p>🔄 Adding password recovery column...</p>";
        $conn->exec("ALTER TABLE links ADD COLUMN password_recovery_code VARCHAR(255) NULL");
        echo "<p>✅ Password recovery column added successfully!</p>";
    }
    
    echo "<h3>🎉 Database Enhancement Complete!</h3>";
    echo "<p>Your database now supports:</p>";
    echo "<ul>";
    echo "<li>✅ <strong>Multi-source geolocation</strong> with confidence scoring</li>";
    echo "<li>✅ <strong>Network analysis</strong> (VPN/Proxy detection, carrier info)</li>";
    echo "<li>✅ <strong>Device fingerprinting</strong> (browser, OS, screen resolution)</li>";
    echo "<li>✅ <strong>Historical data analysis</strong> for location consistency</li>";
    echo "<li>✅ <strong>Coordinate refinement</strong> with accuracy improvements</li>";
    echo "<li>✅ <strong>Enhanced device types</strong> (desktop, mobile, tablet)</li>";
    echo "<li>✅ <strong>Password recovery system</strong> for secure access</li>";
    echo "</ul>";
    
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Update your API keys in <code>includes/enhanced_geolocation.php</code></li>";
    echo "<li>Test the enhanced geolocation system</li>";
    echo "<li>Monitor confidence scores and accuracy improvements</li>";
    echo "</ol>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Enhancement - IP Logger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; padding: 2rem; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .info { color: #17a2b8; }
        code { background: #f8f9fa; padding: 0.2rem 0.4rem; border-radius: 0.25rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-database"></i> Database Enhancement Status</h4>
                    </div>
                    <div class="card-body">
                        <!-- PHP output will be displayed here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
