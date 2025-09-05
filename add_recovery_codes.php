<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

echo "<h2>Adding Recovery Codes to Existing Links</h2>";

try {
    // Check if password_recovery_code column already exists
    $stmt = $conn->query("SHOW COLUMNS FROM links LIKE 'password_recovery_code'");
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        // Add the password_recovery_code column
        $conn->exec("ALTER TABLE links ADD COLUMN password_recovery_code VARCHAR(255) NULL");
        echo "<p>✅ Added password_recovery_code column</p>";
    } else {
        echo "<p>✅ password_recovery_code column already exists</p>";
    }
    
    // Check if index already exists
    $stmt = $conn->query("SHOW INDEX FROM links WHERE Key_name = 'idx_password_recovery'");
    $index_exists = $stmt->fetch();
    
    if (!$index_exists) {
        // Add index for recovery codes
        $conn->exec("ALTER TABLE links ADD INDEX idx_password_recovery (password_recovery_code)");
        echo "<p>✅ Added index for password_recovery_code</p>";
    } else {
        echo "<p>✅ Index for password_recovery_code already exists</p>";
    }
    
    // Get all links that don't have recovery codes
    $stmt = $conn->query("SELECT id, short_code FROM links WHERE password_recovery_code IS NULL");
    $links = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($links)) {
        echo "<p>✅ All links already have recovery codes</p>";
    } else {
        echo "<p>Found " . count($links) . " links without recovery codes. Adding them now...</p>";
        
        $updated = 0;
        foreach ($links as $link) {
            $recovery_code = generateRandomString(12);
            $stmt = $conn->prepare("UPDATE links SET password_recovery_code = ? WHERE id = ?");
            $stmt->execute([$recovery_code, $link['id']]);
            $updated++;
            
            echo "<p>✅ Added recovery code for link {$link['short_code']}: <code>$recovery_code</code></p>";
        }
        
        echo "<p>✅ Successfully updated $updated links with recovery codes</p>";
    }
    
    echo "<h3>Migration Complete!</h3>";
    echo "<p><a href='index.php'>Return to Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Migration - IP Logger</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
        p { margin: 10px 0; }
    </style>
</head>
<body>
</body>
</html>
