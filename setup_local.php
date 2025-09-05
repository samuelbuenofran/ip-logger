<?php
/**
 * Local Setup Script for IP Logger
 * This script sets up the application for local testing
 */

echo "Setting up IP Logger for local testing...\n\n";

// Step 1: Create local database
echo "Step 1: Setting up local database...\n";

// Check if MySQL is available
$mysql_available = false;
try {
    $pdo = new PDO('mysql:host=localhost', 'root', '');
    $mysql_available = true;
    echo "✓ MySQL connection successful\n";
} catch (PDOException $e) {
    echo "✗ MySQL connection failed: " . $e->getMessage() . "\n";
    echo "Please make sure MySQL/MariaDB is running and accessible with user 'root' and no password\n";
    exit(1);
}

// Create database
try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS ip_logger_test");
    echo "✓ Database 'ip_logger_test' created\n";
} catch (PDOException $e) {
    echo "✗ Database creation failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 2: Update config file
echo "\nStep 2: Updating configuration...\n";

// Copy local config to main config
if (file_exists('config/config.local.php')) {
    copy('config/config.local.php', 'config/config.php');
    echo "✓ Configuration updated for local testing\n";
} else {
    echo "✗ Local config file not found\n";
    exit(1);
}

// Step 3: Create database tables
echo "\nStep 3: Creating database tables...\n";

try {
    require_once 'config/config.php';
    require_once 'config/database.php';
    
    $db = new Database();
    $conn = $db->getConnection();
    
    // Run the install script to create tables
    require_once 'install.php';
    
    echo "✓ Database tables created\n";
} catch (Exception $e) {
    echo "✗ Table creation failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Step 4: Test the setup
echo "\nStep 4: Testing setup...\n";

try {
    // Test database connection
    $stmt = $conn->query("SELECT COUNT(*) FROM links");
    $linkCount = $stmt->fetchColumn();
    echo "✓ Database connection test successful (Links: $linkCount)\n";
    
    // Test redirect.php
    if (file_exists('redirect.php')) {
        echo "✓ redirect.php exists\n";
    } else {
        echo "✗ redirect.php not found\n";
    }
    
    // Test .htaccess
    if (file_exists('.htaccess')) {
        echo "✓ .htaccess exists\n";
    } else {
        echo "✗ .htaccess not found\n";
    }
    
} catch (Exception $e) {
    echo "✗ Setup test failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Local setup completed successfully!\n";
echo "\nNext steps:\n";
echo "1. Make sure your web server (Apache/Nginx) is running\n";
echo "2. Make sure mod_rewrite is enabled\n";
echo "3. Access your application at: http://localhost/ip-logger/\n";
echo "4. Create a test link and try accessing it\n";
echo "\nFor production deployment, update config/config.php with your server credentials.\n";
?>
